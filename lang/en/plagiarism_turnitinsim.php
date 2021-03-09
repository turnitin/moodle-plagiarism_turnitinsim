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
 * Strings for plagiarism_turnitinsim component, language 'en'
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2020 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['accessoptions'] = 'Student access';
$string['accessoptions_help'] = 'Students will have access to the Similarity Report for their submission after it generates.';
$string['accessstudents'] = 'Allow students to view Similarity Reports';
$string['addtoindex'] = 'Index all submissions';
$string['code'] = 'Code';
$string['dbexport'] = 'Database export';
$string['dbexporttable'] = 'Export {$a} data';
$string['defaultsettings'] = 'Default settings';
$string['errortoolarge'] = 'This file will not be submitted to Turnitin as it exceeds the maximum size of {$a} allowed';
$string['eulaaccept'] = 'I accept the Turnitin EULA';
$string['eulaaccepted'] = 'Thank you for accepting the new Turnitin EULA. All future submissions will now be sent to Turnitin for processing.';
$string['eulaalreadyaccepted'] = 'You have already accepted the latest Turnitin EULA.';
$string['euladecline'] = 'I decline the Turnitin EULA';
$string['euladeclined'] = 'Your submissions will not be sent to Turnitin as you have not accepted the Turnitin End User Licence Agreement.';
$string['eulanotrequired'] = 'You are not required to accept the Turnitin EULA.';
$string['eulaheader'] = 'Turnitin End User Licence Agreement';
$string['eulalink'] = 'For this submission to be sent to Turnitin, you must accept the <a href="{$a}" target="_blank">Turnitin End User Licence Agreement</a>.';
$string['eulalinkgeneric'] = 'If you would like any of your future submissions to be sent to Turnitin, you must accept the <a href="{$a}" target="_blank">Turnitin End User Licence Agreement</a>.';
$string['eulalinkmodal'] = 'For this submission to be sent to Turnitin, you must accept the <a href="#" class="eula_link">Turnitin End User Licence Agreement</a>.';
$string['excludebiblio'] = 'Bibliography';
$string['excludeoptions'] = 'Exclude from Similarity Reports';
$string['excludeoptions_help'] = 'Selected options will not show as a match in Similarity Reports.';
$string['excludequotes'] = 'Quotes';
$string['faultcode'] = 'Fault code';
$string['getwebhookfailure:subject'] = 'Turnitin webhook check failure';
$string['getwebhookfailure:message'] = 'There may be a problem with the webhook you have registered with Turnitin for the Plagiarism Plugin. The scheduled task to check it has failed to connect to Turnitin. Please check your logs.';
$string['indexoptions'] = 'Submission indexing';
$string['indexoptions_help'] = 'Indexed submissions will be available for comparison in Similarity Reports.';
$string['invalidtablename'] = 'Table {$a} could not be exported';
$string['line'] = 'Line';
$string['message'] = 'Message';
$string['messageprovider:digital_receipt_student'] = 'Turnitin Student Digital Receipt';
$string['messageprovider:digital_receipt_instructor'] = 'Turnitin Instructor Digital Receipt';
$string['messageprovider:get_webhook_failure'] = 'Turnitin webhook check failure';
$string['messageprovider:new_eula'] = 'Turnitin new EULA release';
$string['neweula:subject'] = 'Turnitin new EULA released';
$string['neweula:message'] = 'Turnitin have released a new EULA, for further information please click <a href="{$a}">here</a>.';
$string['pluginname'] = 'Turnitin Integrity plugin';
$string['pluginsetup'] = 'Setup';
$string['queuedrafts'] = 'Process draft submissions';
$string['queuedrafts_help'] = 'Please note that draft submissions will not be indexed in Turnitin for checking against';
$string['receiptstudent:subject'] = 'This is your Turnitin Digital Receipt';
$string['receiptstudent:message'] = 'Dear {$a->firstname} {$a->lastname},<br /><br />Your file <strong>{$a->submission_title}</strong> to the module <strong>{$a->module_name}</strong> in the class <strong>{$a->course_fullname}</strong> has successfully been submitted to Turnitin on <strong>{$a->submission_date}</strong>. Your submission id is <strong>{$a->submission_id}</strong>.<br /><br />Thank you for using Turnitin,<br /><br />The Turnitin Team';
$string['receiptsinstructor:subject'] = 'Submission sent to Turnitin';
$string['receiptsinstructor:message'] = 'A submission entitled <strong>{$a->submission_title}</strong> made to the module <strong>{$a->module_name}</strong> in the class <strong>{$a->course_fullname}</strong> has been sent to Turnitin.<br /><br />Submission ID: <strong>{$a->submission_id}</strong><br />Submission Date: <strong>{$a->submission_date}</strong>';
$string['reportgenoptions'] = 'Generate Similarity Reports';
$string['reportgenoptions_help'] = '<strong>Immediately:</strong> Similarity Reports will generate immediately after the file has been submitted.<br/><br/><strong>On due date:</strong> Similarity Reports will only generate on the due date of the assignment.<br/><br/><strong>Immediately and on due date:</strong> A Similarity Report will generate immediately after the file has been submitted. The Similarity Report will generate again on the due date of the assignment. This option can be used to check for collusion within a class.';
$string['reportgen0'] = 'Immediately';
$string['reportgen1'] = 'Immediately and regenerate on due date';
$string['reportgen2'] = 'Due Date';
$string['savesuccess'] = 'Changes saved';
$string['settingslearnmore'] = 'Learn more about Turnitin settings';
$string['submissiondisplaystatus:awaitingeula'] = 'Awaiting EULA';
$string['submissiondisplaystatus:pending'] = 'Pending';
$string['submissiondisplaystatus:queued'] = 'Queued';
$string['submissiondisplaystatus:notsent'] = 'Not sent';
$string['submissiondisplaystatus:error'] = 'Error';
$string['submissiondisplaystatus:unknown'] = 'Unknown error';
$string['submissiondisplaystatus:cannotextracttext'] = 'Report Unavailable';
$string['submissiondisplayerror:notsent'] = 'File not sent to Turnitin';
$string['submissiondisplayerror:notsent_help'] = 'This file has not been submitted to Turnitin because Turnitin was not enabled at the time of submission, please modify or re-upload your submission if you would like it to be sent to Turnitin.';
$string['submissiondisplayerror:generic'] = 'File not sent to Turnitin';
$string['submissiondisplayerror:generic_help'] = 'This file has not been submitted to Turnitin, please consult your system administrator';
$string['submissiondisplayerror:unknown'] = 'Error with your submission';
$string['submissiondisplayerror:unknown_help'] = 'There was an unknown error with your submission and this file has not been submitted to Turnitin, please consult your system administrator';
$string['submissiondisplayerror:unsupportedfiletype'] = 'Unsupported filetype';
$string['submissiondisplayerror:unsupportedfiletype_help'] = 'The uploaded filetype is not supported.';
$string['submissiondisplayerror:processingerror'] = 'Processing error';
$string['submissiondisplayerror:processingerror_help'] = 'An unspecified error occurred while processing the submissions.';
$string['submissiondisplayerror:cannotextracttext'] = 'Report Unavailable';
$string['submissiondisplayerror:cannotextracttext_help'] = 'A report could not be generated for this submission. This may be because this type of file cannot generate a report or there is not enough text.';
$string['submissiondisplayerror:toolarge'] = 'File is too large';
$string['submissiondisplayerror:toolarge_help'] = 'This file is too large to send to Turnitin. To check for Originality, please submit a file below 100MB in size.';
$string['submissiondisplayerror:toolittletext'] = 'Not enough text';
$string['submissiondisplayerror:toolittletext_help'] = 'The submission does not have enough text to generate a Similarity Report (a submission must contain at least 20 words)';
$string['submissiondisplayerror:toomuchtext'] = 'Too much text';
$string['submissiondisplayerror:toomuchtext_help'] = 'The submission has too much text to generate a Similarity Report (after extracted text is converted to UTF-8, the submission must contain less than {$a} of text)';
$string['submissiondisplayerror:toomanypages'] = 'Too many pages';
$string['submissiondisplayerror:toomanypages_help'] = 'The submission has too many pages to generate a Similarity Report (a submission cannot contain more than 400 pages)';
$string['submissiondisplayerror:filelocked'] = 'File locked';
$string['submissiondisplayerror:filelocked_help'] = 'The uploaded file requires a password in order to be opened.';
$string['submissiondisplayerror:corruptfile'] = 'Corrupt file';
$string['submissiondisplayerror:corruptfile_help'] = 'The uploaded file appears to be corrupt.';
$string['submissiondisplayerror:eulanotaccepted'] = 'EULA not accepted';
$string['submissiondisplayerror:eulanotaccepted_help'] = 'The Turnitin EULA needs to be accepted by the submitter before the submission can be checked for Similarity.';
$string['resubmittoturnitin'] = 'Resubmit to Turnitin';
$string['taskadminupdate'] = 'Update local configuration for Turnitin Integrity Plugin';
$string['taskgetreportscores'] = 'Fetch Report Scores for Turnitin Integrity Plugin';
$string['taskoutputenabledfeaturesretrieved'] = 'Turnitin enabled features retrieved';
$string['taskoutputenabledfeaturesnotretrieved'] = 'Turnitin enabled features could not be retrieved';
$string['taskoutputenabledfeaturesretrievalfailure'] = 'Turnitin enabled features call failed';
$string['taskoutputlatesteularetrieved'] = 'EULA version {$a} retrieved.';
$string['taskoutputlatesteulanotretrieved'] = 'Latest EULA version could not be retrieved';
$string['taskoutputlatesteularetrievalfailure'] = 'Latest EULA version call failed.';
$string['taskoutputfailedconnection'] = 'There was a problem connecting to the Turnitin API';
$string['taskoutputfailedcvlaunchurl'] = 'There was a problem requesting a Turnitin Viewer URL from the Turnitin API for submission id: {$a}';
$string['taskoutputfailedreportrequest'] = 'There was a problem requesting an originality report generation from the Turnitin API for submission id: {$a}';
$string['taskoutputfailedscorerequest'] = 'There was a problem requesting an originality report score from the Turnitin API for submission id: {$a}';
$string['taskoutputfailedupload'] = 'There was a problem uploading a file to the Turnitin API for submission id: {$a}';
$string['taskoutputfileuploaded'] = 'File uploaded to Turnitin submission: {$a}';
$string['taskoutputpluginnotconfigured'] = 'The task to {$a} could not be run as the Turnitin Integrity plugin is not configured. <br/>Please go to the Plugin configuration page in Site Administration to add your Turnitin API Credentials.';
$string['taskoutputsubmissioncreated'] = 'Submission created in Turnitin: {$a}';
$string['taskoutputsubmissionnotcreatedeula'] = 'The submission could not be created in Turnitin because the submitter has not accepted the EULA.';
$string['taskoutputsubmissionnotcreatedgeneral'] = 'The submission could not be created in Turnitin. Please consult your logs.';
$string['taskoutputwebhookcreationfailure'] = 'Webhook creation request failed. Please consult your logs.';
$string['taskoutputwebhookcreated'] = 'Webhook created. Turnitin will send callbacks to {$a}';
$string['taskoutputwebhooknotcreated'] = 'Webhook could not be created. Please consult your logs.';
$string['taskoutputwebhookdeleted'] = 'Webhook {$a} has been deleted.';
$string['taskoutputwebhooknotdeleted'] = 'Webhook {$a} could not be deleted.';
$string['taskoutputwebhookdeletefailure'] = 'Webhook could not be deleted. Please consult your logs.';
$string['taskoutputwebhookretrieved'] = 'Webhook {$a} retrieved. Webhook is active.';
$string['taskoutputwebhooknotretrieved'] = 'Webhook {$a} could not be retrieved. A new webhook will be created';
$string['taskoutputwebhookretrievalfailure'] = 'Webhook {$a} retrieval call failed.';
$string['tasksendqueuedsubmissions'] = 'Send Queued Files from the Turnitin Integrity Plugin';
$string['turnitinsim'] = 'Turnitin Integrity plugin';
$string['turnitinsim:enable'] = 'Enable Turnitin Integrity';
$string['turnitinsim:viewfullreport'] = 'View Originality Report';
$string['turnitinapikey'] = 'Turnitin API Key';
$string['turnitinapiurl'] = 'Turnitin API URL';
$string['turnitinsiminternet'] = 'Check against internet content';
$string['turnitinsimprivate'] = 'Check against private content';
$string['turnitinconfig'] = 'Plugin configuration';
$string['turnitinenablelogging'] = 'Enable diagnostic mode';
$string['turnitinenableremotelogging'] = 'Send logs to Turnitin';
$string['turnitinenableremotelogging_help'] = 'Logs will automatically be sent to Turnitin for diagnostic purposes. No private data is transferred. We recommend this setting remains enabled so we can quickly provide support if needed. However, logs can still be found in your Moodle environment to manually send to Turnitin.';
$string['turnitinfeatures'] = 'Turnitin Integrity features';
$string['turnitinfeatures::header'] = 'Turnitin Integrity features';
$string['turnitinfeatures::moreinfo'] = 'For more information on the enabled features and packages available from Turnitin please see <a href="http://www.turnitin.com" target="_blank">http://www.turnitin.com</a>.';
$string['turnitinfeatures::repositories'] = 'Repositories checked against: ';
$string['turnitinfeatures::viewoptions'] = 'Turnitin Viewer options: ';
$string['turnitinfeatures::eularequired'] = 'Acceptance of the Turnitin EULA is required for all users';
$string['turnitinfeatures::eulanotrequired'] = 'Acceptance of the Turnitin EULA is not required by users';
$string['turnitinhideidentity'] = 'Hide student\'s identity from Turnitin';
$string['turnitinmodenabled'] = 'Enable Turnitin Integrity for {$a}';
$string['turnitinpluginenabled'] = 'Enable Turnitin';
$string['turnitinpluginsettings'] = 'Turnitin Integrity plugin settings';
$string['turnitinviewerviewfullsource'] = 'Allow instructors within your institution to view the full text of submissions for internal matches';
$string['turnitinviewermatchsubinfo'] = 'Allow instructors within your institution to view submission information for internal matches';
$string['turnitinviewersavechanges'] = 'Save viewer changes';
$string['viewerpermissionferpa'] = 'The following permissions impact how data can be shared across your institution. This data is the exclusive responsibility of your institution so when setting these permissions, consider whether they fully comply with your institution’s policies regarding student records.';
$string['viewerpermissionoptions'] = 'Viewer permissions';
$string['viewlogs'] = 'Logs';
$string['viewapilog'] = 'View API logs from {$a}';
$string['webhook_description'] = 'Webhook for {$a}';
$string['webhookincorrectsignature'] = 'Webhook callback failed as signature is incorrect';
$string['connecttest'] = 'Test Turnitin connection';
$string['connecttestsuccess'] = 'Connection test successful';
$string['connecttestfailed'] = 'Connection test failed.';

