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
 * Main library for plagiarism_turnitincheck component
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

// Get global class.
require_once( $CFG->dirroot . '/plagiarism/lib.php' );
require_once( __DIR__ . '/utilities/constants.php' );
require_once( __DIR__ . '/classes/tcsettings.class.php' );
require_once( __DIR__ . '/classes/tcsubmission.class.php' );
require_once( __DIR__ . '/classes/tcuser.class.php' );
require_once( __DIR__ . '/classes/tcgroup.class.php' );
require_once( __DIR__ . '/classes/tcrequest.class.php' );
require_once( __DIR__ . '/classes/tclogger.class.php' );
require_once( __DIR__ . '/classes/tceula.class.php' );
require_once( __DIR__ . '/classes/tctask.class.php' );

class plagiarism_plugin_turnitincheck extends plagiarism_plugin {

    /**
     * Get the fields to be used in the form to configure each module's Turnitin settings.
     *
     * @param object $mform  - Moodle form
     * @param object $context - current context
     * @param string $modulename - Name of the module
     * @return array of settings fields.
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {

        $cmid = optional_param('update', 0, PARAM_INT);

        $location = ($context == context_system::instance()) ? 'defaults' : 'module';

        $form = new tcsettings();
        $form->add_settings_to_module($mform, $location, $modulename);

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
    }

    /**
     * Save the data associated with the plugin from the module's mod_form.
     *
     * @param object $data the form data to save
     */
    public function save_form_elements($data) {

        $moduletiienabled = $moduletiienabled = get_config('plagiarism', 'turnitinmodenabled'.$data->modulename);
        if (empty($moduletiienabled)) {
            return;
        }

        $form = new tcsettings();
        $form->save_module_settings($data);
    }

