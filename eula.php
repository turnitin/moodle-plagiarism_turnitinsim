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
 * Page to allow users to accept the EULA
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/classes/tcrequest.class.php');

$cmd = optional_param('cmd', 'eularedirect', PARAM_ALPHAEXT);

require_login();

switch ($cmd) {
    case "eularedirect":

        if (!confirm_sesskey()) {
            throw new moodle_exception('invalidsesskey', 'error');
        }

        // Get EULA Link.
        $tcrequest = new tcrequest();
        $lang = $tcrequest->get_language();
        $eulaurl = get_config('plagiarism', 'turnitin_eula_url')."?lang=".$lang->localecode;

        header('Location: '.$eulaurl);
        exit;
        break;

    case "displayeula":

        // Set up $PAGE for displaying.
        $PAGE->set_context(context_system::instance());
        $PAGE->set_heading(get_string('eulaheader', 'plagiarism_turnitincheck'));
        $PAGE->set_pagelayout('mypublic');
        $PAGE->set_pagetype('user-profile');
        $PAGE->set_title(get_string('eulaheader', 'plagiarism_turnitincheck'));
        $PAGE->set_url($_SERVER['REQUEST_URI']);

        echo $OUTPUT->header();

        // Display EULA link.
        $plagiarismpluginturnitincheck = new plagiarismpluginturnitincheck();
        echo $plagiarismpluginturnitincheck->print_disclosure(-1);

        echo $OUTPUT->footer();

        exit;
        break;
}
