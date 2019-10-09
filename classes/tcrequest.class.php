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
 * Communicate with Turnitin.
 *
 * @package    plagiarism_turnitincheck
 * @author     John McGettrick http://www.turnitin.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitincheck/lib.php');

/**
 * Get report Scores from Turnitin.
 */
class tcrequest {

    public $headers;
    public $apiurl;
    public $apikey;
    public $endpoint;
    public $logger;

    public function __construct() {
        $pluginconfig = get_config('plagiarism');

        $this->set_apiurl(rtrim($pluginconfig->turnitinapiurl, '/'));
        $this->set_apikey($pluginconfig->turnitinapikey);
        $this->logger = ($pluginconfig->turnitinenablelogging) ? new tclogger() : false;

        $this->set_headers();
    }

    /**
     * @param array $headers
     */
    public function set_headers() {
        global $CFG;

        $this->headers = array(
            'sms-namespace: redwood',
            'sms-serviceName: tca',
            'sms-tenantId: *',
            'sms-serviceVersion: latest',
            'Authorization: Bearer ' . $this->apikey,
            'X-Turnitin-Integration-Name: Moodle',
            'X-Turnitin-Integration-Version: tii-v' . get_config('plagiarism_turnitincheck', 'version'). '.' . $CFG->version
        );
    }

    /**
     * Merge additional headers with current headers.
     *
     * @param array $additional_headers
     */
    public function add_additional_headers($additionalheaders = array()) {
        $this->headers = array_merge($this->headers, $additionalheaders);
    }

    /**
     * Send request to Turnitin.
     *
     * @param $endpoint
     * @param $requestbody
     * @param $method
     * @param $requesttype general/submission
     * @return mixed
     */
    public function send_request($endpoint, $requestbody, $method, $requesttype = 'general') {
        global $CFG;

        // Attach content type to headers if this is not a submission.
        if ($requesttype == 'general') {
            $this->headers = array_merge($this->headers, array('Content-Type: application/json'));
        }

        if ($this->logger) {
            $this->logger->info('[' . $method . '] Request to: ' . $this->get_apiurl() . $endpoint);
            $this->logger->info('Headers: ', $this->headers);

            // Don't log the contents of a file submission as it is the raw file contents.
            if ($requesttype != 'submission') {
                $this->logger->info('Request: ', array($requestbody));
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->get_apiurl() . $endpoint);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        }
        if ($method == 'PUT' || $method == 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }
        if ($method != 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $requestbody);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);

        // Use Moodle's SSL certificate.
        if (is_readable("$CFG->dataroot/moodleorgca.crt")) {
            $sslcertificate = realpath("$CFG->dataroot/moodleorgca.crt");
            curl_setopt($ch, CURLOPT_CAINFO, $sslcertificate);
        }

        // Use Moodle's Proxy details if required.
        if (isset($CFG->proxyhost) AND !empty($CFG->proxyhost)) {
            curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost . ':' . $CFG->proxyport);
        }
        if (isset($CFG->proxyuser) AND !empty($CFG->proxyuser)) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, sprintf('%s:%s', $CFG->proxyuser, $CFG->proxypassword));
        }

        $result = curl_exec($ch);
        $result = (empty($result)) ? new stdClass() : json_decode($result);

        // Add httpstatus to $result.
        $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // The response could be an array or an object.
        if (is_array($result)) {
            $result["httpstatus"] = $httpstatus;
        } else {
            $result->httpstatus = $httpstatus || '';
        }

        $result = json_encode($result);

        curl_close($ch);

        if ($this->logger) {
            $this->logger->info('Response: ', array($result));
        }

        return $result;
    }

    /**
     * Test a connection to Turnitin and give an Ajax response.
     *
     * @param String $apiurl
     * @param String $apikey
     *
     * @return string
     * @throws moodle_exception when invalid session key.
     */
    public function test_connection($apiurl, $apikey) {
        $this->set_apiurl($apiurl);
        $this->set_apikey($apikey);
        $this->set_headers();

        $response = $this->send_request(ENDPOINT_WEBHOOKS, json_encode(array()), 'GET');
        $responsedata = json_decode($response);

        if (isset($responsedata->httpstatus) && $responsedata->httpstatus == HTTP_OK) {
            $data["connection_status"] = HTTP_OK;
        } else {
            $data["connection_status"] = HTTP_BAD_REQUEST;
        }

        return json_encode($data);
    }

    /**
     * Handle API exceptions
     *
     * @param $e
     * @param string $displaystr
     */
    public function handle_exception($e, $displaystr = '') {

        $errorstr = get_string($displaystr, 'plagiarism_turnitincheck').PHP_EOL;

        if (is_callable(array($e, 'getFaultCode'))) {
            $errorstr .= get_string('faultcode', 'plagiarism_turnitincheck').": ".$e->getFaultCode().PHP_EOL;
        }

        if (is_callable(array($e, 'getFile'))) {
            $errorstr .= get_string('file').": ".$e->getFile().PHP_EOL;
        }

        if (is_callable(array($e, 'getLine'))) {
            $errorstr .= get_string('line', 'plagiarism_turnitincheck').": ".$e->getLine().PHP_EOL;
        }

        if (is_callable(array($e, 'getMessage'))) {
            $errorstr .= get_string('message', 'plagiarism_turnitincheck').": ".$e->getMessage().PHP_EOL;
        }

        if (is_callable(array($e, 'getCode'))) {
            $errorstr .= get_string('code', 'plagiarism_turnitincheck').": ".$e->getCode().PHP_EOL;
        }

        if ($this->logger) {
            $this->logger->error($errorstr, (array) $e);
        }

        mtrace($errorstr);
    }

    /**
     * Outputs language codes to use with the Turnitin API
     * Cloud Viewer launch takes en, de or nl.
     * EULA takes locale; en-US, de-DE, nl-NL
     *
     * @return string The cleaned and mapped associated Turnitin lang code
     */
    public function get_language() {
        // Get current language code.
        $langcode = current_language();

        // Replace with language code for CV launch.
        $langarray = array(
            'de' => 'de',
            'de_du' => 'de',
            'nl' => 'nl'
        );

        // Replace with locale for EULA link.
        $localearray = array(
            'de' => 'de-DE',
            'de_du' => 'de-DE',
            'nl' => 'nl-NL'
        );

        $lang = new stdClass();
        $lang->langcode = (isset($langarray[$langcode])) ? $langarray[$langcode] : 'en';
        $lang->localecode = (isset($localearray[$langcode])) ? $localearray[$langcode] : 'en-US';

        return $lang;
    }

    /**
     * @return mixed
     */
    public function get_apiurl() {
        return $this->apiurl;
    }

    /**
     * @param mixed $apiurl
     */
    public function set_apiurl($apiurl) {
        $this->apiurl = $apiurl;
    }

    /**
     * @return mixed
     */

    public function get_apikey() {
        return $this->apikey;
    }

    /**
     * @param mixed $apikey
     */
    public function set_apikey($apikey) {
        $this->apikey = $apikey;
    }
}
