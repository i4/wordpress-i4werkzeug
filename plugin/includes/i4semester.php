<?php
namespace i4semester;

/* Der WordPress Shortcode Name: [i4semester] */
const SHORTCODE_NAME = 'i4semester';

/* Attribut für relative Änderung des Jahres */
const SHORTCODE_ATTR_DELTA = 'delta';

/* Formatierungsattribut für den Shortcode */
const SHORTCODE_ATTR_FORMAT = 'format';

/* Shortcut für das aktuelle Semester (damit ersparen wir uns das prüfen von Regexe) */
const CURRENT_SEMESTER = 'current';


/* (URL)Name der Lehrseite (welche die höchste Hierarchiestufe hat).
   Das Semester ist dann die Seite direkt eine Stufe darunter:
   Lehre (lehre)
     +- Wintersemester 2021/22 (ws21)
          +- Lehrveranstaltung -> Shortcode nutzt (ohne weitere Angabe eines Namens) dann ws21
              +- Unterseite  -> Shortcode nutzt (ohne weitere Angabe eines Namens) dann ebenfalls ws21 */
const TEACHING_PAGE = 'lehre';


/* Erhalte ein Semester in gewünschten Format,
   parse dazu den übergebenen Namen oder verwende das aktuelle Semester */
function get($name = '', $format = 'long', $delta = null) {
	// Suche in der Seitenhierarchie das Semester, zu dem diese Seite zugeordnet ist
	if (empty($name) && is_page()) {
		$parents = get_post_ancestors(null);
		if ($parents && count($parents) >= 2 && get_post($parents[count($parents) - 1])->post_name == TEACHING_PAGE) {
			$name = get_post($parents[count($parents) - 2])->post_name;
		}
	}
	// Aktuelles Jahr & Monat holen
	$year = date("y");
	$month = date("m");
	// Parse den Namen, sofern vorhanden
	if (!empty($name) && $name != CURRENT_SEMESTER && preg_match('/^s(?:o(?:mmer)?)?s(?:e(?:mester)?)?[ ]?(?:(?:[0-9]{2})?([0-9]{2}))?$/i', $name, $matches)) {
		// Sommersemester
		$winter = false;
		if (array_key_exists(1, $matches)) {
			// Jahresangabe vorhanden
			$year = $matches[1];
		} else if ($month < 4) {
			// Ohne Jahresangaben immer auf das letzte Semester zeigen (d.h. bis April das Vorjahr)
			$year--;
		}
	} else if (!empty($name) && $name != CURRENT_SEMESTER && preg_match('/^w(?:i(?:nter)?)?s(?:e(?:mester)?)?[ ]?(?:(?:[0-9]{2})?([0-9]{2})(?:\/(?:[0-9]{2}|[0-9]{4}))?)?$/i', $name, $matches)) {
		// Wintersemester
		$winter = true;
		if (array_key_exists(1, $matches)) {
			// Jahresangabe vorhanden
			$year = $matches[1];
		} else if ($month < 10) {
			// Ohne Jahresangaben immer auf das letzte Semester zeigen (d.h. bis September das Vorjahr)
			$year--;
		}
	} else {
		if ($month < 4) {
			$year--;
			$winter = true;
		} else if ($month >= 10) {
			$winter = true;
		} else {
			$winter = false;
		}
	}
	// Delta
	if (!empty($delta) && is_numeric($delta)) {
		$i = intval($delta);
		if (abs($delta - $i) == "0.5") {
			if ($winter && $delta > 0)
				$year++;
			else if (!$winter && $delta < 0)
				$year--;
			$winter = !$winter;
		}
		$year += $i;
	}
	// Überlaufbehandlung
	if ($year < 0)
		$year = 99;

	// Formatiere die Ausgabe
	$y = str_pad($year, 2, "0", STR_PAD_LEFT);
	$yn = str_pad(($year + 1) % 100, 2, "0", STR_PAD_LEFT);
	switch ($format) {
		case 'long':
			return $winter ? ('Wintersemester 20'.$y.'/'.$yn) : ('Sommersemester 20'.$y);
		case 'short':
			return $winter ? ('WiSe 20'.$y.'/'.$yn) : ('SoSe 20'.$y);
		case 'abbr':
			return $winter ? ('WS'.$y) : ('SS'.$y);
		case 'link':
			return $winter ? ('ws'.$y) : ('ss'.$y);
		case 'univis':
			return '20' . $y . ($winter ? 'w' : 's');
		default:
			return $winter ? ('WS 20'.$y.'/'.$yn) : ('SS 20'.$y);
	}
}

/* Behandlungsfunktion, welche von WordPress für jeden i4semester Shortcode aufgerufen wird */
function handler_function($attrs, $content, $tag) {
	return get($content, \i4helper\attribute($attrs, SHORTCODE_ATTR_FORMAT), \i4helper\attribute($attrs, SHORTCODE_ATTR_DELTA));
}
?>
