#!/usr/bin/php
<?php

define( 'APP_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR );

require_once( APP_PATH . 'load-settings.php' );

if ( ! is_dir( APP_PATH . 'posts' ) ) {
	mkdir( APP_PATH . 'posts', 0700 );
} else {
	if ( ! is_readable( APP_PATH . 'posts' ) ) {
		die( 'Directory posts is not exists or readable' );
	}
}

define( 'WP_USE_THEMES', false );
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

		$posts[] = array(
			'ID'             => get_the_ID(),
			'title'          => get_the_title(),
			'content'        => get_the_content(),
			'date'           => get_the_date('Y.m.d H:i:s'),
			'featured_image' => $featured_image,
		);
	}
} else {
	// no posts found
}

var_dump( $posts );

