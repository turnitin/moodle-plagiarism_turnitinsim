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
 * Restore code for plagiarism/turnitinsim.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Used when restoring data created by the plugin.
 */
class restore_plagiarism_turnitinsim_plugin extends restore_plagiarism_plugin {

    /**
     * Return the paths of the module data along with the function used for restoring that data.
     */
    protected function define_module_plugin_structure() {
        $paths = array();
        $paths[] = new restore_path_element('turnitinsim_mods', $this->get_pathfor('turnitinsim_mods/turnitinsim_mod'));
        $paths[] = new restore_path_element('turnitinsim_subs', $this->get_pathfor('turnitinsim_subs/turnitinsim_sub'));
        $paths[] = new restore_path_element('turnitinsim_usrs', $this->get_pathfor('turnitinsim_usrs/turnitinsim_usr'));

        return $paths;
    }

    /**
     * Restore the Turnitin settings for this module.
     *
     * @param object $data object The data we are restoring.
     * @throws dml_exception
     */
    public function process_turnitinsim_mods($data) {
        global $DB;

        $data = (object)$data;
        $data->cm = $this->task->get_moduleid();

        $DB->insert_record('plagiarism_turnitinsim_mod', $data);
    }

    /**
     * Restore the links to Turnitin submissions.
     * This will only be done if the module is from the same site from where it was backed up
     * and if the Turnitin submission does not currently exist in the database.
     *
     * @param object $data The data we are restoring.
     * @throws dml_exception
     */
    public function process_turnitinsim_subs($data) {
        global $DB, $SESSION;

        if ($this->task->is_samesite()) {
            $data = (object)$data;

            $params = array('turnitinid' => $data->turnitinid, 'cm' => $this->task->get_moduleid());
            $recordexists = (!empty($data->turnitinid)) ? $DB->record_exists('plagiarism_turnitinsim_sub', $params) : false;

            // At this point Moodle has not restored the necessary submission files so we can not relink them.
            // This means we will have to relink the submissions in the after_restore_module method below.
            if (!$recordexists) {
                $data->cm = $this->task->get_moduleid();

                $SESSION->tsrestore[] = $data;
            }
        }
    }

    /**
     * Restore the Turnitin users.
     * This will only be done if the module is from the same site from where it was backed up
     * and if the Turnitin user id does not currently exist in the database.
     *
     * @param object $data The data we are restoring.
     * @throws dml_exception
     */
    public function process_turnitinsim_usrs($data) {
        global $DB;

        if ($this->task->is_samesite()) {
            $data = (object)$data;
            $recordexists = (!empty($data->turnitinid)) ?
                $DB->record_exists('plagiarism_turnitinsim_users', array('turnitinid' => $data->turnitinid)) : false;

            if (!$recordexists) {
                $DB->insert_record('plagiarism_turnitinsim_users', $data);
            }
        }
    }

    /**
     * Restore the links to submissions that have been sent to Turnitin.
     */
    public function after_restore_module() {
        global $DB, $SESSION;

        foreach ($SESSION->tsrestore as $data) {
            // Get new itemid for files.
            if ($data->type == TURNITINSIM_SUBMISSION_TYPE_FILE) {
                $filerecord = $DB->get_records_select(
                    'files',
                    'contenthash = ? AND pathnamehash != ? AND itemid != ?',
                    array($data->contenthash, $data->identifier, $data->itemid),
                    'id DESC',
                    'itemid, pathnamehash',
                    0,
                    1
                );

                $file = current($filerecord);

                $data->itemid = $file->itemid;
                $data->identifier = $file->pathnamehash;
            }

            // Get new itemid for text content.
            if ($data->type == TURNITINSIM_SUBMISSION_TYPE_CONTENT) {
                $cm = get_coursemodule_from_id('', $data->cm);
                // Create module object and get the online text.
                $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;
                $moduleobject = new $moduleclass;

                $onlinetext = $moduleobject->get_onlinetext($data->itemid);

                // If there's no text, skip it.
                if (empty($onlinetext)) {
                    continue;
                }

                $data->identifier = sha1($onlinetext);

                // Get new item id.
                $params = new stdClass();
                $params->moduleid = $cm->instance;
                $params->userid = $data->userid;
                $params->onlinetext = $onlinetext;
                $data->itemid = $moduleobject->get_itemid($params);
            }

            $DB->insert_record('plagiarism_turnitinsim_sub', $data);
        }

        unset($SESSION->tsrestore);
    }
}