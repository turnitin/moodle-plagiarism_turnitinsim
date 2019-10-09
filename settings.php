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
 * Pluging settings for plagiarism_turnitincheck component
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require libs.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');

// Require classes.
require_once(__DIR__ . '/classes/forms/tcsetupform.class.php');

global $PAGE;

// Require the JS module to test the connection.
$PAGE->requires->string_for_js('connecttestsuccess', 'plagiarism_turnitincheck');
$PAGE->requires->string_for_js('connecttestfailed', 'plagiarism_turnitincheck');
$PAGE->requires->string_for_js('connecttest', 'plagiarism_turnitincheck');
$PAGE->requires->js_call_amd('plagiarism_turnitincheck/connection_test', 'connectionTest');

// Restrict access to admins only.
require_login();
admin_externalpage_setup('plagiarismturnitincheck');
$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

$output = $OUTPUT->header();

$tcsetupform = new tcsetupform('');
// Save posted form data.
if (($data = $tcsetupform->get_data()) && confirm_sesskey()) {
    $tcsetupform->save($data);
    $output .= $OUTPUT->notification(get_string('savesuccess', 'plagiarism_turnitincheck'), 'notifysuccess');
}

// Add tabs to output.
$currenttab = 'setup';

ob_start();
require('settings_tabs.php');
$output .= ob_get_contents();
ob_end_clean();

// Display Turnitin enabled features.
$output .= $tcsetupform->display_features();

// Display plugin settings form with saved configuration.
$pluginconfig = get_config('plagiarism');

// Some settings are grouped in an array format so we need to set these manually first.
$pluginconfig->permissionoptions['turnitinviewerviewfullsource'] = (!empty($pluginconfig->turnitinviewerviewfullsource)) ? 1 : 0;
$pluginconfig->permissionoptions['turnitinviewermatchsubinfo'] = (!empty($pluginconfig->turnitinviewermatchsubinfo)) ? 1 : 0;

$tcsetupform->set_data($pluginconfig);
$output .= $tcsetupform->display();

echo $output;

echo $OUTPUT->footer();
