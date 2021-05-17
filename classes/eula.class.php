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
 * Class for handling Turnitin's EULA.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class for handling Turnitin's EULA.
 */
class plagiarism_turnitinsim_eula {

    /**
     * @var plagiarism_turnitinsim_request|null The request object.
     */
    public $tsrequest;

    /**
     * plagiarism_turnitinsim_eula constructor.
     *
     * @param plagiarism_turnitinsim_request|null $tsrequest The request we're handling.
     * @throws dml_exception
     */
    public function __construct(plagiarism_turnitinsim_request $tsrequest = null ) {
        $this->tsrequest = ($tsrequest) ? $tsrequest : new plagiarism_turnitinsim_request();
    }

    /**
     * Attempt to retrieve the latest version of the EULA.
     *
     * @return mixed|stdClass
     * @throws coding_exception
     */
    public function get_latest_version() {
        $responsedata = new stdClass();

        // Make request to get the latest EULA version.
        try {
            $endpoint = TURNITINSIM_ENDPOINT_GET_LATEST_EULA;
            $response = $this->tsrequest->send_request($endpoint, json_encode(array()), 'GET');
            $responsedata = json_decode($response);

            // Latest version retrieved.
            if ($responsedata->httpstatus == TURNITINSIM_HTTP_OK) {
                mtrace(get_string('taskoutputlatesteularetrieved', 'plagiarism_turnitinsim', $responsedata->version));
                return $responsedata;
            }

            mtrace(get_string('taskoutputlatesteulanotretrieved', 'plagiarism_turnitinsim'));
            return $responsedata;

        } catch (Exception $e) {
            $this->tsrequest->handle_exception($e, 'taskoutputlatesteularetrievalfailure');
            return $responsedata;
        }
    }

    /**
     * Method for handling the acceptance of the EULA, called from eula_response.
     * @throws dml_exception
     */
    public function accept_eula() {
        global $DB, $USER;

        // Get current user record.
        $user = $DB->get_record('plagiarism_turnitinsim_users', array('userid' => $USER->id));

        // Update EULA accepted version and timestamp for user.
        $data = new stdClass();
        $data->id = $user->id;
        $data->lasteulaaccepted = get_config('plagiarism_turnitinsim', 'turnitin_eula_version');
        $data->lasteulaacceptedtime = time();
        $lang = $this->tsrequest->get_language();
        $data->lasteulaacceptedlang = $lang->localecode;
        $DB->update_record('plagiarism_turnitinsim_users', $data);

        // Get all submissions for this student with EULA_NOT_ACCEPTED status.
        $submissions = $DB->get_records(
            'plagiarism_turnitinsim_sub',
            array(
                'status' => TURNITINSIM_SUBMISSION_STATUS_EULA_NOT_ACCEPTED,
                'userid' => $USER->id
            )
        );

        // Update all existing submissions where EULA was not accepted.
        foreach ($submissions as $submission) {
            $data = new stdClass();
            $data->id     = $submission->id;
            $data->status = TURNITINSIM_SUBMISSION_STATUS_QUEUED;
            $data->tiiattempts = 0;
            $data->tiiretrytime = 0;

            $DB->update_record('plagiarism_turnitinsim_sub', $data);
        }

        return json_encode(["success" => true]);
    }


    /**
     * This returns the HTML elements required to display a EULA_NOT_ACCEPTED status.
     *
     * @param int $cmid - course module id
     * @param string $submissiontype - The type of submission - file or content.
     * @param string $submissionuserid - The userid the submission is against.
     * @return array The HTML elements for a EULA NOT ACCEPTED status.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function get_eula_status($cmid, $submissiontype, $submissionuserid) {
        global $OUTPUT, $USER;

        $eulaconfirm = '';
        if ($USER->id == $submissionuserid) {
            $plagiarismpluginturnitinsim = new plagiarism_plugin_turnitinsim();
            $eulaconfirm = $plagiarismpluginturnitinsim->print_disclosure($cmid, $submissiontype);
        }

        $helpicon = $OUTPUT->pix_icon(
            'help',
            get_string('submissiondisplayerror:eulanotaccepted_help', 'plagiarism_turnitinsim'),
            'core'
        );

        return array('eula-confirm' => $eulaconfirm, 'eula-status' => html_writer::tag(
            'span',
            get_string('submissiondisplaystatus:awaitingeula', 'plagiarism_turnitinsim') . $helpicon,
            array('class' => 'tii_status_text tii_status_text_eula')
        ));
    }
}