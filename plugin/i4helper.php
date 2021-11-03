<?php
/**
* Plugin Name: i4helper
* Plugin URI: https://www4.cs.fau.de
* Description: Einbetten von HTML Dateien (und optional das ganze Verzeichnis) und ein paar weitere Helferlein für das neue i4 WordPress
* Version: 1.1
* Author: Bernhard Heinloth
* Author URI: https://www4.cs.fau.de/~heinloth
**/

/* Die sog. Query Variable, d.h. der Name der Variable,
   welche in WordPress für dynamische Inhalte (bei `dir="true"`) für die aktuelle
   angeforderte Datei verwendet wird (die URL wird um diesen Variablennamen erweitert) */
$i4include_queryvar = 'extern';

/* Der Name des Shortcodes, wie er im Editor in WordPress verwendet werden muss */
$i4include_shortcode_name = 'i4include';

/* Der Name des Shortcode-attributs, der dynamische Inhalte aktivieren kann */
$i4include_shortcode_attr_dynamic = 'dynamic';

/* Der Name des Shortcode-attributs, der den Pfad zu einem Kurs ermittelt */
$i4include_shortcode_attr_course = 'course';

/* Der Name des Shortcode-attributs, der das Semester bei einer Lehrveranstaltung angibt ermittelt (benötigt course!) */
$i4include_shortcode_attr_semester = 'semester';

/* Der Name des Shortcode-attributs, der die Verarbeitung weiterer Shortcodes im Zieldokument erlaubt */
$i4include_shortcode_attr_shortcode = 'shortcodes';

/* Der Name des Shortcode-attributs, der die Anzeige von Fehlern auf der Webseite (zu Debugzwecken) erlaubt */
$i4include_shortcode_attr_showerror = 'showerrors';

/* Regulärer Ausdruck, welcher die validen (absoluten) Pfade für den Shortcode definiert.
   Jedes Teilmuster muss auf `/.*` enden, damit die Ordnernamen vollständig gematcht werden */
$i4include_allowed_path = '#^(http[s]?://(?:[^/]+\.fau\.de|[^/]+\.uni-erlangen\.de|localhost[:0-9]*)/.*|/proj.stand/i4wp/extern/.*)$#i';

/* Basispfad für relative Pfade (sollte natürlich vom obigen regulären Ausdruck akzeptiert werden) */
$i4include_base_path = '/proj.stand/i4wp/extern';

/* Webzugriff auf Basispfad */
$i4include_base_path_web = '/extern';

/* Erlaubte Dateinamenerweiterungen für eingebettete Inhalte */
$i4include_ext_html = array('htm', 'html', 'shtml', 'ushtml');

/* Dateinamenerweiterungen für Binärdateien (und die dazugehörigen MIME Typen)
   für direktes durchreichen an den Webbrowser */
$i4include_ext_bin = array(
	'gif' => 'image/gif',
	'png' => 'image/png',
	'jpg' => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'svg' => 'image/svg+xml',
	'txt' => 'text/plain',
	'h' => 'text/x-c',
	'c' => 'text/x-c',
	'cc' => 'text/x-c++',
	'cpp' => 'text/x-c++',
	'xml' => 'text/xml',
	'css' => 'text/css',
	'js' => 'text/javascript',
	'pdf' => 'application/pdf',
	'gz' => 'application/gzip',
	'zip' => 'application/zip',
	'sh' => 'application/x-sh',
);

/* Erweiterungen, welche zwingend via WordPress durchgereicht werden müssen */
$i4include_ext_bin_force_pass_through = array('svg');

/* Statusvariable, welche Rekursionen von includes erkennen und verhindern kann */
$i4include_recursion = array();

/* Statusvariable zum Erkennen von mehrfachen dynamic include Shortcodes auf einer Seite */
$i4include_dynamic_path = '';

/* Name der Basisadresse für Lehre */
$i4semester_teaching = 'lehre';

/* Der Name des Shortcode-attributs, der das semester angibt */
$i4course_shortcode_attr_semester = 'semester';

$i4course_shortcode_name = 'i4course';

$i4semester_shortcode_attr_format = 'format';

