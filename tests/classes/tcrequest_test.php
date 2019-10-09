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
 * Unit tests for plagiarism/turnitincheck/classes/tccallback.class.php.
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitincheck/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitincheck/classes/tccallback.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitincheck/tests/utilities.php');

/**
 * Tests for TurnitinCheck submission class
 *
 * @package turnitincheck
 */
class plagiarism_tcrequest_testcase extends advanced_testcase {

    /**
     * Set config for use in the tests.
     */
    public function setup() {
        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism');
        set_config('turnitinapikey', 1234, 'plagiarism');
        set_config('turnitinenablelogging', 0, 'plagiarism');
    }

    /**
     * Test connection test method.
     */
    public function test_connection_test() {
        $this->resetAfterTest();

        // Get the response for a successful connection test.
        $responsesuccess = file_get_contents(__DIR__ . '/../fixtures/get_webhook_success.json');
        $responsefailure = file_get_contents(__DIR__ . '/../fixtures/get_webhook_failure.json');

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_WEBHOOKS])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->exactly(2))
            ->method('send_request')
            ->willReturnOnConsecutiveCalls($responsesuccess, $responsefailure);

        // Test connection.
        $result = $tcrequest->test_connection("url", "key");

        // Test that the connection was successful.
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(HTTP_OK, $responsesuccessparsed['connection_status']);

        // Test connection when expecting a failure.
        $result = $tcrequest->test_connection("url", "key");

        // Test that the connection failed.
        $responsefailedparsed = (array)json_decode($result);
        $this->assertEquals(HTTP_BAD_REQUEST, $responsefailedparsed['connection_status']);
    }

    /**
     * Test that language and locale are returned as expected.
     */
    public function test_get_language() {
        global $SESSION;

        $this->resetAfterTest();

        // Test that a supported language is returned.
        $SESSION->lang = 'de';

        $tcrequest = new tcrequest();
        $lang = $tcrequest->get_language();
        $this->assertEquals('de', $lang->langcode);
        $this->assertEquals('de-DE', $lang->localecode);

        // Test that English is returned for an unsupported language.
        $SESSION->lang = 'fr';

        $lang = $tcrequest->get_language();
        $this->assertEquals('en', $lang->langcode);
        $this->assertEquals('en-US', $lang->localecode);
    }
}