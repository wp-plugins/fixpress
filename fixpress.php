<?php
/*
Plugin Name: FixPress
Plugin URI: http://www.pross.org.uk
Description: Fix the gallery so it validates XHTML and remove aria from default comment form. Plus other goodies!
Author: Simon Prosser
Version: 0.8
Author URI: http://www.pross.org.uk
*/
define( 'FIXPRESS', '0.8' );
add_action( 'wp_footer', '_fp_foot' );

//
// fix the gallery...
//
// we need to add our custom css to the head BEFORE the shortcode 
//
add_action('get_header', 'conditionally_add_css');
function conditionally_add_css () {
  global $posts;
 		if( !empty( $posts ) )
			foreach( $posts as $post )
			if ( strstr( $post->post_content, '[gallery]' ) )
wp_enqueue_style( 'fixpress_gallery_css', WP_PLUGIN_URL. '/fixpress/css/gallery.css', false, FIXPRESS );
}

remove_shortcode('gallery', 'gallery_shortcode');
add_shortcode('gallery', 'fixpress_gallery_shortcode');
function fixpress_gallery_shortcode($attr) {
	global $post, $wp_locale;
	static $instance = 0;
	$instance++;
	// Allow plugins/themes to override the default gallery template.
	$output = apply_filters('post_gallery', '', $attr);
	if ( $output != '' )
		return $output;
	// We're trusting author input, so let's at least make sure it looks like a valid orderby statement
	if ( isset( $attr['orderby'] ) ) {
		$attr['orderby'] = sanitize_sql_orderby( $attr['orderby'] );
		if ( !$attr['orderby'] )
			unset( $attr['orderby'] );
	}
	extract(shortcode_atts(array(
		'order'      => 'ASC',
		'orderby'    => 'menu_order ID',
		'id'         => $post->ID,
		'itemtag'    => 'dl',
		'icontag'    => 'dt',
		'captiontag' => 'dd',
		'columns'    => 3,
		'size'       => 'thumbnail',
		'include'    => '',
		'exclude'    => ''
	), $attr));
	$id = intval($id);
	if ( 'RAND' == $order )
		$orderby = 'none';
	if ( !empty($include) ) {
		$include = preg_replace( '/[^0-9,]+/', '', $include );
		$_attachments = get_posts( array('include' => $include, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
		$attachments = array();
		foreach ( $_attachments as $key => $val ) {
			$attachments[$val->ID] = $_attachments[$key];
		}
	} elseif ( !empty($exclude) ) {
		$exclude = preg_replace( '/[^0-9,]+/', '', $exclude );
		$attachments = get_children( array('post_parent' => $id, 'exclude' => $exclude, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	} else {
		$attachments = get_children( array('post_parent' => $id, 'post_status' => 'inherit', 'post_type' => 'attachment', 'post_mime_type' => 'image', 'order' => $order, 'orderby' => $orderby) );
	}
	if ( empty($attachments) )
		return '';
	if ( is_feed() ) {
		$output = "\n";
		foreach ( $attachments as $att_id => $attachment )
			$output .= wp_get_attachment_link($att_id, $size, true) . "\n";
		return $output;
	}
	$itemtag = tag_escape($itemtag);
	$captiontag = tag_escape($captiontag);
	$columns = intval($columns);
//	$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
	$float = is_rtl() ? 'right' : 'left';
	$selector = "gallery-{$instance}";
	$output = apply_filters('gallery_style', "
    <div id='$selector' class='gallery galleryid-{$id}'>");
	$i = 0;
	foreach ( $attachments as $id => $attachment ) {
    $link = isset($attr['link']) && 'file' == $attr['link'] ? wp_get_attachment_link($id, $size, false, false) : wp_get_attachment_link($id, $size, true, false);
    $output .= "<{$itemtag} class='gallery-item col-{$columns}'>";
    $output .= "
        <{$icontag} class='gallery-icon'>
            $link
        </{$icontag}>";
    if ( $captiontag && trim($attachment->post_excerpt) ) {
        $output .= "
            <{$captiontag} class='gallery-caption'>
            " . wptexturize($attachment->post_excerpt) . "
            </{$captiontag}>";
    } else {
        $output .= "
            <{$captiontag} class='gallery-caption'>
            </{$captiontag}>";
	}
    $output .= "</{$itemtag}>";
    if ( $columns > 0 && ++$i % $columns == 0 )
        $output .= '<br />';
}
$output .= "</div>\n";
$output .= "<!-- Gallery fixed using FixPress http://www.pross.org.uk -->";
return $output;
}

//
//fix the comment form!
//
function fp_comment() {
global $args;
$args['comment_field'] = '<p class="comment-form-comment">' .
                '<label for="comment">' . __( 'Comment' ) . '</label>' .
                '<textarea id="comment" name="comment" cols="45" rows="8"></textarea>' .
                '</p><!-- #form-section-comment .form-section -->';
return $args['comment_field'];
}
add_filter('comment_form_field_comment','fp_comment');

function fp_author($fields, $args = array(), $post_id = null) {
global $user_identity, $id;
if ( null === $post_id )
$post_id = $id;
else
$id = $post_id;
$commenter = wp_get_current_commenter();
$req = get_option( 'require_name_email' );
$fields['author'] = '<p class="comment-form-author">' .
                '<label for="author">' . __( 'Name' ) . '</label> ' .
                ( $req ? '<span class="required">*</span>' : '' ) .
                '<input id="author" name="author" type="text" value="' .
                esc_attr( $commenter['comment_author'] ) . '" size="30" />' .
                '</p><!-- #form-section-author .form-section -->';
$fields['email'] = '<p class="comment-form-email">' .
                '<label for="email">' . __( 'Email' ) . '</label> ' .
                ( $req ? '<span class="required">*</span>' : '' ) .
                '<input id="email" name="email" type="text" value="' . esc_attr(  $commenter['comment_author_email'] ) . '" size="30" />' .
                '</p><!-- #form-section-email .form-section -->';
return $fields;
}
add_filter('comment_form_default_fields','fp_author');

// fix google video!

function _fp_wp_embed_handler_googlevideo( $matches, $attr, $url, $rawattr ) {
	// If the user supplied a fixed width AND height, use it
	if ( !empty($rawattr['width']) && !empty($rawattr['height']) ) {
		$width  = (int) $rawattr['width'];
		$height = (int) $rawattr['height'];
	} else {
		list( $width, $height ) = wp_expand_dimensions( 425, 344, $attr['width'], $attr['height'] );
	}

	//return apply_filters( 'embed_googlevideo', '<embed pro type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docid=' . esc_attr($matches[2]) . '&amp;hl=en&amp;fs=true" style="width:' . esc_attr($width) . 'px;height:' . esc_attr($height) . 'px" allowFullScreen="true" allowScriptAccess="always" />', $matches, $attr, $url, $rawattr );
	return apply_filters( 'embed_googlevideo', '<object type="application/x-shockwave-flash" data="http://video.google.com/googleplayer.swf?docid=' . esc_attr($matches[2]) . '" width="'  . esc_attr($width) . '" height="'  . esc_attr($height) . '"><param name="movie" value="http://video.google.com/googleplayer.swf?docid=' . esc_attr($matches[2]) . '" /><param name="FlashVars" value="playerMode=embedded" /><param name="wmode" value="transparent" /></object>', $matches, $attr, $url, $rawattr );
	}
wp_embed_register_handler( 'googlevideo', '#http://video\.google\.([A-Za-z.]{2,5})/videoplay\?docid=([\d-]+)(.*?)#i', '_fp_wp_embed_handler_googlevideo', 1 );







// fix youtube oEmbed

function add_transparent($oembvideo) {
$patterns = array();
$replacements = array();

$patterns[] = '|<embed.+?>.*?</embed>|i';
$patterns[] = '/width=/';
$patterns[] = '/type="application/x-shockwave-flash" type="application/x-shockwave-flash"/';
$patterns[] = '/allowscriptaccess="always"/';
$patterns[] = '/><\/param>/';

$replacements[] = '';
$replacements[] = 'type="application/x-shockwave-flash" width=';
$replacements[] = 'type="application/x-shockwave-flash"';
$replacements[] = 'wmode="transparent" allowscriptaccess="always"';
$replacements[] = ' />';
return preg_replace($patterns, $replacements, $oembvideo);

	return $oembvideo;
}
add_filter('embed_oembed_html', 'add_transparent');


// remove role= from searchform..
function _remove_role($form) {

     $form = '<form method="get" id="searchform" action="' . home_url( '/' ) . '" >
      <div><label class="screen-reader-text" for="s">' . __('Search for:') . '</label>
      <input type="text" value="' . get_search_query() . '" name="s" id="s" />
      <input type="submit" id="searchsubmit" value="'. esc_attr__('Search') .'" />
      </div>
      </form>';

return $form;
}


add_filter('get_search_form', '_remove_role');



function _fp_foot() {
	echo '
<!-- Using FixPress v' . FIXPRESS .' -->
';
	}
?>
