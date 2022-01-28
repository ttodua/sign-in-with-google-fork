<?php
/**
 * An assortment of functions for themes to utilize.
 *
 * @since      1.6.0
 *
 * @package    Sign_In_With_Google
 * @subpackage Sign_In_With_Google/includes
 */

/**
 * Get SIWG instance, to do access 'authenticate_user' or any other puglic method of 'plugin_admin'
 *
 * @return void
 */
function siwg_get_instance() {
	return apply_filters( 'siwg_instance', null );
}

/**
 * Output the sign in button.
 *
 * @return void
 */
function siwg_button() {
	echo siwg_get_button();
}

/**
 * Get the button html as a string.
 *
 * @return string
 */
function siwg_get_button() {
	return Sign_In_With_Google_Public::get_signin_button();
}

/**
 * Get the google auth url (for headless usage)
 *
 * @return string
 */
function siwg_get_google_auth_url( $state = [] ) {
	$google_auth = new SIWG_GoogleAuth( get_option( 'siwg_google_client_id' ) );
	return $google_auth->get_google_auth_url( $state );
}
