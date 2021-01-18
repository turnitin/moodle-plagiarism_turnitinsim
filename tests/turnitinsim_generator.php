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
 * Generator for Turnitin tests.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Generator for Turnitin tests.
 */
class turnitinsim_generator extends advanced_testcase {

    /**
     * Define test_generator method.
     *
     * @return bool
     */
    public function test_generator() {
        return true;
    }

    /**
     * Create a a course, assignment module instance, student and teacher and enrol them in
     * the course.
     *
     * @param array $params parameters to be provided to the assignment module creation
     * @return array containing the course, assignment module, student and teacher
     * @throws coding_exception
     * @throws dml_exception
     */
    public function create_assign_with_student_and_teacher($params = array()) {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $params = array_merge(array(
            'course' => $course->id,
            'name' => 'assignment',
            'intro' => 'assignment intro text',
        ), $params);

        // Create a course and assignment and users.
        $assign = $this->getDataGenerator()->create_module('assign', $params);

        $cm = get_coursemodule_from_instance('assign', $assign->id);
        $context = context_module::instance($cm->id);

        $student = $this->getDataGenerator()->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $this->getDataGenerator()->enrol_user($student->id, $course->id, $studentrole->id);
        $teacher = $this->getDataGenerator()->create_user();
        $teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, $teacherrole->id);

        assign_capability('mod/assign:view', CAP_ALLOW, $teacherrole->id, $context->id, true);
        assign_capability('mod/assign:viewgrades', CAP_ALLOW, $teacherrole->id, $context->id, true);
        assign_capability('mod/assign:grade', CAP_ALLOW, $teacherrole->id, $context->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        return array(
            'course' => $course,
            'assign' => $assign,
            'student' => $student,
            'teacher' => $teacher
        );
    }

    /**
     * Create a Turnitin submission.
     *
     * @param int $numsubmissions The number of submissions to create.
     * @param string $status The Turnitin status for this submission.
     * @return array
     * @throws coding_exception
     * @throws dml_exception
     */
    public function create_submission($numsubmissions = 1, $status = TURNITINSIM_SUBMISSION_STATUS_QUEUED) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/assign/tests/base_test.php');

        $result = $this->create_assign_with_student_and_teacher(array(
            'assignsubmission_onlinetext_enabled' => 1,
            'teamsubmission' => 0
        ));

        $assignmodule = $result['assign'];
        $student = $result['student'];
        $cm = get_coursemodule_from_instance('assign', $assignmodule->id);
        $context = context_module::instance($cm->id);

        $plagiarismfile = new stdClass();
        $plagiarismfile->cm = $cm->id;
        $plagiarismfile->userid = $student->id;
        $plagiarismfile->identifier = "abcd";
        $plagiarismfile->statuscode = "success";
        $plagiarismfile->similarityscore = 50;
        $plagiarismfile->externalid = 123456789;
        $plagiarismfile->attempt = 1;
        $plagiarismfile->transmatch = 0;
        $plagiarismfile->lastmodified = time();
        $plagiarismfile->submissiontype = 2;
        $plagiarismfile->itemid = 12;
        $plagiarismfile->submitter = $student->id;
        $plagiarismfile->status = $status;

        for ($i = 0; $i < $numsubmissions; $i++) {
            $DB->insert_record('plagiarism_turnitinsim_sub', $plagiarismfile);
        }

        $this->setUser($student);

        return array(
            'student' => $student,
            'context' => $context
        );
    }
}