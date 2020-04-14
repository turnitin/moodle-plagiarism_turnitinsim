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
 * Ajax functionality related to resending an errored submission to Turnitin.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__."/../../../config.php");
require_once(__DIR__."/../lib.php");
require_once( __DIR__ . '/../classes/submission.class.php' );
require_once( __DIR__ . '/../classes/request.class.php' );

require_login();

// Get any params passed in.
$action = required_param('action', PARAM_ALPHAEXT);
$submissionid = optional_param('submissionid', 0, PARAM_INT);

switch ($action) {
    case "resubmit_event":
        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey', 'error');
        }

        $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request(), $submissionid);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $tssubmission->settiiattempts(0);
        $tssubmission->settiiretrytime(0);
        $tssubmission->seterrormessage('');
        $tssubmission->update();

        break;
}
