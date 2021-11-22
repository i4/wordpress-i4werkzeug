<?php
namespace i4univis;

/* Der Name des Shortcodes, wie er im Editor in WordPress verwendet werden muss: */
const SHORTCODE_NAME = 'i4univis';

/* Der URL Prefix zur PRG Schnittstelle */
const UNIVIS_PRG_URL = 'http://univis.uni-erlangen.de/prg?';

/* UnivIS Cache Dauer (in Sekunden) */
const CACHE_EXPIRATION =  60 * 60 * 4;  // 4 Stunden

/* Behandlungsfunktion, welche von WordPress für jeden i4univis Shortcode aufgerufen wird */
function handler_function($attr, $content = '') {
	// Standard codeset für Antworten: UTF8
	if (array_key_exists('codeset', $attr) === false) {
		$attr['codeset'] = 'utf8';
	}
	// Prefix: Titel (falls angegeben.
	$prefix = empty($content) ? '' : '<h2 id="' . \i4helper\to_anchortext($content) . '">' . $content . '</h2>';
	// UnivIS braucht Latin1 encoding
	$attr = mb_convert_encoding($attr, 'LATIN1');
	// Sortieren (für hash)
	ksort($attr);
	// Query bauen
	$url = UNIVIS_PRG_URL . http_build_query($attr);
	// Transient Name (für cache)
	$transient = 'i4univis_' . md5($url);
	// Cache prüfen
	$data = get_transient($transient);
	if (false === $data) {
		// Hole daten
		$urldata = file_get_contents($url);
		if ($urldata === false) {
			// ungültige URL
			return $prefix . '[alert style="danger"]Der UnivIS-Aufruf (<a href="' . $url . '">' . $url . '</a>) schlug fehl![/alert]';
		} else if (preg_match('@<tr><td valign="top" colspan=2><table border=0 width="100%" bgcolor="#ffffff" cellspacing=17 cellpadding=0>\n<tr><td>(.*)(?:<p>)?</td></tr>\n</table></td>\n</tr>@mus', $urldata, $match)) {
			// Daten im passenden Format
			$data = $match[1];
			// Setze Cache
			set_transient($transient, $data, CACHE_EXPIRATION);
		} else {
			// ungültige Antwortdaten
			return $prefix . '[alert style="danger"]Die Antwort-Daten des UnivIS-Aufrufs (<a href="' . $url . '">' . $url . '</a>) sind ungültig![/alert]';
		}
	}
	// Antwort zurückgeben
	return $prefix . '<div class="i4univis' . (array_key_exists('search', $attr) ? ' i4univis_' . esc_html($attr['search']) : ''). '">' . $data . '</div>';
}
?>
