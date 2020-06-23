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
 * Class models request info sent to be logged.
 */
class plagiarism_turnitinsim_logging_request_info {

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
    private $responsestatus;

    /**
     * @var string The response body.
     */
    private $responsebody;

    /**
     * logging_request_info constructor.
     *
     * @param string $url The url.
     * @param string $method The Http Method.
     * @param array $headers Headers.
     * @param int $responsestatus The response http status.
     * @param string $responsebody The response body.
     */
    public function __construct($url, $method = null, $headers = null, $responsestatus = null, $responsebody = null) {
        $this->url = $url;
        $this->method = $method;
        $this->headers = $headers;
        $this->responsestatus = $responsestatus;
        $this->responsebody = $responsebody;
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
    public function get_responsestatus() {
        return $this->responsestatus;
    }

    /**
     * Get response body.
     *
     * @return string
     */
    public function get_responsebody() {
        return $this->responsebody;
    }

}