<?php
/*
Plugin Name: ActivityStream extension
Plugin URI: http://wordpress.org/extend/plugins/activitystream-extension/
Description: An extensions which adds several ActivityStreams (<a href="http://www.activitystrea.ms">activitystrea.ms</a>) Feeds
Author: Matthias Pfefferle
Author URI: http://notizblog.org
Version: 1.0.0
*/

add_action('atom_ns', array('ActivityExtension', 'add_atom_activity_namespace'));
add_action('atom_entry', array('ActivityExtension', 'add_atom_activity_object'));
add_action('atom_author', array('ActivityExtension', 'add_atom_activity_author')); // run before output
add_action('comment_atom_ns', array('ActivityExtension', 'add_atom_activity_namespace'));
add_action('comment_atom_entry', array('ActivityExtension', 'add_comment_atom_activity_object'));
add_action('wp_head', array('ActivityExtension', 'add_html_header'), 5);
add_filter('query_vars', array('ActivityExtension', 'query_vars'));
add_filter('feed_content_type', array('ActivityExtension', 'feed_content_type'), 10, 2);

// add 'json' as feed
add_action('do_feed_as1', array('ActivityExtension', 'do_feed_as1'), 10, 1);
add_action('init', array('ActivityExtension', 'init'));
add_filter('as1_object_type', array('ActivityExtension', 'post_object_type'), 10, 2);

// push json feed
add_filter('pshb_feed_urls', array('ActivityExtension', 'publish_to_hub'));

register_activation_hook(__FILE__, array('ActivityExtension', 'flush_rewrite_rules'));
register_deactivation_hook(__FILE__, array('ActivityExtension', 'flush_rewrite_rules'));

/**
 * ActivityStream Extension
 *
 * @author Matthias Pfefferle
 */
class ActivityExtension {
  /**
   * init function
   */
  public static function init() {
    // add the as1 feed
    add_feed('as1', array('ActivityExtension', 'do_feed_as1'));
  }

  /**
   * add 'callback' as a valid query variables.
   *
   * @param array $vars
   * @return array
   */
  public static function query_vars($vars) {
    $vars[] = 'callback';
    $vars[] = 'feed';
    $vars[] = 'pretty';

    return $vars;
  }

  /**
   * adds "as1" content-type
   *
   * @param string $content_type the default content-type
   * @param string $type the feed-type
   * @return string the as1 content-type
   */
  public static function feed_content_type($content_type, $type) {
    if ($type == "as1") {
      return 'application/stream+json';
    }

    return $content_type;
  }

  /**
   * reset rewrite rules
   */
  public static function flush_rewrite_rules() {
    global $wp_rewrite;
    $wp_rewrite->flush_rules();
  }

  /**
   * echos autodiscovery links
   */
  public static function add_html_header() {
  	// check if theme author want to display feed links
    if ( !current_theme_supports('automatic-feed-links') )
  		return;
?>
<link rel="alternate" type="<?php echo feed_content_type("as1"); ?>" title="<?php echo esc_attr( sprintf( __('%1$s %2$s Activity-Streams Feed', 'activity-extension'), get_bloginfo('name'), __('&raquo;', 'activity-extension') ) ); ?>" href="<?php echo get_feed_link('as1'); ?>" />
<link rel="alternate" type="<?php echo feed_content_type("as1"); ?>" title="<?php echo esc_attr( sprintf( __('%1$s %2$s Activity-Streams Comments Feed', 'activity-extension'), get_bloginfo('name'), __('&raquo;', 'activity-extension') ) ); ?>" href="<?php echo get_feed_link('comments_as1'); ?>" />
<?php
    if (is_singular()) {
      $id = 0;
      $post = get_post( $id );

      if ( comments_open() || pings_open() || $post->comment_count > 0 ) {
?>
<link rel="alternate" type="<?php echo feed_content_type("as1"); ?>" title="<?php echo esc_attr( sprintf( __('%1$s %2$s %3$s Activity-Streams Comments Feed', 'activity-extension'), get_bloginfo('name'), __('&raquo;', 'activity-extension'), esc_html( get_the_title() ) ) ); ?>" href="<?php echo get_post_comments_feed_link(null, "as1"); ?>" />
<?php
      }
    }
  }

  /**
   * echos the activitystream namespace
   */
  public static function add_atom_activity_namespace() {
    echo 'xmlns:activity="http://activitystrea.ms/spec/1.0/"'."\n";
  }

