<?php

/**
 * Stores last import date
 *
 * @since 0.1
 * @return void
 */
function save_last_import_date() {
	$last_import_date = date("Y.m.d H:i:s");
}

/**
 * Creates missing categories
 * This function will modify the array $posts and add 'mapped_acceptor_categories' property
 *
 * @since 0.1
 * @param array $posts
 * @param array $categories
 * @param AcceptorSettingsHelper $acceptor_settings_helper
 * @return void
 */
function create_missing_categories( $posts, $categories, $acceptor_settings_helper ) {
	foreach ( $posts as $post_idx => $post ) {
		$all_post_categories = array();

		foreach ( $post->categories as $donor_category_slug => $donor_category_name ) {
			$should_create_category = true;
			$acceptor_category_id   = $acceptor_settings_helper->default_category_id();

			foreach ( $categories as $category_id => $categories_value ) {
				$category_find = false;

				switch ( $acceptor_settings_helper->compare_category_by() ) {
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

			} else {
				$post_category = $acceptor_category_id;
			}

			$all_post_categories[] = $post_category;
		}

		$post->mapped_acceptor_categories = array_unique($all_post_categories);
	}
}

