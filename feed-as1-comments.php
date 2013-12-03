<?php
/**
 * Activity Streams 1 Feed Template for displaying AS1 Posts feed.
 *
 * @link https://github.com/pento/7B a lot of changes made by @pento
 */

$json = new stdClass();

$json->items = array();

header( 'Content-Type: application/stream+json; charset=' . get_option( 'blog_charset' ), true );

/*
 * The JSONP callback function to add to the JSON feed
 *
 * @param string $callback The JSONP callback function name
 */
$callback = apply_filters( 'as1_json_feed_callback', get_query_var( 'callback' ) );

if ( ! empty( $callback ) && ! apply_filters( 'json_jsonp_enabled', true ) ) {
  status_header( 400 );
  echo json_encode( array(
    'code'  => 'json_callback_disabled',
    'message' => 'JSONP support is disabled on this site.'
  ) );
  exit;
}

if ( preg_match( '/\W/', $callback ) ) {
  status_header( 400 );
  echo json_encode( array(
    'code'  => 'json_callback_invalid',
    'message' => 'The JSONP callback function is invalid.'
  ) );
  exit;
}

/*
 * Action triggerd prior to the AS1 feed being created and sent to the client
 */
do_action( 'commentsas1_json_feed_pre' );

while( have_comments() ) {
  the_comment();

  $comment_post = $GLOBALS['post'] = get_post( $comment->comment_post_ID );

  /*
   * The object type of the current post in the Activity Streams 1 feed
   *
   * @param string $object_type The current object type
   * @param string $post_type The current post type
   */
  $object_type = apply_filters( 'as1_json_object_type', 'post', $comment_post );

  /*
   * The object type of the current post in the Activity Streams 1 feed
   *
   * @param string $object_type The current object type
   * @param string $post_type The current post type
   */
  $comment_object_type = apply_filters( 'commentsas1_json_object_type', 'comment', $comment );

  $item = array(
    'published' => get_comment_time( 'Y-m-d\TH:i:s\Z', true ),
    'generator' => (object)array(
      'url' => 'http://wordpress.org/?v=' . get_bloginfo_rss( 'version' )
    ),
    'provider' => (object)array(
      'url' => get_post_comments_feed_link( $comment_post->ID, 'as1' )
    ),
    'verb' => 'post',
    'target' => (object)array(
      'id'    => get_the_guid($comment_post->ID),
      'displayName' => get_the_title($comment_post->ID),
      'objectType'  => $object_type,
      'summary'   => get_the_excerpt($comment_post->ID),
      'url'   => get_permalink($comment_post->ID)
    ),
    'object' => (object)array(
      'id'    => get_comment_guid(),
      'objectType'  => $comment_object_type,
      'content'   => get_comment_text(),
      'url'   => get_comment_link(),
    ),
    'actor' => (object)array(
      'displayName' => get_comment_author(),
      'objectType'  => 'person',
      'image'   => (object)array(
        'width'  => 96,
        'height' => 96,
        // TODO: get_avatar_url()
        'url'  => 'http://www.gravatar.com/avatar/' . md5( get_comment_author_email() ) . '.png?s=96'
        )
      )
    );

  if (get_comment_author_url()) {
    $item["actor"]->url = get_comment_author_url();
  }

  /*
   * The item to be added to the Activity Streams 1 feed
   *
   * @param object $item The Activity Streams 1 item
   */
  $item = apply_filters( 'commentsas1_json_feed_item', $item );

  $json->items[] = $item;
}

/*
 * The array of data to be sent to the user as JSON
 *
 * @param object $json The JSON data object
 */
$json = apply_filters( 'as1_json_feed', $json );

if ( version_compare( phpversion(), '5.3.0', '<' ) ) {
  // json_encode() options added in PHP 5.3
  $json_str = json_encode( $json );
} else {
  $options = 0;
  // JSON_PRETTY_PRINT added in PHP 5.4
  if ( get_query_var( 'pretty' ) && version_compare( phpversion(), '5.4.0', '>=' ) )
    $options |= JSON_PRETTY_PRINT;

  /*
   * Options to be passed to json_encode()
   *
   * @since 3.8.0
   *
   * @param int $options The current options flags
   */
  $options = apply_filters( 'as1_json_feed_options', $options );

  $json_str = json_encode( $json, $options );
}

if ( ! empty( $callback ) )
  echo "$callback( $json_str );";
else
  echo $json_str;

/*
 * Action triggerd after the AS1 feed has been created and sent to the client
 */
do_action( 'commentsas1_json_feed_post' );