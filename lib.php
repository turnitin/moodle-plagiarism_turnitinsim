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
 * Main library for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

// Get global class.
require_once( $CFG->dirroot . '/plagiarism/lib.php' );
require_once( __DIR__ . '/utilities/constants.php' );
require_once( __DIR__ . '/classes/settings.class.php' );
require_once( __DIR__ . '/classes/submission.class.php' );
require_once( __DIR__ . '/classes/user.class.php' );
require_once( __DIR__ . '/classes/group.class.php' );
require_once( __DIR__ . '/classes/request.class.php' );
require_once( __DIR__ . '/classes/logger.class.php' );
require_once( __DIR__ . '/classes/eula.class.php' );
require_once( __DIR__ . '/classes/task.class.php' );

/**
 * Main library for plagiarism_turnitinsim component.
 */
class plagiarism_plugin_turnitinsim extends plagiarism_plugin {

    /**
     * Get the fields to be used in the form to configure each module's Turnitin settings.
     *
     * TODO: This code needs to be moved for 4.3 as the method will be completely removed from core.
     * See https://tracker.moodle.org/browse/MDL-67526
     *
     * @param object $mform - Moodle form
     * @param object $context - current context
     * @param string $modulename - Name of the module
     * @return void of settings fields.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {
        // This is a bit of a hack and untidy way to ensure the form elements aren't displayed twice.
        // TODO: Remove once this method is removed.

        $canconfigureplugin = false;

        static $hassettings;
        if ($hassettings) {
            return;
        }

        $cmid = optional_param('update', 0, PARAM_INT);

        $location = ($context == context_system::instance()) ? 'defaults' : 'module';

        // Get whether plugin is enabled for this module.
        $moduletiienabled = empty($modulename) ? "0" : get_config('plagiarism_turnitinsim',
            'turnitinmodenabled'.substr($modulename, 4));

        if ($location === 'module') {
            // Exit if Turnitin is not being used for this activity type and location is not default.
            if (empty($moduletiienabled)) {
                return;
            }

            // Course ID is only passed in on new module - if updating then get it from module id.
            $courseid = optional_param('course', 0, PARAM_INT);
            $id = optional_param('id', 0, PARAM_INT);

            if (empty($courseid)) {
                $checkcourse = (!empty($cmid)) ? $cmid : $id;
                $course = get_coursemodule_from_id('', $checkcourse);

                // If it is still empty, return to avoid an error.
                if (empty($course)) {
                    return;
                }

                $courseid = $course->course;
            }

            // Exit if this user does not have permissions to configure the plugin.
            if (has_capability('plagiarism/turnitinsim:enable', context_course::instance($courseid))) {
                $canconfigureplugin = true;
            }
        } else {
            $canconfigureplugin = true;
        }

        $form = new plagiarism_turnitinsim_settings();
        $form->add_settings_to_module($mform, $canconfigureplugin, $location, $modulename);

        if ($modsettings = $this->get_settings( $cmid )) {

            // Set the default value for each option as the value we have stored.
            foreach ($modsettings as $element => $value) {

                // If the element name starts with exclude it needs to be placed in the exclude options group.
                if ( substr($element, 0, 7) == 'exclude' ) {
                    $mform->setDefault('excludeoptions['.$element.']', $value);
                }

                // If the element name starts with reportgen it needs to be placed in the report gen options group.
                if ( substr($element, 0, 9) == 'reportgen' ) {
                    $mform->setDefault('reportgenoptions['.$element.']', $value);
                }

                // If the element is addtoindex it needs to be placed in the index options group.
                if ($element == 'addtoindex') {
                    $mform->setDefault('indexoptions['.$element.']', $value);
                }

                // If the element is accessstudents it needs to be placed in the access options group.
                if ($element == 'accessstudents') {
                    $mform->setDefault('accessoptions['.$element.']', $value);
                }

                $mform->setDefault($element, $value);
            }
        }
        // TODO: Remove once this method is removed.
        $hassettings = true;
    }

    /**
     * Save the data associated with the plugin from the module's mod_form.
     *
     * TODO: This code needs to be moved for 4.3 as the method will be completely removed from core.
     * See https://tracker.moodle.org/browse/MDL-67526
     *
     * @param object $data the form data to save
     * @throws dml_exception
     */
    public function save_form_elements($data) {

        $moduletiienabled = $moduletiienabled = get_config('plagiarism_turnitinsim', 'turnitinmodenabled'.$data->modulename);
        if (empty($moduletiienabled)) {
            return;
        }

        $form = new plagiarism_turnitinsim_settings();
        $form->save_module_settings($data);
    }

