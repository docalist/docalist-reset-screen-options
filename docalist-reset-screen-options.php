<?php
/**
 * This file is part of the 'Docalist Reset Screen Options' plugin.
 *
 * Copyright (C) 2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Plugin Name: Docalist Reset Screen Options
 * Plugin URI:  http://docalist.org/
 * Description: Docalist: Reset Screen Options.
 * Version:     0.1
 * Author:      Daniel Ménard
 * Author URI:  http://docalist.org/
 * Text Domain: drso
 * Domain Path: /languages
 *
 * @package     Docalist
 * @subpackage  ResetScreenOptions
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 */

namespace Docalist\ResetScreenOptions;

use WP_Screen;

// Charge les fichiers de traduction du plugin
// plus tard : on a une seule chaine
// load_plugin_textdomain('drso', false, 'docalist-reset-screen-options/languages');

/**
 * Retourne les options actuelles de l'utilisateur pour l'écran passé en
 * paramètre.
 *
 * La méhode recherche tous les enregistrements présents dans la table
 * wp_usermeta pour l'utilisateur en cours dont le nom correspond à l'écran
 * indiqué.
 *
 * @param string $screen Le nom de code de l'écran recherché.
 * @param string $limit Retourne uniquement la première réponse obtenue
 * (utile pour savoir s'il y a ou non des options pour cet écran).
 *
 * @return array un tableau contenant les noms des options trouvées,
 * éventuellement vide.
 */
function drsoScreenOptions($screen, $firstOnly = false) {
    global $wpdb;

    $user = get_current_user_id();

    // La liste a été obtenue en recherchant "update_user_option" dans le
    // fichier wp-admin/includes/ajax-actions.php de WordPress
    $keys = "('manage{$screen}columnshidden','screen_layout_$screen','meta-box-order_$screen','closedpostboxes_$screen','metaboxhidden_$screen')";

    $sql = "SELECT DISTINCT meta_key FROM $wpdb->usermeta WHERE user_id=$user AND meta_key IN $keys";
    $firstOnly && $sql .= " LIMIT 1";

    return $wpdb->get_col($sql);
}


/*
 * Sources utiles pour la manipulation de la zone "options" de l'écran :
 * - http://w-shadow.com/blog/2010/06/29/adding-stuff-to-wordpress-screen-options/
 * - http://plugins.svn.wordpress.org/raw-html/trunk/include/screen-options/
 */

/**
 * Ajoute une option "reset" dans les options de l'écran si l'utilisateur a
 * modifié les options par défaut.
 */
add_filter('screen_settings', function($html, WP_Screen $screen) {
    // On ne fait quelque chose que si l'utilisateur a modifié les options
    if (drsoScreenOptions($screen->id, true)) {
        // Ajoute le lien en fin de html
        $link = sprintf(
            '<p><a id="reset-screen-options" href="">%s</a></p>',
            __('Restaurer les options par défaut', 'drso'),
            $screen->id
        );
        $html .= $link;

        // Quand on clique, génère une requête ajax reset et recharge la page
        $html .= "
        <script>
            jQuery(function($){
                $('#reset-screen-options').click(function() {
                    $.post(
                        'admin-ajax.php',
                        'action=docalist_reset_screen_options&screen=$screen->id',
                        function() { window.location.reload() }
                    );
                    return false;
                });
            });
        </script>";
    }

    return $html;
}, 10, 2);

/**
 * Handler de la requête ajax
 */
add_action('wp_ajax_docalist_reset_screen_options', function() {
    // Récupère l'écran à réinitialiser et nettoie
    if (empty($_REQUEST['screen']) || '' === $screen = sanitize_key($_REQUEST['screen'])) {
        die(0);
    }

    $user = get_current_user_id();
    foreach(drsoScreenOptions($screen) as $option) {
        delete_user_option($user, $option, true);
    }
    die(1);
});