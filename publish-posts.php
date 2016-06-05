<?php
error_reporting(E_ALL);

define( 'APP_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
define( 'WP_USE_THEMES', false );

require_once( APP_PATH . 'load-settings.php' );

$content = file_get_contents( APP_PATH . 'posts/posts-' . date("Ymd") . '.json' );
$posts   = json_decode( $content );

foreach( $settings['acceptors'] as $acceptor_sitename => $acceptor_settings ) {
	require_once( $acceptor_settings['path'] . 'wp-load.php' );

	if ( ! empty( $acceptor_settings['allow_duplicate_post_title'] ) ) {
		$allow_duplicate_post_title = absint( $acceptor_settings['allow_duplicate_post_title'] ) == 1;
	} else {
		$allow_duplicate_post_title = ALLOW_DUPLICATE_POST_TITLE;
	}

	foreach ( $posts as $post_idx => $post ) {

		if ( ! $allow_duplicate_post_title ) {
			$post_by_title = get_page_by_title( $post->title, OBJECT, 'post' );
			if ( $post_by_title && $post_by_title->post_status == 'publish') {
				echo "Skip post \"{$post->title}\" from publish because title is not unique...\n";
				continue;
			}
		}

		// Create post object
		$new_post = array(
		  'post_title'    => $post->title,
		  'post_content'  => $post->content,
		  'post_status'   => 'publish',
		  'post_author'   => 1,
		  // 'post_category' => array( 8,39 )
		);

		// Insert the post into the database
		wp_insert_post( $new_post );
	}
}

