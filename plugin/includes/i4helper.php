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

?>
