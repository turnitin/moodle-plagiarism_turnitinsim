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
 * Tests for workshop module class for plagiarism_turnitincheck component
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitincheck/classes/modules/tcworkshop.class.php');
require_once($CFG->dirroot . '/mod/workshop/locallib.php');
require_once($CFG->dirroot . '/mod/workshop/tests/fixtures/testable.php');

class tcworkshop_test extends advanced_testcase {

    const TEST_WORKSHOP_TEXT = 'Generated content';

    /**
     * Set config for use in the tests.
     */
    public function setup() {
        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism');
        set_config('turnitinapikey', 1234, 'plagiarism');
        set_config('turnitinenablelogging', 0, 'plagiarism');

        $this->student1 = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();
    }

    public function test_get_onlinetext_returns_correct_text() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($this->student1->id,
            $course->id,
            $studentrole->id);

        $workshop = $this->getDataGenerator()->create_module('workshop', array('course' => $course));
        $cm = get_coursemodule_from_instance('workshop', $workshop->id, $course->id, false, MUST_EXIST);
        $this->workshop = new testable_workshop($workshop, $cm, $course);

        $workshopgenerator = $this->getDataGenerator()->get_plugin_generator('mod_workshop');

        $submissionid = $workshopgenerator->create_submission($this->workshop->id, $this->student1->id);

        $tcworkshop = new tcworkshop();
        $result = $tcworkshop->get_onlinetext($submissionid);

        $this->assertEquals($result, self::TEST_WORKSHOP_TEXT);
    }

    /**
     * Test that we get back the correct itemid when get_itemid is called.
     */
    public function test_get_itemid_returns_correct_itemid() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($this->student1->id,
            $course->id,
            $studentrole->id);

        $workshop = $this->getDataGenerator()->create_module('workshop', array('course' => $course));
        $cm = get_coursemodule_from_instance('workshop', $workshop->id, $course->id, false, MUST_EXIST);
        $this->workshop = new testable_workshop($workshop, $cm, $course);

        $workshopgenerator = $this->getDataGenerator()->get_plugin_generator('mod_workshop');

        $submissionid = $workshopgenerator->create_submission($this->workshop->id, $this->student1->id);

        // Get item id.
        $tcworkshop = new tcworkshop();
        $params = new stdClass();
        $params->moduleid = $workshop->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_WORKSHOP_TEXT;
        $result = $tcworkshop->get_itemid($params);

        $this->assertEquals($result, $submissionid);
    }

    /**
     * Test that we get back the correct itemid when get_itemid is called.
     */
    public function test_get_itemid_returns_zero_if_no_submission() {
        $this->resetAfterTest();

        $this->setAdminUser();

        // Create course and workshop.
        $course = $this->getDataGenerator()->create_course();
        $workshop = $this->getDataGenerator()->create_module('workshop', array('course' => $course));

        // Get item id.
        $tcworkshop = new tcworkshop();
        $params = new stdClass();
        $params->moduleid = $workshop->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_WORKSHOP_TEXT;
        $result = $tcworkshop->get_itemid($params);

        $this->assertEquals($result, 0);
    }

    /*
     * Test that getting the author returns the related user id.
     */
    public function test_get_author_returns_related_user_id() {
        $this->resetAfterTest(true);

        // Test that get author returns student2 as the author.
        $tcworkshop = new tcworkshop();
        $response = $tcworkshop->get_author($this->student1->id, $this->student2->id, 0, 0);
        $this->assertEquals($this->student2->id, $response);

        // Test that get author returns student1 as the author because relateduserid is empty.
        $tcworkshop = new tcworkshop();
        $response = $tcworkshop->get_author($this->student1->id, 0, 0, 0);
        $this->assertEquals($this->student1->id, $response);
    }

    /**
     * Test that is submission draft returns expected value.
     */
    public function test_is_submission_draft() {
        $this->resetAfterTest();

        $tcworkshop = new tcworkshop();
        $response = $tcworkshop->is_submission_draft(0);
        $this->assertEquals(false, $response);
    }

    /**
     * Test that get due date returns expected value.
     */
    public function test_get_due_date() {
        $this->resetAfterTest();

        $tcworkshop = new tcworkshop();
        $response = $tcworkshop->get_due_date(0);
        $this->assertEquals(0, $response);
    }

    /**
     * Test that show other posts links returns expected value.
     */
    public function test_show_other_posts_links() {
        $this->resetAfterTest();

        $tcworkshop = new tcworkshop();
        $response = $tcworkshop->show_other_posts_links(0, 0);
        $this->assertEquals(true, $response);
    }
}