    /**
     * Hook to allow report score and link to be displayed beside a submission.
     *
     * @param array $linkarray contains all relevant information to display a report score and link to cloud viewer.
     * @return string Output for similarity score and other display information.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_links($linkarray) {
        global $DB, $OUTPUT, $PAGE, $USER;

        // Require the relevant JS modules.  Only include once.
        static $jsloaded;
        if (empty($jsloaded)) {
            $jsloaded = true;
            $PAGE->requires->string_for_js('submissiondisplaystatus:queued', 'plagiarism_turnitinsim');
            $PAGE->requires->string_for_js('eulaaccepted', 'plagiarism_turnitinsim');
            $PAGE->requires->string_for_js('euladeclined', 'plagiarism_turnitinsim');
            $PAGE->requires->string_for_js('submissiondisplaystatus:queued', 'plagiarism_turnitinsim');
            $PAGE->requires->js_call_amd('plagiarism_turnitinsim/cv_launch', 'openCv');
            $PAGE->requires->js_call_amd('plagiarism_turnitinsim/resend_submission', 'resendSubmission');
            $PAGE->requires->js_call_amd('plagiarism_turnitinsim/eula_response', 'eulaResponse');
        }
        $output = '';

        // Don't show links for certain file types as they won't have been submitted to Turnitin.
        if (!empty($linkarray["file"])) {
            $file = $linkarray["file"];
            $filearea = $file->get_filearea();

            $nonsubmittingareas = array("feedback_files", "introattachment");
            $allowedcomponents = array("assignsubmission_file", "mod_assign", "mod_forum", "mod_workshop", "question");

            if ((in_array($filearea, $nonsubmittingareas)) || !in_array($file->get_component(), $allowedcomponents)) {
                return $output;
            }
        }

        // If this is a quiz, retrieve the cmid.
        $component = (!empty($linkarray['component'])) ? $linkarray['component'] : "";
        if ($component == "qtype_essay" && !empty($linkarray['area'])) {
            $questions = question_engine::load_questions_usage_by_activity($linkarray['area']);

            // Try to get cm using the questions owning context.
            $context = $questions->get_owning_context();
            if (empty($linkarray['cmid']) && $context->contextlevel == CONTEXT_MODULE) {
                $linkarray['cmid'] = $context->instanceid;
            }
        }

        // Get course module details.
        static $cm;
        if (empty($cm) && !empty($linkarray["cmid"])) {
            $cm = get_coursemodule_from_id('', $linkarray["cmid"]);
        }

        // Check whether the plugin is active.
        static $ispluginactive;
        if (empty($ispluginactive)) {
            $ispluginactive = $this->is_plugin_active($cm);
        }

        // Return empty output if the plugin is not being used.
        if (!$ispluginactive) {
            return $output;
        }

        // Create module object.
        $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;
        $moduleobject = new $moduleclass;

        // Check if the logged in user is an instructor.
        $instructor = has_capability(
            'plagiarism/turnitinsim:viewfullreport',
             context_module::instance($cm->id)
        );

        // Get the user ID for a quiz submission as it does not exist in the linkarray.
        if (!empty($linkarray['file']) && $cm->modname == "quiz") {
            $linkarray['userid'] = $DB->get_record(
                'files',
                ['id' => $linkarray['file']->get_id()],
                'userid'
            )->userid;
        }

        // Display cv link and OR score or status.
        if ((!empty($linkarray['file'])) || (!empty($linkarray['content']))) {
            $submissionid = '';
            $eulaconfirm = '';
            $status = '';
            $showresubmitlink = false;
            $submission = null;

            // Get turnitin submission details.
            $plagiarismfile = plagiarism_turnitinsim_submission::get_submission_details($linkarray);

            // The links for forum posts get shown to all users.
            // Return if the logged in user shouldn't see OR scores. E.g. forum posts.
            if (!$moduleobject->show_other_posts_links($cm->course, $linkarray['userid'])) {
                return $output;
            }

            $plagiarismsettings = $this->get_settings($cm->id);

            if ($plagiarismfile) {
                $submission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request(), $plagiarismfile->id);

                // If the user is a student and they are not allowed to view reports,
                // and they have accepted the EULA then return empty output.
                if (!$instructor && empty($plagiarismsettings->accessstudents) &&
                    $submission->getstatus() !== TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED) {
                    return $output;
                }
            }

            // Render the OR score or current submission status.
            if ($submission) {
                switch ($submission->getstatus()) {
                    case TURNITINSIM_SUBMISSION_STATUS_QUEUED:
                        $status = html_writer::tag('span', get_string('submissiondisplaystatus:queued',
                            'plagiarism_turnitinsim'));
                        break;

                    case TURNITINSIM_SUBMISSION_STATUS_NOT_SENT:
                        $status = html_writer::tag('span', get_string('submissiondisplaystatus:notsent',
                            'plagiarism_turnitinsim'));
                        $showresubmitlink = true;
                        break;

                    case TURNITINSIM_SUBMISSION_STATUS_CREATED:
                    case TURNITINSIM_SUBMISSION_STATUS_UPLOADED:
                    case TURNITINSIM_SUBMISSION_STATUS_REQUESTED:
                    case TURNITINSIM_SUBMISSION_STATUS_PROCESSING:
                        $status = html_writer::tag('span', get_string('submissiondisplaystatus:pending',
                            'plagiarism_turnitinsim'));
                        break;

                    case TURNITINSIM_SUBMISSION_STATUS_COMPLETE:
                        $score = $submission->getoverallscore() . '%';
                        $submissionid = $submission->getid();
                        $orcolour = ' turnitinsim_or_score_colour_' . round($submission->getoverallscore(), -1);
                        $status = html_writer::tag('div', $score, array('class' => 'turnitinsim_or_score' . $orcolour));
                        break;

                    case TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED:
                        $eula = new plagiarism_turnitinsim_eula();
                        $statusset = $eula->get_eula_status($cm->id, $submission->gettype(), $submission->getuserid());
                        $status = $statusset['eula-status'];
                        $eulaconfirm = $statusset['eula-confirm'];
                        $showresubmitlink = false;

                        break;

                    case TURNITINSIM_SUBMISSION_STATUS_ERROR:

                        $errorstrsuffix = strtolower(str_replace("_", "", $submission->geterrormessage()));

                        // Check if a string exists for this error and display it, otherwise use a generic one.
                        if (get_string_manager()->string_exists('submissiondisplayerror:' . $errorstrsuffix,
                            'plagiarism_turnitinsim')) {
                            $errorstring = 'submissiondisplayerror:' . $errorstrsuffix;
                        } else {
                            $errorstring = 'submissiondisplayerror:generic';
                            $showresubmitlink = true;
                        }

                        // Show a help icon with more information.
                        $erroricon = $OUTPUT->help_icon($errorstring, 'plagiarism_turnitinsim');

                        // Render status.
                        $statusstring = "submissiondisplaystatus:error";
                        if ($submission->geterrormessage() == TURNITINSIM_SUBMISSION_STATUS_CANNOT_EXTRACT_TEXT) {
                            $statusstring = 'submissiondisplaystatus:' . $errorstrsuffix;
                        }
                        $status = html_writer::tag('span', get_string($statusstring, 'plagiarism_turnitinsim'),
                            array('class' => 'tii_status_text'));
                        $status .= html_writer::tag('span', $erroricon);
                        break;

                    default:
                        // Unknown submission status. Should never happen but adding a resubmit link in case.
                        $helpicon = $OUTPUT->help_icon('submissiondisplayerror:unknown', 'plagiarism_turnitinsim');

                        $status = html_writer::tag(
                            'span',
                            get_string('submissiondisplaystatus:unknown', 'plagiarism_turnitinsim') . $helpicon,
                            array('class' => 'tii_status_text')
                        );
                        $showresubmitlink = true;
                        break;
                }

            } else if ($linkarray['userid'] != null) {
                if ($instructor && $linkarray['userid'] === "0") {
                    return $output;
                } else {
                    $linkarray['userid'] = $USER->id;
                }

                // If the plugin was enabled after a submission was made then it will not have been sent to Turnitin. Queue it.
                $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;
                $moduleobject = new $moduleclass;

                $eventdata = $moduleobject->create_submission_event_data($linkarray);
                $this->submission_handler($eventdata);

                // Check if student has accepted the EULA.
                $plagiarismfile = plagiarism_turnitinsim_submission::get_submission_details($linkarray);

                if ($plagiarismfile->status === TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED) {
                    $eula = new plagiarism_turnitinsim_eula();
                    $statusset = $eula->get_eula_status($cm->id, $plagiarismfile->type, $plagiarismfile->userid);
                    $status = $statusset['eula-status'];
                    $eulaconfirm = $statusset['eula-confirm'];
                } else {
                    $status = html_writer::tag('span', get_string('submissiondisplaystatus:queued',
                            'plagiarism_turnitinsim'));
                }
            }

            // Render a Turnitin logo.
            $turnitinicon = $OUTPUT->pix_icon('turnitin-icon', '', 'plagiarism_turnitinsim', array('class' => 'tii_icon'));

            // Render a resubmit link for instructors if necessary.
            $resubmitlink = ($instructor && $showresubmitlink) ? $this->render_resubmit_link($submission->getid()) : '';

            // Output rendered status and resubmission link if applicable.
            if ($instructor || (!$instructor && $plagiarismsettings->accessstudents)) {
                $output .= html_writer::tag('div', $eulaconfirm . $turnitinicon . $status . $resubmitlink,
                    array('class' => 'turnitinsim_status submission_' . $submissionid));
            }
        }

        return html_writer::tag('div', $output, array('class' => 'turnitinsim_links'));
    }

    /**
     * Check whether the plugin is active.
     * @param object $cm The course module data.
     * @return bool true if the plugin is active.
     * @throws dml_exception
     */
    public function is_plugin_active($cm) {
        // Get whether plugin is enabled for this module.
        $moduletiienabled = get_config('plagiarism_turnitinsim', 'turnitinmodenabled'.$cm->modname);

        // Exit if Turnitin is not being used for this activity type.
        if (empty($moduletiienabled)) {
            return false;
        }

        // Get plugin settings for this module.
        $plagiarismsettings = $this->get_settings($cm->id);

        // Exit if Turnitin is not being used for this module.
        if (empty($plagiarismsettings->turnitinenabled)) {
            return false;
        }

        return true;
    }

