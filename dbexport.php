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
 * View logs page for plagiarism_turnitinsim component
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require libs.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once( __DIR__ . '/utilities/handle_deprecation.php' );

// Restrict access to admins only.
require_login();
admin_externalpage_setup('plagiarismturnitinsim');
$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

// Get table and dataformat if passed in, otherwise show list of logs.
$table = optional_param('table', null, PARAM_ALPHANUMEXT);
$dataformat = optional_param('dataformat', null, PARAM_ALPHANUMEXT);

$output = $OUTPUT->header();

// Add tabs to output.
$currenttab = 'dbexport';

ob_start();
require('settings_tabs.php');
$output .= ob_get_contents();
ob_end_clean();

$exportfile = "export_".$table."_".date('Y-m-d_His');

// Use Moodle's dataformatting functions to display a form to download output in different formats.
$tables = array(
    'plagiarism_turnitinsim_mod',
    'plagiarism_turnitinsim_users',
    'plagiarism_turnitinsim_sub',
    'plagiarism_turnitinsim_group'
);

// If a table has been passed in then export that table data.
if (!is_null($table)) {
    if (in_array($table, $tables)) {

        raise_memory_limit(MEMORY_EXTRA);

        $data = $DB->get_records($table, null, 'id ASC');

        // Use Moodle's dataformatting functions to output the data in the desired format.
        \core\dataformat::download_data($exportfile, $dataformat, array_keys($DB->get_columns($table)), $data);

        exit;

    } else {
        $output .= html_writer::tag('div', get_string('invalidtablename', 'plagiarism_turnitinsim', $table));
    }

} else {

    $downloadoptions = "";
    foreach ($tables as $table) {
        $downloadoptions .= $OUTPUT->download_dataformat_selector(
            get_string('dbexporttable', 'plagiarism_turnitinsim', $table),
            'dbexport.php',
            'dataformat',
            array('table' => $table)
        );
    }

    $output .= html_writer::tag('div', $downloadoptions, array('class' => 'turnitinsim_setup_download_links'));
}

echo $output;

echo $OUTPUT->footer();