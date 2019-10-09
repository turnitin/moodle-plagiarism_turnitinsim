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
 * Class for handling Turnitin's EULA.
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class tceula {

    public $tcrequest;

    public function __construct( tcrequest $tcrequest = null ) {
        $this->tcrequest = ($tcrequest) ? $tcrequest : new tcrequest();
    }

    /**
     * Attempt to retrieve the latest version of the EULA.
     *
     * @return mixed|stdClass
     */
    public function get_latest_version() {
        $responsedata = new stdClass();

        // Make request to get the latest EULA version.
        try {
            $endpoint = ENDPOINT_GET_LATEST_EULA;
            $response = $this->tcrequest->send_request($endpoint, json_encode(array()), 'GET');
            $responsedata = json_decode($response);

            // Latest version retrieved.
            if ($responsedata->httpstatus == HTTP_OK) {
                mtrace(get_string('taskoutputlatesteularetrieved', 'plagiarism_turnitincheck', $responsedata->version));
                return $responsedata;
            }

            mtrace(get_string('taskoutputlatesteulanotretrieved', 'plagiarism_turnitincheck'));
            return $responsedata;

        } catch (Exception $e) {
            $this->tcrequest->handle_exception($e, 'taskoutputlatesteularetrievalfailure');
            return $responsedata;
        }
    }
}