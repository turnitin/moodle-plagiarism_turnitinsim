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

// namespace behat;
namespace behat1;

use behat_base;
use testing_util;

// Uncomment line below for screenshots upon behat test failure for debug aid
// use Behat\Behat\Hook\Scope\AfterStepScope;
require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');
require_once(__DIR__ . '/../../vendor/autoload.php');


class behat_plagiarism_turnitinsim extends behat_base
{

    // Uncomment chunk below for screenshots upon behat test failure for debug aid

//    /**
//     * @AfterStep
//     */
//    public function takeScreenshotAfterFailedStep(AfterStepScope $scope) {
//        if ($scope->getTestResult()->getResultCode() == 99) {
//            $this->takeScreenshot();
//        }
//    }
//
//    private function takeScreenshot() {
//        $screenshot = $this->getSession()->getDriver()->getScreenshot();
//        $path = __DIR__ . '/screenshots/' . date('d-m-y') . '-' . uniqid() . '.png';
//
//        if (!is_dir(dirname($path))) {
//            mkdir(dirname($path), 0777, true);
//        }
//
//        file_put_contents($path, $screenshot);
//        print 'Screenshot at: ' . $path . PHP_EOL;
//    }
    /**
     * @var string used by {@link switch_to_window()} and
     * {@link switch_to_the_main_window()} to work-around a Chrome browser issue.
     */
    const MAIN_WINDOW_NAME = '__moodle_behat_main_window_name';

    /**
     * @Given I configure Turnitin Integrity credentials
     */
    public function i_configure_turnitinsim_credentials()
    {
        $apikey = getenv('TII_APIKEY');
        $apiurl = getenv('TII_APIURL');

        // $this->getSession()->getPage()->find("css", "//input[@type='password' and @name='turnitinapikey']")->setValue($apikey);
        // $this->getSession()->getPage()->find("css", "//input[@type='text' and @name='turnitinapiurl']")->setValue($apiurl);

        $this->getSession()->getPage()->find("css", "#id_turnitinapikey")->setValue($apikey);
        $this->getSession()->getPage()->find("css", "#id_turnitinapiurl")->setValue($apiurl);

        // $this->getSession()->getPage()->find("css", "#id_plagiarism_turnitin_accountid")->setValue($account);

        // $this->getSession()->getPage()->find('css', '[title="Edit password"]')->click();
        // $this->getSession()->getPage()->find("css", "#id_plagiarism_turnitin_secretkey")->setValue($secret);
    }

    /**
     * @Given I create a unique moodle user with username :username
     * @param $username
     */
    public function i_create_a_unique_moodle_user($username)
    {
        $generator = testing_util::get_data_generator();
        $generator->create_user(array(
            'email' => uniqid($username, true) . '@example.com',
            'username' => $username,
            'password' => $username,
            'firstname' => $username,
            'lastname' => $username
        ));
    }

    /**
     * Switches to the specified window. Useful when interacting with popup windows.
     *
     * @Given /^I switch to viewer window$/
     *
     */
    public function switch_to_viewer()
    {

        $this->getSession()->executeScript(
            'if (window.name == "") window.name = "' . self::MAIN_WINDOW_NAME . '"');
        $windowNames = $this->getSession()->getWindowNames();
        if (count($windowNames) > 1) {
            $this->getSession()->switchToWindow($windowNames[1]);
        }
    }

    /**
     * Switches to the main Moodle window. Useful when you finish interacting with popup windows.
     *
     * @Given /^I switch back to the main window$/
     */
    public function switch_back_to_the_main_window()
    {
        $this->getSession()->switchToWindow(self::MAIN_WINDOW_NAME);
    }

}