<?php
namespace i4include;

/* Der Name des Shortcodes, wie er im Editor in WordPress verwendet werden muss: [i4include */
const SHORTCODE_NAME= 'i4include';

/* Der Name des Shortcode-attributs, der dynamische Inhalte aktivieren kann */
const SHORTCODE_ATTR_DYNAMIC = 'dynamic';

/* Der Name des Shortcode-attributs, der den Pfad zu einem Kurs ermittelt */
const SHORTCODE_ATTR_COURSE = 'course';

/* Der Name des Shortcode-attributs, der das Semester bei einer Lehrveranstaltung angibt ermittelt (benötigt course!) */
const SHORTCODE_ATTR_SEMESTER = 'semester';

/* Der Name des Shortcode-attributs, der die Verarbeitung weiterer Shortcodes im Zieldokument erlaubt */
const SHORTCODE_ATTR_SHORTCODE = 'shortcodes';

/* Der Name des Shortcode-attributs, der die Anzeige von Fehlern auf der Webseite (zu Debugzwecken) erlaubt */
const SHORTCODE_ATTR_SHOWERROR = 'showerrors';


/* Regulärer Ausdruck, welcher die validen (absoluten) Pfade als Angaben im Shortcode definiert.
   Jedes Teilmuster muss auf `/.*` enden, damit die Ordnernamen vollständig gematcht werden */
const ALLOWED_PATH_REGEX = '#^(http[s]?://(?:[^/]+\.fau\.de|[^/]+\.uni-erlangen\.de|localhost[:0-9]*)/.*|/proj/i4www/home/.*|/proj.stand/i4wp/extern/.*)$#i';

/* Basispfad für relative Pfade (sollte natürlich auch vom obigen regulären Ausdruck `ALLOWED_CONTENT` akzeptiert werden) */
const FILESYSTEM_BASE = '/proj.stand/i4wp/extern';

/* URL Prefix um auf die Inhalte von `FILESYSTEM_BASE` zuzugreifen.
   So wird
       FILESYSTEM_BASE/foo/bar
   via
       https://server/URL_BASE/foo/bar
   ausgeliefert */
const URL_BASE = '/extern';

/* Die sog. Query Variable, d.h. der Name der Variable,
   welche in WordPress für dynamische Inhalte (bei `dir="true"`) für die aktuelle
   angeforderte Datei verwendet wird (die URL wird um diesen Variablennamen erweitert),
   z.B. um auf der WordPress Seite foo die Datei baz.html aus dem Ordner bar dynamisch einzubinden, wird
       https://server/URL_BASE/foo
   automagisch zu
       https://server/URL_BASE/foo/extern/baz.html
   umgeleitet - und andere Dateien aus dem Ordner bar sind so ebenfalls abrufbar
    */
const QUERY_VAR = 'extern';


/* Erlaubte Dateinamenerweiterungen für in die WordPress Seite textuell eingebettete Inhalte */
const EXT_EMBED = array('htm', 'html', 'shtml', 'ushtml', 'xhtml',);

/* Dateinamenerweiterungen für Binärdateien (und die dazugehörigen MIME Typen)
   welche erlaubt (valid) sind, und entweder direkt durch WordPress durchgereicht werden,
   oder, sofern Möglich, die URL umgeschrieben und via URL_BASE direkt vom Webserver ausgeliefert werden */
