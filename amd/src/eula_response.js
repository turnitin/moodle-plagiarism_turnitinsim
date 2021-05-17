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
 * Javascript controller for handling the EULA response.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    John McGettrick <jmcgettrick@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module plagiarism_turnitinsim/handleEulaResponse
 */

define(['jquery', 'core/str'], function($, str) {
    return {
        eulaResponse: function() {
            $(document).ready(function() {
                if ($("#turnitinsim_eulacontainer").length) {
                    $('input[name=submitbutton]').prop('disabled', 'disabled');
                }
            });

            $(document).on('click', '#turnitinsim_eula_accept', function() {
                $('input[name=submitbutton]').prop('disabled', '');

                // Hide the EULA link.
                $.ajax({
                    type: "POST",
                    url: M.cfg.wwwroot + "/plagiarism/turnitinsim/ajax/eula_response.php",
                    dataType: "text",
                    data: {action: "accept_eula", sesskey: M.cfg.sesskey},
                    success: function() {
                        str.get_string('eulaaccepted', 'plagiarism_turnitinsim').done(function(text) {
                            $('.turnitinsim_eulacontainer').hide().html(text).fadeIn();

                            str.get_string('submissiondisplaystatus:queued', 'plagiarism_turnitinsim').done(function(text) {
                                $('.tii_status_text').html(text);
                            });
                        });
                    }
                });
            });

            $(document).on('click', '#turnitinsim_eula_decline', function() {
                str.get_string('euladeclined', 'plagiarism_turnitinsim').done(function(text) {
                    $('.turnitinsim_eulacontainer').hide().html(text).fadeIn();
                });

                $('input[name=submitbutton]').prop('disabled', '');
            });
        }
    };
});