$i4semester_shortcode_name = 'i4semester';


/* Hilfsfunktion, welche prüft ob es ein Shortcode-Attribut gibt */
function i4helper_has_attribute($attributes, $name) {
	return !empty($attributes) && array_key_exists($name, $attributes);
}


/* Hilfsfunktion, welche prüft ob es ein Shortcode-Attribut gibt
   und ggf dessen Wert zurück gibt (falls nicht, dann wird `null` zurück gegeben */
function i4helper_attribute($attributes, $name) {
	return i4helper_has_attribute($attributes, $name) ? $attributes[$name] : null;
}

/* Hilfsfunktion, welche prüft ob es ein boolsches Shortcode-Attribut gibt
   und ggf dessen Wert zurück gibt (falls nicht, dann wird `false` zurück gegeben */
function i4helper_attribute_as_bool($attributes, $name) {
	return i4helper_has_attribute($attributes, $name)
	    && filter_var($attributes[$name], FILTER_VALIDATE_BOOLEAN);
}



/* Bekomme das aktuelle Semester bzw parse den Namen */
function i4semester_get($name = '', $format = 'long') {
	if (empty($name) && is_page()) {
		global $post, $i4semester_teaching;
		$parents = get_post_ancestors($post->ID);
		if ($parents && count($parents) >= 2 && get_post($parents[count($parents) - 1])->post_name == $i4semester_teaching) {
			$name = get_post($parents[count($parents) - 2])->post_name;
		}
	}
	if (!empty($name) && preg_match('/^s(?:o(?:mmer)?)?s(?:e(?:mester)?)?[ ]?(?:(?:[0-9]{2})?([0-9]{2}))$/i', $name, $matches)) {
		$winter = false;
		$year = $matches[1];
	} else if (!empty($name) && preg_match('/^w(?:i(?:nter)?)?s(?:e(?:mester)?)?[ ]?(?:[0-9]{2})?(?:([0-9]{2})(?:\/(?:[0-9]{2}|[0-9]{4}))?)$/i', $name, $matches)) {
		$winter = true;
		$year = $matches[1];
	} else {
		$year = date("y");
		$month = date("m");
		if ($month < 4) {
			$year--;
			if ($year < 0)
				$year = 99;
			$winter = true;
		} else if ($month >= 10) {
			$winter = true;
		} else {
			$winter = false;
		}
	}

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
		default:
			return $winter ? ('WS 20'.$y.'/'.$yn) : ('SS 20'.$y);
	}
}

function i4semester_handler_function($attrs, $content, $tag) {
	global $i4semester_shortcode_attr_format;
	return i4semester_get($content, i4helper_attribute($attrs, $i4semester_shortcode_attr_format));
}
add_shortcode($i4semester_shortcode_name, 'i4semester_handler_function' );


/* Gib für Lehrveranstaltungen die Adresse zum Semester der Seitenhierarchie zurück.
   Falls der Aufruf nicht aus einer Lehrveranstaltung erfolgt oder semester gesetzt ist,
   gib einen Adresse zum aktuellen Semester zurück */
function i4course_get($name, $semester = null) {
	global $i4semester_teaching;
	return '/'.$i4semester_teaching.'/'.i4semester_get($semester, 'link').'/'.$name;
}

function i4course_handler_function($attrs, $content = '') {
	global $i4course_shortcode_attr_semester;
	return i4course_get($content, i4helper_attribute($attrs, $i4course_shortcode_attr_semester));
}
add_shortcode($i4course_shortcode_name, 'i4course_handler_function' );


