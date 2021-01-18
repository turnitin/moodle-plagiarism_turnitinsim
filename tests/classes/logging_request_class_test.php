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

/**
 * Tests for Turnitin Integrity submission class.
 */
class logging_request_class_testcase extends advanced_testcase {


    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        // Set plugin as enabled in config for this module type.
        set_config('turnitinenableremotelogging', 1, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');
    }

    /**
     * Test send error to turnitin if remote logging is enabled.
     */
    public function test_send_error_to_turnitin_should_send_if_remote_logging_is_enabled() {
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

        $tsloggingrequest = new plagiarism_turnitinsim_logging_request('Error', $tsrequest);

        $tsloggingrequest->send_error_to_turnitin();
    }

    /**
     * Test send error to turnitin if remote logging is disabled.
     */
    public function test_send_error_to_turnitin_should_send_if_remote_logging_is_disabled() {
        $this->resetAfterTest();

        set_config('turnitinenableremotelogging', 0, 'plagiarism_turnitinsim');

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->getMock();

        // Mock API send request method.
        $tsrequest->expects($this->never())
            ->method('send_request')
            ->willReturn('');

        $tsloggingrequest = new plagiarism_turnitinsim_logging_request('Error', $tsrequest);

        $tsloggingrequest->send_error_to_turnitin();
    }

}