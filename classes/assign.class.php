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
 * Helper class for plagiarism_turnitinsim component in assignments
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class plagiarism_turnitinsim_assign {

    /**
     * Get the text from the database for the submission.
     *
     * @param $itemid
     * @return mixed
     */
    public function get_onlinetext($itemid) {
        global $DB;

        $moodletextsubmission = $DB->get_record('assignsubmission_onlinetext',
            array('submission' => $itemid), 'onlinetext');
        return $moodletextsubmission->onlinetext;
    }

    /**
     * Get the item id from the database for this submission.
     *
     * @param $params
     * @return mixed
     */
    public function get_itemid($params) {
        global $DB;

        $item = $DB->get_record_sql('SELECT O.submission FROM {assign_submission} S
                                    RIGHT JOIN {assignsubmission_onlinetext} O
                                    ON S.id = O.submission
                                    WHERE S.assignment = ?
                                    AND S.userid = ?
                                    AND O.onlinetext = ?',
                                    array($params->moduleid, $params->userid, $params->onlinetext)
        );

        return ($item) ? $item->submission : 0;
    }

    /**
     * Get the actual author of the submission.
     *
     * @param $userid
     * @param $relateduserid
     * @param $cm
     * @param $itemid
     */
    public function get_author($userid, $relateduserid, $cm, $itemid) {
        $author = (!empty($relateduserid)) ? $relateduserid : $userid;

        // The relateduserid will be null for an instructor submitting on behalf of a student in a group.
        // The best way round this is to set the author as the first student in the group.
        $editpermission = has_capability(
            'mod/assign:editothersubmission',
            context_module::instance($cm->id),
            $userid
        );

        if (empty($eventdata['relateduserid']) && $editpermission) {
            $groupid = $this->get_groupid($itemid);
            if (!empty($groupid)) {
                $author = $this->get_first_group_author($cm->course, $groupid);
            }
        }

        return $author;
    }

    /**
     * Get the group id that a submission belongs to.
     *
     * @param $itemid
     * @return int
     */
    public function get_groupid($itemid) {
        global $DB;

        $moodlesubmission = $DB->get_record('assign_submission', array('id' => $itemid), 'groupid');

        return (!empty($moodlesubmission->groupid)) ? $moodlesubmission->groupid : null;
    }

    /*
     * Related user ID will be NULL if an instructor submits on behalf of a student who is in a group.
     * To get around this, we get the group ID, get the group members and set the author as the first student in the group.

     * @param int $courseid - The course ID.
     * @param int $groupid - The ID of the Moodle group that we're getting from.
     * @return int $author The Moodle user ID that we'll be using for the author.
     */
    public function get_first_group_author($courseid, $groupid) {
        static $context;
        if (empty($context)) {
            $context = context_course::instance($courseid);
        }

        $groupmembers = groups_get_members($groupid, 'u.id', 'id ASC');
        foreach ($groupmembers as $author) {
            if (!has_capability('mod/assign:grade', $context, $author->id)) {
                return $author->id;
            }
        }

        return 0;
    }

    /**
     * Return whether the submission is a draft.
     *
     * @param $itemid
     * @return bool
     */
    public function is_submission_draft($itemid) {
        global $DB;

        $moodlesubmission = $DB->get_record('assign_submission', array('id' => $itemid), 'status');

        return ($moodlesubmission->status == 'draft') ? true : false;
    }

    /*
     * Get the assignment due date.
     * @param $id
     */
    public function get_due_date($id) {
        global $DB;

        $module = $DB->get_record('assign', array('id' => $id), 'duedate');

        return $module->duedate;
    }

    /*
     * Determines whether the OR links in other posts should be seen. This is not applicable for assignments.
     *
     * @param $courseid
     * @param $userid
     * @return bool
     */
    public function show_other_posts_links($courseid, $userid) {
        return true;
    }
}