/* Hilfsfunktion zur Bestimmung der relevanten Pfadteile.
     $shortcode_path ist der im Shortcode übergebene Pfad
     $shortcode_attr ist ein Array mit den in Shortcode angegebenen Attributen

   Diese Funktion liefert ein assoziatives Array mit folgenden Inhalten zurück:
     ['full']    ist der absolute Pfad der Datei die im Shortcode eingegeben wird
     ['base']    ist das Verzeichnis zum im Shortcode angegebenen Pfad
                 (also unabhängig vom dynamischen Endpoint)
     ['link']    ist die volle URL (inklusive Endpoint) zu der aktuell angezeigten Wordpress Seite
     ['file']    ist die aktuell zu inkludierende Datei
                 (welche auch anhand des übergebenen Endpoint bestimmt wird)
     ['dir']     ist der volle Pfad des aktuellen Verzeichnisses der zu
                 inkludierenden Datei (unter Berücksichtigung des Endpoints)
     ['ext']     ist die Dateinamenerweiterung der aktuell angeforderten Datei (ohne `.`)
     ['path']    ist der resultierende volle Pfad der aktuell zu inkludierenden Datei
                 (unter Berücksichtigung des Endpoints)
     ['valid']   nur wahr, wenn der resultierende Pfad gültig ist
     ['dynamic'] wird auf wahr gesetzt, wenn dynamische Inhalte unterstützt werden
     ['query']   Der Wert der Variable `extern` (dieses Arrayelement existiert nur,
                 sofern `dynamic` auf wahr gesetzt und `extern` vorhanden ist)
*/
function i4include_pathinfo($shortcode_path, $shortcode_attr) {
	global $i4include_base_path, $i4include_dynamic_path, $i4include_queryvar, $i4include_shortcode_attr_dynamic, $i4include_shortcode_attr_course, $i4include_shortcode_attr_semester, $i4include_allowed_path;

	// ggf. relativen Pfad anpassen
	if (stream_is_local($shortcode_path)) {
		if (!path_is_absolute($shortcode_path)) {
			if (i4helper_has_attribute($shortcode_attr, $i4include_shortcode_attr_course))
				$shortcode_path = $i4include_base_path.i4course_get($shortcode_attr[$i4include_shortcode_attr_course], i4helper_attribute($shortcode_attr, $i4include_shortcode_attr_semester)).'/'.$shortcode_path;
			else
				$shortcode_path = $i4include_base_path.'/'.$shortcode_path;
		}
		$shortcode_path = realpath($shortcode_path);
	}

	// Rückgabe Array initialisieren
	$r = array(
		'full' => $shortcode_path,
		'base' => dirname($shortcode_path),
		// Prüfe, ob das Attribut `dynamic` vorhanden & auf wahr gesetzt ist
		'dynamic' => i4helper_attribute_as_bool($shortcode_attr, $i4include_shortcode_attr_dynamic)
	);

	if ($r['dynamic']) {
		// Dynamisch (Pfad anhand Shortcode sowie  `extern`, sofern vorhanden)
		if (empty($i4include_dynamic_path)){
			$i4include_dynamic_path = $shortcode_path;
		}

		/* Da `get_query_var()` bei nicht vorhandener Variable eine leere
		   Zeichenkette liefert, was aber auch ein valider Wert sein kann,
		   ein kleiner Hack: Der Inhalt von `$notset` ist ein invalider Wert
		   (den `extern` nicht annehmen kann), welcher nun bei `get_query_var`
		   als default (d.h. wenn die Variable nicht vorhanden ist)
		   zurückgegeben wird */
		$notset='/notset/';
		$query_var = get_query_var($i4include_queryvar, $notset);
		if ($query_var == $notset) {
			// Variable `extern` nicht gesetzt, d.h. wir berücksichtigen nur den Shortcodepfad
			$r['file'] = basename($shortcode_path);
			$r['dir'] = $r['base'];
			$r['link'] = get_permalink().'/'.$i4include_queryvar.'/'.$r['file'];
			$r['path'] = $shortcode_path;
		} else {
			// Variable `extern` gesetzt, d.h. wir kombinieren diese mit den Shortcodepfad
			$r['query'] = $query_var;
			$r['link'] =  get_permalink().'/'.$i4include_queryvar.'/'.$query_var;
			$r['file'] = basename($query_var);
			$dir = dirname($query_var);
			$r['dir'] = $r['base'].( $dir != '.' ? '/'.$dir : '');
			$r['path'] = $r['dir'].'/'.$r['file'];
			if (stream_is_local($r['path'])) {
				$r['path'] = realpath($r['path']);
			}
		}
	} else {
		// Statisch (Verwende nur Shortcodepfad)
		$r['dynamic'] = FALSE;
		$r['file'] = basename($shortcode_path);
		$r['dir'] = $r['base'];
		$r['link'] = get_permalink();
		$r['path'] = $shortcode_path;
	}

	// Dateiendung
	$r['ext'] = strtolower(substr($r['file'], strrpos($r['file'], '.') + 1));

	// Prüfe ob Pfad valid (er muss mit 'base' beginnen und dem Regex entsprechen)
	$r['valid'] = substr($r['path'], 0, strlen($r['base'])) === $r['base'] &&
	              preg_match($i4include_allowed_path, $r['path']) > 0;

	return $r;
}


