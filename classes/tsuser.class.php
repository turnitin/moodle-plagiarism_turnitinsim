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
 * User class for plagiarism_turnitinsim component
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class tsuser {

    public $userid;
    public $turnitinid;
    public $lasteulaaccepted;
    public $lasteulaacceptedtime;
    public $lasteulaacceptedlang;
    public $tsrequest;

    public function __construct($userid) {
        global $DB;

        $this->set_userid($userid);

        // If there is no user record then create one.
        if ($user = $DB->get_record('plagiarism_turnitinsim_users', array('userid' => $userid))) {
            $this->set_turnitinid($user->turnitinid);
            $this->set_lasteulaaccepted($user->lasteulaaccepted);
            $this->set_lasteulaacceptedtime($user->lasteulaacceptedtime);
            $this->set_lasteulaacceptedlang($user->lasteulaacceptedlang);
        }

        // Create turnitin ID if necessary.
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

        $turnitinid = generate_uuid();

        $user = new stdClass();
        $user->userid = $this->get_userid();
        $user->turnitinid = $turnitinid;

        $DB->insert_record('plagiarism_turnitinsim_users', $user);

        return $turnitinid;
    }

    /**
     * @return mixed
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * @param mixed $userid
     */
    public function set_userid($userid) {
        $this->userid = $userid;
    }

    /**
     * @return mixed
     */
    public function get_turnitinid() {
        return $this->turnitinid;
    }

    /**
     * @param mixed $turnitinid
     */
    public function set_turnitinid($turnitinid) {
        $this->turnitinid = $turnitinid;
    }

    /**
     * @return mixed
     */
    public function get_lasteulaaccepted() {
        return $this->lasteulaaccepted;
    }

    /**
     * @param mixed $lasteulaaccepted
     */
    public function set_lasteulaaccepted($lasteulaaccepted) {
        $this->lasteulaaccepted = $lasteulaaccepted;
    }

    /**
     * @return mixed
     */
    public function get_lasteulaacceptedtime() {
        return $this->lasteulaacceptedtime;
    }

    /**
     * @param mixed $lasteulaacceptedtime
     */
    public function set_lasteulaacceptedtime($lasteulaacceptedtime) {
        $this->lasteulaacceptedtime = $lasteulaacceptedtime;
    }

    /**
     * @return mixed
     */
    public function get_lasteulaacceptedlang() {
        return $this->lasteulaacceptedlang;
    }

    /**
     * @param mixed $lasteulaacceptedtime
     */
    public function set_lasteulaacceptedlang($lasteulaacceptedlang) {
        $this->lasteulaacceptedlang = $lasteulaacceptedlang;
    }
}