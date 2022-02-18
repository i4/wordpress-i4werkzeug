<?php
namespace i4subnav;

/* Der Name des Shortcodes, wie er im Editor in WordPress verwendet werden muss, um einen rohen Link einzufügen: i4subnaventry */
const SHORTCODE_NAME= 'i4nav';

/* Shortcode-attribut für das Label (= Name im subnav menü) der Liste */
const SHORTCODE_ATTR_LABEL = 'name';

/* Shortcode-attribut für die Ankerid eines i4nav (optional, mutually exclusive zu href) */
const SHORTCODE_ATTR_ID = 'anchor';

/* Der Name des Shortcode-attributs, welches für subnaventries das Linkziel angibt (optional, mutually exclusive zu anchor) */
const SHORTCODE_ATTR_LINKHREF = 'href';

/* Der Name des Shortcode-attributs, welches die menu_order des nav-eintrags definiert.
 * Standardmäßig werden einträge nach anderen Einträgen derselben Ebene eingefügt */
const SHORTCODE_ATTR_ORDER = 'order';
const SHORTCODE_ATTR_ORDER_DEFAULT = 0;

$i4_subnav_recursionbreaker = 0;
function adapt_subnav($output, $parsed_args, $pages) {
	global $post;

	// We call wp_list_pages recusively. We only activate the hook once
	global $i4_subnav_recursionbreaker;
	if ($i4_subnav_recursionbreaker > 0) {
		return $output;
	}

	// Only engage when we are a descendant of "Lehre"
	$parents = get_post_ancestors(null);
	if (!($parents && count($parents) >= 2 && get_post($parents[count($parents) - 1])->post_name == \i4semester\TEACHING_PAGE)) {
		return $output;
	}
	$semesterbasepage = $parents[count($parents) - 2];
	if (count($parents) == 2) {
		$lecturebasepage  = $post->ID;
	} else {
		$lecturebasepage  = $parents[count($parents) - 3];
	}

	// Hide subnav-header, but display sub-sublists. Please note that this is theme-specific
	$output = '<style>#subnavtitle { display: none; } #subnav ul { display: inline !important; }</style>';

	// Manually collect all pages that we want to exclude
	$i4_subnav_recursionbreaker++;
	// Hacky: we want the lecture to be included, so:
	//        Add the whole semester and remove all other lectures
	$parsed_args['child_of'] = $semesterbasepage;
	$excludelist = array_filter($pages,
		fn($p) => get_post_parent($p)->ID == $semesterbasepage && $p->ID != $lecturebasepage
	);
	$excludelist = array_map(fn($p) => $p->ID, $excludelist);

	// Include our excludes, along with excludes that were potentially passed from the outside
	if(!isset($parsed_args['exclude'])) {
		$parsed_args['exclude'] = "";
	}
	$exclude_param = wp_parse_id_list($parsed_args['exclude']);
	$parsed_args['exclude'] = implode(',', array_merge($exclude_param, $excludelist));

	// Now rerun the listing, using a custom walker to insert synthetic pages
	$parsed_args['walker'] = new Walker($pages);
	$output .= wp_list_pages($parsed_args);
	$i4_subnav_recursionbreaker--;

	return $output;
}

/* Behandlungsfunktion, welche von WordPress für jeden i4nav Shortcode aufgerufen wird */
function handler_function($attrs, $content, $tag) {
	// Process shortcodes in $content first
	$output = do_shortcode($content);

	// generate #id tags from content if none are explicitly set
	$id_default = NULL;
	if (!empty($output)) {
		$id_default = \i4helper\to_anchortext($output);
	}
	$a = shortcode_atts( array(
		SHORTCODE_ATTR_ID => $id_default,
	), $attrs);

	// If we got an ID, generate an anchor
	if (!is_null($a[SHORTCODE_ATTR_ID])) {
		return "<a id='" . esc_attr($a[SHORTCODE_ATTR_ID]) . "'>" . $output . "</a>";
	} else {
		return $output;
	}
}

function get_linkanchors($text) {
	$re = get_shortcode_regex([SHORTCODE_NAME]);
	$list = [];
	if (preg_match_all('/' . $re . '/', $text, $matches, PREG_SET_ORDER)) {
		foreach($matches as $match) {
			$atts = shortcode_parse_atts($match[3]);
			if (empty($atts)) {
				// return values of shortcode_parse_atts are completely insane.
				// sometimes it returns an empty string for valid (= empty) input...
				$atts = array();
			}

			// For anchor-like entries, we can generate some tags from the content field, if given
			$content = $match[5];
			if ((!isset($atts[SHORTCODE_ATTR_LINKHREF])) && (!empty($content))) {
				// Generate "name" from content if it was not set explicitly
				if (!isset($atts[SHORTCODE_ATTR_LABEL])) {
					$atts[SHORTCODE_ATTR_LABEL] = $content;
				}
				// Generate anchor id if it was not set explicitly
				if (!isset($atts[SHORTCODE_ATTR_ID])) {
					$atts[SHORTCODE_ATTR_ID] = \i4helper\to_anchortext($content);
				}
			}

			if (isset($atts[SHORTCODE_ATTR_LABEL])
			   	&& (isset($atts[SHORTCODE_ATTR_ID]) || isset($atts[SHORTCODE_ATTR_LINKHREF]))) {
				array_push($list, $atts);
			}
		}
	}
	return $list;
}

