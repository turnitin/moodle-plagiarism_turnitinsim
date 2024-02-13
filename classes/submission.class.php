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
 * Submission class for plagiarism_turnitinsim component.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use plagiarism_turnitinsim\message\receipt_instructor;
use plagiarism_turnitinsim\message\receipt_student;

require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/assign.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/forum.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/quiz.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/workshop.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/logging_request.class.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/classes/logging_request_info.class.php');

/**
 * Submission class for plagiarism_turnitinsim component.
 */
class plagiarism_turnitinsim_submission {

    /**
     * @var int The submission ID.
     */
    public $id;

    /**
     * @var object The course module.
     */
    public $cm;

    /**
     * @var int The user ID.
     */
    public $userid;

    /**
     * @var int The group ID.
     */
    public $groupid;

    /**
     * @var int The submitter of the paper.
     */
    public $submitter;

    /**
     * @var int The Turnitin submission ID.
     */
    public $turnitinid;

    /**
     * @var string The submission status.
     */
    public $status;

    /**
     * @var string The Moodle identifier for the file.
     */
    public $identifier;

    /**
     * @var string The Moodle itemid for the submission.
     */
    public $itemid;

    /**
     * @var string The type of submission, for example text or file.
     */
    public $type;

    /**
     * @var int The time the submission was made.
     */
    public $submittedtime;

    /**
     * @var int Whether or not the submission is still to be generated.
     */
    public $togenerate;

    /**
     * @var int The time the originality report was generated.
     */
    public $generationtime;

    /**
     * @var int The time the originality report was requested.
     */
    public $requestedtime;

    /**
     * @var int The originality report.
     */
    public $overallscore;

    /**
     * @var string The error nessage, if the submission did not complete successfully.
     */
    public $errormessage;

    /**
     * @var int The number of attempts to obtain a similarity report.
     */
    public $tiiattempts;

    /**
     * @var int The time of the next retry for this submission.
     */
    public $tiiretrytime;

    /**
     * @var string The quiz answer unique key, made up for questionusageid and slot number.
     */
    public $quizanswer;

    /**
     * @var object The request object.
     */
    public $tsrequest;

    /**
     * plagiarism_turnitinsim_submission constructor.
     *
     * @param plagiarism_turnitinsim_request|null $tsrequest Request object.
     * @param null $id The submission ID.
     * @throws dml_exception
     */
    public function __construct(plagiarism_turnitinsim_request $tsrequest = null, $id = null) {
        global $DB;

        $this->setid($id);
        $this->tsrequest = ($tsrequest) ? $tsrequest : new plagiarism_turnitinsim_request();
        $this->plagiarism_plugin_turnitinsim = new plagiarism_plugin_turnitinsim();

        if (!empty($id)) {
            $submission = $DB->get_record('plagiarism_turnitinsim_sub', array('id' => $id));

            $this->setcm($submission->cm);
            $this->setuserid($submission->userid);
            $this->setsubmitter($submission->submitter);
            $this->setgroupid($submission->groupid);
            $this->setturnitinid($submission->turnitinid);
            $this->setstatus($submission->status);
            $this->setidentifier($submission->identifier);
            $this->setitemid($submission->itemid);
            $this->settogenerate($submission->togenerate);
            $this->setgenerationtime($submission->generationtime);
            $this->settype($submission->type);
            $this->setsubmittedtime($submission->submittedtime);
            $this->setoverallscore($submission->overallscore);
            $this->setrequestedtime($submission->requestedtime);
            $this->seterrormessage($submission->errormessage);
            $this->settiiattempts($submission->tiiattempts);
            $this->settiiretrytime($submission->tiiretrytime);
            $this->setquizanswer($submission->quizanswer);
        }
    }

    /**
     * Save the submission data to the files table.
     */
    public function update() {
        global $DB;

        if (!empty($this->id)) {
            $DB->update_record('plagiarism_turnitinsim_sub', $this);
        } else {
            $id = $DB->insert_record('plagiarism_turnitinsim_sub', $this);
            $this->setid($id);
        }

        return true;
    }

    /**
     * Set the generation time for a paper.
     *
     * @param bool $generated true if originality report has been generated.
     * @throws coding_exception
     */
    public function calculate_generation_time($generated = false) {
        $cm = get_coursemodule_from_id('', $this->getcm());

        // If we can't find a course module, don't proceed.
        if (!$cm) {
            $this->settogenerate(0);
            return;
        }

        $plagiarismsettings = $this->plagiarism_plugin_turnitinsim->get_settings($cm->id);

        // Create module object.
        $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;
        $moduleobject = new $moduleclass;

        $duedate = $moduleobject->get_due_date($cm->instance);

        // If the report has already generated then only proceed if report speed is 1.
        if ($generated && $plagiarismsettings->reportgeneration != TURNITINSIM_REPORT_GEN_IMMEDIATE_AND_DUEDATE) {
            $this->settogenerate(0);
            return;
        }

        // Set Generation Time dependent on report generation speed.
        switch ($plagiarismsettings->reportgeneration) {

            // Generate Immediately.
            case TURNITINSIM_REPORT_GEN_IMMEDIATE:
                $this->settogenerate(1);
                $this->setgenerationtime(time());
                break;

            // Generate Immediately, and on Due Date (only applicable to assignments).
            case TURNITINSIM_REPORT_GEN_IMMEDIATE_AND_DUEDATE:

                // If submission hasn't been processed yet then generate immediately.
                $immediatestatuses = array(
                    TURNITINSIM_SUBMISSION_STATUS_QUEUED,
                    TURNITINSIM_SUBMISSION_STATUS_UPLOADED
                );

                // Set the report generation time.
                $this->settogenerate(1);
                if (in_array($this->getstatus(), $immediatestatuses)) {
                    $this->setgenerationtime(time());
                } else {
                    $this->setgenerationtime($duedate);
                }

                // If the duedate has passed and the report has already been generated then we don't want to regenerate.
                if ($duedate < time() && $generated) {
                    $this->settogenerate(0);
                    $this->setgenerationtime(null);
                }

                break;

            // Generate on Due Date (only applicable to assignments).
            case TURNITINSIM_REPORT_GEN_DUEDATE:
                $this->settogenerate(1);
                if ($duedate > time()) {
                    $this->setgenerationtime($duedate);
                } else {
                    $this->setgenerationtime(time());
                }
                break;
        }

        return;
    }

