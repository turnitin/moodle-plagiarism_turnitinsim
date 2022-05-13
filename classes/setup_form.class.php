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
 * Plugin setup form for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/task.class.php');

/**
 * Plugin setup form for plagiarism_turnitinsim component.
 */
class plagiarism_turnitinsim_setup_form extends moodleform {

    /**
     * Define the form.
     *
     * @throws coding_exception
     */
    public function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'turnitinconfig', get_string('turnitinconfig', 'plagiarism_turnitinsim'));

        // Loop through all modules that support Plagiarism.
        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modpath) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $mform->addElement('advcheckbox',
                    'turnitinmodenabled'.$mod,
                    get_string('turnitinmodenabled', 'plagiarism_turnitinsim', ucfirst($mod)),
                    '',
                    null,
                    array(0, 1)
                );
            }
        }

        // API URL.
        $mform->addElement('text', 'turnitinapiurl', get_string('turnitinapiurl', 'plagiarism_turnitinsim'));
        $mform->setType('turnitinapiurl', PARAM_URL);

        // API Key.
        $mform->addElement('password', 'turnitinapikey', get_string('turnitinapikey', 'plagiarism_turnitinsim'));
        $mform->setType('turnitinapikey', PARAM_TEXT);

        // Test Connection.
        $mform->addElement('button', 'connection_test', get_string("connecttest", 'plagiarism_turnitinsim'));

        // Toggle Logging.
        $mform->addElement(
            'advcheckbox',
            'turnitinenablelogging',
            get_string('turnitinenablelogging', 'plagiarism_turnitinsim'),
            '',
            null,
            array(0, 1)
        );

        // Toggle Remote Logging.
        $mform->addElement(
            'advcheckbox',
            'turnitinenableremotelogging',
            get_string('turnitinenableremotelogging', 'plagiarism_turnitinsim'),
            '',
            null,
            array(0, 1)
        );

        // Add tool tip.
        $mform->addHelpButton('turnitinenableremotelogging', 'turnitinenableremotelogging', 'plagiarism_turnitinsim');
        $mform->setDefault('turnitinenableremotelogging', 1);

        // Toggle Student Data Privacy.
        $mform->addElement(
            'advcheckbox',
            'turnitinhideidentity',
            get_string('turnitinhideidentity', 'plagiarism_turnitinsim'),
            '',
            null,
            array(0, 1)
        );

        // Toggle Viewer Permission whether instructors can view full source of matches.
        $label = get_string('turnitinviewerviewfullsource', 'plagiarism_turnitinsim');
        $permissions[] = $mform->createElement('checkbox', 'turnitinviewerviewfullsource', null, $label);

        // Toggle Viewer Permission whether instructors can view match submission info.
        $label = get_string('turnitinviewermatchsubinfo', 'plagiarism_turnitinsim');
        $permissions[] = $mform->createElement('checkbox', 'turnitinviewermatchsubinfo', null, $label);

        // Toggle Viewer Permissions whether Save Viewer changes is on.
        $label = get_string('turnitinviewersavechanges', 'plagiarism_turnitinsim');
        $permissions[] = $mform->createElement('checkbox', 'turnitinviewersavechanges', null, $label);

        // Show FERPA statemnet.
        $ferpastatement = html_writer::tag(
            'div',
            get_string('viewerpermissionferpa', 'plagiarism_turnitinsim'),
            array('class' => 'turnitinsim_ferpa')
        );

        $mform->addElement('html', $ferpastatement);

        // Group viewer permissions together.
        $mform->addGroup(
            $permissions,
            'permissionoptions',
            get_string('viewerpermissionoptions', 'plagiarism_turnitinsim'),
            '<br />'
        );

        $this->add_action_buttons(false);
    }

    /**
     * Display the form, saving the contents of the output buffer overriding Moodle's
     * display function that prints to screen when called
     *
     * @return false|string The form as an object to print to screen at our convenience.
     */
    public function display() {
        ob_start();
        parent::display();
        $form = ob_get_contents();
        ob_end_clean();

        return $form;
    }

    /**
     * Save the plugin config data.
     *
     * @param object $data The data to save.
     * @throws coding_exception
     */
    public function save($data) {
        $useplugin = 0;

        // Save whether the plugin is enabled for individual modules.
        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modpath) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $property = "turnitinmodenabled" . $mod;
                ${ "turnitinmodenabled" . "$mod" } = (!empty($data->$property)) ? $data->$property : 0;
                if (${ "turnitinmodenabled" . "$mod" }) {
                    $useplugin = 1;
                }
            }
        }

        $validurlregexwithapi = '/.+\.(turnitin\.com|turnitinuk\.com|turnitin\.dev|turnitin\.org|tii-sandbox\.com)\/api$/m';

        if ((!empty($data->turnitinapiurl))) {
            // Strip any trailing / chars from api url.
            $apiurl = rtrim($data->turnitinapiurl, '/');
            if (preg_match($validurlregexwithapi, $apiurl)) {
                $logger = new plagiarism_turnitinsim_logger();
                $logger->info('Stripping /api from Turnitin URL on save: ', array($apiurl));
                $turnitinapiurl = str_replace("/api", '', $apiurl);
            } else {
                $turnitinapiurl = $apiurl;
            }
        } else {
            $turnitinapiurl = '';
        }

        $turnitinapikey = (!empty($data->turnitinapikey)) ? $data->turnitinapikey : '';
        $turnitinenablelogging = (!empty($data->turnitinenablelogging)) ? $data->turnitinenablelogging : 0;
        $turnitinenableremotelogging = (!empty($data->turnitinenableremotelogging)) ? $data->turnitinenableremotelogging : 0;
        $turnitinhideidentity = (!empty($data->turnitinhideidentity)) ? $data->turnitinhideidentity : 0;
        $turnitinviewerviewfullsource = (!empty($data->permissionoptions['turnitinviewerviewfullsource'])) ? 1 : 0;
        $turnitinviewermatchsubinfo = (!empty($data->permissionoptions['turnitinviewermatchsubinfo'])) ? 1 : 0;
        $turnitinviewersavechanges = (!empty($data->permissionoptions['turnitinviewersavechanges'])) ? 1 : 0;

        plagiarism_plugin_turnitinsim::enable_plugin($useplugin);

        // Loop through all modules that support Plagiarism.
        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modpath) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                set_config('turnitinmodenabled'.$mod, ${ "turnitinmodenabled" . "$mod" }, 'plagiarism_turnitinsim');
            }
        }

        set_config('turnitinapiurl', $turnitinapiurl, 'plagiarism_turnitinsim');
        set_config('turnitinapikey', $turnitinapikey, 'plagiarism_turnitinsim');
        set_config('turnitinenablelogging', $turnitinenablelogging, 'plagiarism_turnitinsim');
        set_config('turnitinenableremotelogging', $turnitinenableremotelogging, 'plagiarism_turnitinsim');
        set_config('turnitinhideidentity', $turnitinhideidentity, 'plagiarism_turnitinsim');
        set_config('turnitinviewerviewfullsource', $turnitinviewerviewfullsource, 'plagiarism_turnitinsim');
        set_config('turnitinviewermatchsubinfo', $turnitinviewermatchsubinfo, 'plagiarism_turnitinsim');
        set_config('turnitinviewersavechanges', $turnitinviewersavechanges, 'plagiarism_turnitinsim');

        if ($turnitinapiurl) {
            $tsrequest = new plagiarism_turnitinsim_request();
            set_config('turnitinroutingurl', $tsrequest->get_routing_url(true), 'plagiarism_turnitinsim');
        } else {
            set_config('turnitinroutingurl', '', 'plagiarism_turnitinsim');
        }
    }

    /**
     * Display the features enabled in Turnitin.
     */
    public function display_features() {
        global $CFG, $OUTPUT;

        // Only display features if plugin is configured.
        $turnitinapiurl = get_config('plagiarism_turnitinsim', 'turnitinapiurl');
        $turnitinapikey = get_config('plagiarism_turnitinsim', 'turnitinapikey');
        if (empty($turnitinapiurl) && empty($turnitinapikey)) {
            return;
        }

        // Check that we have features enabled stored locally.
        $features = get_config('plagiarism_turnitinsim', 'turnitin_features_enabled');

        // If we don't then retrieve them and overwrite mtrace so it doesn't output to screen.
        $CFG->mtrace_wrapper = 'plagiarism_turnitinsim_mtrace';
        if (empty($features) || $features == "{}") {
            try {
                $tstask = new plagiarism_turnitinsim_task();
                $tstask->check_enabled_features();
            } catch (Exception $e) {
                $logger = new plagiarism_turnitinsim_logger();
                $logger->error(get_string('errorenabledfeatures', 'plagiarism_turnitinsim'));
                // Gracefully handle error - do nothing.
            }
            $features = get_config('plagiarism_turnitinsim', 'turnitin_features_enabled');
        }

        // Display some of the features available from Turnitin and whether they are enabled.
        $enabledfeatures = html_writer::tag('h4', get_string('turnitinfeatures::header', 'plagiarism_turnitinsim'));
        $features = json_decode($features, true);

        // Display Search Repositories.
        $repolist = html_writer::tag('dt', get_string('turnitinfeatures::repositories', 'plagiarism_turnitinsim'));

        if (!empty($features) && $features != "{}") {
            foreach ($features['similarity']['generation_settings']['search_repositories'] as $repo) {
                $repolist .= html_writer::tag('dd', ucwords(strtolower(str_replace('_', ' ', $repo))));
            }
            $enabledfeatures .= html_writer::tag('dl', $repolist, array('class' => 'turnitinsim_featurelist'));

            // Display View Options.
            $settinglist = html_writer::tag('dt', get_string('turnitinfeatures::viewoptions', 'plagiarism_turnitinsim'));
            foreach ($features['similarity']['view_settings'] as $setting => $enabled) {
                $icon = ($enabled) ? 'option-yes' : 'option-no';
                $icon = $OUTPUT->pix_icon($icon, '', 'plagiarism_turnitinsim', array('class' => 'turnitinsim_optionicon'));
                $settinglist .= html_writer::tag('dd', $icon . ucwords(str_replace('_', ' ', $setting)));
            }
            $enabledfeatures .= html_writer::tag('dl', $settinglist, array('class' => 'turnitinsim_featurelist'));

            $eulastring = (!(bool)$features['tenant']['require_eula']) ? 'eulanotrequired' : 'eularequired';
            $enabledfeatures .= html_writer::tag(
                'p',
                get_string('turnitinfeatures::'.$eulastring, 'plagiarism_turnitinsim'),
                array('class' => 'bold')
            );
        }

        $enabledfeatures .= html_writer::tag('p', get_string('turnitinfeatures::moreinfo', 'plagiarism_turnitinsim'));

        return html_writer::tag('div', $enabledfeatures, array('class' => 'turnitinsim_features'));
    }
}
