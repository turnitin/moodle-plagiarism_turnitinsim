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
 * Tests for assign module class for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/assign.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/utilities.php');

/**
 * Tests for assign module class for plagiarism_turnitinsim component.
 */
class assign_class_testcase extends advanced_testcase {

    /**
     * This is text content for unit testing a text submission.
     */
    const TEST_ASSIGN_TEXT = 'This is text content for unit testing a text submission.';

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
        $this->instructorrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($this->instructor->id,
            $this->course->id,
            $this->instructorrole->id
        );

        // Create student and enrol on course.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->student1->id,
            $this->course->id,
            $studentrole->id
        );

        // Create student and enrol on course.
        $this->student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->student2->id,
            $this->course->id,
            $studentrole->id
        );
    }

    /**
     * Test that we get back the correct online text when get_onlinetext is called.
     */
    public function test_get_onlinetext() {
        global $DB;

        $this->resetAfterTest();

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $cm->id, 'turnitinenabled' => 1);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log new user in.
        $this->setUser($this->student1);

        // Add a submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'text' => self::TEST_ASSIGN_TEXT,
            'format' => FORMAT_HTML
        );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        $tsassign = new plagiarism_turnitinsim_assign();
        $result = $tsassign->get_onlinetext($submission->id);

        $this->assertEquals($result, self::TEST_ASSIGN_TEXT);
    }

    /**
     * Test that online text returns false if no submission is found.
     */
    public function test_get_onlinetext_returns_false_if_no_text() {
        $this->resetAfterTest();

        $tsassign = new plagiarism_turnitinsim_assign();
        $result = $tsassign->get_onlinetext(1);

        $this->assertNull($result);
    }

    /**
     * Test that we get back the correct itemid when get_itemid is called.
     */
    public function test_get_itemid_returns_correct_itemid() {
        global $DB;

        $this->resetAfterTest();

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $cm->id, 'turnitinenabled' => 1);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log new user in.
        $this->setUser($this->student1);

        // Add a submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'text' => self::TEST_ASSIGN_TEXT,
            'format' => FORMAT_HTML
        );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Get itemid.
        $tsassign = new plagiarism_turnitinsim_assign();
        $params = new stdClass();
        $params->moduleid = $cm->instance;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_ASSIGN_TEXT;

        $this->assertEquals($tsassign->get_itemid($params), $submission->id);
    }

    /**
     * Test that we get back 0 when get_itemid is called if there is no submission.
     */
    public function test_get_itemid_returns_zero_if_no_submission() {
        $this->resetAfterTest();

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        // Get itemid.
        $tsassign = new plagiarism_turnitinsim_assign();
        $params = new stdClass();
        $params->moduleid = $cm->instance;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_ASSIGN_TEXT;

        $this->assertEquals($tsassign->get_itemid($params), 0);
    }

    /**
     * Test that getting the author returns the correct user id.
     */
    public function test_get_author_returns_correct_user_id() {
        $this->resetAfterTest(true);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Add user capability.
        assign_capability('mod/assign:editothersubmission', CAP_ALLOW, $this->instructorrole->id, $context->id);

        // Create group.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        // Enrol students in group.
        groups_add_member($group, $this->student1);
        groups_add_member($group, $this->student2);

        // Create group submission.
        $submission = $assign->get_group_submission($this->student1->id, $group->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'text' => self::TEST_ASSIGN_TEXT,
            'format' => FORMAT_HTML
        );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Test that get author returns student2 as the author.
        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->get_author($this->student1->id, $this->student2->id, $cm, 0);
        $this->assertEquals($this->student2->id, $response);

        // Test that get author returns student1 as the author because relateduserid is empty.
        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->get_author($this->student1->id, 0, $cm, 0);
        $this->assertEquals($this->student1->id, $response);

        // Test that get author returns student2 as the author.
        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->get_author($this->instructor->id, 0, $cm, $submission->id);
        $this->assertEquals($this->student1->id, $response);

    }

    /**
     * Test that checking the group author returns the first student.
     */
    public function test_check_group_first_author_returns_first_student() {
        $this->resetAfterTest(true);

        // Create group.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        // Enrol students in group.
        groups_add_member($group, $this->student1);
        groups_add_member($group, $this->student2);

        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->get_first_group_author($this->course->id, $group->id);

        // Test should return the first student id.
        $this->assertEquals($this->student1->id, $response);
    }

    /**
     * Test that checking the group author does not return an instructor.
     */
    public function test_check_group_first_author_does_not_return_instructor() {
        $this->resetAfterTest(true);

        // Create group.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        // Enrol instructor and student in group.
        groups_add_member($group, $this->instructor);
        groups_add_member($group, $this->student1);

        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->get_first_group_author($this->course->id, $group->id);

        // Test should return the student id and not the instructor id.
        $this->assertEquals($this->student1->id, $response);
    }

    /**
     * Test that checking the group author does not return a user if no students are in the group.
     */
    public function test_check_group_first_author_returns_no_user_if_no_students_in_group() {
        $this->resetAfterTest(true);

        // Create group.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        // Enrol instructor and student in group.
        groups_add_member($group, $this->instructor);

        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->get_first_group_author($this->course->id, $group->id);

        // Test should return 0.
        $this->assertEquals(0, $response);
    }

    /**
     * Test that is submission draft returns correctly.
     */
    public function test_is_submission_draft() {
        global $DB;

        $this->resetAfterTest();

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 1;
        $assign = $this->getDataGenerator()->create_module('assign', $record);

        // Create a student with a submission.
        $submission = new stdClass();
        $submission->assignment = $assign->id;
        $submission->userid = $this->student1->id;
        $submission->timecreated = time();
        $submission->timemodified = $submission->timecreated;
        $submission->status = 'draft';
        $submission->attemptnumber = 0;
        $submission->latest = 0;
        $submission->id = $DB->insert_record('assign_submission', $submission);

        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->is_submission_draft($submission->id);

        // Test should return true that the submission is a draft.
        $this->assertEquals(true, $response);

        // Finalise submission.
        $submission->status = 'submitted';
        $DB->update_record('assign_submission', $submission);

        $response = $tsassign->is_submission_draft($submission->id);

        // Test should return true that the submission is not a draft.
        $this->assertEquals(false, $response);
    }

    /**
     * Test that get due date returns expected value.
     */
    public function test_get_due_date() {
        $this->resetAfterTest();
        $tsassign = new plagiarism_turnitinsim_assign();

        // Log instructor in.
        $this->setUser($this->instructor);

        // Create assign module with a due date.
        $record = new stdClass();
        $record->course = $this->course;
        $record->duedate = 1000000001;
        $assign = $this->getDataGenerator()->create_module('assign', $record);

        $response = $tsassign->get_due_date($assign->id);
        $this->assertEquals(1000000001, $response);

        // Create assign module without a due date.
        $record = new stdClass();
        $record->course = $this->course;
        $record->duedate = 1000000001;
        $assign = $this->getDataGenerator()->create_module('assign', $record);

        $response = $tsassign->get_due_date($assign->id);
        $this->assertEquals(1000000001, $response);
    }

    /**
     * Test that show other posts links returns expected value.
     */
    public function test_show_other_posts_links() {
        $this->resetAfterTest();

        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->show_other_posts_links(0, 0);
        $this->assertEquals(true, $response);
    }

    /**
     * Test that the correct event data is returned when handling a file submission for an assignment.
     */
    public function test_create_submission_event_data_returns_correct_data_for_file_submission() {
        global $DB;

        $this->resetAfterTest();

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $cm->id, 'turnitinenabled' => 1);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Add a submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'text' => self::TEST_ASSIGN_TEXT,
            'format' => FORMAT_HTML
        );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "file" => $file,
            "content" => ''
        );

        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->create_submission_event_data($linkarray);

        $this->assertEquals('assessable_submitted', $response['eventtype']);
        $this->assertEquals($cm->id, $response['contextinstanceid']);
        $this->assertEquals($this->student1->id, $response['userid']);
        $this->assertEquals(array($file->get_pathnamehash()), $response['other']['pathnamehashes']);
        $this->assertEquals($submission->id, $response['objectid']);
        $this->assertEquals($submission->userid, $response['relateduserid']);
        $this->assertEquals('assign', $response['other']['modulename']);
        $this->assertEmpty($response['other']['content']);
    }

    /**
     * Test that the correct event data is returned when handling a text submission for an assignment.
     */
    public function test_create_submission_event_data_returns_correct_data_for_text_submission() {
        global $DB;

        $this->resetAfterTest();

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $cm->id, 'turnitinenabled' => 1);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log new user in.
        $this->setUser($this->student1);

        // Add a submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'text' => self::TEST_ASSIGN_TEXT,
            'format' => FORMAT_HTML
        );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "content" => self::TEST_ASSIGN_TEXT
        );

        $tsassign = new plagiarism_turnitinsim_assign();
        $response = $tsassign->create_submission_event_data($linkarray);

        $this->assertEquals('assessable_submitted', $response['eventtype']);
        $this->assertEquals($cm->id, $response['contextinstanceid']);
        $this->assertEquals($this->student1->id, $response['userid']);
        $this->assertEquals($submission->id, $response['objectid']);
        $this->assertEquals($submission->userid, $response['relateduserid']);
        $this->assertEquals('assign', $response['other']['modulename']);
        $this->assertEquals(self::TEST_ASSIGN_TEXT, $response['other']['content']);
    }
}