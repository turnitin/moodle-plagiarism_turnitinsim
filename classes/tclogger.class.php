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
 * @package    plagiarism_turnitincheck
 * @author     John McGettrick http://www.turnitin.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require($CFG->dirroot . '/plagiarism/turnitincheck/vendor/autoload.php');
require_once($CFG->dirroot . '/plagiarism/turnitincheck/lib.php');

use Katzgrau\KLogger;

class tclogger extends Katzgrau\KLogger\Logger {

    const LOG_DIR = '/turnitincheck/logs/';
    const KEEPLOGS = 10;
    const APILOG_PREFIX = 'apilog_';

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
     * @param $filepath
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