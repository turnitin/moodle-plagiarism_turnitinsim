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
 * Class models request info sent to be logged.
 */
class logging_request_info {

    /**
     * @var string The url.
     */
    private $url;

    /**
     * @var string The Http Method.
     */
    private $method;

    /**
     * @var array Request headers.
     */
    private $headers;

    /**
     * @var int Response http status.
     */
    private $response_status;

    /**
     * @var string The response body.
     */
    private $response_body;

    /**
     * logging_request_info constructor.
     * @param $url
     * @param $method
     * @param $headers
     * @param $response_status
     * @param $response_body
     */
    public function __construct($url, $method = null, $headers = null, $response_status = null, $response_body = null) {
        $this->url = $url;
        $this->method = $method;
        $this->headers = $headers;
        $this->response_status = $response_status;
        $this->response_body = $response_body;
    }


    /**
     * Get the url.
     *
     * @return string
     */
    public function get_url() {
        return $this->url;
    }

    /**
     * Get the Http Method.
     *
     * @return string
     */
    public function get_method() {
        return $this->method;
    }

    /**
     * Get the request headers.
     *
     * @return array
     */
    public function get_headers() {
        return $this->headers;
    }

    /**
     * Get response status.
     *
     * @return int
     */
    public function get_response_status() {
        return $this->response_status;
    }

    /**
     * Get response body.
     *
     * @return string
     */
    public function get_response_body() {
        return $this->response_body;
    }

}