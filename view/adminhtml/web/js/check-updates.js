/**
 * Bouton « Vérifier les mises à jour ».
 *
 * La liste des modules est affichée DANS le formulaire de configuration de Magento : on ne peut pas y imbriquer un
 * second formulaire. Le clic construit donc un formulaire POST à part (avec la clé de formulaire) et l'envoie.
 * Rien ne part sur le réseau tant que l'administrateur n'a pas cliqué.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).on('click', function (event) {
            var $form;

            event.preventDefault();
            $(element).prop('disabled', true).attr('aria-busy', 'true');

            $form = $('<form method="post"></form>')
                .attr('action', config.url)
                .append($('<input type="hidden" name="form_key"/>').val(window.FORM_KEY))
                .appendTo('body');
            $form.trigger('submit');
        });
    };
});
