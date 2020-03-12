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
 * Extra library for plagiarism_turnitinsim component
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/plagiarism/turnitinsim/classes/task.class.php');

/**
 * Wrapper function for where PHP's getallheaders() function doesn't exist
 *
 * return array
 */
function plagiarism_turnitinsim_get_request_headers() {
    if (!function_exists('getallheaders')) {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    return getallheaders();
}

/**
 * Scheduled Task to request and retrieve report scores from Turnitin.
 */
function plagiarism_turnitinsim_task_get_reports() {
    $tstask = new plagiarism_turnitinsim_task();
    $tstask->get_reports();
}

/**
 * Scheduled Task to send submissions to Turnitin.
 */
function plagiarism_turnitinsim_task_send_submissions() {
    $tstask = new plagiarism_turnitinsim_task();
    $tstask->send_queued_submissions();
}

/**
 * Scheduled Task to update local configuration from Turnitin.
 */
function plagiarism_turnitinsim_task_admin_update() {
    $tstask = new plagiarism_turnitinsim_task();
    $tstask->admin_update();
}
