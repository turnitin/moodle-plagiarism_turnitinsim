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
 * Database upgrade script for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Database upgrade script for plagiarism_turnitinsim component.
 *
 * @param int $oldversion The version that is currently installed. (The version being upgraded from)
 * @return bool true if upgrade was successful.
 * @throws ddl_exception
 * @throws ddl_table_missing_exception
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_plagiarism_turnitinsim_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2018021501) {
        $table = new xmldb_table('plagiarism_turnitinsim_sub');
        $field = new xmldb_field('requested_time', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'submitted_time');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018021501, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018021601) {
        $table = new xmldb_table('plagiarism_turnitinsim_sub');
        $field = new xmldb_field('errormessage', XMLDB_TYPE_CHAR, '255', null, false, null, null, 'requested_time');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018021601, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018030601) {
        $table = new xmldb_table('plagiarism_turnitinsim_sub');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '20', null, false, null, null, 'itemid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018030601, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018031801) {
        $table = new xmldb_table('plagiarism_turnitinsim_mod');

        $field = new xmldb_field('queuedrafts', XMLDB_TYPE_INTEGER, '1', null, false, null, 0, 'checkprivate');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018031801, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018032001) {
        $table = new xmldb_table('plagiarism_turnitinsim_mod');

        $field = new xmldb_field('reportgeneration', XMLDB_TYPE_INTEGER, '1', null, false, null, 0, 'turnitinenabled');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018032001, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018032003) {
        $table = new xmldb_table('plagiarism_turnitinsim_sub');

        $field = new xmldb_field('to_generate', XMLDB_TYPE_INTEGER, '1', null, false, null, 0, 'submitted_time');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('generation_time', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'to_generate');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018032003, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018050401) {
        $table = new xmldb_table('plagiarism_turnitinsim_users');

        $field = new xmldb_field('lasteulaaccepted', XMLDB_TYPE_CHAR, '100', null, false, null, null, 'turnitinid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('lasteulaacceptedtime', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'lasteulaaccepted');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018050401, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018051001) {
        $table = new xmldb_table('plagiarism_turnitinsim_mod');

        $field = new xmldb_field('addtoindex', XMLDB_TYPE_INTEGER, '1', null, false, null, 0, 'reportgeneration');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('accessstudents', XMLDB_TYPE_INTEGER, '1', null, false, null, 0, 'addtoindex');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018051001, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018051501) {
        $table = new xmldb_table('plagiarism_turnitinsim_users');
        $field = new xmldb_field('lasteulaacceptedlang', XMLDB_TYPE_CHAR, '10', null, false, null, null, 'lasteulaacceptedtime');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018051501, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018062601) {
        $table = new xmldb_table('plagiarism_turnitinsim_sub');
        $field = new xmldb_field('submitter', XMLDB_TYPE_INTEGER, '10', null, false, null, 0, 'userid');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2018062601, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2018080301) {
        // Add groupid column to submission.
        $table = new xmldb_table('plagiarism_turnitinsim_sub');
        $field = new xmldb_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'submitter');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add group table.
        $table = new xmldb_table('plagiarism_turnitinsim_group');

        // Adding fields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('turnitinid', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table assign_overrides.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Create table.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_plugin_savepoint(true, 2018080301, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2019121601) {
        // Get the current features enabled.
        // If require_eula does not exist then set it to true, it will be overwritten
        // if necessary when the scheduled tasks run.
        $features = json_decode(get_config('plagiarism_turnitinsim', 'turnitin_features_enabled'));
        if (!isset($features->tenant)) {
            $features = new \stdClass();
            $features->tenant = new \stdClass();
            $features->tenant->require_eula = true;
            set_config('turnitin_features_enabled', json_encode($features), 'plagiarism_turnitinsim');
        }

        upgrade_plugin_savepoint(true, 2019121601, 'plagiarism', 'turnitinsim');
    }

    // This block will migrate the config namespace for the plugin to plagiarism_turnitinsim.
    if ($oldversion < 2020012801) {
        $data = get_config('plagiarism');

        $properties = array('turnitinmodenabledassign', 'turnitinmodenabledforum', 'turnitinmodenabledworkshop',
            'turnitinapikey', 'turnitinapiurl', 'turnitinenablelogging', 'turnitin_eula_url', 'turnitin_eula_version',
            'turnitin_features_enabled', 'turnitinhideidentity', 'turnitinviewermatchsubinfo', 'turnitinviewersavechanges',
            'turnitinviewerviewfullsource', 'turnitin_webhook_id', 'turnitin_webhook_secret');
        foreach ($properties as $property) {
            if (isset($data->$property)) {
                set_config($property, $data->$property, 'plagiarism_turnitinsim');
                unset_config($property, 'plagiarism');
            }
        }

        upgrade_plugin_savepoint(true, 2020012801, 'plagiarism', 'turnitinsim');
    }

    // This block will rename database fields that have underscores.
    if ($oldversion < 2020032404) {
        $table = new xmldb_table('plagiarism_turnitinsim_sub');

        $field = new xmldb_field('submitted_time', XMLDB_TYPE_INTEGER, '10', null, false, null, 0, 'type');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'submittedtime');
        }

        $field = new xmldb_field('to_generate', XMLDB_TYPE_INTEGER, '1', null, false, null, 0, 'submitted_time');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'togenerate');
        }

        $field = new xmldb_field('generation_time', XMLDB_TYPE_INTEGER, '10', null, false, null, 0, 'to_generate');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'generationtime');
        }

        $field = new xmldb_field('overall_score', XMLDB_TYPE_INTEGER, '10', null, false, null, null, 'generation_time');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'overallscore');
        }

        $field = new xmldb_field('requested_time', XMLDB_TYPE_INTEGER, '10', null, false, null, 0, 'overall_score');
        if ($dbman->field_exists($table, $field)) {
            $dbman->rename_field($table, $field, 'requestedtime');
        }

        upgrade_plugin_savepoint(true, 2020032404, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2020041501) {
        $table = new xmldb_table('plagiarism_turnitinsim_sub');
        $field = new xmldb_field('tiiattempts', XMLDB_TYPE_INTEGER, '10', null, false, null, 0, 'errormessage');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $DB->set_field('plagiarism_turnitinsim_sub', 'tiiattempts', 0);
        }

        $field = new xmldb_field('tiiretrytime', XMLDB_TYPE_INTEGER, '10', null, false, null, 0, 'tiiattempts');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $DB->set_field('plagiarism_turnitinsim_sub', 'tiiattempts', 0);
        }
        upgrade_plugin_savepoint(true, 2020041501, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2020061201) {
        // Convert turnitinsim_use as it is being deprecated.
        $turnitinsimuse = get_config('plagiarism', 'turnitinsim_use');
        set_config('enabled', empty($turnitinsimuse) ? 0 : 1, 'plagiarism_turnitinsim');

        upgrade_plugin_savepoint(true, 2020061201, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2020061202) {
        // This line needs to be included in all future upgrade blocks until we drop 3.8 support.
        // This is because a user may upgrade Moodle after upgrading to this version.
        (new handle_deprecation)->unset_turnitinsim_use();

        upgrade_plugin_savepoint(true, 2020061202, 'plagiarism', 'turnitinsim');
    }

    if ($oldversion < 2020092301) {
        (new handle_deprecation)->unset_turnitinsim_use();

        $table = new xmldb_table('plagiarism_turnitinsim_sub');
        $field = new xmldb_field('quizanswer', XMLDB_TYPE_CHAR, '32', null, false, null, 0, 'tiiretrytime');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $DB->set_field('plagiarism_turnitinsim_sub', 'quizanswer', 0);
        }

        upgrade_plugin_savepoint(true, 2020092301, 'plagiarism', 'turnitinsim');
    }

    // Remove "/api" from the Turnitin URL as its been added to the endpoint constants.
    // Update field defaults for quizanswer to match install.xml.
    if ($oldversion < 2021030201) {
        (new handle_deprecation)->unset_turnitinsim_use();

        $turnitinapiurl = get_config('plagiarism_turnitinsim', 'turnitinapiurl');

        set_config('turnitinapiurl', str_replace("/api", '', $turnitinapiurl), 'plagiarism_turnitinsim');

        $table = new xmldb_table('plagiarism_turnitinsim_sub');
        $field = new xmldb_field('quizanswer', XMLDB_TYPE_CHAR, '32', null, false, null, 0, 'tiiretrytime');

        $dbman->change_field_default($table, $field);

        upgrade_plugin_savepoint(true, 2021030201, 'plagiarism', 'turnitinsim');
    }

    // Use the existing API URL to map to an external routing URL.
    if ($oldversion < 2021101802) {
        (new handle_deprecation)->unset_turnitinsim_use();

        // Get the routing URL if necessary.
        (new plagiarism_turnitinsim_task())->check_routing_url();

        upgrade_plugin_savepoint(true, 2021101802, 'plagiarism', 'turnitinsim');
    }

    return true;
}