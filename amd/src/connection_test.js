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
 * Javascript controller for the Turnitin Connection Test.
 *
 * @package   plagiarism_turnitinsim
 * @copyright 2018 Turnitin
 * @author    David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module plagiarism_turnitinsim/connection_test
 */

define(['jquery', 'core/str'], function($, str) {
    return {
        connectionTest: function() {
            // Store connection test selector.
            var ct = $('#id_connection_test');

            if (ct.length > 0) {
                ct.click(function() {
                    $.ajax({
                        type: "POST",
                        url: M.cfg.wwwroot + "/plagiarism/turnitinsim/ajax/connection_test.php",
                        dataType: "json",
                        data: {
                            action: "connection_test",
                            sesskey: M.cfg.sesskey,
                            apiurl: $('#id_turnitinapiurl').val(),
                            apikey: $('#id_turnitinapikey').val()
                        },
                        success: function(data) {
                            ct.removeClass("btn-secondary");
                            if (data.connection_status === 200) {
                                ct.removeClass("btn-danger");
                                ct.addClass("btn-success");

                                str.get_string('connecttestsuccess', 'plagiarism_turnitinsim').done(function(text) {
                                    changeString(ct, text);
                                });
                            } else {
                                ct.removeClass("btn-success");
                                ct.addClass("btn-danger");

                                str.get_string('connecttestfailed', 'plagiarism_turnitinsim').done(function(text) {
                                    changeString(ct, text);
                                });
                            }

                            // Fade out classes and swap back values.
                            ct.delay(3000).fadeOut("slow", function() {
                                ct.removeClass("btn-danger");
                                ct.removeClass("btn-success");
                                $(this).addClass("btn-secondary");

                                str.get_string('connecttest', 'plagiarism_turnitinsim').done(function(text) {
                                    changeString(ct, text);
                                });
                            }).fadeIn("slow");
                        }
                    });
                });
            }

            /**
             * Helper function to change the button text depending on which type of element we're handling.
             * @param {jQuery} ct - The button element - may be input or button depending on the Moodle theme.
             * @param {String} langString  - The language string we're setting.
             */
            function changeString(ct, langString) {
                if (ct.get(0).tagName === "BUTTON") {
                    ct.text(langString);
                } else {
                    ct.attr('value', langString);
                }
            }
        }
    };
});