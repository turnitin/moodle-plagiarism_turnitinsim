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
 * Helper class for plagiarism_turnitinsim component in workshops.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for plagiarism_turnitinsim component in workshops
 */
class plagiarism_turnitinsim_workshop {

    /**
     * Get the text from the database for the submission.
     *
     * @param String $itemid The itemid for this submission.
     * @return mixed The text of the submission.
     * @throws dml_exception
     */
    public function get_onlinetext($itemid) {
        global $DB;

        $submission = $DB->get_record('workshop_submissions', array('id' => $itemid), 'content');

        if (isset($submission->content)) {
            return $submission->content;
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

        $item = $DB->get_record_sql('SELECT id FROM {workshop_submissions}
                                    WHERE workshopid = ?
                                    AND authorid = ?
                                    AND content = ?',
                                    array($params->moduleid, $params->userid, $params->onlinetext)
        );

        return ($item) ? $item->id : 0;
    }

    /**
     * Get the actual author of the submission.
     *
     * @param int $userid The userid as given by Moodle.
     * @param int $relateduserid The relateduserid as given by Moodle.
     * @return int The authorid.
     */
    public function get_author($userid, $relateduserid) {
        return (!empty($relateduserid)) ? $relateduserid : $userid;
    }

    /**
     * Get the group id that a submission belongs to. - (N/A in workshops).
     *
     * @param string $itemid The itemid for the submission.
     * @return int The group id.
     */
    public function get_groupid($itemid) {
        return null;
    }

    /**
     * Return whether the submission is a draft. Never the case with a workshop submission.
     *
     * @param string $itemid The itemid for the submission.
     * @return bool If the submission is a draft.
     */
    public function is_submission_draft($itemid) {
        return false;
    }

    /**
     * Return the due date so we can work out report generation time. Not applicable to forums.
     *
     * @param int $id The forum ID we want the due date for.
     * @return int The due date for the assignment.
     */
    public function get_due_date($id) {
        return 0;
    }

    /**
     * Determines whether the OR links in other posts should be seen. This is not applicable for workshops.
     *
     * @param int $courseid The ID for this course.
     * @param int $userid The userid.
     * @return bool true if showing other posts links.
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
            $eventdata['objectid'] = $linkarray['file']->get_itemid();
        } else {
            $params = array('workshopid' => $cm->instance, 'authorid' => $linkarray['userid']);
            $moodlesubmission = $DB->get_record('workshop_submissions', $params);
            $eventdata['objectid'] = $moodlesubmission->id;
        }

        if (isset($linkarray['userid'])) {
            $eventdata['relateduserid'] = $linkarray['userid'];
        }

        $eventdata['other']['modulename'] = $cm->modname;

        if (isset($linkarray['content'])) {
            $eventdata['other']['content'] = $linkarray['content'];
        }

        return $eventdata;
    }
}