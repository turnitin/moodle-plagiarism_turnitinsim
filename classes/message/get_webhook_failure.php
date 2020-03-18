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
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitinsim\message;

defined('MOODLE_INTERNAL') || die();

/**
 * Notification for admin that testing webhook check has failed.
 */
class get_webhook_failure {
    /**
     * Send notification to site admins.
     */
    public function send_message() {
        $eventdata = new \core\message\message();

        $eventdata->component         = 'plagiarism_turnitinsim';
        $eventdata->name              = 'get_webhook_failure'; // This is the message name from messages.php.
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->subject           = get_string('getwebhookfailure:subject', 'plagiarism_turnitinsim');
        $eventdata->fullmessage       = get_string('getwebhookfailure:message', 'plagiarism_turnitinsim');
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = get_string('getwebhookfailure:message', 'plagiarism_turnitinsim');
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
