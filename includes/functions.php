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

/**
 * Default image property, using the post-thumbnail and any attached images.
 *
 * @see https://github.com/willnorris/wordpress-opengraph/blob/master/opengraph.php#L188
 */
function activitystream_extension_get_post_images( $id ) {
	$max_images = apply_filters( 'activitystram_extension_max_images', 3 );

	$images = array();

	// max images can't be negative or zero
	if ( $max_images <= 0 ) {
		$max_images = 1;
	}

	$image_ids = array();
	// list post thumbnail first if this post has one
	if ( function_exists( 'has_post_thumbnail' ) && has_post_thumbnail( $id ) ) {
		$image_ids[] = get_post_thumbnail_id( $id );
		$max_images--;
	}
	// then list any image attachments
	$query = new WP_Query(
		array(
			'post_parent'    => $id,
			'post_status'    => 'inherit',
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'order'          => 'ASC',
			'orderby'        => 'menu_order ID',
			'posts_per_page' => $max_images,
		)
	);
	foreach ( $query->get_posts() as $attachment ) {
		if ( ! in_array( $attachment->ID, $image_ids, true ) ) {
			$image_ids[] = $attachment->ID;
		}
	}
	// get URLs for each image
	foreach ( $image_ids as $id ) {
		$thumbnail = wp_get_attachment_image_src( $id, 'medium' );
		$mimetype  = get_post_mime_type( $id );

		if ( $thumbnail ) {
			$images[] = array(
				'url'  => $thumbnail[0],
				'type' => $mimetype,
			);
		}
	}

	return $images;
}
