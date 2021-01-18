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
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_turnitinsim\message\get_webhook_failure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/logging_request.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/logging_request_info.class.php');

/**
 * Class for handling callbacks from Turnitin.
 */
class plagiarism_turnitinsim_callback {

    /**
     * @var plagiarism_turnitinsim_request|null Request object.
     */
    public $tsrequest;

    /**
     * plagiarism_turnitinsim_callback constructor.
     *
     * @param plagiarism_turnitinsim_request|null $tsrequest The request we're handling.
     */
    public function __construct(plagiarism_turnitinsim_request $tsrequest = null ) {
        $this->tsrequest = $tsrequest;
    }

    /**
     * Attempt to retrieve the webhook.
     *
     * @param string $webhookid The webhookid to check.
     * @return bool true if has webhook.
     * @throws coding_exception
     */
    public function has_webhook($webhookid) {
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
     * @param string $webhookid The webhookid to be deleted.
     * @return bool true if webhook has been deleted.
     * @throws coding_exception
     */
    public function delete_webhook($webhookid) {
        // Make request to get webhook.
        try {
            $endpoint = TURNITINSIM_ENDPOINT_GET_WEBHOOK;
            $endpoint = str_replace('{{webhook_id}}', $webhookid, $endpoint);
            $response = $this->tsrequest->send_request($endpoint, json_encode(array()), 'DELETE');
            $responsedata = json_decode($response);

            if ($responsedata->httpstatus === TURNITINSIM_HTTP_NO_CONTENT) {
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
        $request['event_types'] = array(
            TURNITINSIM_SUBMISSION_COMPLETE,
            TURNITINSIM_SIMILARITY_COMPLETE,
            TURNITINSIM_SIMILARITY_UPDATED
        );
        $request['allow_insecure'] = preg_match("@^https?://@", $CFG->wwwroot) ? true : false;

        // Make request to add webhook.
        try {
            $response = $this->tsrequest->send_request(TURNITINSIM_ENDPOINT_WEBHOOKS, json_encode($request), 'POST');
            $responsedata = json_decode($response);

            // Print message if creating in cron.
            if ($responsedata->httpstatus == TURNITINSIM_HTTP_CREATED) {
                if (isset($responsedata->id)) {
                    set_config('turnitin_webhook_id', $responsedata->id, 'plagiarism_turnitinsim');
                }
                set_config('turnitin_webhook_secret', $secret, 'plagiarism_turnitinsim');

                mtrace(get_string('taskoutputwebhookcreated', 'plagiarism_turnitinsim', TURNITINSIM_CALLBACK_URL));
            } else if ($responsedata->httpstatus === TURNITINSIM_HTTP_CANNOT_EXTRACT_TEXT) {
                // Webhook for this URL already exists in Turnitin, but not Moodle. Get webhooks.
                $response = $this->tsrequest->send_request(TURNITINSIM_ENDPOINT_WEBHOOKS, json_encode(array()), 'GET');
                $webhooks = json_decode($response);

                // We want the webhook ID for this callback URL. Save it.
                foreach ($webhooks as $webhook) {
                    if (is_object($webhook) && $webhook->url === TURNITINSIM_CALLBACK_URL) {
                        set_config('turnitin_webhook_id', $webhook->id, 'plagiarism_turnitinsim');
                        set_config('turnitin_webhook_secret', $secret, 'plagiarism_turnitinsim');
                        break;
                    }
                }
            } else {
                mtrace(get_string('taskoutputwebhooknotcreated', 'plagiarism_turnitinsim', TURNITINSIM_CALLBACK_URL));
                $loggingrequestinfo = new plagiarism_turnitinsim_logging_request_info(TURNITINSIM_ENDPOINT_WEBHOOKS, "POST",
                    null, $responsedata->httpstatus, $response);
                $loggingrequest = new plagiarism_turnitinsim_logging_request('Webhook could not be created', $this->tsrequest);
                $loggingrequest->send_error_to_turnitin($loggingrequestinfo);
            }

        } catch (Exception $e) {
            $this->tsrequest->handle_exception($e, 'taskoutputwebhookcreationfailure');
        }
    }

    /**
     * Generate a 64 character length hash of the request string.
     *
     * @param string $requeststring The request in string format.
     * @return string A 64 character length hash.
     * @throws dml_exception
     */
    public function expected_callback_signature($requeststring) {
        $secret = get_config('plagiarism_turnitinsim', 'turnitin_webhook_secret');
        $sig = hash_hmac('sha256', $requeststring, base64_decode($secret));

        return $sig;
    }

    /**
     * Generate random webhook secret.
     *
     * @return string Random webhook secret.
     * @throws Exception
     */
    public function generate_secret() {
        $randomstring = random_bytes(20);
        return bin2hex($randomstring);
    }
}