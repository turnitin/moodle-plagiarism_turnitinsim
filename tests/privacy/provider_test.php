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
 * Privacy provider tests.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use plagiarism_turnitinsim\privacy\provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/assign/externallib.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/tests/turnitinsim_generator.php');

/**
 * Privacy provider tests.
 */
class plagiarism_turnitinsim_privacy_provider_testcase extends advanced_testcase {

    /**
     * Setup method that runs before each test.
     */
    public function setUp(): void {
        $this->turnitinsim_generator = new turnitinsim_generator();
        $this->submission = $this->turnitinsim_generator->create_submission();
    }

    /**
     * Test for _get_metadata.
     */
    public function test_get_metadata() {
        $this->resetAfterTest();

        $collection = new collection('plagiarism_turnitinsim');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();

        $this->assertCount(3, $itemcollection);

        // Verify plagiarism_turnitinsim_sub data is returned.
        $this->assertEquals('plagiarism_turnitinsim_sub', $itemcollection[0]->get_name());

        $privacyfields = $itemcollection[0]->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('turnitinid', $privacyfields);
        $this->assertArrayHasKey('identifier', $privacyfields);
        $this->assertArrayHasKey('itemid', $privacyfields);
        $this->assertArrayHasKey('submittedtime', $privacyfields);
        $this->assertArrayHasKey('overallscore', $privacyfields);

        // Verify plagiarism_turnitinsim_users data is returned.
        $this->assertEquals('plagiarism_turnitinsim_users', $itemcollection[1]->get_name());

        $privacyfields = $itemcollection[1]->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('turnitinid', $privacyfields);
        $this->assertArrayHasKey('lasteulaaccepted', $privacyfields);
        $this->assertArrayHasKey('lasteulaacceptedtime', $privacyfields);
        $this->assertArrayHasKey('lasteulaacceptedlang', $privacyfields);

        // Verify turnitinsim_client data is returned.
        $this->assertEquals('plagiarism_turnitinsim_client', $itemcollection[2]->get_name());

        $privacyfields = $itemcollection[2]->get_privacy_fields();
        $this->assertArrayHasKey('firstname', $privacyfields);
        $this->assertArrayHasKey('lastname', $privacyfields);
        $this->assertArrayHasKey('submission_title', $privacyfields);
        $this->assertArrayHasKey('submission_filename', $privacyfields);
        $this->assertArrayHasKey('submission_content', $privacyfields);

        $this->assertEquals('privacy:metadata:plagiarism_turnitinsim_client', $itemcollection[2]->get_summary());
    }

    /**
     * Test that user's contexts are exported.
     */
    public function test_get_contexts_for_userid() {
        global $DB;
        $this->resetAfterTest();

        // Set the cm to the correct one for our submission.
        $cms = $DB->get_records('course_modules');
        $cm = reset($cms);
        $submissions = $DB->get_records('plagiarism_turnitinsim_sub');
        $submission = reset($submissions);

        $update = new stdClass();
        $update->id = $submission->id;
        $update->cm = $cm->instance;
        $DB->update_record('plagiarism_turnitinsim_sub', $update);

        $this->assertEquals(1, count($submissions));

        $contextlist = provider::get_contexts_for_userid($this->submission['student']->id);

        $this->assertCount(1, $contextlist);
    }

    /**
     * Test that user data is exported,
     */
    public function test_export_plagiarism_user_data() {
        global $DB;
        $this->resetAfterTest();

        $submissions = $DB->get_records('plagiarism_turnitinsim_sub');
        $this->assertEquals(1, count($submissions));

        // Export all of the data for the user.
        provider::export_plagiarism_user_data($this->submission['student']->id, $this->submission['context'], array(), array());
        $writer = \core_privacy\local\request\writer::with_context($this->submission['context']);
        $this->assertTrue($writer->has_any_data());
    }

    /**
     * Test that data can be deleted.
     */
    public function test_delete_plagiarism_for_user() {
        global $DB;
        $this->resetAfterTest();

        $submissions = $DB->get_records('plagiarism_turnitinsim_sub');
        $this->assertEquals(1, count($submissions));

        // Delete all of the data for the user.
        provider::delete_plagiarism_for_user($this->submission['student']->id, $this->submission['context']);

        $submissions = $DB->get_records('plagiarism_turnitinsim_sub');
        $this->assertEquals(0, count($submissions));
    }

    /**
     * Test that data for contexts can be deleted.
     */
    public function test_delete_plagiarism_for_context() {
        global $DB;
        $this->resetAfterTest();

        $submissions = $this->turnitinsim_generator->create_submission(3);

        $cmid = $submissions['context']->__get('instanceid');

        $turnitinsimsubmissions = $DB->get_records(
            'plagiarism_turnitinsim_sub',
            array('cm' => $cmid)
        );
        $this->assertEquals(3, count($turnitinsimsubmissions));

        // Delete all of the data for the user.
        provider::delete_plagiarism_for_context($submissions['context']);

        $turnitinsimsubmissions = $DB->get_records(
            'plagiarism_turnitinsim_sub',
            array('cm' => $cmid)
        );
        $this->assertEquals(0, count($turnitinsimsubmissions));
    }
}