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
 * Unit tests for (some of) plagiarism/turnitinsim/classes/settings.class.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/defaults_form.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/settings.class.php');

/**
 * Tests for settings form.
 */
class settings_class_testcase extends advanced_testcase {

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        global $CFG;

        // Set API details in config.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', 1234, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');

        // Overwrite mtrace.
        $CFG->mtrace_wrapper = 'plagiarism_turnitinsim_mtrace';
    }

    /**
     * Test that save module settings saves the settings for a module.
     */
    public function test_save_module_settings() {
        global $DB;

        $this->resetAfterTest();

        // Create data object for new assignment.
        $data = new stdClass();
        $data->coursemodule = 1;
        $data->turnitinenabled = 1;
        $data->reportgenoptions['reportgeneration'] = TURNITINSIM_REPORT_GEN_DUEDATE;
        $data->queuedrafts = 1;
        $data->indexoptions['addtoindex'] = 0;
        $data->excludeoptions['excludequotes'] = 0;
        $data->excludeoptions['excludebiblio'] = 1;
        $data->accessoptions['accessstudents'] = 1;

        // Save Module Settings to test inserting.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Check settings have been saved.
        $settings = $DB->get_record('plagiarism_turnitinsim_mod', array('cm' => $data->coursemodule));

        $this->assertEquals(1, $settings->turnitinenabled);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_DUEDATE, $settings->reportgeneration);
        $this->assertEquals(1, $settings->queuedrafts);
        $this->assertEquals(0, $settings->addtoindex);
        $this->assertEquals(0, $settings->excludequotes);
        $this->assertEquals(1, $settings->excludebiblio);
        $this->assertEquals(1, $settings->accessstudents);

        // Change Module Settings to test updating.
        $data->excludeoptions['excludequotes'] = 1;
        $data->excludeoptions['excludebiblio'] = 0;

        // Save Module Settings again.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Check settings have been saved.
        $settings = $DB->get_record('plagiarism_turnitinsim_mod', array('cm' => $data->coursemodule));

        $this->assertEquals(1, $settings->excludequotes);
        $this->assertEquals(0, $settings->excludebiblio);
    }

    /**
     * Test that save module settings does not create an entry if Turnitin is disabled.
     */
    public function test_save_module_settings_does_not_create_entry_if_turnitin_disabled() {
        global $DB;

        $this->resetAfterTest();

        // Create data object for new assignment.
        $data = new stdClass();
        $data->coursemodule = 1;
        $data->turnitinenabled = 0;

        // Save Module Settings to test inserting.
        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);

        // Check that there is no entry for this module.
        $this->assertFalse($DB->get_record('plagiarism_turnitinsim_mod', array('cm' => $data->coursemodule)));
    }

    /**
     * Test that get_enabled_features does not return features on failure.
     */
    public function test_get_enabled_features_failure() {
        $this->resetAfterTest();

        // Get the response for a failed EULA version retrieval.
        $response = file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_failure.json');

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_FEATURES_ENABLED])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Get Features enabled.
        $tssettings = new plagiarism_turnitinsim_settings( $tsrequest );
        $result = $tssettings->get_enabled_features();

        // Test that the enabled features have not been retrieved.
        $this->assertFalse(isset($result->similarity));
    }

    /**
     * Test that get_enabled_features request to Turnitin fails with exception.
     */
    public function test_get_enabled_features_exception() {
        $this->resetAfterTest();

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_FEATURES_ENABLED])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->will($this->throwException(new Exception()));

        // Get Features enabled.
        $tssettings = new plagiarism_turnitinsim_settings($tsrequest);
        $result = $tssettings->get_enabled_features();

        // Test that the enabled features have not been retrieved.
        $this->assertFalse(isset($result->similarity));
    }

    /**
     * Test get latest eula version success request to Turnitin.
     */
    public function test_get_enabled_features_success() {
        $this->resetAfterTest();

        // Get the response for a failed EULA version retrieval.
        $response = file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json');

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_GET_FEATURES_ENABLED])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Get the latest EULA version.
        $tssettings = new plagiarism_turnitinsim_settings( $tsrequest );
        $result = $tssettings->get_enabled_features();

        // Test that the latest EULA version has been retrieved.
        $this->assertTrue(isset($result->similarity));
    }
}