/* Setze `extern` als zusätzliche Wordpress Variable */
function i4include_query_vars($vars) {
	global $i4include_queryvar;
	$vars[] = $i4include_queryvar;
	return $vars;
}
add_filter('query_vars', 'i4include_query_vars');


/* Registriere `extern` als WordPress Endpoint
   (das beeinflusst die Rewrite Regeln, welche ggf erneuert werden müssen */
function i4include_rewrite_endpoint() {
	global $i4include_queryvar;
	add_rewrite_endpoint($i4include_queryvar, EP_PERMALINK | EP_PAGES );

	/* Nachfolgende Zeile ist für die Entwicklung hilfreich:
	   Sie erneuert die rewrite rules, was notwendig ist,
	   wenn z.B. $i4include_queryvar geändert wurde */
	//flush_rewrite_rules();
}
add_filter('init','i4include_rewrite_endpoint');


/* Das Herz: diese Funktion wird für jeden Shortcode `[i4include ...]` aufgerufen,
   liest die entsprechende Datei und gibt diese aus */
function i4include_handler_function($attrs, $content = '/') {
	global $i4include_dynamic_path, $i4include_ext_html, $i4include_ext_bin, $i4include_shortcode_attr_shortcode, $i4include_shortcode_attr_showerror, $i4include_recursion;
	$pathinfo = i4include_pathinfo($content, $attrs);
	if ($pathinfo['dynamic'] && $i4include_dynamic_path != $pathinfo['full']) {
		$error = 'es darf nicht mehrere <tt>dynamic includes</tt> mit unterschiedlichen Pfaden (<tt>'.esc_html($i4include_dynamic_path).'</tt> und <tt>'.esc_html($pathinfo['full']).'</tt>) auf dieser Seite geben!';
	} else if (empty($pathinfo['path'])) {
		$error = 'die Datei <tt>'.esc_html($pathinfo['file']).'</tt> existiert im Verzeichnis <tt>'.esc_html($pathinfo['dir']).'</tt> nicht!';
	} else if (!$pathinfo['valid']) {
		$error = 'das Einbetten des Pfads <tt>'.esc_html($pathinfo['path']).'</tt> ist nicht erlaubt!';
	} else if (!in_array($pathinfo['ext'], $i4include_ext_html)) {
		$error = 'Dateien mit der Endung <tt>'.esc_html($pathinfo['ext']).'</tt> sind nicht erlaubt!';
	} else if (in_array($pathinfo['path'], $i4include_recursion)) {
		$error = 'die Datei <tt>'.esc_html($pathinfo['path']).'</tt> soll erneut (endlos)rekursiv eingebunden werden!';
	} else if ($pathinfo['dynamic'] && count($i4include_recursion) > 0) {
		$error = 'dynamische Includes sind nur in WordPress möglich, nicht jedoch über eingebundene Seiten!';
	} else {
		$result = file_get_contents($pathinfo['path']);
		if ($result === false) {
			$error = 'die Datei <tt>.'.esc_html($pathinfo['path']).'</tt> konnte nicht gelesen werden!';
		} else {
			// Falls von  unserer www4 Webseite was eingebettet wird, entferne Kopf und Fußzeile
			// TODO: irgendwann entfernen.
			if (preg_match('#http[s]?://www4\.[^/]+(?:fau|uni-erlangen)\.de(/.*)#i', $content, $contenturl) > 0 && preg_match('/(<div id="content">.*)<!-- beginning footer\.shtml -->/sm', $result, $contentdiv)) {
				$result = str_replace(array($pathinfo['dir'], dirname($contenturl[1]).'/'), './', $contentdiv[1]);
			}
			// Sofern das Attribut `shortcodes` aktiviert ist, werden im engebundenen Dokument die Shortcodes interpretiert
			if (i4helper_attribute_as_bool($attrs, $i4include_shortcode_attr_shortcode)) {
				// Alerdings müssen Rekursionen durch i4include verhindert werden!
				array_push($i4include_recursion, $pathinfo['path']);
				$result = do_shortcode($result);
				if (($key = array_search($pathinfo['path'], $i4include_recursion)) !== false) {
					unset($i4include_recursion[$key]);
				}
			}
			// Einzubettender Inhalt
			return $result;
		}
	}
	// Fehlerbehandlung
	if (i4helper_attribute_as_bool($attrs, $i4include_shortcode_attr_showerror)) {
		// Zeige Fehler auf der Webseite, wenn `showerrors` gesetzt ist
		return '<div style="margin:2px; padding: 2px; border:2px solid red"><b>i4include Fehler:</b> Der Inhalt kann nicht angezeigt werden &ndash; '.$error.'<br><pre>'.esc_textarea(print_r($pathinfo, true)).'</pre></div>';
	} else {
		// Speichere im Log, und ignoriere Shortcode auf der Webseite
		error_log($pathinfo['link'].': '.$error);
		return '(Einbettung fehlgeschlagen)';
	}
}
add_shortcode($i4include_shortcode_name, 'i4include_handler_function' );