    /**
     * Build a user array entry from a passed in user object for submission metadata.
     *
     * @param object $user The user data object.
     * @return mixed
     * @throws dml_exception
     */
    public function build_user_array_entry($user) {

        // If there is no user object return false.
        if (empty($user)) {
             return;
        }

        // Create tsuser object so we get turnitin id.
        $tsuser = new plagiarism_turnitinsim_user($user->id);

        $userdata = array('id' => $tsuser->get_turnitinid());

        if (!get_config('plagiarism_turnitinsim', 'turnitinhideidentity')) {
            $userdata["family_name"] = $user->lastname;
            $userdata["given_name"] = $user->firstname;
            $userdata["email"] = $user->email;
        }
        return $userdata;
    }

    /**
     * Compile metadata for submission request.
     *
     * return mixed
     */
    public function create_group_metadata() {
        global $DB;

        if (!$cm = get_coursemodule_from_id('', $this->getcm())) {
            return false;
        }

        // Add assignment metadata.
        $assignment = array(
            'id'   => $cm->id,
            'name' => $cm->name,
            'type' => $cm->modname == "assign" ? "ASSIGNMENT" : strtoupper($cm->modname)
        );

        // Add course metadata.
        $coursedetails = $DB->get_record('course', array('id' => $cm->course), 'fullname');
        $course = array(
            'id'   => $cm->course,
            'name' => $coursedetails->fullname
        );

        // Get all the instructors in the course.
        $instructors = get_enrolled_users(
            context_module::instance($cm->id),
            'plagiarism/turnitinsim:viewfullreport',
            0, 'u.id, u.firstname, u.lastname, u.email', 'u.id'
        );

        // Add instructors to the owners array.
        foreach ($instructors as $instructor) {
            $course['owners'][] = $this->build_user_array_entry($instructor);
        }

        // Add metadata to request.
        return array(
            'group'         => $assignment,
            'group_context' => $course
        );
    }

    /**
     * Add the owners in to the metadata.
     *
     * return array of userdata / empty
     */
    public function create_owners_metadata() {
        global $DB;
        $owners = array();

        // If this is a group submission then add all group users as owners.
        if (!empty($this->getgroupid())) {
            $groupmembers = groups_get_members($this->getgroupid(), "u.id, u.firstname, u.lastname, u.email", "u.id");
            foreach ($groupmembers as $member) {
                $owners[] = $this->build_user_array_entry($member);
            }
        } else if (!empty($this->getuserid())) {
            $owner = $DB->get_record('user', array('id' => $this->getuserid()));
            $owners[] = $this->build_user_array_entry($owner);
        } else {
            return;
        }

        return $owners;
    }

    /**
     * Return the submission owner, this will be the group id for group submissions.
     *
     * @return integer Turnitin id identifying the owner.
     * @throws dml_exception
     */
    public function get_owner() {
        if (!empty($this->getgroupid())) {
            $tsgroup = new plagiarism_turnitinsim_group($this->getgroupid());
            return $tsgroup->get_turnitinid();
        }

        $tsauthor = new plagiarism_turnitinsim_user($this->getuserid());
        return $tsauthor->get_turnitinid();
    }

