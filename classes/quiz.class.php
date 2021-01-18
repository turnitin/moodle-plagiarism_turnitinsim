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
 * Helper class for plagiarism_turnitinsim component in quizzes.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2020 Turnitin
 * @author    David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for plagiarism_turnitinsim component in quizzes.
 */
class plagiarism_turnitinsim_quiz {

    /**
     * Get the text from the database for the submission.
     *
     * @param String $itemid The itemid for this submission.
     * @return mixed The text of the submission.
     * @throws dml_exception
     */
    public function get_onlinetext($itemid) {
        global $DB;

        $moodletextsubmission = $DB->get_record('question_attempts', array('id' => $itemid), 'responsesummary');

        if (isset($moodletextsubmission->responsesummary)) {
            return $moodletextsubmission->responsesummary;
        }
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

        $item = $DB->get_record_sql('SELECT DISTINCT(q.id) FROM {question_attempt_steps} s
                                    RIGHT JOIN {question_attempts} a
                                    ON s.questionattemptid = a.id
                                    RIGHT JOIN {quiz_attempts} q
                                    ON a.questionusageid = q.uniqueid
                                    WHERE q.quiz = ?
                                    AND s.userid = ?
                                    AND a.responsesummary = ?
                                    AND q.state = ?',
            array($params->moduleid, $params->userid, $params->onlinetext, 'finished')
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
     * Get the group id that a submission belongs to. - (N/A in quizzes).
     *
     * @param string $itemid The itemid for the submission.
     * @return int The group id.
     */
    public function get_groupid($itemid) {
        return null;
    }

    /**
     * Return whether the submission is a draft. Never the case with a quiz submission.
     *
     * @param string $itemid The itemid for the submission.
     * @return bool If the submission is a draft.
     */
    public function is_submission_draft($itemid) {
        return false;
    }

    /**
     * Return the due date so we can work out report generation time. Not applicable to quizzes.
     *
     * @param int $id The forum ID we want the due date for.
     * @return int The due date for the assignment.
     */
    public function get_due_date($id) {
        return 0;
    }

    /**
     * Determines whether the OR links in other posts should be seen. This is not applicable for quizzes.
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
        $cm = get_coursemodule_from_id('', $linkarray['cmid']);

        $eventdata = array();
        $eventdata['contextinstanceid'] = $linkarray['cmid'];

        $eventdata['eventtype'] = 'quiz_submitted';
        $eventdata['userid'] = $linkarray['userid'];
        $eventdata['objectid'] = $linkarray['area'];

        if (isset($linkarray['file'])) {
            $eventdata['other']['pathnamehashes'] = array($linkarray['file']->get_pathnamehash());
        } else {
            $params = new stdClass();
            $params->moduleid = $cm->instance;

            $params->userid = $linkarray['userid'];
            $params->onlinetext = $linkarray['content'];
        }
        if (isset($linkarray['userid'])) {
            $eventdata['relateduserid'] = $linkarray['userid'];
        }

        $eventdata['other']['modulename'] = $cm->modname;

        return $eventdata;
    }
}