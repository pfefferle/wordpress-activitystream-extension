<?php
/**
 * Activity Streams 2 Feed Template for displaying AS2 Comments feed.
 */

$json = new stdClass();

$json->{'@context'} = 'http://www.w3.org/ns/activitystreams';
$json->type         = 'Collection';

if ( is_singular() ) {
	$json->id   = get_post_comments_feed_link( get_the_ID(), 'as2' );
	$json->name = esc_attr( sprintf( __( '"%1$s" - comments', 'activitystream_extension' ), get_the_title() ) );
} else {
	$json->id   = get_feed_link( 'comments_as2' );
	$json->name = esc_attr( sprintf( __( '"%1$s" - comments', 'activitystream_extension' ), get_bloginfo( 'name' ) ) );
}

// phpcs:ignore
$json->totalItems = (int) get_option( 'posts_per_rss' );

$json->items = array();

header( 'Content-Type: ' . feed_content_type( 'as2' ), true );

/*
 * The JSONP callback function to add to the JSON feed
 *
 * @param string $callback The JSONP callback function name
 */
$callback = apply_filters( 'as2_feed_callback', get_query_var( 'callback' ) );

if ( ! empty( $callback ) && ! apply_filters( 'json_jsonp_enabled', true ) ) {
	status_header( 400 );
	echo wp_json_encode(
		array(
			'code'    => 'json_callback_disabled',
			'message' => 'JSONP support is disabled on this site.',
		)
	);
	exit;
}

if ( preg_match( '/\W/', $callback ) ) {
	status_header( 400 );
	echo wp_json_encode(
		array(
			'code'    => 'json_callback_invalid',
			'message' => 'The JSONP callback function is invalid.',
		)
	);
	exit;
}

/*
 * Action triggerd prior to the AS1 feed being created and sent to the client
 */
do_action( 'comments_as2_feed_pre' );

while ( have_comments() ) {
	the_comment();

	$GLOBALS['post'] = get_post( $comment->comment_post_ID );
	$comment_post    = $GLOBALS['post'];

	/*
	 * The object type of the current post in the Activity Streams 1 feed
	 *
	 * @param Object $comment_post The current post
	 */
	$object_type = apply_filters( 'as2_object_type', 'article', $comment_post );

	/*
	 * The object type of the current comment in the Activity Streams 1 feed
	 *
	 * @param Object $comment The current comment
	 */
	$comment_object_type = apply_filters( 'comments_as2_object_type', 'comment', $comment );

	$item = array(
		'published' => get_comment_time( 'Y-m-d\TH:i:s\Z', true ),
		'generator' => 'http://wordpress.org/?v=' . get_bloginfo_rss( 'version' ),
		'id'        => get_post_comments_feed_link( get_the_ID(), 'as2' ),
		'type'      => 'Create',
		'name'      => esc_attr( sprintf( __( '%1$s posted a comment', 'activitystream_extension' ), get_comment_author() ) ),
		'inReplyTo' => (object) array(
			'id'      => get_the_guid( $comment_post->ID ),
			'type'    => $object_type,
			'name'    => get_the_title( $comment_post->ID ),
			'summary' => get_the_excerpt( $comment_post->ID ),
			'url'     => get_permalink( $comment_post->ID ),
		),
		'object'    => (object) array(
			'id'      => get_comment_guid(),
			'type'    => 'Note',
			'name'    => get_comment_text(),
			'content' => get_comment_text(),
			'url'     => get_comment_link(),
		),
		'actor'     => (object) array(
			'name'  => get_comment_author(),
			'type'  => 'Person',
			'image' => (object) array(
				'type'   => 'Link',
				'width'  => 96,
				'height' => 96,
				'href'   => get_avatar_url( get_the_author_meta( 'email' ), array( 'size' => 96 ) ),
			),
		),
	);

	// check if comment author provided his URL
	if ( get_comment_author_url() ) {
		$item['actor']->url = get_comment_author_url();
		$item['actor']->id  = get_comment_author_url();
	}

	/*
	 * The item to be added to the Activity Streams 1 feed
	 *
	 * @param object $item The Activity Streams 1 item
	 */
	$item = apply_filters( 'comments_as2_feed_item', $item );

	$json->items[] = $item;
}

/*
 * The array of data to be sent to the user as JSON
 *
 * @param object $json The JSON data object
 */
$json = apply_filters( 'comments_as2_feed', $json );

$options = 0;
// JSON_PRETTY_PRINT added in PHP 5.4
if ( get_query_var( 'pretty' ) ) {
	$options |= JSON_PRETTY_PRINT;
}

/*
 * Options to be passed to wp_json_encode()
 *
 * @param int $options The current options flags
 */
$options = apply_filters( 'as2_feed_options', $options );

$json_str = wp_json_encode( $json, $options );

if ( ! empty( $callback ) ) {
	echo esc_html( $callback ) . "($json_str);";
} else {
	echo $json_str;
}

/*
 * Action triggerd after the AS1 feed has been created and sent to the client
 */
do_action( 'comments_as2_feed_post' );
