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

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/plagiarism/turnitinsim/lib.php');

class plagiarism_turnitinsim_observer {


    /**
     * Build the eventdata array.
     *
     * @param $event
     * @param $eventtype
     * @param $module
     * @return array $eventdata
     */
    public static function build_event_data($event, $eventtype, $module = '') {
        $eventdata = $event->get_data();
        $eventdata['eventtype'] = $eventtype;
        if ($module != '') {
            $eventdata['other']['modulename'] = $module;
        }

        return $eventdata;
    }

    /**
     * Handle the assignment assessable_uploaded event.
     * @param \assignsubmission_file\event\assessable_uploaded $event
     */
    public static function assignsubmission_file_uploaded(\assignsubmission_file\event\assessable_uploaded $event) {
        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->submission_handler(self::build_event_data($event, 'file_uploaded', 'assign'));
    }

    /**
     * Handle the assignment assessable_uploaded event.
     * @param \assignsubmission_onlinetext\event\assessable_uploaded $event
     */
    public static function assignsubmission_onlinetext_uploaded(
        \assignsubmission_onlinetext\event\assessable_uploaded $event) {
        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->submission_handler(self::build_event_data($event, 'content_uploaded', 'assign'));
    }

    /**
     * Handle the assignment assessable_submitted event.
     * @param \mod_assign\event\assessable_submitted $event
     */
    public static function assignsubmission_submitted(\mod_assign\event\assessable_submitted $event) {
        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->submission_handler(self::build_event_data($event, 'assessable_submitted', 'assign'));
    }

    /**
     * Handle the forum assessable_uploaded event.
     * @param \mod_forum\event\assessable_uploaded $event
     */
    public static function forum_assessable_uploaded(\mod_forum\event\assessable_uploaded $event) {
        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->submission_handler(self::build_event_data($event, 'assessable_submitted', 'forum'));
    }

    /**
     * Handle the workshop assessable_uploaded event.
     * @param \mod_workshop\event\assessable_uploaded $event
     */
    public static function workshop_assessable_uploaded(
        \mod_workshop\event\assessable_uploaded $event) {
        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->submission_handler(self::build_event_data($event, 'assessable_submitted', 'workshop'));
    }

    public static function module_updated(\core\event\course_module_updated $event) {
        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->module_updated(self::build_event_data($event, 'module_updated'));
    }
}