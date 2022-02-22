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
require_once(I4WERKZEUG_PATH . 'includes/i4univis.php');
require_once(I4WERKZEUG_PATH . 'includes/i4hiddentext.php');

// Filter registrieren
add_filter('query_vars', 'i4include\query_vars');
add_filter('init', 'i4include\rewrite_endpoint');
add_filter('wp_list_pages', 'i4subnav\adapt_subnav', 10, 3);
function i4_adapt_multilang_perms($metaCaps) {
	// Sprachvarianten einer Seite auch durch Benutzer mit "Redakteurs"-Rechten bearbeiten lassen
	$metaCaps['rrze_multilang_access_all_locales'] = 'manage_categories';
	return $metaCaps;
}
add_filter('rrze_multilang_map_meta_cap', 'i4_adapt_multilang_perms');
// Rewrite-Regel-Cache spülen wenn Plugin deaktiviert wird
register_deactivation_hook( __FILE__, 'flush_rewrite_rules');
// Registrierung der Verarbeitung bei i4include (vor der Ausgabe)
add_action('template_redirect', 'i4include\redirect_on_shortcode', 1);
// Registrierung der Extraktion von Pagelokalen menüeinträgen
add_action('wp_insert_post', 'i4subnav\action_insert_post', 10, 3);

// Wordpress 404 redirection logik deaktivieren, vgl.:
// https://make.wordpress.org/core/2020/06/26/wordpress-5-5-better-fine-grained-control-of-redirect_guess_404_permalink/
add_filter( 'do_redirect_guess_404_permalink', '__return_false' );

// Registriere Shortcodes in WordPress
add_shortcode(i4include\SHORTCODE_NAME, 'i4include\shortcode_handler_function');
add_shortcode(i4link\SHORTCODE_NAME, 'i4link\handler_function');
add_shortcode(i4list\SHORTCODE_NAME, 'i4list\handler_function');
add_shortcode(i4semester\SHORTCODE_NAME, 'i4semester\handler_function');
add_shortcode(i4code\SHORTCODE_NAME, 'i4code\handler_function');
add_shortcode(i4subnav\SHORTCODE_NAME, 'i4subnav\handler_function');
add_shortcode(i4univis\SHORTCODE_NAME, 'i4univis\handler_function');
add_shortcode(i4hiddentext\SHORTCODE_NAME, 'i4hiddentext\shortcodeHiddenText');

// Eigene Styles
function i4werkzeug_admin_style() {
	wp_enqueue_style('i4werkzeug-admin-style', plugins_url('styles/admin.css', __FILE__));
}
add_action('admin_enqueue_scripts', 'i4werkzeug_admin_style');
function i4werkzeug_style() {
	wp_enqueue_style('i4werkzeug-style-theme', plugins_url('styles/theme.css', __FILE__));
	wp_enqueue_style('i4werkzeug-style-univis', plugins_url('styles/univis.css', __FILE__));
	wp_enqueue_style('i4werkzeug-style-semplan', plugins_url('styles/semplan.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'i4werkzeug_style');
?>
