<?php
/*
 * dl-intern.php
 *
 * Limit access to files in a special folder to authenticated users or from a certain network
 *
 * Based on:
 *
 * @link http://wordpress.stackexchange.com/questions/37144/protect-wordpress-uploads-if-user-is-not-logged-in
 * @link https://gist.github.com/hakre/1552239?permalink_comment_id=3751687#gistcomment-3751687
 *
 * @author hakre <http://hakre.wordpress.com/>
 * @license GPL-3.0+
 * @registry SPDX
 */

// Configuration
define("CACHE_SECONDS", 1800);
define("INTERN_BASE_DIR", '/proj.stand/i4wp/intern/');
define("INTERN_BASE_NAME", '/proj/i4wp/intern/');
define("NONAUTH_IP_RANGE", array('10.188.34.0/24', 'fd00:638:a000:414e::/64', '131.188.34.0/24', '2001:638:a000:4134::/64'));


// Disable output buffering
if (ob_get_level()) {
	ob_end_clean();
}
ob_start();

// Include WP Files
require_once('wp-load.php');
require_once ABSPATH.WPINC.'/formatting.php';
require_once ABSPATH.WPINC.'/capabilities.php';
require_once ABSPATH.WPINC.'/user.php';
require_once ABSPATH.WPINC.'/meta.php';
require_once ABSPATH.WPINC.'/post.php';
require_once ABSPATH.WPINC.'/pluggable.php';
wp_cookie_constants();
ob_get_clean();
ob_end_flush();

/**
 * Check if an IP address matches an entry in an access control list (ACL)
 * Returns true if match, false otherwise (including if $ip is not a valid IP
 * address). Works with both IPv4 and IPv6 addresses.
 *
 * Source; https://stackoverflow.com/a/49373789
 *
 * Example: check_acl("10.6.1.16", array("10.6.0.0/16","2a01:fe8:95::/48"));
 * @param string $ip   IP address to check
 * @param array  $acl  Array of CIDR-notation IP addresses
 * @return boolean
 */
function check_acl($ip, $acl) {
	$ipb = inet_pton($ip);
	$iplen = strlen($ipb);
	if (strlen($ipb) < 4) {
		// Invalid IP address
		return false;
	}
	foreach ($acl as $cidr) {
		$ar = explode('/',$cidr);
		$ip1 = $ar[0];
		$ip1b = inet_pton($ip1);
		$ip1len = strlen($ip1b);
		if ($ip1len != $iplen) {
			// Different type
			continue;
		}
		if (count($ar)>1) {
			$bits=(int)($ar[1]);
		} else {
			$bits = $iplen * 8;
		}
		for ($c=0; $bits>0; $c++) {
			$bytemask = ($bits < 8) ? 0xff ^ ((1 << (8-$bits))-1) : 0xff;
			if (((ord($ipb[$c]) ^ ord($ip1b[$c])) & $bytemask) != 0)
				continue 2;
			$bits-=8;
		}
		return true;
	}
	return false;
}

// Check if logged in or valid ip range
if (!is_user_logged_in() && !check_acl($_SERVER['REMOTE_ADDR'], NONAUTH_IP_RANGE)) {
	auth_redirect();
	die();
}

// Check file
if (empty(INTERN_BASE_DIR) || !array_key_exists('file', $_GET) || empty($_GET[ 'file' ])) {
	status_header(404);
	wp_die('<h2>Oh-oh...</h2>Entweder stimmt die Konfiguration nicht oder du hast keine Datei angegeben!', "Datei nicht gefunden", array('response' => 404));
}
$file = realpath(INTERN_BASE_DIR . $_GET['file']);
if (empty($file) || substr($file, 0, strlen(INTERN_BASE_DIR)) != INTERN_BASE_DIR || !is_file($file)) {
	status_header(404);
	wp_die('<h2>Upsi...</h2>Konnte den Dateipfad <tt>'.INTERN_BASE_NAME.$_GET['file'].'</tt> leider nicht finden!', "Datei nicht gefunden", array('response' => 404));
}

// File information header
$mime = wp_check_filetype($file);
if (false === $mime['type'] && function_exists('mime_content_type'))
	$mime['type'] = mime_content_type($file);
header('Content-Type: '.$mime['type'] ?: 'application/octet-stream');
header('Content-Length: '.filesize($file));
$last_modified = gmdate('D, d M Y H:i:s', filemtime($file));
$etag = '"'.md5($last_modified.$file).'"';
header("Last-Modified: $last_modified GMT");
header('ETag: '.$etag);

// Cache settings
header('Cache-Control: max-age='.CACHE_SECONDS);
header('Vary: Accept-Encoding' );
header('Expires: '.gmdate('D, d M Y H:i:s', time() + CACHE_SECONDS).' GMT');

// Support for Conditional GET
$not_modified = null;
if (array_key_exists('HTTP_IF_NONE_MATCH', $_SERVER))
	$not_modified = stripslashes($_SERVER['HTTP_IF_NONE_MATCH']) == $etag;
if (array_key_exists('HTTP_IF_MODIFIED_SINCE', $_SERVER))
	$not_modified = strtotime(trim($_SERVER['HTTP_IF_MODIFIED_SINCE'])) >= strtotime($last_modified) && (is_null($not_modified) || $not_modified);
if (!is_null($not_modified) && $not_modified) {
	status_header(304);
	die();
}

// Serve the file
status_header(200);
readfile($file);
?>
