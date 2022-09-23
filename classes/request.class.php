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
 * @package    plagiarism_turnitinsim
 * @copyright  2018 Turnitin
 * @author     John McGettrick http://www.turnitin.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');

/**
 * Communicate with Turnitin.
 */
class plagiarism_turnitinsim_request {

    /**
     * @var
     */
    public $headers;

    /**
     * @var string The API URL for the account.
     */
    public $apiurl;

    /**
     * @var string The API key for the account.
     */
    public $apikey;

    /**
     * @var string The routing URL for the account.
     */
    public $routingurl;

    /**
     * @var string The endpoint being requested.
     */
    public $endpoint;

    /**
     * @var bool|plagiarism_turnitinsim_logger Instance of the logger.
     */
    public $logger;

    /**
     * plagiarism_turnitinsim_request constructor.
     * @throws dml_exception
     */
    public function __construct() {
        // Only set attributes if plugin is configured.
        $plugin = new plagiarism_plugin_turnitinsim();
        if ($plugin->is_plugin_configured()) {
            $pluginconfig = get_config('plagiarism_turnitinsim');

            $this->set_apiurl(rtrim($pluginconfig->turnitinapiurl, '/'));
            $this->set_apikey($pluginconfig->turnitinapikey);
            $this->set_routingurl($pluginconfig->turnitinroutingurl ?? null);
            $this->logger = ($pluginconfig->turnitinenablelogging) ? new plagiarism_turnitinsim_logger() : false;

            $this->set_headers();
        }
    }

    /**
     * Set the headers for the request.
     *
     * @throws dml_exception
     */
    public function set_headers() {
        global $CFG;

        $this->headers = array(
            'Authorization: Bearer ' . $this->apikey,
            'X-Turnitin-Integration-Name: Moodle',
            'X-Turnitin-Integration-Version: tii-v' . get_config('plagiarism_turnitinsim', 'version'). '.' . $CFG->version
        );
    }

    /**
     * Merge additional headers with current headers.
     *
     * @param array $additionalheaders Additional headers to add.
     */
    public function add_additional_headers($additionalheaders = array()) {
        $this->headers = array_merge($this->headers, $additionalheaders);
    }

