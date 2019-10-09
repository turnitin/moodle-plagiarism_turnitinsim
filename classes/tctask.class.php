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
 * Perform Scheduled Tasks.
 *
 * @package    plagiarism_turnitincheck
 * @author     John McGettrick <jmcgettrick@turnitin.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/plagiarism/turnitincheck/lib.php');
require_once($CFG->dirroot . '/plagiarism/turnitincheck/classes/tccallback.class.php');

use plagiarism_turnitincheck\message\new_eula;

/**
 * Scheduled tasks.
 */
class tctask {

    public $tcrequest;
    public $tccallback;
    public $tcsettings;

    public function __construct( $params = null ) {
        $this->tcrequest = (!empty($params->tcrequest)) ? $params->tcrequest : new tcrequest();
        $this->tccallback = (!empty($params->tccallback)) ? $params->tccallback : new tccallback($this->tcrequest);
        $this->tcsettings = (!empty($params->tcsettings)) ? $params->tcsettings : new tcsettings($this->tcrequest);
        $this->tceula = (!empty($params->tceula)) ? $params->tceula : new tceula();
    }

    /**
     * Send Queued submissions to Turnitin.
     * @return boolean
     */
    public function send_queued_submissions() {
        global $DB;

        // Create webhook if necessary.
        $webhookid = get_config('plagiarism', 'turnitin_webhook_id');
        if (empty($webhookid)) {
            $this->tccallback = new tccallback($this->tcrequest);
            $this->tccallback->create_webhook();
        }

        // Get Submissions to send.
        $submissions = $DB->get_records_select('plagiarism_turnitincheck_sub', 'status = ?',
            array(TURNITINCHECK_SUBMISSION_STATUS_QUEUED, TURNITINCHECK_SUBMISSION_STATUS_CREATED), '', '*', 0,
            TURNITINCHECK_SUBMISSION_SEND_LIMIT);

        // Create each submission in Turnitin and upload submission.
        foreach ($submissions as $submission) {

            // Reset headers.
            $this->tcrequest->set_headers();

            $tcsubmission = new tcsubmission($this->tcrequest, $submission->id);

            if ($tcsubmission->getstatus() == TURNITINCHECK_SUBMISSION_STATUS_QUEUED) {
                $tcsubmission->create_submission_in_turnitin();
            }

            if (!empty($tcsubmission->getturnitinid())) {
                $tcsubmission->upload_submission_to_turnitin();

                // Set the time for the report to be generated.
                $tcsubmission->calculate_generation_time();
            }

            $tcsubmission->update();
        }

        return true;
    }

    /**
     * Request a report to be generated and get report scores from Turnitin.
     * @return boolean
     */
    public function get_reports() {
        global $DB;

        // Get submissions to request reports for.
        $submissions = $DB->get_records_select(
            'plagiarism_turnitincheck_sub',
            " ((to_generate = ? AND generation_time <= ?) OR (status = ?)) AND turnitinid IS NOT NULL",
            array(1, time(), TURNITINCHECK_SUBMISSION_STATUS_REQUESTED)
            );

        // Request reports be generated or get scores for reports that have been requested.
        $count = 0;
        foreach ($submissions as $submission) {

            $tcsubmission = new tcsubmission($this->tcrequest, $submission->id);

            // Request Originality Report to be generated if it hasn't already, this should have been done by the
            // webhook callback so ignore anything submitted to Turnitin in the 2 minutes.
            // Otherwise retrieve originality score if we haven't received it back within 5 minutes.
            if ($tcsubmission->getstatus() == TURNITINCHECK_SUBMISSION_STATUS_UPLOADED
                && $tcsubmission->getsubmittedtime() < (time() - $this->get_report_gen_request_delay())) {

                $tcsubmission->request_turnitin_report_generation();

            } else if ($tcsubmission->getstatus() != TURNITINCHECK_SUBMISSION_STATUS_UPLOADED
                && $tcsubmission->getrequestedtime() < (time() - $this->get_report_gen_score_delay())) {

                $tcsubmission->request_turnitin_report_score();
            }

            // Only process a set number of submissions.
            $count++;
            if ($count == TURNITINCHECK_SUBMISSION_SEND_LIMIT) {
                break;
            }
        }

        return true;
    }

