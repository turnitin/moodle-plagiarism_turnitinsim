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
 * Instructor Digital Receipt for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitinsim\message;

defined('MOODLE_INTERNAL') || die();

/**
 * Instructor Digital Receipt for plagiarism_turnitinsim component
 */
class receipt_instructor {

    /**
     * Send digital receipt to instructor. This message must preserve the anonymity of a submission.
     *
     * @param array $input - used to build message
     * @return string
     * @throws \coding_exception
     */
    public function build_message($input) {
        $message = new \stdClass();
        $message->submission_title = $input['submission_title'];
        $message->module_name = $input['module_name'];
        $message->course_fullname = $input['course_fullname'];
        $message->submission_date = $input['submission_date'];
        $message->submission_id = $input['submission_id'];

        return get_string('receiptsinstructor:message', 'plagiarism_turnitinsim', $message);
    }

    /**
     * Send digital receipt to instructors.
     *
     * @param array $instructors The instructors to send the receipt message to.
     * @param string $message The message to send.
     * @param int $courseid The ID for the course the submission is on.
     * @throws \coding_exception
     */
    public function send_message($instructors, $message, $courseid) {
        $eventdata = new \core\message\message();

        $eventdata->component         = 'plagiarism_turnitinsim';
        $eventdata->name              = 'digital_receipt_instructor'; // This is the message name from messages.php.
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->subject           = get_string('receiptsinstructor:subject', 'plagiarism_turnitinsim');
        $eventdata->fullmessage       = $message;
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml   = $message;
        $eventdata->smallmessage      = '';
        $eventdata->notification      = 1; // This is only set to 0 for personal messages between users.
        $eventdata->courseid          = $courseid;

        foreach ($instructors as $instructor) {
            $eventdata->userto = $instructor->id;
            message_send($eventdata);
        }
    }
}
