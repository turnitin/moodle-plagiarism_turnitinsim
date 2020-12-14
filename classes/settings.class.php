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
 * Plugin module settings form for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin module settings form for plagiarism_turnitinsim component.
 */
class plagiarism_turnitinsim_settings {

    /**
     * @var plagiarism_turnitinsim_request|null Request object.
     */
    public $tsrequest;

    /**
     * plagiarism_turnitinsim_settings constructor.
     *
     * @param plagiarism_turnitinsim_request|null $tsrequest Request object.
     * @throws dml_exception
     */
    public function __construct(plagiarism_turnitinsim_request $tsrequest = null ) {
        $this->tsrequest = ($tsrequest) ? $tsrequest : new plagiarism_turnitinsim_request();
    }

    /**
     * Add Turnitin settings to module form.
     *
     * @param object $mform The Moodle form object.
     * @param boolean $canconfigureplugin If this user has permission to configure the plugin.
     * @param string $context The context, eg module or course.
     * @param string $modulename The name of the module.
     * @return mixed Moodle form with settings.
     * @throws coding_exception
     */
    public function add_settings_to_module($mform, $canconfigureplugin = false, $context = 'module', $modulename = '') {
        global $PAGE;

        if ($context == 'module') {
            $mform->addElement('header', 'plugin_header', get_string('turnitinpluginsettings', 'plagiarism_turnitinsim'));
        }

        // Require JS modules.
        if ($modulename == 'mod_assign') {
            $PAGE->requires->js_call_amd('plagiarism_turnitinsim/set_report_generation', 'setReportGeneration');
        }

        $mform->addElement('checkbox', 'turnitinenabled', get_string('turnitinpluginenabled', 'plagiarism_turnitinsim'));

        // TODO: Change create elements to loop & add further exclude options depending on features-enabled (INT-11451).

        // Exclude Options.
        // Exclude Bibliography.
        $label = get_string('excludebiblio', 'plagiarism_turnitinsim');
        $excludes[] = $mform->createElement('checkbox', 'excludebiblio', null, $label);

        // Exclude Quotes.
        $label = get_string('excludequotes', 'plagiarism_turnitinsim');
        $excludes[] = $mform->createElement('checkbox', 'excludequotes', null, $label);

        // Group exclude options together.
        $mform->addGroup($excludes, 'excludeoptions', get_string('excludeoptions', 'plagiarism_turnitinsim'), '<br />');
        $mform->addHelpButton('excludeoptions', 'excludeoptions', 'plagiarism_turnitinsim');
        $mform->disabledIf('excludeoptions', 'turnitinenabled', 'notchecked');

        // Indexing options.
        // Add to Index.
        $label = get_string('addtoindex', 'plagiarism_turnitinsim');
        $indexes[] = $mform->createElement('checkbox', 'addtoindex', null, $label);

        // Group index options together.
        $mform->addGroup($indexes, 'indexoptions', get_string('indexoptions', 'plagiarism_turnitinsim'), '<br />');
        $mform->addHelpButton('indexoptions', 'indexoptions', 'plagiarism_turnitinsim');
        $mform->disabledIf('indexoptions', 'turnitinenabled', 'notchecked');

        // If this is an assignment we will offer report generation options. Otherwise default to immediate.
        if ($modulename == 'mod_assign' || $context == 'defaults') {

            // Immediate.
            $label = get_string('reportgen0', 'plagiarism_turnitinsim');
            $reportgen[] = $mform->createElement(
                'radio',
                'reportgeneration',
                null,
                $label,
                TURNITINSIM_REPORT_GEN_IMMEDIATE,
                array('class' => 'turnitinsim_settings_radio')
            );

            // Immediate and Due Date.
            $label = get_string('reportgen1', 'plagiarism_turnitinsim');
            $reportgen[] = $mform->createElement(
                'radio',
                'reportgeneration',
                null,
                $label,
                TURNITINSIM_REPORT_GEN_IMMEDIATE_AND_DUEDATE,
                array('class' => 'turnitinsim_settings_radio')
            );

            // Due Date.
            $label = get_string('reportgen2', 'plagiarism_turnitinsim');
            $reportgen[] = $mform->createElement(
                'radio',
                'reportgeneration',
                null,
                $label,
                TURNITINSIM_REPORT_GEN_DUEDATE,
                array('class' => 'turnitinsim_settings_radio')
            );

            // Group Report Gen options together.
            $mform->addGroup($reportgen, 'reportgenoptions', get_string('reportgenoptions', 'plagiarism_turnitinsim'), '<br />');
            $mform->addHelpButton('reportgenoptions', 'reportgenoptions', 'plagiarism_turnitinsim');
            $mform->disabledIf('reportgenoptions', 'duedate[enabled]', 'notchecked');
            $mform->disabledIf('reportgenoptions', 'turnitinenabled', 'notchecked');
        } else {
            $mform->addElement('hidden', 'reportgeneration', TURNITINSIM_REPORT_GEN_IMMEDIATE);
            $mform->setType('reportgeneration', PARAM_RAW);
        }

        // Access options.
        // Students view.
        $label = get_string('accessstudents', 'plagiarism_turnitinsim');
        $access[] = $mform->createElement('checkbox', 'accessstudents', null, $label);

        // Group index options together.
        $mform->addGroup($access, 'accessoptions', get_string('accessoptions', 'plagiarism_turnitinsim'), '<br />');
        $mform->addHelpButton('accessoptions', 'accessoptions', 'plagiarism_turnitinsim');
        $mform->disabledIf('accessoptions', 'turnitinenabled', 'notchecked');

        // Send submission drafts to Turnitin setting.
        if ($mform->elementExists('submissiondrafts') || $context != 'module') {
            $mform->addElement('checkbox', 'queuedrafts', get_string('queuedrafts', 'plagiarism_turnitinsim'));
            $mform->addHelpButton('queuedrafts', 'queuedrafts', 'plagiarism_turnitinsim');
            $mform->disabledIf('queuedrafts', 'submissiondrafts', 'eq', 0);
            $mform->disabledIf('queuedrafts', 'turnitinenabled', 'notchecked');
        }

        // Show link to guides.
        $link = html_writer::link(
            get_string('help_link', 'plagiarism_turnitinsim'),
            get_string('settingslearnmore', 'plagiarism_turnitinsim'),
            array('target' => '_blank')
        );
        $mform->addElement('html', html_writer::tag('div', $link));

        if (!$canconfigureplugin) {
            $mform->freeze('turnitinenabled');
            $mform->freeze('excludeoptions');
            $mform->freeze('indexoptions');
            $mform->freeze('reportgenoptions');
            $mform->freeze('accessoptions');
            $mform->freeze('queuedrafts');
        }

        return $mform;
    }

