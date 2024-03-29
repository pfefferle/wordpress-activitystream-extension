<?php
/**
 * Plugin Name: ActivityStream extension
 * Plugin URI: http://wordpress.org/plugins/activitystream-extension/
 * Description: An extensions which adds several ActivityStreams (<a href="http://www.activitystrea.ms">activitystrea.ms</a>) Feeds
 * Author: Matthias Pfefferle
 * Author URI: https://notiz.blog
 * Version: 1.3.8
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: activitystream-extension
 */

add_action( 'init', array( 'ActivityStreamExtensionPlugin', 'init' ) );

register_activation_hook( __FILE__, array( 'ActivityStreamExtensionPlugin', 'flush_rewrite_rules' ) );
register_deactivation_hook( __FILE__, array( 'ActivityStreamExtensionPlugin', 'flush_rewrite_rules' ) );

/**
 * ActivityStream Extension
 *
 * @author Matthias Pfefferle
 */
class ActivityStreamExtensionPlugin {

	/**
	 * Init function
	 */
	public static function init() {
		require_once dirname( __FILE__ ) . '/includes/functions.php';

		add_filter( 'query_vars', array( 'ActivityStreamExtensionPlugin', 'query_vars' ) );
		add_filter( 'feed_content_type', array( 'ActivityStreamExtensionPlugin', 'feed_content_type' ), 10, 2 );

		// add the as1 feed
		add_feed( 'as1', array( 'ActivityStreamExtensionPlugin', 'do_feed_as1' ) );
		add_action( 'do_feed_as1', array( 'ActivityStreamExtensionPlugin', 'do_feed_as1' ), 10, 1 );
		add_filter( 'as1_object_type', array( 'ActivityStreamExtensionPlugin', 'post_as1_object_type' ), 10, 2 );

		// add the as2 feed
		add_feed( 'as2', array( 'ActivityStreamExtensionPlugin', 'do_feed_as2' ) );
		add_action( 'do_feed_as2', array( 'ActivityStreamExtensionPlugin', 'do_feed_as2' ), 10, 1 );
		add_filter( 'as2_object_type', array( 'ActivityStreamExtensionPlugin', 'post_as2_object_type' ), 10, 2 );

		// push json feed
		add_filter( 'pubsubhubbub_supported_feed_types', array( 'ActivityStreamExtensionPlugin', 'supported_feed_types' ) );

		// extend core feeds with AS1
		add_action( 'atom_ns', array( 'ActivityStreamExtensionPlugin', 'add_atom_activity_namespace' ) );
		add_action( 'atom_entry', array( 'ActivityStreamExtensionPlugin', 'add_atom_activity_object' ) );
		add_action( 'atom_entry', array( 'ActivityStreamExtensionPlugin', 'add_atom_enclosure' ) );
		add_action( 'atom_author', array( 'ActivityStreamExtensionPlugin', 'add_atom_activity_author' ) ); // run before output
		add_action( 'comment_atom_ns', array( 'ActivityStreamExtensionPlugin', 'add_atom_activity_namespace' ) );
		add_action( 'comment_atom_entry', array( 'ActivityStreamExtensionPlugin', 'add_comment_atom_activity_object' ) );

		// discovery
		add_action( 'wp_head', array( 'ActivityStreamExtensionPlugin', 'add_html_header' ), 5 );
		add_action( 'host_meta', array( 'ActivityStreamExtensionPlugin', 'add_host_meta_links' ) );
		add_action( 'webfinger_user_data', array( 'ActivityStreamExtensionPlugin', 'add_webfinger_links' ), 10, 3 );
	}

	/**
	 * Add 'callback' as a valid query variables.
	 *
	 * @param array $vars
	 * @return array
	 */
	public static function query_vars( $vars ) {
		$vars[] = 'callback';
		$vars[] = 'feed';
		$vars[] = 'pretty';

		return $vars;
	}

