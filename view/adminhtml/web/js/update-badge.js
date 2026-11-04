/**
 * Pose le nombre de modules Maxcode à mettre à jour sur l'onglet « Maxcode » du menu de gauche de la
 * configuration. Le compteur vient du serveur (catalogue embarqué) : aucune requête n'est faite.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config) {
        var $tab = $('.maxcode-tab'),
            $title = $tab.children('.admin__page-nav-title');

        if (!$title.length || !config.count) {
            return;
        }

        $title.find('.maxcode-core-tab-badge').remove();

        $('<span class="maxcode-core-tab-badge" role="img"></span>')
            .attr('aria-label', config.label)
            .attr('title', config.label)
            .text(config.count)
            .appendTo($title);

        $tab.addClass('maxcode-has-updates');
    };
});