    /**
     * Check whether the plugin is configured to connect to Turnitin.
     */
    public function is_plugin_configured() {
        $turnitinapiurl = get_config('plagiarism_turnitinsim', 'turnitinapiurl');
        $turnitinapikey = get_config('plagiarism_turnitinsim', 'turnitinapikey');
        $turnitinroutingurl = get_config('plagiarism_turnitinsim', 'turnitinroutingurl');

        return (!empty($turnitinapikey) && (!empty($turnitinapiurl) || !empty($turnitinroutingurl))) ? true : false;
    }

    /**
     * Render a link to resubmit the file to Turnitin.
     *
     * @param int $submissionid The ID of the submission.
     * @return mixed A link to resubmit the submission.
     * @throws coding_exception
     */
    public function render_resubmit_link($submissionid) {
        global $OUTPUT;

        $resubmittext = get_string('resubmittoturnitin', 'plagiarism_turnitinsim');
        $resubmiticon = $OUTPUT->pix_icon('refresh', $resubmittext, 'plagiarism_turnitinsim');
        $resubmitlink = html_writer::tag(
            'div',
            $resubmiticon . $resubmittext,
            array(
                'title' => $resubmittext,
                'class' => 'turnitinsim_error_icon clear turnitinsim_resubmit_link pp_resubmit_id_' . $submissionid
            )
        );

        return $resubmitlink;
    }