	/**
	 * Adds "as1" content-type
	 *
	 * @param string $content_type the default content-type
	 * @param string $type the feed-type
	 * @return string the as1 content-type
	 */
	public static function feed_content_type( $content_type, $type ) {
		if ( 'as1' === $type ) {
			return 'application/stream+json';
		}

		if ( 'as2' === $type ) {
			return 'application/activity+json';
		}

		return $content_type;
	}

	/**
	 * Reset rewrite rules
	 */
	public static function flush_rewrite_rules() {
		\flush_rewrite_rules();
	}

	/**
	 * Echos autodiscovery links
	 */
	public static function add_html_header() {
		// check if theme author want to display feed links
		if ( ! current_theme_supports( 'automatic-feed-links' ) ) {
			return;
		}
		?>
<link rel="alternate" type="<?php echo esc_attr( feed_content_type( 'as1' ) ); ?>" title="<?php echo esc_attr( sprintf( __( '%1$s %2$s Activity-Streams Feed', 'activitystream_extension' ), get_bloginfo( 'name' ), __( '&raquo;', 'activitystream_extension' ) ) ); ?>" href="<?php echo esc_url( get_feed_link( 'as1' ) ); ?>" />
<link rel="alternate" type="<?php echo esc_attr( feed_content_type( 'as1' ) ); ?>" title="<?php echo esc_attr( sprintf( __( '%1$s %2$s Activity-Streams Comments Feed ', 'activitystream_extension' ), get_bloginfo( 'name' ), __( '&raquo;', 'activitystream_extension' ) ) ); ?>" href="<?php echo esc_url( get_feed_link( 'comments_as1' ) ); ?>" />
		<?php
		if ( is_singular() ) {
			$id   = 0;
			$post = get_post( $id );

			if ( comments_open() || pings_open() || $post->comment_count > 0 ) {
				?>
<link rel="alternate" type="<?php echo esc_attr( feed_content_type( 'as1' ) ); ?>" title="<?php echo esc_attr( sprintf( __( '%1$s %2$s %3$s Activity-Streams Comments Feed', 'activitystream_extension' ), get_bloginfo( 'name' ), __( '&raquo;', 'activitystream_extension' ), esc_html( get_the_title() ) ) ); ?>" href="<?php echo esc_url( get_post_comments_feed_link( null, 'as1' ) ); ?>" />
				<?php
			}
		}
	}

	/**
	 * Add AS links to the host-meta file
	 *
	 * @param array $jrd the host-meta JRD array
	 *
	 * @return array the filtered array
	 */
	public static function add_host_meta_links( $jrd ) {
		$jrd['links'][] = array(
			'rel'   => 'feed',
			'type'  => esc_attr( feed_content_type( 'as1' ) ),
			'title' => esc_attr( __( 'Activity-Streams 1.0 Feed', 'activitystream_extension' ) ),
			'href'  => esc_url( get_feed_link( 'as1' ) ),
		);

		$jrd['links'][] = array(
			'rel'   => 'feed',
			'type'  => esc_attr( feed_content_type( 'as1' ) ),
			'title' => esc_attr( __( 'Activity-Streams 1.0 Comments Feed ', 'activitystream_extension' ) ),
			'href'  => esc_url( get_feed_link( 'comments_as1' ) ),
		);

		$jrd['links'][] = array(
			'rel'   => 'feed',
			'type'  => esc_attr( feed_content_type( 'as2' ) ),
			'title' => esc_attr( __( 'Activity-Streams 2.0 Feed', 'activitystream_extension' ) ),
			'href'  => esc_url( get_feed_link( 'as2' ) ),
		);

		$jrd['links'][] = array(
			'rel'   => 'feed',
			'type'  => esc_attr( feed_content_type( 'as2' ) ),
			'title' => esc_attr( __( 'Activity-Streams 2.0 Comments Feed ', 'activitystream_extension' ) ),
			'href'  => esc_url( get_feed_link( 'comments_as2' ) ),
		);

		return $jrd;
	}

