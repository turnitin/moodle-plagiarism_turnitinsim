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
 * Helper class for plagiarism_turnitinsim component in forums
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class plagiarism_turnitinsim_forum {

    /**
     * Get the text from the database for the forum post.
     *
     * @param $itemid
     * @return mixed
     */
    public function get_onlinetext($itemid) {
        global $DB;

        $forumpost = $DB->get_record('forum_posts', array('id' => $itemid), 'message');
        return $forumpost->message;
    }

    /**
     * Get the item id from the database for this submission.
     *
     * @param $params
     * @return $postid
     */
    public function get_itemid($params) {
        global $DB;

        $item = $DB->get_record_sql('SELECT FP.id FROM {forum_posts} FP
                                    RIGHT JOIN {forum_discussions} FD
                                    ON FD.id = FP.discussion
                                    WHERE FD.forum = ?
                                    AND FP.userid = ?
                                    AND FP.message = ?',
                                    array($params->moduleid, $params->userid, $params->onlinetext)
        );

        return ($item) ? $item->id : 0;
    }

    /**
     * Get the actual author of the submission.
     *
     * @param $userid
     * @param $relateduserid
     * @param $cmid
     */
    public function get_author($userid, $relateduserid, $cmid, $itemid) {
        return (!empty($relateduserid)) ? $relateduserid : $userid;
    }

    /**
     * Get the group id that a submission belongs to - (N/A in forums).
     *
     * @param $itemid
     * @return null
     */
    public function get_groupid($itemid) {
        return null;
    }

    /**
     * Return whether the submission is a draft. Never the case with a forum submission.
     *
     * @param $itemid
     * @return bool
     */
    public function is_submission_draft($itemid) {
        return false;
    }

    /**
     * Return the due date so we can work out report generation time. Not applicable to forums.
     *
     * @param $id
     * @return int
     */
    public function get_due_date($id) {
        return 0;
    }

    /*
     * Determines whether the OR links in other posts should be seen.
     *
     * @param $courseid
     * @param $userid
     * @return bool
     */
    public function show_other_posts_links($courseid, $userid) {
        global $USER;

        static $context;
        if (empty($context)) {
            $context = context_course::instance($courseid);
        }

        if (has_capability('plagiarism/turnitinsim:viewfullreport', $context) || $USER->id == $userid) {
            return true;
        }

        return false;
    }
}