    /**
     * Creates a submission record in Turnitin.
     */
    public function create_submission_in_turnitin() {

        $tssubmitter = new plagiarism_turnitinsim_user($this->getsubmitter());
        $filedetails = $this->get_file_details();

        // Initialise request with owner and submitter.
        $request = array(
            'owner' => $this->get_owner(),
            'submitter' => $tssubmitter->get_turnitinid()
        );

        // Add submission title to request.
        if ($filedetails) {
            $request['title'] = str_replace('%20', ' ', rawurlencode($filedetails->get_filename()));
        } else {
            $request['title'] = 'onlinetext_'.$this->id.'_'.$this->cm.'_'.$this->itemid.'.txt';
        }

        // Create group related metadata.
        $request['metadata'] = $this->create_group_metadata();

        // Add owners to the metadata.
        $request['metadata']['owners'] = $this->create_owners_metadata();

        // Add original submission time metadata.
        $request['metadata']['original_submitted_time'] = gmdate("Y-m-d\TH:i:s\Z", time());

        // Add EULA acceptance details to submission if the submitter has accepted it.
        $language = $this->tsrequest->get_language()->localecode;
        $locale = ($tssubmitter->get_lasteulaacceptedlang()) ? $tssubmitter->get_lasteulaacceptedlang() : $language;

        // Get the features enabled so we can check if EULA is required for this tenant.
        $features = json_decode(get_config('plagiarism_turnitinsim', 'turnitin_features_enabled'));

        // Include EULA metadata if necessary.
        if (!empty($tssubmitter->get_lasteulaaccepted()) || !(bool)$features->tenant->require_eula) {
            $request['eula'] = array(
                'accepted_timestamp' => gmdate("Y-m-d\TH:i:s\Z", ($tssubmitter->get_lasteulaacceptedtime())),
                'language' => $locale,
                'version' => $tssubmitter->get_lasteulaaccepted()
            );
        }

        // Include role data.
        $request['owner_default_permission_set'] = TURNITINSIM_ROLE_LEARNER;

        // Send correct user role in request. If owner and submitter are the same, the student submitted.
        if ($this->get_owner() === $tssubmitter->get_turnitinid()) {
            $request['submitter_default_permission_set'] = TURNITINSIM_ROLE_LEARNER;
        } else {
            $request['submitter_default_permission_set'] = TURNITINSIM_ROLE_INSTRUCTOR;
        }

        // Make request to create submission record in Turnitin.
        try {
            $response = $this->tsrequest->send_request(
                TURNITINSIM_ENDPOINT_CREATE_SUBMISSION,
                json_encode($request),
                'POST'
            );
            $responsedata = json_decode($response);

            $this->handle_create_submission_response($responsedata);

        } catch (Exception $e) {
            // This should only ever fail due to a failed connection to Turnitin so we will leave the paper as queued.
            $this->tsrequest->handle_exception($e, 'taskoutputfailedconnection');

            $this->settiiattempts($this->gettiiattempts() + 1);
            $this->settiiretrytime(time() + ($this->gettiiattempts() * TURNITINSIM_SUBMISSION_RETRY_WAIT_SECONDS));
            $this->update();
        }
    }

    /**
     * Handle the API create submission response.
     *
     * @param object $params containing the submission response.
     * @throws coding_exception
     */
    public function handle_create_submission_response($params) {

        switch ($params->httpstatus) {
            case TURNITINSIM_HTTP_CREATED:
                // Handle a TURNITINSIM_HTTP_CREATED response.
                $this->setturnitinid($params->id);
                $this->setstatus($params->status);
                $this->setsubmittedtime(strtotime($params->created_time));

                mtrace(get_string('taskoutputsubmissioncreated', 'plagiarism_turnitinsim', $params->id));

                break;

            case TURNITINSIM_HTTP_UNAVAILABLE_FOR_LEGAL_REASONS:
                // Handle the response for a user who has not accepted the EULA.
                $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED);
                $this->setsubmittedtime(time());
                $this->settiiattempts($this->gettiiattempts() + 1);
                $this->settiiretrytime(time() + ($this->gettiiattempts() * TURNITINSIM_SUBMISSION_RETRY_WAIT_SECONDS));

                mtrace(get_string('taskoutputsubmissionnotcreatedeula', 'plagiarism_turnitinsim'));

                break;

            default:
                $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
                $this->settiiattempts(TURNITINSIM_SUBMISSION_MAX_SEND_ATTEMPTS);
                $this->seterrormessage($params->message);
                mtrace(get_string('taskoutputsubmissionnotcreatedgeneral', 'plagiarism_turnitinsim'));
                $loggingrequestinfo = new plagiarism_turnitinsim_logging_request_info(TURNITINSIM_ENDPOINT_CREATE_SUBMISSION,
                    "POST", null, $params->httpstatus, json_encode($params));
                $loggingrequest = new plagiarism_turnitinsim_logging_request('The submission could not be created in Turnitin');
                $loggingrequest->send_error_to_turnitin($loggingrequestinfo);
                break;
        }