	/**
	 * Add AS links to the webfinger file
	 *
	 * @param array $jrd the WebFinger JRD array
	 *
	 * @return array the filtered array
	 */
	public static function add_webfinger_links( $jrd, $acct, $user ) {
		$jrd['links'][] = array(
			'rel'   => 'feed',
			'type'  => esc_attr( feed_content_type( 'as1' ) ),
			'title' => esc_attr( __( 'Activity-Streams 1.0 Feed', 'activitystream_extension' ) ),
			'href'  => esc_url( get_author_feed_link( $user->ID, 'as1' ) ),
		);

		$jrd['links'][] = array(
			'rel'   => 'feed',
			'type'  => esc_attr( feed_content_type( 'as2' ) ),
			'title' => esc_attr( __( 'Activity-Streams 2.0 Feed', 'activitystream_extension' ) ),
			'href'  => esc_url( get_author_feed_link( $user->ID, 'as2' ) ),
		);

		return $jrd;
	}

	/**
	 * Echos the activitystream namespace
	 */
	public static function add_atom_activity_namespace() {
		echo 'xmlns:activity="http://activitystrea.ms/spec/1.0/"' . "\n";
	}

	/**
	 * Echos the activity verb and object for the WordPress entries
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
	<activity:object-type>http://activitystrea.ms/schema/1.0/<?php echo esc_attr( $object_type ); ?></activity:object-type>
	<id><?php the_guid(); ?></id>
	<title type="<?php html_type_rss(); ?>"><![CDATA[<?php the_title(); ?>]]></title>
	<summary type="<?php html_type_rss(); ?>"><![CDATA[<?php the_excerpt_rss(); ?>]]></summary>
	<link rel="alternate" type="text/html" href="<?php the_permalink_rss(); ?>" />
		<?php
		$images = activitystream_extension_get_post_images( get_the_ID() );

		if ( $images ) {
			foreach ( $images as $image ) {
				?>
	<link rel="enclosure" type="<?php echo $image['type']; ?>" href="<?php echo $image['url']; ?>" />
				<?php
			}
		}
		?>
</activity:object>
		<?php
	}

	/**
	 * Echos the activity verb and object for the WordPress entries
	 */
	public static function add_atom_enclosure() {
		$images = activitystream_extension_get_post_images( get_the_ID() );

		if ( ! $images ) {
			return false;
		}

		foreach ( $images as $image ) {
			?>
<link rel="enclosure" type="<?php echo $image['type']; ?>" href="<?php echo $image['url']; ?>" />
			<?php
		}
	}

	/**
	 * Echos the activity verb and object for the WordPress comments
	 */
	public static function add_comment_atom_activity_object() {
		?>
<activity:verb>http://activitystrea.ms/schema/1.0/post</activity:verb>
<activity:object>
	<activity:object-type>http://activitystrea.ms/schema/1.0/comment</activity:object-type>
	<id><?php comment_guid(); ?></id>
	<content type="html" xml:base="<?php comment_link(); ?>"><![CDATA[<?php comment_text(); ?>]]></content>
	<link rel="alternate" href="<?php comment_link(); ?>" type="<?php bloginfo_rss( 'html_type' ); ?>" />
	<thr:in-reply-to ref="<?php the_guid(); ?>" href="<?php the_permalink_rss(); ?>" type="<?php bloginfo_rss( 'html_type' ); ?>" />
</activity:object>
<activity:target>
	<activity:object-type>http://activitystrea.ms/schema/1.0/article</activity:object-type>
	<id><?php the_guid(); ?></id>
	<title type="<?php html_type_rss(); ?>"><![CDATA[<?php the_title(); ?>]]></title>
	<summary type="<?php html_type_rss(); ?>"><![CDATA[<?php the_excerpt_rss(); ?>]]></summary>
	<link rel="alternate" type="text/html" href="<?php the_permalink_rss(); ?>" />
</activity:target>
		<?php
	}

