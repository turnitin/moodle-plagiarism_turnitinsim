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
 * Unit tests for (some of) plagiarism/turnitinsim/lib.php.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

class behat_plagiarism_turnitinsim extends behat_base {

    /**
     * @Given I configure TurnitinSim credentials
     */
    public function i_configure_turnitinsim_credentials() {
        $apikey = getenv('TII_APIKEY');
        $apiurl = getenv('TII_APIURL');

        $this->getSession()->getPage()->find("xpath", "//input[@type='password' and @name='turnitinapikey']")->setValue($apikey);
        $this->getSession()->getPage()->find("xpath", "//input[@type='text' and @name='turnitinapiurl']")->setValue($apiurl);
    }

    /**
     * @Given I create a unique moodle user with username :username
     * @param $username
     */
    public function i_create_a_unique_moodle_user($username) {
        $generator = testing_util::get_data_generator();
        $generator->create_user(array(
            'email' => uniqid($username, true) . '@example.com',
            'username' => $username,
            'password' => $username,
            'firstname' => $username,
            'lastname' => $username
        ));
    }

}