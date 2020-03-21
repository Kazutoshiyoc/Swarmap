<?php
	$consumer_key = '';
	$consumer_secret = '';
	$redirect_uri = 'http://example.com/app.php';

	header( 'Location: https://foursquare.com/oauth2/authenticate?client_id=' . $consumer_key . '&response_type=code&redirect_uri=' . rawurlencode( $redirect_uri ) );
	exit;
?>
