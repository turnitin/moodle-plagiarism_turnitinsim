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
 * Settings navigation tabs for plagiarism_turnitincheck
 *
 * @package    plagiarism_turnitincheck
 * @author     John McGettrick http://www.turnitin.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (empty($currenttab)) {
    $currenttab = 'setup';
}

$tabs = array();
$tabs[] = new tabobject('setup',
    new moodle_url('/plagiarism/turnitincheck/settings.php'),
    get_string('pluginsetup', 'plagiarism_turnitincheck'));

$tabs[] = new tabobject('defaults',
    new moodle_url('/plagiarism/turnitincheck/defaults.php'),
    get_string('defaultsettings', 'plagiarism_turnitincheck'));

$tabs[] = new tabobject('logs',
    new moodle_url('/plagiarism/turnitincheck/logs.php'),
    get_string('viewlogs', 'plagiarism_turnitincheck'));

$tabs[] = new tabobject('dbexport',
    new moodle_url('/plagiarism/turnitincheck/dbexport.php'),
    get_string('dbexport', 'plagiarism_turnitincheck'));

echo html_writer::tag('div', $OUTPUT->tabtree($tabs, $currenttab), array('class' => 'turnitincheck-settings-tabs'));