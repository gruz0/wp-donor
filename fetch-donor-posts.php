#!/usr/bin/php
<?php
/**
 * Fetch donor posts
 *
 * @package  WP_Donor
 * @author   Alexander Gruzov
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     https://github.com/gruz0/wp-donor
 */

error_reporting( E_ALL );

define( 'APP_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
define( 'WP_USE_THEMES', false );
define( 'DEFAULT_DATE_TIME', '1970.01.01 00:00:00' );
define( 'NEWER_POST_DATE_FILE_PATH', APP_PATH . 'posts/newer_post_date.txt' );

require_once( APP_PATH . 'load-settings.php' );
require_once( APP_PATH . 'functions.php' );

require_once( APP_PATH . 'lib/Encoding.php' );
use \ForceUTF8\Encoding;

if ( ! is_dir( APP_PATH . 'posts' ) ) {
	mkdir( APP_PATH . 'posts', 0700 );
} else {
	if ( ! is_readable( APP_PATH . 'posts' ) ) {
		die( 'Directory posts is not exists or readable' );
	}
}

include_once( $settings['donor_path'] . 'wp-blog-header.php' );

// Remove previous files from current date.
array_map( 'unlink', glob( 'posts/posts-' . date_with_timezone( 'Ymd' ) . '*.json' ) );

// TODO: Получение и добавление меток.
// TODO: Загрузка изображений.
// TODO: Здесь надо бы добавить выборку максимального количества постов из настроек WP.
$posts_per_page = 50;

if ( $settings['first_run'] ) {
	$date_parts = DateTime::createFromFormat( 'Y.m.d H:i:s', $settings['donor_start_from'] );
} else {
	$newer_post_date_from_file = load_newer_post_date_from_file();
	$date_parts = DateTime::createFromFormat( 'Y.m.d H:i:s', $newer_post_date_from_file );
}

$args = array(
	'post_type'  => 'post',
	'orderby'    => 'ID',
	'order'      => 'ASC',
	'date_query' => array(
		array(
			array( 'year' => (int) $date_parts->format( 'Y' ), 'compare' => '>=' ),
			array( 'month' => (int) $date_parts->format( 'm' ), 'compare' => '>=' ),
			array( 'day' => (int) $date_parts->format( 'd' ), 'compare' => '>=' ),
			array( 'hour' => (int) $date_parts->format( 'H' ), 'compare' => '>=' ),
			array( 'minute' => (int) $date_parts->format( 'i' ), 'compare' => '>=' ),
			array( 'second' => (int) $date_parts->format( 's' ), 'compare' => '>=' ),
		),
	),
	'posts_per_page'         => $posts_per_page,
	'offset'                 => 0,
	'cache_results'          => false,
	'update_post_meta_cache' => false,
);

$posts                 = array();
$posts_dates           = array();
$temp_posts_dates      = array();
$total_posts_processed = 0;
$page_number           = 0;

$donor       = new WP_Query( $args );
$count_posts = $donor->found_posts;
$pages_count = (int) ( $count_posts / $posts_per_page );

if ( ( $count_posts % $posts_per_page ) > 0 ) {
	$pages_count++;
}

for ( $idx = 0; $idx < $pages_count; $idx++ ) {
	$donor = new WP_Query( $args );

	$domain         = get_site_url();
	$wp_upload_dir  = wp_upload_dir();
	$wp_upload_dir  = str_replace( '/wp-content/uploads', '', $wp_upload_dir['basedir'] );

	if ( $donor->have_posts() ) {
		while ( $donor->have_posts() ) {
			$donor->the_post();

			// Загружаем миниатюру записи.
			$featured_image  = '';
			$large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
			if ( ! empty( $large_image_url[0] ) ) {
				$featured_image = $wp_upload_dir . str_replace( $domain, '', $large_image_url[0] );
				if ( ! file_exists( $featured_image ) ) {
					$featured_image = '';
				}
			}

			// Загружаем рубрики.
			$categories = get_the_category();
			$categories_list = array();
			foreach ( $categories as $category ) {
				$categories_list[ esc_attr( $category->slug ) ] = Encoding::fixUTF8( esc_attr( $category->name ) );
			}
			$categories = $categories_list;

			// Формируем массив постов для сохранения в виде JSON.
			$post_id      = get_the_ID();
			$post_title   = get_the_title();
			$post_content = get_the_content();
			$post_date    = get_the_date( 'Y.m.d H:i:s' );

			// Проверяем название записи на содержание кривых символов.
			if ( ! mb_check_encoding( $post_title, 'UTF-8' ) ) {
				$post_title = Encoding::fixUTF8( $post_title );
			}

			// Проверяем название записи на содержание кривых символов.
			if ( ! mb_check_encoding( $post_content, 'UTF-8' ) ) {
				$post_content = Encoding::fixUTF8( $post_content );
			}

			// Проверяем миниатюру записи на содержание кривых символов.
			if ( ! mb_check_encoding( $featured_image, 'UTF-8' ) ) {
				$featured_image = '';
			}

			$posts[] = array(
				'ID'             => $post_id,
				'title'          => $post_title,
				'content'        => $post_content,
				'date'           => $post_date,
				'featured_image' => $featured_image,
				'categories'     => $categories,
			);

			echo 'Post ' . $post_id . ' "' . $post_title . '" processed – ' . $post_date . "\n";

			$temp_posts_dates[] = $post_date;
			$total_posts_processed++;
		}

		if ( 50 === count( $posts ) || ( ( $pages_count - 1 ) === $idx ) ) {
			file_put_contents(
				APP_PATH . 'posts/posts-' . date_with_timezone( 'Ymd' ) . '-' . sprintf( '%03d', ++$page_number ) . '.json',
				json_encode( $posts )
			);

			// Store newer post date.
			arsort( $temp_posts_dates );
			$posts_dates[] = array_shift( $temp_posts_dates );

			$posts            = array();
			$temp_posts_dates = array();
		}

		echo "===========================\n";
		echo 'Processed now: ' . $total_posts_processed . ' of ' . $count_posts . "\n\n";

		$args['offset'] += $posts_per_page;
		sleep( 2 );

	} else {
		var_dump( '============ NO POSTS FOUND =============' );
		break;
	}

	unset( $donor );
}

echo "===============\n";
echo 'Processed: ' . $total_posts_processed . "\n";

// Store newer post date or use previous post date from file if posts are not found.
if ( count( $posts_dates ) ) {
	arsort( $posts_dates );
	$newer_post_date = array_shift( $posts_dates );
} else {
	$newer_post_date = $newer_post_date_from_file;
}

file_put_contents( NEWER_POST_DATE_FILE_PATH, $newer_post_date );

