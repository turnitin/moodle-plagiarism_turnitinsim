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
 * Group class for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/plagiarism/turnitinsim/utilities/handle_deprecation.php');

/**
 * Group class for plagiarism_turnitinsim component.
 */
class plagiarism_turnitinsim_group {

    /**
     * @var int The ID of the grup.
     */
    public $groupid;

    /**
     * @var string The Turnitin group ID for the group.
     */
    public $turnitinid;

    /**
     * plagiarism_turnitinsim_group constructor.
     * @param int $groupid The ID of the group.
     * @throws dml_exception
     */
    public function __construct($groupid) {
        global $DB;

        $this->set_groupid($groupid);

        // Get group details.
        if ($group = $DB->get_record('plagiarism_turnitinsim_group', array('groupid' => $groupid))) {
            $this->set_turnitinid($group->turnitinid);
        }

        // If there is no group record then we will create one.
        if (empty($this->get_turnitinid())) {
            $turnitinid = $this->create_turnitinid();
            $this->set_turnitinid($turnitinid);
        }
    }

    /**
     * Create a Turnitin id and save it for this user.
     */
    public function create_turnitinid() {
        global $DB;

        $turnitinid = (new handle_deprecation)->create_uuid();

        $group = new stdClass();
        $group->groupid = $this->get_groupid();
        $group->turnitinid = $turnitinid;

        $DB->insert_record('plagiarism_turnitinsim_group', $group);

        return $turnitinid;
    }

    /**
     * Get the group ID.
     *
     * @return mixed
     */
    public function get_groupid() {
        return $this->groupid;
    }

    /**
     * Set the group ID.
     *
     * @param mixed $groupid
     */
    public function set_groupid($groupid) {
        $this->groupid = $groupid;
    }

    /**
     * Get the Turnitin group ID.
     *
     * @return mixed
     */
    public function get_turnitinid() {
        return $this->turnitinid;
    }

    /**
     * Set the Turnitin group ID.
     *
     * @param mixed $turnitinid
     */
    public function set_turnitinid($turnitinid) {
        $this->turnitinid = $turnitinid;
    }
}