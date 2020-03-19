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
 * Update local configuration from Turnitin.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace plagiarism_turnitinsim\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Update local configuration from Turnitin.
 */
class admin_update extends \core\task\scheduled_task {

    /**
     * Get the task name.
     *
     * @return string
     * @throws \coding_exception
     */
    public function get_name() {
        return \get_string('taskadminupdate', 'plagiarism_turnitinsim');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG;

        require_once($CFG->dirroot.'/plagiarism/turnitinsim/locallib.php');
        plagiarism_turnitinsim_task_admin_update();
    }
}