/**
 * Javascript controller for the Delete assignment part modal.
 *
 * @package   turnitin
 * @copyright 2018 John McGettrick <jmcgettrick@turnitin.com>
 */

/**
 * @module plagiarism_turnitinsim/modal_eula
 */

define(
    [
        'jquery',
        'core/notification',
        'core/custom_interaction_events',
        'core/modal',
        'core/modal_registry',
        'core/modal_events',
        'core/config'
    ],
    function($, Notification, CustomEvents, Modal, ModalRegistry, ModalEvents, Config) {

        var registered = false;
        var SELECTORS = {
            EULA_LINK: '[class="eula_link"]',
            ACCEPT_BUTTON: '[data-action="accept-eula"]',
            CANCEL_BUTTON: '[data-action="cancel"]'
        };

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var ModalTcEula = function(root) {
            Modal.call(this, root);
        };

        ModalTcEula.TYPE = 'plagiarism_turnitinsim-modal_eula';
        ModalTcEula.prototype = Object.create(Modal.prototype);
        ModalTcEula.prototype.constructor = ModalTcEula;

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        ModalTcEula.prototype.registerEventListeners = function() {
            // Apply parent event listeners.
            Modal.prototype.registerEventListeners.call(this);

            // On clicking the EULA link, open in a new window.
            this.getModal().on(CustomEvents.events.activate, SELECTORS.EULA_LINK, function(e) {
                e.preventDefault();
                window.open(
                    Config.wwwroot + '/plagiarism/turnitinsim/eula.php?cmd=eularedirect&sesskey=' + Config.sesskey,
                    '_blank'
                );
            }.bind(this));

            // On accepting the EULA, update the db and queue submissions for this module.
            this.getModal().on(CustomEvents.events.activate, SELECTORS.ACCEPT_BUTTON, function() {
                var modal = this;

                $.ajax({
                    type: "POST",
                    url: Config.wwwroot + "/plagiarism/turnitinsim/ajax/eula_response.php",
                    dataType: "json",
                    data: {
                        action: 'accept_eula',
                        contextid: Config.contextid,
                        sesskey: Config.sesskey
                    },
                    success: function () {
                        modal.hide();
                        var link = window.location.origin + window.location.pathname + window.location.search + '&eula=1';
                        window.location.href = link;
                    }
                });

            }.bind(this));

            // On cancel, then hide the modal.
            this.getModal().on(CustomEvents.events.activate, SELECTORS.CANCEL_BUTTON, function(e, data) {

                var cancelEvent = $.Event(ModalEvents.cancel);
                this.getRoot().trigger(cancelEvent, this);

                if (!cancelEvent.isDefaultPrevented()) {
                    this.hide();
                    data.originalEvent.preventDefault();
                }
            }.bind(this));
        };

        // Automatically register with the modal registry the first time this module is imported so that
        // you can create modals of this type using the modal factory.
        if (!registered) {
            ModalRegistry.register(ModalTcEula.TYPE, ModalTcEula, 'plagiarism_turnitinsim/modal_eula');
            registered = true;
        }

        return ModalTcEula;
    }
);