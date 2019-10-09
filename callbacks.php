<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Endpoint for handling callbacks from Turnitin.
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
require_once(__DIR__."/../../config.php");
require_once(__DIR__."/lib.php");
require_once(__DIR__."/locallib.php");
require_once(__DIR__."/classes/tccallback.class.php");

$PAGE->set_context(context_system::instance());

$logger = new tclogger();
$tccallback = new tccallback();

// Get headers and body from request.
$reqheaders = plagiarism_turnitincheck_get_request_headers();
// There is a strange anomaly with the headers on different environments.
if (isset($reqheaders['X-Turnitin-Eventtype'])) {
    $reqheaders['X-Turnitin-EventType'] = $reqheaders['X-Turnitin-Eventtype'];
}

$requeststring = file_get_contents('php://input');
$params = (object)json_decode($requeststring, true);

// Generate expected secret.
$expectedsecret = $tccallback->expected_callback_signature($requeststring);

$pluginconfig = get_config('plagiarism');

// Log out webhook request.
if ($pluginconfig->turnitinenablelogging) {
    $logger->info('-------- WEBHOOK START --------');
    $logger->info('WEBHOOK HEADERS: ', $reqheaders);

    $logger->info('WEBHOOK REQUEST: '.$requeststring);
    $logger->info('EXPECTED SIGNATURE: '.$expectedsecret);
}

// Verify that callback is genuine. Exit if not.
if ($expectedsecret !== $reqheaders['X-Turnitin-Signature']) {
    if ($pluginconfig->turnitinenablelogging) {
        $logger->error(get_string('webhookincorrectsignature', 'plagiarism_turnitincheck'));
    }

    echo get_string('webhookincorrectsignature', 'plagiarism_turnitincheck');
    exit;
}

// Handle Submission complete callback.
if ($reqheaders['X-Turnitin-EventType'] == SUBMISSION_COMPLETE) {
    // Get Moodle submission id from Turnitin id.
    $submission = $DB->get_record_select('plagiarism_turnitincheck_sub', 'turnitinid = ?', array($params->id));
    $tcsubmission = new tcsubmission( new tcrequest(), $submission->id );

    // If webhook comes after submission response then no need to handle it.
    if ($tcsubmission->getstatus() == TURNITINCHECK_SUBMISSION_STATUS_UPLOADED) {
        $tcsubmission->handle_upload_response($params, $params->title);
    }

    // Request report to be generated if required.
    if ($tcsubmission->gettogenerate() == 1 && $tcsubmission->getgenerationtime() <= time()) {
        $tcsubmission->request_turnitin_report_generation();
    }
}

// Handle Similarity complete callback.
if ($reqheaders['X-Turnitin-EventType'] == SIMILARITY_COMPLETE || $reqheaders['X-Turnitin-EventType'] == SIMILARITY_UPDATED) {
    // Get Moodle submission id from Turnitin id.
    $submission = $DB->get_record_select('plagiarism_turnitincheck_sub', 'turnitinid = ?', array($params->submission_id));
    $tcsubmission = new tcsubmission( new tcrequest(), $submission->id );

    $tcsubmission->handle_similarity_response($params);
}

$logger->info('-------- WEBHOOK END --------');
