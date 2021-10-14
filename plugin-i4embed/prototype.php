<?php
/**
* Plugin Name: i4include
* Plugin URI: https://www4.cs.fau.de
* Description: Embed HTML files (and optional their directories) in WordPress
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

/* Der Name des Shortcode-attributs, der dynamische Inhalte aktivieren kann*/
$i4include_shortcode_attr_dynamic = 'dynamic';

/* Regulärer Ausdruck, welcher die validen Pfade für den Shortcode definiert */
$i4include_allowed_path = '#^(http[s]?://[^/]*(?:fau|uni-erlangen)\.de/.*|/proj/i4www/.*|/var/www/data/.*)$#i';

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


/* Hilfsfunktion zur Bestimmung der relevanten Pfadteile.
     $shortcode_path ist der im Shortcode übergebene Pfad
     $shortcode_attr ist ein Array mit den in Shortcode angegebenen Attributen

   Diese Funktion liefert ein assoziatives Array mit folgenden Inhalten zurück:
     ['base']    ist der volle Pfad des Ordners, wie er im Shortcode eingegeben wird
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
	global $i4include_queryvar, $i4include_shortcode_attr_dynamic, $i4include_allowed_path;
	
	// ggf. relativen Pfad anpassen 
	if (stream_is_local($shortcode_path)) {
		$shortcode_path = realpath($shortcode_path);
	}

	// Rückgabe Array initialisieren
	$r = array(
		'base' => dirname($shortcode_path),
		// Prüfe, ob das Attribut `dynamic` vorhanden & auf wahr gesetzt ist
		'dynamic' => array_key_exists($i4include_shortcode_attr_dynamic, $shortcode_attr)
		          && filter_var($shortcode_attr[$i4include_shortcode_attr_dynamic], FILTER_VALIDATE_BOOLEAN)
	);

	if ($r['dynamic']) {
		// Dynamisch (Pfad anhand Shortcode sowie  `extern`, sofern vorhanden)

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
function i4include_handler_function($atts, $content, $tag) {
	global $i4include_ext_html, $i4include_ext_bin;
	$pathinfo = i4include_pathinfo($content, $atts);
	if (!$pathinfo['valid']) {
		$error = 'das Einbetten des Pfads <tt>'.$pathinfo['path'].'</tt> ist nicht erlaubt!';
	} else if (!in_array($pathinfo['ext'], $i4include_ext_html)) {
		$error = 'Dateien mit der Endung <tt>'.$pathinfo['ext'].'</tt> sind nicht erlaubt!';
	} else {
		$embed = file_get_contents($pathinfo['path']);
		if ($embed === false) {
			$error = 'die Datei <tt>.'.$pathinfo['path'].'</tt> konnte nicht gelesen werden!';
		} else {
			// Falls von  unserer www4 Webseite was eingebettet wird, entferne Kopf und Fußzeile
			if (preg_match('#http[s]?://www4\.[^/]+(?:fau|uni-erlangen)\.de(/.*)#i', $content, $contenturl) > 0 && preg_match('/(<div id="content">.*)<!-- beginning footer\.shtml -->/sm', $embed, $contentdiv)) {
				$embed = str_replace(array($pathinfo['dir'], dirname($contenturl[1]).'/'), './', $contentdiv[1]);
			}
			return $embed;
		}
	}
	error_log($pathinfo['link'].': '.$error);
	return '<div style="margin:2px; padding: 2px; border:2px solid red"><b>i4include Fehler:</b> Der Inhalt kann nicht angezeigt werden &ndash; '.$error.'</div>';
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
		if (preg_match('/'.get_shortcode_regex(array($i4include_shortcode_name)).'/',$post->post_content, $shortcode_match) > 0) {
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
					header('Content-type: ' . $i4include_ext_bin[$pathinfo['ext']]);
					print(file_get_contents($pathinfo['path']));
					exit();
				}
			}
		}
	}
}
add_action('template_redirect','i4include_redirect_on_shortcode',1);

?>
