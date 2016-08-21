#!/usr/bin/php
<?php
/**
 * Publish posts
 *
 * @package  WP_Donor
 * @author   Alexander Gruzov
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/gruz0/wp-donor
 */

error_reporting( E_ALL );
set_time_limit( 0 );

define( 'APP_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
define( 'WP_USE_THEMES', false );

require_once( APP_PATH . 'load-settings.php' );
require_once( APP_PATH . 'functions.php' );
require_once( APP_PATH . 'acceptor-settings-helper.php' );

$files = get_files();

for ( $idx = 0; $idx < count( $files ); $idx++ ) {
	echo "\n" . 'Processing file posts/' . $files[ $idx ] . "...\n";

	$content = file_get_contents( APP_PATH . 'posts/' . $files[ $idx ] );
	$posts = json_decode( $content );

	foreach ( $settings['acceptors'] as $acceptor_sitename => $acceptor_settings ) {
		require_once( $acceptor_settings['path'] . 'wp-load.php' );

		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( $acceptor_settings['path'] . 'wp-admin/includes/image.php' );

		$wp_upload_dir = wp_upload_dir();

		// Remove filters to use raw data when inserting the post.
		kses_remove_filters();

		// Retrieve all categories.
		$all_categories = get_categories( 'hide_empty=0' );
		$categories = array();
		foreach ( $all_categories as $category ) {
			$categories[ $category->cat_ID ] = array(
				'slug' => esc_attr( $category->slug ),
				'name' => esc_attr( $category->name ),
			);
		}

		// Instantiate AcceptorSettingsHelper.
		$acceptor_settings_helper = new AcceptorSettingsHelper( $acceptor_settings );

		if ( $acceptor_settings_helper->create_missing_categories() ) {
			create_missing_categories( & $posts, $categories, $acceptor_settings_helper );
		}

		$errors          = array();
		$posts_processed = 0;
		$posts_count     = count( $posts );

		foreach ( $posts as $post_idx => $post ) {
			if ( $post->date < $acceptor_settings_helper->start_from() ) {
				$errors[] = "Skip the post #{$post->ID} \"{$post->title}\" because it has the date lower than {$acceptor_settings_helper->start_from()}";
				continue;
			}

			$post_status = 'publish';

			if ( ! $acceptor_settings_helper->allow_duplicate_post_title() ) {
				$posts_found = $wpdb->get_row( $wpdb->prepare(
					"SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_title = %s AND post_type = %s AND post_status = 'publish' LIMIT 1",
					$post->title,
					'post'
				) );

				if ( $posts_found ) {
					if ( $acceptor_settings_helper->save_duplicate_post_title_to_draft() ) {
						$post_status = 'draft';
					} else {
						continue;
					}
				}
			}

			if ( $acceptor_settings_helper->create_missing_categories() ) {
				$post_category = $post->mapped_acceptor_categories;
			} else {
				$post_category = array( $acceptor_settings_helper->default_category_id() );
			}

			// NOTE: Если пост не публикуется, то скорее всего по причине пустого контента.
			$post_content = html_entity_decode( $post->content );
			$post_content = empty( $post_content ) ? '<p></p>' : $post_content;

			// Create post object.
			$new_post = array(
				'post_title'    => $post->title,
				'post_content'  => $post_content,
				'post_status'   => $post_status,
				'post_author'   => $acceptor_settings_helper->author_id(),
				'post_category' => $post_category,
			);

			echo 'Publishing post #' . $post->ID . ' "' . $post->title . '"... ';

			// Insert the post into the database.
			// True in last param used in WP_Error to return error description.
			$result = wp_insert_post( $new_post, true );

			if ( is_wp_error( $result ) ) {
				echo "Failed!\n";
				$errors[] = "Error occured when posting #{$post->ID} \"{$post->title}\": {$result->get_error_message()}";
				var_dump( $new_post );
			} else {
				$new_post_id = $result;

				echo 'Done! New ID: #' . $new_post_id . "\n";

				// Try to insert featured image.
				if ( $acceptor_settings_helper->use_featured_images() ) {
					echo 'Uploading featured image... ';

					if ( empty( $post->featured_image ) && $acceptor_settings_helper->skip_featured_image_if_empty() ) {
						echo "Skip because it empty!\n\n";
					} else {
						$result = insert_featured_image( $post->featured_image, $new_post_id, $post->title, $wp_upload_dir, & $wpdb );
						if ( $result ) {
							echo "Done!\n\n";
						} else {
							echo "Failed!\n\n";
						}
					}
				}
			}

			$posts_processed++;

			if ( 0 === $posts_processed % 25 || ( ( $posts_count - 1 ) === $post_idx ) ) {
				echo date_with_timezone( 'Y-m-d H:i:s' ) . ' – Processed ' . $posts_processed . ' posts from file ' . $files[ $idx ] . " Sleeping...\n";
				sleep( 2 );
			}
		}
	}
}

if ( count( $errors ) ) {
	var_dump( '=== ERRORS ===' );
	var_dump( $errors );
}

