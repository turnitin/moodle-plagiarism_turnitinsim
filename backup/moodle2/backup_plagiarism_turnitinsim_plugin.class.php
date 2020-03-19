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
 * Backup code for plagiarism/turnitinsim.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Used when backing up data created by the plugin.
 */
class backup_plagiarism_turnitinsim_plugin extends backup_plagiarism_plugin {

    /**
     * Required by Moodle's backup tool to define the plugin structure.
     *
     * @return backup_plugin_element
     * @throws backup_step_exception
     * @throws base_element_struct_exception
     */
    protected function define_module_plugin_structure() {
        $plugin = $this->get_plugin_element();

        $pluginelement = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($pluginelement);

        // Add module config elements.
        $mods = new backup_nested_element('turnitinsim_mods');
        $mod = new backup_nested_element(
            'turnitinsim_mod',
            array('id'),
            array(
                'turnitinenabled', 'reportgeneration', 'queuedrafts', 'addtoindex',
                'excludequotes', 'excludebiblio', 'accessstudents'
            )
        );
        $pluginelement->add_child($mods);
        $mods->add_child($mod);
        $mod->set_source_table('plagiarism_turnitinsim_mod', array('cm' => backup::VAR_PARENTID));

        // Add submission and user elements if required.
        if ($this->get_setting_value('userinfo')) {
            $submissions = new backup_nested_element('turnitinsim_subs');
            $submission = new backup_nested_element(
                'turnitinsim_sub',
                array('id'),
                array(
                    'userid', 'turnitinid', 'status', 'identifier', 'itemid', 'type', 'submittedtime',
                    'togenerate', 'generationtime', 'overallscore', 'requestedtime', 'errormessage', 'contenthash'
                )
            );
            $pluginelement->add_child($submissions);
            $submissions->add_child($submission);

            // Get submission details along with contenthash from files table.
            $submission->set_source_sql(
                'SELECT PTS.userid, PTS.turnitinid, PTS.status, PTS.identifier, PTS.itemid, PTS.type,
                PTS.submittedtime, PTS.togenerate, PTS.generationtime, PTS.overallscore, PTS.requestedtime,
                PTS.errormessage, F.contenthash
                FROM {plagiarism_turnitinsim_sub} PTS
                LEFT JOIN {files} F
                ON PTS.identifier = F.pathnamehash
                WHERE PTS.cm = ? ',
                array(backup::VAR_PARENTID)
            );

            // Backup users who have submitted to this module.
            $users = new backup_nested_element('turnitinsim_usrs');
            $user = new backup_nested_element(
                'turnitinsim_usr',
                array('id'),
                array('userid', 'turnitinid')
            );
            $pluginelement->add_child($users);
            $users->add_child($user);
            $user->set_source_sql(
                'SELECT PTU.id, PTU.userid, PTU.turnitinid
                FROM {plagiarism_turnitinsim_users} PTU
                JOIN {plagiarism_turnitinsim_sub} PTS
                ON PTS.userid = PTU.userid
                WHERE PTS.cm = ? ',
                array(backup::VAR_PARENTID)
            );
        }

        return $plugin;
    }
}