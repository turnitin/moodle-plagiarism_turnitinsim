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
 * Unit tests for (some of) plagiarism/turnitinsim/classes/setup_form.class.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/setup_form.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/utilities/handle_deprecation.php');

/**
 * Tests for settings form.
 */
class setupform_class_testcase extends advanced_testcase {

    /**
     * Plugin enabled.
     */
    const TURNITINSIM_ENABLED = 1;

    /**
     * Plugin disabled.
     */
    const TURNITINSIM_DISABLED = 0;

    /**
     * Sample API URL used for testing.
     */
    const TEST_API_URL = 'http://www.example.com';

    /**
     * Sample API key used for testing.
     */
    const TEST_API_KEY = 123456;

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();

        // Set routing URL.
        set_config('turnitinroutingurl', "https://www.example.com", 'plagiarism_turnitinsim');
    }

    /**
     * Test that save module settings saves the settings.
     */
    public function test_save_plugin_setup() {
        $this->resetAfterTest();

        // Create data object for new assignment.
        $data = new stdClass();
        $data->turnitinmodenabledassign = self::TURNITINSIM_ENABLED;
        $data->turnitinmodenabledforum = self::TURNITINSIM_ENABLED;
        $data->turnitinmodenabledworkshop = self::TURNITINSIM_ENABLED;
        $data->turnitinapiurl = self::TEST_API_URL;
        $data->turnitinapikey = self::TEST_API_KEY;
        $data->turnitinenablelogging = self::TURNITINSIM_ENABLED;
        $data->turnitinenableremotelogging = self::TURNITINSIM_ENABLED;
        $data->turnitinhideidentity = self::TURNITINSIM_ENABLED;
        $data->permissionoptions['turnitinviewerviewfullsource'] = self::TURNITINSIM_ENABLED;
        $data->permissionoptions['turnitinviewermatchsubinfo'] = self::TURNITINSIM_ENABLED;
        $data->permissionoptions['turnitinviewersavechanges'] = self::TURNITINSIM_ENABLED;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_setup_form();
        $form->save($data);

        // Check settings have been saved.
        $turnitinsimuse = plagiarism_plugin_turnitinsim::plugin_enabled();

        $settings = get_config('plagiarism_turnitinsim');

        $this->assertEquals(self::TURNITINSIM_ENABLED, $turnitinsimuse);
        $this->assertEquals(self::TURNITINSIM_ENABLED, $settings->turnitinmodenabledassign);
        $this->assertEquals(self::TURNITINSIM_ENABLED, $settings->turnitinmodenabledforum);
        $this->assertEquals(self::TURNITINSIM_ENABLED, $settings->turnitinmodenabledworkshop);
        $this->assertEquals(self::TURNITINSIM_ENABLED, $settings->turnitinenablelogging);
        $this->assertEquals(self::TURNITINSIM_ENABLED, $settings->turnitinenableremotelogging);
        $this->assertEquals(self::TEST_API_URL, $settings->turnitinapiurl);
        $this->assertEquals(self::TEST_API_KEY, $settings->turnitinapikey);
        $this->assertEquals(self::TURNITINSIM_ENABLED, $settings->turnitinhideidentity);
        $this->assertEquals(self::TURNITINSIM_ENABLED, $settings->turnitinviewerviewfullsource);
        $this->assertEquals(self::TURNITINSIM_ENABLED, $settings->turnitinviewermatchsubinfo);
        $this->assertEquals(self::TURNITINSIM_ENABLED, $settings->turnitinviewersavechanges);
    }

    /**
     * Test that save module settings saves with no plugin setup params configured.
     */
    public function test_save_plugin_setup_empty() {
        $this->resetAfterTest();

        // Save Module Settings with empty data object.
        $form = new plagiarism_turnitinsim_setup_form();
        $data = new stdClass();
        $form->save($data);

        // Check settings have been saved.
        $turnitinsimuse = plagiarism_plugin_turnitinsim::plugin_enabled();
        $settings = get_config('plagiarism_turnitinsim');

        $this->assertEquals(self::TURNITINSIM_DISABLED, $turnitinsimuse);
        $this->assertEquals(self::TURNITINSIM_DISABLED, $settings->turnitinmodenabledassign);
        $this->assertEquals(self::TURNITINSIM_DISABLED, $settings->turnitinmodenabledforum);
        $this->assertEquals(self::TURNITINSIM_DISABLED, $settings->turnitinmodenabledworkshop);
        $this->assertEquals('', $settings->turnitinapiurl);
        $this->assertEquals('', $settings->turnitinapikey);
        $this->assertEquals(self::TURNITINSIM_DISABLED, $settings->turnitinenablelogging);
        $this->assertEquals(self::TURNITINSIM_DISABLED, $settings->turnitinenableremotelogging);
        $this->assertEquals(self::TURNITINSIM_DISABLED, $settings->turnitinhideidentity);
        $this->assertEquals(self::TURNITINSIM_DISABLED, $settings->turnitinviewerviewfullsource);
        $this->assertEquals(self::TURNITINSIM_DISABLED, $settings->turnitinviewermatchsubinfo);
        $this->assertEquals(self::TURNITINSIM_DISABLED, $settings->turnitinviewersavechanges);
    }

    /**
     * Test that display outputs an HTML form.
     */
    public function test_display() {
        $form = new plagiarism_turnitinsim_setup_form();
        $output = $form->display();

        handle_deprecation::assertcontains($this, '</form>', $output);

        // Verify that FERPA statement is present.
        handle_deprecation::assertcontains($this, get_string('viewerpermissionferpa', 'plagiarism_turnitinsim'), $output);
    }

    /**
     * Test that displayed features returns empty output if the plugin is not configured.
     */
    public function test_display_features_not_configured() {
        $form = new plagiarism_turnitinsim_setup_form();
        $output = $form->display_features();

        $this->assertEmpty($output);
    }

    /**
     * Test that displayed features returns expected output if the plugin is configured and features are stored locally.
     */
    public function test_display_features_features_stored() {
        $this->resetAfterTest();

        // Create data object for new assignment.
        $data = new stdClass();
        $data->turnitinapiurl = self::TEST_API_URL;
        $data->turnitinapikey = self::TEST_API_KEY;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_setup_form();
        $form->save($data);

        // Get features enabled in config.
        $featuresenabled = file_get_contents(__DIR__.'/../fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');

        $form = new plagiarism_turnitinsim_setup_form();
        $output = $form->display_features();

        handle_deprecation::assertcontains($this, get_string('turnitinfeatures::moreinfo', 'plagiarism_turnitinsim'), $output);
    }
}