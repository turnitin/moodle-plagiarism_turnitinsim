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
 * Unit tests for (some of) plagiarism/turnitinsim/lib.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/setup_form.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/utilities/handle_deprecation.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/utilities.php');

/**
 * Tests for lib methods.
 */
class plagiarism_turnitinsim_lib_testcase extends advanced_testcase {

    /**
     * Sample eula version for unit testing.
     */
    const EULA_VERSION_1 = 'EULA1';

    /**
     * Sample API URL for unit testing.
     */
    const TURNITINSIM_API_URL = 'http://test.turnitin.com';

    /**
     * Sample API key for unit testing.
     */
    const TURNITINSIM_API_KEY = '123456';

    /**
     * Get a list of activity modules that support plagiarism plugins.
     *
     * @return int|string
     * @throws coding_exception
     */
    public function get_module_that_supports_plagiarism() {
        $mods = core_component::get_plugin_list('mod');

        foreach ($mods as $mod => $modpath) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                return $mod;
            }
        }
    }

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
        $featuresenabled = file_get_contents(__DIR__ . '/fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');

        // Init. plugin class.
        $this->plugin = new plagiarism_plugin_turnitinsim();

        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();

        // Create a basic assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $this->assign = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $this->cm = get_coursemodule_from_instance('assign', $this->assign->id);

        // Create student user.
        $this->student1 = self::getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));

        // Create tsuser entry for student1.
        $this->student1ts = new plagiarism_turnitinsim_user($this->student1->id);

        // Enrol user on course.
        $this->getDataGenerator()->enrol_user($this->student1->id,
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

        // Set EULA data.
        set_config('turnitin_eula_version', self::EULA_VERSION_1, 'plagiarism_turnitinsim');
        set_config('plagiarism_turnitinsim', 'turnitin_eula_url', self::TURNITINSIM_API_URL);
        $this->eulaurl = get_config('plagiarism_turnitinsim', 'turnitin_eula_url', self::TURNITINSIM_API_URL);
    }

    /**
     * Save Form elements
     */
    public function test_save_form_elements() {
        global $DB;

        $this->resetAfterTest();

        // Create data object for new assignment.
        $data = new stdClass();
        $data->modulename = 'assign';
        $data->coursemodule = 1;
        $data->turnitinenabled = 1;

        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->save_form_elements($data);

        // Check settings are not saved.
        $settings = $DB->get_record('plagiarism_turnitinsim_mod', array('cm' => $data->coursemodule));

        $this->assertEmpty($settings);

        // Set plugin as enabled in config for this module type.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        $plugin->save_form_elements($data);

        // Check settings are saved.
        $settings = $DB->get_record('plagiarism_turnitinsim_mod', array('cm' => $data->coursemodule));

        $this->assertEquals(1, $settings->turnitinenabled);
    }

    /**
     * Test that get_links returns an empty div if there is no submission.
     */
    public function test_get_links_no_submission() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin as enabled in config for this module type.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $this->cm->id, 'turnitinenabled' => 1);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log instructor in.
        $this->setUser($this->instructor);

        // Do not provide a file in linkarray.
        $linkarray = array(
            'cmid' => $this->cm->id,
            'file' => array()
        );

        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals('<div class="turnitinsim_links"></div>', $plagiarismturnitinsim->get_links($linkarray));
    }

    /**
     * Test that get_links does not display the report if a student is not allowed to view reports.
     */
    public function test_get_links_student_view_reports() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin as enabled in config for this module type.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $this->cm->id, 'turnitinenabled' => 1, 'accessstudents' => 0);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log instructor in.
        $this->setUser($this->student1);

        // Do not provide a file in linkarray.
        $linkarray = array(
            'cmid' => $this->cm->id,
            'file' => array()
        );

        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals('<div class="turnitinsim_links"></div>', $plagiarismturnitinsim->get_links($linkarray));
    }

    /**
     * Test that a valid file passed to submit handler gets queued.
     */
    public function test_get_links_with_submission() {
        global $DB;

        $this->resetAfterTest();

        // Get course module data.
        $context = context_module::instance($this->cm->id);
        $assign = new assign($context, $this->cm, $this->course);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $modsettings = array('cm' => $this->cm->id, 'turnitinenabled' => 1, 'accessstudents' => 1);
        $DB->insert_record('plagiarism_turnitinsim_mod', $modsettings);

        // Log student1 in.
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
            "cmid" => $this->cm->id,
            "userid" => $this->student1->id,
            "file" => $file,
            "objectid" => $submission->id
        );

        // Create a Turnitin Integrity submission record that is queued for sending to Turnitin.
        $tssubmission = new plagiarism_turnitinsim_submission( new plagiarism_turnitinsim_request() );
        $tssubmission->setcm($this->cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier($file->get_pathnamehash());
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $tssubmission->setsubmittedtime(time());
        $tssubmission->setoverallscore(100);
        $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);
        $tssubmission->settogenerate(1);
        $tssubmission->setquizanswer(0);
        $tssubmission->update();

        // The HTML returned should contain the queued status and a Tii icon.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();

        handle_deprecation::assertcontains($this,
            '<span>'.get_string( 'submissiondisplaystatus:queued', 'plagiarism_turnitinsim').'</span>',
            $plagiarismturnitinsim->get_links($linkarray)
        );
        handle_deprecation::assertcontains($this, 'tii_icon', $plagiarismturnitinsim->get_links($linkarray));

        // Change submission status to Uploaded and verify that pending is displayed.
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_UPLOADED);
        $tssubmission->update();
        handle_deprecation::assertcontains($this,
            '<span>'.get_string( 'submissiondisplaystatus:pending', 'plagiarism_turnitinsim').'</span>',
            $plagiarismturnitinsim->get_links($linkarray)
        );

        // Change submission status to Uploaded and verify that not sent is displayed.
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_NOT_SENT);
        $tssubmission->update();
        handle_deprecation::assertcontains($this,
            '<span>'.get_string( 'submissiondisplaystatus:notsent', 'plagiarism_turnitinsim').'</span>',
            $plagiarismturnitinsim->get_links($linkarray)
        );

        // Change submission status to Requested and verify that pending is displayed.
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_REQUESTED);
        $tssubmission->update();
        handle_deprecation::assertcontains($this,
            '<span>'.get_string( 'submissiondisplaystatus:pending', 'plagiarism_turnitinsim').'</span>',
            $plagiarismturnitinsim->get_links($linkarray)
        );

        // Change submission status to Eula not accepted and verify that the error message is displayed.
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED);
        $tssubmission->update();
        $output = $plagiarismturnitinsim->get_links($linkarray);

        handle_deprecation::assertcontains($this,
            get_string('submissiondisplaystatus:awaitingeula', 'plagiarism_turnitinsim'),
            $output
        );
        handle_deprecation::assertcontains($this,
            get_string('submissiondisplayerror:eulanotaccepted_help', 'plagiarism_turnitinsim'),
            $output
        );
        // Log instructor in and check they do not see a resubmit link.
        $this->setUser($this->instructor);
        handle_deprecation::assertnotcontains($this,
            get_string('resubmittoturnitin', 'plagiarism_turnitinsim'),
            $output
        );

        // Change submission status to a non constant and verify that the default is displayed.
        $tssubmission->setstatus('nonconstantstring');
        $tssubmission->update();
        handle_deprecation::assertcontains($this,
            get_string( 'submissiondisplaystatus:unknown', 'plagiarism_turnitinsim'),
            $plagiarismturnitinsim->get_links($linkarray));

        // Change submission status to Error and verify that the error message is displayed.
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
        $tssubmission->seterrormessage(TURNITINSIM_SUBMISSION_STATUS_TOO_MUCH_TEXT);
        $tssubmission->update();
        handle_deprecation::assertcontains($this,
            get_string( 'submissiondisplayerror:toomuchtext', 'plagiarism_turnitinsim'),
            $plagiarismturnitinsim->get_links($linkarray)
        );

        // Change error message to generic and verify that it is displayed.
        $tssubmission->seterrormessage('random_string_that_is_not_a_constant');
        $tssubmission->update();
        handle_deprecation::assertcontains($this,
            get_string( 'submissiondisplayerror:generic', 'plagiarism_turnitinsim'),
            $plagiarismturnitinsim->get_links($linkarray)
        );

        // Log instructor in and check they see a resubmit link.
        $this->setUser($this->instructor);
        handle_deprecation::assertcontains($this,
            get_string( 'resubmittoturnitin', 'plagiarism_turnitinsim'),
            $plagiarismturnitinsim->get_links($linkarray)
        );

        // Check score is displayed if complete and correct css class applied.
        $score = 50;
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_COMPLETE);
        $tssubmission->setoverallscore($score);
        $tssubmission->update();
        handle_deprecation::assertcontains($this, $score.'%', $plagiarismturnitinsim->get_links($linkarray));
        handle_deprecation::assertcontains($this,
            'turnitinsim_or_score_colour_' . round($score, -1), $plagiarismturnitinsim->get_links($linkarray));
    }

    /**
     * Test that a resubmit link is rendered correctly.
     */
    public function test_render_resubmit_link() {
        $this->resetAfterTest();

        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $submissionid = 1;
        handle_deprecation::assertcontains($this,
            'pp_resubmit_id_'.$submissionid, $plagiarismturnitinsim->render_resubmit_link($submissionid));
    }

    /**
     * Test that is_plugin_active returns false if the plugin is not enabled for this module type.
     */
    public function test_is_plugin_active_not_enabled_for_mod_type() {
        $this->resetAfterTest();

        // Set plugin as not enabled in config for this module type.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 0, 'plagiarism_turnitinsim');

        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertFalse($plagiarismturnitinsim->is_plugin_active($this->cm));
    }

    /**
     * Test that is_plugin_active returns false if the plugin is not enabled.
     */
    public function test_is_plugin_active_not_enabled_for_mod() {
        $this->resetAfterTest();

        // Set plugin as enabled in config for this module type.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Disable plugin for module.
        $data = new stdClass();
        $data->modulename = 'assign';
        $data->coursemodule = $this->cm->id;
        $data->turnitinenabled = 0;

        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->save_form_elements($data);

        $this->assertFalse($plugin->is_plugin_active($this->cm));
    }

    /**
     * Test that is_plugin_configured returns false if the plugin is not configured with API URL and API Key.
     */
    public function test_is_plugin_configured_with_no_credentials_saved() {
        $this->resetAfterTest();

        // Set plugin as not enabled in config for this module type.
        set_config('turnitinapiurl', '', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', '', 'plagiarism_turnitinsim');

        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertFalse($plagiarismturnitinsim->is_plugin_configured($this->cm));
    }

    /**
     * Test that is_plugin_configured returns false if the plugin is not configured correctly with both of API URL or API Key.
     */
    public function test_is_plugin_configured_with_partial_credentials_saved() {
        $this->resetAfterTest();

        // Set API URL but not Key.
        set_config('turnitinapiurl', self::TURNITINSIM_API_URL, 'plagiarism_turnitinsim');
        set_config('turnitinapikey', '', 'plagiarism_turnitinsim');

        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertFalse($plagiarismturnitinsim->is_plugin_configured($this->cm));

        // Set API Key but not URL.
        set_config('turnitinapiurl', '', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', self::TURNITINSIM_API_KEY, 'plagiarism_turnitinsim');

        $this->assertFalse($plagiarismturnitinsim->is_plugin_configured($this->cm));
    }

    /**
     * Test that is_plugin_configured returns true if the plugin is configured with API URL and API Key.
     */
    public function test_is_plugin_configured_with_credentials_saved() {
        $this->resetAfterTest();

        // Set plugin as not enabled in config for this module type.
        set_config('turnitinapiurl', 'test.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', '123456', 'plagiarism_turnitinsim');

        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertTrue($plagiarismturnitinsim->is_plugin_configured($this->cm));
    }

    /**
     * Test that the EULA is output if the user has not accepted the latest version previously.
     */
    public function test_print_disclosure_display_latest() {
        $this->resetAfterTest();

        // Set plugin as enabled in config for this module type.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->modulename = 'assign';
        $data->coursemodule = $this->cm->id;
        $data->turnitinenabled = 1;
        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->save_form_elements($data);

        // Log student in.
        $this->setUser($this->student1);

        // Get locale.
        $tsrequest = new plagiarism_turnitinsim_request();
        $lang = $tsrequest->get_language();
        $eulaurl = $this->eulaurl."?lang=".$lang->localecode;

        // Verify EULA is output.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        handle_deprecation::assertcontains($this,
            get_string('eulalink', 'plagiarism_turnitinsim', $eulaurl),
            $plagiarismturnitinsim->print_disclosure($this->cm->id)
        );
    }

    /**
     * Test that the EULA is not output if the user has accepted the latest version previously.
     */
    public function test_print_disclosure_not_display_latest() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin as enabled in config for this module type.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->modulename = 'assign';
        $data->coursemodule = $this->cm->id;
        $data->turnitinenabled = 1;
        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->save_form_elements($data);

        // Accept EULA for student.
        $data = $DB->get_record('plagiarism_turnitinsim_users', ['userid' => $this->student1->id]);
        $data->lasteulaaccepted = self::EULA_VERSION_1;
        $DB->update_record('plagiarism_turnitinsim_users', $data);

        // Log student in.
        $this->setUser($this->student1);

        // Verify EULA is not output.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        handle_deprecation::assertcontains($this,
            get_string('eulaalreadyaccepted', 'plagiarism_turnitinsim'),
            $plagiarismturnitinsim->print_disclosure($this->cm->id));
    }

    /**
     * Test that the EULA is not output if it is not required at tenant level.
     */
    public function test_print_disclosure_eula_not_displayed_if_not_required() {
        $this->resetAfterTest();

        // Set plugin as enabled in config for this module type.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Set the features enabled.
        $featuresenabled = file_get_contents(__DIR__ . '/fixtures/get_features_enabled_eula_not_required.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->modulename = 'assign';
        $data->coursemodule = $this->cm->id;
        $data->turnitinenabled = 1;
        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->save_form_elements($data);

        // Log student in.
        $this->setUser($this->student1);

        // Verify EULA is not output.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        handle_deprecation::assertcontains($this,
            get_string('eulanotrequired', 'plagiarism_turnitinsim'),
            $plagiarismturnitinsim->print_disclosure($this->cm->id));
    }

    /**
     * Test that correct settings are returned.
     */
    public function test_get_settings() {
        global $CFG;

        $this->resetAfterTest();

        // Use settings form method to save data for a module.
        require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/settings.class.php');

        // Create data object for new assignment.
        $data = new stdClass();
        $data->coursemodule = 1;
        $data->turnitinenabled = 1;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $settings = $plagiarismturnitinsim->get_settings(1, $fields = '*');

        $this->assertEquals(1, $settings->turnitinenabled);
    }

    /**
     * Test that submit handler will not process a file for a course module that does not exist.
     */
    public function test_submit_handler_no_cmid() {
        global $DB;

        $this->resetAfterTest();

        // Create dummy event data.
        $cmid = 0;
        $eventdata = array(
            'contextinstanceid' => $cmid,
            'other' => array (
                'modulename' => $this->get_module_that_supports_plagiarism()
            )
        );

        // Handler should always return true despite cm not existing.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be no records in submissions table.
        $recordcount = $DB->count_records('plagiarism_turnitinsim_sub', array('cm' => $cmid));
        $this->assertEquals(0, $recordcount);
    }

    /**
     * Test that submit handler will not process a file if the plugin is not enabled for this module.
     */
    public function test_submit_handler_plugin_not_enabled() {
        global $DB;

        $this->resetAfterTest();

        // Create dummy event data.
        $eventdata = array(
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'modulename' => $this->get_module_that_supports_plagiarism()
            )
        );

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(0);
        set_config('turnitinmodenabledassign', 0, 'plagiarism_turnitinsim');

        // Handler should always return true despite plugin not being enabled.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be no records in submissions table.
        $recordcount = $DB->count_records('plagiarism_turnitinsim_sub', array('cm' => $this->assign->cmid));
        $this->assertEquals(0, $recordcount);
    }

    /**
     * Test that a file which doesn't exist gets saved by submit handler.
     */
    public function test_submit_handler_empty_file_saved() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->coursemodule = $this->assign->cmid;
        $data->turnitinenabled = 1;
        $data->queuedrafts = 1;

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Create dummy event data.
        $eventdata = array(
            'userid' => $this->student1->id,
            'relateduserid' => $this->student1->id,
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'pathnamehashes' => array(
                    0 => 'HASH THAT DOES NOT EXIST'
                ),
                'modulename' => $this->get_module_that_supports_plagiarism()
            ),
            'objectid' => 1,
            'eventtype' => 'file_uploaded'
        );

        // Handler should always return true despite file being empty.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be one record in the submissions table.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub', 'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_EMPTY_DELETED));

        $this->assertEquals(1, $recordcount);
    }

    /**
     * Test that a valid file passed to submit handler gets queued.
     */
    public function test_submit_handler_file_queued_after_accepting_eula() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->coursemodule = $this->assign->cmid;
        $data->turnitinenabled = 1;
        $data->queuedrafts = 1;

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create test file.
        $file = create_test_file(0, $usercontext->id, 'user', 'draft');

        // Create dummy event data.
        $eventdata = array(
            'userid' => $this->student1->id,
            'relateduserid' => $this->student1->id,
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'pathnamehashes' => array(
                    0 => $file->get_pathnamehash()
                ),
                'modulename' => $this->get_module_that_supports_plagiarism()
            ),
            'objectid' => 1,
            'eventtype' => 'file_uploaded'
        );

        // Handler should return true.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be one record in the submissions table awaiting EULA acceptance.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub', 'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED));

        $this->assertEquals(1, $recordcount);

        // Accept EULA for student.
        $data = $DB->get_record('plagiarism_turnitinsim_users', ['userid' => $this->student1->id]);
        $data->lasteulaaccepted = self::EULA_VERSION_1;
        $DB->update_record('plagiarism_turnitinsim_users', $data);

        // Resubmit file.
        $plagiarismturnitinsim->submission_handler($eventdata);

        // There should now be one queued record.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub', 'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_QUEUED));

        $this->assertEquals(1, $recordcount);
    }

    /**
     * Test that a valid file passed to submit handler gets queued without accepting the EULA as it is not required.
     */
    public function test_submit_handler_file_queued_without_requiring_eula() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Set the features enabled.
        $featuresenabled = file_get_contents(__DIR__ . '/fixtures/get_features_enabled_eula_not_required.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->coursemodule = $this->assign->cmid;
        $data->turnitinenabled = 1;
        $data->queuedrafts = 1;

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create test file.
        $file = create_test_file(0, $usercontext->id, 'user', 'draft');

        // Create dummy event data.
        $eventdata = array(
            'userid' => $this->student1->id,
            'relateduserid' => $this->student1->id,
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'pathnamehashes' => array(
                    0 => $file->get_pathnamehash()
                ),
                'modulename' => $this->get_module_that_supports_plagiarism()
            ),
            'objectid' => 1,
            'eventtype' => 'file_uploaded'
        );

        // Handler should return true.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be no records in the submissions table awaiting EULA acceptance.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub', 'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED));

        $this->assertEquals(0, $recordcount);

        // There should instead be one queued record.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub', 'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_QUEUED));

        $this->assertEquals(1, $recordcount);
    }

    /**
     * Test that a valid file passed to submit handler gets queued.
     */
    public function test_submit_handler_file_queued_and_saves_group_id() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Create group.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        // Enrol students in group.
        groups_add_member($group, $this->student1);

        // Enable plugin for module.
        $data = new stdClass();
        $data->coursemodule = $this->assign->cmid;
        $data->turnitinenabled = 1;
        $data->queuedrafts = 1;

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Create assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->teamsubmission = 1;
        $module = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $module->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Create assignment submission.
        $submission = $assign->get_group_submission($this->student1->id, $group->id, true);
        $data = new stdClass();
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        // Create test file.
        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create dummy event data.
        $eventdata = array(
            'userid' => $this->student1->id,
            'relateduserid' => $this->student1->id,
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'pathnamehashes' => array(
                    0 => $file->get_pathnamehash()
                ),
                'modulename' => $this->get_module_that_supports_plagiarism()
            ),
            'objectid' => $submission->id,
            'eventtype' => 'file_uploaded'
        );

        // Accept EULA for student.
        $data = $DB->get_record('plagiarism_turnitinsim_users', ['userid' => $this->student1->id]);
        $data->lasteulaaccepted = self::EULA_VERSION_1;
        $DB->update_record('plagiarism_turnitinsim_users', $data);

        // Handler should return true.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should now be a queued record with the group id stored.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub',
            'cm = ? AND userid = ? AND groupid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, $group->id, TURNITINSIM_SUBMISSION_STATUS_QUEUED));

        $this->assertEquals(1, $recordcount);
    }

    /**
     * Test that if a user submits the same valid file, it gets queued using the same submission record.
     */
    public function test_submit_handler_file_queues_same_file() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->coursemodule = $this->assign->cmid;
        $data->turnitinenabled = 1;
        $data->queuedrafts = 1;

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Accept EULA for student.
        $student = $DB->get_record('plagiarism_turnitinsim_users', array('userid' => $this->student1->id));
        $student->lasteulaaccepted = self::EULA_VERSION_1;
        $DB->update_record('plagiarism_turnitinsim_users', $student);

        // Create test file.
        $file = create_test_file(0, $usercontext->id, 'user', 'draft');

        // Create dummy event data.
        $eventdata = array(
            'userid' => $this->student1->id,
            'relateduserid' => $this->student1->id,
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'pathnamehashes' => array(
                    0 => $file->get_pathnamehash()
                ),
                'modulename' => $this->get_module_that_supports_plagiarism()
            ),
            'objectid' => 1,
            'eventtype' => 'file_uploaded'
        );

        // Handler should return true.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be one record in the submissions table.
        $recordcount = $DB->count_records_select(
            'plagiarism_turnitinsim_sub',
            'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_QUEUED)
        );
        $this->assertEquals(1, $recordcount);

        // Resubmit the same file.
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should still be one record in the submissions table.
        $recordcount = $DB->count_records_select(
            'plagiarism_turnitinsim_sub',
            'cm = ? AND userid = ? AND identifier = ?',
            array($this->assign->cmid, $this->student1->id, $file->get_pathnamehash())
        );
        $this->assertEquals(1, $recordcount);
    }

    /**
     * Test that if a user submits the same valid file, it doesn't get requeued if already processed unless
     * the file was modified after the submission time.
     */
    public function test_submit_handler_file_requeueing_previously_submitted_files() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->coursemodule = $this->assign->cmid;
        $data->turnitinenabled = 1;
        $data->queuedrafts = 1;

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Accept EULA for student.
        $student = $DB->get_record('plagiarism_turnitinsim_users', array('userid' => $this->student1->id));
        $student->lasteulaaccepted = self::EULA_VERSION_1;
        $DB->update_record('plagiarism_turnitinsim_users', $student);

        // Create test file.
        $file = create_test_file(0, $usercontext->id, 'user', 'draft');

        // Create dummy event data.
        $eventdata = array(
            'userid' => $this->student1->id,
            'relateduserid' => $this->student1->id,
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'pathnamehashes' => array(
                    0 => $file->get_pathnamehash()
                ),
                'modulename' => $this->get_module_that_supports_plagiarism()
            ),
            'objectid' => 1,
            'eventtype' => 'file_uploaded'
        );

        // Handler should return true.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // Check that file was processed.
        $submission = $DB->get_record_select(
            'plagiarism_turnitinsim_sub',
            'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_QUEUED)
        );
        $this->assertEquals($submission->identifier, $file->get_pathnamehash());

        // Update submission to have a status of completed and submitted time after when file was last modified.
        $submission->status = TURNITINSIM_SUBMISSION_STATUS_COMPLETE;
        $submission->submittedtime = $file->get_timemodified() + 1;
        $DB->update_record('plagiarism_turnitinsim_sub', $submission);

        // Resubmit the same file.
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // The submission should not have been requeued.
        $submission = $DB->get_record_select(
            'plagiarism_turnitinsim_sub',
            'cm = ? AND userid = ? AND identifier = ?',
            array($this->assign->cmid, $this->student1->id, $file->get_pathnamehash())
        );
        $this->assertEquals($submission->status, TURNITINSIM_SUBMISSION_STATUS_COMPLETE);

        // Update submission to have a status of completed and submitted time before when file was last modified.
        $submission->status = TURNITINSIM_SUBMISSION_STATUS_COMPLETE;
        $submission->submittedtime = $file->get_timemodified() - 1;
        $DB->update_record('plagiarism_turnitinsim_sub', $submission);

        // Resubmit the same file.
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // The submission should have been requeued.
        $submission = $DB->get_record_select(
            'plagiarism_turnitinsim_sub',
            'cm = ? AND userid = ? AND identifier = ?',
            array($this->assign->cmid, $this->student1->id, $file->get_pathnamehash())
        );
        $this->assertEquals($submission->status, TURNITINSIM_SUBMISSION_STATUS_QUEUED);
    }

    /**
     * Test that valid text content passed to submit handler gets queued.
     */
    public function test_submit_handler_text_content_queued() {
        global $DB;

        $this->resetAfterTest();

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->coursemodule = $this->assign->cmid;
        $data->turnitinenabled = 1;
        $data->queuedrafts = 1;

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Log new user in.
        $this->setUser($this->student1);

        // Accept EULA for student.
        $data = $DB->get_record('plagiarism_turnitinsim_users', ['userid' => $this->student1->id]);
        $data->lasteulaaccepted = self::EULA_VERSION_1;
        $DB->update_record('plagiarism_turnitinsim_users', $data);

        // Create dummy event data.
        $textcontent = "This is text content for unit testing a text submission.";
        $eventdata = array(
            'userid' => $this->student1->id,
            'relateduserid' => $this->student1->id,
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'content' => $textcontent,
                'modulename' => $this->get_module_that_supports_plagiarism()
            ),
            'objectid' => 1,
            'eventtype' => 'content_uploaded'
        );

        // Handler should return true.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be one record in the submissions table.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub', 'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_QUEUED));

        $this->assertEquals(1, $recordcount);
    }

    /**
     * Test that draft files are queued for sending to Turnitin.
     */
    public function test_submit_handler_queues_draft() {
        global $DB;

        $this->resetAfterTest();

        // Create an assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 1;
        $this->assign = $this->getDataGenerator()->create_module('assign', $record);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->coursemodule = $this->assign->cmid;
        $data->turnitinenabled = 1;
        $data->queuedrafts = 1;

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Log student1 in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Accept EULA for student.
        $data = $DB->get_record('plagiarism_turnitinsim_users', ['userid' => $this->student1->id]);
        $data->lasteulaaccepted = self::EULA_VERSION_1;
        $DB->update_record('plagiarism_turnitinsim_users', $data);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $data->status = 'draft';
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create dummy event data.
        $eventdata = array(
            'userid' => $this->student1->id,
            'relateduserid' => $this->student1->id,
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'pathnamehashes' => array(
                    0 => $file->get_pathnamehash()
                ),
                'modulename' => 'assign'
            ),
            'objectid' => 1,
            'eventtype' => 'file_uploaded'
        );

        // Handler should return true.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be one record in the submissions table.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub', 'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_QUEUED));

        $this->assertEquals(1, $recordcount);
    }

    /**
     * Test that draft files are not queued for sending to Turnitin but the final submission is.
     */
    public function test_submit_handler_does_not_queue_draft_but_queues_final_submission() {
        global $DB;

        $this->resetAfterTest();

        // Create an assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $record->submissiondrafts = 1;
        $this->assign = $this->getDataGenerator()->create_module('assign', $record);

        // Set plugin config.
        plagiarism_plugin_turnitinsim::enable_plugin(1);
        set_config('turnitinmodenabledassign', 1, 'plagiarism_turnitinsim');

        // Enable plugin for module.
        $data = new stdClass();
        $data->coursemodule = $this->assign->cmid;
        $data->turnitinenabled = 1;
        $data->queuedrafts = 0;

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Log student1 in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        // Accept EULA for student.
        $data = $DB->get_record('plagiarism_turnitinsim_users', ['userid' => $this->student1->id]);
        $data->lasteulaaccepted = self::EULA_VERSION_1;
        $DB->update_record('plagiarism_turnitinsim_users', $data);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);
        $context = context_module::instance($cm->id);
        $assign = new assign($context, $cm, $record->course);

        // Create assignment submission.
        $submission = $assign->get_user_submission($this->student1->id, true);
        $data = new stdClass();
        $data->status = 'draft';
        $plugin = $assign->get_submission_plugin_by_type('file');
        $plugin->save($submission, $data);

        $file = create_test_file($submission->id, $usercontext->id, 'mod_assign', 'submissions');

        // Create dummy event data.
        $eventdata = array(
            'userid' => $this->student1->id,
            'relateduserid' => $this->student1->id,
            'contextinstanceid' => $this->assign->cmid,
            'other' => array (
                'pathnamehashes' => array(
                    0 => $file->get_pathnamehash()
                ),
                'modulename' => 'assign'
            ),
            'objectid' => 1,
            'eventtype' => 'file_uploaded'
        );

        // Handler should return true.
        $plagiarismturnitinsim = new plagiarism_plugin_turnitinsim();
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be a record in the submissions table flagged as not sent as we aren't sending drafts.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub', 'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_NOT_SENT));

        $this->assertEquals(1, $recordcount);

        // Finalise submission.
        $data = new stdClass();
        $data->status = 'submitted';
        $plugin->save($submission, $data);

        // Handler should return true.
        $eventdata['eventtype'] = 'assessable_submitted';
        $this->assertEquals(true, $plagiarismturnitinsim->submission_handler($eventdata));

        // There should be one record in the submissions table as it should be queued.
        $recordcount = $DB->count_records_select('plagiarism_turnitinsim_sub', 'cm = ? AND userid = ? AND status = ?',
            array($this->assign->cmid, $this->student1->id, TURNITINSIM_SUBMISSION_STATUS_QUEUED));

        $this->assertEquals(1, $recordcount);
    }

    /**
     * Test that the generation time for a submission is set correctly when a module updates.
     */
    public function test_module_updated() {
        $this->resetAfterTest();

        // Log instructor in.
        $this->setUser($this->instructor);

        // Create an assign module.
        $record = new stdClass();
        $record->course = $this->course;
        $duedate = time() + (60 * 60 * 2);
        $record->duedate = $duedate;
        $this->assign = $this->getDataGenerator()->create_module('assign', $record);

        // Get course module data.
        $cm = get_coursemodule_from_instance('assign', $this->assign->id);

        // Create data object for cm assignment.
        $data = new stdClass();
        $data->coursemodule = $cm->id;
        $data->turnitinenabled = 1;
        $data->reportgenoptions['reportgeneration'] = TURNITINSIM_REPORT_GEN_IMMEDIATE_AND_DUEDATE;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Create dummy turnitin submission.
        $tssubmission = new plagiarism_turnitinsim_submission( new plagiarism_turnitinsim_request() );
        $tssubmission->setcm($cm->id);
        $tssubmission->setuserid($this->student1->id);
        $tssubmission->setsubmitter($this->student1->id);
        $tssubmission->setidentifier('PATHNAMEHASH');
        $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
        $tssubmission->settogenerate(1);
        $tssubmission->setgenerationtime($duedate + 1);
        $tssubmission->update();

        // Create dummy event data.
        $eventdata = array(
            'objectid' => $cm->id
        );

        // Mirror triggered event to update module.
        $this->plugin->module_updated($eventdata);

        // Check submission was updated.
        $tssubmission = new plagiarism_turnitinsim_submission( new plagiarism_turnitinsim_request(), $tssubmission->id );
        $this->assertEquals($duedate, $tssubmission->getgenerationtime());
        $this->assertEquals(1, $tssubmission->gettogenerate());
    }

    /**
     * Test the enable_plugin and plugin_enabled methods.
     */
    public function test_enable_plugin() {
        $this->resetAfterTest();

        set_config('branch', 38);

        plagiarism_plugin_turnitinsim::enable_plugin(1);
        $this->assertEquals(1, plagiarism_plugin_turnitinsim::plugin_enabled());

        plagiarism_plugin_turnitinsim::enable_plugin(0);
        $this->assertEquals(0, plagiarism_plugin_turnitinsim::plugin_enabled());

        set_config('branch', 39);

        plagiarism_plugin_turnitinsim::enable_plugin(1);
        $this->assertEquals(1, plagiarism_plugin_turnitinsim::plugin_enabled());

        plagiarism_plugin_turnitinsim::enable_plugin(0);
        $this->assertEquals(0, plagiarism_plugin_turnitinsim::plugin_enabled());

        plagiarism_plugin_turnitinsim::enable_plugin(null);
        $this->assertEquals(null, plagiarism_plugin_turnitinsim::plugin_enabled());
    }
}
