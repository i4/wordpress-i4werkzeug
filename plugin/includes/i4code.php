<?php
namespace i4code;

/* Der WordPress Shortcode Name: [i4code] */
const SHORTCODE_NAME = 'i4code';

/* Behandlungsfunktion, welche von WordPress für jeden i4code Shortcode aufgerufen wird */
function handler_function($attrs, $content = '') {
	return '<code>' . esc_html($content) . '</code>';
}
?>
