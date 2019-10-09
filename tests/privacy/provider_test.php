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
 * @package    plagiarism_turnitincheck
 * @copyright  2018 John McGettrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_privacy\local\metadata\collection;
use core_privacy\local\request\deletion_criteria;
use plagiarism_turnitincheck\privacy\provider;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/assign/externallib.php');
require_once($CFG->dirroot . '/plagiarism/turnitincheck/tests/turnitincheck_generator.php');

class plagiarism_turnitincheck_privacy_provider_testcase extends advanced_testcase {

    public function setup() {
        $this->turnitincheck_generator = new turnitincheck_generator();
        $this->submission = $this->turnitincheck_generator->create_submission();
    }

    /**
     * Test for _get_metadata.
     */
    public function test_get_metadata() {
        $this->resetAfterTest();

        $collection = new collection('plagiarism_turnitincheck');
        $newcollection = provider::get_metadata($collection);
        $itemcollection = $newcollection->get_collection();

        $this->assertCount(3, $itemcollection);

        // Verify plagiarism_turnitincheck_sub data is returned.
        $this->assertEquals('plagiarism_turnitincheck_sub', $itemcollection[0]->get_name());

        $privacyfields = $itemcollection[0]->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('turnitinid', $privacyfields);
        $this->assertArrayHasKey('identifier', $privacyfields);
        $this->assertArrayHasKey('itemid', $privacyfields);
        $this->assertArrayHasKey('submitted_time', $privacyfields);
        $this->assertArrayHasKey('overall_score', $privacyfields);

        // Verify plagiarism_turnitincheck_usr data is returned.
        $this->assertEquals('plagiarism_turnitincheck_usr', $itemcollection[1]->get_name());

        $privacyfields = $itemcollection[1]->get_privacy_fields();
        $this->assertArrayHasKey('userid', $privacyfields);
        $this->assertArrayHasKey('turnitinid', $privacyfields);
        $this->assertArrayHasKey('lasteulaaccepted', $privacyfields);
        $this->assertArrayHasKey('lasteulaacceptedtime', $privacyfields);
        $this->assertArrayHasKey('lasteulaacceptedlang', $privacyfields);

        // Verify turnitincheck_client data is returned.
        $this->assertEquals('plagiarism_turnitincheck_client', $itemcollection[2]->get_name());

        $privacyfields = $itemcollection[2]->get_privacy_fields();
        $this->assertArrayHasKey('firstname', $privacyfields);
        $this->assertArrayHasKey('lastname', $privacyfields);
        $this->assertArrayHasKey('submission_title', $privacyfields);
        $this->assertArrayHasKey('submission_filename', $privacyfields);
        $this->assertArrayHasKey('submission_content', $privacyfields);

        $this->assertEquals('privacy:metadata:plagiarism_turnitincheck_client', $itemcollection[2]->get_summary());
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
        $submissions = $DB->get_records('plagiarism_turnitincheck_sub');
        $submission = reset($submissions);

        $update = new stdClass();
        $update->id = $submission->id;
        $update->cm = $cm->instance;
        $DB->update_record('plagiarism_turnitincheck_sub', $update);

        $this->assertEquals(1, count($submissions));

        $contextlist = provider::get_contexts_for_userid($this->submission['student']->id);

        $this->assertCount(1, $contextlist);
    }

    public function test_export_plagiarism_user_data() {
        global $DB;
        $this->resetAfterTest();

        $submissions = $DB->get_records('plagiarism_turnitincheck_sub');
        $this->assertEquals(1, count($submissions));

        // Export all of the data for the user.
        provider::export_plagiarism_user_data($this->submission['student']->id, $this->submission['context'], array(), array());
        $writer = \core_privacy\local\request\writer::with_context($this->submission['context']);
        $this->assertTrue($writer->has_any_data());
    }

    public function test_delete_plagiarism_for_user() {
        global $DB;
        $this->resetAfterTest();

        $submissions = $DB->get_records('plagiarism_turnitincheck_sub');
        $this->assertEquals(1, count($submissions));

        // Delete all of the data for the user.
        provider::delete_plagiarism_for_user($this->submission['student']->id, $this->submission['context']);

        $submissions = $DB->get_records('plagiarism_turnitincheck_sub');
        $this->assertEquals(0, count($submissions));
    }

    public function test_delete_plagiarism_for_context() {
        global $DB;
        $this->resetAfterTest();

        $submissions = $this->turnitincheck_generator->create_submission(3);

        $cmid = $submissions['context']->__get('instanceid');

        $turnitinchecksubmissions = $DB->get_records(
            'plagiarism_turnitincheck_sub',
            array('cm' => $cmid)
        );
        $this->assertEquals(3, count($turnitinchecksubmissions));

        // Delete all of the data for the user.
        provider::delete_plagiarism_for_context($submissions['context']);

        $turnitinchecksubmissions = $DB->get_records(
            'plagiarism_turnitincheck_sub',
            array('cm' => $cmid)
        );
        $this->assertEquals(0, count($turnitinchecksubmissions));
    }
}