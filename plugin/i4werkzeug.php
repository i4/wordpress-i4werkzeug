<?php
/**
* Plugin Name: i4werkzeug
* Plugin URI: https://www4.cs.fau.de
* Description: Einbetten von HTML Dateien (und optional das ganze Verzeichnis) und ein paar weitere Helferlein für das neue i4 WordPress
* Version: 0.9
* Author: Bernhard Heinloth
* Author URI: https://www4.cs.fau.de/~heinloth
**/

define('I4WERKZEUG_PATH', plugin_dir_path(__FILE__));

require_once(I4WERKZEUG_PATH . 'includes/i4helper.php');
require_once(I4WERKZEUG_PATH . 'includes/i4semester.php');
require_once(I4WERKZEUG_PATH . 'includes/i4link.php');
require_once(I4WERKZEUG_PATH . 'includes/i4include.php');
require_once(I4WERKZEUG_PATH . 'includes/i4list.php');
require_once(I4WERKZEUG_PATH . 'includes/i4code.php');
require_once(I4WERKZEUG_PATH . 'includes/i4subnav.php');

// Filter registrieren
add_filter('query_vars', 'i4include\query_vars');
add_filter('init', 'i4include\rewrite_endpoint');
add_filter('wp_list_pages', 'i4subnav\adapt_subnav', 10, 3);
// Rewrite-Regel-Cache spülen wenn Plugin deaktiviert wird
register_deactivation_hook( __FILE__, 'flush_rewrite_rules');
// Registrierung der Verarbeitung bei i4include (vor der Ausgabe)
add_action('template_redirect', 'i4include\redirect_on_shortcode', 1);
// Registrierung der Extraktion von Pagelokalen menüeinträgen
add_action('wp_insert_post', 'i4subnav\action_insert_post', 10, 3);

// Registriere Shortcodes in WordPress
add_shortcode(i4include\SHORTCODE_NAME, 'i4include\shortcode_handler_function');
add_shortcode(i4link\SHORTCODE_NAME, 'i4link\handler_function');
add_shortcode(i4list\SHORTCODE_NAME, 'i4list\handler_function');
add_shortcode(i4semester\SHORTCODE_NAME, 'i4semester\handler_function');
add_shortcode(i4code\SHORTCODE_NAME, 'i4code\handler_function');
add_shortcode(i4subnav\SHORTCODE_NAME, 'i4subnav\handler_function');
?>
