<?php
namespace i4link;

/* Der WordPress Shortcode Name: [i4link] */
const SHORTCODE_NAME = 'i4link';

/* Semester Shortcodeattribut für Links auf Lehrveranstaltungsseiten */
const SHORTCODE_ATTR_SEMESTER = 'semester';

/* Lehrveranstaltungs-Shortcodeattribut für Links auf die entsprechende Kurswebseite */
const SHORTCODE_ATTR_COURSE= 'course';

/* Shortcodeattribut für Verweise auf das eingeblendete Dateisystem */
const SHORTCODE_ATTR_EXTERN = 'extern';

/* Shortcodeattribut für vollständige URLs (mit Domain) */
const SHORTCODE_ATTR_FULL = 'full';

/* Shortcodeattribut für Name des Links (statt dem Pfad) */
const SHORTCODE_ATTR_NAME = 'name';

/* Shortcodeattribut um den Inhalt des Links auszugeben (Name wird dann ignoriert) */
const SHORTCODE_ATTR_RAW = 'raw';

/* Gib für Lehrveranstaltungen die Adresse zum Semester der Seitenhierarchie zurück.
   Falls der Aufruf nicht aus einer Lehrveranstaltung erfolgt oder semester gesetzt ist,
   gib einen Adresse zum aktuellen Semester zurück */
function get($link, $semester = null, $course = null, $extern = false, $full = false) {
	$url = ($full ? get_home_url() : '') . ($extern ? \i4include\URL_BASE : '');

	$absolute_link = !empty($link) ? substr($link, 0, 1) == '/' : false;

	if (empty($semester) && empty($course)) {
		if (!empty($link) && $absolute_link) {
			// Absoluter link
			return $url . $link;
		} else if (is_page()) {
			// Baue URL aus Seitenhierarchie
			global $post;
			foreach (array_reverse(get_post_ancestors($post->ID)) as $page)
				$url .= '/' . get_post($page)->post_name;
			$url .= '/' . $post->post_name;
		}
	} else {
		$url .= '/' . \i4semester\TEACHING_PAGE . '/' . \i4semester\get($semester, 'link');
		if (!empty($course))
			$url .= '/' . $course;
	}
	if (!empty($link)) {
		if (!$absolute_link)
			$url .= '/';
		$url .= $link;
	}
	return $url;
}

/* Behandlungsfunktion, welche von WordPress für jeden i4link Shortcode aufgerufen wird */
function handler_function($attrs, $content = '') {
	$link = get($content, \i4helper\attribute($attrs, SHORTCODE_ATTR_SEMESTER), \i4helper\attribute($attrs, SHORTCODE_ATTR_COURSE), \i4helper\attribute_as_bool($attrs, SHORTCODE_ATTR_EXTERN), \i4helper\attribute_as_bool($attrs, SHORTCODE_ATTR_FULL));
	if (\i4helper\attribute_as_bool($attrs, SHORTCODE_ATTR_RAW)) {
		return esc_html($link);
	} else {
		$name = \i4helper\attribute($attrs, SHORTCODE_ATTR_NAME, $content);
		return '<a href="' . esc_attr($link) . '" title="' . esc_attr($content) . '">' . esc_html(empty($name) ? 'Link' : $name) . '</a>';
	}
}
?>
