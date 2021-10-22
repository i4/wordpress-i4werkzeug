<?php
/**
* Plugin Name: i4include
* Plugin URI: https://www4.cs.fau.de
* Description: Einbetten von HTML Dateien (und optional das ganze Verzeichnis) für das neue i4 WordPress
* Version: 1.0
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

/* Der Name des Shortcode-attributs, der die Verarbeitung weiterer Shortcodes im Zieldokument erlaubt */
$i4include_shortcode_attr_shortcode = 'shortcodes';

/* Der Name des Shortcode-attributs, der die Anzeige von Fehlern auf der Webseite (zu Debugzwecken) erlaubt */
$i4include_shortcode_attr_showerror = 'showerrors';

/* Regulärer Ausdruck, welcher die validen (absoluten) Pfade für den Shortcode definiert.
   Jedes Teilmuster muss auf `/.*` enden, damit die Ordnernamen vollständig gematcht werden */
$i4include_allowed_path = '#^(http[s]?://[^/]*(?:fau|uni-erlangen)\.de/.*|/proj/i4www/.*|/var/www/data/.*)$#i';

/* Basispfad für relative Pfade (sollte natürlich vom obigen regulären Ausdruck akzeptiert werden) */
$i4include_base_path = '/var/www/data';

/* Erlaubte Dateinamenerweiterungen für eingebettete Inhalte */
$i4include_ext_html = array('htm', 'html', 'shtml', 'ushtml');

/* Dateinamenerweiterungen für Binärdateien (und die dazugehörigen MIME Typen)
   für direktes durchreichen an den Webbrowser */
$i4include_ext_bin = array(
	'gif' => 'image/gif',
	'png' => 'image/png',
	'jpg' => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'svg' => 'image/svg+xml'
);

/* Statusvariable, welche Rekursionen von includes erkennen und verhindern kann */
$i4include_recursion = array();

/* Statusvariable zum Erkennen von mehrfachen dynamic include Shortcodes auf einer Seite */
$i4include_dynamic_path = '';

/* Hilfsfunktion, welche Prüft ob es ein boolsches Shortcode-Attribut gibt
   und ggf dessen Wert zurück gibt (falls nicht, dann wird `false` zurück gegeben */
function i4include_attribute_as_bool($attributes, $name) {
	return !empty($attributes) && array_key_exists($name, $attributes)
	    && filter_var($attributes[$name], FILTER_VALIDATE_BOOLEAN);
}


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
	global $i4include_base_path, $i4include_dynamic_path, $i4include_queryvar, $i4include_shortcode_attr_dynamic, $i4include_allowed_path;
	
	// ggf. relativen Pfad anpassen 
	if (stream_is_local($shortcode_path)) {
		if (!path_is_absolute($shortcode_path)) {
			$shortcode_path = $i4include_base_path.'/'.$shortcode_path;
		}
		$shortcode_path = realpath($shortcode_path);
	}

	// Rückgabe Array initialisieren
	$r = array(
		'full' => $shortcode_path,
		'base' => dirname($shortcode_path),
		// Prüfe, ob das Attribut `dynamic` vorhanden & auf wahr gesetzt ist
		'dynamic' => i4include_attribute_as_bool($shortcode_attr, $i4include_shortcode_attr_dynamic)
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
	// flush_rewrite_rules();
}
add_filter('init','i4include_rewrite_endpoint');


/* Das Herz: diese Funktion wird für jeden Shortcode `[i4include ...]` aufgerufen,
   liest die entsprechende Datei und gibt diese aus */
function i4include_handler_function($attrs, $content, $tag) {
	global $i4include_dynamic_path, $i4include_ext_html, $i4include_ext_bin, $i4include_shortcode_attr_shortcode, $i4include_shortcode_attr_showerror, $i4include_recursion;
	$pathinfo = i4include_pathinfo($content, $attrs);
	if ($pathinfo['dynamic'] && $i4include_dynamic_path != $pathinfo['full']) {
		$error = 'es darf nicht mehrere <tt>dynamic includes</tt> mit unterschiedlichen Pfaden (<tt>'.$i4include_dynamic_path.'</tt> und <tt>'.$pathinfo['full'].'</tt>) auf dieser Seite geben!';
	} else if (empty($pathinfo['path'])) {
		$error = 'die Datei <tt>'.$pathinfo['file'].'</tt> existiert im Verzeichnis <tt>'.$pathinfo['dir'].'</tt> nicht!';
	} else if (!$pathinfo['valid']) {
		$error = 'das Einbetten des Pfads <tt>'.$pathinfo['path'].'</tt> ist nicht erlaubt!';
	} else if (!in_array($pathinfo['ext'], $i4include_ext_html)) {
		$error = 'Dateien mit der Endung <tt>'.$pathinfo['ext'].'</tt> sind nicht erlaubt!';
	} else if (in_array($pathinfo['path'], $i4include_recursion)) {
		$error = 'die Datei <tt>'.$pathinfo['path'].'</tt> soll erneut (endlos)rekursiv eingebunden werden!';
	} else if ($pathinfo['dynamic'] && count($i4include_recursion) > 0) {
		$error = 'dynamische Includes sind nur in WordPress möglich, nicht jedoch über eingebundene Seiten!';
	} else {
		$result = file_get_contents($pathinfo['path']);
		if ($result === false) {
			$error = 'die Datei <tt>.'.$pathinfo['path'].'</tt> konnte nicht gelesen werden!';
		} else {
			// Falls von  unserer www4 Webseite was eingebettet wird, entferne Kopf und Fußzeile
			// TODO: irgendwann entfernen.
			if (preg_match('#http[s]?://www4\.[^/]+(?:fau|uni-erlangen)\.de(/.*)#i', $content, $contenturl) > 0 && preg_match('/(<div id="content">.*)<!-- beginning footer\.shtml -->/sm', $result, $contentdiv)) {
				$result = str_replace(array($pathinfo['dir'], dirname($contenturl[1]).'/'), './', $contentdiv[1]);
			}
			// Sofern das Attribut `shortcodes` aktiviert ist, werden im engebundenen Dokument die Shortcodes interpretiert
			if (i4include_attribute_as_bool($attrs, $i4include_shortcode_attr_shortcode)) {
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
	if (i4include_attribute_as_bool($attrs, $i4include_shortcode_attr_showerror)) {
		// Zeige Fehler auf der Webseite, wenn `showerrors` gesetzt ist
		return '<div style="margin:2px; padding: 2px; border:2px solid red"><b>i4include Fehler:</b> Der Inhalt kann nicht angezeigt werden &ndash; '.$error.'<br><pre>'.print_r($pathinfo, true).'</pre></div>';
	} else {
		// Speichere im Log, und ignoriere Shortcode auf der Webseite
		error_log($pathinfo['link'].': '.$error);
		return '';
	}
}
add_shortcode($i4include_shortcode_name, 'i4include_handler_function' );


/* Durch die Verwendung von 'template_redirect' wird dies Funktion VOR der
   Ausgabe von WordPress ausgeführt, allerdings sind die anzuzeigende Inhalte
   schon vorhanden (d.h. die URL ausgewertet) */
function i4include_redirect_on_shortcode() {
	global $post, $i4include_queryvar, $i4include_shortcode_name, $i4include_ext_bin;
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
					// Sofern die Dateiendung auf eine Binärdatei hinweist, gib direkt den Inhalt aus
					header('Content-Type: ' . $i4include_ext_bin[$pathinfo['ext']]);
					print(file_get_contents($pathinfo['path']));
					exit();
				}
			}
		}
	}
}
add_action('template_redirect','i4include_redirect_on_shortcode',1);

?>
