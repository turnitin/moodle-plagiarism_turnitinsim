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
 * Unit tests for (some of) plagiarism/turnitinsim/classes/eula.class.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/utilities.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/turnitinsim_generator.php');

/**
 * Tests for Turnitin Integrity submission class.
 */
class eula_class_testcase extends advanced_testcase {

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        global $CFG;

        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', 1234, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');

        $CFG->mtrace_wrapper = 'plagiarism_turnitinsim_mtrace';
    }

    /**
     * Test get latest eula version failed request to Turnitin.
     */
    public function test_get_latest_version_failure() {
        $this->resetAfterTest();

        // Get the response for a failed EULA version retrieval.
        $response = file_get_contents(__DIR__ . '/../fixtures/get_latest_eula_version_failure.json');

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_LATEST_EULA])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Get latest EULA version.
        $tseula = new plagiarism_turnitinsim_eula( $tsrequest );
        $result = $tseula->get_latest_version();

        // Test that the EULA version has not been retrieved.
        $this->assertFalse(isset($result->version));
    }

    /**
     * Test get latest eula version request to Turnitin fails with exception.
     */
    public function test_get_latest_version_exception() {
        $this->resetAfterTest();

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_LATEST_EULA])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->will($this->throwException(new Exception()));

        // Get the latest EULA version.
        $tseula = new plagiarism_turnitinsim_eula($tsrequest);
        $result = $tseula->get_latest_version();

        // Test that the latest EULA version has not been retrieved.
        $this->assertFalse(isset($result->version));
    }

    /**
     * Test get latest eula version success request to Turnitin.
     */
    public function test_get_latest_version_success() {
        $this->resetAfterTest();

        // Get the response for a failed EULA version retrieval.
        $response = file_get_contents(__DIR__ . '/../fixtures/get_latest_eula_version_success.json');

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_LATEST_EULA])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Get the latest EULA version.
        $tseula = new plagiarism_turnitinsim_eula( $tsrequest );
        $result = $tseula->get_latest_version();

        // Test that the latest EULA version has been retrieved.
        $this->assertTrue(isset($result->version));
    }

    /**
     * Test accept EULA updates the status of the EULA for all of a student's submissions.
     */
    public function test_accept_eula_saves_eula_and_updates_submissions() {
        global $DB;

        $this->resetAfterTest();

        set_config('turnitin_eula_version', 'v1beta', 'plagiarism_turnitinsim');

        // Create 3 submissions.
        $this->turnitinsim_generator = new turnitinsim_generator();
        $submission = $this->turnitinsim_generator->create_submission(3, TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED);

        $this->setUser($submission['student']);

        // Insert a user to the TII table.
        $user = new stdClass();
        $user->userid = $submission['student']->id;
        $user->turnitinid = (new handle_deprecation)->create_uuid();
        $DB->insert_record('plagiarism_turnitinsim_users', $user);

        // Check the data.
        $submissions = $DB->get_records('plagiarism_turnitinsim_sub');
        $this->assertCount(3, $submissions);

        $users = $DB->get_records('plagiarism_turnitinsim_users');
        $this->assertCount(1, $users);

        // Accept the EULA.
        $tseula = new plagiarism_turnitinsim_eula();
        $result = json_decode($tseula->accept_eula());
        $this->assertEquals(true, $result->success);

        // Check the results.
        $userresult = $DB->get_record('plagiarism_turnitinsim_users', array('userid' => $user->userid));
        $this->assertEquals('v1beta', $userresult->lasteulaaccepted);
        $this->assertGreaterThan('lasteulaacceptedtime', time() - 60);
        $this->assertEquals('en-US', $userresult->lasteulaacceptedlang);

        $submissionresult = $DB->get_records(
            'plagiarism_turnitinsim_sub',
            array('status' => TURNITINSIM_SUBMISSION_STATUS_QUEUED)
        );
        $this->assertCount(3, $submissionresult);
    }

    /**
     * Test get_eula_status returns expected output for student.
     */
    public function test_get_eula_status_for_student() {
        global $DB;

        $this->resetAfterTest();

        // Set the features enabled.
        $featuresenabled = file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');
        set_config('turnitin_eula_version', 'v1-beta', 'plagiarism_turnitinsim');

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create student and enrol on course.
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($this->student->id,
            $this->course->id,
            $studentrole->id
        );

        // Log new user in.
        $this->setUser($this->student);

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

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        $tseula = new plagiarism_turnitinsim_eula();
        $result = $tseula->get_eula_status($cm->id, 'file', $this->student->id);

        handle_deprecation::assertcontains($this,
            get_string('eulalink', 'plagiarism_turnitinsim', '?lang=en-US'), $result['eula-confirm']);
        handle_deprecation::assertcontains($this,
            get_string('submissiondisplayerror:eulanotaccepted_help', 'plagiarism_turnitinsim'), $result['eula-status']);
    }

    /**
     * Test get_eula_status returns expected output for instructor.
     */
    public function test_get_eula_status_for_instructor() {
        global $DB;

        $this->resetAfterTest();

        // Set the features enabled.
        $featuresenabled = file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');
        set_config('turnitin_eula_version', 'v1-beta', 'plagiarism_turnitinsim');

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create instructor and enrol on course.
        $this->instructor = $this->getDataGenerator()->create_user();
        $instructorrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($this->instructor->id,
            $this->course->id,
            $instructorrole->id
        );

        // Assign capability to instructor to view full reports at course level.
        $context = context_course::instance($this->course->id);
        assign_capability('plagiarism/turnitinsim:viewfullreport', CAP_ALLOW, $instructorrole->id, $context->id);
        role_assign($instructorrole->id, $this->instructor->id, $context->id);
        accesslib_clear_all_caches_for_unit_testing();

        // Log new user in.
        $this->setUser($this->instructor);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $cm->id, 'turnitinenabled' => 1);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        $tseula = new plagiarism_turnitinsim_eula();
        $result = $tseula->get_eula_status($cm->id, 'file', $this->instructor->id);

        handle_deprecation::assertcontains($this,
            get_string('eulalink', 'plagiarism_turnitinsim', '?lang=en-US'), $result['eula-confirm']);
        handle_deprecation::assertcontains($this,
            get_string('submissiondisplayerror:eulanotaccepted_help', 'plagiarism_turnitinsim'), $result['eula-status']);
    }
}