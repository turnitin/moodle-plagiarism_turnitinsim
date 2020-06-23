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
 * Configuration for message providers for sending notifications.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$messageproviders = array (
    // Notify student with their digital receipt.
    'digital_receipt_student' => array (),
    // Notify instructors with their copy of the digital receipt.
    'digital_receipt_instructor' => array (
        'capability'  => 'plagiarism/turnitinsim:viewfullreport'
    ),
    // Notify administrators if the test webhook check fails.
    'get_webhook_failure' => array (
        'capability'  => 'moodle/site:config'
    ),
    // Notify all Turnitin users with a link to accept the new EULA.
    'new_eula' => array (
        'defaults' => [
            'popup' => MESSAGE_PERMITTED,
            'email' => MESSAGE_PERMITTED
        ],
    )
);