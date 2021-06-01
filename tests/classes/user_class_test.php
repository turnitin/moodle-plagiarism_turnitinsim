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
 * Unit tests for (some of) plagiarism/turnitinsim/classes/user.class.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/user.class.php');

/**
 * Tests for Turnitin Integrity user class.
 */
class user_class_testcase extends advanced_testcase {

    /**
     * Test that user constructor creates a turnitinid in the correct format.
     */
    public function test_constructor_creates_turnitinid() {
        $this->resetAfterTest();

        // Create new student.
        $student1 = self::getDataGenerator()->create_user();

        // Create new tsuser which should create a Turnitin id.
        $tsuser = new plagiarism_turnitinsim_user($student1->id);

        // Turnitinid should match reg ex.
        $format = "/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i";
        $turnitinid = $tsuser->get_turnitinid();
        handle_deprecation::assertregex($this, $format, $turnitinid);
    }

}