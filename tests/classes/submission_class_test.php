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
 * Unit tests for (some of) plagiarism/turnitinsim/classes/submission.class.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/utilities.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/utilities/handle_deprecation.php');

/**
 * Tests for Turnitin Integrity submission class.
 */
class submission_class_testcase extends advanced_testcase {

    /**
     * A valid submission ID.
     */
    const VALID_SUBMISSION_ID = '0ec9141f-3390-460e-8d2f-a4080080e749';

    /**
     * An invalid submission ID.
     */
    const INVALID_SUBMISSION_ID = 'INVALID_ID';

    /**
     * A sample error message.
     */
    const TEST_ERROR_MESSAGE_1 = 'Example error message 1.';

    /**
     * Another sample error message.
     */
    const TEST_ERROR_MESSAGE_2 = 'Example error message 2.';

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        global $CFG, $DB;

        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', 1234, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');
        set_config('turnitinenableremotelogging', 1, 'plagiarism_turnitinsim');

        // Set webhook details so tests don't create one.
        set_config('turnitin_webhook_id', 1, 'plagiarism_turnitinsim');
        set_config('turnitin_webhook_secret', 'secret', 'plagiarism_turnitinsim');

        // Set features enabled.
        $featuresenabled = file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');

        // Overwrite mtrace.
        $CFG->mtrace_wrapper = 'plagiarism_turnitinsim_mtrace';

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create student and enrol on course.
        $this->student1 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user(
            $this->student1->id,
            $this->course->id,
            $studentrole->id
        );

        // Create second student, enrol them on the course and add them to group.
        $this->student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user(
            $this->student2->id,
            $this->course->id,
            $studentrole->id
        );

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
    }

    /**
     * Test that update saves the record to the database.
     */
    public function test_update() {
        global $DB;

        $this->resetAfterTest();

        // Submissions table should be empty.
        $submission = $DB->get_records('plagiarism_turnitinsim_sub');
        $this->assertEmpty($submission);

        // Create new submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm(1);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier('PATHNAMEHASH');
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $tssubmission->settogenerate(1);
        $tssubmission->setgenerationtime(100000001);

        $tssubmission->update();

        // Submission id should now be set.
        handle_deprecation::assertinternaltypeint($this, $tssubmission->getid());

        // Check an id that doesn't exist doesn't return an object.
        $submission = $DB->get_record('plagiarism_turnitinsim_sub', array('id' => 0));
        $this->assertFalse(is_object($submission));

        // There should now be an entry in the database table.
        $submission = $DB->get_record('plagiarism_turnitinsim_sub', array('id' => $tssubmission->getid()));
        $this->assertTrue(is_object($submission));

        // Check params are what we set originally.
        $this->assertEquals($submission->identifier, $tssubmission->getidentifier());
        $this->assertEquals($submission->status, TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $this->assertEquals($submission->userid, $this->student1->id);
        $this->assertEquals($submission->cm, 1);
        $this->assertEquals($submission->togenerate, 1);
        $this->assertEquals($submission->generationtime, 100000001);

        // Change a parameter and check it saves correctly.
        $tssubmission->setidentifier('NEWPATHNAMEHASH');
        $this->assertEquals($tssubmission->getidentifier(), 'NEWPATHNAMEHASH');
    }

    /**
     * Test that build_user_array_entry returns nothing if the user object is empty.
     */
    public function test_build_user_array_entry_empty_if_empty_user_object() {
        $this->resetAfterTest();

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setuserid($this->student1->id);

        // Build user entry and check response.
        $userentry = $tssubmission->build_user_array_entry('');
        $this->assertEmpty($userentry);
    }

    /**
     * Test that build_user_array_entry returns a metadata friendly array entry of correct user details.
     */
    public function test_build_user_array_entry_returns_user_details_array() {
        $this->resetAfterTest();

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setuserid($this->student1->id);

        // Build user entry and get Turnitin id.
        $userentry = $tssubmission->build_user_array_entry($this->student1);
        $tsuser = new plagiarism_turnitinsim_user($this->student1->id);

        // Check user array returns correct details.
        $this->assertEquals($this->student1->lastname, $userentry['family_name']);
        $this->assertEquals($this->student1->firstname, $userentry['given_name']);
        $this->assertEquals($this->student1->email, $userentry['email']);
        $this->assertEquals($tsuser->get_turnitinid(), $userentry['id']);
    }

    /**
     * Test that build_user_array_entry returns just the userid if student privacy is enabled.
     */
    public function test_build_user_array_entry_returns_userid_only_if_student_privacy_is_enabled() {
        $this->resetAfterTest();

        // Enable student privacy.
        set_config('turnitinhideidentity', 1, 'plagiarism_turnitinsim');

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setuserid($this->student1->id);

        // Build user entry and get Turnitin id.
        $userentry = $tssubmission->build_user_array_entry($this->student1);
        $tsuser = new plagiarism_turnitinsim_user($this->student1->id);

        // Check user array returns correct details.
        $this->assertArrayNotHasKey('family_name', $userentry);
        $this->assertArrayNotHasKey('given_name', $userentry);
        $this->assertArrayNotHasKey('email', $userentry);
        $this->assertEquals($tsuser->get_turnitinid(), $userentry['id']);
    }

    /**
     * Test that create metadata returns no data if the cm doesn't exist.
     */
    public function test_create_group_metadata_no_cm() {
        $this->resetAfterTest();

        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm(-1);
        $this->assertFalse($tssubmission->create_group_metadata());
    }

    /**
     * Test that all the group members are returned in the owners metadata for a group submission.
     */
    public function test_create_owners_metadata_returns_all_group_member_details_as_owners() {
        $this->resetAfterTest();

        // Create group and add the students.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));
        groups_add_member($group->id, $this->student1->id);
        groups_add_member($group->id, $this->student2->id);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setgroupid($group->id);

        // Build user entry and get Turnitin id.
        $owners = $tssubmission->create_owners_metadata();
        $tsuser1 = new plagiarism_turnitinsim_user($this->student1->id);
        $tsuser2 = new plagiarism_turnitinsim_user($this->student2->id);

        // Check user array returns correct details.
        handle_deprecation::assertcontains($this, $this->student1->lastname, $owners[0]['family_name']);
        handle_deprecation::assertcontains($this, $this->student1->firstname, $owners[0]['given_name']);
        handle_deprecation::assertcontains($this, $this->student1->email, $owners[0]['email']);
        handle_deprecation::assertcontains($this, $tsuser1->get_turnitinid(), $owners[0]['id']);

        handle_deprecation::assertcontains($this, $this->student2->lastname, $owners[1]['family_name']);
        handle_deprecation::assertcontains($this, $this->student2->firstname, $owners[1]['given_name']);
        handle_deprecation::assertcontains($this, $this->student2->email, $owners[1]['email']);
        handle_deprecation::assertcontains($this, $tsuser2->get_turnitinid(), $owners[1]['id']);
    }

    /**
     * Test that the owners metadata is created correctly for a user's submission.
     */
    public function test_create_owners_metadata_returns_owner_details() {
        $this->resetAfterTest();

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setuserid($this->student1->id);

        // Build user entry and get Turnitin id.
        $owners = $tssubmission->create_owners_metadata();
        $tsuser = new plagiarism_turnitinsim_user($this->student1->id);

        // Check user array returns correct details.
        $this->assertEquals($this->student1->lastname, $owners[0]['family_name']);
        $this->assertEquals($this->student1->firstname, $owners[0]['given_name']);
        $this->assertEquals($this->student1->email, $owners[0]['email']);
        $this->assertEquals($tsuser->get_turnitinid(), $owners[0]['id']);
    }

    /**
     * Test that the owners metadata is empty if there is no owner.
     */
    public function test_create_owners_metadata_returns_empty_if_no_owner() {
        $this->resetAfterTest();

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();

        // Build user entry and get Turnitin id.
        $owners = $tssubmission->create_owners_metadata();

        // Check user array returns correct details.
        $this->assertEmpty($owners);
    }

    /**
     * Test that get owner returns the owner's Turnitin user id.
     */
    public function test_get_owner_returns_user_id_for_non_group_submission() {
        $this->resetAfterTest();

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setuserid($this->student1->id);

        $owner = $tssubmission->get_owner();
        $tsuser = new plagiarism_turnitinsim_user($this->student1->id);

        // Check owner is the user.
        $this->assertEquals($tsuser->get_turnitinid(), $owner);
    }

    /**
     * Test that get owner returns the group's Turnitin id for group submissions.
     */
    public function test_get_owner_returns_group_id_for_group_submission() {
        $this->resetAfterTest();

        // Create Group.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setgroupid($group->id);

        // Build user entry and get Turnitin id.
        $owner = $tssubmission->get_owner();
        $tsgroup = new plagiarism_turnitinsim_group($group->id);

        // Check owner is the group.
        $this->assertEquals($tsgroup->get_turnitinid(), $owner);
    }

    /**
     * Test that create metadata returns as expected.
     */
    public function test_create_group_metadata_full() {
        global $DB;

        $this->resetAfterTest();

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        $metadata = $tssubmission->create_group_metadata();

        // Verify assignment is in metadata.
        $this->assertEquals($metadata['group']['id'], $cm->id);
        $this->assertEquals($metadata['group']['name'], $cm->name);
        $this->assertEquals($metadata['group']['type'], 'ASSIGNMENT');

        // Verify course data is in metadata.
        $coursedetails = $DB->get_record('course', array('id' => $cm->course), 'fullname');
        $this->assertEquals($metadata['group_context']['id'], $cm->course);
        $this->assertEquals($metadata['group_context']['name'], $coursedetails->fullname);

        // Verify instructor is in metadata.
        $instructor = $DB->get_record('user', array('id' => $this->instructor->id));
        $instructor->tsdetails = new plagiarism_turnitinsim_user($this->instructor->id);

        $this->assertEquals($metadata['group_context']['owners'][0]['id'], $instructor->tsdetails->get_turnitinid());
        $this->assertEquals($metadata['group_context']['owners'][0]['family_name'], $instructor->lastname);
        $this->assertEquals($metadata['group_context']['owners'][0]['given_name'], $instructor->firstname);
        $this->assertEquals($metadata['group_context']['owners'][0]['email'], $instructor->email);
    }

    /**
     * Test that create metadata returns expected group type for forum.
     */
    public function test_create_group_metadata_forum() {
        $this->resetAfterTest();

        // Create forum module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('forum', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('forum', $module->id);

        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        $metadata = $tssubmission->create_group_metadata();

        // Verify forum is in metadata.
        $this->assertEquals($metadata['group']['type'], 'FORUM');
    }

    /**
     * Test that create metadata returns expected group type for workshop.
     */
    public function test_create_group_metadata_workshop() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create workshop module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('workshop', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('workshop', $module->id);

        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        $metadata = $tssubmission->create_group_metadata();

        // Verify forum is in metadata.
        $this->assertEquals($metadata['group']['type'], 'WORKSHOP');
    }

    /**
     * Test that create metadata returns expected group type for quiz.
     */
    public function test_create_group_metadata_quiz() {
        $this->resetAfterTest();

        // Create quiz module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('quiz', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('quiz', $module->id);

        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        $metadata = $tssubmission->create_group_metadata();

        // Verify forum is in metadata.
        $this->assertEquals($metadata['group']['type'], 'QUIZ');
    }

    /**
     * Test that get file details returns false if a file doesn't exist.
     */
    public function test_get_file_details_with_non_existent_file() {
        $this->resetAfterTest();

        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setidentifier('HASH FOR FILE THAT WONT EXIST');

        $this->assertFalse($tssubmission->get_file_details());
    }

    /**
     * Test that get file details returns an actual file.
     */
    public function test_get_file_details_with_actual_file() {
        $this->resetAfterTest();

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        $file = create_test_file(0, $usercontext->id, 'user', 'draft');

        $tssubmission = new plagiarism_turnitinsim_submission( new plagiarism_turnitinsim_request() );
        $tssubmission->setidentifier($file->get_pathnamehash());

        $file = $tssubmission->get_file_details();
        $this->assertTrue( is_a($file, 'stored_file') );
    }

    /**
     * Test the create file submission in Turnitin request.
     */
    public function test_create_file_submission_in_turnitin_success() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $response = file_get_contents(__DIR__ . '/../fixtures/create_submission_success.json');

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_CREATE_SUBMISSION])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        $file = create_test_file(0, $usercontext->id, 'user', 'draft');

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm(1);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->create_submission_in_turnitin();

        // Test that the submission status is created.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_CREATED, $tssubmission->getstatus());
    }

    /**
     * Test the create text submission in Turnitin request.
     */
    public function test_create_text_submission_in_turnitin_success() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $response = file_get_contents(__DIR__ . '/../fixtures/create_submission_success.json');

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_CREATE_SUBMISSION])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $this->course);

        // Log student in.
        $this->setUser($this->student1);

        // Create assignment text submission.
        $textcontent = "This is text content for unit testing a text submission.";
        $textcontent .= $textcontent;
        $textcontent .= $textcontent;

        // Add a submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'text' => $textcontent,
            'format' => FORMAT_HTML
        );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setitemid($submission->id);
        $tssubmission->setidentifier(sha1($textcontent));
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_CONTENT);
        $tssubmission->create_submission_in_turnitin();

        // Test that the submission status is created.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_CREATED, $tssubmission->getstatus());
    }

    /**
     * Test successfully uploading a file submission to Turnitin request.
     */
    public function test_upload_file_submission_to_turnitin_success() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $uploadresponse = file_get_contents(__DIR__ . '/../fixtures/upload_file_to_submission_success.json');

        // Mock API create submission request class and send call.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // We must parse the expected response to get the submission id for the upload request.
        $endpoint = TURNITINSIM_ENDPOINT_UPLOAD_SUBMISSION;
        $endpoint = str_replace('{{submission_id}}', self::VALID_SUBMISSION_ID, $endpoint);

        // Mock send request method for upload.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with($endpoint)
            ->willReturn($uploadresponse);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        $file = create_test_file(0, $usercontext->id, 'user', 'draft');

        // Set submission object params.
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setturnitinid(self::VALID_SUBMISSION_ID);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_CREATED);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);

        // Upload file to submission.
        $tssubmission->upload_submission_to_turnitin();

        // Test that the submission status is uploaded.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_UPLOADED, $tssubmission->getstatus());
    }

    /**
     * Test failure to upload a file submission to Turnitin request.
     */
    public function test_upload_file_submission_to_turnitin_failure() {
        $this->resetAfterTest();

        // Get the response for a failed submission.
        $uploadresponse = file_get_contents(__DIR__ . '/../fixtures/upload_file_to_submission_failure.json');
        $jsonresponse = (array)json_decode($uploadresponse);

        // Mock API update submission request class and send call.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // We must parse the expected response to get the submission id for the upload request.
        $endpoint = TURNITINSIM_ENDPOINT_UPLOAD_SUBMISSION;
        $endpoint = str_replace('{{submission_id}}', self::INVALID_SUBMISSION_ID, $endpoint);

        // Mock send request method for upload.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with($endpoint)
            ->willReturn($uploadresponse);

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        $file = create_test_file(0, $usercontext->id, 'user', 'draft');

        // Create submission object with status created and an invalid Turnitin Id to simulate a not found error.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm(1);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setturnitinid(self::INVALID_SUBMISSION_ID);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_CREATED);
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);

        // Upload file to submission.
        $tssubmission->upload_submission_to_turnitin();

        // Test that the submission status is error.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $tssubmission->getstatus());
        $this->assertEquals($jsonresponse['message'], $tssubmission->geterrormessage());
    }

    /**
     * Test failure to upload a file submission to Turnitin request if the file has been deleted.
     */
    public function test_upload_file_submission_to_turnitin_failure_file_deleted() {
        $this->resetAfterTest();

        $this->setUser($this->student1);

        // Create submission object with status created and an invalid Turnitin Id to simulate a not found error.
        $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
        $tssubmission->setcm(1);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier('test');
        $tssubmission->setturnitinid(self::INVALID_SUBMISSION_ID);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_CREATED);
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);

        // Upload file to submission.
        $tssubmission->upload_submission_to_turnitin();

        // Test that the submission status is empty/deleted and that it won't retry.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_EMPTY_DELETED, $tssubmission->getstatus());
        $this->assertEquals(TURNITINSIM_SUBMISSION_MAX_SEND_ATTEMPTS, $tssubmission->gettiiattempts());
    }

    /**
     * Test successfully uploading a text content submission to Turnitin request.
     */
    public function test_upload_text_content_submission_to_turnitin_success() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $uploadresponse = file_get_contents(__DIR__ . '/../fixtures/upload_file_to_submission_success.json');

        // Mock API create submission request class and send call.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // We must parse the expected response to get the submission id for the upload request.
        $endpoint = TURNITINSIM_ENDPOINT_UPLOAD_SUBMISSION;
        $endpoint = str_replace('{{submission_id}}', self::VALID_SUBMISSION_ID, $endpoint);

        // Mock send request method for upload.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with($endpoint)
            ->willReturn($uploadresponse);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);

        // Log student in.
        $this->setUser($this->student1);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $this->course);

        // Create assignment text submission.
        $textcontent = "This is text content for unit testing a text submission.";
        $textcontent .= $textcontent;
        $textcontent .= $textcontent;

        // Add a submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'text' => $textcontent,
            'format' => FORMAT_HTML
        );
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Set submission object params.
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setturnitinid(self::VALID_SUBMISSION_ID);
        $tssubmission->setitemid($submission->id);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_CREATED);
        $tssubmission->setidentifier(sha1($textcontent));
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_CONTENT);

        // Upload file to submission.
        $tssubmission->upload_submission_to_turnitin();

        // Test that the submission status is uploaded.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_UPLOADED, $tssubmission->getstatus());
    }

    /**
     * Test successful report generation request.
     */
    public function test_request_turnitin_report_generation_success() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $reportgenresponse = file_get_contents(__DIR__ . '/../fixtures/request_report_generation_success.json');

        // Mock API create submission request class and send call.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // Add submission ID to endpoint.
        $endpoint = TURNITINSIM_ENDPOINT_SIMILARITY_REPORT;
        $endpoint = str_replace('{{submission_id}}', self::VALID_SUBMISSION_ID, $endpoint);

        // Mock send request method for upload.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with($endpoint)
            ->willReturn($reportgenresponse);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 0;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create data object for cm assignment.
        $data = new stdClass();
        $data->coursemodule = $cm->id;
        $data->turnitinenabled = 1;
        $data->checkinternet = 1;
        $data->checkprivate = 1;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setitemid($submission->id);
        $tssubmission->setturnitinid(self::VALID_SUBMISSION_ID);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);

        // Request report generation.
        $tssubmission->request_turnitin_report_generation();

        // Test that the submission status is uploaded.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_REQUESTED, $tssubmission->getstatus());
    }

    /**
     * Test successful report generation request on due date.
     */
    public function test_request_turnitin_report_generation_success_on_due_date() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $reportgenresponse = file_get_contents(__DIR__ . '/../fixtures/request_report_generation_success.json');

        // Mock API create submission request class and send call.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // Add submission ID to endpoint.
        $endpoint = TURNITINSIM_ENDPOINT_SIMILARITY_REPORT;
        $endpoint = str_replace('{{submission_id}}', self::VALID_SUBMISSION_ID, $endpoint);

        // Mock send request method for upload.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with($endpoint)
            ->willReturn($reportgenresponse);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 0;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create data object for cm assignment.
        $data = new stdClass();
        $data->coursemodule = $cm->id;
        $data->turnitinenabled = 1;
        $data->checkinternet = 1;
        $data->checkprivate = 1;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setitemid($submission->id);
        $tssubmission->setturnitinid(self::VALID_SUBMISSION_ID);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);

        // Request report generation.
        $tssubmission->request_turnitin_report_generation(true);

        // Test that the submission status is uploaded.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_REQUESTED, $tssubmission->getstatus());
        $this->assertEquals(0, $tssubmission->gettogenerate());
        $this->assertLessThanOrEqual(time(), $tssubmission->getgenerationtime());
    }

    /**
     * Test report generation request failure with invalid ID.
     */
    public function test_request_turnitin_report_generation_failure_invalid_id() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $reportgenresponse = file_get_contents(__DIR__ . '/../fixtures/request_report_generation_failure_invalid_id.json');
        $jsonresponse = (array)json_decode($reportgenresponse);

        // Mock API create submission request class and send call.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // Add submission ID to endpoint.
        $endpoint = TURNITINSIM_ENDPOINT_SIMILARITY_REPORT;
        $endpoint = str_replace('{{submission_id}}', self::INVALID_SUBMISSION_ID, $endpoint);

        // Mock send request method for upload.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with($endpoint)
            ->willReturn($reportgenresponse);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 0;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create data object for cm assignment.
        $data = new stdClass();
        $data->coursemodule = $cm->id;
        $data->turnitinenabled = 1;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setitemid($submission->id);
        $tssubmission->setturnitinid(self::INVALID_SUBMISSION_ID);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);

        // Request report generation.
        $tssubmission->request_turnitin_report_generation();

        // Test that the submission status is errored.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $tssubmission->getstatus());
        $this->assertEquals($jsonresponse['message'], $tssubmission->geterrormessage());
    }

    /**
     * Test report generation request failure with missing required settings.
     */
    public function test_request_turnitin_report_generation_failure_missing_required_settings() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $filepath = '/../fixtures/request_report_generation_failure_missing_required_settings.json';
        $reportgenresponse = file_get_contents(__DIR__ . $filepath);
        $jsonresponse = (array)json_decode($reportgenresponse);

        // Mock API create submission request class and send call.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // Add submission ID to endpoint.
        $endpoint = TURNITINSIM_ENDPOINT_SIMILARITY_REPORT;
        $endpoint = str_replace('{{submission_id}}', self::VALID_SUBMISSION_ID, $endpoint);

        // Mock send request method for upload.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with($endpoint)
            ->willReturn($reportgenresponse);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 0;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create data object for cm assignment.
        $data = new stdClass();
        $data->coursemodule = $cm->id;
        $data->turnitinenabled = 1;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setitemid($submission->id);
        $tssubmission->setturnitinid(self::VALID_SUBMISSION_ID);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);

        // Request report generation.
        $tssubmission->request_turnitin_report_generation();

        // Test that the submission status is errored.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $tssubmission->getstatus());
        $this->assertEquals($jsonresponse['message'], $tssubmission->geterrormessage());
    }

    /**
     * Test report generation request failure with not enough text.
     */
    public function test_request_turnitin_report_generation_failure_not_enough_text() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $reportgenresponse = file_get_contents(__DIR__ . '/../fixtures/request_report_generation_failure_not_enough_text.json');
        $jsonresponse = (array)json_decode($reportgenresponse);

        // Mock API create submission request class and send call.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // Add submission ID to endpoint.
        $endpoint = TURNITINSIM_ENDPOINT_SIMILARITY_REPORT;
        $endpoint = str_replace('{{submission_id}}', self::VALID_SUBMISSION_ID, $endpoint);

        // Mock send request method for upload.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with($endpoint)
            ->willReturn($reportgenresponse);

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 0;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create data object for cm assignment.
        $data = new stdClass();
        $data->coursemodule = $cm->id;
        $data->turnitinenabled = 1;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setitemid($submission->id);
        $tssubmission->setturnitinid(self::VALID_SUBMISSION_ID);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);

        // Request report generation.
        $tssubmission->request_turnitin_report_generation();

        // Test that the submission status is errored.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $tssubmission->getstatus());
        $this->assertEquals($jsonresponse['message'], $tssubmission->geterrormessage());
    }

    /**
     * Test report generation request failure if cannot extract text.
     */
    public function test_request_turnitin_report_generation_failure_cannot_extract_text() {
        $this->resetAfterTest();

        // Get the response for a successfully created submission.
        $reportgenresponse = file_get_contents(__DIR__ . '/../fixtures/request_report_generation_failure_cannot_extract_text.json');
        $jsonresponse = (array)json_decode($reportgenresponse);

        // Mock API create submission request class and send call.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // Add submission ID to endpoint.
        $endpoint = TURNITINSIM_ENDPOINT_SIMILARITY_REPORT;
        $endpoint = str_replace('{{submission_id}}', self::VALID_SUBMISSION_ID, $endpoint);

        // Mock send request method for upload.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with($endpoint)
            ->willReturn($reportgenresponse);

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 0;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create data object for cm assignment.
        $data = new stdClass();
        $data->coursemodule = $cm->id;
        $data->turnitinenabled = 1;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setitemid($submission->id);
        $tssubmission->setturnitinid(self::VALID_SUBMISSION_ID);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);

        // Request report generation.
        $tssubmission->request_turnitin_report_generation();

        // Test that the submission status is errored.
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $tssubmission->getstatus());
        $this->assertEquals($jsonresponse['message'], $tssubmission->geterrormessage());
    }

    /**
     * Test getting the submission details for a file.
     */
    public function test_get_submission_details_file() {
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

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "file" => $file
        );

        // Create a Turnitin Integrity submission record that is queued for sending to Turnitin.
        $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->settogenerate(1);
        $tssubmission->setquizanswer(0);
        $tssubmission->update();

        // Compare submission details.
        $result = plagiarism_turnitinsim_submission::get_submission_details($linkarray);

        $this->assertEquals($result->userid, $this->student1->id);
        $this->assertEquals($result->status, TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $this->assertEquals($result->identifier, $file->get_pathnamehash());
    }

    /**
     * Test getting the submission details for text content.
     */
    public function test_get_submission_details_text() {
        global $DB;

        $this->resetAfterTest();

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 1;
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

        // Log student in.
        $this->setUser($this->student1);

        $textcontent = "This is text content for unit testing a text submission.";

        // Add a submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $data->onlinetext_editor = array(
            'itemid' => $submission->id,
            'text' => $textcontent,
            'format' => FORMAT_HTML);
        $plugin = $assign->get_submission_plugin_by_type('onlinetext');
        $plugin->save($submission, $data);

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "content" => $textcontent,
            "objectid" => $submission->id,
            "component" => "assign"
        );

        // Compare submission details.
        $result = plagiarism_turnitinsim_submission::get_submission_details($linkarray);

        $this->assertEquals($result->userid, $this->student1->id);
        $this->assertEquals($result->status, TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $this->assertEquals($result->identifier, sha1($textcontent));
        $this->assertEquals($result->itemid, $submission->id);
    }

    /**
     * Test that the generation date is set correctly when report generation is set to immediate
     */
    public function test_set_generationtime_immediate() {
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
        $modsettings = array('cm' => $cm->id, 'turnitinenabled' => 1, 'reportgeneration' => TURNITINSIM_REPORT_GEN_IMMEDIATE);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "file" => $file
        );

        // Create a Turnitin Integrity submission record that is queued for sending to Turnitin.
        $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setquizanswer(0);
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        // Compare submission details.
        $result = plagiarism_turnitinsim_submission::get_submission_details($linkarray);

        $this->assertEquals($result->togenerate, 1);
        $this->assertLessThanOrEqual($result->generationtime, time());
    }

    /**
     * Test that report generation is set correctly when report generation is set to immediate
     * then to regenerate on due date.
     */
    public function test_set_generationtime_immediate_duedate() {
        global $DB;

        $this->resetAfterTest();

        // Log instructor in.
        $this->setUser($this->instructor);

        // Create assign module.
        $duedate = time() + (60 * 60 * 2);
        $record = new stdClass();
        $record->course = $this->course;
        $record->duedate = $duedate;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $cm->id, 'turnitinenabled' => 1,
            'reportgeneration' => TURNITINSIM_REPORT_GEN_IMMEDIATE_AND_DUEDATE);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "file" => $file
        );

        // Create a Turnitin Integrity submission record that is queued for sending to Turnitin.
        $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setquizanswer(0);
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        // Compare submission details.
        $result = plagiarism_turnitinsim_submission::get_submission_details($linkarray);

        $this->assertEquals($result->togenerate, 1);
        $this->assertLessThanOrEqual($result->generationtime, time());

        // Update status and generation time.
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_COMPLETE);
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        // Compare submission details.
        $result = plagiarism_turnitinsim_submission::get_submission_details($linkarray);

        $this->assertEquals($result->togenerate, 1);
        $this->assertEquals($result->generationtime, $duedate);
    }

    /**
     * Test that report generation is set correctly when report generation is set to due date.
     */
    public function test_set_generationtime_duedate() {
        global $DB;

        $this->resetAfterTest();

        // Log instructor in.
        $this->setUser($this->instructor);

        // Create assign module.
        $duedate = time() + (60 * 60 * 2);
        $record = new stdClass();
        $record->course = $this->course;
        $record->duedate = $duedate;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $cm->id, 'turnitinenabled' => 1, 'reportgeneration' => TURNITINSIM_REPORT_GEN_DUEDATE);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "file" => $file
        );

        // Create a Turnitin Integrity submission record that is queued for sending to Turnitin.
        $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->calculate_generation_time();
        $tssubmission->setquizanswer(0);
        $tssubmission->update();

        // Compare submission details.
        $result = plagiarism_turnitinsim_submission::get_submission_details($linkarray);

        $this->assertEquals($result->togenerate, 1);
        $this->assertEquals($result->generationtime, $duedate);

        // Edit assignment duedate to be in the past.
        $duedate = time() - (60 * 60 * 2);
        $update = new stdClass();
        $update->id = $module->id;
        $update->duedate = $duedate;
        $DB->update_record('assign', $update);

        // Update generation time.
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        // Compare submission details.
        $result = plagiarism_turnitinsim_submission::get_submission_details($linkarray);

        $this->assertEquals($result->togenerate, 1);
        $this->assertLessThanOrEqual($result->generationtime, time());
    }

    /**
     * Test that the generation date is set correctly when no course module exists.
     */
    public function test_set_generationtime_no_course_module() {
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
        $modsettings = array('cm' => $cm->id, 'turnitinenabled' => 1, 'reportgeneration' => TURNITINSIM_REPORT_GEN_IMMEDIATE);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log student in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create a Turnitin Integrity submission record that is queued for sending to Turnitin.
        $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $tssubmission->setidentifier($file->get_pathnamehash());

        $assign->delete_instance();

        // Call the method we're testing and update.
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => $cm->id]);

        $this->assertEquals(0, $record->togenerate);
        $this->assertEquals(0, $record->generationtime);
    }

    /**
     * Test that is submission anonymous returns false if blind marking is off.
     */
    public function test_is_submission_anonymous_blindmarking_off() {
        $this->resetAfterTest();

        // Create assign module with blind marking off.
        $record = new stdClass();
        $record->course = $this->course;
        $record->blindmarking = 0;

        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $this->course);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);

        // Test that the submission is not anonymous if blindmarking is off.
        $this->assertEquals(false, $tssubmission->is_submission_anonymous());
    }

    /**
     * Test that is submission anonymous returns true if blind marking is on.
     */
    public function test_is_submission_anonymous_blindmarking_on() {
        global $DB;

        $this->resetAfterTest();

        // Create assign module with blind marking on.
        $record = new stdClass();
        $record->course = $this->course;
        $record->blindmarking = 1;

        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $this->course);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);

        // Test that the submission is not anonymous if blindmarking is on.
        $this->assertEquals(true, $tssubmission->is_submission_anonymous());

        // Reveal Identities.
        $data = new stdClass();
        $data->id = $cm->instance;
        $data->revealidentities = 1;
        $DB->update_record('assign', $data);

        // Test that the submission is not anonymous if blindmarking is on and identities have been revealed.
        $this->assertEquals(false, $tssubmission->is_submission_anonymous());
    }

    /**
     * Test that is submission anonymous returns true if hiding student identities.
     */
    public function test_is_submission_anonymous_hide_identities_on() {
        $this->resetAfterTest();

        set_config('turnitinhideidentity', 1, 'plagiarism_turnitinsim');

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        // Test that the submission is anonymous if hide identities is on.
        $this->assertEquals(true, $tssubmission->is_submission_anonymous());
    }

    /**
     * Test that is submission anonymous returns true if hiding student identities.
     */
    public function test_is_submission_anonymous_hide_identities_off() {
        $this->resetAfterTest();

        set_config('turnitinhideidentity', 0, 'plagiarism_turnitinsim');

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        // Test that the submission is anonymous if hide identities is on.
        $this->assertEquals(false, $tssubmission->is_submission_anonymous());
    }

    /**
     * Test that the viewer permissions returned are true if enabled.
     */
    public function test_viewer_permissions_are_true_if_enabled() {
        $this->resetAfterTest();

        set_config('turnitinviewerviewfullsource', 1, 'plagiarism_turnitinsim');
        set_config('turnitinviewermatchsubinfo', 1, 'plagiarism_turnitinsim');

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        // Verify that viewer permissions are true as the config values are set to true.
        $permissions = $tssubmission->create_report_viewer_permissions();
        $this->assertEquals(true, $permissions['may_view_submission_full_source']);
        $this->assertEquals(true, $permissions['may_view_match_submission_info']);
    }

    /**
     * Test that the viewer permissions returned are false if not enabled.
     */
    public function test_viewer_permissions_false_if_not_enabled() {
        $this->resetAfterTest();

        set_config('turnitinviewerviewfullsource', 0, 'plagiarism_turnitinsim');
        set_config('turnitinviewermatchsubinfo', 0, 'plagiarism_turnitinsim');

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        // Verify that viewer permissions are false as the config values are set to false.
        $permissions = $tssubmission->create_report_viewer_permissions();
        $this->assertEquals(false, $permissions['may_view_submission_full_source']);
        $this->assertEquals(false, $permissions['may_view_match_submission_info']);
    }

    /**
     * Test that the viewer permissions returned are true if enabled.
     */
    public function test_viewer_permissions_may_view_match_info_false_if_anonymous() {
        $this->resetAfterTest();

        set_config('turnitinviewerviewfullsource', 1, 'plagiarism_turnitinsim');
        set_config('turnitinviewermatchsubinfo', 1, 'plagiarism_turnitinsim');

        // Create assign module with blind marking on.
        $record = new stdClass();
        $record->course = $this->course;
        $record->blindmarking = 1;

        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);

        // Verify that viewer permissions are true as the config values are set to true.
        $permissions = $tssubmission->create_report_viewer_permissions();
        $this->assertEquals(true, $tssubmission->is_submission_anonymous());
        $this->assertEquals(true, $permissions['may_view_submission_full_source']);
        $this->assertEquals(false, $permissions['may_view_match_submission_info']);
    }

    /**
     * Test that the similarity overrides are true when configured as such.
     */
    public function test_similarity_overrides_are_true() {
        $this->resetAfterTest();

        set_config('turnitinviewersavechanges', 1, 'plagiarism_turnitinsim');

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        // Verify that viewer permissions are true as the config values are set to true.
        $overrides = $tssubmission->create_similarity_overrides(TURNITINSIM_ROLE_INSTRUCTOR);
        $this->assertTrue($overrides['modes']['match_overview']);
        $this->assertTrue($overrides['modes']['all_sources']);
        $this->assertTrue($overrides['view_settings']['save_changes']);
    }

    /**
     * Test that the similarity overrides are false when configured as such.
     */
    public function test_similarity_overrides_are_false() {
        $this->resetAfterTest();

        set_config('turnitinviewersavechanges', 0, 'plagiarism_turnitinsim');

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        // Verify that viewer permissions are true as the config values are set to true.
        $overrides = $tssubmission->create_similarity_overrides(TURNITINSIM_ROLE_INSTRUCTOR);
        $this->assertTrue($overrides['modes']['match_overview']);
        $this->assertTrue($overrides['modes']['all_sources']);
        $this->assertFalse($overrides['view_settings']['save_changes']);
    }


    /**
     * Test that the similarity overrides are true when configured as such.
     */
    public function test_similarity_overrides_save_change_is_false_when_role_is_learner() {
        $this->resetAfterTest();

        set_config('turnitinviewersavechanges', 1, 'plagiarism_turnitinsim');

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);

        // Create submission object.
        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm($cm->id);

        // Verify that viewer permissions are true as the config values are set to true.
        $overrides = $tssubmission->create_similarity_overrides(TURNITINSIM_ROLE_LEARNER);
        $this->assertTrue($overrides['modes']['match_overview']);
        $this->assertTrue($overrides['modes']['all_sources']);
        $this->assertFalse($overrides['view_settings']['save_changes']);
    }

    /**
     * Test that the similarity response status sets correct values if status is COMPLETE.
     */
    public function test_handle_similarity_response_status_sets_correct_values_if_status_is_complete() {
        global $DB;

        $this->resetAfterTest();

        $params = new stdClass();
        $params->status = TURNITINSIM_SUBMISSION_STATUS_COMPLETE;
        $params->overall_match_percentage = 50;

        $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
        $tssubmission->setcm(1);
        $tssubmission->setuserid(1);
        $tssubmission->setsubmitter(1);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->setidentifier('6be293577b6b42bd04accd034bb40a8ca0b4bdd6');
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $tssubmission->handle_similarity_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_COMPLETE, $record->status);
        $this->assertEquals(1, $record->tiiattempts);
        $this->assertEquals(0, $record->tiiretrytime);
        $this->assertEquals(50, $record->overallscore);
    }

    /**
     * Test that the similarity response status sets correct values if status is CREATED.
     */
    public function test_handle_similarity_response_status_sets_correct_values_if_status_is_created() {
        global $DB;

        $this->resetAfterTest();

        $response = '{"status": "CREATED", "message": "'.self::TEST_ERROR_MESSAGE_1.'"}';

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_SUBMISSION_INFO])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->exactly(2))
            ->method('send_request')
            ->willReturn($response);

        $params = new stdClass();
        $params->status = TURNITINSIM_SUBMISSION_STATUS_CREATED;
        $params->message = self::TEST_ERROR_MESSAGE_2;

        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm(1);
        $tssubmission->setuserid(1);
        $tssubmission->setsubmitter(1);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_CREATED);
        $tssubmission->setidentifier('6be293577b6b42bd04accd034bb40a8ca0b4bdd6');
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $tssubmission->handle_similarity_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_QUEUED, $record->status);
        $this->assertEquals(1, $record->tiiattempts);
        $this->assertEquals('', $record->errormessage);
        $this->assertGreaterThan(time(), $record->tiiretrytime);

        // Prepare to test scenario where max attempts is reached.
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_CREATED);
        $tssubmission->settiiattempts(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS - 1);
        $tssubmission->update();

        $tssubmission->handle_similarity_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $record->status);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS, $record->tiiattempts);
        $this->assertEquals(self::TEST_ERROR_MESSAGE_2, $record->errormessage);
    }

    /**
     * Test that the similarity response status sets correct values if status is ERROR.
     */
    public function test_handle_similarity_response_status_sets_correct_values_if_status_is_error() {
        global $DB;

        $this->resetAfterTest();

        $response = '{"status": "ERROR", "message": "'.self::TEST_ERROR_MESSAGE_1.'"}';

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_SUBMISSION_INFO])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        $params = new stdClass();
        $params->status = TURNITINSIM_SUBMISSION_STATUS_CREATED;
        $params->message = self::TEST_ERROR_MESSAGE_2;

        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm(1);
        $tssubmission->setuserid(1);
        $tssubmission->setsubmitter(1);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_CREATED);
        $tssubmission->setidentifier('6be293577b6b42bd04accd034bb40a8ca0b4bdd6');
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $tssubmission->handle_similarity_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $record->status);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS, $record->tiiattempts);
        $this->assertEquals(0, $record->tiiretrytime);
        $this->assertEquals(self::TEST_ERROR_MESSAGE_1, $record->errormessage);
    }

    /**
     * Test that the similarity response status sets correct values if status is PROCESSING.
     */
    public function test_handle_similarity_response_status_sets_correct_values_if_status_is_processing() {
        global $DB;

        $this->resetAfterTest();

        $response = '{"status": "PROCESSING"}';

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_SUBMISSION_INFO])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->exactly(2))
            ->method('send_request')
            ->willReturn($response);

        $params = new stdClass();
        $params->status = TURNITINSIM_SUBMISSION_STATUS_CREATED;

        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm(1);
        $tssubmission->setuserid(1);
        $tssubmission->setsubmitter(1);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->setidentifier('6be293577b6b42bd04accd034bb40a8ca0b4bdd6');
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $tssubmission->handle_similarity_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_UPLOADED, $record->status);
        $this->assertEquals(1, $record->tiiattempts);
        $this->assertGreaterThan(time(), $record->tiiretrytime);

        // Prepare to test scenario where max attempts is reached.
        $tssubmission->settiiattempts(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS - 1);
        $tssubmission->update();

        $tssubmission->handle_similarity_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $record->status);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS, $record->tiiattempts);
        $this->assertEquals(get_string('submissiondisplaystatus:unknown', 'plagiarism_turnitinsim'), $record->errormessage);
    }

    /**
     * Test that the similarity response status sets correct values if the request fails.
     */
    public function test_handle_similarity_response_status_sets_correct_values_if_request_fails() {
        global $DB;

        $this->resetAfterTest();

        $response = new stdClass();
        $response->status = false;
        $response = json_encode($response);

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_SUBMISSION_INFO])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        $params = new stdClass();
        $params->status = TURNITINSIM_SUBMISSION_STATUS_CREATED;

        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm(1);
        $tssubmission->setuserid(1);
        $tssubmission->setsubmitter(1);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->setidentifier('6be293577b6b42bd04accd034bb40a8ca0b4bdd6');
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $tssubmission->handle_similarity_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_UPLOADED, $record->status);
        $this->assertEquals(1, $record->tiiattempts);
        $this->assertGreaterThan(time(), $record->tiiretrytime);
    }

    /**
     * Test the submission info status when submission is in processing state.
     */
    public function test_handle_submission_info_if_submission_is_in_processing_state() {
        global $DB;

        $this->resetAfterTest();

        $params = new stdClass();
        $params->status = TURNITINSIM_SUBMISSION_STATUS_PROCESSING;

        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm(1);
        $tssubmission->setuserid(1);
        $tssubmission->setsubmitter(1);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->setidentifier('6be293577b6b42bd04accd034bb40a8ca0b4bdd6');
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $issubmissioncomplete = $tssubmission->handle_submission_info_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertFalse($issubmissioncomplete);
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_UPLOADED, $record->status);
        $this->assertEquals(1, $record->tiiattempts);
        $this->assertGreaterThan(time(), $record->tiiretrytime);

        // Prepare to test scenario where max attempts is reached.
        $tssubmission->settiiattempts(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS - 1);
        $tssubmission->update();

        $issubmissioncomplete = $tssubmission->handle_submission_info_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertFalse($issubmissioncomplete);
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $record->status);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS, $record->tiiattempts);
        $this->assertEquals(get_string('submissiondisplaystatus:unknown', 'plagiarism_turnitinsim'), $record->errormessage);
    }

    /**
     * Test the submission info status when turnitin returns error.
     */
    public function test_handle_submission_info_if_turnitin_returns_error() {
        global $DB;

        $this->resetAfterTest();

        $params = new stdClass();
        $params->httpstatus = 500;

        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm(1);
        $tssubmission->setuserid(1);
        $tssubmission->setsubmitter(1);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->setidentifier('6be293577b6b42bd04accd034bb40a8ca0b4bdd6');
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $issubmissioncomplete = $tssubmission->handle_submission_info_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertFalse($issubmissioncomplete);
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_UPLOADED, $record->status);
        $this->assertEquals(1, $record->tiiattempts);
        $this->assertGreaterThan(time(), $record->tiiretrytime);

        // Prepare to test scenario where max attempts is reached.
        $tssubmission->settiiattempts(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS - 1);
        $tssubmission->update();

        $issubmissioncomplete = $tssubmission->handle_submission_info_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertFalse($issubmissioncomplete);
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $record->status);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS, $record->tiiattempts);
        $this->assertEquals(get_string('submissiondisplaystatus:unknown', 'plagiarism_turnitinsim'), $record->errormessage);
    }

    /**
     * Test the submission info status when submission is in complete state.
     */
    public function test_handle_submission_info_if_submission_is_complete() {
        global $DB;

        $this->resetAfterTest();

        $params = new stdClass();
        $params->status = TURNITINSIM_SUBMISSION_STATUS_COMPLETE;

        $tssubmission = new plagiarism_turnitinsim_submission();
        $tssubmission->setcm(1);
        $tssubmission->setuserid(1);
        $tssubmission->setsubmitter(1);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->setidentifier('6be293577b6b42bd04accd034bb40a8ca0b4bdd6');
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $issubmissioncomplete = $tssubmission->handle_submission_info_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertTrue($issubmissioncomplete);
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_UPLOADED, $record->status);
        $this->assertEquals(0, $record->tiiattempts);
        $this->assertEquals(0, $record->tiiretrytime);
    }

    /**
     * Test the submission info status when submission is in error state.
     */
    public function test_handle_submission_info_if_submission_is_in_error_state() {
        global $DB;

        $this->resetAfterTest();

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->with(TURNITINSIM_ENDPOINT_LOGGING)
            ->willReturn('');

        $params = new stdClass();
        $params->status = TURNITINSIM_SUBMISSION_STATUS_ERROR;
        $params->error_code = TURNITINSIM_SUBMISSION_STATUS_TOO_LITTLE_TEXT;

        $tssubmission = new plagiarism_turnitinsim_submission($tsrequest);
        $tssubmission->setcm(1);
        $tssubmission->setuserid(1);
        $tssubmission->setsubmitter(1);
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->setidentifier('6be293577b6b42bd04accd034bb40a8ca0b4bdd6');
        $tssubmission->calculate_generation_time();
        $tssubmission->update();

        $issubmissioncomplete = $tssubmission->handle_submission_info_response($params);

        $record = $DB->get_record('plagiarism_turnitinsim_sub', ['cm' => 1]);

        $this->assertFalse($issubmissioncomplete);
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_ERROR, $record->status);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS, $record->tiiattempts);
        $this->assertEquals(0, $record->tiiretrytime);
        $this->assertEquals(TURNITINSIM_SUBMISSION_STATUS_TOO_LITTLE_TEXT, $record->errormessage);
    }
}
