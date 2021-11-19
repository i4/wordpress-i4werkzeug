<?php
namespace i4helper;


/* Hilfsfunktion, welche prüft ob es ein Shortcode-Attribut gibt */
function has_attribute($attrs, $name) {
	return !empty($attrs) && array_key_exists($name, $attrs);
}


/* Hilfsfunktion, welche prüft ob es ein Shortcode-Attribut gibt
   und ggf dessen Wert zurück gibt (falls nicht, dann wird `$default` zurück gegeben */
function attribute($attrs, $name, $default = null) {
	return has_attribute($attrs, $name) ? $attrs[$name] : $default;
}


/* Hilfsfunktion, welche prüft ob es ein boolsches Shortcode-Attribut gibt
   und ggf dessen Wert zurück gibt (falls nicht, dann wird `false` zurück gegeben */
function attribute_as_bool($attrs, $name) {
	return has_attribute($attrs, $name)
	    && filter_var($attrs[$name], FILTER_VALIDATE_BOOLEAN);
}

/* stabile Sortfunktion, Quelle: Hayley Watson, https://www.php.net/manual/en/array.sorting.php
 * Nötig, da PHP erst ab PHP8 eine stabile Sortierfunktion in der Standardlib anbietet
 */
function stable_usort(&$array, $cmp)
{
    $i = 0;
    $array = array_map(function($elt)use(&$i)
    {
        return [$i++, $elt];
	}, $array);
    usort($array, function($a, $b)use($cmp)
    {
        return $cmp($a[1], $b[1]) ?: ($a[0] - $b[0]);
    });
    $array = array_column($array, 1);
}

/* Hilfsfunktion welche beliebigen Text in eine als `id`-Feld eines HTML-Tags geeignete
 * Darstellung überführt
 */
function to_anchortext($text) {
	return preg_replace('/[^a-z0-9]+/', '_', str_replace(array('ä', 'ö', 'ü'), array('ae', 'oe', 'ue'), strtolower($text)));
}

?>
