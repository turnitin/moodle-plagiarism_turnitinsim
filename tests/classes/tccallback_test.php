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
class plagiarism_tccallback_class_testcase extends advanced_testcase {

    /**
     * Set config for use in the tests.
     */
    public function setup() {
        global $CFG;

        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism');
        set_config('turnitinapikey', 1234, 'plagiarism');
        set_config('turnitinenablelogging', 0, 'plagiarism');

        $CFG->mtrace_wrapper = 'plagiarism_turnitincheck_mtrace';
    }

    /**
     * Test get webhook failed request to Turnitin.
     */
    public function test_get_webhook_failure() {
        $this->resetAfterTest();

        // Get the response for a failed webhook retrieval.
        $response = file_get_contents(__DIR__ . '/../fixtures/get_webhook_failure.json');

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_GET_WEBHOOK])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Get webhook.
        $tccallback = new tccallback( $tcrequest );
        $mockwebhookid = generate_uuid();
        $result = $tccallback->get_webhook($mockwebhookid);

        // Test that the webhook has not been retrieved.
        $this->assertFalse($result);
    }

    /**
     * Test get webhook request to Turnitin fails with exception.
     */
    public function test_get_webhook_exception() {
        $this->resetAfterTest();

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_GET_WEBHOOK])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->will($this->throwException(new Exception()));

        // Get webhook.
        $tccallback = new tccallback($tcrequest);
        $mockwebhookid = generate_uuid();
        $result = $tccallback->get_webhook($mockwebhookid);

        // Test that the webhook has not been retrieved.
        $this->assertTrue($result);
    }

    /**
     * Test get webhook success request to Turnitin.
     */
    public function test_get_webhook_failure_different_url() {
        $this->resetAfterTest();

        // Get the response for a successful webhook retrieval.
        $response = file_get_contents(__DIR__ . '/../fixtures/get_webhook_different_url.json');

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_GET_WEBHOOK])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Get webhook.
        $tccallback = new tccallback( $tcrequest );
        $mockwebhookid = generate_uuid();
        $result = $tccallback->get_webhook($mockwebhookid);

        // Test that the webhook should return false as the URL does not match the current site.
        $this->assertFalse($result);
    }

    /**
     * Test get webhook success request to Turnitin.
     */
    public function test_get_webhook_success() {
        $this->resetAfterTest();

        // Get the response for a successful webhook retrieval.
        $response = file_get_contents(__DIR__ . '/../fixtures/get_webhook_success.json');

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_GET_WEBHOOK])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Get webhook.
        $tccallback = new tccallback( $tcrequest );
        $mockwebhookid = generate_uuid();
        $result = $tccallback->get_webhook($mockwebhookid);

        // Test that the webhook should fail to retrieve as the URL does not match the current site.
        $this->assertTrue($result);
    }

    /**
     * Test create webhook failed request to Turnitin.
     */
    public function test_create_webhook_in_turnitin_failure() {
        $this->resetAfterTest();

        // Get the response for a failed webhook creation.
        $response = file_get_contents(__DIR__ . '/../fixtures/create_webhook_failure.json');

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_WEBHOOKS])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Test that the webhook does not exist.
        $this->assertFalse(get_config('plagiarism', 'turnitin_webhook_id'));

        // Create webhook.
        $tccallback = new tccallback( $tcrequest );
        $tccallback->create_webhook();

        // Test that the webhook has not been created.
        $this->assertFalse(get_config('plagiarism', 'turnitin_webhook_id'));
    }

    /**
     * Test create webhook failed request to Turnitin.
     */
    public function test_create_webhook_in_turnitin_exception() {
        $this->resetAfterTest();

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_WEBHOOKS])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->will($this->throwException(new Exception()));

        // Create webhook.
        $tccallback = new tccallback($tcrequest);
        $tccallback->create_webhook();

        // Test that the webhook has not been created.
        $this->assertFalse(get_config('plagiarism', 'turnitin_webhook_id'));
    }

    /**
     * Test create webhook successful request to Turnitin.
     */
    public function test_create_webhook_in_turnitin_success() {
        $this->resetAfterTest();

        // Get the response for a successfully created webhook.
        $response = file_get_contents(__DIR__ . '/../fixtures/create_webhook_success.json');
        $jsonresponse = (array)json_decode($response);

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_WEBHOOKS])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Test that the webhook does not exist.
        $this->assertFalse(get_config('plagiarism', 'turnitin_webhook_id'));

        // Create webhook.
        $tccallback = new tccallback( $tcrequest );
        $tccallback->create_webhook();

        // Test that the webhook is created.
        $this->assertEquals($jsonresponse["id"], get_config('plagiarism', 'turnitin_webhook_id'));
    }

    /**
     * Test delete webhook failed request to Turnitin.
     */
    public function test_delete_webhook_in_turnitin_failure() {
        $this->resetAfterTest();

        // Get the response for a failed webhook deletion.
        $response = file_get_contents(__DIR__ . '/../fixtures/delete_webhook_failure.json');

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_GET_WEBHOOK])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Delete webhook.
        $tccallback = new tccallback( $tcrequest );
        $mockwebhookid = generate_uuid();
        $result = $tccallback->delete_webhook($mockwebhookid);

        // Test that the webhook has not been deleted.
        $this->assertFalse($result);
    }

    /**
     * Test delete webhook request to Turnitin that throws exception.
     */
    public function test_delete_webhook_in_turnitin_exception() {
        $this->resetAfterTest();

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_WEBHOOKS])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->will($this->throwException(new Exception()));

        // Delete webhook.
        $tccallback = new tccallback($tcrequest);
        $mockwebhookid = generate_uuid();
        $result = $tccallback->delete_webhook($mockwebhookid);

        // Test that the webhook has not been deleted.
        $this->assertFalse($result);
    }

    /**
     * Test create webhook successful request to Turnitin.
     */
    public function test_delete_webhook_in_turnitin_success() {
        $this->resetAfterTest();

        // Get the response for a successfully created webhook.
        $response = file_get_contents(__DIR__ . '/../fixtures/delete_webhook_success.json');

        // Mock API request class.
        $tcrequest = $this->getMockBuilder(tcrequest::class)
            ->setMethods(['send_request'])
            ->setConstructorArgs([ENDPOINT_WEBHOOKS])
            ->getMock();

        // Mock API send request method.
        $tcrequest->expects($this->once())
            ->method('send_request')
            ->willReturn($response);

        // Delete webhook.
        $tccallback = new tccallback($tcrequest);
        $mockwebhookid = generate_uuid();
        $result = $tccallback->delete_webhook($mockwebhookid);

        // Test that the webhook is deleted.
        $this->assertTrue($result);
    }

    /**
     * Test that the expected_callback_signature() generates a 64 character length hash.
     */
    public function test_expected_callback_signature_generates_hash() {
        $this->resetAfterTest();

        $tccallback = new tccallback( new tcrequest() );
        $hash = $tccallback->expected_callback_signature('{"any": "string","but": "usually", "raw": "json"}');

        $this->assertRegExp( "/[0-9a-f]{64}/i", $hash);
    }
}