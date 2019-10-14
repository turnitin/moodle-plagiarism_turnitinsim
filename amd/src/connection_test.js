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
 * @copyright 2018 David Winn <dwinn@turnitin.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module plagiarism_turnitinsim/connection_test
 */

define(['jquery'], function($) {
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
                            if (data.connection_status === 200) {
                                ct.removeClass("connection-test-failed");
                                ct.addClass("connection-test-success");

                                changeString(ct, M.str.plagiarism_turnitinsim.connecttestsuccess);
                            } else {
                                ct.removeClass("connection-test-success");
                                ct.addClass("connection-test-failed");

                                changeString(ct, M.str.plagiarism_turnitinsim.connecttestfailed);
                            }

                            // Fade out classes and swap back values.
                            ct.delay(1000).fadeOut("slow", function() {
                                $(this).removeClass("turnitinsim_connection-test-failed");
                                $(this).removeClass("turnitinsim_connection-test-success");

                                changeString(ct, M.str.plagiarism_turnitinsim.connecttest);
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