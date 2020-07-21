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
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine
require_once(__DIR__."/../../config.php");
require_once(__DIR__."/lib.php");
require_once(__DIR__."/locallib.php");
require_once(__DIR__."/classes/callback.class.php");
require_once(__DIR__."/classes/logging_request.class.php");
require_once(__DIR__."/classes/logging_request_event_info.class.php");

$PAGE->set_context(context_system::instance());

$logger = new plagiarism_turnitinsim_logger();
$tscallback = new plagiarism_turnitinsim_callback();

// Get headers and body from request.
$reqheaders = array_change_key_case(plagiarism_turnitinsim_get_request_headers(), CASE_LOWER);

$requeststring = file_get_contents('php://input');
$params = (object)json_decode($requeststring, true);

// Generate expected secret.
$expectedsecret = $tscallback->expected_callback_signature($requeststring);

$pluginconfig = get_config('plagiarism_turnitinsim');

// Log out webhook request.
if ($pluginconfig->turnitinenablelogging) {
    $logger->info('-------- WEBHOOK START --------');
    $logger->info('WEBHOOK HEADERS: ', $reqheaders);

    $logger->info('WEBHOOK REQUEST: '.$requeststring);
    $logger->info('EXPECTED SIGNATURE: '.$expectedsecret);
}

// Verify that callback is genuine. Exit if not.
if ($expectedsecret !== $reqheaders['x-turnitin-signature']) {
    if ($pluginconfig->turnitinenablelogging) {
        $logger->error(get_string('webhookincorrectsignature', 'plagiarism_turnitinsim'));
    }

    $eventinfo = new plagiarism_turnitinsim_logging_request_event_info("callback.php", $reqheaders, $requeststring);
    $loggingrequest = new plagiarism_turnitinsim_logging_request('Webhook callback failed as signature is incorrect');
    $loggingrequest->send_error_to_turnitin(null, $eventinfo, true);

    echo get_string('webhookincorrectsignature', 'plagiarism_turnitinsim');
    exit;
}

// Handle Submission complete callback.
if ($reqheaders['x-turnitin-eventtype'] == TURNITINSIM_SUBMISSION_COMPLETE) {
    // Get Moodle submission id from Turnitin id.
    $submission = $DB->get_record_select('plagiarism_turnitinsim_sub', 'turnitinid = ?', array($params->id));
    $tssubmission = new plagiarism_turnitinsim_submission( new plagiarism_turnitinsim_request(), $submission->id );

    // If webhook comes after submission response then no need to handle it.
    if ($tssubmission->getstatus() == TURNITINSIM_SUBMISSION_STATUS_UPLOADED) {
        $tssubmission->handle_upload_response($params, $params->title);
    }

    // Request report to be generated if required.
    if ($tssubmission->gettogenerate() == 1 && $tssubmission->getgenerationtime() <= time()) {
        $tssubmission->request_turnitin_report_generation();
    }
}

// Handle Similarity complete callback.
if ($reqheaders['x-turnitin-eventtype'] == TURNITINSIM_SIMILARITY_COMPLETE ||
    $reqheaders['x-turnitin-eventtype'] == TURNITINSIM_SIMILARITY_UPDATED) {
    // Get Moodle submission id from Turnitin id.
    $submission = $DB->get_record_select('plagiarism_turnitinsim_sub', 'turnitinid = ?', array($params->submission_id));
    $tssubmission = new plagiarism_turnitinsim_submission( new plagiarism_turnitinsim_request(), $submission->id );

    $tssubmission->handle_similarity_response($params);
}

if ($pluginconfig->turnitinenablelogging) {
    $logger->info('-------- WEBHOOK END --------');
}
