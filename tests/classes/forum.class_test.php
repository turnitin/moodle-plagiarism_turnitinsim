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
 * Tests for forum module class for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/forum.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/utilities.php');

/**
 * Tests for forum module class for plagiarism_turnitinsim component.
 */
class forum_class_testcase extends advanced_testcase {

    /**
     * Sample text for testing a forum.
     */
    const TEST_FORUM_TEXT = 'This is a test forum post';

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        global $DB;

        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', 1234, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');

        // Set the features enabled.
        $featuresenabled = file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create instructor and enrol on course.
        $this->instructor = $this->getDataGenerator()->create_user();
        $instructorrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($this->instructor->id,
            $this->course->id,
            $instructorrole->id
        );

        // Create students and enrol on course.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->student1->id,
            $this->course->id,
            $studentrole->id
        );

        $this->student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->student2->id,
            $this->course->id,
            $studentrole->id
        );
    }

    /**
     * Test that get_onlinetext returns the correct text.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_get_onlinetext_returns_correct_text() {
        $this->resetAfterTest();

        // Create a forum.
        $record = new stdClass();
        $record->course = $this->course->id;
        $forum = $this->getDataGenerator()->create_module('forum', $record);

        // Add discussion to course.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->userid = $this->student1->id;
        $record->forum = $forum->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add post to discussion.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->userid = $this->student1->id;
        $record->forum = $forum->id;
        $record->discussion = $discussion->id;
        $record->message = self::TEST_FORUM_TEXT;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        $tsforum = new plagiarism_turnitinsim_forum();
        $result = $tsforum->get_onlinetext($post->id);

        $this->assertEquals($result, self::TEST_FORUM_TEXT);
    }

    /**
     * Test that online text returns false if no submission is found.
     */
    public function test_get_onlinetext_returns_false_if_no_text() {
        $this->resetAfterTest();

        $tsassign = new plagiarism_turnitinsim_forum();
        $result = $tsassign->get_onlinetext(1);

        $this->assertNull($result);
    }

    /**
     * Test that we get back the correct itemid when get_itemid is called.
     */
    public function test_get_itemid_returns_correct_itemid() {
        $this->resetAfterTest();

        // Create a forum.
        $record = new stdClass();
        $record->course = $this->course->id;
        $forum = $this->getDataGenerator()->create_module('forum', $record);

        // Add discussion to course.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->userid = $this->student1->id;
        $record->forum = $forum->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add post to discussion.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->userid = $this->student1->id;
        $record->forum = $forum->id;
        $record->discussion = $discussion->id;
        $record->message = self::TEST_FORUM_TEXT;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        // Get itemid.
        $tsforum = new plagiarism_turnitinsim_forum();
        $params = new stdClass();
        $params->moduleid = $forum->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_FORUM_TEXT;
        $result = $tsforum->get_itemid($params);

        $this->assertEquals($result, $post->id);
    }

    /**
     * Test that we get back 0 when get_itemid is called if there is no submission.
     */
    public function test_get_itemid_returns_zero_if_no_submission() {
        $this->resetAfterTest();

        // Create a forum.
        $record = new stdClass();
        $record->course = $this->course->id;
        $forum = $this->getDataGenerator()->create_module('forum', $record);

        // Get itemid.
        $tsforum = new plagiarism_turnitinsim_forum();
        $params = new stdClass();
        $params->moduleid = $forum->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_FORUM_TEXT;
        $result = $tsforum->get_itemid($params);

        $this->assertEquals($result, 0);
    }

    /**
     * Test that getting the author returns the related user id.
     */
    public function test_get_author_returns_related_user_id() {
        $this->resetAfterTest();

        // Test that get author returns student2 as the author.
        $tsforum = new plagiarism_turnitinsim_forum();
        $response = $tsforum->get_author($this->student1->id, $this->student2->id);
        $this->assertEquals($this->student2->id, $response);

        // Test that get author returns student1 as the author because relateduserid is empty.
        $tsforum = new plagiarism_turnitinsim_forum();
        $response = $tsforum->get_author($this->student1->id, 0);
        $this->assertEquals($this->student1->id, $response);
    }

    /**
     * Test that is submission draft returns expected value.
     */
    public function test_is_submission_draft() {
        $this->resetAfterTest();

        $tsforum = new plagiarism_turnitinsim_forum();
        $response = $tsforum->is_submission_draft(0);
        $this->assertEquals(false, $response);
    }

    /**
     * Test that get due date returns expected value.
     */
    public function test_get_due_date() {
        $this->resetAfterTest();

        $tsforum = new plagiarism_turnitinsim_forum();
        $response = $tsforum->get_due_date(0);
        $this->assertEquals(0, $response);
    }

    /**
     * Test that show other posts links for an instructor is true.
     */
    public function test_show_other_posts_links_instructor() {
        $this->resetAfterTest();

        // Login as instructor.
        $this->setUser($this->instructor);

        $tsforum = new plagiarism_turnitinsim_forum();
        $response = $tsforum->show_other_posts_links($this->course->id, $this->instructor->id);
        $this->assertEquals(true, $response);
    }

    /**
     * Test that show other posts links for a student is false.
     */
    public function test_show_other_posts_links_student() {
        $this->resetAfterTest();

        // Login as student.
        $this->setUser($this->student1);

        $tsforum = new plagiarism_turnitinsim_forum();
        $response = $tsforum->show_other_posts_links($this->course->id, $this->student2->id);
        $this->assertEquals(false, $response);
    }

    /**
     * Test that the correct event data is returned when handling a file submission for a forum.
     */
    public function test_create_submission_event_data_returns_correct_data_for_file_submission() {
        $this->resetAfterTest();

        // Create a forum.
        $record = new stdClass();
        $record->course = $this->course->id;
        $forum = $this->getDataGenerator()->create_module('forum', $record);

        $cm = get_coursemodule_from_instance('forum', $forum->id);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Add discussion to course.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->userid = $this->student1->id;
        $record->forum = $forum->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add post to discussion.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->userid = $this->student1->id;
        $record->forum = $forum->id;
        $record->discussion = $discussion->id;
        $record->message = self::TEST_FORUM_TEXT;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        // Get itemid.
        $tsforum = new plagiarism_turnitinsim_forum();
        $params = new stdClass();
        $params->moduleid = $forum->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_FORUM_TEXT;
        $result = $tsforum->get_itemid($params);

        $this->assertEquals($result, $post->id);

        $file = create_test_file($post->id, $usercontext->id, 'mod_forum', 'submissions');

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "file" => $file,
            "content" => ''
        );

        $tsforum = new plagiarism_turnitinsim_forum();
        $response = $tsforum->create_submission_event_data($linkarray);

        $this->assertEquals('assessable_submitted', $response['eventtype']);
        $this->assertEquals($cm->id, $response['contextinstanceid']);
        $this->assertEquals($this->student1->id, $response['userid']);
        $this->assertEquals(array($file->get_pathnamehash()), $response['other']['pathnamehashes']);
        $this->assertEquals($post->id, $response['objectid']);
        $this->assertEquals($this->student1->id, $response['relateduserid']);
        $this->assertEquals('forum', $response['other']['modulename']);
        $this->assertEmpty($response['other']['content']);
    }

    /**
     * Test that the correct event data is returned when handling a text submission for a forum.
     */
    public function test_create_submission_event_data_returns_correct_data_for_text_submission() {
        $this->resetAfterTest();

        // Create a forum.
        $record = new stdClass();
        $record->course = $this->course->id;
        $forum = $this->getDataGenerator()->create_module('forum', $record);

        $cm = get_coursemodule_from_instance('forum', $forum->id);

        // Log student in.
        $this->setUser($this->student1);

        // Add discussion to course.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->userid = $this->student1->id;
        $record->forum = $forum->id;
        $discussion = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add post to discussion.
        $record = new stdClass();
        $record->course = $this->course->id;
        $record->userid = $this->student1->id;
        $record->forum = $forum->id;
        $record->discussion = $discussion->id;
        $record->message = self::TEST_FORUM_TEXT;
        $post = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        // Get itemid.
        $tsforum = new plagiarism_turnitinsim_forum();
        $params = new stdClass();
        $params->moduleid = $forum->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_FORUM_TEXT;
        $result = $tsforum->get_itemid($params);

        $this->assertEquals($result, $post->id);

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "content" => self::TEST_FORUM_TEXT
        );

        $tsforum = new plagiarism_turnitinsim_forum();
        $response = $tsforum->create_submission_event_data($linkarray);

        $this->assertEquals('assessable_submitted', $response['eventtype']);
        $this->assertEquals($cm->id, $response['contextinstanceid']);
        $this->assertEquals($this->student1->id, $response['userid']);
        $this->assertEquals($post->id, $response['objectid']);
        $this->assertEquals($this->student1->id, $response['relateduserid']);
        $this->assertEquals('forum', $response['other']['modulename']);
        $this->assertEquals(self::TEST_FORUM_TEXT, $response['other']['content']);
    }
}