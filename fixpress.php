<?php
/*
Plugin Name: FixPress
Plugin URI: http://www.pross.org.uk
Description: Fix the gallery so it validates XHTML and remove aria from default comment form. Plus other goodies!
Author: Simon Prosser
Version: 0.3
Author URI: http://www.pross.org.uk
*/
define('FIXPRESS', '0.3');
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
				add_action('wp_head', 'fp_css');
}

//
// The css to be added
//
function fp_css() {
echo '<style type="text/css">
.gallery { margin: auto; overflow: hidden; width: 100%; }
.gallery dl { margin: 0px; }
.gallery .gallery-item { float: left; margin-top: 10px; text-align: center; }
.gallery img { border: 2px solid #cfcfcf; }
.gallery .gallery-caption { margin-left: 0; }
.gallery br { clear: both }
.col-2 { width: 50% }
.col-3 { width: 33.333% }
.col-4 { width: 25% }
.col-5 { width: 20% }
.col-6 { width: 16.666% }
.col-7 { width: 14.285% }
.col-8 { width: 12.5% }
.col-9 { width: 11.111% }
</style>';
}
//
// Now the reworked [gallery] shortcode
//
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

function wp_embed_handler_googlevideo( $matches, $attr, $url, $rawattr ) {
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
wp_embed_register_handler( 'googlevideo', '#http://video\.google\.([A-Za-z.]{2,5})/videoplay\?docid=([\d-]+)(.*?)#i', 'wp_embed_handler_googlevideo' );



// fix youtube oEmbed
add_filter('oembed_dataparse', '_strip_embed');
function _strip_embed($data) {
    $data = preg_replace('|<embed.+?>.*?</embed>|i', '', $data);
    return $data;
}
?>