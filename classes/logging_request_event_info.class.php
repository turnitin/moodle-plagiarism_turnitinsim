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
 * Class models event info sent to be logged.
 */
class plagiarism_turnitinsim_logging_request_event_info {

    /**
     * @var string The url.
     */
    private $url;

    /**
     * @var array Request headers.
     */
    private $headers;

    /**
     * @var string The response body.
     */
    private $body;

    /**
     * logging_request_info constructor.
     *
     * @param string $url The url.
     * @param array $headers The request headers.
     * @param string $body The response body.
     */
    public function __construct($url, $headers = null, $body = null) {
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
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
     * Get the request headers.
     *
     * @return array
     */
    public function get_headers() {
        return $this->headers;
    }

    /**
     * Get response body.
     *
     * @return string
     */
    public function get_body() {
        return $this->body;
    }
}
