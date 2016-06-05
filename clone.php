#!/usr/bin/php
<?php

require_once( 'settings.php' );

if ( empty( $settings ) || ! is_array( $settings ) ) {
	die( 'Settings are not present!' );
}

if ( empty( $settings['donor_path'] ) || ! is_dir( $settings['donor_path'] ) || ! is_readable( $settings['donor_path'] ) ) {
	die( 'donor_path is not present in settings or not exists or not readable' );
}

if ( empty( $settings['acceptors'] ) || ! is_array( $settings['acceptors'] ) || count( $settings['acceptors'] ) == 0 ) {
	die( 'acceptors are not present in settings or not an array' );
}

foreach( $settings['acceptors'] as $acceptor_name => $acceptor_values ) {
	if ( empty( $acceptor_name ) ) {
		die( 'acceptor acceptor_name is empty' );
	}

	if ( empty( $acceptor_values ) || ! is_array( $acceptor_values ) || count( $acceptor_values ) == 0 ) {
		die( 'acceptors are not present in settings or not an array' );
	}

	foreach( $acceptor_values as $key => $value ) {
		if ( ! is_string( $key ) ) {
			die( 'acceptor key is not present a string' );
		}

		switch ( $key ) {
			case 'path':
				if ( empty( $value ) || ! is_dir( $value ) || ! is_readable( $value ) ) {
					die( 'acceptor path is not present or not exists or not readable' );
				}
				break;
		}
	}
}

include_once( $settings['donor_path'] . 'wp-load.php' );

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

$result = array();

$donor = new WP_Query( $args );

if ( $donor->have_posts() ) {
	while ( $donor->have_posts() ) {
		$donor->the_post();

		$featured_image = '';
		$large_image_url = wp_get_attachment_image_src( get_post_thumbnail_id(), 'large' );
		if ( ! empty( $large_image_url[0] ) ) {
			$featured_image = esc_url( $large_image_url[0] );
		}

		$result[] = array(
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

var_dump( $result );

