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
 * @copyright 2020 Turnitin
 * @author    Grijesh Saini <gsaini@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');


/**
 * Class for sending error logs to Turnitin.
 */
class plagiarism_turnitinsim_logging_request {

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
     * @param plagiarism_turnitinsim_logging_request_info|null $loggingrequestinfo The logging_request_info object.
     * @param plagiarism_turnitinsim_logging_request_event_info|null $loggingrequesteventinfo The logging_request_event_info object.
     * @param bool $sendsecrets The boolean value, if true send secrets as part of logs.
     */
    public function send_error_to_turnitin(plagiarism_turnitinsim_logging_request_info $loggingrequestinfo = null,
                                           plagiarism_turnitinsim_logging_request_event_info $loggingrequesteventinfo = null,
                                           $sendsecrets = false) {

        if (!get_config('plagiarism_turnitinsim', 'turnitinenableremotelogging')) {
            return;
        }

        $this->set_basic_details();

        if ($sendsecrets) {
            $this->set_secrets();
        }

        if ($loggingrequestinfo) {
            $this->set_request_info($loggingrequestinfo);
        }

        if ($loggingrequesteventinfo) {
            $this->set_event_info($loggingrequesteventinfo);
        }

        try {
            $this->tsrequest->send_request(TURNITINSIM_ENDPOINT_LOGGING, json_encode($this->loggingrequest),
                'POST', 'logging', true);
        } catch (Exception $e) {
            $logger = new plagiarism_turnitinsim_logger();
            $logger->error('Error while sending logs');
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
     * @param plagiarism_turnitinsim_logging_request_info $loggingrequestinfo The logging_request_info object.
     */
    private function set_request_info(plagiarism_turnitinsim_logging_request_info $loggingrequestinfo) {
        $this->loggingrequest["request"]["url"] = $loggingrequestinfo->get_url();
        $this->loggingrequest["request"]["method"] = $loggingrequestinfo->get_method();
        $this->loggingrequest["request"]["headers"] = $loggingrequestinfo->get_headers();
        $this->loggingrequest["request"]["response_status"] = $loggingrequestinfo->get_responsestatus();
        $this->loggingrequest["request"]["response_body"] = $loggingrequestinfo->get_responsebody();
    }

    /**
     * Set event info details.
     *
     * @param plagiarism_turnitinsim_logging_request_event_info $loggingrequesteventinfo The $logging_request_event_info object.
     */
    private function set_event_info(plagiarism_turnitinsim_logging_request_event_info $loggingrequesteventinfo) {
        $this->loggingrequest["event"]["url"] = $loggingrequesteventinfo->get_url();
        $this->loggingrequest["event"]["headers"] = $loggingrequesteventinfo->get_headers();
        $this->loggingrequest["event"]["body"] = $loggingrequesteventinfo->get_body();
    }

    /**
     * Set submission id.
     *
     * @param string $submissionid
     */
    public function set_submissionid($submissionid) {
        $this->submissionid = $submissionid;
    }

}
