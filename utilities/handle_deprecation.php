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
     * @param int $enabled 1 if enabled, 0 if not.
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
     */
    public static function get_plugin_enabled() {
        global $CFG;

        return $CFG->branch < 39 ? get_config('plagiarism', 'turnitinsim_use')
            : get_config('plagiarism_turnitinsim', 'enabled');
    }

    /**
     * In Moodle 3.9, download_as_dataformat() was deprecated and \core\dataformat::download_data() was introduced.
     * This method handles our support for multiple Moodle versions.
     *
     * @param string $exportfile The name of the file to download.
     * @param string $dataformat The format of the file.
     * @param array $columns The names of the columns.
     * @param string $data The data to download.
     */
    public static function download_data($exportfile, $dataformat, $columns, $data) {
        global $CFG;

        $CFG->branch >= 39 ? \core\dataformat::download_data($exportfile, $dataformat, $columns, $data)
            : download_as_dataformat($exportfile, $dataformat, $columns, $data);
    }

    /**
     * In Moodle 3.10, Moodle switched to use PHPUnit 8.5 which contains deprecations for some assertions.
     * assertContains was deprecated in favour of the newer assertStringContainsString. (PHPUnit 7.5)
     * This method handles our support for Moodle versions that use PHPUnit 6.5. (Moodle 3.5 and 3.6)
     *
     * @param object $object The test class object.
     * @param string $needle The string we want to find.
     * @param string $haystack The string we are searching within.
     */
    public static function assertcontains($object, $needle, $haystack) {
        global $CFG;

        $CFG->branch >= 37 ? $object->assertStringContainsString($needle, $haystack)
            : $object->assertContains($needle, $haystack);
    }

    /**
     * In Moodle 3.10, Moodle switched to use PHPUnit 8.5 which contains deprecations for some assertions.
     * assertNotContains was deprecated in favour of the newer assertStringNotContainsString. (PHPUnit 7.5)
     * This method handles our support for Moodle versions that use PHPUnit 6.5. (Moodle 3.5 and 3.6)
     *
     * @param object $object The test class object.
     * @param string $needle The string we want to find.
     * @param string $haystack The string we are searching within.
     */
    public static function assertnotcontains($object, $needle, $haystack) {
        global $CFG;

        $CFG->branch >= 37 ? $object->assertStringNotContainsString($needle, $haystack)
            : $object->assertNotContains($needle, $haystack);
    }

    /**
     * In Moodle 3.10, Moodle switched to use PHPUnit 8.5 which contains deprecations for some assertions.
     * assertInternalType was deprecated in favour of newer methods such as assertIsInt. (PHPUnit 7.5)
     * This method handles our support for Moodle versions that use PHPUnit 6.5. (Moodle 3.5 and 3.6)
     *
     * @param object $object The test class object.
     * @param string $value The value we are looking for.
     */
    public static function assertinternaltypeint($object, $value) {
        global $CFG;

        $CFG->branch >= 37 ? $object->assertIsInt($value) : $object->assertInternalType("int", $value);
    }

    /**
     * In Moodle 3.11, Moodle switched to use PHPUnit 9.5 which contains deprecations for some assertions.
     * assertRegExp was deprecated in favour of newer methods such as assertMatchesRegularExpression. (PHPUnit 7.5)
     * This method handles our support for Moodle versions that use PHPUnit versions below 9.1. (Moodle 3.10 and below)
     *
     * @param object $object The test class object.
     * @param string $pattern The regex pattern we are looking for.
     * @param string $string The string we are searching within.
     */
    public static function assertregex($object, $pattern, $string) {
        global $CFG;

        $CFG->branch >= 311 ? $object->assertMatchesRegularExpression($pattern, $string) :
            $object->assertRegExp($pattern, $string);
    }
}