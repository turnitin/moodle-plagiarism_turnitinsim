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
 * Submission class for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');


/**
 * Class for sending error logs to Turnitin.
 */
class logging_request {

    /**
     * @var object The request object.
     */
    public $tsrequest;

    /**
     * @var array The log object.
     */
    public $loggingrequest;

    /**
     * @var string The submission id.
     */
    private $submissionid;

    /**
     * Constructor.
     *
     * @param string $message The error message.
     * @param plagiarism_turnitinsim_request|null $tsrequest Request object.
     */
    public function __construct($message = 'Error', plagiarism_turnitinsim_request $tsrequest = null) {
        $this->tsrequest = ($tsrequest) ? $tsrequest : new plagiarism_turnitinsim_request();
        $this->loggingrequest = array();
        $this->loggingrequest["message"] = $message;
    }

    /**
     * Create remote logging request and send to turnitin.
     *
     * @param logging_request_info|null $logging_request_info The logging_request_info object.
     * @param logging_request_event_info|null $logging_request_event_info The logging_request_event_info object.
     * @param bool $send_secrets The boolean value, if true send secrets as part of logs.
     */
    public function send_error_to_turnitin(logging_request_info $logging_request_info = null, logging_request_event_info $logging_request_event_info = null, $send_secrets = false) {

        if (!get_config('plagiarism_turnitinsim', 'turnitinenableremotelogging')) {
            return;
        }

        $this->set_basic_details();

        if ($send_secrets) {
           $this->set_secrets();
        }

        if ($logging_request_info) {
            $this->set_request_info($logging_request_info);
        }

        if ($logging_request_event_info) {
            $this->set_event_info($logging_request_event_info);
        }

        try {
            $this->tsrequest->send_request(TURNITINSIM_ENDPOINT_LOGGING, json_encode($this->loggingrequest), 'POST', 'logging', true);
        } catch (Exception $e) {
           // Handle silently.
        }
    }

    /**
     * Populate basic and mandatory details.
     */
    private function set_basic_details() {
        global $CFG;

        $this->loggingrequest["integration_type"] = "Moodle";
        $this->loggingrequest["integration_version"] = get_config('plagiarism_turnitinsim', 'version');
        $this->loggingrequest["lms_version"] = $CFG->branch;
        $this->loggingrequest["log_level"] = "ERROR";
        $this->loggingrequest["date"] = date("Y-m-d H:i:s");
        $this->loggingrequest["tenant"] = $this->tsrequest->get_apiurl();
        $this->loggingrequest["submission_id"] = $this->submissionid;
    }

    /**
     * Set secrets.
     */
    private function set_secrets() {
        $this->loggingrequest["api_key"] = $this->tsrequest->get_apikey();
        $this->loggingrequest["webhook_secret"] = get_config('plagiarism_turnitinsim', 'turnitin_webhook_secret');
    }

    /**
     * Set request info details.
     *
     * @param logging_request_info $logging_request_info The logging_request_info object.
     */
    private function set_request_info(logging_request_info $logging_request_info) {
        $this->loggingrequest["request"]["url"] = $logging_request_info->get_url();
        $this->loggingrequest["request"]["method"] = $logging_request_info->get_method();
        $this->loggingrequest["request"]["headers"] = $logging_request_info->get_headers();
        $this->loggingrequest["request"]["response_status"] = $logging_request_info->get_response_status();
        $this->loggingrequest["request"]["response_body"] = $logging_request_info->get_response_body();
    }

    /**
     * Set event info details.
     *
     * @param logging_request_event_info $logging_request_event_info The $logging_request_event_info object.
     */
    private function set_event_info(logging_request_event_info $logging_request_event_info) {
        $this->loggingrequest["event"]["url"] = $logging_request_event_info->get_url();
        $this->loggingrequest["event"]["headers"] = $logging_request_event_info->get_headers();
        $this->loggingrequest["event"]["body"] = $logging_request_event_info->get_body();
    }

    /**
     * Set submission id.
     *
     * @param string $submissionid
     */
    public function set_submissionid($submissionid){
        $this->submissionid = $submissionid;
    }

}
