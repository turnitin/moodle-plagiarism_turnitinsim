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
 * Notification for admin that testing webhook check has failed.
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitincheck\message;

defined('MOODLE_INTERNAL') || die();

class get_webhook_failure {
    /**
     * Send notification to site admins.
     */
    public function send_message() {
        $eventdata = new \core\message\message();

        $eventdata->component         = 'plagiarism_turnitincheck';
        $eventdata->name              = 'get_webhook_failure'; // This is the message name from messages.php.
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->subject           = get_string('getwebhookfailure:subject', 'plagiarism_turnitincheck');
        $eventdata->fullmessage       = get_string('getwebhookfailure:message', 'plagiarism_turnitincheck');
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = get_string('getwebhookfailure:message', 'plagiarism_turnitincheck');
        $eventdata->smallmessage      = '';
        $eventdata->notification      = 1; // This is only set to 0 for personal messages between users.
        $eventdata->courseid          = 0;

        // Get all site administrators and send message to them.
        $admins = get_admins();
        foreach ($admins as $admin) {
            $eventdata->userto = $admin;
            message_send($eventdata);
        }
    }
}
