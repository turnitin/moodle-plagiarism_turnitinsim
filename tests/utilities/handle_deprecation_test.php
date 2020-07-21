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
 * Unit tests for (some of) plagiarism/turnitinsim/utilities/handle_deprecations.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2020 Turnitin
 * @author    David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/turnitinsim/utilities/handle_deprecation.php');

/**
 * Tests for handle_deprecations methods.
 */
class plagiarism_turnitinsim_handle_deprecation_testcase extends advanced_testcase {

    /**
     * Test the enable_plugin and plugin_enabled methods.
     */
    public function test_plugin_enabled() {
        $this->resetAfterTest();

        set_config('branch', 38);

        handle_deprecation::set_plugin_enabled(1);
        $this->assertEquals(1, handle_deprecation::get_plugin_enabled());

        handle_deprecation::set_plugin_enabled(0);
        $this->assertEquals(0, handle_deprecation::get_plugin_enabled());

        set_config('branch', 39);

        handle_deprecation::set_plugin_enabled(1);
        $this->assertEquals(1, handle_deprecation::get_plugin_enabled());

        handle_deprecation::set_plugin_enabled(0);
        $this->assertEquals(0, handle_deprecation::get_plugin_enabled());

        handle_deprecation::set_plugin_enabled(null);
        $this->assertEquals(null, handle_deprecation::get_plugin_enabled());
    }

    /**
     * Test that unset_turnitinsim_use handled >= Moodle 3.9 correctly.
     */
    public function test_unset_turnitinsim_use_unsets_value_in_39() {
        $this->resetAfterTest();

        set_config('branch', 39);

        set_config( 'turnitinsim_use', 1, 'plagiarism');

        (new handle_deprecation)->unset_turnitinsim_use();

        $value = get_config('plagiarism', 'turnitinsim_use');

        $this->assertEmpty($value);
    }

    /**
     * Test that unset_turnitinsim_use handled < Moodle 3.9 correctly.
     */
    public function test_unset_turnitinsim_use_leaves_value_in_38() {
        $this->resetAfterTest();

        set_config('branch', 38);
        set_config( 'turnitinsim_use', 1, 'plagiarism');

        (new handle_deprecation)->unset_turnitinsim_use();

        $value = get_config('plagiarism', 'turnitinsim_use');

        $this->assertEquals($value, 1);
    }
}
