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
 * Unit tests for (some of) plagiarism/turnitinsim/classes/tstask.class.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/tstask.class.php');

/**
 * Tests for TurnitinSim user class.
 *
 * @package turnitinsim
 */
class plagiarism_turnitinsim_task_class_testcase extends advanced_testcase {

    /**
     * Set config for use in the tests.
     */
    public function setup() {
        global $CFG;

        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism');
        set_config('turnitinapikey', 1234, 'plagiarism');
        set_config('turnitinenablelogging', 0, 'plagiarism');

        // Overwrite mtrace.
        $CFG->mtrace_wrapper = 'plagiarism_turnitinsim_mtrace';
    }

    /**
     * Test admin update
     */
    public function test_admin_update() {
        $this->resetAfterTest();

        // Mock settings class and get_enabled_features method.
        $tssettings = $this->getMockBuilder(tssettings::class)
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
        $tstask = new tstask($params);
        $this->assertTrue($tstask->admin_update());
    }

    /**
     * Test that test_webhook attempts to create a weebhook if one doesn't exist.
     */
    public function test_test_webhook() {
        $this->resetAfterTest();

        // Mock API callback request class and send call.
        $tscallback = $this->getMockBuilder(tscallback::class)
            ->setMethods(['create_webhook'])
            ->getMock();

        // Mock get_webhook request method.
        $tscallback->expects($this->once())
            ->method('create_webhook')
            ->willReturn('');

        $params = new stdClass();
        $params->tscallback = $tscallback;
        $tstask = new tstask($params);
        $this->assertTrue($tstask->test_webhook());
    }

    /**
     * Test that check_latest_eula_version sets the config variables as expected.
     */
    public function test_check_latest_eula_version() {
        $this->resetAfterTest();

        // Mock API eula request class and get latest version call.
        $tseula = $this->getMockBuilder(tseula::class)
            ->setMethods(['get_latest_version'])
            ->getMock();

        // Set features enabled.
        $latesteula = json_decode(file_get_contents(__DIR__ . '/../fixtures/get_latest_eula_version_success.json'));

        // Mock get_latest_version request method.
        $tseula->expects($this->once())
            ->method('get_latest_version')
            ->willReturn($latesteula);

        $params = new stdClass();
        $params->tseula = $tseula;
        $tstask = new tstask($params);
        $this->assertTrue($tstask->check_latest_eula_version());

        // Check version and url are set in config.
        $version = get_config('plagiarism', 'turnitin_eula_version');
        $url = get_config('plagiarism', 'turnitin_eula_url');

        $this->assertEquals($version, $latesteula->version);
        $this->assertEquals($url, $latesteula->url);
    }

    /*
     * Test that test_check_enabled_features sets the config as expected.
     */
    public function test_check_enabled_features() {
        $this->resetAfterTest();

        // Mock settings class and get_enabled_features method.
        $tssettings = $this->getMockBuilder(tssettings::class)
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
        $tstask = new tstask($params);
        $this->assertTrue($tstask->check_enabled_features());

        // Check features were set in config.
        $features = get_config('plagiarism', 'turnitin_features_enabled');
        $this->assertEquals($features, json_encode($featuresenabled));
    }

    /**
     * Test that the correct report gen score and request delays are returned.
     */
    public function test_get_report_gen_score_and_request_delay() {
        $this->resetAfterTest();
        $tstask = new tstask();

        // Verify that normal delays are returned.
        $this->assertEquals(TURNITINSIM_REPORT_GEN_SCORE_DELAY, $tstask->get_report_gen_score_delay());
        $this->assertEquals(TURNITINSIM_REPORT_GEN_REQUEST_DELAY, $tstask->get_report_gen_request_delay());

        // Verify that shorter delay is returned due to behat tests running.
        define('BEHAT_TEST', true);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_REQUEST_DELAY_TESTING, $tstask->get_report_gen_request_delay());
        $this->assertEquals(TURNITINSIM_REPORT_GEN_SCORE_DELAY_TESTING, $tstask->get_report_gen_score_delay());
    }

}