    /**
     * Hook to allow report score and link to be displayed beside a submission.
     *
     * @param array $linkarray contains all relevant information to display a report score and link to cloud viewer.
     * @return string
     */
    public function get_links($linkarray) {
        global $OUTPUT, $PAGE;

        // Require the relevant JS modules.  Only include once.
        static $jsloaded;
        if (empty($jsloaded)) {
            $jsloaded = true;
            $PAGE->requires->string_for_js('loadingcv', 'plagiarism_turnitincheck');
            $PAGE->requires->string_for_js('submissiondisplaystatus:queued', 'plagiarism_turnitincheck');
            $PAGE->requires->js_call_amd('plagiarism_turnitincheck/cv_launch', 'open_cv');
            $PAGE->requires->js_call_amd('plagiarism_turnitincheck/resend_submission', 'resendSubmission');
            $PAGE->requires->js_call_amd('plagiarism_turnitincheck/inbox_eula_launch', 'inbox_eula_launch');
        }
        $output = '';

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
        $moduleclass = 'tc'.$cm->modname;
        $moduleobject = new $moduleclass;

        // Check if the logged in user is an instructor.
        $instructor = has_capability(
            'plagiarism/turnitincheck:viewfullreport',
             context_module::instance($cm->id)
        );

        // If the user is a student and they are not allowed to view reports then return empty output.
        $plagiarismsettings = $this->get_settings($cm->id);
        if (!$instructor && empty($plagiarismsettings->accessstudents)) {
            return $output;
        }

        // Display cv link and OR score or status.
        if ((!empty($linkarray['file'])) || (!empty($linkarray['content']))) {
            $submissionid = '';
            $showresubmitlink = false;

            // Get turnitin submission details.
            $plagiarismfile = tcsubmission::get_submission_details($linkarray);

            // The links for forum posts get shown to all users.
            // Return if the logged in user shouldn't see OR scores. E.g. forum posts.
            if (!$moduleobject->show_other_posts_links($cm->course, $linkarray['userid'])) {
                return $output;
            }

            // Render the OR score or current submission status.
            if ($plagiarismfile) {
                $submission = new tcsubmission(new tcrequest(), $plagiarismfile->id);

                switch ($submission->getstatus()) {
                    case TURNITINCHECK_SUBMISSION_STATUS_QUEUED:
                        $status = html_writer::tag('span', get_string('submissiondisplaystatus:queued',
                            'plagiarism_turnitincheck'));
                        break;

                    case TURNITINCHECK_SUBMISSION_STATUS_NOT_SENT:
                        $status = html_writer::tag('span', get_string('submissiondisplaystatus:notsent',
                            'plagiarism_turnitincheck'));
                        $showresubmitlink = true;
                        break;

                    case TURNITINCHECK_SUBMISSION_STATUS_CREATED:
                    case TURNITINCHECK_SUBMISSION_STATUS_UPLOADED:
                    case TURNITINCHECK_SUBMISSION_STATUS_REQUESTED:
                    case TURNITINCHECK_SUBMISSION_STATUS_PROCESSING:
                        $status = html_writer::tag('span', get_string('submissiondisplaystatus:pending',
                            'plagiarism_turnitincheck'));
                        break;

                    case TURNITINCHECK_SUBMISSION_STATUS_COMPLETE:
                        $score = $submission->getoverallscore() . '%';
                        $submissionid = $submission->getid();
                        $orcolour = ' or_score_colour_' . round($submission->getoverallscore(), -1);
                        $status = html_writer::tag('div', $score, array('class' => 'or_score' . $orcolour));
                        break;

                    case TURNITINCHECK_SUBMISSION_STATUS_EULA_NOT_ACCEPTED:
                        // Allow a modal to be launched with a EULA link and ability to accept.
                        $tcrequest = new tcrequest();
                        $lang = $tcrequest->get_language();
                        $eulaurl = get_config('plagiarism', 'turnitin_eula_url')."?lang=".$lang->localecode;

                        $helpicon = $OUTPUT->pix_icon(
                            'help',
                            get_string('submissiondisplayerror:eulanotaccepted', 'plagiarism_turnitincheck'),
                            'core',
                            ['class' => 'eula-row-launch', 'data-eula-link' => $eulaurl]
                        );

                        $eulalaunch = ' '.$helpicon;

                        $status = html_writer::tag(
                            'span',
                            get_string('submissiondisplaystatus:awaitingeula', 'plagiarism_turnitincheck') . $eulalaunch,
                            array('class' => 'tii_status_text tii_status_text_eula')
                        );
                        $showresubmitlink = true;
                        break;

                    case TURNITINCHECK_SUBMISSION_STATUS_ERROR:
                        $errorstrsuffix = strtolower(str_replace("_", "", $submission->geterrormessage()));

                        // Check if a string exists for this error and display it, otherwise use a generic one.
                        if (get_string_manager()->string_exists('submissiondisplayerror:' . $errorstrsuffix,
                            'plagiarism_turnitincheck')) {
                            $errorstring = 'submissiondisplayerror:' . $errorstrsuffix;
                        } else {
                            $errorstring = 'submissiondisplayerror:generic';
                            $showresubmitlink = true;
                        }

                        // Show a help icon with more information.
                        $erroricon = $OUTPUT->help_icon($errorstring, 'plagiarism_turnitincheck');

                        // Render status.
                        $status = html_writer::tag('span',
                            get_string('submissiondisplaystatus:error', 'plagiarism_turnitincheck'),
                            array('class' => 'tii_status_text'));
                        $status .= html_writer::tag('span', $erroricon);
                        break;

                    default:
                        // Unknown submission status. Should never happen but adding a resubmit link in case.
                        $helpicon = $OUTPUT->help_icon('submissiondisplayerror:unknown', 'plagiarism_turnitincheck');

                        $status = html_writer::tag(
                            'span',
                            get_string('submissiondisplaystatus:unknown', 'plagiarism_turnitincheck') . $helpicon,
                            array('class' => 'tii_status_text')
                        );
                        $showresubmitlink = true;
                        break;
                }

            } else {
                // If the plugin was enabled after a submission was made then it will not have been sent to Turnitin.
                $helpicon = $OUTPUT->help_icon('submissiondisplayerror:notsent', 'plagiarism_turnitincheck');

                $status = html_writer::tag('span', get_string('submissiondisplaystatus:notsent',
                    'plagiarism_turnitincheck') . $helpicon);
            }

            // Render a Turnitin logo.
            $turnitinicon = $OUTPUT->pix_icon('tiiIcon', '', 'plagiarism_turnitincheck', array('class' => 'tii_icon'));

            // Render a resubmit link for instructors if necessary.
            $resubmitlink = ($instructor && $showresubmitlink) ? $this->render_resubmit_link($submission->getid()) : '';

            // Output rendered status and resubmission link if applicable.
            $output .= html_writer::tag('div', $turnitinicon.$status.$resubmitlink,
                            array('class' => 'turnitincheck_status submission_'.$submissionid));
        }

        return html_writer::tag('div', $output, array('class' => 'turnitincheck_links'));
    }