const EXT_BINARY = array(
	// Webmetazeugs
	'css'   => 'text/css',
	'js'    => 'text/javascript',
	'json'  => 'application/json',
	'xml'   => 'text/xml',
	'ico'   => 'image/vnd.microsoft.icon',
	'ics'   => 'text/calendar',
	'otf'   => 'font/otf',
	'woff'  => 'font/woff',
	'woff2' => 'font/woff2',
	'ttf'   => 'font/ttf',

	// PGP
	'pgp'   => 'application/pgp-encrypted',
	'asc'   => 'application/pgp-signature',
	'sig'   => 'application/pgp-signature',

	// Bildformate
	'bmp'   => 'image/bmp',
	'gif'   => 'image/gif',
	'png'   => 'image/png',
	'jpg'   => 'image/jpeg',
	'jpeg'  => 'image/jpeg',
	'svg'   => 'image/svg+xml',
	'tif'   => 'image/tiff',
	'tiff'  => 'image/tiff',
	'webp'  => 'image/webp',

	// Videoformate
	'ogv'   => 'video/ogg',
	'mp4'   => 'video/mp4',
	'mpeg'  => 'video/mpeg',
	'webm'  => 'video/webm',

	// Übungsrelevant
	'h'    => 'text/x-c',
	'c'    => 'text/x-c',
	'cc'   => 'text/x-c++',
	'cpp'  => 'text/x-c++',
	'jar'  => 'application/java-archive',
	'sh'   => 'application/x-sh',

	// Dokumente
	'pdf'  => 'application/pdf',
	'tex'  => 'application/x-tex',

	// Gepackte Dateien
	'tar'  => 'application/x-tar',
	'gz'   => 'application/gzip',
	'zip'  => 'application/zip',
	'bz'   => 'application/x-bzip',
	'bz2'  => 'application/x-bzip2',
	'rar'  => 'application/vnd.rar',
	'7z'   => 'application/x-7z-compressed',

	// Generisch
	'txt'  => 'text/plain',
	'csv'  => 'text/csv',
	'iso'  => 'application/octet-stream',
	'bin'  => 'application/octet-stream',
);

/* Subset von obige Dateinamenerweiterungen,
   welche zwingend via WordPress durchgereicht werden müssen
   (z.B. weil sie relative Links enthalten können) */
const EXT_BINARY_FORCEPASSTHROUGH = array('svg');


/* Für ein passthrough einer HTTP(S) ressource werden folgende Header einfach "durchgereicht */
const ALLOWED_PASSTHROUGH_HEADER = array('Last-Modified', 'Content-Language', 'Content-Description', 'Content-Type', 'Content-Disposition', 'Expires', 'Cache-Control', 'Pragma', 'Content-Length');

/* Definiert wie lange (in Sekunden) ausgelieferte Binärdateien und auch Umleitungen vom
   Webbrowser gespeichert werden dürfen. */
const CACHE_SECONDS = 86400;  // 1 Tag


/* Statusvariable, welche Rekursionen von includes erkennen und verhindern kann */
$include_recursion = array();

/* Statusvariable zum Erkennen von mehrfachen dynamic include Shortcodes auf einer Seite */
$dynamic_path = '';


/* Hilfsklasse zur Bestimmung der relevanten Pfadteile */
class Pathinfo {
	/* Der absolute Pfad der Datei die im Shortcode eingegeben wird */
	public string $full;

	/* Das Verzeichnis zum im Shortcode angegebenen Pfad
	   (also unabhängig vom dynamischen Endpoint) */
	public string $base = '';

	/* Die volle URL (inklusive Endpoint) zu der aktuell angezeigten Wordpress Seite */
	public string $link = '';

	/* Die aktuell zu inkludierende Datei
	   (welche auch anhand des übergebenen Endpoint bestimmt wird) */
	public string $file = '';

	/* Der volle Pfad des aktuellen Verzeichnisses der zu
	   inkludierenden Datei (unter Berücksichtigung des Endpoints) */
	public string $dir = '';

	/* Die Dateinamenerweiterung der aktuell angeforderten Datei (ohne `.`) */
	public string $ext = '';

	/* Der resultierende volle Pfad der aktuell zu inkludierenden Datei
	  (unter Berücksichtigung des Endpoints) */
	public string $path;

	/* Wahr, wenn das Ziel lokal (d.h. aus dem Dateisystem) ist */
	public bool $local;

	/* nur wahr, wenn der resultierende Pfad gültig ist */
	public bool $valid = false;

	/* wird auf wahr gesetzt, wenn dynamische Inhalte unterstützt werden */
	public bool $dynamic = false;

	/* Der Wert der Variable `extern` (dieses Element ist nur vorhanden,
	   sofern `dynamic` auf wahr gesetzt und `extern` vorhanden ist) */
	public $query = null;