/* Durch die Verwendung von 'template_redirect' wird dies Funktion VOR der
   Ausgabe von WordPress ausgeführt, allerdings sind die anzuzeigende Inhalte
   schon vorhanden (d.h. die URL ausgewertet) */
function i4include_redirect_on_shortcode() {
	global $post, $i4include_queryvar, $i4include_shortcode_name, $i4include_ext_bin, $i4include_ext_bin_force_pass_through, $i4include_base_path, $i4include_base_path_web;
	// Untersuche Nur valide Seiten mit Inhalt
	if (is_singular() && !empty($post->post_content)) {
		// Prüfe, ob der i4include Shortcode verwendet wird
		preg_match_all('/'.get_shortcode_regex(array($i4include_shortcode_name)).'/',$post->post_content, $shortcode_matches, PREG_SET_ORDER);
		foreach ($shortcode_matches as $shortcode_match) {
			/* $shortcode_match[3] hat nun alle Attribute und
			   $shortcode_match[5] den Shortcode Pfad */

			// Hole Informationen über den Pfad
			$pathinfo = i4include_pathinfo($shortcode_match[5], shortcode_parse_atts($shortcode_match[3]));

			// Umschreiben nur bei dynamischen (validen) Inhalten notwendig
			if ($pathinfo['dynamic'] && $pathinfo['valid']) {
				if (!array_key_exists('query', $pathinfo) || empty($pathinfo['query'])) {
					// Sofern keine Query Variable vorhanden ist, ändere die URL auf .../extern
					//wp_redirect(get_permalink().'/'.$i4include_queryvar.'/'.basename($shortcode_match[5]));
					wp_redirect($pathinfo['link']);
					exit();
				} else if (array_key_exists($pathinfo['ext'], $i4include_ext_bin)) {
					// Sofern die Dateiendung auf eine (erlaubte) Binärdatei hinweist...
					if (!in_array($pathinfo['ext'], $i4include_ext_bin_force_pass_through) && substr($pathinfo['path'], 0, strlen($i4include_base_path)) === $i4include_base_path) {
						// ... so kann diese entweder direkt vom Webserver ausgeliefert werden
						wp_redirect(get_home_url().$i4include_base_path_web.substr($pathinfo['path'], strlen($i4include_base_path)));
					} else {
						// ... oder wir leiten sie durch WordPress an den Client
						header('Content-Type: ' . $i4include_ext_bin[$pathinfo['ext']]);
						print(file_get_contents($pathinfo['path']));
					}
					exit();
				}
			}
		}
	}
}
add_action('template_redirect','i4include_redirect_on_shortcode',1);

?>
