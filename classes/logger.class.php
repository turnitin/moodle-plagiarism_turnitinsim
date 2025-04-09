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

use Monolog\Monolog;

/**
 * Log API requests and responses from Turnitin.
 */
class plagiarism_turnitinsim_logger {

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

    private Monolog\Logger $logger;

    /**
     * plagiarism_turnitinsim_logger constructor.
     */
    public function __construct() {
        global $CFG;
        
        $this->logger = new Logger(APILOG_PREFIX);

        // Use RotatingFileHandler for automatic log rotation
        $handler = new RotatingFileHandler($CFG->tempdir.'/'.self::LOG_DIR, KEEPLOGS, Logger::DEBUG);
        $this->logger->pushHandler($handler);
    }
}