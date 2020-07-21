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
 * Configuration to remove on install for plagiarism_turnitinsim component
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');

/**
 * Configuration to remove on install.
 */
function xmldb_plagiarism_turnitinsim_uninstall() {
    plagiarism_plugin_turnitinsim::enable_plugin(null);

    // Loop through all modules that support Plagiarism.
    $mods = core_component::get_plugin_list('mod');
    foreach ($mods as $mod => $modpath) {
        if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
            set_config('turnitinmodenabled'.$mod, null, 'plagiarism_turnitinsim');
        }
    }

    set_config('turnitinapiurl', null, 'plagiarism_turnitinsim');
    set_config('turnitinapikey', null, 'plagiarism_turnitinsim');
    set_config('turnitinenablelogging', null, 'plagiarism_turnitinsim');

    set_config('turnitin_webhook_id', null, 'plagiarism_turnitinsim');
    set_config('turnitin_webhook_secret', null, 'plagiarism_turnitinsim');

    set_config('turnitin_eula_version', null, 'plagiarism_turnitinsim');
    set_config('turnitin_eula_url', null, 'plagiarism_turnitinsim');
}
