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
 * Ajax functionality for handling the Turnitin EULA response.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__."/../../../config.php");
require_once(__DIR__."/../lib.php");

require_login();

if (!confirm_sesskey()) {
    throw new moodle_exception('invalidsesskey', 'error');
}

// Get any params passed in.
$action = required_param('action', PARAM_ALPHAEXT);
$contextid = optional_param('contextid', 0, PARAM_INT);

$tsrequest = new plagiarism_turnitinsim_request();

switch ($action) {
    case "accept_eula":
        // Get current user record.
        $user = $DB->get_record('plagiarism_turnitinsim_users', array('userid' => $USER->id));

        // Update EULA accepted version and timestamp for user.
        $data = new stdClass();
        $data->id = $user->id;
        $data->lasteulaaccepted = get_config('plagiarism_turnitinsim', 'turnitin_eula_version');
        $data->lasteulaacceptedtime = time();
        $lang = $tsrequest->get_language();
        $data->lasteulaacceptedlang = $lang->localecode;
        $DB->update_record('plagiarism_turnitinsim_users', $data);

        // If we have a context id then this is an instructor. So we update current submissions.
        if (!empty($contextid)) {

            // Get all submissions in this context.
            $context = context::instance_by_id($contextid);
            $submissions = $DB->get_records(
                'plagiarism_turnitinsim_sub',
                array(
                    'cm'        => $context->instanceid,
                    'status'    => TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED,
                    'submitter' => $USER->id
                )
            );

            // Set each paper in this module submitted by this user to queued.
            foreach ($submissions as $submission) {
                $data = new stdClass();
                $data->id     = $submission->id;
                $data->status = TURNITINSIM_SUBMISSION_STATUS_QUEUED;
                $data->cm     = $context->instanceid;

                $DB->update_record('plagiarism_turnitinsim_sub', $data);
            }
        }
        echo json_encode(["success" => true]);

        break;
}
