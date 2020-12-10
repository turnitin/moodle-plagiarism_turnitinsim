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
 * Tests for quiz module class for plagiarism_turnitinsim component
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2020 Turnitin
 * @author    David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/quiz.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/utilities.php');
require_once($CFG->dirroot . '/mod/workshop/locallib.php');
require_once($CFG->dirroot . '/mod/workshop/tests/fixtures/testable.php');

/**
 * Tests for quiz module class for plagiarism_turnitinsim component
 */
class quiz_class_testcase extends advanced_testcase {

    /**
     * Sample text used for unit testing a quiz.
     */
    const QUIZ_ANSWER_TEXT = 'Generic quiz answer.';

    /**
     * Default module ID used when testing a quiz.
     */
    const MODULE_ID = 1;

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', 1234, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');

        // Set the features enabled.
        $featuresenabled = file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');

        $this->student1 = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();
    }

    /**
     * Test that get_onlinetext returns the correct text.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function test_get_onlinetext_returns_correct_text() {
        global $DB;

        $this->resetAfterTest();

        // Create a question attempt.
        $qattempt = $this->create_question_attempt();

        $tsquiz = new plagiarism_turnitinsim_quiz();
        $result = $tsquiz->get_onlinetext($qattempt->id);

        $this->assertEquals($result, self::QUIZ_ANSWER_TEXT);

    }

    /**
     * Test that online text returns false if no text is found.
     */
    public function test_get_onlinetext_returns_false_if_no_text() {
        $this->resetAfterTest();

        $tsassign = new plagiarism_turnitinsim_quiz();
        $result = $tsassign->get_onlinetext(2);

        $this->assertNull($result);
    }

    /**
     * Test that we get back the correct itemid when get_itemid is called.
     */
    public function test_get_itemid_returns_correct_itemid() {
        global $DB;

        $this->resetAfterTest();

        // Create a question attempt, step and quiz attempt.
        $questionattempt = $this->create_question_attempt();
        $this->create_question_attempt_step($questionattempt->id);
        $quizattempt = $this->create_quiz_attempt($questionattempt->questionusageid);

        $tsquiz = new plagiarism_turnitinsim_quiz();

        // Create params to call get_item with.
        $params = new stdClass();
        $params->moduleid = self::MODULE_ID;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::QUIZ_ANSWER_TEXT;

        $result = $tsquiz->get_itemid($params);
        $this->assertEquals($result, $quizattempt->id);
    }

    /**
     * Test that we get back 0 if there is no answer.
     */
    public function test_get_itemid_returns_zero_if_no_submission() {
        global $DB;

        $this->resetAfterTest();

        // Create a question attempt, step and quiz attempt.
        $questionattempt = $this->create_question_attempt();
        $this->create_question_attempt_step($questionattempt->id);
        $this->create_quiz_attempt($questionattempt->questionusageid);

        $tsquiz = new plagiarism_turnitinsim_quiz();

        // Create params to call get_item with.
        $params = new stdClass();
        $params->moduleid = self::MODULE_ID;
        $params->userid = $this->student1->id;
        $params->onlinetext = null;

        $result = $tsquiz->get_itemid($params);
        $this->assertEquals($result, 0);
    }

    /**
     * Test that getting the author returns the related user id.
     */
    public function test_get_author_returns_related_user_id() {
        $this->resetAfterTest(true);

        // Test that get author returns user2 as the author.
        $tsquiz = new plagiarism_turnitinsim_quiz();
        $response = $tsquiz->get_author($this->student1->id, $this->student2->id);
        $this->assertEquals($this->student2->id, $response);

        // Test that get author returns user1 as the author because relateduserid is empty.
        $tsquiz = new plagiarism_turnitinsim_quiz();
        $response = $tsquiz->get_author($this->student1->id, null);
        $this->assertEquals($this->student1->id, $response);
    }

    /**
     * Test that get_groupid returns expected value.
     */
    public function test_get_groupid() {
        $this->resetAfterTest();

        $tsquiz = new plagiarism_turnitinsim_quiz();
        $response = $tsquiz->get_groupid(1);
        $this->assertEquals(null, $response);
    }

    /**
     * Test that is submission draft returns expected value.
     */
    public function test_is_submission_draft() {
        $this->resetAfterTest();

        $tsquiz = new plagiarism_turnitinsim_quiz();
        $response = $tsquiz->is_submission_draft(0);
        $this->assertEquals(false, $response);
    }

    /**
     * Test that get due date returns expected value.
     */
    public function test_get_due_date() {
        $this->resetAfterTest();

        $tsquiz = new plagiarism_turnitinsim_quiz();
        $response = $tsquiz->get_due_date(0);
        $this->assertEquals(0, $response);
    }

    /**
     * Test that show other posts links returns expected value.
     */
    public function test_show_other_posts_links() {
        $this->resetAfterTest();

        $tsquiz = new plagiarism_turnitinsim_quiz();
        $response = $tsquiz->show_other_posts_links(0, 0);
        $this->assertEquals(true, $response);
    }

    /**
     * Test that the correct event data is returned when handling a file submission for a quiz.
     */
    public function test_create_submission_event_data_returns_correct_data_for_file_submission() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($this->student1->id,
            $course->id,
            $studentrole->id);

        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course));
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

        // Log new user in.
        $this->setUser($this->student1);

        $file = create_test_file(1, 1, 'mod_quiz', 'submissions');

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "file" => $file,
            "content" => '',
            "area" => 1
        );

        $tsquiz = new plagiarism_turnitinsim_quiz();
        $response = $tsquiz->create_submission_event_data($linkarray);

        $this->assertEquals('quiz_submitted', $response['eventtype']);
        $this->assertEquals($cm->id, $response['contextinstanceid']);
        $this->assertEquals($this->student1->id, $response['userid']);
        $this->assertEquals(array($file->get_pathnamehash()), $response['other']['pathnamehashes']);
        $this->assertEquals($linkarray['area'], $response['objectid']);
        $this->assertEquals($this->student1->id, $response['relateduserid']);
        $this->assertEquals('quiz', $response['other']['modulename']);
    }

    /**
     * Test that the correct event data is returned when handling a text submission for a quiz.
     */
    public function test_create_submission_event_data_returns_correct_data_for_text_submission() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($this->student1->id,
            $course->id,
            $studentrole->id);

        $quiz = $this->getDataGenerator()->create_module('quiz', array('course' => $course));
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

        $this->setUser($this->student1);

        // Create a question attempt, step and quiz attempt.
        $questionattempt = $this->create_question_attempt();
        $this->create_question_attempt_step($questionattempt->id);
        $this->create_quiz_attempt($questionattempt->questionusageid);

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "content" => self::QUIZ_ANSWER_TEXT,
            "area" => 1
        );

        $tsquiz = new plagiarism_turnitinsim_quiz();

        $response = $tsquiz->create_submission_event_data($linkarray);
        $this->assertEquals('quiz_submitted', $response['eventtype']);
        $this->assertEquals($cm->id, $response['contextinstanceid']);
        $this->assertEquals($this->student1->id, $response['userid']);
        $this->assertEquals($linkarray['area'], $response['objectid']);
        $this->assertEquals($this->student1->id, $response['relateduserid']);
        $this->assertEquals('quiz', $response['other']['modulename']);
    }

    /**
     * Helper method to create a question attempt.
     *
     * @return stdClass
     * @throws dml_exception
     */
    public function create_question_attempt() {
        global $DB;

        $attempt = new stdClass();
        $attempt->questionusageid = 1;
        $attempt->slot = 1;
        $attempt->questionid = 1;
        $attempt->maxmark = 100;
        $attempt->minfraction = 50;
        $attempt->timemodified = time();
        $attempt->responsesummary = self::QUIZ_ANSWER_TEXT;
        $attempt->id = $DB->insert_record('question_attempts', $attempt);

        return $attempt;
    }

    /**
     * Helper method to create a question attempt step.
     *
     * @param int $questionattemptid - The ID of the question attempt.
     * @throws dml_exception
     */
    public function create_question_attempt_step($questionattemptid) {
        global $DB;

        $step = new stdClass();
        $step->questionattemptid = $questionattemptid;
        $step->sequencenumber = 1;
        $step->state = 1;
        $step->fraction = 100;
        $step->timecreated = time();
        $step->userid = $this->student1->id;
        $step->id = $DB->insert_record('question_attempt_steps', $step);

        return $step;
    }

    /**
     * Helper method to create a quiz attempt.
     *
     * @param int $questionusageid - A unique ID that maps to the questionusageid for a question attempt..
     * @throws dml_exception
     */
    public function create_quiz_attempt($questionusageid) {
        global $DB;

        $quizattempt = new stdClass();
        $quizattempt->quiz = self::MODULE_ID;
        $quizattempt->userid = $this->student1->id;
        $quizattempt->attempt = 1;
        $quizattempt->uniqueid = $questionusageid;
        $quizattempt->layout = '1,0';
        $quizattempt->currentpage = 0;
        $quizattempt->preview = 0;
        $quizattempt->state = 'finished';
        $quizattempt->timestart = time();
        $quizattempt->timefinish = time();
        $quizattempt->timemodified = time();
        $quizattempt->timemodifiedoffline = time();
        $quizattempt->id = $DB->insert_record('quiz_attempts', $quizattempt);

        return $quizattempt;
    }
}