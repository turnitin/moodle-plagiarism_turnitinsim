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
 * Log API requests and responses from Turnitin.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require($CFG->dirroot . '/plagiarism/turnitinsim/vendor/autoload.php');
require_once($CFG->dirroot . '/plagiarism/turnitinsim/lib.php');

use Katzgrau\KLogger;

/**
 * Log API requests and responses from Turnitin.
 */
class plagiarism_turnitinsim_logger extends Katzgrau\KLogger\Logger {

    /**
     * The location of the log directory.
     */
    const LOG_DIR = '/turnitinsim/logs/';

    /**
     * The number of logs to keep.
     */
    const KEEPLOGS = 10;

    /**
     * The prefix for the API log file name.
     */
    const APILOG_PREFIX = 'apilog_';

    /**
     * plagiarism_turnitinsim_logger constructor.
     */
    public function __construct() {
        global $CFG;

        $this->rotate_logs( $CFG->tempdir.'/'.self::LOG_DIR );

        parent::__construct($CFG->tempdir.'/'.self::LOG_DIR, Psr\Log\LogLevel::DEBUG, array (
            'prefix' => self::APILOG_PREFIX
        ));
    }

    /**
     * Rotate logs, only keep the last KEEPLOGS number of logs.
     *
     * @param string $filepath The file path for the logs.
     */
    private function rotate_logs( $filepath ) {

        // Create log directory if necessary.
        if ( !file_exists( $filepath ) ) {
            mkdir( $filepath, 0777, true );
        }

        // Search for log files to delete.
        $dir = opendir( $filepath );
        $files = array();
        while ( $entry = readdir( $dir ) ) {
            if ( substr( basename( $entry ), 0, 1 ) != '.' AND substr_count( basename( $entry ), self::APILOG_PREFIX ) > 0 ) {
                $files[] = basename( $entry );
            }
        }

        // Delete old log files.
        sort( $files );
        for ($i = 0; $i < count( $files ) - self::KEEPLOGS; $i++) {
            unlink( $filepath . '/' . $files[$i] );
        }
    }
}