    /**
     *
     * This task performs several sub tasks;
     * Test whether the webhook is working, if not create a new one.
     * Check what is the latest version of the EULA and store details locally.
     * Update the features enabled on the Turnitin Account and store locally.
     *
     * @return bool
     */
    public function admin_update() {

        // Update enabled features.
        $this->check_enabled_features();

        // Test the webhook.
        $this->test_webhook();

        // Check the latest EULA version.
        $this->check_latest_eula_version();

        return true;
    }

    /**
     * Test whether the webhook is working, if not create a new one.
     * @return bool
     */
    public function test_webhook() {
        // Reset headers.
        $this->tcrequest->set_headers();

        // Check webhook is valid.
        $webhookid = get_config('plagiarism', 'turnitin_webhook_id');

        // If we have a webhook id then retrieve the webhook.
        if ($webhookid) {
            $valid = $this->tccallback->get_webhook($webhookid);

            if (!$valid) {
                $this->tccallback->delete_webhook($webhookid);
                $this->tccallback->create_webhook();
            }
        } else {
            $this->tccallback->create_webhook();
        }

        return true;
    }

    /**
     * Check what is the latest version of the EULA and store details locally.
     */
    public function check_latest_eula_version() {
        // Reset headers.
        $this->tcrequest->set_headers();

        // Get the latest EULA version.
        $response = $this->tceula->get_latest_version();
        if (!empty($response)) {
            // Compare latest EULA to the current EULA we have stored.
            $currenteulaversion = get_config('plagiarism', 'turnitin_eula_version');
            $neweulaversion = (empty($response->version)) ? '' : $response->version;

            // Update EULA version and url if necessary.
            if ($currenteulaversion != $neweulaversion) {
                set_config('turnitin_eula_version', $response->version, 'plagiarism');
                set_config('turnitin_eula_url', $response->url, 'plagiarism');

                // Notify all users linked to Turnitin that there is a new EULA to accept.
                $message = new new_eula();
                $message->send_message();
            }
        }

        return true;
    }

    /**
     * Check the features enabled for this account in Turnitin.
     */
    public function check_enabled_features() {
        // Get the enabled features.
        $response = $this->tcsettings->get_enabled_features();

        if (!empty($response) && $response->httpstatus == HTTP_OK) {

            // Remove status from response.
            unset($response->httpstatus);
            unset($response->status);

            // Compare enabled features to the current enabled features we have stored.
            $currentfeatures = get_config('plagiarism', 'turnitin_features_enabled');
            $newfeatures = json_encode($response);

            // Update enabled features if necessary.
            if ($currentfeatures != $newfeatures && !empty($newfeatures)) {
                set_config('turnitin_features_enabled', json_encode($response), 'plagiarism');
            }
        }

        return true;
    }

    /**
     * Get the delay for requesting report generation as a backup to the webhook failing.
     * This is a lot less in testing to avoid long waits.
     *
     * @return int
     */
    public function get_report_gen_request_delay() {
        if (defined('BEHAT_SITE_RUNNING') || defined('BEHAT_TEST')) {
            return TURNITINCHECK_REPORT_GEN_REQUEST_DELAY_TESTING;
        }

        return TURNITINCHECK_REPORT_GEN_REQUEST_DELAY;
    }

    /**
     * Get the delay for requesting report score as a backup to the webhook failing.
     * This is a lot less in testing to avoid long waits.
     *
     * @return int
     */
    public function get_report_gen_score_delay() {
        if (defined('BEHAT_SITE_RUNNING') || defined('BEHAT_TEST')) {
            return TURNITINCHECK_REPORT_GEN_SCORE_DELAY_TESTING;
        }

        return TURNITINCHECK_REPORT_GEN_SCORE_DELAY;
    }
}