	/* Konstruktor
	     $path ist der im Shortcode übergebene Pfad
	     $attr ist ein Array mit den in Shortcode angegebenen Attributen */
	public function __construct($path, $attr) {
		global $dynamic_path;

		// Der angefragte Pfad
		$this->path = $path;

		// Prüfe ob lokal
		$this->local = stream_is_local($path);

		// Bei lokalen Dateien brauchen wir noch den absoluten Pfad
		if ($this->local) {
			// ggf. relativen Pfad anpassen
			if (!path_is_absolute($path)) {
				if (\i4helper\has_attribute($attr, SHORTCODE_ATTR_COURSE))
					$this->path = FILESYSTEM_BASE . \i4link\get($path, \i4helper\attribute($attr, SHORTCODE_ATTR_SEMESTER), $attr[SHORTCODE_ATTR_COURSE], false, false);
				else
					$this->path = FILESYSTEM_BASE . '/' . $path;
			}
			$path = realpath($this->path);
		}

		if (empty($path)) {
			$this->valid = false;
		} else {
			$this->full = $path;
			$this->base = dirname($path);
			// Prüfe, ob das Attribut `dynamic` vorhanden & auf wahr gesetzt ist
			$this->dynamic = \i4helper\attribute_as_bool($attr, SHORTCODE_ATTR_DYNAMIC);

			if ($this->dynamic) {
				// Dynamisch (Pfad anhand Shortcode sowie `extern`, sofern vorhanden)
				if (empty($dynamic_path)){
					$dynamic_path = $path;
				}

				/* Da `get_query_var()` bei nicht vorhandener Variable eine leere
				   Zeichenkette liefert, was aber auch ein valider Wert sein kann,
				   ein kleiner Hack: Der Inhalt von `$notset` ist ein invalider Wert
				   (den `extern` nicht annehmen kann), welcher nun bei `get_query_var`
				   als default (d.h. wenn die Variable nicht vorhanden ist)
				   zurückgegeben wird */
				$notset = '/notset/';
				$query_var = get_query_var(QUERY_VAR, $notset);
				if ($query_var == $notset) {
					// Variable `extern` nicht gesetzt, d.h. wir berücksichtigen nur den Shortcodepfad
					$this->file = basename($path);
					$this->dir = $this->base;
					$this->link = get_permalink() . '/' . QUERY_VAR . '/' . $this->file;
					$this->path = $path;
				} else {
					// Variable `extern` gesetzt, d.h. wir kombinieren diese mit den Shortcodepfad
					$this->query = $query_var;
					$this->link =  get_permalink() . '/' . QUERY_VAR . '/' . $query_var;
					$this->file = basename($query_var);

					$dir = dirname($query_var);
					$this->dir = $this->base . ($dir != '.' ? '/' . $dir : '');
					$this->path = $this->dir . '/' . $this->file;
					if ($this->local)
						$this->path = realpath($this->path);
				}
			} else {
				// Statisch (Verwende nur Shortcodepfad)
				$this->dynamic = FALSE;
				$this->file = basename($path);
				$this->dir = $this->base;
				$this->link = get_permalink();
				$this->path = $path;
			}

			// Dateiendung
			$this->ext = strtolower(substr($this->file, strrpos($this->file, '.') + 1));

			// Prüfe ob Pfad valid (er muss mit 'base' beginnen und dem Regex entsprechen)
			$this->valid = substr($this->path, 0, strlen($this->base)) === $this->base && preg_match(ALLOWED_PATH_REGEX, $this->path) > 0;
		}
	}
}


/* Hilfsfunktion, um die Cache-Dauer zu setzen */
function send_cache_headers() {
	if (CACHE_SECONDS > 0) {
		// Erlaube das cachen für die gegebene Zeit
		header('Cache-Control: max-age='.CACHE_SECONDS);
		header('Vary: Accept-Encoding' );
		header('Expires: '.gmdate('D, d M Y H:i:s', time() + CACHE_SECONDS).' GMT');
	} else {
		// Kein cachen im Browser!
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		nocache_headers();
	}
}

/* Hilfsfunktion, um die 404er Fehlerseite anzuzeigen */
function error_page(int $status_code = 404) {
	status_header($status_code);
	nocache_headers();
	// Es gibt nur ein 404er template, egal, tut's auch.
	include(get_404_template());
	die();
}

