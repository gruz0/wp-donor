#!/usr/bin/php
<?php
error_reporting(E_ALL);

define( 'APP_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );
define( 'WP_USE_THEMES', false );

require_once( APP_PATH . 'load-settings.php' );

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

$year  = 2016; // intval(date("Y"));
$month = 5;    // intval(date("m"));
$day   = 30;   // intval(date("d"));

$args = array(
	'post_type'  => 'post',
	'orderby'    => 'ID',
	'order'      => 'ASC',
	'date_query' => array(
		array(
			'year'  => $year,
			'month' => $month,
			'day'   => $day,
		),
	),
);

$posts = array();

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

		$posts[] = array(
			'ID'             => get_the_ID(),
			'title'          => wp_strip_all_tags( get_the_title() ),
			'content'        => get_the_content(),
			'date'           => get_the_date('Y.m.d H:i:s'),
			'featured_image' => $featured_image,
			'categories'     => $categories,
		);
	}
} else {
	// no posts found
}

if ( count( $posts ) ) {
	file_put_contents( APP_PATH . 'posts/posts-' . date("Ymd") . '.json', json_encode( $posts ) );
	exit(0);
} else {
	exit(127);
}

