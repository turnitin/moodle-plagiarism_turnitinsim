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
 * Unit tests for plagiarism/turnitinsim/classes/callback.class.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/callback.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/utilities.php');

/**
 * Tests for Turnitin Integrity submission class.
 */
class request_class_testcase extends advanced_testcase {

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', 1234, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');
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
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_WEBHOOKS])
            ->getMock();

        // Test connection should return failed if url is missing.
        $result = $tsrequest->test_connection("", "key");
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_BAD_REQUEST, $responsesuccessparsed['connection_status']);

        // Test connection should return failed if key is missing.
        $result = $tsrequest->test_connection("url", "");
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_BAD_REQUEST, $responsesuccessparsed['connection_status']);

        // Test connection should return failed if url is invalid TII url.
        $result = $tsrequest->test_connection("http://abcd.tii.com", "key");
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_BAD_REQUEST, $responsesuccessparsed['connection_status']);

        // Test connection should return failed if url doesn't end with /api.
        $result = $tsrequest->test_connection("http://abcd.turnitin.com", "key");
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_BAD_REQUEST, $responsesuccessparsed['connection_status']);

        // Mock API send request method.
        $tsrequest->expects($this->exactly(6))
            ->method('send_request')
            ->willReturnOnConsecutiveCalls($responsesuccess, $responsesuccess, $responsesuccess, $responsesuccess,
                $responsesuccess, $responsefailure);

        // Test connection.
        $result = $tsrequest->test_connection("http://test.turnitin.com/api", "key");

        // Test that the connection was successful.
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_OK, $responsesuccessparsed['connection_status']);

        // Test connection.
        $result = $tsrequest->test_connection("http://test.turnitin.org/api", "key");

        // Test that the connection was successful.
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_OK, $responsesuccessparsed['connection_status']);

        // Test connection.
        $result = $tsrequest->test_connection("http://test.turnitin.dev/api", "key");

        // Test that the connection was successful.
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_OK, $responsesuccessparsed['connection_status']);

        // Test connection.
        $result = $tsrequest->test_connection("http://test.turnitinuk.com/api", "key");

        // Test that the connection was successful.
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_OK, $responsesuccessparsed['connection_status']);

        // Test connection.
        $result = $tsrequest->test_connection("http://test.tii-sandbox.com/api", "key");

        // Test that the connection was successful.
        $responsesuccessparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_OK, $responsesuccessparsed['connection_status']);

        // Test connection when expecting a failure.
        $result = $tsrequest->test_connection("http://test.turnitin.com/api", "key");

        // Test that the connection failed.
        $responsefailedparsed = (array)json_decode($result);
        $this->assertEquals(TURNITINSIM_HTTP_BAD_REQUEST, $responsefailedparsed['connection_status']);
    }

    /**
     * Test that a routing URL is returned if one does not already exist.
     */
    public function test_get_routing_url() {
        $this->resetAfterTest();

        $responsesuccess = file_get_contents(__DIR__ . '/../fixtures/where_am_i_success.json');
        $responsebad = file_get_contents(__DIR__ . '/../fixtures/where_am_i_missing_data.json');
        $responseunknown = file_get_contents(__DIR__ . '/../fixtures/where_am_i_unknown.json');

        // Mock API request class.
        $tsrequest = $this->getMockBuilder(plagiarism_turnitinsim_request::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([TURNITINSIM_ENDPOINT_WHERE_AM_I])
            ->getMock();

        $tsrequest->expects($this->exactly(3))
            ->method('send_request')
            ->willReturnOnConsecutiveCalls($responsesuccess, $responsebad, $responseunknown);

        // Test that the routing URL is returned.
        $this->assertEquals("https://".TURNITINSIM_EXTERNAL_USCALD, $tsrequest->get_routing_url());

        // Test that null is returned if the expected json parameter is missing.
        $this->assertEquals(null, $tsrequest->get_routing_url());

        // Test that null is returned if an unknown service-center is returned.
        $this->assertEquals(null, $tsrequest->get_routing_url());

        // Test that the existing routing URL is returned if already exists.
        set_config('turnitinroutingurl', "https://www.example.com", 'plagiarism_turnitinsim');
        $this->assertEquals("https://www.example.com", $tsrequest->get_routing_url());
    }

    /**
     * Test that language and locale are returned as expected.
     */
    public function test_get_language() {
        global $SESSION;

        $this->resetAfterTest();

        // Test that a supported language is returned.
        $SESSION->lang = 'de';

        $tsrequest = new plagiarism_turnitinsim_request();
        $lang = $tsrequest->get_language();
        $this->assertEquals('de', $lang->langcode);
        $this->assertEquals('de-DE', $lang->localecode);

        // Test that English is returned for an unsupported language.
        $SESSION->lang = 'fr';

        $lang = $tsrequest->get_language();
        $this->assertEquals('en', $lang->langcode);
        $this->assertEquals('en-US', $lang->localecode);
    }

    /**
     * Test that get_tii_url returns the correct URL depending on what's set in the database.
     */
    function test_get_tii_url() {
        $this->resetAfterTest();

        $tsrequest = new plagiarism_turnitinsim_request();

        $this->assertEquals("http://www.example.com", $tsrequest->get_tii_url());

        set_config('turnitinroutingurl', "https://".TURNITINSIM_EXTERNAL_USCALD, 'plagiarism_turnitinsim');

        // This should change to the routing URL if one is set.
        $tsrequest = new plagiarism_turnitinsim_request();
        $this->assertEquals("https://".TURNITINSIM_EXTERNAL_USCALD, $tsrequest->get_tii_url());
    }
}