/* Hilfsfunktion, um eine Umleitung durchzuführen*/
function redirect(string $location) {
	send_cache_headers();
	wp_redirect($location, CACHE_SECONDS > 0 ? 301 : 302, 'i4WordPress');
	exit();
}


/* Setze QUERY_VAR (`extern`) als zusätzliche Wordpress Variable */
function query_vars(array $vars) {
	$vars[] = QUERY_VAR;
	return $vars;
}


/* Registriere  QUERY_VAR (`extern`) als WordPress Endpoint
   (das beeinflusst die Rewrite Regeln, welche ggf erneuert werden müssen */
function rewrite_endpoint() {
	add_rewrite_endpoint(QUERY_VAR, EP_PERMALINK | EP_PAGES);

	/* Nachfolgende Zeile ist für die Entwicklung hilfreich:
	   Sie erneuert die rewrite rules, was notwendig ist,
	   wenn z.B. QUERY_VAR geändert wurde */
	//flush_rewrite_rules();
}


/* Das Herz: diese Funktion wird für jeden Shortcode `[i4include ...]` aufgerufen,
   liest die entsprechende Datei und gibt diese aus */
function shortcode_handler_function($attr, $content = '') {
	global $dynamic_path, $include_recursion;

	if (empty($content)) {
		// Fehler: Keine Zieldatei angegeben
		$error = 'es wurde keine Zieldatei angegeben!';
	} else {
		// Hole Pfadinformationen
		$pathinfo = new Pathinfo($content, $attr);
		if ($pathinfo->dynamic && $dynamic_path != $pathinfo->full) {
			// Fehler: mehrfache [i4include dynamic=true...] auf einer Seite...
			$error = 'es darf nicht mehrere <tt>dynamic includes</tt> mit unterschiedlichen Pfaden (<tt>' . esc_html($dynamic_path) . '</tt> und <tt>' . esc_html($pathinfo->full) . '</tt>) auf dieser Seite geben!';
		} else if (empty($pathinfo->path)) {
			// Fehler: Zieldatei existiert nicht
			$error = 'die angeforderte Datei <tt>' . esc_html($pathinfo->path) . '</tt> existiert nicht!';
		} else if (!$pathinfo->valid) {
			// Fehler: Nicht erlaubt
			$error = 'das Einbetten des Pfads <tt>' . esc_html($pathinfo->path) . '</tt> ist nicht erlaubt!';
		} else if (!in_array($pathinfo->ext, EXT_EMBED)) {
			// Fehler: Dateityp zur Einbettung nicht erlaubt
			$error = 'Dateien mit der Endung <tt>' . esc_html($pathinfo->ext) . '</tt> sind nicht erlaubt!';
		} else if (in_array($pathinfo->path, $include_recursion)) {
			// Fehler: Endlosreskursive Einbettung erkannt
			$error = 'die Datei <tt>' . esc_html($pathinfo->path) . '</tt> soll erneut (endlos)rekursiv eingebunden werden!';
		} else if ($pathinfo->dynamic && count($include_recursion) > 0) {
			// Fehler: Rekursive [i4include dynamic=true...]
			$error = 'dynamische includes sind nur in WordPress möglich, nicht jedoch über (dynamisch) eingebundene Seiten!';
		} else {
			// Kein Fehler bisher - lade Inhalt der Datei
			$result = file_get_contents($pathinfo->path);
			if ($result === false) {
				// Fehler: Datei nicht lesbar
				$error = 'die Datei <tt>.'.esc_html($pathinfo->path).'</tt> konnte nicht gelesen werden!';
			} else {
				// Sofern das Attribut `shortcodes` aktiviert ist, werden im engebundenen Dokument die Shortcodes interpretiert
				if (\i4helper\attribute_as_bool($attr, SHORTCODE_ATTR_SHORTCODE)) {
					// Alerdings müssen Rekursionen durch i4include verhindert werden!
					array_push($include_recursion, $pathinfo->path);
					$result = do_shortcode($result);
					if (($key = array_search($pathinfo->path, $include_recursion)) !== false) {
						unset($include_recursion[$key]);
					}
				}
				// Fertig - einzubettender Inhalt wird an WordPress zurück gegeben
				return $result;
			}
		}
	}

	// Fehlerbehandlung
	if (\i4helper\attribute_as_bool($attr, SHORTCODE_ATTR_SHOWERROR)) {
		// Zeige Fehler auf der Webseite, wenn `showerrors` gesetzt ist
		$retmsg = '<strong>i4include Fehler:</strong> Der Inhalt kann nicht angezeigt werden &ndash; ' . $error;
	} else {
		// Speichere im Log, und ignoriere Shortcode auf der Webseite
		error_log($pathinfo->link . ': ' . $error);
		$retmsg = 'Einbettung fehlgeschlagen';
	}
	// Nutze RRZE Elements für Ausgabe
	return do_shortcode('[alert style="danger"]' . $retmsg . '[/alert]');
}


