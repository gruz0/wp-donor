<?php

if ( ! file_exists( APP_PATH . 'settings.php' ) || ! is_readable( APP_PATH . 'settings.php' ) ) {
	echo 'settings.php is not exists or readable';
	die(1);
}

require_once( APP_PATH . 'settings.php' );

if ( empty( $settings ) || ! is_array( $settings ) ) {
	echo 'Settings are not present!';
	die(1);
}

if ( empty( $settings['donor_path'] ) || ! is_dir( $settings['donor_path'] ) || ! is_readable( $settings['donor_path'] ) ) {
	echo 'donor_path is not present in settings or not exists or not readable';
	die(1);
}

if ( ! empty( $settings['first_run'] ) && $settings['first_run'] ) {
	if ( empty( $settings['donor_start_from'] ) ) {
		echo 'donor_start_from is not present in settings';
		die(1);
	}

	if ( DateTime::createFromFormat( 'Y.m.d', $settings['donor_start_from'] ) ) {
		$settings['donor_start_from'] .= ' 00:00:00';

	} elseif ( DateTime::createFromFormat( 'Y.m.d H:i:s', $settings['donor_start_from'] ) ) {

	} else {
		echo 'donor_start_from has not a valid format. Use "Y.m.d H:i:s"';
		die(1);
	}
} else {
	$settings['first_run'] = false;
}

if ( empty( $settings['acceptors'] ) || ! is_array( $settings['acceptors'] ) || count( $settings['acceptors'] ) == 0 ) {
	echo 'acceptors are not present in settings or not an array';
	die(1);
}

foreach( $settings['acceptors'] as $acceptor_name => $acceptor_values ) {
	if ( empty( $acceptor_name ) ) {
		echo 'acceptor acceptor_name is empty';
		die(1);
	}

	if ( empty( $acceptor_values ) || ! is_array( $acceptor_values ) || count( $acceptor_values ) == 0 ) {
		echo 'acceptors are not present in settings or not an array';
		die(1);
	}

	foreach( $acceptor_values as $key => & $value ) {
		if ( ! is_string( $key ) ) {
			echo 'acceptor key is not present a string';
			die(1);
		}

		switch ( $key ) {
			case 'path': {
				if ( empty( $value ) || ! is_dir( $value ) || ! is_readable( $value ) ) {
					echo 'acceptor path is not present or not exists or not readable';
					die(1);
				}
				break;
			}

			case 'start_from': {
				if ( empty( $value ) ) {
					echo 'acceptor start_from is empty';
					die(1);
				}

				if ( DateTime::createFromFormat( 'Y.m.d', $value ) ) {
					$value .= ' 00:00:00';

				} elseif ( DateTime::createFromFormat( 'Y.m.d H:i:s', $value ) ) {

				} else {
					echo 'acceptor start_from has not a valid format. Use "Y.m.d H:i:s"';
					die(1);
				}

				break;
			}
		}
	}

	// If start_from does not present then we use default value '1970.01.01 00:00:00'
	if ( empty( $acceptor_values['start_from'] ) ) {
		$acceptor_values['start_from'] = '1970.01.01 00:00:00';
	}
}

