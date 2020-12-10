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
 * Unit tests for (some of) plagiarism/turnitinsim/classes/task.class.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/task.class.php');

/**
 * Tests for Turnitin Integrity user class.
 */
class task_class_testcase extends advanced_testcase {

    /**
     * An example API URL used for unit testing.
     */
    const TURNITINSIM_API_URL = 'http://test.turnitin.com';

    /**
     * An example API key used for unit testing.
     */
    const TURNITINSIM_API_KEY = '123456';

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        global $CFG;

        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', 1234, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');

        // Overwrite mtrace.
        $CFG->mtrace_wrapper = 'plagiarism_turnitinsim_mtrace';
    }

    /**
     * Test admin update
     */
    public function test_admin_update() {
        $this->resetAfterTest();

        // Mock settings class and get_enabled_features method.
        $tssettings = $this->getMockBuilder(plagiarism_turnitinsim_settings::class)
            ->setMethods(['get_enabled_features'])
            ->getMock();

        // Get features enabled to return.
        $featuresenabled = json_decode(file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json'));

        // Mock send request method for upload.
        $tssettings->expects($this->once())
            ->method('get_enabled_features')
            ->willReturn($featuresenabled);

        $params = new stdClass();
        $params->tssettings = $tssettings;
        $tstask = new plagiarism_turnitinsim_task($params);
        $this->assertTrue($tstask->admin_update());
    }

    /**
     * Test that test_webhook attempts to create a weebhook if one doesn't exist.
     */
    public function test_test_webhook() {
        $this->resetAfterTest();

        // Mock API callback request class and send call.
        $tscallback = $this->getMockBuilder(plagiarism_turnitinsim_callback::class)
            ->setMethods(['create_webhook'])
            ->getMock();

        // Mock get_webhook request method.
        $tscallback->expects($this->once())
            ->method('create_webhook')
            ->willReturn('');

        $params = new stdClass();
        $params->tscallback = $tscallback;
        $tstask = new plagiarism_turnitinsim_task($params);
        $this->assertTrue($tstask->test_webhook());
    }

    /**
     * Test that check_latest_eula_version sets the config variables as expected.
     */
    public function test_check_latest_eula_version() {
        $this->resetAfterTest();

        // Set the features enabled.
        $featuresenabled = file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');

        // Mock API eula request class and get latest version call.
        $tseula = $this->getMockBuilder(plagiarism_turnitinsim_eula::class)
            ->setMethods(['get_latest_version'])
            ->getMock();

        // Set latest EULA version.
        $latesteula = json_decode(file_get_contents(__DIR__ . '/../fixtures/get_latest_eula_version_success.json'));

        // Mock get_latest_version request method.
        $tseula->expects($this->once())
            ->method('get_latest_version')
            ->willReturn($latesteula);

        $params = new stdClass();
        $params->tseula = $tseula;
        $tstask = new plagiarism_turnitinsim_task($params);
        $this->assertTrue($tstask->check_latest_eula_version());

        // Check version and url are set in config.
        $version = get_config('plagiarism_turnitinsim', 'turnitin_eula_version');
        $url = get_config('plagiarism_turnitinsim', 'turnitin_eula_url');

        $this->assertEquals($version, $latesteula->version);
        $this->assertEquals($url, $latesteula->url);
    }

    /**
     * Test that test_check_enabled_features sets the config as expected.
     */
    public function test_check_enabled_features() {
        $this->resetAfterTest();

        // Mock settings class and get_enabled_features method.
        $tssettings = $this->getMockBuilder(plagiarism_turnitinsim_settings::class)
            ->setMethods(['get_enabled_features'])
            ->getMock();

        // Get features enabled to return.
        $featuresenabled = json_decode(file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json'));

        // Mock send request method for upload.
        $tssettings->expects($this->once())
            ->method('get_enabled_features')
            ->willReturn($featuresenabled);

        $params = new stdClass();
        $params->tssettings = $tssettings;
        $tstask = new plagiarism_turnitinsim_task($params);
        $this->assertTrue($tstask->check_enabled_features());

        // Check features were set in config.
        $features = get_config('plagiarism_turnitinsim', 'turnitin_features_enabled');
        $this->assertEquals($features, json_encode($featuresenabled));
    }

    /**
     * Test that the correct report gen score and request delays are returned.
     */
    public function test_get_report_gen_score_and_request_delay() {
        $this->resetAfterTest();
        $tstask = new plagiarism_turnitinsim_task();

        // Verify that normal delays are returned.
        $this->assertEquals(TURNITINSIM_REPORT_GEN_SCORE_DELAY, $tstask->get_report_gen_score_delay());
        $this->assertEquals(TURNITINSIM_REPORT_GEN_REQUEST_DELAY, $tstask->get_report_gen_request_delay());

        // Verify that shorter delay is returned due to behat tests running.
        define('BEHAT_TEST', true);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_REQUEST_DELAY_TESTING, $tstask->get_report_gen_request_delay());
        $this->assertEquals(TURNITINSIM_REPORT_GEN_SCORE_DELAY_TESTING, $tstask->get_report_gen_score_delay());
    }

    /**
     * Test that run_task returns false if the plugin is not configured with API URL and API Key.
     */
    public function test_run_task_with_plugin_not_configured() {
        $this->resetAfterTest();

        // Set plugin as not enabled in config for this module type.
        set_config('turnitinapiurl', '', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', '', 'plagiarism_turnitinsim');

        $tstask = new plagiarism_turnitinsim_task();
        $this->assertFalse($tstask->run_task());
    }

    /**
     * Test that is_plugin_configured returns false if the plugin is not configured correctly with both of API URL or API Key.
     */
    public function test_run_task_with_plugin_partially_configured() {
        $this->resetAfterTest();

        // Set API URL but not Key.
        set_config('turnitinapiurl', self::TURNITINSIM_API_URL, 'plagiarism_turnitinsim');
        set_config('turnitinapikey', '', 'plagiarism_turnitinsim');

        $tstask = new plagiarism_turnitinsim_task();
        $this->assertFalse($tstask->run_task());

        // Set API Key but not URL.
        set_config('turnitinapiurl', '', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', self::TURNITINSIM_API_KEY, 'plagiarism_turnitinsim');

        $this->assertFalse($tstask->run_task());
    }

    /**
     * Test that is_plugin_configured returns true if the plugin is configured with API URL and API Key.
     */
    public function test_run_task_with_plugin_configured() {
        $this->resetAfterTest();

        // Set plugin as not enabled in config for this module type.
        set_config('turnitinapiurl', 'test.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', '123456', 'plagiarism_turnitinsim');

        $tstask = new plagiarism_turnitinsim_task();
        $this->assertTrue($tstask->run_task());
    }
}