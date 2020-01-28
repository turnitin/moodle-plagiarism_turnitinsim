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
 * Ajax functionality related to Turnitin Cloud Viewer.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__."/../../../config.php");
require_once(__DIR__."/../lib.php");

require_login();

// Get any params passed in.
$action = required_param('action', PARAM_ALPHAEXT);
$submissionid = optional_param('submissionid', 0, PARAM_INT);

if (!confirm_sesskey()) {
    throw new moodle_exception('invalidsesskey', 'error');
}

switch ($action) {
    case "request_cv_launch":
        $tssubmission = new plagiarism_turnitinsim_submission( new plagiarism_turnitinsim_request(), $submissionid);
        echo $tssubmission->request_cv_launch_url();

        break;
}