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
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require libs.
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->libdir.'/adminlib.php');

// Require classes.
require_once(__DIR__ . '/classes/setup_form.class.php');

// Restrict access to admins only.
require_login();
admin_externalpage_setup('plagiarismturnitinsim');
$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

// Get file date to view if passed in, otherwise show list of logs.
$filedate = optional_param('filedate', null, PARAM_ALPHANUMEXT);

$output = $OUTPUT->header();

// Add tabs to output.
$currenttab = 'logs';

ob_start();
require('settings_tabs.php');
$output .= ob_get_contents();
ob_end_clean();

$logsdir = $CFG->tempdir . "/turnitinsim/logs/";
$savefile = 'apilog_'.$filedate.'.txt';

// If a file date has been passed in then show that file.
if (!is_null($filedate)) {
    header("Content-type: plain/text; charset=UTF-8");
    send_file( $logsdir.$savefile, $savefile, false );
} else {

    // Show list of API logs with links to view.
    if (file_exists($logsdir) && $readdir = opendir($logsdir)) {
        $i = 0;
        while (false !== ($entry = readdir($readdir))) {
            if (substr_count($entry, 'apilog') > 0) {
                $i++;
                $split = preg_split("/_/", $entry);
                $date = array_pop($split);
                $date = str_replace('.txt', '', $date);

                $link = html_writer::link($CFG->wwwroot.'/plagiarism/turnitinsim/logs.php?filedate='.$date,
                    get_string('viewapilog', 'plagiarism_turnitinsim', userdate(strtotime($date), '%d/%m/%Y')),
                    array('target' => '_blank'));
                $output .= html_writer::tag('p', $link);
            }
        }
        if ($i == 0) {
            $output .= get_string("nologsfound");
        }
    } else {
        $output .= get_string("nologsfound");
    }

}

echo $output;

echo $OUTPUT->footer();