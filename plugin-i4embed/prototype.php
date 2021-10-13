<?php
/**
* Plugin Name: i4Embed
* Plugin URI: https://www4.cs.fau.de
* Description: Embed HTML directories (with images) in WordPress
* Version: 1.0
* Author: Bernhard Heinloth
* Author URI: https://www4.cs.fau.de/~heinloth
**/

/* Please note: This plugin requires an permalink setting WITHOUT trailing slash,
   e.g.
        /%category%/%postname%
   set in the WP admin interface (.../wp-admin/options-permalink.php)
   
   Moreover, the target file must be specified with the filename
   e.g.
        [i4extern]/var/www/data/aufgabe0/a0.shtml[/i4extern]
   and
        [i4extern]https://www4.cs.fau.de/Lehre/WS21/V_BS/index.ushtml[/i4extern]
   (mind the index.ushtml, which is not obvious for webpages)
*/

// Query variable (URL will be extended by this name)
$i4extern_query_var = 'extern';
// Shortcode name
$i4extern_shortcode = 'i4extern';

// Allowed extensions to embed in WordPress
$i4extern_embed = array('htm', 'html', 'shtml', 'ushtml');

// Extensions (and the corresponding MIME types) to directly forward
$i4extern_forward = array(
	'gif' => 'image/gif',
	'png' => 'image/png',
	'jpg' => 'image/jpeg',
	'jpeg' => 'image/jpeg',
	'svg' => 'image/svg+xml'
);

function i4extern_pathinfo($base) {
	global $i4extern_query_var;

	$r = array(
		'base' => dirname($base),
		'link' => get_permalink().'/'.$i4extern_query_var.'/'
	);

	$notset='/notset/';  // neither false nor null is allowed
	$query_var = get_query_var($i4extern_query_var, $notset);
	if ($query_var == $notset) {
		$r['file'] = basename($base);
		$r['dir'] = $r['base'];
		$r['link'] .= $r['file'];
	} else {
		$r['query'] = $query_var;
		$r['link'] .= $query_var;
		$r['file'] = basename($query_var);
		$dir = dirname($query_var);
		$r['dir'] = $r['base'].( $dir != '.' ? '/'.$dir : '');
	}
	$r['ext'] = strtolower(substr($r['file'], strrpos($r['file'], '.') + 1));
	$r['path'] = $r['dir'].'/'.$r['file'];

	return $r;
}


function i4extern_query_vars($vars) {
	global $i4extern_query_var;
	$vars[] = $i4extern_query_var;
	return $vars;
}
add_filter('query_vars', 'i4extern_query_vars');


function i4extern_rewrite_endpoint() {
	global $i4extern_query_var;
	add_rewrite_endpoint($i4extern_query_var, EP_PERMALINK | EP_PAGES );
	//flush_rewrite_rules();
}
add_filter('init','i4extern_rewrite_endpoint');


function i4extern_handler_function($atts, $content, $tag) {
	global $i4extern_embed;
	$pathinfo = i4extern_pathinfo($content);
	if (in_array($pathinfo['ext'], $i4extern_embed)) {
		// TODO: Security!
		$embed = file_get_contents($pathinfo['path']);
		// From the web page? Then do some reformat
		if (preg_match('#http[s]?://www4\.cs\.fau\.de(/.*)#i', $content, $contenturl) > 0 && preg_match('/(<div id="content">.*)<!-- beginning footer\.shtml -->/sm', $embed, $contentdiv)) {
			print_r($contenturl);
			$embed = str_replace(array($pathinfo['dir'], dirname($contenturl[1]).'/'), './', $contentdiv[1]);
		}
		return $embed;
	}
}
add_shortcode($i4extern_shortcode, 'i4extern_handler_function' );


function i4extern_redirect_on_shortcode() {
	global $post, $i4extern_query_var, $i4extern_shortcode, $i4extern_forward;
	if (is_singular() && !empty($post->post_content)) {
		if (preg_match('/'.get_shortcode_regex(array($i4extern_shortcode)).'/',$post->post_content, $matchurl) > 0) {
			$pathinfo = i4extern_pathinfo($matchurl[5]);
			if (!array_key_exists('query', $pathinfo) || empty($pathinfo['query'])) {
				wp_redirect(get_permalink().'/'.$i4extern_query_var.'/'.basename($matchurl[5]));
				exit();
			} else if (array_key_exists($pathinfo['ext'], $i4extern_forward)) {
				header('Content-type: ' . $i4extern_forward[$ext]);
				print(file_get_contents($pathinfo['path']));
				exit();
			}
		}
	}
}
add_action('template_redirect','i4extern_redirect_on_shortcode',1);

?>