    /**
     * Check whether the plugin is active.
     * @param $cm
     * @return bool
     */
    public function is_plugin_active($cm) {
        // Get whether plugin is enabled for this module.
        $moduletiienabled = get_config('plagiarism', 'turnitinmodenabled'.$cm->modname);

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
     * Render a link to resubmit the file to Turnitin.
     *
     * @param $submissionid
     * @return mixed
     */
    public function render_resubmit_link($submissionid) {
        global $OUTPUT;

        $resubmittext = get_string('resubmittoturnitin', 'plagiarism_turnitincheck');
        $resubmiticon = $OUTPUT->pix_icon('refresh', $resubmittext, 'plagiarism_turnitincheck');
        $resubmitlink = html_writer::tag(
            'div',
            $resubmiticon . $resubmittext,
            array(
                'title' => $resubmittext,
                'class' => 'turnitincheck_error_icon clear pp_resubmit_link pp_resubmit_id_' . $submissionid
            )
        );

        return $resubmitlink;
    }

    /**
     * Hook to allow a disclosure to be printed notifying users what will happen with their submission.
     *
     * @param int $cmid - course module id
     * @return string
     */
    public function print_disclosure($cmid) {
        global $CFG, $PAGE, $USER;

        // Return empty output if the plugin is not being used.
        if ($cmid > -1) {
            $cm = get_coursemodule_from_id('', $cmid);
            if (!$this->is_plugin_active($cm)) {
                return '';
            }
        }

        // Check we have the latest version of the EULA stored.
        // This should only happen the very first time someone submits.
        $eulaversion = get_config('plagiarism', 'turnitin_eula_version');
        // Overwrite mtrace so when EULA is checked it doesn't output to screen.
        $CFG->mtrace_wrapper = 'plagiarism_turnitincheck_mtrace';
        if (empty($eulaversion)) {
            $tctask = new tctask();
            $tctask->check_latest_eula_version();
            $eulaversion = get_config('plagiarism', 'turnitin_eula_version');
        }

        // Check user has accepted the latest EULA.
        $user = new tcuser($USER->id);
        if ($user->get_lasteulaaccepted() == $eulaversion) {
            return '';
        }

        // Require the JS module to handle the user's eula response.
        $PAGE->requires->string_for_js('eulaaccepted', 'plagiarism_turnitincheck');
        $PAGE->requires->string_for_js('euladeclined', 'plagiarism_turnitincheck');
        $PAGE->requires->js_call_amd('plagiarism_turnitincheck/eula_response', 'eula_response');

        // Link to open the Turnitin EULA in a new tab.
        $tcrequest = new tcrequest();
        $lang = $tcrequest->get_language();
        $eulaurl = get_config('plagiarism', 'turnitin_eula_url')."?lang=".$lang->localecode;
        $eulastring = ($cmid > -1) ? 'eulalink' : 'eulalinkgeneric';
        $eulalink = get_string($eulastring, 'plagiarism_turnitincheck', $eulaurl);

        // Button to allow the user to accept the Turnitin EULA.
        $eulaacceptbtn = html_writer::tag('button',
            get_string('eulaaccept', 'plagiarism_turnitincheck'),
            array('class' => 'btn btn-primary', 'id' => 'pp-eula-accept')
        );

        // Button to allow the user to decline the Turnitin EULA.
        $euladeclinebtn = html_writer::tag('button',
            get_string('euladecline', 'plagiarism_turnitincheck'),
            array('class' => 'btn btn-secondary', 'id' => 'pp-eula-decline')
        );

        // Output EULA container.
        $output = html_writer::tag(
            'div',
            $eulalink.$eulaacceptbtn.$euladeclinebtn,
            array('class' => 'eulacontainer', 'id' => 'eulacontainer')
        );

        return $output;
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
     * @param int $cm_id - the course module id, if this is 0 the default settings will be retrieved
     * @param string $fields - fields to return, all by default
     * @return array of Turnitin settings for a module
     */
    public function get_settings($cmid = null, $fields = '*') {
        global $DB;
        $settings = $DB->get_record('plagiarism_turnitincheck_mod', array('cm' => $cmid), $fields);

        return $settings;
    }

    /**
     * @param array $eventdata - provided by Moodle, should contain enough data to process a submission.
     * @return bool
     */
    public function submission_handler($eventdata) {
        global $DB;

        // Remove the event if the course module no longer exists.
        if (!$cm = get_coursemodule_from_id($eventdata['other']['modulename'], $eventdata['contextinstanceid'])) {
            return true;
        }

        // Get config settings, module settings and plagiarism settings for this module.
        $plagiarismsettings = $this->get_settings($eventdata['contextinstanceid']);
        $pluginconfig = get_config('plagiarism');

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
        $moduleclass = 'tc'.$cm->modname;
        $moduleobject = new $moduleclass;

        // Set the author, submitter and group (if applicable).
        $author = $moduleobject->get_author($eventdata['userid'], $eventdata['relateduserid'], $cm, $eventdata['objectid']);
        $groupid = $moduleobject->get_groupid($eventdata['objectid']);
        $submitter = new tcuser($eventdata['userid']);

        // Get the item ID.
        $itemid = (!empty($eventdata['objectid'])) ? $eventdata['objectid'] : null;

        // If this is a user confirming a final submission then revert the submission to
        // TURNITINCHECK_SUBMISSION_STATUS_UPLOADED so that a report gets requested and the paper gets indexed if needed.
        if ($moduledata->submissiondrafts && $eventdata['other']['modulename'] == 'assign' && $eventdata['eventtype'] == "assessable_submitted") {
            $submissions = $DB->get_records_select(
                'plagiarism_turnitincheck_sub',
                'cm = ? AND userid = ? AND itemid = ? AND status != ?',
                array($cm->id, $author, $itemid, TURNITINCHECK_SUBMISSION_STATUS_EULA_NOT_ACCEPTED)
            );

            foreach ($submissions as $submission) {
                $tcsubmission = new tcsubmission(new tcrequest(), $submission->id);

                $statusarray = array(
                    TURNITINCHECK_SUBMISSION_STATUS_NOT_SENT,
                    TURNITINCHECK_SUBMISSION_STATUS_ERROR,
                    TURNITINCHECK_SUBMISSION_STATUS_QUEUED
                );

                $status = (in_array($tcsubmission->getstatus(), $statusarray)) ?
                    TURNITINCHECK_SUBMISSION_STATUS_QUEUED : TURNITINCHECK_SUBMISSION_STATUS_UPLOADED;

                $generated = (in_array($tcsubmission->getstatus(), $statusarray)) ?
                    false : true;

                $tcsubmission->calculate_generation_time($generated);
                $tcsubmission->setstatus($status);
                $tcsubmission->update();
            }

            return true;
        }

        // Queue files to submit to Turnitin.
        if (!empty($eventdata['other']['pathnamehashes'])) {
            foreach ($eventdata['other']['pathnamehashes'] as $pathnamehash) {
                $tcsubmission = new tcsubmission(new tcrequest());
                $tcsubmission->setcm($cm->id);
                $tcsubmission->setuserid($author);
                $tcsubmission->setgroupid($groupid);
                $tcsubmission->setsubmitter($submitter->userid);
                $tcsubmission->setitemid($itemid);
                $tcsubmission->setidentifier($pathnamehash);
                $tcsubmission->settype(TURNITINCHECK_SUBMISSION_TYPE_FILE);

                // Check if this file has been submitted previously and re-use record.
                $query = ' cm = ? AND userid = ? AND identifier = ? ';
                $params = array($cm->id, $author, $pathnamehash);
                if (!is_null($groupid)) {
                    $query .= ' AND groupid = ?';
                    $params[] = $groupid;
                }
                $submission = $DB->get_record_select('plagiarism_turnitincheck_sub', $query, $params);

                if (!empty($submission)) {
                    $tcsubmission->setid($submission->id);

                    // We don't want to re-process existing submissions.
                    if ($submission->status === TURNITINCHECK_SUBMISSION_STATUS_COMPLETE) {
                        continue;
                    }
                }

                // Check that the file exists and is not empty.
                $filedetails = $tcsubmission->get_file_details();
                if (!$filedetails) {
                    $tcsubmission->settogenerate(0);
                    $tcsubmission->setstatus(TURNITINCHECK_SUBMISSION_STATUS_EMPTY_DELETED);
                    $tcsubmission->update();
                    continue;
                }
                // Check that the file is not a directory.
                if ($filedetails->get_filename() === '.') {
                    $tcsubmission->settogenerate(0);
                    $tcsubmission->setstatus(TURNITINCHECK_SUBMISSION_STATUS_EMPTY_DELETED);
                    $tcsubmission->update();
                    continue;
                }

                // Check that the file does not exceed the maximum file size.
                if ($filedetails->get_filesize() > TURNITINCHECK_SUBMISSION_MAX_FILE_UPLOAD_SIZE) {
                    $tcsubmission->settogenerate(0);
                    $tcsubmission->setstatus(TURNITINCHECK_SUBMISSION_STATUS_ERROR);
                    $tcsubmission->seterrormessage(TURNITINCHECK_SUBMISSION_STATUS_TOO_LARGE);
                    $tcsubmission->update();
                    continue;
                }

                // If the submitter has not accepted the EULA then flag accordingly.
                if (empty($submitter->get_lasteulaaccepted()) ||
                    $submitter->get_lasteulaaccepted() < get_config('plagiarism', 'turnitin_eula_version')) {
                    $tcsubmission->setstatus(TURNITINCHECK_SUBMISSION_STATUS_EULA_NOT_ACCEPTED);
                    $tcsubmission->update();
                    continue;
                }

                // If this is to be sent then queue, otherwise mark as not sent.
                $status = ($sendtoturnitin) ? TURNITINCHECK_SUBMISSION_STATUS_QUEUED : TURNITINCHECK_SUBMISSION_STATUS_NOT_SENT;
                $tcsubmission->calculate_generation_time();
                $tcsubmission->setstatus($status);

                $tcsubmission->update();
            }
        }

        // Queue text content to submit to Turnitin.
        if (!empty($eventdata['other']['content'])) {
            $tcsubmission = new tcsubmission(new tcrequest());
            $tcsubmission->setcm($cm->id);
            $tcsubmission->setuserid($author);
            $tcsubmission->setgroupid($groupid);
            $tcsubmission->setsubmitter($submitter->userid);
            $tcsubmission->setitemid($itemid);

            $identifier = sha1($eventdata['other']['content']);

            $tcsubmission->setidentifier($identifier);
            $tcsubmission->settype(TURNITINCHECK_SUBMISSION_TYPE_CONTENT);

            // Check if user has submitted text content for this item previously.
            $submission = $DB->get_record_select('plagiarism_turnitincheck_sub',
                'cm = ? AND userid = ? AND itemid = ? AND type = ?',
                array($cm->id, $author, $itemid, TURNITINCHECK_SUBMISSION_TYPE_CONTENT));

            // Resubmit text content if this submission is being edited.
            if (!empty($submission)) {
                $tcsubmission->setid($submission->id);
            }

            // If the submitter has not accepted the EULA then flag accordingly.
            if ($submitter->get_lasteulaaccepted() < get_config('plagiarism', 'turnitin_eula_version')) {
                $tcsubmission->setstatus(TURNITINCHECK_SUBMISSION_STATUS_EULA_NOT_ACCEPTED);
                $tcsubmission->update();
                return true;
            }

            $tcsubmission->setstatus(TURNITINCHECK_SUBMISSION_STATUS_QUEUED);
            $tcsubmission->calculate_generation_time();
            $tcsubmission->update();
        }
        return true;
    }

    /**
     * Event hook for when a module has been changed. Set the generation flag for a submission.
     *
     * @param $eventdata
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
        if ($module->reportgeneration != TURNITINCHECK_REPORT_GEN_IMMEDIATE) {

            // Create module object and get due date.
            $moduleclass = 'tc'.$cm->modname;
            $moduleobject = new $moduleclass;

            $duedate = $moduleobject->get_due_date($cm->instance);

            // Update to_generate field.
            $DB->set_field_select(
                'plagiarism_turnitincheck_sub',
                'to_generate',
                1,
                'cm = ? AND generation_time > ? ',
                array($cm->id, $duedate)
            );

            // Update generation time.
            $DB->set_field_select(
                'plagiarism_turnitincheck_sub',
                'generation_time',
                $duedate,
                'cm = ? AND generation_time > ? ',
                array($cm->id, time())
            );
        }
    }

}

/**
 * Override Moodle's mtrace function for methods shared with tasks.
 */
function plagiarism_turnitincheck_mtrace($string, $eol) {
    return true;
}