/* Durch die Verwendung von 'template_redirect' wird dies Funktion VOR der
   Ausgabe von WordPress ausgeführt, allerdings sind die anzuzeigende Inhalte
   schon vorhanden (d.h. die URL ausgewertet) */
function redirect_on_shortcode() {
	global $post;
	// Untersuche Nur valide Seiten mit Inhalt
	if (is_singular() && !empty($post->post_content)) {
		// Prüfe, ob der i4include Shortcode verwendet wird
		preg_match_all('/'.get_shortcode_regex(array(SHORTCODE_NAME)).'/',$post->post_content, $shortcode_matches, PREG_SET_ORDER);
		foreach ($shortcode_matches as $shortcode_match) {
			/* $shortcode_match[3] hat nun alle Attribute und
			   $shortcode_match[5] den Shortcode Pfad */

			// Sofern kein Dynamic tag vorhanden --> wir brauchen nicht weiter schauen, prüfe nächsten match
			if (stripos($shortcode_match[3], SHORTCODE_ATTR_DYNAMIC) === false)
				continue;

			// Hole Informationen über den Pfad
			$pathinfo = new Pathinfo($shortcode_match[5], shortcode_parse_atts($shortcode_match[3]));

			// Umschreiben nur bei dynamischen (validen) Inhalten notwendig
			if (!$pathinfo->dynamic)
				continue;

			if (!$pathinfo->valid) {
				// Wenn der Pfad invalid ist, die Datei also nicht erlaubt ist, werfen wir einen Fehler
				// Sende HTTP Status 403 Forbidden
				error_page(403);
			} else if (empty($pathinfo->query)) {
				// Sofern keine Query Variable vorhanden ist, ändere die URL auf .../extern
				redirect($pathinfo->link);
			} else if (array_key_exists($pathinfo->ext, EXT_BINARY)) {
				// Sofern die Dateiendung auf eine (erlaubte) Binärdatei hinweist...
				$filesystem_base_len = strlen(FILESYSTEM_BASE);
				if (!in_array($pathinfo->ext, EXT_BINARY_FORCEPASSTHROUGH) && substr($pathinfo->path, 0, $filesystem_base_len) === FILESYSTEM_BASE) {
					// ... so kann diese unter Umständen entweder direkt vom Webserver ausgeliefert werden
					redirect(get_home_url() . URL_BASE . substr($pathinfo->path, $filesystem_base_len));
				} else {
					// ... oder wir leiten sie durch WordPress an den Client
					$fp = fopen($pathinfo->path, 'r');
					if ($fp === false) {
						// Datei nicht gefunden
						// Sende HTTP Status 404 File Not Found
						error_page(404);
					} else if (!$pathinfo->local && !empty($http_response_header)) {  // $http_response_header ist eine magisch erscheinende Variable durch manche PHP File Wrappers
						// Datei ist von einem anderen Webserver -- wir reichen die relevanten header durch.
						foreach ($http_response_header as $header)
							foreach (ALLOWED_PASSTHROUGH_HEADER as $allowed)
								if (stripos($header, $allowed.':') === 0)
									header($header);
					} else {
						// Datei ist vom Dateisystem, wir senden eigene Header
						header('Content-Type: ' . EXT_BINARY[$pathinfo->ext]);
						send_cache_headers();
					}
					// Ggf Ausgabepuffer löschen
					if (ob_get_level() != 0)
						ob_end_clean();
					// Inhalt durchreichen
					fpassthru($fp);
					// Schliessen
					fclose($fp);
				}
				exit();
			}
		}
	}
}
?>
