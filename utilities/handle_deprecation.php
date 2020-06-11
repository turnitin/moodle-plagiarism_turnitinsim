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
        return $this->branch < 38 ? generate_uuid() : \core\uuid::generate();
    }

    /**
     * In Moodle 3.9, the config values for enabling and disabling the plugin were changed.
     * turnitinsim_use is now deprecated and replaced with "enabled" for this plugin.
     *
     * This method handles our support for multiple Moodle versions for unsetting the config value.
     * As this can't be unset until after a user has upgraded Moodle, we must run it on future upgrades.
     * Until we no longer support 3.8.
     *
     */
    public function unset_turnitinsim_use() {
        global $CFG;

        $turnitinsimuse = get_config('plagiarism', 'turnitinsim_use');

        if ($CFG->branch >= 39 && !empty($turnitinsimuse)) {
            unset_config('turnitinsim_use', 'plagiarism');
        }
    }

    /**
     * In Moodle 3.9, the config values for enabling and disabling the plugin were changed.
     * turnitinsim_use is now deprecated and replaced with "enabled" for this plugin.
     *
     * This method handles our support for multiple Moodle versions for setting the config value.
     *
     * @param $enabled 1 if enabled, 0 if not.
     */
    public static function set_plugin_enabled($enabled) {
        global $CFG;

        $CFG->branch < 39 ? set_config('turnitinsim_use', $enabled, 'plagiarism')
            : set_config('enabled', $enabled, 'plagiarism_turnitinsim');
    }

    /**
     * In Moodle 3.9, the config values for enabling and disabling the plugin were changed.
     * turnitinsim_use is now deprecated and replaced with "enabled" for this plugin.
     *
     * This method handles our support for multiple Moodle versions for getting the config value.
     *
     * @param $enabled 1 if enabled, 0 if not.
     */
    public static function get_plugin_enabled() {
        global $CFG;

        return $CFG->branch < 39 ? get_config('plagiarism', 'turnitinsim_use')
            : get_config('plagiarism_turnitinsim', 'enabled');
    }
}