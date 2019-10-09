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
 * Class for handling callbacks from Turnitin.
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_turnitincheck\message\get_webhook_failure;

defined('MOODLE_INTERNAL') || die();

class tccallback {

    public $tcrequest;

    public function __construct( tcrequest $tcrequest = null ) {
        $this->tcrequest = $tcrequest;
    }

    /**
     * Attempt to retrieve the webhook.
     *
     * @param $webhookid
     * @return bool
     */
    public function get_webhook($webhookid) {
        // Make request to get webhook.
        try {
            $endpoint = ENDPOINT_GET_WEBHOOK;
            $endpoint = str_replace('{{webhook_id}}', $webhookid, $endpoint);
            $response = $this->tcrequest->send_request($endpoint, json_encode(array()), 'GET');
            $responsedata = json_decode($response);

            // Return false if the webhook could not be retrieved.
            if ($responsedata->httpstatus != HTTP_OK) {
                mtrace(get_string('taskoutputwebhooknotretrieved', 'plagiarism_turnitincheck', $webhookid));
                return false;
            }

            // If the webhook URL does not match this Moodle site then return false.
            if ($responsedata->url != TURNITINCHECK_CALLBACK_URL) {
                return false;
            }

            mtrace(get_string('taskoutputwebhookretrieved', 'plagiarism_turnitincheck', $webhookid));
            return true;

        } catch (Exception $e) {
            $this->tcrequest->handle_exception($e, 'taskoutputwebhookretrievalfailure');

            // Send message to admins.
            $message = new get_webhook_failure();
            $message->send_message();

            // Return true here as we don't want to continually recreate webhooks if there is an underlying issue.
            return true;
        }
    }

    /**
     * Attempt to delete the webhook (not currently used).
     *
     * @param $webhookid
     * @return bool
     */
    public function delete_webhook($webhookid) {
        // Make request to get webhook.
        try {
            $endpoint = ENDPOINT_GET_WEBHOOK;
            $endpoint = str_replace('{{webhook_id}}', $webhookid, $endpoint);
            $response = $this->tcrequest->send_request($endpoint, json_encode(array()), 'DELETE');
            $responsedata = json_decode($response);

            if ($responsedata->httpstatus == HTTP_NO_CONTENT) {
                mtrace(get_string('taskoutputwebhookdeleted', 'plagiarism_turnitincheck', $webhookid));
                return true;
            }

            mtrace(get_string('taskoutputwebhooknotdeleted', 'plagiarism_turnitincheck', $webhookid));
            return false;

        } catch (Exception $e) {
            $this->tcrequest->handle_exception($e, 'taskoutputwebhookdeletefailure');
            return false;
        }
    }

    /**
     * Create webhook in Turnitin.
     */
    public function create_webhook() {
        global $CFG;

        // Build web request.
        $secret = $this->generate_secret();
        $request = array();
        $request['signing_secret'] = $secret;
        $request['description'] = get_string('webhook_description', 'plagiarism_turnitincheck', TURNITINCHECK_CALLBACK_URL);
        $request['url'] = TURNITINCHECK_CALLBACK_URL;
        $request['event_types'] = array('SUBMISSION_COMPLETE', 'SIMILARITY_COMPLETE', 'SIMILARITY_UPDATED');
        $request['allow_insecure'] = preg_match("@^https?://@", $CFG->wwwroot) ? true : false;

        // Make request to add webhook.
        try {
            $response = $this->tcrequest->send_request(ENDPOINT_WEBHOOKS, json_encode($request), 'POST');
            $responsedata = json_decode($response);

            // Print message if creating in cron.
            if ($responsedata->httpstatus == HTTP_CREATED) {
                set_config('turnitin_webhook_id', $responsedata->id, 'plagiarism');
                set_config('turnitin_webhook_secret', $secret, 'plagiarism');

                mtrace(get_string('taskoutputwebhookcreated', 'plagiarism_turnitincheck', TURNITINCHECK_CALLBACK_URL));
            } else {
                mtrace(get_string('taskoutputwebhooknotcreated', 'plagiarism_turnitincheck', TURNITINCHECK_CALLBACK_URL));
            }

        } catch (Exception $e) {
            $this->tcrequest->handle_exception($e, 'taskoutputwebhookcreationfailure');
        }
    }

    /**
     * Handle callbacks from Turnitin.
     */
    public function expected_callback_signature($requeststr) {
        $secret = get_config('plagiarism', 'turnitin_webhook_secret');
        $sig = hash_hmac('sha256', $requeststr, base64_decode($secret));

        return $sig;
    }

    /**
     * Generate random webhook secret.
     *
     * @return string
     */
    public function generate_secret() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $secret = '';
        for ($i = 0; $i < 20; $i++) {
            $secret .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $secret;
    }
}