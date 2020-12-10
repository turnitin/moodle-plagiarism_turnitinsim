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
}