  /**
   * echos the activity verb and object for the wordpress entries
   */
  public static function add_atom_activity_object() {
    /*
     * The object type of the current post in the Activity Streams 1 feed
     *
     * @param Object $comment_post The current post
     */
    $object_type = apply_filters( 'as1_object_type', 'article', get_post() );
?>
    <activity:verb>http://activitystrea.ms/schema/1.0/post</activity:verb>
    <activity:object>
      <activity:object-type>http://activitystrea.ms/schema/1.0/<?php echo $object_type; ?></activity:object-type>
      <id><?php the_guid(); ?></id>
      <title type="<?php html_type_rss(); ?>"><![CDATA[<?php the_title(); ?>]]></title>
      <summary type="<?php html_type_rss(); ?>"><![CDATA[<?php the_excerpt_rss(); ?>]]></summary>
      <link rel="alternate" type="text/html" href="<?php the_permalink_rss() ?>" />
    </activity:object>
<?php
  }

  /**
   * echos the activity verb and object for the wordpress comments
   */
  public static function add_comment_atom_activity_object() {
?>
    <activity:verb>http://activitystrea.ms/schema/1.0/post</activity:verb>
    <activity:object>
      <activity:object-type>http://activitystrea.ms/schema/1.0/comment</activity:object-type>
      <id><?php comment_guid(); ?></id>
      <content type="html" xml:base="<?php comment_link(); ?>"><![CDATA[<?php comment_text(); ?>]]></content>
      <link rel="alternate" href="<?php comment_link(); ?>" type="<?php bloginfo_rss('html_type'); ?>" />
      <thr:in-reply-to ref="<?php the_guid() ?>" href="<?php the_permalink_rss() ?>" type="<?php bloginfo_rss('html_type'); ?>" />
    </activity:object>
    <activity:target>
      <activity:object-type>http://activitystrea.ms/schema/1.0/article</activity:object-type>
      <id><?php the_guid(); ?></id>
      <title type="<?php html_type_rss(); ?>"><![CDATA[<?php the_title(); ?>]]></title>
      <summary type="<?php html_type_rss(); ?>"><![CDATA[<?php the_excerpt_rss(); ?>]]></summary>
      <link rel="alternate" type="text/html" href="<?php the_permalink_rss() ?>" />
    </activity:target>
<?php
  }

  /**
   * adds a json feed
   */
  public static function do_feed_as1( $for_comments ) {
    if ( $for_comments ) {
      // load comment template
      load_template(dirname(__FILE__) . '/feed-as1-comments.php');
    } else {
      // load post template
      load_template(dirname(__FILE__) . '/feed-as1.php');
    }
  }

  /**
   * adds the json feed to PubsubHubBub
   *
   * @param array $feeds
   * @return array
   */
  public static function publish_to_hub($feeds) {
    $feeds[] = get_feed_link('as1');

    return $feeds;
  }

  public static function add_atom_activity_author() {
?>
    <activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
    <link rel='alternate' type='text/html' href='<?php echo get_author_posts_url( get_the_author_meta( "ID" ) ); ?>' />
<?php
  }

  /**
   * returns the activity object for a given post
   *
   * @param string $type the object type
   * @param Object $post the post object
   * @return string the object type
   */
  public static function post_object_type($type, $post) {
    $post_type = get_post_type($post);
    switch ( $post_type ) {
      case "post":
        $post_format = get_post_format($post);
        switch ( $post_format ) {
          case "aside":
          case "status":
          case "quote":
          case "note":
            $object_type = "note";
            break;
          case "gallery":
          case "image":
            $object_type = "image";
            break;
          case "video":
            $object_type = "video";
            break;
          case "audio":
            $object_type = "audio";
            break;
          default:
            $object_type = "article";
            break;
        }
        break;
      case "page":
        $object_type = "page";
        break;
      case "attachment":
        $mime_type = get_post_mime_type();
        $media_type = preg_replace("/(\/[a-zA-Z]+)/i", "", $mime_type);

        switch ($media_type) {
          case 'audio':
            $object_type = "audio";
            break;
          case 'video':
            $object_type = "video";
            break;
          case 'image':
            $object_type = "image";
            break;
        }
        break;
      default:
        $object_type = "article";
        break;
    }

    return $object_type;
  }
}
