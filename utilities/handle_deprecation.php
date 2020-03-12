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
 * Class for handling deprecations for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2020 Turnitin
 * @author    David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Handle class deprecations so that we can support multiple Moodle versions.
 */
class handle_deprecation {
    /**
     * @var int The Moodle version.
     */
    public $branch;

    /**
     * handle_deprecation constructor.
     */
    public function __construct() {
        global $CFG;
        $this->branch = $CFG->branch;
    }

    /**
     * In Moodle 3.8, generate_uuid() was deprecated and \core\uuid::generate() was introduced.
     * This method handles our support for multiple Moodle versions.
     *
     * @return string representing a UUID.
     */
    public function create_uuid() {
        return ($this->branch < 38) ? generate_uuid() : \core\uuid::generate();
    }
}