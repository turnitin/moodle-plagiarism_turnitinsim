/**
 * @module plagiarism_turnitincheck/inbox_eula_launch
 */

define(['jquery',
        'core/templates',
        'core/modal_factory',
        'plagiarism_turnitincheck/modal_eula'
    ],
    function($, Templates, ModalFactory, ModalTcEula) {
        return {
            inbox_eula_launch: function() {
                var trigger = $('.eula-row-launch');

                ModalFactory.create(
                    {
                        type: ModalTcEula.TYPE
                    },
                    trigger
                );
            }
        };
    }
);