	/**
	 * Add author informations to the Atom feed
	 */
	public static function add_atom_activity_author() {
		?>
<activity:object-type>http://activitystrea.ms/schema/1.0/person</activity:object-type>
		<?php
	}

	/**
	 * Adds an as1 json feed
	 */
	public static function do_feed_as1( $for_comments ) {
		if ( $for_comments ) {
			// load comment template
			load_template( dirname( __FILE__ ) . '/templates/feed-as1-comments.php' );
		} else {
			// load post template
			load_template( dirname( __FILE__ ) . '/templates/feed-as1.php' );
		}
	}

	/**
	 * Adds an as2 json feed
	 */
	public static function do_feed_as2( $for_comments ) {
		if ( $for_comments ) {
			// load comment template
			load_template( dirname( __FILE__ ) . '/templates/feed-as2-comments.php' );
		} else {
			// load post template
			load_template( dirname( __FILE__ ) . '/templates/feed-as2.php' );
		}
	}

	/**
	 * Add `json` as "supported feed type" for the WebSub implementation.
	 *
	 * @param array $feed_types The list of supported feed types.
	 *
	 * @return array $feed_types The filtered list of supported feed types.
	 */
	public static function supported_feed_types( $feed_types ) {
		$feed_types[] = 'as1';
		$feed_types[] = 'as2';

		return $feed_types;
	}

	/**
	 * Returns the as1 object for a given post
	 *
	 * @param string $type the object type
	 * @param Object $post the post object
	 * @return string the object type
	 */
	public static function post_as1_object_type( $type, $post ) {
		$post_type = get_post_type( $post );

		switch ( $post_type ) {
			case 'post':
				$post_format = get_post_format( $post );

				switch ( $post_format ) {
					case 'aside':
					case 'status':
					case 'quote':
					case 'note':
						$object_type = 'note';
						break;
					case 'gallery':
					case 'image':
						$object_type = 'image';
						break;
					case 'video':
						$object_type = 'video';
						break;
					case 'audio':
						$object_type = 'audio';
						break;
					default:
						$object_type = 'article';
						break;
				}
				break;
			case 'page':
				$object_type = 'page';
				break;
			case 'attachment':
				$mime_type  = get_post_mime_type();
				$media_type = preg_replace( '/(\/[a-zA-Z]+)/i', '', $mime_type );

				switch ( $media_type ) {
					case 'audio':
						$object_type = 'audio';
						break;
					case 'video':
						$object_type = 'video';
						break;
					case 'image':
						$object_type = 'image';
						break;
				}
				break;
			default:
				$object_type = 'article';
				break;
		}

		return $object_type;
	}

	/**
	 * Returns the as2 object for a given post
	 *
	 * @param string $type the object type
	 * @param Object $post the post object
	 * @return string the object type
	 */
	public static function post_as2_object_type( $type, $post ) {
		$post_type = get_post_type( $post );

		switch ( $post_type ) {
			case 'post':
				$post_format = get_post_format( $post );

				switch ( $post_format ) {
					case 'aside':
					case 'status':
					case 'quote':
					case 'note':
						$object_type = 'Note';
						break;
					case 'gallery':
					case 'image':
						$object_type = 'Image';
						break;
					case 'video':
						$object_type = 'Video';
						break;
					case 'audio':
						$object_type = 'Audio';
						break;
					default:
						$object_type = 'Article';
						break;
				}
				break;
			case 'page':
				$object_type = 'Page';
				break;
			case 'attachment':
				$mime_type  = get_post_mime_type();
				$media_type = preg_replace( '/(\/[a-zA-Z]+)/i', '', $mime_type );

				switch ( $media_type ) {
					case 'audio':
						$object_type = 'Audio';
						break;
					case 'video':
						$object_type = 'Video';
						break;
					case 'image':
						$object_type = 'Image';
						break;
				}
				break;
			default:
				$object_type = 'Article';
				break;
		}

		return $object_type;
	}
}
