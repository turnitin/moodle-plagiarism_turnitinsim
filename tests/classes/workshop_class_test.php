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
 * Tests for workshop module class for plagiarism_turnitinsim component
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/workshop.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/utilities.php');
require_once($CFG->dirroot . '/mod/workshop/locallib.php');
require_once($CFG->dirroot . '/mod/workshop/tests/fixtures/testable.php');

/**
 * Tests for workshop module class for plagiarism_turnitinsim component
 */
class workshop_class_testcase extends advanced_testcase {

    /**
     * Sample text used for unit testing a workshop.
     */
    const TEST_WORKSHOP_TEXT = 'Generated content';

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        // Set plugin as enabled in config for this module type.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', 1234, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');

        // Set the features enabled.
        $featuresenabled = file_get_contents(__DIR__ . '/../fixtures/get_features_enabled_success.json');
        set_config('turnitin_features_enabled', $featuresenabled, 'plagiarism_turnitinsim');

        $this->student1 = $this->getDataGenerator()->create_user();
        $this->student2 = $this->getDataGenerator()->create_user();
    }

    /**
     * Test that get_onlinetext returns the correct text.
     * @throws coding_exception
     * @throws dml_exception
     */
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

        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $result = $tsworkshop->get_onlinetext($submissionid);

        $this->assertEquals($result, self::TEST_WORKSHOP_TEXT);
    }

    /**
     * Test that online text returns false if no submission is found.
     */
    public function test_get_onlinetext_returns_false_if_no_text() {
        $this->resetAfterTest();

        $tsassign = new plagiarism_turnitinsim_workshop();
        $result = $tsassign->get_onlinetext(1);

        $this->assertNull($result);
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
        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $params = new stdClass();
        $params->moduleid = $workshop->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_WORKSHOP_TEXT;
        $result = $tsworkshop->get_itemid($params);

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
        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $params = new stdClass();
        $params->moduleid = $workshop->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_WORKSHOP_TEXT;
        $result = $tsworkshop->get_itemid($params);

        $this->assertEquals($result, 0);
    }

    /**
     * Test that getting the author returns the related user id.
     */
    public function test_get_author_returns_related_user_id() {
        $this->resetAfterTest(true);

        // Test that get author returns student2 as the author.
        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $response = $tsworkshop->get_author($this->student1->id, $this->student2->id);
        $this->assertEquals($this->student2->id, $response);

        // Test that get author returns student1 as the author because relateduserid is empty.
        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $response = $tsworkshop->get_author($this->student1->id, 0);
        $this->assertEquals($this->student1->id, $response);
    }

    /**
     * Test that is submission draft returns expected value.
     */
    public function test_is_submission_draft() {
        $this->resetAfterTest();

        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $response = $tsworkshop->is_submission_draft(0);
        $this->assertEquals(false, $response);
    }

    /**
     * Test that get due date returns expected value.
     */
    public function test_get_due_date() {
        $this->resetAfterTest();

        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $response = $tsworkshop->get_due_date(0);
        $this->assertEquals(0, $response);
    }

    /**
     * Test that show other posts links returns expected value.
     */
    public function test_show_other_posts_links() {
        $this->resetAfterTest();

        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $response = $tsworkshop->show_other_posts_links(0, 0);
        $this->assertEquals(true, $response);
    }

    /**
     * Test that the correct event data is returned when handling a file submission for a workshop.
     */
    public function test_create_submission_event_data_returns_correct_data_for_file_submission() {
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

        // Log new user in.
        $this->setUser($this->student1);
        $usercontext = context_user::instance($this->student1->id);

        $submissionid = $workshopgenerator->create_submission($this->workshop->id, $this->student1->id);

        // Get item id.
        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $params = new stdClass();
        $params->moduleid = $workshop->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_WORKSHOP_TEXT;
        $result = $tsworkshop->get_itemid($params);

        $this->assertEquals($result, $submissionid);

        $file = create_test_file($submissionid, $usercontext->id, 'mod_workshop', 'submissions');

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "file" => $file,
            "content" => ''
        );

        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $response = $tsworkshop->create_submission_event_data($linkarray);

        $this->assertEquals('assessable_submitted', $response['eventtype']);
        $this->assertEquals($cm->id, $response['contextinstanceid']);
        $this->assertEquals($this->student1->id, $response['userid']);
        $this->assertEquals(array($file->get_pathnamehash()), $response['other']['pathnamehashes']);
        $this->assertEquals($submissionid, $response['objectid']);
        $this->assertEquals($this->student1->id, $response['relateduserid']);
        $this->assertEquals('workshop', $response['other']['modulename']);
        $this->assertEmpty($response['other']['content']);
    }

    /**
     * Test that the correct event data is returned when handling a text submission for a workshop.
     */
    public function test_create_submission_event_data_returns_correct_data_for_text_submission() {
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

        $this->setUser($this->student1);
        $submissionid = $workshopgenerator->create_submission($this->workshop->id, $this->student1->id);

        // Get item id.
        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $params = new stdClass();
        $params->moduleid = $workshop->id;
        $params->userid = $this->student1->id;
        $params->onlinetext = self::TEST_WORKSHOP_TEXT;
        $result = $tsworkshop->get_itemid($params);

        $this->assertEquals($result, $submissionid);

        // Create dummy link array data.
        $linkarray = array(
            "cmid" => $cm->id,
            "userid" => $this->student1->id,
            "content" => self::TEST_WORKSHOP_TEXT
        );

        $tsworkshop = new plagiarism_turnitinsim_workshop();
        $response = $tsworkshop->create_submission_event_data($linkarray);

        $this->assertEquals('assessable_submitted', $response['eventtype']);
        $this->assertEquals($cm->id, $response['contextinstanceid']);
        $this->assertEquals($this->student1->id, $response['userid']);
        $this->assertEquals($submissionid, $response['objectid']);
        $this->assertEquals($this->student1->id, $response['relateduserid']);
        $this->assertEquals('workshop', $response['other']['modulename']);
        $this->assertEquals(self::TEST_WORKSHOP_TEXT, $response['other']['content']);
    }
}