    /**
     * Hook to allow a disclosure to be printed notifying users what will happen with their submission.
     *
     * @param int $cmid - course module id
     * @param string $submissiontype - The type of submission - file or content.
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    public function print_disclosure($cmid, $submissiontype = 'file') {
        global $CFG, $PAGE, $USER;

        // Avoid printing the EULA acceptance box more than once.
        // This needs to be shown twice for a text submission as it exists in the dom twice.
        // Allowed for unit testing otherwise only the first test that calls this would work.
        static $disclosurecount = 1;
        if (($submissiontype == 'file' && $disclosurecount === 1) ||
            ($submissiontype == 'content' && $disclosurecount <= 2) ||
            PHPUNIT_TEST) {
            $disclosurecount++;

            // Return empty output if the plugin is not being used.
            if ($cmid > -1) {
                $cm = get_coursemodule_from_id('', $cmid);
                if (!$this->is_plugin_active($cm)) {
                    return '';
                }
            }

            // Check we have the latest version of the EULA stored.
            // This should only happen the very first time someone submits.
            $eulaversion = get_config('plagiarism_turnitinsim', 'turnitin_eula_version');
            // Overwrite mtrace so when EULA is checked it doesn't output to screen.
            $CFG->mtrace_wrapper = 'plagiarism_turnitinsim_mtrace';
            if (empty($eulaversion)) {
                $tstask = new plagiarism_turnitinsim_task();
                $tstask->check_latest_eula_version();
                $eulaversion = get_config('plagiarism_turnitinsim', 'turnitin_eula_version');
            }

            // We don't need to continue if the user has accepted the latest EULA and/or EULA acceptance is not required.
            $user = new plagiarism_turnitinsim_user($USER->id);
            $features = json_decode(get_config('plagiarism_turnitinsim', 'turnitin_features_enabled'));

            if ($user->get_lasteulaaccepted() == $eulaversion) {
                return html_writer::tag(
                    'div',
                    get_string('eulaalreadyaccepted', 'plagiarism_turnitinsim'),
                    array('class' => 'turnitinsim_eulacontainer', 'id' => 'turnitinsim_eulaaccepted')
                );
            }

            if (!(bool)$features->tenant->require_eula) {
                return html_writer::tag(
                    'div',
                    get_string('eulanotrequired', 'plagiarism_turnitinsim'),
                    array('class' => 'turnitinsim_eulacontainer', 'id' => 'turnitinsim_eulanotrequired')
                );
            }

            // Require the JS module to handle the user's eula response.
            $PAGE->requires->string_for_js('eulaaccepted', 'plagiarism_turnitinsim');
            $PAGE->requires->string_for_js('euladeclined', 'plagiarism_turnitinsim');
            $PAGE->requires->string_for_js('submissiondisplaystatus:queued', 'plagiarism_turnitinsim');
            $PAGE->requires->js_call_amd('plagiarism_turnitinsim/eula_response', 'eulaResponse');

            // Link to open the Turnitin EULA in a new tab.
            $tsrequest = new plagiarism_turnitinsim_request();
            $lang = $tsrequest->get_language();
            $eulaurl = get_config('plagiarism_turnitinsim', 'turnitin_eula_url')."?lang=".$lang->localecode;
            $eulastring = ($cmid > -1) ? 'eulalink' : 'eulalinkgeneric';
            $eulalink = get_string($eulastring, 'plagiarism_turnitinsim', $eulaurl);

            // Button to allow the user to accept the Turnitin EULA.
            $eulaacceptbtn = html_writer::tag('span',
                get_string('eulaaccept', 'plagiarism_turnitinsim'),
                array('class' => 'btn btn-primary', 'id' => 'turnitinsim_eula_accept')
            );

            // Button to allow the user to decline the Turnitin EULA.
            $euladeclinebtn = html_writer::tag('span',
                get_string('euladecline', 'plagiarism_turnitinsim'),
                array('class' => 'btn btn-secondary', 'id' => 'turnitinsim_eula_decline')
            );

            // Output EULA container.
            $output = html_writer::tag(
                'div',
                html_writer::tag(
                    'p',
                    $eulalink
                ).$eulaacceptbtn.$euladeclinebtn,
                array('class' => 'turnitinsim_eulacontainer', 'id' => 'turnitinsim_eulacontainer')
            );

            return $output;
        }
    }

    /**
     * Hook to allow status of submitted files to be updated - called on grading/report pages.
     *
     * @param object $course - full Course object
     * @param object $cm - full cm object
     */
    public function update_status($course, $cm) {
    }