    /**
     * Send request to Turnitin.
     *
     * @param string $endpoint The endpoint to make a request to.
     * @param string $requestbody The request body to send.
     * @param string $method The request method eg GET/POST.
     * @param string $requesttype The type of request, can be general or submission.
     * @param bool $isasync The flag to define type of http request.
     * @return array|bool|false|mixed|stdClass|string
     */
    public function send_request($endpoint, $requestbody, $method, $requesttype = 'general', $isasync = false) {
        global $CFG;

        // Attach content type to headers if this is not a submission.
        if ($requesttype == 'general' || $requesttype === 'logging') {
            if (!in_array('Content-Type: application/json', $this->headers)) {
                $this->headers[] = 'Content-Type: application/json';
            }
        }

        $tiiurl = $this->get_tii_url();

        if ($this->logger) {
            $this->logger->info('[' . $method . '] Request to: ' . $tiiurl . $endpoint);
            $this->logger->info('Headers: ', $this->headers);

            // Don't log the contents of a file submission as it is the raw file contents.
            if ($requesttype != 'submission') {
                $this->logger->info('Request: ', array($requestbody));
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tiiurl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($isasync) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        } else {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        }

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

        // Set the default for whether a response was found.
        $responsefound = true;

        $result = curl_exec($ch);
        if ($result === false) {
            $responsefound = false;
            if ($this->logger) {
                $this->logger->error('Curl error: ' . curl_error($ch));
            }
        }

        // Add httpstatus to $result.
        $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (empty($result)) {
            $result = new stdClass();
        } else {
            $originaljson = $result;
            $result = json_decode($result);

            // If Json is not valid set httpstatus 400.
            if (json_last_error() !== JSON_ERROR_NONE) {
                if ($this->logger) {
                    $this->logger->error('The JSON returned was not valid. Returned JSON: '. $originaljson);
                }

                $result = new stdClass();
                $httpstatus = 400;
            }
        }

        // The response could be an array or an object.
        if (is_array($result)) {
            $result["httpstatus"] = $httpstatus;
        } else {
            $result->httpstatus = $httpstatus || '' ? $httpstatus : '';
        }

        $result = json_encode($result);

        curl_close($ch);

        if ($this->logger) {
            if ($responsefound) {
                $this->logger->info('Response: ', array($result));
            } else {
                $this->logger->info('Response: There was no response from this request.');
            }
        }

        return $result;
    }

    /**
     * Get the Turnitin URL for use in a request.
     *
     * @return string The URL to call Turnitin with.
     */
    public function get_tii_url() {
        return $this->get_routingurl() ?: $this->get_apiurl();
    }

    /**
     * Test a connection to Turnitin and give an Ajax response.
     *
     * @param string $apiurl The service API URL.
     * @param string $apikey The service API key.
     *
     * @return string
     * @throws moodle_exception when invalid session key.
     */
    public function test_connection($apiurl, $apikey) {

        // Strip any trailing / chars from api url.
        $apiurl = rtrim($apiurl, '/');

        $validurlregex = '/.+\.(turnitin\.com|turnitinuk\.com|turnitin\.dev|turnitin\.org|tii-sandbox\.com)(\/api)?$/m';

        if (empty($apikey) || empty($apiurl)) {
            $data["connection_status"] = TURNITINSIM_HTTP_BAD_REQUEST;
            return json_encode($data);
        }

        if (!preg_match($validurlregex, $apiurl)) {
            $data["connection_status"] = TURNITINSIM_HTTP_BAD_REQUEST;

            if ($this->logger) {
                $this->logger->info('Invalid Turnitin URL: ', array($apiurl));
            }
            return json_encode($data);
        }

        $apiurl = str_replace("/api", '', $apiurl);

        $this->set_apiurl($apiurl);
        $this->set_apikey($apikey);
        $this->set_routingurl(null);
        $this->set_headers();

        $response = $this->send_request(TURNITINSIM_ENDPOINT_WEBHOOKS, json_encode(array()), 'GET');
        $responsedata = json_decode($response);

        if (isset($responsedata->httpstatus) && $responsedata->httpstatus === TURNITINSIM_HTTP_OK) {
            $data["connection_status"] = TURNITINSIM_HTTP_OK;
        } else {
            $data["connection_status"] = TURNITINSIM_HTTP_BAD_REQUEST;
        }

        return json_encode($data);
    }

    /**
     * Calls the where-am-i endpoint to get the service center and uses this to create an external routing URL.
     * This only needs to be done once, as the service center URL should not change.
     *
     * @param string $force Forces a check for a routing URL. Necessary when updating the API URL.
     * @return string Mapping to the external URL.
     */
    public function get_routing_url($force = false) {
        $turnitinroutingurl = get_config('plagiarism_turnitinsim', 'turnitinroutingurl');

        if (empty($turnitinroutingurl) || $force) {
            // Ensure there is no cached URL - useful if we're updating the URL in the admin settings.
            $this->set_routingurl(null);

            $response = $this->send_request(TURNITINSIM_ENDPOINT_WHERE_AM_I, json_encode(array()), 'GET');
            $responsedata = json_decode($response);

            if (!isset($responsedata->{'service-center'})) {
                return null;
            }

            // Map to external URL.
            $externalurlconstant = strtoupper("TURNITINSIM_EXTERNAL_" . $responsedata->{'service-center'});

            return (defined($externalurlconstant)) ? "https://" . constant($externalurlconstant) : null;
        }

        return $turnitinroutingurl;
    }

    /**
     * Handle API exceptions
     *
     * @param object $e The exception.
     * @param string $displaystr The string to display for the error.
     * @param string|object|array $a An object, string or number that can be used
     *      within translation strings
     * @throws coding_exception
     */
    public function handle_exception($e, $displaystr = '', $a = null) {

        $errorstr = get_string($displaystr, 'plagiarism_turnitinsim', $a).PHP_EOL;

        if (is_callable(array($e, 'getFaultCode'))) {
            $errorstr .= get_string('faultcode', 'plagiarism_turnitinsim').": ".$e->getFaultCode().PHP_EOL;
        }

        if (is_callable(array($e, 'getFile'))) {
            $errorstr .= get_string('file').": ".$e->getFile().PHP_EOL;
        }

        if (is_callable(array($e, 'getLine'))) {
            $errorstr .= get_string('line', 'plagiarism_turnitinsim').": ".$e->getLine().PHP_EOL;
        }

        if (is_callable(array($e, 'getMessage'))) {
            $errorstr .= get_string('message', 'plagiarism_turnitinsim').": ".$e->getMessage().PHP_EOL;
        }

        if (is_callable(array($e, 'getCode'))) {
            $errorstr .= get_string('code', 'plagiarism_turnitinsim').": ".$e->getCode().PHP_EOL;
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
     * Get the API URL.
     *
     * @return mixed
     */
    public function get_apiurl() {
        return $this->apiurl;
    }

    /**
     * Set the API URL.
     *
     * @param mixed $apiurl
     */
    public function set_apiurl($apiurl) {
        $this->apiurl = $apiurl;
    }

    /**
     * Get the API key.
     *
     * @return mixed
     */
    public function get_apikey() {
        return $this->apikey;
    }

    /**
     * Set the API key.
     *
     * @param mixed $apikey
     */
    public function set_apikey($apikey) {
        $this->apikey = $apikey;
    }

    /**
     * Get the routing URL.
     *
     * @return mixed
     */
    public function get_routingurl() {
        return $this->routingurl;
    }

    /**
     * Set the routing URL.
     *
     * @param mixed $apiurl
     */
    public function set_routingurl($routingurl) {
        $this->routingurl = $routingurl;
    }
}
