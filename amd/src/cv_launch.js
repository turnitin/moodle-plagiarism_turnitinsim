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
 * Javascript controller for the Turnitin Cloud Viewer launch.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2017 John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module plagiarism_turnitinsim/cv_launch
 */

define(['jquery'], function($) {
    return {
        open_cv: function() {
            $(document).on('click', '.or_score', function() {

                // Moodle forums strip ids from elements so we have to use classes.
                var classList = $(this).parent().attr('class').split(/\s+/);
                var submissionid = 0;
                $(classList).each(function(index){
                    if (classList[index].match("^submission_")) {
                        submissionid = classList[index].split("_")[1];
                    }
                });

                // Launch the Cloud Viewer in a new window.
                // It is being done this way as in the future we may have to listen for it closing and act on it.
                var dvWindow = window.open('', 'cvWindow');
                var loading = '<div class="tii_dv_loading" style="text-align:center;">';
                var icon = M.cfg.wwwroot + '/plagiarism/turnitinsim/pix/tiiIcon.svg';
                loading += '<img src="' + icon + '" style="width:100px; height: 100px">';
                var loadingCvStr = M.str.plagiarism_turnitinsim.loadingcv;
                loading += '<p style="font-family: Arial, Helvetica, sans-serif;">' + loadingCvStr + '</p>';
                loading += '</div>';
                $(dvWindow.document.body).html(loading);

                $.ajax({
                    type: "GET",
                    url: M.cfg.wwwroot + "/plagiarism/turnitinsim/ajax/cv.php",
                    dataType: "json",
                    data: {
                        action: 'request_cv_launch',
                        submissionid: submissionid,
                        sesskey: M.cfg.sesskey
                    },
                    success: function(data) {
                        // Redirect opened window to returned URL.
                        dvWindow.location = data.viewer_url;
                    }
                });
            });
        }
    };
});