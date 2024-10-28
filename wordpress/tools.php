<?php
namespace WordPress\Tools;

function check_php_version( $needed_php_version = '5.3', $message = '' ) {

	// thanks to Inpsyde
	$compared_php_version = version_compare( phpversion(), $needed_php_version, '>=' );

	if ( empty( $message ) ) {

		$message = sprintf(
				"<p>This Plugin requires <strong>PHP %s</strong> or higher.<br>You are running PHP %s</p>",
				$needed_php_version,
				phpversion()
		);

	}

	if ( false == $compared_php_version ) {
		echo $message;
		exit;
	}

}