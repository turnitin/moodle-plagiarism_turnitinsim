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
 * User class for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/plagiarism/turnitinsim/utilities/handle_deprecation.php');

/**
 * User class for plagiarism_turnitinsim component.
 */
class plagiarism_turnitinsim_user {

    /**
     * @var int The Moodle user ID.
     */
    public $userid;

    /**
     * @var int The Turnitin user ID.
     */
    public $turnitinid;

    /**
     * @var string The EULA version the user last accepted.
     */
    public $lasteulaaccepted;

    /**
     * @var int The unix timestamp that latest EULA was accepted.
     */
    public $lasteulaacceptedtime;

    /**
     * @var string The language of the EULA that was last accepted.
     */
    public $lasteulaacceptedlang;

    /**
     * @var object Request object.
     */
    public $tsrequest;

    /**
     * plagiarism_turnitinsim_user constructor.
     * @param int $userid The Moodle user ID.
     * @throws dml_exception
     */
    public function __construct($userid) {
        global $DB;

        if (empty($userid)) {
            return;
        }

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

        $turnitinid = (new handle_deprecation)->create_uuid();

        $user = new stdClass();
        $user->userid = $this->get_userid();
        $user->turnitinid = $turnitinid;

        $DB->insert_record('plagiarism_turnitinsim_users', $user);

        return $turnitinid;
    }

    /**
     * Get the Moodle user ID.
     *
     * @return mixed
     */
    public function get_userid() {
        return $this->userid;
    }

    /**
     * Set the Moodle user ID.
     *
     * @param int $userid The Moodle user ID.
     */
    public function set_userid($userid) {
        $this->userid = $userid;
    }

    /**
     * Get the Turnitin user ID.
     *
     * @return mixed
     */
    public function get_turnitinid() {
        return $this->turnitinid;
    }

    /**
     * Set the Turnitin user ID.
     *
     * @param int $turnitinid The Turnitin user ID.
     */
    public function set_turnitinid($turnitinid) {
        $this->turnitinid = $turnitinid;
    }

    /**
     * Get the EULA version the user last accepted.
     *
     * @return mixed
     */
    public function get_lasteulaaccepted() {
        return $this->lasteulaaccepted;
    }

    /**
     * Set the EULA version the user last accepted.
     *
     * @param string $lasteulaaccepted The EULA version the user last accepted.
     */
    public function set_lasteulaaccepted($lasteulaaccepted) {
        $this->lasteulaaccepted = $lasteulaaccepted;
    }

    /**
     * Get the unix timestamp that latest EULA was accepted.
     * @return mixed
     */
    public function get_lasteulaacceptedtime() {
        return $this->lasteulaacceptedtime;
    }

    /**
     * Set the unix timestamp that latest EULA was accepted.
     *
     * @param mixed $lasteulaacceptedtime The unix timestamp that latest EULA was accepted.
     */
    public function set_lasteulaacceptedtime($lasteulaacceptedtime) {
        $this->lasteulaacceptedtime = $lasteulaacceptedtime;
    }

    /**
     * Get the language of the EULA that was last accepted.
     *
     * @return mixed
     */
    public function get_lasteulaacceptedlang() {
        return $this->lasteulaacceptedlang;
    }

    /**
     * Set the language of the EULA that was last accepted.
     *
     * @param string $lasteulaacceptedlang The language of the EULA that was last accepted.
     */
    public function set_lasteulaacceptedlang($lasteulaacceptedlang) {
        $this->lasteulaacceptedlang = $lasteulaacceptedlang;
    }
}