    /**
     * Get the Turnitin settings for a module.
     *
     * @param int $cmid - the course module id, if this is 0 the default settings will be retrieved
     * @param string $fields - fields to return, all by default
     * @return array of Turnitin settings for a module
     * @throws dml_exception
     */
    public function get_settings($cmid = null, $fields = '*') {
        global $DB;
        $settings = $DB->get_record('plagiarism_turnitinsim_mod', array('cm' => $cmid), $fields);

        return $settings;
    }

    /**
     * Handler for the submission event.
     *
     * @param array $eventdata - provided by Moodle, should contain enough data to process a submission.
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function submission_handler($eventdata) {
        global $DB;

        // Remove the event if the course module no longer exists.
        if (!$cm = get_coursemodule_from_id($eventdata['other']['modulename'], $eventdata['contextinstanceid'])) {
            return true;
        }

        // Get config settings, module settings and plagiarism settings for this module.
        $plagiarismsettings = $this->get_settings($eventdata['contextinstanceid']);
        $pluginconfig = get_config('plagiarism_turnitinsim');
        $features = (!empty($pluginconfig->turnitin_features_enabled)) ?
            json_decode($pluginconfig->turnitin_features_enabled) : '';

        // Either module not using Turnitin or Turnitin not being used at all so return true to remove event from queue.
        $modenabled = "turnitinmodenabled".$eventdata['other']['modulename'];
        if (empty($plagiarismsettings->turnitinenabled) || empty($pluginconfig->$modenabled)) {
            return true;
        }

        // Initialise setting to send draft submissions.
        $plagiarismsettings->queuedrafts = (isset($plagiarismsettings->queuedrafts)) ?
            $plagiarismsettings->queuedrafts : 0;

        // Get module data.
        $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));
        if ($cm->modname != 'assign') {
            $moduledata->submissiondrafts = 0;
        }

        // If draft submissions are turned on then only send to Turnitin if the queue draft setting is set.
        $sendtoturnitin = true;
        if ($moduledata->submissiondrafts && !$plagiarismsettings->queuedrafts &&
            ($eventdata['eventtype'] == 'file_uploaded' || $eventdata['eventtype'] == 'content_uploaded')) {
            $sendtoturnitin = false;
        }

        // Create module object.
        $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;
        $moduleobject = new $moduleclass;

        // Set the author, submitter and group (if applicable).
        $author = $moduleobject->get_author($eventdata['userid'], $eventdata['relateduserid'], $cm, $eventdata['objectid']);
        $groupid = $moduleobject->get_groupid($eventdata['objectid']);
        $submitter = new plagiarism_turnitinsim_user($eventdata['userid']);

        $itemid = (!empty($eventdata['objectid'])) ? $eventdata['objectid'] : null;

        // If this is a user confirming a final submission then revert the submission to
        // TURNITINSIM_SUBMISSION_STATUS_UPLOADED so that a report gets requested and the paper gets indexed if needed.
        if ($moduledata->submissiondrafts &&
            $eventdata['other']['modulename'] == 'assign' &&
            $eventdata['eventtype'] == "assessable_submitted") {

            $submissions = $DB->get_records_select(
                'plagiarism_turnitinsim_sub',
                'cm = ? AND userid = ? AND itemid = ? AND status NOT IN (?, ?)',
                array($cm->id, $author, $itemid, TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED, TURNITINSIM_SUBMISSION_STATUS_COMPLETE)
            );

            foreach ($submissions as $submission) {
                $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request(), $submission->id);

                $statusarray = array(
                    TURNITINSIM_SUBMISSION_STATUS_NOT_SENT,
                    TURNITINSIM_SUBMISSION_STATUS_ERROR,
                    TURNITINSIM_SUBMISSION_STATUS_QUEUED
                );

                $statusexists = in_array($tssubmission->getstatus(), $statusarray);
                $status = ($statusexists) ? TURNITINSIM_SUBMISSION_STATUS_QUEUED : TURNITINSIM_SUBMISSION_STATUS_UPLOADED;
                $generated = ($statusexists) ? false : true;

                $tssubmission->calculate_generation_time($generated);
                $tssubmission->setstatus($status);
                $tssubmission->settogenerate(1);
                $tssubmission->update();
            }

            return true;
        }

        // Quizzes don't pass the content in their event and work differently.
        if (($eventdata['eventtype'] == 'quiz_submitted') ||
            (isset($eventdata['other']['modulename']) && $eventdata['other']['modulename'] == 'quiz')) {
            $this->quiz_handler($cm, $eventdata, $sendtoturnitin, $features);

            return true;
        }

        // Queue files to submit to Turnitin.
        $this->queue_files($cm, $eventdata, $sendtoturnitin, $features);

        // Queue text content to submit to Turnitin.
        if (!empty($eventdata['other']['content'])) {
            $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
            $tssubmission->setcm($cm->id);
            $tssubmission->setuserid($author);
            $tssubmission->setgroupid($groupid);
            $tssubmission->setsubmitter($submitter->userid);
            $tssubmission->setitemid($itemid);
            $tssubmission->setquizanswer(0);

            $identifier = sha1($eventdata['other']['content']);

            $tssubmission->setidentifier($identifier);
            $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_CONTENT);

            // Check if user has submitted text content for this item previously.
            $submission = $DB->get_record_select('plagiarism_turnitinsim_sub',
                'cm = ? AND userid = ? AND itemid = ? AND type = ?',
                array($cm->id, $author, $itemid, TURNITINSIM_SUBMISSION_TYPE_CONTENT));

            // Resubmit text content if this submission is being edited.
            if (!empty($submission)) {
                $tssubmission->setid($submission->id);
            }

            // If the submitter has not accepted the EULA AND the eula is required then flag accordingly.
            $authoruser = new plagiarism_turnitinsim_user($author);
            if ((bool)$features->tenant->require_eula && $authoruser->get_lasteulaaccepted() <
                get_config('plagiarism_turnitinsim', 'turnitin_eula_version')) {

                $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED);
                $tssubmission->update();
                return true;
            }

            $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
            $tssubmission->calculate_generation_time();
            $tssubmission->settiiattempts(0);
            $tssubmission->settiiretrytime(0);
            $tssubmission->update();
        }

        return true;
    }

    /**
     * Method for queuing a file submission.
     *
     * @param object $cm Information relating to a course module.
     * @param array $eventdata - provided by Moodle, should contain enough data to process a submission.
     * @param boolean $sendtoturnitin Send if draft submissions are not enabled.
     * @param object $features The features available for this account.
     * @param string $quizanswer The quiz answer unique key, made up for questionusageid and slot number.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function queue_files($cm, $eventdata, $sendtoturnitin, $features, $quizanswer = '0') {
        global $DB;

        // Create module object.
        $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;
        $moduleobject = new $moduleclass;

        // Set the author, submitter and group (if applicable).
        $author = $moduleobject->get_author($eventdata['userid'], $eventdata['relateduserid'], $cm, $eventdata['objectid']);
        $groupid = $moduleobject->get_groupid($eventdata['objectid']);
        $submitter = new plagiarism_turnitinsim_user($eventdata['userid']);

        $itemid = (!empty($eventdata['objectid'])) ? $eventdata['objectid'] : null;

        if (!empty($eventdata['other']['pathnamehashes'])) {
            foreach ($eventdata['other']['pathnamehashes'] as $pathnamehash) {
                $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
                $tssubmission->setcm($cm->id);
                $tssubmission->setuserid($author);
                $tssubmission->setgroupid($groupid);
                $tssubmission->setsubmitter($submitter->userid);
                $tssubmission->setitemid($itemid);
                $tssubmission->setidentifier($pathnamehash);
                $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_FILE);
                $tssubmission->setquizanswer($quizanswer);

                // Check if this file has been submitted previously and re-use record.
                $query = ' cm = ? AND userid = ? AND identifier = ? ';
                $params = array($cm->id, $author, $pathnamehash);
                if (!is_null($groupid)) {
                    $query .= ' AND groupid = ?';
                    $params[] = $groupid;
                }
                $submission = $DB->get_record_select('plagiarism_turnitinsim_sub', $query, $params);
                $filedetails = $tssubmission->get_file_details();

                // Do not submit feedback or into files.
                if ($filedetails) {
                    $filearea = $filedetails->get_filearea();
                    $nonsubmittingareas = array("feedback_files", "introattachment");
                    if (in_array($filearea, $nonsubmittingareas)) {
                        return true;
                    }
                }

                // Check that the file exists and is not empty.
                if (!$filedetails) {
                    $tssubmission->settogenerate(0);
                    $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_EMPTY_DELETED);
                    $tssubmission->update();
                    continue;
                }

                if (!empty($submission)) {
                    $tssubmission->setid($submission->id);

                    // Only re-queue previously submitted files if they have been modified since original submission.
                    if ($filedetails->get_timemodified() < $submission->submittedtime
                        && $submission->status === TURNITINSIM_SUBMISSION_STATUS_COMPLETE) {
                        continue;
                    }
                }

                // Check that the file is not a directory.
                if ($filedetails->get_filename() === '.') {
                    $tssubmission->settogenerate(0);
                    $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_EMPTY_DELETED);
                    $tssubmission->update();
                    continue;
                }

                // Check that the file does not exceed the maximum file size.
                if ($filedetails->get_filesize() > TURNITINSIM_SUBMISSION_MAX_FILE_UPLOAD_SIZE) {
                    $tssubmission->settogenerate(0);
                    $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
                    $tssubmission->seterrormessage(TURNITINSIM_SUBMISSION_STATUS_TOO_LARGE);
                    $tssubmission->update();
                    continue;
                }

                // If the submitter has not accepted the EULA then flag accordingly.
                $authoruser = new plagiarism_turnitinsim_user($author);
                if ((empty($authoruser->get_lasteulaaccepted()) ||
                        $authoruser->get_lasteulaaccepted() < get_config('plagiarism_turnitinsim', 'turnitin_eula_version')) &&
                    (bool)$features->tenant->require_eula
                ) {
                    $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED);
                    $tssubmission->update();
                    continue;
                }

                // If this is to be sent then queue, otherwise mark as not sent.
                $status = ($sendtoturnitin) ? TURNITINSIM_SUBMISSION_STATUS_QUEUED : TURNITINSIM_SUBMISSION_STATUS_NOT_SENT;
                $tssubmission->calculate_generation_time();
                $tssubmission->setstatus($status);
                $tssubmission->settiiattempts(0);
                $tssubmission->settiiretrytime(0);

                $tssubmission->update();
            }
        }
    }

    /**
     * Specific method for handling the quiz_submitted event type.
     * This is because a quiz might have many questions to queue.
     *
     * @param object $cm Information relating to a course module.
     * @param array $eventdata - provided by Moodle, should contain enough data to process a submission.
     * @param boolean $sendtoturnitin Send if draft submissions are not enabled.
     * @param object $features The features available for this account.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function quiz_handler($cm, $eventdata, $sendtoturnitin, $features) {
        // Create module object.
        $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;
        $moduleobject = new $moduleclass;

        // Set the author, submitter and group (if applicable).
        $author = $moduleobject->get_author($eventdata['userid'], $eventdata['relateduserid'], $cm, $eventdata['objectid']);
        $groupid = $moduleobject->get_groupid($eventdata['objectid']);
        $submitter = new plagiarism_turnitinsim_user($eventdata['userid']);

        // Queue every question submitted in a quiz attempt.
        $attempt = quiz_attempt::create($eventdata['objectid']);
        $context = context_module::instance($attempt->get_cmid());
        foreach ($attempt->get_slots() as $slot) {
            $eventdata['other']['pathnamehashes'] = array();
            $qa = $attempt->get_question_attempt($slot);

            if ($qa->get_question()->get_type_name() != 'essay') {
                continue;
            }

            $quizanswer = $qa->get_usage_id().'-'.$qa->get_slot();

            $files = $qa->get_last_qt_files('attachments', $context->id);
            if ($files) {
                foreach ($files as $fileinfo) {
                    $eventdata['other']['pathnamehashes'][] = $fileinfo->get_pathnamehash();
                }
            }

            $this->queue_files($cm, $eventdata, $sendtoturnitin, $features, $quizanswer);

            // Don't queue empty content as it may be a file only question.
            if (empty($qa->get_response_summary())) {
                continue;
            }

            $tssubmission = new plagiarism_turnitinsim_submission(new plagiarism_turnitinsim_request());
            $tssubmission->setcm($cm->id);
            $tssubmission->setuserid($author);
            $tssubmission->setgroupid($groupid);
            $tssubmission->setsubmitter($submitter->userid);
            $tssubmission->setitemid($eventdata['objectid']);
            $tssubmission->setidentifier(sha1($qa->get_response_summary()));
            $tssubmission->settype(TURNITINSIM_SUBMISSION_TYPE_CONTENT);
            $tssubmission->setquizanswer($quizanswer);

            // If the submitter has not accepted the EULA then flag accordingly.
            if ($submitter->get_lasteulaaccepted() < get_config('plagiarism_turnitinsim', 'turnitin_eula_version')) {
                $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED);
                $tssubmission->update();
                continue;
            }

            $tssubmission->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
            $tssubmission->calculate_generation_time();
            $tssubmission->settiiattempts(0);
            $tssubmission->settiiretrytime(0);
            $tssubmission->update();
        }
    }

    /**
     * Event hook for when a module has been changed. Set the generation flag for a submission.
     *
     * @param array $eventdata Contains information from the event being handled.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function module_updated($eventdata) {
        global $DB;

        $cm = get_coursemodule_from_id('', $eventdata['objectid']);

        $module = $this->get_settings($cm->id);

        if (!$module) {
            return;
        }

        // Currently this is only used by assignments in case of a due date changing before regeneration.
        // If a due date changes, then we set the regeneration time accordingly.
        if ($module->reportgeneration != TURNITINSIM_REPORT_GEN_IMMEDIATE) {

            // Create module object and get due date.
            $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;
            $moduleobject = new $moduleclass;

            $duedate = $moduleobject->get_due_date($cm->instance);

            // Update togenerate field.
            $DB->set_field_select(
                'plagiarism_turnitinsim_sub',
                'togenerate',
                1,
                'cm = ? AND generationtime > ? ',
                array($cm->id, $duedate)
            );

            // Update generation time.
            $DB->set_field_select(
                'plagiarism_turnitinsim_sub',
                'generationtime',
                $duedate,
                'cm = ? AND generationtime > ? ',
                array($cm->id, time())
            );
        }
    }

    /**
     * Wrapper method for Moodle's set_config method for enabling the plugin.
     * This is so that when the deprecation is deleted we only need to change one place.
     *
     * @param int $enabled 1 if plugin is to be enabled.
     */
    public static function enable_plugin($enabled) {
        handle_deprecation::set_plugin_enabled($enabled);
    }

