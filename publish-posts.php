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

	if ( ! empty( $acceptor_settings['save_duplicate_post_title_to_draft'] ) ) {
		$save_duplicate_post_title_to_draft = absint( $acceptor_settings['save_duplicate_post_title_to_draft'] ) == 1;
	} else {
		$save_duplicate_post_title_to_draft = SAVE_DUPLICATE_POST_TITLE_TO_DRAFT;
	}

	foreach ( $posts as $post_idx => $post ) {
		$continue    = false;
		$post_status = 'publish';

		if ( ! $allow_duplicate_post_title ) {
			$query = $wpdb->prepare(
				"SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_title = %s AND post_type = %s AND post_status = 'publish'",
				$post->title,
				'post',
				OBJECT
			);

			$found_posts = $wpdb->get_results( $query );

			if ( count( $found_posts ) == 0 ) {
				$continue = true;

			} else {
				if ( $save_duplicate_post_title_to_draft ) {
					$post_status = 'draft';
					$continue    = true;
				}
			}

		} else {
			$continue = true;
		}

		if ( ! $continue ) continue;

		// Create post object
		$new_post = array(
		  'post_title'    => $post->title,
		  'post_content'  => $post->content,
		  'post_status'   => $post_status,
		  'post_author'   => 1,
		  // 'post_category' => array( 8,39 )
		);

		// Insert the post into the database
		wp_insert_post( $new_post );
	}
}

