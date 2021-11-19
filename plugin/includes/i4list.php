<?php
namespace i4list;

/* Der Name des Shortcodes, wie er im Editor in WordPress verwendet werden muss: [i4list */
const SHORTCODE_NAME= 'i4list';

/* Shortcode-attribut mit dem Namen für die Liste */
const SHORTCODE_ATTR_NAME = 'name';


/* Der Name des Shortcode-attributs, mit dem die Datumsanzeige aktiviert werden kann */
const SHORTCODE_ATTR_SHOWDATE = 'showdate';

/* Der Name des Shortcode-attributs, mit dem das einblenden der Seite gesteuert werden kann */
const SHORTCODE_ATTR_UNCOVER = 'uncover';


/* Deutsche Namen der Wochentage (für den Fall, dass wir die locals nicht haben) */
const WEEKDAYS = array('Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag');

/* Deutsche Namen der Monate (für den Fall, dass wir die locals nicht haben) */
const MONTHS = array(
	 1 => 'Januar',
	 2 => 'Februar',
	 3 => 'März',
	 4 => 'April',
	 5 => 'Mai',
	 6 => 'Juni',
	 7 => 'Juli',
	 8 => 'August',
	 9 => 'September',
	10 => 'Oktober',
	11 => 'November',
	12 => 'Dezember'
);

/* Zeichen welche als Liste interpretiert werden sollen */
const LIST_ITEMS = '\*|&#8211;|-';

/* Formatierung des Textes (eine sehr knappes, schnell hingehacktes Markdownsubset) */
function format($text) {
	// Listen
	$text = preg_replace_callback('/(?:\n(\s+)(' . LIST_ITEMS . '|[0-9]+)[.]?\s+(.+(?:\n\1.+)+))\n/u',
		function ($match) {
			$list = is_numeric($match[2]) ? 'ol' : 'ul';
			return "\n<$list>\n\t<li>" . implode("</li>\n\t<li>", preg_split('/\n' . $match[1] . ($list == 'ul' ? '(' . LIST_ITEMS . ')' : '[0-9]+[.]?') . '\s+/', $match[3])) . "</li>\n</$list>\n";
		}, $text);

	// Fett
	$text = preg_replace('/(?<!\*)\*\*([^\*\s][^\*]*?)\*\*(?!\*)/', '<strong>\1</strong>', $text);
	// Kursiv
	$text = preg_replace('/(?<!\*)\*([^\*\s][^\*]*?)\*(?!\*)/', '<i>\1</i>', $text);
	// Unterstrichen
	$text = preg_replace('/(?<!_)([_][_]+)([^_\s][^_]*?)\1(?!_)/', '<u>\2</u>', $text);
	// Horizontale Linie
	$text = preg_replace('/^\s*(--[-]+|&#8212;)\s*$/m', '<hr>', $text);
	// Links (absolut)
	$text = preg_replace('#\[(.+?)\]\((http[s]://.+?)\)#i','<a href="\2">\1</a>', $text);
	// Links (relativ)
	$text = preg_replace_callback('#\[(.*?)\]\((.+?)\)#i',
		function ($match) {
			return '<a href="' . esc_attr(\i4link\get($match[2], null, null, true)) . '">' . esc_html($match[1]) . '</a>';
		}, $text);
	// FAU.tv
	$text = preg_replace('#(?<=^|\s)(https://www\.(?:fau\.tv|video\.uni-erlangen\.de)/clip/id/[0-9]+)(?=\s|$)#i','[fauvideo url="\1"]', $text);
	return trim($text) . "\n";
}

/* Generiere eine Akkordionliste mit formatierten Elementen*/
function generate($content, $name = '', $showdate = false, $uncover = null) {
	$id_prefix = empty($name) ? 'el' : \i4helper\to_anchortext($name);

	$out = "[collapsibles]\n";
	$matches = preg_split('/^(#(?!#)|[0-9-\/.]{10}\s+)(.*)\n/m', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
	for ($i = 1; $i + 2 < count($matches); $i += 3) {
		// Parse timestamp
		$timestamp = $matches[$i] == '#' ? false : strtotime($matches[$i]);

		// Setze auf Mitte des Tages (um fehler mit Schaltsekunden zu vermeiden)
		if ($timestamp !== false)
			$timestamp += 43200;

		//.Generiere id
		$id = $id_prefix . '-' . (($i - 1) / 3);

		// Uncover = hidden-text
		if ($timestamp !== false && is_numeric($uncover))
			$out .= '[hidden-text end="' . date('Y-m-d', $timestamp - $uncover * 86400) . '"]' . "\n";

		// Neues Akkordionelement
		$out .= '[collapse title="' . esc_attr(trim($matches[$i + 1])) . '" name="' . $id . '"]' . "\n";

		// Zeige Datum
		if ($timestamp !== false && $showdate)
			$out .= $name . ' am ' . WEEKDAYS[date('w', $timestamp)] . date(', j. ', $timestamp) . MONTHS[date('n', $timestamp)] . date(' Y', $timestamp) . "\n\n";

		$blocks = preg_split('/^#[#]+\s*(.*)\\n/m', $matches[$i + 2], -1, PREG_SPLIT_DELIM_CAPTURE);
		$out .= format($blocks[0]);
		if (count($blocks) > 1) {
			$out .= "[accordion]\n";
			// Optional noch unter-akkordions
			for ($j = 1; $j + 1 < count($blocks); $j += 2) {
				$out .= '[accordion-item title="' . $blocks[$j] . '" name="' .$id . '-' . (($j - 1) / 2) . '"]' . "\n"
				     . format($blocks[$j + 1])
				     . "[/accordion-item]\n";
			}
			$out .= "[/accordion]\n";
		}
		$out .= "[/collapse]\n";
		if ($timestamp !== false && is_numeric($uncover))
			$out .= "[/hidden-text]\n";
	}
	return $out . "[/collapsibles]\n";
}


/* Behandlungsfunktion, welche von WordPress für jeden i4list Shortcode aufgerufen wird */
function handler_function($attrs, $content, $tag) {
	return do_shortcode(generate($content, \i4helper\attribute($attrs, SHORTCODE_ATTR_NAME, ''), \i4helper\attribute_as_bool($attrs, SHORTCODE_ATTR_SHOWDATE), \i4helper\attribute($attrs, SHORTCODE_ATTR_UNCOVER)));
}
?>