/* Emit a pagelist, but insert extra menu links from post metadata where necessary
 * The process is not as nice as it could be because we have to differentiate between cases where
 * pages already have children (we will receive an end_lvl callback before the closing </ul>)
 * and pages without children (we will have to build our own <ul></ul> in end_el)
 */
class Walker extends \Walker_Page {
	// The Walkers traversal stack: Last entry is the most recently entered page/menuitem
	private $stack = array();
	// Indicator whether a page has children (see above)
	private $page_has_children = array();
	// Ordered map of page-ids to orderd (by menulist order) i4nav-generated children of the respective page
	private $page_subnaventries = array();

	function __construct($pages) {
		foreach($pages as $page) {
			$this->page_has_children[$page->post_parent] = 1;
			$items = get_post_meta($page->ID, SHORTCODE_NAME, true);
			if (is_array($items)) {
				$this->page_subnaventries[$page->ID] = $items;
			} else {
				$this->page_subnaventries[$page->ID] = array();
			}
		}
	}

	function is_relative($url) {
		if (preg_match('/^[^:\/]+:\/\//', $url)) {
			// Url with schema
			return false;
		} elseif (preg_match('/^\//', $url)) {
			// Path-absolute URL (/path/to/file.html) or absolute URL without scheme (//example.org/foo.html)
			return false;
		} else {
			// Relative path (../foo.html, foo/bar.html) or fragment only (#frag)
			return true;
		}
	}

	function is_anchor($url) {
		return strpos($url, "#") === 0;
	}

	// Emit navlink entry for a synthetic child of $page, whose details are stored in $item
	function emit_navlist_link(&$output, $page, $item) {
		if(isset($item[SHORTCODE_ATTR_LINKHREF])) {
			// We deliberatly do the href processing here instead of parsing time to ensure
			// that navlinks are correct even if pages are moved
			$href = $item[SHORTCODE_ATTR_LINKHREF];
			if ($this->is_anchor($href)) {
				$href = get_permalink($page) . $href;
			} elseif ($this->is_relative($href)) {
				$href = get_permalink($page) . "/" . $href;
			}
		} else {
			$href = get_permalink($page) . "#" . $item[SHORTCODE_ATTR_ID];
		}
		$output .= "<li><a href='" . esc_attr($href) . "'>" . esc_html($item[SHORTCODE_ATTR_LABEL]) . "</a></li>";
	}

	// Emit all childitems of $page whose menu-list order is below $order
	// Removes those items from page_subnaventries as well
	function generate_manual_navlist_below_orderid(&$output, $page, $order) {
		while((!empty($this->page_subnaventries[$page->ID]))
			&& \i4helper\attribute($this->page_subnaventries[$page->ID][0], SHORTCODE_ATTR_ORDER, SHORTCODE_ATTR_ORDER_DEFAULT) < $order) {
			$item = array_shift($this->page_subnaventries[$page->ID]);
			$this->emit_navlist_link($output, $page, $item);
		}
	}

	// Emit all items $items, irrespective of their menu-list--order value
	function generate_manual_navlist(&$output, $page) {
		$items = $this->page_subnaventries[$page->ID];
		foreach($items as $item) {
			$this->emit_navlist_link($output, $page, $item);
		}
		$this->page_subnaventries[$page->ID] = array();
	}

	function start_el(&$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		// Emit all subnav entries that have to preceed $page
		if (count($this->stack) > 0) {
			$this->generate_manual_navlist_below_orderid($output, end($this->stack), $page->menu_order);
		}
		// Actually start processing $page
		array_push($this->stack, $page);
		parent::start_el($output, $page, $depth, $args, $current_page);
	}

	function end_lvl( &$output, $depth = 0, $args = array() ) {
		// "Flush out" all remaining synthetic menu items of the current page
		if (count($this->stack) > 0) {
			$cur = end($this->stack);
			$this->generate_manual_navlist($output, $cur);
		}
		parent::end_lvl($output, $depth, $args);
	}

	function end_el(&$output, $page, $depth = 0, $args = array()) {
		array_pop($this->stack);
		// If the page does not have child pages, but synthetic menu entries, create a new list
		if (!isset($this->page_has_children[$page->ID])) {
			$output .= "<ul class='children'>";
			$this->generate_manual_navlist($output, $page);
			$output .= "</ul>";
		}
		parent::end_el($output, $page, $depth, $args);
	}
}


/* Hook saving a page: extract menu items after save and store it in post metadata */
function action_insert_post( $post_id, $post, $update ) {
	$metadata = get_linkanchors($post->post_content);
	\i4helper\stable_usort($metadata, function($a, $b) {
		return  \i4helper\attribute($a, SHORTCODE_ATTR_ORDER, SHORTCODE_ATTR_ORDER_DEFAULT)
		   	<=> \i4helper\attribute($b, SHORTCODE_ATTR_ORDER, SHORTCODE_ATTR_ORDER_DEFAULT);
	});
	update_post_meta($post_id, SHORTCODE_NAME, $metadata);
}

?>
