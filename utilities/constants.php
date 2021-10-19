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
 * Constants for plagiarism_turnitinsim component
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Submission status constants.
define('TURNITINSIM_SUBMISSION_STATUS_QUEUED', 'QUEUED');
define('TURNITINSIM_SUBMISSION_STATUS_EMPTY_DELETED', 'EMPTYORNOFILE');
define('TURNITINSIM_SUBMISSION_STATUS_ERROR', 'ERROR');
define('TURNITINSIM_SUBMISSION_STATUS_CANNOT_EXTRACT_TEXT', 'CANNOT_EXTRACT_TEXT');
define('TURNITINSIM_SUBMISSION_STATUS_CREATED', 'CREATED');
define('TURNITINSIM_SUBMISSION_STATUS_UPLOADED', 'UPLOADED');
define('TURNITINSIM_SUBMISSION_STATUS_REQUESTED', 'REQUESTED');
define('TURNITINSIM_SUBMISSION_STATUS_PROCESSING', 'PROCESSING');
define('TURNITINSIM_SUBMISSION_STATUS_COMPLETE', 'COMPLETE');
define('TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED', 'EULA_NOT_ACCEPTED');
define('TURNITINSIM_SUBMISSION_STATUS_TOO_LARGE', 'TOO_LARGE');
define('TURNITINSIM_SUBMISSION_STATUS_NOT_SENT', 'NOTSENT');
define('TURNITINSIM_SUBMISSION_STATUS_UNSUPPORTED_FILETYPE', 'UNSUPPORTED_FILETYPE');
define('TURNITINSIM_SUBMISSION_STATUS_PROCESSING_ERROR', 'PROCESSING_ERROR');
define('TURNITINSIM_SUBMISSION_STATUS_TOO_LITTLE_TEXT', 'TOO_LITTLE_TEXT');
define('TURNITINSIM_SUBMISSION_STATUS_TOO_MUCH_TEXT', 'TOO_MUCH_TEXT');
define('TURNITINSIM_SUBMISSION_STATUS_TOO_MANY_PAGES', 'TOO_MANY_PAGES');
define('TURNITINSIM_SUBMISSION_STATUS_FILE_LOCKED', 'FILE_LOCKED');
define('TURNITINSIM_SUBMISSION_STATUS_CORRUPT_FILE', 'CORRUPT_FILE');

// API response http statuses.
define('TURNITINSIM_HTTP_OK', 200);
define('TURNITINSIM_HTTP_CREATED', 201);
define('TURNITINSIM_HTTP_ACCEPTED', 202);
define('TURNITINSIM_HTTP_NO_CONTENT', 204);
define('TURNITINSIM_HTTP_BAD_REQUEST', 400);
define('TURNITINSIM_HTTP_CANNOT_EXTRACT_TEXT', 409);
define('TURNITINSIM_HTTP_UNPROCESSABLE_ENTITY', 422);
define('TURNITINSIM_HTTP_UNAVAILABLE_FOR_LEGAL_REASONS', 451);

// API Endpoints.
define('TURNITINSIM_ENDPOINT_CREATE_SUBMISSION', '/sms-namespace/redwood/sms-serviceName/tca/api/v1/submissions');
define('TURNITINSIM_ENDPOINT_GET_SUBMISSION_INFO', '/sms-namespace/redwood/sms-serviceName/tca/api/v1/submissions/{{submission_id}}');
define('TURNITINSIM_ENDPOINT_UPLOAD_SUBMISSION', '/sms-namespace/redwood/sms-serviceName/tca/api/v1/submissions/{{submission_id}}/original');
define('TURNITINSIM_ENDPOINT_SIMILARITY_REPORT', '/sms-namespace/redwood/sms-serviceName/tca/api/v1/submissions/{{submission_id}}/similarity');
define('TURNITINSIM_ENDPOINT_CV_LAUNCH', '/sms-namespace/redwood/sms-serviceName/tca/api/v1/submissions/{{submission_id}}/viewer-url');
define('TURNITINSIM_ENDPOINT_WEBHOOKS', '/sms-namespace/redwood/sms-serviceName/tca/api/v1/webhooks');
define('TURNITINSIM_ENDPOINT_GET_WEBHOOK', '/sms-namespace/redwood/sms-serviceName/tca/api/v1/webhooks/{{webhook_id}}');
define('TURNITINSIM_ENDPOINT_GET_LATEST_EULA', '/sms-namespace/redwood/sms-serviceName/tca/api/v1/eula/latest');
define('TURNITINSIM_ENDPOINT_GET_FEATURES_ENABLED', '/sms-namespace/redwood/sms-serviceName/tca/api/v1/features-enabled');
define('TURNITINSIM_ENDPOINT_LOGGING', '/remote-logging/api/log');
define('TURNITINSIM_ENDPOINT_WHERE_AM_I', '/where-am-i');