    /**
     * Wrapper method for Moodle's get_config method for checking if the plugin is enabled.
     * This is so that when the deprecation is deleted we only need to change one place.
     *
     * @return mixed
     */
    public static function plugin_enabled() {
        return handle_deprecation::get_plugin_enabled();
    }
}

/**
 * Add the Turnitin settings form to an add/edit activity page
 *
 * @param moodleform $formwrapper Moodleform wrapper
 * @param MoodleQuickForm $mform Moodle Mform that we want to add our code to.
 */
function plagiarism_turnitinsim_coursemodule_standard_elements($formwrapper, $mform) {
    $context = context_course::instance($formwrapper->get_course()->id);

    (new plagiarism_plugin_turnitinsim())->get_form_elements_module(
        $mform,
        $context,
        isset($formwrapper->get_current()->modulename) ? 'mod_'.$formwrapper->get_current()->modulename : ''
    );
}

/**
 * Handle saving data from the Turnitin settings form..
 *
 * @param stdClass $data The form data.
 * @param stdClass $course The course the call is made from.
 */
function plagiarism_turnitinsim_coursemodule_edit_post_actions($data, $course) {
    (new plagiarism_plugin_turnitinsim())->save_form_elements($data);

    return $data;
}

/**
 * Override Moodle's mtrace function for methods shared with tasks.
 *
 * @param string $string The message that would otherwise be displayed.
 * @param string $eol end of line.
 * @return bool
 */
function plagiarism_turnitinsim_mtrace($string, $eol) {
    return true;
}