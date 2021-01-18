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
 * Unit tests for (some of) plagiarism/turnitinsim/classes/turnitinsim_setttings_defaults_form.class.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/defaults_form.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/utilities/handle_deprecation.php');

/**
 * Tests for default settings form.
 */
class defaultsform_class_testcase extends advanced_testcase {

    /**
     * Set config for use in the tests.
     */
    public function setUp(): void {
        // Set API details in config.
        set_config('turnitinapiurl', 'http://www.example.com', 'plagiarism_turnitinsim');
        set_config('turnitinapikey', 1234, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', 0, 'plagiarism_turnitinsim');
    }

    /**
     * Test that default settings are saved.
     */
    public function test_save() {
        global $DB;

        $this->resetAfterTest();

        // Create data object for default assignment settings.
        $data = new stdClass();
        $data->turnitinenabled = 1;
        $data->reportgenoptions['reportgeneration'] = TURNITINSIM_REPORT_GEN_DUEDATE;
        $data->queuedrafts = 1;
        $data->indexoptions['addtoindex'] = 0;
        $data->excludeoptions['excludequotes'] = 0;
        $data->excludeoptions['excludebiblio'] = 1;
        $data->accessoptions['accessstudents'] = 1;

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_defaults_form();
        $form->save($data);

        // Check settings have been saved.
        $settings = $DB->get_record('plagiarism_turnitinsim_mod', array('cm' => 0));

        $this->assertEquals(1, $settings->turnitinenabled);
        $this->assertEquals(TURNITINSIM_REPORT_GEN_DUEDATE, $settings->reportgeneration);
        $this->assertEquals(1, $settings->queuedrafts);
        $this->assertEquals(0, $settings->addtoindex);
        $this->assertEquals(0, $settings->excludequotes);
        $this->assertEquals(1, $settings->excludebiblio);
        $this->assertEquals(1, $settings->accessstudents);
    }

    /**
     * Test that display outputs an HTML form.
     */
    public function test_display() {
        $this->resetAfterTest();

        // Save Module Settings.
        $form = new plagiarism_turnitinsim_defaults_form();
        $output = $form->display();

        handle_deprecation::assertcontains($this, '</form>', $output);
    }
}