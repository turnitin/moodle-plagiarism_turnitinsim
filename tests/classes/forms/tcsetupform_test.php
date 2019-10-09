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
 * Unit tests for (some of) plagiarism/turnitincheck/classes/forms/tcsetupform.class.php.
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitincheck/classes/forms/tcsetupform.class.php');

/**
 * Tests for settings form.
 *
 * @package turnitincheck
 */
class plagiarism_tcsetupform_class_testcase extends advanced_testcase {

    const TURNITINCHECK_ENABLED = 1;
    const TURNITINCHECK_DISABLED = 0;
    const TEST_API_URL = 'http://www.example.com';
    const TEST_API_KEY = 123456;

    /**
     * Test that save module settings saves the settings.
     */
    public function test_save_plugin_setup() {
        $this->resetAfterTest();

        // Create data object for new assignment.
        $data = new stdClass();
        $data->turnitinmodenabledassign = self::TURNITINCHECK_ENABLED;
        $data->turnitinmodenabledforum = self::TURNITINCHECK_ENABLED;
        $data->turnitinmodenabledworkshop = self::TURNITINCHECK_ENABLED;
        $data->turnitinapiurl = self::TEST_API_URL;
        $data->turnitinapikey = self::TEST_API_KEY;
        $data->turnitinenablelogging = self::TURNITINCHECK_ENABLED;
        $data->turnitinhideidentity = self::TURNITINCHECK_ENABLED;
        $data->permissionoptions['turnitinviewerviewfullsource'] = self::TURNITINCHECK_ENABLED;
        $data->permissionoptions['turnitinviewermatchsubinfo'] = self::TURNITINCHECK_ENABLED;

        // Save Module Settings.
        $form = new tcsetupform();
        $form->save($data);

        // Check settings have been saved.
        $settings = get_config('plagiarism');

        $this->assertEquals(self::TURNITINCHECK_ENABLED, $settings->turnitincheck_use);
        $this->assertEquals(self::TURNITINCHECK_ENABLED, $settings->turnitinmodenabledassign);
        $this->assertEquals(self::TURNITINCHECK_ENABLED, $settings->turnitinmodenabledforum);
        $this->assertEquals(self::TURNITINCHECK_ENABLED, $settings->turnitinmodenabledworkshop);
        $this->assertEquals(self::TURNITINCHECK_ENABLED, $settings->turnitinenablelogging);
        $this->assertEquals(self::TEST_API_URL, $settings->turnitinapiurl);
        $this->assertEquals(self::TEST_API_KEY, $settings->turnitinapikey);
        $this->assertEquals(self::TURNITINCHECK_ENABLED, $settings->turnitinhideidentity);
        $this->assertEquals(self::TURNITINCHECK_ENABLED, $settings->turnitinviewerviewfullsource);
        $this->assertEquals(self::TURNITINCHECK_ENABLED, $settings->turnitinviewermatchsubinfo);
    }

    /**
     * Test that save module settings saves with no plugin setup params configured.
     */
    public function test_save_plugin_setup_empty() {
        $this->resetAfterTest();

        // Save Module Settings with empty data object.
        $form = new tcsetupform();
        $data = new stdClass();
        $form->save($data);

        // Check settings have been saved.
        $settings = get_config('plagiarism');

        $this->assertEquals(self::TURNITINCHECK_DISABLED, $settings->turnitincheck_use);
        $this->assertEquals(self::TURNITINCHECK_DISABLED, $settings->turnitinmodenabledassign);
        $this->assertEquals(self::TURNITINCHECK_DISABLED, $settings->turnitinmodenabledforum);
        $this->assertEquals(self::TURNITINCHECK_DISABLED, $settings->turnitinmodenabledworkshop);
        $this->assertEquals('', $settings->turnitinapiurl);
        $this->assertEquals('', $settings->turnitinapikey);
        $this->assertEquals(self::TURNITINCHECK_DISABLED, $settings->turnitinenablelogging);
        $this->assertEquals(self::TURNITINCHECK_DISABLED, $settings->turnitinhideidentity);
        $this->assertEquals(self::TURNITINCHECK_DISABLED, $settings->turnitinviewerviewfullsource);
        $this->assertEquals(self::TURNITINCHECK_DISABLED, $settings->turnitinviewermatchsubinfo);
    }

    /**
     * Test that display outputs an HTML form.
     */
    public function test_display() {
        $form = new tcsetupform();
        $output = $form->display();

        $this->assertContains('</form>', $output);

        // Verify that FERPA statement is present.
        $this->assertContains(get_string('viewerpermissionferpa', 'plagiarism_turnitincheck'), $output);
    }

    /**
     * Test that displayed features returns empty output if the plugin is not configured.
     */
    public function test_display_features_not_configured() {
        $form = new tcsetupform();
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
        $form = new tcsetupform();
        $form->save($data);

        // Get features enabled in config.
        $featuresenabled = file_get_contents(__DIR__ . '/../../fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism');

        $form = new tcsetupform();
        $output = $form->display_features();

        $this->assertContains(get_string('turnitinfeatures::moreinfo', 'plagiarism_turnitincheck'), $output);
    }
}