        $this->update();
    }

    /**
     * Uploads a file to the Turnitin submission.
     */
    public function upload_submission_to_turnitin() {
        global $DB, $CFG;

        // Create request body with file attached.
        if ($this->type == "file") {
            $filedetails = $this->get_file_details();

            // Check that the file exists and is not empty.
            if (!$filedetails) {
                $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_EMPTY_DELETED);
                $this->settiiattempts(TURNITINSIM_SUBMISSION_MAX_SEND_ATTEMPTS);
                $this->update();

                return;
            }

            // Encode filename.
            $filename = str_replace('%20', ' ', rawurlencode($filedetails->get_filename()));

            $textcontent = $filedetails->get_content();
        } else {
            // Get cm and modtype.
            $cm = get_coursemodule_from_id('', $this->getcm());

            // Create module object.
            $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;

            if ($cm->modname == "quiz") {
                require_once($CFG->dirroot . '/mod/quiz/locallib.php');

                $quizattempt = $DB->get_record('quiz_attempts', array('id' => $this->getitemid()));
                if (!$quizattempt) {
                    // If the quiz attempt doesn't exist, we don't want to break the cron.
                    mtrace(get_string('taskoutputfailedupload', 'plagiarism_turnitinsim', $this->getturnitinid()));

                    $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
                    $this->seterrormessage(get_string('errorquizattemptnotfound', 'plagiarism_turnitinsim'));
                    $this->settiiattempts(TURNITINSIM_SUBMISSION_MAX_SEND_ATTEMPTS);
                    $this->update();

                    return;
                }

                // Queue each answer to a question.
                $attempt = quiz_attempt::create($this->getitemid());
                foreach ($attempt->get_slots() as $slot) {
                    $qa = $attempt->get_question_attempt($slot);
                    if ($this->getidentifier() == sha1($qa->get_response_summary())) {
                        $textcontent = html_to_text($qa->get_response_summary());
                        $filename = 'onlinetext_'.$this->id.'_'.$this->cm.'_'.$this->itemid.'.txt';
                        break;
                    }
                }
            } else {
                $moduleobject = new $moduleclass;
                // Add text content to request.
                $filename = 'onlinetext_'.$this->id.'_'.$this->cm.'_'.$this->itemid.'.txt';
                $textcontent = html_to_text($moduleobject->get_onlinetext($this->getitemid()));
            }

            // Check that the text exists and is not empty.
            if (empty($textcontent)) {
                $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_EMPTY_DELETED);
                $this->settiiattempts(TURNITINSIM_SUBMISSION_MAX_SEND_ATTEMPTS);
                $this->update();

                return;
            }
        }

        // Add content to request.
        $request = $textcontent;

        // Add additional headers to request.
        $additionalheaders = array(
            'Content-Type: binary/octet-stream',
            'Content-Disposition: inline; filename="'.$filename.'"'
        );

        $this->tsrequest->add_additional_headers($additionalheaders);

        // Make request to add file to submission.
        try {
            $endpoint = TURNITINSIM_ENDPOINT_UPLOAD_SUBMISSION;
            $endpoint = str_replace('{{submission_id}}', $this->getturnitinid(), $endpoint);
            $response = $this->tsrequest->send_request($endpoint, $request, 'PUT', 'submission');
            $responsedata = json_decode($response);

            // Handle response from the API.
            $this->handle_upload_response($responsedata, $filename);
        } catch (Exception $e) {
            $this->tsrequest->handle_exception($e, 'taskoutputfailedupload', $this->getturnitinid());
        }
    }

    /**
     * Handle the API submission response and callback from Turnitin.
     *
     * @param object $params containing the upload response.
     * @param string $filename The name of the file submitted.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function handle_upload_response($params, $filename) {
        // Update submission status.
        mtrace( get_string('taskoutputfileuploaded', 'plagiarism_turnitinsim', $this->getturnitinid()));

        if (!empty($params->httpstatus)) {
            $status = ($params->httpstatus == TURNITINSIM_HTTP_ACCEPTED) ?
                TURNITINSIM_SUBMISSION_STATUS_UPLOADED : TURNITINSIM_SUBMISSION_STATUS_ERROR;
        } else {
            $status = (!empty($params->status) && $params->status == TURNITINSIM_SUBMISSION_STATUS_COMPLETE) ?
                TURNITINSIM_SUBMISSION_STATUS_UPLOADED : TURNITINSIM_SUBMISSION_STATUS_ERROR;
        }

        // On success, reset retries for use in report generation task. On error, never retry.
        if ($status == TURNITINSIM_SUBMISSION_STATUS_UPLOADED) {
            $this->settiiattempts(0);
            $this->settiiretrytime(0);
        } else {
            $this->settiiattempts(TURNITINSIM_SUBMISSION_MAX_SEND_ATTEMPTS);
        }

        $this->setstatus($status);

        // Save error message if request has errored, otherwise send digital receipts.
        if ($status == TURNITINSIM_SUBMISSION_STATUS_ERROR) {
            $this->seterrormessage($params->message);
            $loggingrequestinfo = new plagiarism_turnitinsim_logging_request_info(TURNITINSIM_ENDPOINT_UPLOAD_SUBMISSION,
                "POST", null, 500, json_encode($params));
            $loggingrequest = new plagiarism_turnitinsim_logging_request('Error while uploading the file');
            $loggingrequest->set_submissionid($this->turnitinid);
            $loggingrequest->send_error_to_turnitin($loggingrequestinfo);
        } else {
            $this->send_digital_receipts($filename);
        }
        $this->update();
    }

    /**
     * Handle the API submission info response and callback from Turnitin.
     *
     * @param object $params containing the upload response.
     * @return bool, true if submission status is complete else false.
     */
    public function handle_submission_info_response($params) {
        $issubmissioncomplete = false;

        // Handle scenario if Turnitin API is returning error.
        // Set status as processing, so it will be retried.
        if (!empty($params->httpstatus) && ($params->httpstatus !== TURNITINSIM_HTTP_OK)) {
            $params->status = TURNITINSIM_SUBMISSION_STATUS_PROCESSING;
        }

        // On success, reset retries. On error, never retry.
        if ($params->status === TURNITINSIM_SUBMISSION_STATUS_COMPLETE) {
            $this->reset_retries();
            $issubmissioncomplete = true;
        } else if ($params->status === TURNITINSIM_SUBMISSION_STATUS_PROCESSING) {
            $this->update_report_generation_retries();
        } else {
            $error = isset($params->error_code) ? $params->error_code :
                get_string('submissiondisplaystatus:unknown', 'plagiarism_turnitinsim');
            $this->set_error_with_max_retry_attempts($error, TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS);
            $loggingrequestinfo = new plagiarism_turnitinsim_logging_request_info(TURNITINSIM_ENDPOINT_GET_SUBMISSION_INFO,
                "GET", null, 500, json_encode($params));
            $loggingrequest = new plagiarism_turnitinsim_logging_request($error, $this->tsrequest);
            $loggingrequest->set_submissionid($this->turnitinid);
            $loggingrequest->send_error_to_turnitin($loggingrequestinfo);
        }

        $this->update();

        return $issubmissioncomplete;
    }

    /**
     * Send digital receipts to the instructors and student.
     *
     * @param string $filename The name of the file submitted.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function send_digital_receipts($filename) {
        global $DB;

        // Get user and course details.
        $user = $DB->get_record('user', array('id' => $this->getuserid()));
        $cm = get_coursemodule_from_id('', $this->getcm());
        $course = $DB->get_record('course', array('id' => $cm->course));

        // Send a message to the user's Moodle inbox with the digital receipt.
        $receiptcontent = array(
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'submission_title' => $filename,
            'module_name' => $cm->name,
            'course_fullname' => $course->fullname,
            'submission_date' => date('d-M-Y h:iA', $this->getsubmittedtime()),
            'submission_id' => $this->getturnitinid()
        );

        // Student digital receipt.
        $receipt = new receipt_student();
        $message = $receipt->build_message($receiptcontent);
        $receipt->send_message($user->id, $message, $course->id);

        // Instructor digital receipt.
        $receipt = new receipt_instructor();
        $message = $receipt->build_message($receiptcontent);

        // Get Instructors.
        $instructors = get_enrolled_users(
            context_module::instance($cm->id),
            'plagiarism/turnitinsim:viewfullreport',
            groups_get_activity_group($cm),
            'u.id'
        );

        $receipt->send_message($instructors, $message, $course->id);
    }

    /**
     * Request a Turnitin report to be generated.
     * @param bool $regenerateonduedate if true then set to generate to 0 else calculate generation time.
     */
    public function request_turnitin_report_generation($regenerateonduedate = false) {
        // Get module settings.
        $plugin = new plagiarism_plugin_turnitinsim();
        $modulesettings = $plugin->get_settings($this->getcm());
        $cm = get_coursemodule_from_id('', $this->getcm());

        // Create module helper object.
        $moduleclass = 'plagiarism_turnitinsim_'.$cm->modname;
        $moduleobject = new $moduleclass;

        // Configure request body array.
        $request = array();

        // Indexing settings. Don't index drafts.
        $draft = $moduleobject->is_submission_draft($this->getitemid());
        if (!empty($modulesettings->addtoindex) && !$draft) {
            $request['indexing_settings'] = array('add_to_index' => true);
        }

        // Generation Settings.
        // Configure repositories to search.
        $features = json_decode(get_config('plagiarism_turnitinsim', 'turnitin_features_enabled'));
        $searchrepositories = $features->similarity->generation_settings->search_repositories;
        $request['generation_settings'] = array('search_repositories' => $searchrepositories);
        $request['generation_settings']['auto_exclude_self_matching_scope'] = TURNITINSIM_REPORT_GEN_EXCLUDE_SELF_GROUP;

        // View Settings.
        $request['view_settings'] = array(
            'exclude_quotes' => (!empty($modulesettings->excludequotes)) ? true : false,
            'exclude_bibliography' => (!empty($modulesettings->excludebiblio)) ? true : false
        );

        // Make request to generate report.
        try {
            $endpoint = TURNITINSIM_ENDPOINT_SIMILARITY_REPORT;
            $endpoint = str_replace('{{submission_id}}', $this->getturnitinid(), $endpoint);
            $response = $this->tsrequest->send_request($endpoint, json_encode($request), 'PUT');
            $responsedata = json_decode($response);

            // Update submission status.
            mtrace('Turnitin Originality Report requested for: '.$this->getturnitinid());

            if ($responsedata->httpstatus == TURNITINSIM_HTTP_ACCEPTED) {
                $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_REQUESTED);

                if ($responsedata->message == TURNITINSIM_SUBMISSION_STATUS_CANNOT_EXTRACT_TEXT) {
                    $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
                    $this->seterrormessage($responsedata->message);
                }
            } else {
                $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
                $this->seterrormessage($responsedata->message);
            }

            $this->setrequestedtime(time());

            if ($regenerateonduedate) {
                $this->settogenerate(0);
                $this->setgenerationtime(time());
            } else {
                $this->calculate_generation_time(true);
            }
            $this->update();
        } catch (Exception $e) {
            $this->tsrequest->handle_exception($e, 'taskoutputfailedreportrequest', $this->getturnitinid());
        }
    }

    /**
     * Request a report score from Turnitin.
     */
    public function request_turnitin_report_score() {

        // Make request to get report score.
        try {
            $endpoint = TURNITINSIM_ENDPOINT_SIMILARITY_REPORT;
            $endpoint = str_replace('{{submission_id}}', $this->getturnitinid(), $endpoint);
            $response = $this->tsrequest->send_request($endpoint, json_encode(array()), 'GET');
            $responsedata = json_decode($response);

            $this->handle_similarity_response($responsedata);
        } catch (Exception $e) {

            $this->tsrequest->handle_exception($e, 'taskoutputfailedscorerequest', $this->getturnitinid());
        }
    }

    /**
     * Handle the API similarity response and callback from Turnitin.
     *
     * @param object $params containing the similarity score response.
     * @throws coding_exception
     */
    public function handle_similarity_response($params) {
        if (isset($params->status)) {
            if ($params->status != "COMPLETE") {
                mtrace('Turnitin Originality Report score could not be retrieved for: ' . $this->getturnitinid());

                // Check if the file actually exists in Turnitin. Make a call here.
                $response = $this->get_submission_info();

                switch ($response->status) {
                    case TURNITINSIM_SUBMISSION_STATUS_CREATED:
                        // Submission has been created but no file has been uploaded. Reprocess and allow retry in an hour.
                        $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_QUEUED);
                        $this->settiiattempts($this->gettiiattempts() + 1);
                        $this->settiiretrytime(time() + ($this->gettiiattempts() * TURNITINSIM_REPORT_GEN_RETRY_WAIT_SECONDS));

                        // Error message should come from the call for the report.
                        if ($this->gettiiattempts() == TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS) {
                            $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
                            $this->seterrormessage($params->message);
                        }

                        break;
                    case TURNITINSIM_SUBMISSION_STATUS_ERROR:
                        // An error occurred during submission processing. Don't allow retry.
                        $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
                        $this->settiiattempts(TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS);

                        // Error message should come from the call to get the submission info.
                        $this->seterrormessage($response->message);

                        break;
                    default:
                        // File contents have been uploaded and the submission is being processed.
                        // Status of TURNITINSIM_SUBMISSION_STATUS_PROCESSING.
                        // Or get_submission_info() returns no response. Allow another try in an hour.
                        $this->settiiattempts($this->gettiiattempts() + 1);
                        $this->settiiretrytime(time() + ($this->gettiiattempts() * TURNITINSIM_REPORT_GEN_RETRY_WAIT_SECONDS));

                        if ($this->gettiiattempts() == TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS) {
                            $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
                            $this->seterrormessage(get_string('submissiondisplaystatus:unknown', 'plagiarism_turnitinsim'));
                        }
                        break;
                }

            } else {
                mtrace('Turnitin Originality Report score retrieved for: ' . $this->getturnitinid());

                $this->setstatus($params->status);
                $this->settiiattempts($this->gettiiattempts() + 1);
                $this->seterrormessage('');
            }
        }

        if (isset($params->overall_match_percentage)) {
            $this->setoverallscore($params->overall_match_percentage);
            $this->calculate_generation_time(true);
        }
        $this->update();
    }

    /**
     * Get the details for a submission from the Moodle database.
     *
     * @param array $linkarray The linkarray given by Moodle containing module data.
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function get_submission_details($linkarray) {
        global $DB;
        static $cm;

        $quizanswer = 0;

        if (empty($cm)) {
            $cm = get_coursemodule_from_id('', $linkarray["cmid"]);

            if ($cm->modname == 'forum') {
                if (! $forum = $DB->get_record("forum", array("id" => $cm->instance))) {
                    print_error('invalidforumid', 'forum');
                }
            }
        }

        // To uniquely identify the quiz answer.
        if (!empty($linkarray["component"]) && $linkarray["component"] == "qtype_essay") {
            $quizanswer = $linkarray['area'].'-'.$linkarray['itemid'];
        }

        if (!empty($linkarray['file'])) {
            $file = $linkarray['file'];
            $itemid = $file->get_itemid();
            $identifier = $file->get_pathnamehash();

            // Get correct user id that submission is for rather than who submitted it this only affects
            // mod_assign file submissions and group submissions.
            if ($itemid != 0 && $cm->modname == "assign") {
                $assignsubmission = $DB->get_record('assign_submission', array('id' => $itemid), 'groupid, userid');

                if (empty($assignsubmission->userid)) {
                    // Group submission.
                    return $DB->get_record('plagiarism_turnitinsim_sub', array('itemid' => $itemid,
                        'cm' => $linkarray['cmid'], 'identifier' => $identifier));
                } else {
                    // Submitted on behalf of student.
                    $linkarray['userid'] = $assignsubmission->userid;
                }
            }
        } else if (!empty($linkarray["content"])) {
            $identifier = sha1($linkarray['content']);

            // If user id is empty this must be a group submission.
            if (empty($linkarray['userid'])) {
                return $DB->get_record('plagiarism_turnitinsim_sub', array('identifier' => $identifier,
                    'type' => 'content', 'cm' => $linkarray['cmid'], 'quizanswer' => $quizanswer));
            }
        }

        return $DB->get_record('plagiarism_turnitinsim_sub', array('userid' => $linkarray['userid'],
            'cm' => $linkarray['cmid'], 'identifier' => $identifier, 'quizanswer' => $quizanswer));

    }

    /**
     * Create the cloud viewer permissions array to send when requesting a viewer launch URL.
     *
     * @return array
     * @throws dml_exception
     */
    public function create_report_viewer_permissions() {
        $turnitinviewerviewfullsource = get_config('plagiarism_turnitinsim', 'turnitinviewerviewfullsource');
        $turnitinviewermatchsubinfo = get_config('plagiarism_turnitinsim', 'turnitinviewermatchsubinfo');
        $turnitinviewersavechanges = get_config('plagiarism_turnitinsim', 'turnitinviewersavechanges');

        return array(
            'may_view_submission_full_source' => (!empty($turnitinviewerviewfullsource)) ? true : false,
            'may_view_match_submission_info' => (!empty($turnitinviewermatchsubinfo)) &&
            !$this->is_submission_anonymous() ? true : false,
            'may_view_save_viewer_changes' => (!empty($turnitinviewersavechanges)) ? true : false
        );
    }

    /**
     * Create the similarity report settings overrides to send when requesting a viewer launch URL.
     *
     * These are true but may be configurable in the future.
     *
     * @param string $viewerdefaultpermissionset The user role.
     * @return array
     * @throws dml_exception
     */
    public function create_similarity_overrides($viewerdefaultpermissionset) {
        $turnitinviewersavechanges = get_config('plagiarism_turnitinsim', 'turnitinviewersavechanges');

        return array(
            'modes' => array(
                'match_overview' => true,
                'all_sources' => true
            ),
            "view_settings" => array(
                "save_changes"  => (!empty($turnitinviewersavechanges) &&
                    $viewerdefaultpermissionset !== TURNITINSIM_ROLE_LEARNER) ? true : false
            )
        );
    }

    /**
     * Request Cloud Viewer Launch URL.
     */
    public function request_cv_launch_url() {
        global $DB, $USER;

        // Make request to get cloud viewer launch url.
        $endpoint = TURNITINSIM_ENDPOINT_CV_LAUNCH;
        $endpoint = str_replace('{{submission_id}}', $this->getturnitinid(), $endpoint);

        // Build request.
        $lang = $this->tsrequest->get_language();
        $viewinguser = new plagiarism_turnitinsim_user($USER->id);
        $request = array(
            "locale" => $lang->langcode,
            "viewer_user_id" => $viewinguser->get_turnitinid()
        );

        // If submission is anonymous then do not send the student name.
        if (!$this->is_submission_anonymous()) {
            // Get submitter's details.
            $author = $DB->get_record('user', array('id' => $this->getuserid()));

            $request['given_name'] = $author->firstname;
            $request['family_name'] = $author->lastname;
        }

        // Send correct user role in request.
        if (has_capability('plagiarism/turnitinsim:viewfullreport', context_module::instance($this->getcm()))) {
            $request['viewer_default_permission_set'] = TURNITINSIM_ROLE_INSTRUCTOR;
            // Override viewer permissions depending on admin options.
            $request['viewer_permissions'] = $this->create_report_viewer_permissions();
        } else {
            $request['viewer_default_permission_set'] = TURNITINSIM_ROLE_LEARNER;
        }

        // Add similarity overrides - all true for now but this may change in future.
        $request['similarity'] = $this->create_similarity_overrides($request['viewer_default_permission_set']);

        // Make request to get Cloud Viewer URL.
        try {
            $response = $this->tsrequest->send_request($endpoint, json_encode($request), 'POST');
            return $response;
        } catch (Exception $e) {
            $this->tsrequest->handle_exception($e, 'taskoutputfailedcvlaunchurl', $this->getturnitinid());
        }

        return null;
    }

    /**
     * Check whether the submission is anonymous.
     *
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public function is_submission_anonymous() {
        global $DB;

        // Get module details.
        $cm = get_coursemodule_from_id('', $this->getcm());
        $moduledata = $DB->get_record($cm->modname, array('id' => $cm->instance));

        $blindmarkingon = !empty($moduledata->blindmarking);
        $identitiesrevealed = !empty($moduledata->revealidentities);

        // Return true if hide identities is on, otherwise go by module blind marking settings.
        $turnitinhideidentity = get_config('plagiarism_turnitinsim', 'turnitinhideidentity');
        if ($turnitinhideidentity) {
            $anon = true;
        } else {
            $anon = $blindmarkingon && !$identitiesrevealed;
        }

        return $anon;
    }

    /**
     * Get the submission details for a submission from Turnitin.
     *
     * @return bool|mixed
     * @throws coding_exception
     */
    public function get_submission_info() {
        try {
            $endpoint = TURNITINSIM_ENDPOINT_GET_SUBMISSION_INFO;
            $endpoint = str_replace('{{submission_id}}', $this->getturnitinid(), $endpoint);
            $response = $this->tsrequest->send_request($endpoint, json_encode(array()), 'GET');

            return json_decode($response);
        } catch (Exception $e) {
            $logger = new plagiarism_turnitinsim_logger();
            $logger->error(get_string('errorgettingsubmissioninfo', 'plagiarism_turnitinsim'));

            $response = new stdClass();
            $response->status = false;

            return $response;
        }
    }

    /**
     * Get the path to the file from the pathnamehash
     *
     * @return bool|stored_file $filepath
     */
    public function get_file_details() {
        $fs = get_file_storage();
        $file = $fs->get_file_by_hash($this->getidentifier());

        return $file;
    }

    /**
     * Get the submission ID.
     *
     * @return int
     */
    public function getid() {
        return $this->id;
    }

    /**
     * Set the submission ID.
     *
     * @param int $id
     */
    public function setid($id) {
        $this->id = $id;
    }

    /**
     * Get the course module.
     *
     * @return object
     */
    public function getcm() {
        return $this->cm;
    }

    /**
     * Set the course module.
     *
     * @param int $cm
     */
    public function setcm($cm) {
        $this->cm = $cm;
    }

    /**
     * Get the user ID.
     *
     * @return int
     */
    public function getuserid() {
        return $this->userid;
    }

    /**
     * Set the user ID.
     *
     * @param int $userid
     */
    public function setuserid($userid) {
        $this->userid = $userid;
    }

    /**
     * Get the group ID.
     *
     * @return mixed
     */
    public function getgroupid() {
        return $this->groupid;
    }

    /**
     * Set the group ID.
     *
     * @param mixed $groupid
     */
    public function setgroupid($groupid) {
        $this->groupid = $groupid;
    }

    /**
     * Get the submitter of the paper.
     *
     * @return mixed
     */
    public function getsubmitter() {
        return $this->submitter;
    }

    /**
     * Set the submitter of the paper.
     *
     * @param mixed $submitter
     */
    public function setsubmitter($submitter) {
        $this->submitter = $submitter;
    }

    /**
     * Get the Turnitin submission ID.
     *
     * @return int
     */
    public function getturnitinid() {
        return $this->turnitinid;
    }

    /**
     * Set the Turnitin submission ID.
     *
     * @param int $turnitinid
     */
    public function setturnitinid($turnitinid) {
        $this->turnitinid = $turnitinid;
    }

    /**
     * Get the submission status.
     *
     * @return mixed
     */
    public function getstatus() {
        return $this->status;
    }

    /**
     * Set the submission status.
     *
     * @param mixed $status
     */
    public function setstatus($status) {
        $this->status = $status;
    }

    /**
     * Get the Moodle identifier for the file.
     *
     * @return mixed
     */
    public function getidentifier() {
        return $this->identifier;
    }

    /**
     * Set the Moodle identifier for the file.
     *
     * @param mixed $identifier
     */
    public function setidentifier($identifier) {
        $this->identifier = $identifier;
    }

    /**
     * Get the time the submission was made.
     *
     * @return mixed
     */
    public function getsubmittedtime() {
        return $this->submittedtime;
    }

    /**
     * Set the time the submission was made.
     *
     * @param mixed $submittedtime
     */
    public function setsubmittedtime($submittedtime) {
        $this->submittedtime = $submittedtime;
    }

    /**
     * Get the originality report.
     *
     * @return mixed
     */
    public function getoverallscore() {
        return $this->overallscore;
    }

    /**
     * Set the originality report.
     *
     * @param mixed $overallscore
     */
    public function setoverallscore($overallscore) {
        $this->overallscore = $overallscore;
    }

    /**
     * Get the Moodle itemid for the submission.
     * @return mixed
     */
    public function getitemid() {
        return $this->itemid;
    }

    /**
     * Set the Moodle itemid for the submission.
     *
     * @param mixed $itemid
     */
    public function setitemid($itemid) {
        $this->itemid = $itemid;
    }

    /**
     * Get the time the originality report was requested.
     *
     * @return mixed
     */
    public function getrequestedtime() {
        return $this->requestedtime;
    }

    /**
     * Set the time the originality report was requested.
     *
     * @param mixed $requestedtime
     */
    public function setrequestedtime($requestedtime) {
        $this->requestedtime = $requestedtime;
    }

    /**
     * Get the error nessage, if the submission did not complete successfully.
     *
     * @return mixed
     */
    public function geterrormessage() {
        return $this->errormessage;
    }

    /**
     * Set the error nessage, if the submission did not complete successfully.
     *
     * @param mixed $errormessage
     */
    public function seterrormessage($errormessage) {
        $this->errormessage = $errormessage;
    }

    /**
     * Get the type of submission, for example text or file.
     *
     * @return mixed
     */
    public function gettype() {
        return $this->type;
    }

    /**
     * Set the type of submission, for example text or file.
     *
     * @param mixed $type
     */
    public function settype($type) {
        $this->type = $type;
    }

    /**
     * Get whether or not the submission is still to be generated.
     * @return mixed
     */
    public function gettogenerate() {
        return $this->togenerate;
    }

    /**
     * Set whether or not the submission is still to be generated.
     *
     * @param mixed $togenerate
     */
    public function settogenerate($togenerate) {
        $this->togenerate = $togenerate;
    }

    /**
     * Get the time the originality report was generated.
     *
     * @return mixed
     */
    public function getgenerationtime() {
        return $this->generationtime;
    }

    /**
     * Get the time the originality report was generated.
     *
     * @param mixed $generationtime
     */
    public function setgenerationtime($generationtime) {
        $this->generationtime = $generationtime;
    }

    /**
     * Get the number of report generation attempts.
     *
     * @return int
     */
    public function gettiiattempts() {
        return $this->tiiattempts;
    }

    /**
     * Set the number of report generation attempts.
     *
     * @param string $tiiattempts
     */
    public function settiiattempts($tiiattempts) {
        $this->tiiattempts = $tiiattempts;
    }

    /**
     * Get the time of the next retry.
     *
     * @return int
     */
    public function gettiiretrytime() {
        return $this->tiiretrytime;
    }

    /**
     * Set the time of the next retry.
     *
     * @param string $tiiretrytime
     */
    public function settiiretrytime($tiiretrytime) {
        $this->tiiretrytime = $tiiretrytime;
    }

    /**
     * Get the key for the quiz answer.
     *
     * @return string
     */
    public function getquizanswer() {
        return $this->quizanswer;
    }

    /**
     * Set the key for the quiz answer.
     *
     * @param string $quizanswer The key for the quiz answer, made up of questionusageid and slot.
     */
    public function setquizanswer($quizanswer) {
        $this->quizanswer = $quizanswer;
    }

    /**
     * Reset retries.
     */
    private function reset_retries() {
        $this->settiiattempts(0);
        $this->settiiretrytime(0);
    }

    /**
     * Common method to update maximum retry attempts and error Status.
     *
     * @param string $error Error code from Turnitin.
     * @param int $retryattempts Maximum retry attempts.
     */
    public function set_error_with_max_retry_attempts($error, $retryattempts) {
        $this->settiiattempts($retryattempts);
        $this->seterrormessage($error);
        $this->setstatus(TURNITINSIM_SUBMISSION_STATUS_ERROR);
    }

    /**
     * Update report generation retries limit and time.
     */
    private function update_report_generation_retries() {
        $this->settiiattempts($this->gettiiattempts() + 1);
        // On first attempt set retry time as 15 minutes.
        if ($this->gettiiattempts() === 1) {
            $this->settiiretrytime(time() + TURNITINSIM_REPORT_GEN_FIRST_ATTEMPT_RETRY_WAIT_SECONDS);
        } else if ($this->gettiiattempts() === TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS) {
            $this->set_error_with_max_retry_attempts(get_string('submissiondisplaystatus:unknown', 'plagiarism_turnitinsim'),
                TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS);
        } else {
            $this->settiiretrytime(time() + ($this->gettiiattempts() * TURNITINSIM_REPORT_GEN_RETRY_WAIT_SECONDS));
        }
    }
}
