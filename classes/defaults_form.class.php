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
 * Default setting form for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir."/formslib.php");
require_once($CFG->dirroot.'/plagiarism/turnitinsim/classes/settings.class.php');

/**
 * Default setting form for plagiarism_turnitinsim component.
 */
class plagiarism_turnitinsim_defaults_form extends moodleform {

    /**
     * Form definition required when building a Moodle form.
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    public function definition () {
        $mform =& $this->_form;

        $plugin = new plagiarism_plugin_turnitinsim();
        $plugin->get_form_elements_module($mform, context_system::instance());

        $this->add_action_buttons(true);
    }

    /**
     * Display the form, saving the contents of the output buffer overriding Moodle's
     * display function that prints to screen when called
     *
     * @return string The form as an object to print to screen at our convenience
     */
    public function display() {
        ob_start();
        parent::display();
        $form = ob_get_contents();
        ob_end_clean();

        return $form;
    }

    /**
     * Save the default settings
     */

    /**
     * Save the default settings
     *
     * @param object $data The form data we were passed and need to save.
     * @throws dml_exception
     */
    public function save($data) {
        $settings = new plagiarism_turnitinsim_settings();
        $data->coursemodule = 0;
        $settings->save_module_settings($data);
    }
}