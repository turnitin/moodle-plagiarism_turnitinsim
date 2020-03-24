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
 * Helper methods for plagiarism_turnitinsim unit tests.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Create and return a file to use for test submissions.
 *
 * @param string $itemid The Moodle item ID for the submission file.
 * @param int $usercontextid The context ID.
 * @param string $component The component the file belongs to.
 * @param string $filearea The file area for the file.
 * @return mixed
 * @throws coding_exception
 * @throws file_exception
 * @throws stored_file_creation_exception
 */
function create_test_file($itemid, $usercontextid, $component, $filearea) {

    // Create dummy file.
    if ($itemid == 0) {
        $itemid = file_get_unused_draft_itemid();
    }

    $filerecord = array(
        'contextid' => $usercontextid,
        'component' => $component,
        'filearea'  => $filearea,
        'itemid'    => $itemid,
        'filepath'  => '/',
        'filename'  => 'testtext.txt',
    );
    $fs = get_file_storage();
    $fs->create_file_from_string($filerecord, '');

    // Get file from specified area.
    $files = $fs->get_area_files($usercontextid, $component, $filearea, $itemid, "timecreated", false);
    $file = current($files);

    return $file;
}