// URLs.
define('TURNITINSIM_EULA', '/plagiarism/turnitinsim/eula.php?cmd=displayeula');
define('TURNITINSIM_CALLBACK_URL', $CFG->wwwroot.'/plagiarism/turnitinsim/callbacks.php');

// Webhook Event Types.
define('TURNITINSIM_SUBMISSION_COMPLETE', 'SUBMISSION_COMPLETE');
define('TURNITINSIM_SIMILARITY_COMPLETE', 'SIMILARITY_COMPLETE');
define('TURNITINSIM_SIMILARITY_UPDATED', 'SIMILARITY_UPDATED');

// Misc. constants.
define('TURNITINSIM_SUBMISSION_SEND_LIMIT', 50);
define('TURNITINSIM_SUBMISSION_MAX_FILENAME_LENGTH', 180);
define('TURNITINSIM_SUBMISSION_MAX_FILE_UPLOAD_SIZE', 104857600);
define('TURNITINSIM_SUBMISSION_MAX_SEND_ATTEMPTS', 12);
define('TURNITINSIM_SUBMISSION_RETRY_WAIT_SECONDS', 3600);

// Submission types.
define('TURNITINSIM_SUBMISSION_TYPE_FILE', 'file');
define('TURNITINSIM_SUBMISSION_TYPE_CONTENT', 'content');

// Report Generation speeds.
define('TURNITINSIM_REPORT_GEN_IMMEDIATE', 0);
define('TURNITINSIM_REPORT_GEN_IMMEDIATE_AND_DUEDATE', 1);
define('TURNITINSIM_REPORT_GEN_DUEDATE', 2);

// Report Generation requests.
define('TURNITINSIM_REPORT_GEN_REQUEST_DELAY', 120);
define('TURNITINSIM_REPORT_GEN_REQUEST_DELAY_TESTING', 10);
define('TURNITINSIM_REPORT_GEN_SCORE_DELAY', 300);
define('TURNITINSIM_REPORT_GEN_SCORE_DELAY_TESTING', 20);
define('TURNITINSIM_REPORT_GEN_EXCLUDE_SELF_GROUP', 'GROUP_CONTEXT');
define('TURNITINSIM_REPORT_GEN_MAX_ATTEMPTS', 3);
define('TURNITINSIM_REPORT_GEN_RETRY_WAIT_SECONDS', 3600);
define('TURNITINSIM_REPORT_GEN_FIRST_ATTEMPT_RETRY_WAIT_SECONDS', 900);

// Turnitin Roles.
define('TURNITINSIM_ROLE_INSTRUCTOR', 'INSTRUCTOR');
define('TURNITINSIM_ROLE_LEARNER', 'LEARNER');

// External URLs
define('TURNITINSIM_EXTERNAL_USCALD', 'external.turnitin.dev');
define('TURNITINSIM_EXTERNAL_USW2DEV', 'external-dev.us2.turnitin.dev');
define('TURNITINSIM_EXTERNAL_USCALQ', 'external-qa.turnitin.org');
define('TURNITINSIM_EXTERNAL_USW2QA', 'external-qa.us2.turnitin.org');
define('TURNITINSIM_EXTERNAL_EUFRASAND', 'external-sandbox.eu.tii-sandbox.com');
define('TURNITINSIM_EXTERNAL_EUC1SAND', 'external-sandbox.eu.tii-sandbox.com');
define('TURNITINSIM_EXTERNAL_USW2SAND', 'external-sandbox.us.tii-sandbox.com');
define('TURNITINSIM_EXTERNAL_APSYDP', 'external-production.au.turnitin.com');
define('TURNITINSIM_EXTERNAL_CACENP', 'external-production.turnitin.ca');
define('TURNITINSIM_EXTERNAL_EUFRAP', 'external-production.eu.turnitin.com');
define('TURNITINSIM_EXTERNAL_USCALP', 'external-production.us.turnitin.com');
define('TURNITINSIM_EXTERNAL_USCALD-EKS', 'external.turnitin.dev');
define('TURNITINSIM_EXTERNAL_USW2PROD', 'external-production.us2.turnitin.com');
define('TURNITINSIM_EXTERNAL_USW2CNC', 'external-cnc.turnitin.org');
