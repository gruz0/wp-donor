<?php

if ( ! file_exists( APP_PATH . 'settings.php' ) || ! is_readable( APP_PATH . 'settings.php' ) ) {
	die( 'settings.php is not exists or readable' );
}

require_once( APP_PATH . 'settings.php' );

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

