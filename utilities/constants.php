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
 * Constants for plagiarism_turnitincheck component
 *
 * @package   plagiarism_turnitincheck
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Submission status constants.
define('TURNITINCHECK_SUBMISSION_STATUS_QUEUED', 'QUEUED');
define('TURNITINCHECK_SUBMISSION_STATUS_EMPTY_DELETED', 'EMPTYORNOFILE');
define('TURNITINCHECK_SUBMISSION_STATUS_ERROR', 'ERROR');
define('TURNITINCHECK_SUBMISSION_STATUS_CREATED', 'CREATED');
define('TURNITINCHECK_SUBMISSION_STATUS_UPLOADED', 'UPLOADED');
define('TURNITINCHECK_SUBMISSION_STATUS_REQUESTED', 'REQUESTED');
define('TURNITINCHECK_SUBMISSION_STATUS_PROCESSING', 'PROCESSING');
define('TURNITINCHECK_SUBMISSION_STATUS_COMPLETE', 'COMPLETE');
define('TURNITINCHECK_SUBMISSION_STATUS_EULA_NOT_ACCEPTED', 'EULA_NOT_ACCEPTED');
define('TURNITINCHECK_SUBMISSION_STATUS_TOO_LARGE', 'TOO_LARGE');
define('TURNITINCHECK_SUBMISSION_STATUS_NOT_SENT', 'NOTSENT');
define('TURNITINCHECK_SUBMISSION_STATUS_UNSUPPORTED_FILETYPE', 'UNSUPPORTED_FILETYPE');
define('TURNITINCHECK_SUBMISSION_STATUS_PROCESSING_ERROR', 'PROCESSING_ERROR');
define('TURNITINCHECK_SUBMISSION_STATUS_TOO_LITTLE_TEXT', 'TOO_LITTLE_TEXT');
define('TURNITINCHECK_SUBMISSION_STATUS_TOO_MUCH_TEXT', 'TOO_MUCH_TEXT');
define('TURNITINCHECK_SUBMISSION_STATUS_TOO_MANY_PAGES', 'TOO_MANY_PAGES');
define('TURNITINCHECK_SUBMISSION_STATUS_FILE_LOCKED', 'FILE_LOCKED');
define('TURNITINCHECK_SUBMISSION_STATUS_CORRUPT_FILE', 'CORRUPT_FILE');

// API response http statuses.
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_ACCEPTED', 202);
define('HTTP_NO_CONTENT', 204);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAVAILABLE_FOR_LEGAL_REASONS', 451);

// API Endpoints.
define('ENDPOINT_CREATE_SUBMISSION', '/v1/submissions');
define('ENDPOINT_UPLOAD_SUBMISSION', '/v1/submissions/{{submission_id}}/original');
define('ENDPOINT_SIMILARITY_REPORT', '/v1/submissions/{{submission_id}}/similarity');
define('ENDPOINT_CV_LAUNCH', '/v1/submissions/{{submission_id}}/viewer-url');
define('ENDPOINT_WEBHOOKS', '/v1/webhooks');
define('ENDPOINT_GET_WEBHOOK', '/v1/webhooks/{{webhook_id}}');
define('ENDPOINT_GET_LATEST_EULA', '/v1/eula/latest');
define('ENDPOINT_GET_FEATURES_ENABLED', '/v1/features-enabled');

// URLs.
define('TURNITINCHECK_HELP_LINK', 'https://help.turnitin.com/simcheck/integrations/moodle/moodle-home.htm');
define('TURNITINCHECK_EULA', '/plagiarism/turnitincheck/eula.php?cmd=displayeula');
define('TURNITINCHECK_CALLBACK_URL', $CFG->wwwroot.'/plagiarism/turnitincheck/callbacks.php');

// Webhook Event Types.
define('SUBMISSION_COMPLETE', 'SUBMISSION_COMPLETE');
define('SIMILARITY_COMPLETE', 'SIMILARITY_COMPLETE');

// Misc. constants.
define('TURNITINCHECK_SUBMISSION_SEND_LIMIT', 50);
define('TURNITINCHECK_SUBMISSION_MAX_FILENAME_LENGTH', 180);
define('TURNITINCHECK_SUBMISSION_MAX_FILE_UPLOAD_SIZE', 104857600);

// Submission types.
define('TURNITINCHECK_SUBMISSION_TYPE_FILE', 'file');
define('TURNITINCHECK_SUBMISSION_TYPE_CONTENT', 'content');

// Report Generation speeds.
define('TURNITINCHECK_REPORT_GEN_IMMEDIATE', 0);
define('TURNITINCHECK_REPORT_GEN_IMMEDIATE_AND_DUEDATE', 1);
define('TURNITINCHECK_REPORT_GEN_DUEDATE', 2);

// Report Generation requests.
define('TURNITINCHECK_REPORT_GEN_REQUEST_DELAY', 120);
define('TURNITINCHECK_REPORT_GEN_REQUEST_DELAY_TESTING', 10);
define('TURNITINCHECK_REPORT_GEN_SCORE_DELAY', 300);
define('TURNITINCHECK_REPORT_GEN_SCORE_DELAY_TESTING', 20);
define('TURNITINCHECK_REPORT_GEN_EXCLUDE_SELF_GROUP', 'GROUP_CONTEXT');

// Metadata.
define('TURNITINCHECK_GROUP_TYPE_ASSIGNMENT', 'ASSIGNMENT');

// Turnitin Roles.
define('TURNITINCHECK_ROLE_INSTRUCTOR', 'INSTRUCTOR');
define('TURNITINCHECK_ROLE_LEARNER', 'LEARNER');