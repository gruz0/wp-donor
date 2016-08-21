<?php
/**
 * Functions
 *
 * @package  WP_Donor
 * @author   Alexander Gruzov
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/gruz0/wp-donor
 */

/**
 * Load newer post date from file
 *
 * @since 0.1
 * @return mixed
 */
function load_newer_post_date_from_file() {
	if ( file_exists( NEWER_POST_DATE_FILE_PATH ) && is_readable( NEWER_POST_DATE_FILE_PATH ) ) {
		return trim( file_get_contents( NEWER_POST_DATE_FILE_PATH ) );
	} else {
		echo 'File "' . NEWER_POST_DATE_FILE_PATH . '" does not exist or not readable!' . "\n";
		echo "Try to create it...\n";

		try {
			file_put_contents( NEWER_POST_DATE_FILE_PATH, DEFAULT_DATE_TIME );
			return DEFAULT_DATE_TIME;
		} catch (Exception $e) {
			echo 'Can\'t create file "' . NEWER_POST_DATE_FILE_PATH . '"!' . "\n";
			exit( 127 );
		}
	}
}

/**
 * Creates missing categories
 * This function will modify the array $posts and add 'mapped_acceptor_categories' property
 *
 * @since 0.1

 * @param array                  $posts Posts array.
 * @param array                  $categories Categories array.
 * @param AcceptorSettingsHelper $acceptor_settings_helper Instance of Acceptor Settings Helper.
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
						$category_find = $categories_value['slug'] === $donor_category_slug;
						break;

					case 'name':
						$category_find = $categories_value['name'] === $donor_category_name;
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

		$post->mapped_acceptor_categories = array_unique( $all_post_categories );
	}
}

/**
 * Get posts files as array
 *
 * @since 0.1

 * @return array
 */
function get_files() {
	$dir   = APP_PATH . 'posts';
	$dh    = opendir( $dir );
	$files = array();

	while ( false !== ( $filename = readdir( $dh ) ) ) {
		if ( '.' === $filename || '..' === $filename || strpos( $filename, 'posts-' . date_with_timezone( 'Ymd' ) ) === false ) {
			continue;
		}

		$files[] = $filename;
	}

	sort( $files );

	return $files;
}

/**
 * Returns date with timezone
 *
 * @param string $format Template. Example: Y.m.d H:i:s.
 * @return string
 */
function date_with_timezone( $format ) {
	$tz        = 'Europe/Moscow';
	$timestamp = time();
	$dt        = new DateTime( 'now', new DateTimeZone( $tz ) );
	$dt->setTimestamp( $timestamp );

	return $dt->format( $format );
}

/**
 * Insert featured image to post
 *
 * @param string $featured_image_path Image location in filesystem.
 * @param int    $post_id Post ID.
 * @param string $post_title Post title.
 * @param array  $wp_upload_dir Acceptor's WP upload dir array.
 * @param object $wpdb Reference to $wpdb object.
 * @return mixed
 */
function insert_featured_image( $featured_image_path, $post_id, $post_title, $wp_upload_dir, $wpdb ) {
	// Получаем пользователя и группу wp-config.php для установки их на загружаемый файл.
	$wp_config_path = str_replace( 'wp-content/uploads', '', $wp_upload_dir['basedir'] ) . 'wp-config.php';
	$stat           = stat( $wp_config_path );

	$file_basename = basename( $featured_image_path );
	$new_file_path = $wp_upload_dir['path'] . DIRECTORY_SEPARATOR . $file_basename;

	if ( file_exists( $new_file_path ) ) {
		// Получение attach id.
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $wp_upload_dir['url'] . '/' . $file_basename ) );
		$attach_id = $attachment[0];

	} else {
		if ( ! copy( $featured_image_path, $new_file_path ) ) {
			echo 'Failed to copy ' . $featured_image_path . ' to ' . $new_file_path . '...' . "\n";
		}

		chmod( $new_file_path, 0755 ) or die( 'Can\'t execute chmod on ' . $new_file_path );
		chown( $new_file_path, $stat['uid'] ) or die( 'Can\'t execute chown on ' . $new_file_path );
		chgrp( $new_file_path, $stat['gid'] ) or die( 'Can\'t execute chgrp on ' . $new_file_path );

		// Check the type of file. We'll use this as the 'post_mime_type'.
		$filetype = wp_check_filetype( basename( $new_file_path ), null );

		// Prepare an array of post data for the attachment.
		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $new_file_path ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => $post_title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $new_file_path, $post_id );

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $new_file_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );
	}

	return set_post_thumbnail( $post_id, $attach_id );
}