    /**
     * Save Turnitin settings for a module.
     *
     * @param object $data The settings data to add.
     * @throws dml_exception
     */
    public function save_module_settings($data) {
        global $DB;

        $settings = new stdClass();
        $settings->cm = (int)$data->coursemodule;
        $settings->turnitinenabled = (!empty($data->turnitinenabled)) ? (int)$data->turnitinenabled : 0;
        $settings->reportgeneration = (!empty($data->reportgenoptions['reportgeneration'])) ?
            (int)$data->reportgenoptions['reportgeneration'] : TURNITINSIM_REPORT_GEN_IMMEDIATE;
        $settings->queuedrafts = (!empty($data->queuedrafts)) ? (int)$data->queuedrafts : 0;
        $settings->addtoindex = (!empty($data->indexoptions['addtoindex'])) ? (int)$data->indexoptions['addtoindex'] : 0;
        $settings->excludebiblio = (!empty($data->excludeoptions['excludebiblio'])) ?
            (int)$data->excludeoptions['excludebiblio'] : 0;
        $settings->excludequotes = (!empty($data->excludeoptions['excludequotes'])) ?
            (int)$data->excludeoptions['excludequotes'] : 0;
        $settings->accessstudents = (!empty($data->accessoptions['accessstudents'])) ?
            (int)$data->accessoptions['accessstudents'] : 0;

        if ($modsettings = $DB->get_record('plagiarism_turnitinsim_mod', array('cm' => $settings->cm))) {
            $settings->id = $modsettings->id;
            $DB->update_record('plagiarism_turnitinsim_mod', $settings);
        } else {
            // Inserts only happen on activity creation, so if turnitinenabled is false - don't insert.
            if ($settings->turnitinenabled) {
                $DB->insert_record('plagiarism_turnitinsim_mod', $settings);
            }
        }
    }

    /**
     * Get the enabled features on the account from Turnitin.
     */
    public function get_enabled_features() {
        $responsedata = new stdClass();

        // Make request to get the enabled features on the account.
        try {
            $endpoint = TURNITINSIM_ENDPOINT_GET_FEATURES_ENABLED;
            $response = $this->tsrequest->send_request($endpoint, json_encode(array()), 'GET');
            $responsedata = json_decode($response);

            // Latest version retrieved.
            if ($responsedata->httpstatus == TURNITINSIM_HTTP_OK) {
                mtrace(get_string('taskoutputenabledfeaturesretrieved', 'plagiarism_turnitinsim'));
                return $responsedata;
            }

            mtrace(get_string('taskoutputenabledfeaturesnotretrieved', 'plagiarism_turnitinsim'));
            return $responsedata;

        } catch (Exception $e) {
            $this->tsrequest->handle_exception($e, 'taskoutputenabledfeaturesretrievalfailure');
            return $responsedata;
        }
    }
}
