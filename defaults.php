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
 * View logs page for plagiarism_turnitincheck component
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require libs.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/forms/tcdefaultsform.class.php');

// Restrict access to admins only.
require_login();
admin_externalpage_setup('plagiarismturnitincheck');
$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

$output = $OUTPUT->header();

$defaultsform = new tcdefaultsform();
// Save posted form data.
if (($data = $defaultsform->get_data()) && confirm_sesskey()) {
    $defaultsform->save($data);
    $output .= $OUTPUT->notification(get_string('savesuccess', 'plagiarism_turnitincheck'), 'notifysuccess');
}

// Add tabs to output.
$currenttab = 'defaults';

ob_start();
require('settings_tabs.php');
$output .= ob_get_contents();
ob_end_clean();

// Output form.
$plugin = new plagiarism_plugin_turnitincheck();
$defaults = $plugin->get_settings(0);

$defaultsform->set_data($defaults);
$output .= $defaultsform->display();

echo $output;

echo $OUTPUT->footer();