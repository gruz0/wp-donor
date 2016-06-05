<?php
error_reporting(E_ALL);

define( 'APP_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
define( 'WP_USE_THEMES', false );

require_once( APP_PATH . 'load-settings.php' );

$content = file_get_contents( APP_PATH . 'posts/posts-' . date("Ymd") . '.json' );
$posts   = json_decode( $content );

foreach( $settings['acceptors'] as $acceptor_sitename => $acceptor_settings ) {
	require_once( $acceptor_settings['path'] . 'wp-load.php' );

	// Remove filters to use raw data when inserting the post
	kses_remove_filters();

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

	if ( ! empty( $acceptor_settings['author_id'] ) ) {
		$author_id = absint( $acceptor_settings['author_id'] );
	} else {
		$author_id = DEFAULT_AUTHOR_ID;
	}

	if ( ! empty( $acceptor_settings['create_missing_categories'] ) ) {
		$create_missing_categories = absint( $acceptor_settings['create_missing_categories'] ) == 1;
	} else {
		$create_missing_categories = CREATE_MISSING_CATEGORIES;
	}

	if ( ! empty( $acceptor_settings['default_category_id'] ) ) {
		$default_category_id = absint( $acceptor_settings['default_category_id'] );
	} else {
		$default_category_id = DEFAULT_CATEGORY_ID;
	}

	if ( ! empty( $acceptor_settings['compare_category_by'] ) ) {
		if ( in_array( esc_attr( $acceptor_settings['compare_category_by'] ), array( 'slug', 'name' ) ) ) {
			$compare_category_by = esc_attr( $acceptor_settings['compare_category_by'] );
		} else {
			$compare_category_by = 'slug';
		}
	} else {
		$compare_category_by = COMPARE_CATEGORY_BY;
	}

	// Retrieve all categories
	$all_categories = get_categories( 'hide_empty=0' );
	$categories = array();
	foreach ( $all_categories as $category ) {
		$categories[$category->cat_ID] = array(
			'slug' => esc_attr( $category->slug ),
			'name' => esc_attr( $category->name ),
		);
	}

	foreach ( $posts as $post_idx => $post ) {
		$post_status = 'publish';

		if ( ! $allow_duplicate_post_title ) {
			$query = $wpdb->prepare(
				"SELECT ID, post_title, post_status FROM $wpdb->posts WHERE post_title = %s AND post_type = %s AND post_status = 'publish'",
				$post->title,
				'post',
				OBJECT
			);

			$found_posts = $wpdb->get_results( $query );

			if ( count( $found_posts ) ) {
				if ( $save_duplicate_post_title_to_draft ) {
					$post_status = 'draft';
				} else {
					continue;
				}
			}
		}

		if ( $create_missing_categories ) {

			// TODO: Есть смысл вынести создание несуществующих рубрик до всего цикла обхода постов
			foreach ( $post->categories as $donor_category_slug => $donor_category_name ) {
				$should_create_category = true;
				$acceptor_category_id   = $default_category_id;

				foreach ( $categories as $category_id => $categories_value ) {
					$category_find = false;

					switch ( $compare_category_by ) {
						case 'slug':
							$category_find = $categories_value['slug'] == $donor_category_slug;
							break;

						case 'name':
							$category_find = $categories_value['name'] == $donor_category_name;
							break;
					}

					if ( $category_find ) {
						$should_create_category = false;
						$acceptor_category_id   = $category_id;
						continue;
					}
				}

				if ( $should_create_category ) {
					$insert_category_result = wp_insert_term( $donor_category_name, 'category', array( 'slug' => $donor_category_slug ) );

					if ( is_wp_error( $insert_category_result ) ) {
						$post_category = $insert_category_result->error_data['term_exists'];
					} else {
						$post_category = $insert_category_result['term_id'];
					}

					$post_category = array( $post_category );
				} else {
					$post_category = array( $acceptor_category_id );
				}
			}

		} else {
			$post_category = array( $default_category_id );
		}

		// Create post object
		$new_post = array(
			'post_title'    => $post->title,
			'post_content'  => html_entity_decode( $post->content ),
			'post_status'   => $post_status,
			'post_author'   => $author_id,
			'post_category' => $post_category,
		);

		// Insert the post into the database
		wp_insert_post( $new_post );
	}
}

