<?php
/**
 * Display the post content. Optinally allows post ID to be passed
 * @uses the_content()
 *
 * @param int $id Optional. Post ID.
 * @param string $more_link_text Optional. Content for when there is more text.
 * @param bool $stripteaser Optional. Strip teaser content before the more text. Default is false.
 */
function get_the_content_by_id( $post_id = 0, $more_link_text = null, $stripteaser = false ) {
	global $post;
	$post = get_post( $post_id );
	setup_postdata( $post, $more_link_text, $stripteaser );
	$content = get_the_content();
	wp_reset_postdata( $post );

	return $content;
}
