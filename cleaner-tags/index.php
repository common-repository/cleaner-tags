<?php
/*
Plugin Name: Cleaner Tags
Plugin URI: http://www.ccastig.com
Description: So fresh and so clean (because tag clouds are messy and so 2005).
Author: Chris Castiglione 
Version: 0.5
Author URI: http://www.ccastig.com
*/

//ADD STYLES
    function add_my_stylesheet() {
        $myStyleUrl = WP_PLUGIN_URL . '/cleaner-tags/styles.css';
        $myStyleFile = WP_PLUGIN_DIR . '/cleaner-tags/styles.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('myStyleSheets', $myStyleUrl);
            wp_enqueue_style('myStyleSheets');
        }
    }

    /*
     * register with hook 'wp_print_styles'
     */
    add_action('wp_print_styles', 'add_my_stylesheet');



//FUNCTIONS TO GENERATE THE CLEANER TAGS LIST 
function cleaner_tag_cloud( $args = '' ) {
	$defaults = array(
		'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 2,
		'format' => 'flat', 'orderby' => 'count', 'order' => 'DESC',
		'exclude' => '', 'include' => ''
	);
	$args = wp_parse_args( $args, $defaults );

	$tags = get_tags( array_merge($args, array('orderby' => 'count', 'order' => 'DESC', 'number' => 12)) ); // Always query top tags

	if ( empty($tags) )
		return;

	$return = cleaner_generate_tag_cloud( $tags, $args ); // Where the top tags get sorted according to $args
	if ( is_wp_error( $return ) )
		return false;
	else
		echo apply_filters( 'cleaner_tag_cloud', $return, $args );
}

function cleaner_generate_tag_cloud( $tags, $args = '' ) {
	global $wp_rewrite;
	$defaults = array(
		'smallest' => 8, 'largest' => 22, 'unit' => 'pt', 'number' => 2,
		'format' => 'flat', 'orderby' => 'count', 'order' => 'DESC'
	);
	$args = wp_parse_args( $args, $defaults );
	extract($args);

	if ( !$tags )
		return;
	$counts = $tag_links = array();
	foreach ( (array) $tags as $tag ) {
		$counts[$tag->name] = $tag->count;
		$tag_links[$tag->name] = get_tag_link( $tag->term_id );
		if ( is_wp_error( $tag_links[$tag->name] ) )
			return $tag_links[$tag->name];
		$tag_ids[$tag->name] = $tag->term_id;
	}

	$min_count = min($counts);
	$spread = max($counts) - $min_count;
	if ( $spread <= 0 )
		$spread = 1;
	$font_spread = $largest - $smallest;
	if ( $font_spread <= 0 )
		$font_spread = 1;
	$font_step = $font_spread / $spread;

	if ( 'name' == $orderby )
		uksort($counts, 'strnatcasecmp');
	else
		asort($counts);

	if ( 'DESC' == $order )
		$counts = array_reverse( $counts, true );

	$a = array();

	$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

	foreach ( $counts as $tag => $count ) {
		$tag_id = $tag_ids[$tag];
		$tag_link = clean_url($tag_links[$tag]);
		$tag = str_replace(' ', '&nbsp;', wp_specialchars( $tag ));
		$a[] = "\t <li><span class=\"count\">$count</span><a href=\"". $tag_link . "\">$tag </a></li>";
	}

	switch ( $format ) :
	case 'array' :
		$return =& $a;
		break;
	case 'list' :
		$return = "<ul class='wp-tag-cloud'>\n\t<li>";
		$return .= join("</li>\n\t<li>", $a);
		$return .= "</li>\n</ul>\n";
		break;
	default :
		$return = join("\n", $a);
		break;
	endswitch;

	return apply_filters( 'cleaner_generate_tag_cloud', $return, $tags, $args );
}

//PRINT THE WIDGET 
function widget_cleanerTags($args) {
  extract($args);
  echo $before_widget;
  echo $before_title;?>Tags<?php echo $after_title;
  echo "<ul class=\"cleanertags\">";
  echo cleaner_tag_cloud('number=0&orderby=count&order=DESC'); 
  echo "</ul>";
  echo $after_widget;
}

//ACTIONS
function cleanerTags_init()
{
  register_sidebar_widget(__('Cleaner Tags'), 'widget_cleanerTags');
}
add_action("plugins_loaded", "cleanerTags_init");
?>