$string['privacy:metadata:plagiarism_turnitinsim_sub'] = 'Information that links a Moodle submission to a Turnitin submission.';
$string['privacy:metadata:plagiarism_turnitinsim_sub:userid'] = 'The ID of the user who has made a submission.';
$string['privacy:metadata:plagiarism_turnitinsim_sub:turnitinid'] = 'The ID used by Turnitin to reference the submission.';
$string['privacy:metadata:plagiarism_turnitinsim_sub:overallscore'] = 'The overall similarity score of the submission.';
$string['privacy:metadata:plagiarism_turnitinsim_sub:submittedtime'] = 'A timestamp indicating when the user\'s submission was sent to Turnitin.';
$string['privacy:metadata:plagiarism_turnitinsim_sub:identifier'] = 'A hash used by Moodle to identify the file submitted.';
$string['privacy:metadata:plagiarism_turnitinsim_sub:itemid'] = 'Id that identifies the submission for the relevant module type.';

$string['privacy:metadata:plagiarism_turnitinsim_users'] = 'Information that links a Moodle user to a Turnitin user.';
$string['privacy:metadata:plagiarism_turnitinsim_users:userid'] = 'The ID of the user who has made a submission.';
$string['privacy:metadata:plagiarism_turnitinsim_users:turnitinid'] = 'The ID used by Turnitin to reference the user.';
$string['privacy:metadata:plagiarism_turnitinsim_users:lasteulaaccepted'] = 'The last version of the Turnitin EULA accepted by the user.';
$string['privacy:metadata:plagiarism_turnitinsim_users:lasteulaacceptedtime'] = 'A timestamp indicating when user last accepted the Turnitin EULA.';
$string['privacy:metadata:plagiarism_turnitinsim_users:lasteulaacceptedlang'] = 'The langauge in which the user last accepted the Turnitin EULA.';

