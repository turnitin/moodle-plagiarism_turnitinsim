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
 * @package   plagiarism_turnitinsim
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_turnitinsim\message\get_webhook_failure;

defined('MOODLE_INTERNAL') || die();

class tscallback {

    public $tsrequest;

    public function __construct(tsrequest $tsrequest = null ) {
        $this->tsrequest = $tsrequest;
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
            $endpoint = TURNITINSIM_ENDPOINT_GET_WEBHOOK;
            $endpoint = str_replace('{{webhook_id}}', $webhookid, $endpoint);
            $response = $this->tsrequest->send_request($endpoint, json_encode(array()), 'GET');
            $responsedata = json_decode($response);

            // Return false if the webhook could not be retrieved.
            if ($responsedata->httpstatus != TURNITINSIM_HTTP_OK) {
                mtrace(get_string('taskoutputwebhooknotretrieved', 'plagiarism_turnitinsim', $webhookid));
                return false;
            }

            // If the webhook URL does not match this Moodle site then return false.
            if ($responsedata->url != TURNITINSIM_CALLBACK_URL) {
                return false;
            }

            mtrace(get_string('taskoutputwebhookretrieved', 'plagiarism_turnitinsim', $webhookid));
            return true;

        } catch (Exception $e) {
            $this->tsrequest->handle_exception($e, 'taskoutputwebhookretrievalfailure');

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
            $endpoint = TURNITINSIM_ENDPOINT_GET_WEBHOOK;
            $endpoint = str_replace('{{webhook_id}}', $webhookid, $endpoint);
            $response = $this->tsrequest->send_request($endpoint, json_encode(array()), 'DELETE');
            $responsedata = json_decode($response);

            if ($responsedata->httpstatus == TURNITINSIM_HTTP_NO_CONTENT) {
                mtrace(get_string('taskoutputwebhookdeleted', 'plagiarism_turnitinsim', $webhookid));
                return true;
            }

            mtrace(get_string('taskoutputwebhooknotdeleted', 'plagiarism_turnitinsim', $webhookid));
            return false;

        } catch (Exception $e) {
            $this->tsrequest->handle_exception($e, 'taskoutputwebhookdeletefailure');
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
        $request['description'] = get_string('webhook_description', 'plagiarism_turnitinsim', TURNITINSIM_CALLBACK_URL);
        $request['url'] = TURNITINSIM_CALLBACK_URL;
        $request['event_types'] = array(TURNITINSIM_SUBMISSION_COMPLETE, TURNITINSIM_SIMILARITY_COMPLETE, TURNITINSIM_SIMILARITY_UPDATED);
        $request['allow_insecure'] = preg_match("@^https?://@", $CFG->wwwroot) ? true : false;

        // Make request to add webhook.
        try {
            $response = $this->tsrequest->send_request(TURNITINSIM_ENDPOINT_WEBHOOKS, json_encode($request), 'POST');
            $responsedata = json_decode($response);

            // Print message if creating in cron.
            if ($responsedata->httpstatus == TURNITINSIM_HTTP_CREATED) {
                set_config('turnitin_webhook_id', $responsedata->id, 'plagiarism');
                set_config('turnitin_webhook_secret', $secret, 'plagiarism');

                mtrace(get_string('taskoutputwebhookcreated', 'plagiarism_turnitinsim', TURNITINSIM_CALLBACK_URL));
            } else {
                mtrace(get_string('taskoutputwebhooknotcreated', 'plagiarism_turnitinsim', TURNITINSIM_CALLBACK_URL));
            }

        } catch (Exception $e) {
            $this->tsrequest->handle_exception($e, 'taskoutputwebhookcreationfailure');
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
        $characters = random_bytes(20);
        return bin2hex($characters);
    }
}