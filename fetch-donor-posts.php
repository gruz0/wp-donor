#!/usr/bin/php
<?php
error_reporting(E_ALL);

define( 'APP_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
define( 'WP_USE_THEMES', false );
define( 'DEFAULT_DATE_TIME', '1970.01.01 00:00:00' );
define( 'NEWER_POST_DATE_FILE_PATH', APP_PATH . 'posts/newer_post_date.txt' );

require_once( APP_PATH . 'load-settings.php' );
require_once( APP_PATH . 'functions.php' );

if ( ! is_dir( APP_PATH . 'posts' ) ) {
	mkdir( APP_PATH . 'posts', 0700 );
} else {
	if ( ! is_readable( APP_PATH . 'posts' ) ) {
		die( 'Directory posts is not exists or readable' );
	}
}

include_once( $settings['donor_path'] . 'wp-blog-header.php' );

// TODO: Получение и добавление категорий
// TODO: Получение и добавление меток
// TODO: Загрузка изображений и featured image

$newer_post_date_from_file = load_newer_post_date_from_file();

$date_parts = DateTime::createFromFormat( 'Y.m.d H:i:s', $newer_post_date_from_file );

$args = array(
	'post_type'  => 'post',
	'orderby'    => 'ID',
	'order'      => 'ASC',
	'date_query' => array(
		array(
			array( 'year'   => (int) $date_parts->format( 'Y' ), 'compare' => '>' ),
			array( 'month'  => (int) $date_parts->format( 'm' ), 'compare' => '>' ),
			array( 'day'    => (int) $date_parts->format( 'd' ), 'compare' => '>' ),
			array( 'hour'   => (int) $date_parts->format( 'H' ), 'compare' => '>' ),
			array( 'minute' => (int) $date_parts->format( 'i' ), 'compare' => '>' ),
			array( 'second' => (int) $date_parts->format( 's' ), 'compare' => '>' ),
		),
	),
	'posts_per_page' => -1,
);

$posts = array();
$posts_dates = array();

$donor = new WP_Query( $args );

if ( $donor->have_posts() ) {
	while ( $donor->have_posts() ) {
		$donor->the_post();

		$featured_image = '';
		$large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( ! empty( $large_image_url[0] ) ) {
			$featured_image = esc_url( $large_image_url[0] );
		}

		$categories = get_the_category();
		$categories_list = array();
		foreach ( $categories as $category ) {
			$categories_list[esc_attr( $category->slug )] = esc_attr( $category->name );
		}
		$categories = $categories_list;

		$post_date = get_the_date( 'Y.m.d H:i:s' );

		$posts[] = array(
			'ID'             => get_the_ID(),
			'title'          => wp_strip_all_tags( get_the_title() ),
			'content'        => get_the_content(),
			'date'           => $post_date,
			'featured_image' => $featured_image,
			'categories'     => $categories,
		);

		$posts_dates[] = $post_date;
	}
} else {
	// no posts found
}

// Store newer post date or use previous post date from file if posts not found
if ( count( $posts_dates ) ) {
	arsort( $posts_dates );
	$newer_post_date = array_shift( $posts_dates );
} else {
	$newer_post_date = $newer_post_date_from_file;
}

if ( count( $posts ) ) {
	file_put_contents( APP_PATH . 'posts/posts-' . date("Ymd") . '.json', json_encode( $posts ) );
	file_put_contents( NEWER_POST_DATE_FILE_PATH, $newer_post_date );
	exit(0);
} else {
	exit(127);
}