$string['privacy:metadata:plagiarism_turnitinsim_client'] = 'To successfully make a submission to Turnitin, specific user data needs to be exchanged between Moodle and Turnitin. For more information around Moodle Plugins and GDPR, please visit: https://help.turnitin.com/feedback-studio/moodle/moodle-plugins-and-gdpr.htm';
$string['privacy:metadata:plagiarism_turnitinsim_client:firstname'] = 'The user\'s first name is sent to Turnitin on a Turnitin Viewer launch so that the user can be identified.';
$string['privacy:metadata:plagiarism_turnitinsim_client:lastname'] = 'The user\'s last name is sent to Turnitin on a Turnitin Viewer launch so that the user can be identified.';
$string['privacy:metadata:plagiarism_turnitinsim_client:submission_title'] = 'The title of the submission is sent to Turntin so that it is identifiable.';
$string['privacy:metadata:plagiarism_turnitinsim_client:submission_filename'] = 'The name of the submitted file is sent to Turntin so that it is identifiable.';
$string['privacy:metadata:plagiarism_turnitinsim_client:submission_content'] = 'Please be aware that the content of a file/submission is sent to Turnitin for processing.';

$string['errorenabledfeatures'] = 'Could not get the list of enabled features.';
$string['errorgettingsubmissioninfo'] = 'There was an error attempting to get the submission info.';
$string['errorprocessingdeletedsubmission'] = 'This submission belongs to a deleted assignment and cannot be processed.';
$string['errorquizattemptnotfound'] = 'The quiz attempt for this submission could not be found.';
$string['help_link'] = 'https://help.turnitin.com/integrity/instructor/moodle.htm';
