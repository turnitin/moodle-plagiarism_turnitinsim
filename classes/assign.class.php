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

/**
 * Helper class for plagiarism_turnitinsim component in assignments
 */
class plagiarism_turnitinsim_assign {

    /**
     * Get the text from the database for the submission.
     *
     * @param String $itemid The itemid for this submission.
     * @return mixed The text of the submission.
     * @throws dml_exception
     */
    public function get_onlinetext($itemid) {
        global $DB;

        $moodletextsubmission = $DB->get_record('assignsubmission_onlinetext',
            array('submission' => $itemid), 'onlinetext');

        if (isset($moodletextsubmission->onlinetext)) {
            return $moodletextsubmission->onlinetext;
        }

        return null;
    }

    /**
     * Get the item id from the database for this submission.
     *
     * @param object $params The params to call the DB with.
     * @return int The itemid.
     * @throws dml_exception
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
     * @param int $userid The userid as given by Moodle.
     * @param int $relateduserid The relateduserid as given by Moodle.
     * @param object $cm The course module.
     * @param string $itemid The itemid for this submission.
     * @return int The authorid.
     * @throws coding_exception
     * @throws dml_exception
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
     * @param string $itemid The itemid for the submission.
     * @return int The group id.
     * @throws dml_exception
     */
    public function get_groupid($itemid) {
        global $DB;

        $moodlesubmission = $DB->get_record('assign_submission', array('id' => $itemid), 'groupid');

        return (!empty($moodlesubmission->groupid)) ? $moodlesubmission->groupid : null;
    }

    /**
     * Related user ID will be NULL if an instructor submits on behalf of a student who is in a group.
     * To get around this, we get the group ID, get the group members and set the author as the first student in the group.

     * @param int $courseid - The course ID.
     * @param int $groupid - The ID of the Moodle group that we're getting from.
     * @return int $author The Moodle user ID that we'll be using for the author.
     * @throws coding_exception
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
     * @param string $itemid The itemid for the submission.
     * @return bool If the submission is a draft.
     * @throws dml_exception
     */
    public function is_submission_draft($itemid) {
        global $DB;

        $moodlesubmission = $DB->get_record('assign_submission', array('id' => $itemid), 'status');

        return ($moodlesubmission->status == 'draft') ? true : false;
    }

    /**
     * Get the assignment due date.
     *
     * @param int $id The assignment ID we want the due date for.
     * @return mixed The due date for the assignment.
     * @throws dml_exception
     */
    public function get_due_date($id) {
        global $DB;

        $module = $DB->get_record('assign', array('id' => $id), 'duedate');

        return $module->duedate;
    }

    /**
     * Determines whether the OR links in other posts should be seen. This is not applicable for assignments.
     *
     * @param int $courseid The ID for this course.
     * @param int $userid The userid.
     * @return bool true if showing other links.
     */
    public function show_other_posts_links($courseid, $userid) {
        return true;
    }

    /**
     * Create the submission event data needed to queue a submission if Turnitin is enabled after a submission.
     *
     * @param array $linkarray Data passed by Moodle that belongs to a submission.
     * @return array Event data.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function create_submission_event_data($linkarray) {
        global $DB;

        $cm = get_coursemodule_from_id('', $linkarray['cmid']);

        $eventdata = array();
        $eventdata['contextinstanceid'] = $linkarray['cmid'];

        $eventdata['eventtype'] = 'assessable_submitted';
        $eventdata['userid'] = $linkarray['userid'];

        if (isset($linkarray['file'])) {
            $eventdata['other']['pathnamehashes'] = array($linkarray['file']->get_pathnamehash());

            $params = array('id' => $linkarray['file']->get_itemid());
        } else {
            $params = array('assignment' => $cm->instance, 'userid' => $linkarray['userid'], 'groupid' => 0);
        }

        $moodlesubmission = $DB->get_record('assign_submission', $params);
        $eventdata['objectid'] = $moodlesubmission->id;
        $eventdata['relateduserid'] = $moodlesubmission->userid;
        $eventdata['other']['modulename'] = $cm->modname;

        if (isset($linkarray['content'])) {
            $eventdata['other']['content'] = $linkarray['content'];
        }

        return $eventdata;
    }
}