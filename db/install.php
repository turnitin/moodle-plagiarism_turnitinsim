<?php
// This file is part of Ephorus
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
 * Configuration to update on install for plagiarism_turnitinsim component
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Configuration to update on install.
 */
function xmldb_plagiarism_turnitinsim_install() {
    global $DB;

    if ($DB->get_record('config_plugins', array('plugin' => 'plagiarism_turnitincheck'))) {
        upgrade_from_turnitincheck_plugin("plagiarism_turnitincheck_grp", "plagiarism_turnitinsim_group");
        upgrade_from_turnitincheck_plugin("plagiarism_turnitincheck_mod", "plagiarism_turnitinsim_mod");
        upgrade_from_turnitincheck_plugin("plagiarism_turnitincheck_sub", "plagiarism_turnitinsim_sub");
        upgrade_from_turnitincheck_plugin("plagiarism_turnitincheck_usr", "plagiarism_turnitinsim_users");

        set_config('turnitinsim_use', get_config('plagiarism', 'turnitincheck_use'), 'plagiarism');
    }
}

function upgrade_from_turnitincheck_plugin($oldtable, $newtable) {
    global $DB;

    $dbman = $DB->get_manager();

    $table = new xmldb_table($oldtable);
    if ($dbman->table_exists($table)) {
        $data = $DB->get_records($oldtable);
        foreach ($data as $row) {
            unset($row->id);
            $DB->insert_record($newtable, $row);
        }
    }
}
