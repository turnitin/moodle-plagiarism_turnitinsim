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
 * Pluging settings for plagiarism_turnitinsim component
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require libs.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');

// Require classes.
require_once(__DIR__ . '/classes/setup_form.class.php');

global $PAGE;

// Require the JS module to test the connection.
$PAGE->requires->string_for_js('connecttestsuccess', 'plagiarism_turnitinsim');
$PAGE->requires->string_for_js('connecttestfailed', 'plagiarism_turnitinsim');
$PAGE->requires->string_for_js('connecttest', 'plagiarism_turnitinsim');
$PAGE->requires->js_call_amd('plagiarism_turnitinsim/connection_test', 'connectionTest');

// Restrict access to admins only.
require_login();
admin_externalpage_setup('plagiarismturnitinsim');
$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

$output = $OUTPUT->header();

$tssetupform = new plagiarism_turnitinsim_setup_form('');
// Save posted form data.
if (($data = $tssetupform->get_data()) && confirm_sesskey()) {
    $tssetupform->save($data);
    $output .= $OUTPUT->notification(get_string('savesuccess', 'plagiarism_turnitinsim'), 'notifysuccess');
}

// Add tabs to output.
$currenttab = 'setup';

ob_start();
require('settings_tabs.php');
$output .= ob_get_contents();
ob_end_clean();

// Display Turnitin enabled features.
$output .= $tssetupform->display_features();

// Display plugin settings form with saved configuration.
$pluginconfig = get_config('plagiarism_turnitinsim');

// Some settings are grouped in an array format so we need to set these manually first.
$pluginconfig->permissionoptions['turnitinviewerviewfullsource'] = (!empty($pluginconfig->turnitinviewerviewfullsource)) ? 1 : 0;
$pluginconfig->permissionoptions['turnitinviewermatchsubinfo'] = (!empty($pluginconfig->turnitinviewermatchsubinfo)) ? 1 : 0;
$pluginconfig->permissionoptions['turnitinviewersavechanges'] = (!empty($pluginconfig->turnitinviewersavechanges)) ? 1 : 0;

$tssetupform->set_data($pluginconfig);
$output .= $tssetupform->display();

echo $output;

echo $OUTPUT->footer();
