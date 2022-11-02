<?php
/**
 * Plugin Name:       Contacts in Mautic
 * Plugin URI:        http://brainstormforce.com
 * Description:       Get All Mautic Contacts Count using simple shortcode.
 * Version:           1.0.7
 * Author:            Pratik Chaskar
 * Author URI:        https://pratikchaskar.com
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       contacts-in-mautic
 *
 * @package Contacts in Mautic
 */

/**
 * Contacts in Mautic
 * Copyright (C) 2016, http://brainstormforce.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 **/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Set the BSF_CONTACT_MAUTIC_VERSION.
define( 'BSF_CONTACT_MAUTIC_VERSION', '1.0.7' );

// Include the auto update file.
require_once plugin_dir_path( __FILE__ ) . 'classes/class-bsf-cm-auto-update.php';

add_action( 'admin_init', 'bsf_mautic_cnt_set_code' );
add_action( 'admin_menu', 'bsf_mautic_menu' );
add_action( 'wp_loaded', 'bsf_cnt_authenticate_update' );
add_action( 'admin_enqueue_scripts', 'bsf_cnt_load_style' );
add_shortcode( 'mauticcount', 'bsf_mautic_cnt_scode' );

/**
 * Implement mautic contact count.
 *
 * @since  1.0.4
 */
function bsf_mautic_cnt_set_code() {
	$get_mautic_connect_type = get_option( 'bsf_mautic_connection_type' );

	if ( isset( $_REQUEST['bsf_contact_in_mautic_nonce'] ) && wp_verify_nonce( $_REQUEST['bsf_contact_in_mautic_nonce'], 'bsf_contact_in_mautic_nonce_page' ) && isset( $_GET['code'] ) && 'mautic-count' == $_REQUEST['page'] && 'mautic_api' == $get_mautic_connect_type ) {
		$credentials                = get_option( '_bsf_mautic_cnt_credentials' );
		$credentials['access_code'] = esc_attr( $_GET['code'] );

		update_option( '_bsf_mautic_cnt_credentials', $credentials );
		bsf_get_mautic_data();
	}
}

/**
 * Enqueue style and JS files.
 *
 * @since  1.0.4
 */
function bsf_cnt_load_style() {
	if ( isset( $_REQUEST['bsf_contact_in_mautic_nonce'] ) && wp_verify_nonce( $_REQUEST['bsf_contact_in_mautic_nonce'], 'bsf_contact_in_mautic_nonce_page' ) && ( isset( $_REQUEST['page'] ) && 'mautic-count' == $_REQUEST['page'] ) ) {
		wp_enqueue_style( 'bsfm-cnt-admin-style', plugins_url( '/', __FILE__ ) . 'assets/css/bsfm-cnt-admin.css', array(), BSF_CONTACT_MAUTIC_VERSION );

		wp_enqueue_script( 'bsfm-cnt-admin-script', plugins_url( '/', __FILE__ ) . 'assets/js/bsfm-cnt-admin-script.js', array( 'jquery' ), BSF_CONTACT_MAUTIC_VERSION, true );
	}
}

/**
 * Contact in mautic shortcode
 *
 * @since  1.0.4
 * @param array $bsf_atts array mautic.
 */
function bsf_mautic_cnt_scode( $bsf_atts ) {
	$atts = shortcode_atts(
		array(
			'anonymous' => 'off',
		),
		$bsf_atts,
		'mauticcount'
	);

	$mautic_count_trans = get_transient( 'bsf_mautic_contact_count' );

	if ( $mautic_count_trans ) {
		return number_format( $mautic_count_trans );
	}

	$method           = 'GET';
	$status           = 'success';
	$contacts_details = '';

	$get_mautic_connect_type = get_option( 'bsf_mautic_connection_type' );

	// Get Connection type and check.

	if ( 'mautic_user_pass' === $get_mautic_connect_type ) {

		$credentials = get_option( '_bsf_mautic_cnt_credentials' );
		if ( 'on' === $atts['anonymous'] ) {
			$response = bsfm_connect_mautic_username_password( $credentials, 'on' );
		} else {
			$response = bsfm_connect_mautic_username_password( $credentials, 'off' );
		}

		$response_body    = $response['body'];
		$contacts_details = $response_body;

	} else {
		$credentials = get_option( '_bsf_mautic_cnt_credentials' );
		// if token expired, get new access token.
		if ( isset( $credentials['expires_in'] ) && $credentials['expires_in'] < time() ) {
			$grant_type = 'refresh_token';
			$response   = bsf_mautic_get_access_token( $grant_type );

			if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
				$access_details               = wp_remote_retrieve_body( $response );
				$expiration                   = time() + $access_details->expires_in;
				$credentials['access_token']  = $access_details->access_token;
				$credentials['expires_in']    = $expiration;
				$credentials['refresh_token'] = $access_details->refresh_token;
				update_option( '_bsf_mautic_cnt_credentials', $credentials );
			}
		} // refresh code token ends

		$credentials  = get_option( '_bsf_mautic_cnt_credentials' );
		$access_token = isset( $credentials['access_token'] ) ? $credentials['access_token'] : '';
		$response     = '';

		if ( ! empty( $access_token ) ) {

			if ( 'on' === $atts['anonymous'] ) {
				$url = $credentials['baseUrl'] . '/api/contacts?access_token=' . $access_token;
			} else {
				$url = $credentials['baseUrl'] . '/api/contacts?search=!is:anonymous&access_token=' . $access_token;
			}
			$response = wp_remote_get( $url );
			if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				$response_body    = $response['body'];
				$contacts_details = json_decode( $response_body );
			}
		}
	}

	if ( isset( $contacts_details->total ) ) {
		set_transient( 'bsf_mautic_contact_count', $contacts_details->total, WEEK_IN_SECONDS );
		return number_format( $contacts_details->total );
	} else {
		$user = wp_get_current_user();

		if ( in_array( 'administrator', (array) $user->roles ) ) {
			return esc_html_e( 'Something is wrong with mautic authentication. Please authenticate Mautic.', 'contacts-in-mautic' );
		} else {
			$previous_count = get_option( '_bsf_mautic_cnt_config' );
			return number_format( $previous_count['total_count'] );
		}
	}
}

/**
 * Contact in mautic menu
 *
 * @since  1.0.4
 */
function bsf_mautic_menu() {
	$_REQUEST['bsf_contact_in_mautic_nonce'] = wp_create_nonce( 'bsf_contact_in_mautic_nonce_page' );
	add_options_page( 'Contacts in Mautic', __( 'Contacts in Mautic', 'contacts-in-mautic' ), 'manage_options', 'mautic-count', 'bsf_mautic_contact_setting_page' );
}

/**
 * Contact in mautic menu setting page
 *
 * @since  1.0.4
 */
function bsf_mautic_contact_setting_page() {
	if ( isset( $_POST['bsf-mautic-clr-cnt-nonce'] ) && wp_verify_nonce( $_POST['bsf-mautic-clr-cnt-nonce'], 'bsfclrmauticcnt' ) ) {
		delete_transient( 'bsf_mautic_contact_count' );
	}
	?>
	<h3><?php esc_html_e( 'Configure Mautic', 'contacts-in-mautic' ); ?></h3>
	<form id="mautic-cnt-config-form" action="<?php echo esc_url( admin_url( 'options-general.php?page=mautic-count' ) ); ?>" method="post">
	<?php
	if ( isset( $_POST['bsf-mautic-cnt-nonce'] ) && isset( $_POST['bsfm-cnt-disconnect-mautic'] ) ) {
		delete_option( '_bsf_mautic_cnt_credentials' );
		update_option( 'bsf_mautic_connection_type', 'mautic_api' );
	}

	$get_mautic_connect_type = get_option( 'bsf_mautic_connection_type' );

	// Get Connection type and check.

	if ( 'mautic_api' === $get_mautic_connect_type ) {

		$bsfm            = get_option( '_bsf_mautic_cnt_config' );
		$bsfm_base_url   = '';
		$bsfm_public_key = '';
		$bsfm_secret_key = '';
		if ( is_array( $bsfm ) ) {
			$bsfm_base_url   = ( array_key_exists( 'bsfm-base-url', $bsfm ) ) ? $bsfm['bsfm-base-url'] : '';
			$bsfm_public_key = ( array_key_exists( 'bsfm-public-key', $bsfm ) ) ? $bsfm['bsfm-public-key'] : '';
			$bsfm_secret_key = ( array_key_exists( 'bsfm-secret-key', $bsfm ) ) ? $bsfm['bsfm-secret-key'] : '';
		}
	} else {

		$bsfm          = get_option( '_bsf_mautic_cnt_credentials' );
		$bsfm_base_url = '';
		$bsfm_username = '';
		$bsfm_password = '';
		if ( is_array( $bsfm ) ) {
			$bsfm_base_url = ( array_key_exists( 'bsfm-base-url', $bsfm ) ) ? $bsfm['bsfm-base-url'] : '';
			$bsfm_username = ( array_key_exists( 'bsfm-username', $bsfm ) ) ? $bsfm['bsfm-username'] : '';
			$bsfm_password = ( array_key_exists( 'bsfm-password', $bsfm ) ) ? $bsfm['bsfm-password'] : '';
		}
	}
	?>

		<!-- Base Url -->
		<div class="form-setting">
			<h4><?php esc_html_e( 'Base URL', 'contacts-in-mautic' ); ?></h4>
			<input type="text" class="regular-text" name="bsfm-base-url" value="<?php echo esc_attr( $bsfm_base_url ); ?>" class="contacts-in-mautic-text"/>

			<p class="admin-help">
				<?php esc_html_e( 'URL of your Mautic instance you want to connect with. eg. https://*your-name*.mautic.net', 'contacts-in-mautic' ); ?>
			</p>
		</div>

		<!-- Mautic Username and Password Error Section -->

	<?php
	$flag                       = false;
	$mautic_user_pass_error_msg = get_option( 'mautic_user_pass_error_msg' );

	if ( ! empty( $mautic_user_pass_error_msg ) || '' !== $mautic_user_pass_error_msg ) {
		$flag = true;
		?>
		<!-- Mautic Username and Password error message -->
		<div class="warning notice notice-error is-dismissible">
			<p>
		<?php
		/* translators: %s: Error message. */
		sprintf( __( 'Error: %s', 'contacts-in-mautic' ), $mautic_user_pass_error_msg );
		?>
			</p>
		</div>

	<?php } ?>

	<?php
	if ( 'mautic_api' === $get_mautic_connect_type && ( ! get_option( '_bsf_mautic_cnt_credentials' ) || get_option( '_bsf_mautic_cnt_credentials' ) ) ) {
		$flag        = true;
		$credentials = get_option( '_bsf_mautic_cnt_credentials' );
		if ( isset( $credentials['access_token'] ) ) {
			$flag = false;
		}
	} elseif ( 'mautic_api' !== $get_mautic_connect_type && ( ! empty( $mautic_user_pass_error_msg ) || '' !== $mautic_user_pass_error_msg ) ) {
		$flag = true;
	}

	if ( $flag ) {

		?>

		<!-- Select the type of connection -->
		<div class="form-setting">
			<h4><?php esc_html_e( 'Type of Connection', 'contacts-in-mautic' ); ?></h4>
			<select name="bsfm_mautic_type" class="bsfm-mautic-type">
				<option value="mautic_api">Mautic API</option>
				<option value="mautic_user_pass">Mautic Username and Password</option>
			</select>
		</div>

		<!-- Mautic Username -->
		<div class="form-setting contacts-in-mautic-text-bsfm-username" style="display: none;">
			<h4><?php esc_html_e( 'Mautic Username', 'contacts-in-mautic' ); ?></h4>
			<input type="text" class="regular-text" name="bsfm-username" value=""
			class="contacts-in-mautic-text"/>
		</div>


		<!-- Mautic Password -->
		<div class="form-setting contacts-in-mautic-text-bsfm-password" style="display: none;">
			<h4><?php esc_html_e( 'Mautic Password', 'contacts-in-mautic' ); ?></h4>
			<input type="password" class="regular-text" name="bsfm-password" value=""
			class="contacts-in-mautic-text"/>
		</div>

	<?php } ?>


	<?php

	if ( 'mautic_api' === $get_mautic_connect_type || ! empty( $mautic_user_pass_error_msg ) ) {

		$credentials = get_option( '_bsf_mautic_cnt_credentials' );
		$expires_in  = isset( $credentials['expires_in'] ) ? $credentials['expires_in'] : '';
		if ( ! isset( $credentials['access_token'] ) ) {
			?>

				<!-- Client Public Key -->
				<div class="form-setting contacts-in-mautic-text-bsfm-public-key">
					<h4><?php esc_html_e( 'Public Key', 'contacts-in-mautic' ); ?></h4>
					<input type="text" class="regular-text" name="bsfm-public-key" value=""
					class="contacts-in-mautic-text"/>
				</div>
				<!-- Client Secret Key -->
				<div class="form-setting contacts-in-mautic-text-bsfm-secret-key">
					<h4><?php esc_html_e( 'Secret Key', 'contacts-in-mautic' ); ?></h4>
					<input type="text" class="regular-text" name="bsfm-secret-key" value=""
					class="contacts-in-mautic-text"/>
				</div>
				<p class="admin-help">
			<?php esc_html_e( 'Need help to get Mautic API credentials? Read <a target="_blank" href="https://docs.brainstormforce.com/how-to-get-mautic-api-credentials/">this article</a> for more information.', 'contacts-in-mautic' ); ?>
				</p>

				<p class="submit">
					<input type="submit" name="bsfm-save-authenticate" class="button-primary"
					value="<?php esc_attr_e( 'Save and Authenticate', 'contacts-in-mautic' ); ?>"/>
					<span class="bsf-mautic-status-disconnect"> <?php esc_html_e( 'Not Connected', 'contacts-in-mautic' ); ?> </span>
				</p>
			<?php
		}
	}

	// If not authorized.
	if ( ! $flag && ( get_option( '_bsf_mautic_cnt_credentials' ) || isset( $credentials['access_token'] ) ) ) {
		?>
		<p class="submit">
			<span class="bsf-mautic-status-connected" style="background-color: #1baf1b;color: #fff;padding: 3px 6px;margin-right: 2em;"> <?php esc_html_e( 'Connected', 'contacts-in-mautic' ); ?> </span>
			<input type="submit" name="bsfm-cnt-disconnect-mautic" class="button" value="<?php esc_attr_e( 'Disconnect Mautic', 'contacts-in-mautic' ); ?>" />
		</p>
		<?php
	}
	wp_nonce_field( 'bsfmauticcnt', 'bsf-mautic-cnt-nonce' );
	?>
	</form>
	<p class="admin-help"> <?php esc_html_e( 'If shortcode is not displaying correct count, Refresh Mautic Contacts count.', 'contacts-in-mautic' ); ?> </p>
	<form id="mautic-config-clearcount" action="<?php echo esc_url( admin_url( 'options-general.php?page=mautic-count' ) ); ?>" method="post">
		<p class="submit">
			<input type="submit" name="bsfm-refresh-count" class="button-primary" value="<?php esc_attr_e( 'Refresh Contact Count', 'contacts-in-mautic' ); ?>"/>
	<?php wp_nonce_field( 'bsfclrmauticcnt', 'bsf-mautic-clr-cnt-nonce' ); ?>
		</p>
	</form>
	<h4>
	<?php
	esc_html_e( 'Get Mautic Contacts Count using simple shortcode [mauticcount]', 'contacts-in-mautic' );
}

/**
 * Authenticate Mautic access token.
 *
 * @since  1.0.4
 * @param string $grant_type granting type.
 */
function bsf_mautic_get_access_token( $grant_type ) {
	$credentials = get_option( '_bsf_mautic_cnt_credentials' );

	if ( ! isset( $credentials['baseUrl'] ) ) {
		return;
	}

	$url  = $credentials['baseUrl'] . '/oauth/v2/token';
	$body = array(
		'client_id'     => $credentials['clientKey'],
		'client_secret' => $credentials['clientSecret'],
		'grant_type'    => $grant_type,
		'redirect_uri'  => $credentials['callback'],
		'sslverify'     => false,
	);

	if ( 'authorization_code' == $grant_type ) {
		$body['code'] = $credentials['access_code'];
	} else {
		$body['refresh_token'] = $credentials['refresh_token'];
	}

	// Request to get access token.
	$response = wp_remote_post(
		$url,
		array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $body,
			'cookies'     => array(),
		)
	);

	return $response;
}

/**
 * Authenticate and update Mautic connection
 *
 * @since  1.0.4
 */
function bsf_cnt_authenticate_update() {
	if ( ! isset( $_POST['bsf-mautic-cnt-nonce'] ) || isset( $_POST['bsfm-cnt-disconnect-mautic'] ) ) {
		return;
	}

	if ( isset( $_POST['bsf-mautic-cnt-nonce'] ) && wp_verify_nonce( $_POST['bsf-mautic-cnt-nonce'], 'bsfmauticcnt' ) ) {
		if ( isset( $_POST['bsfm-base-url'] ) ) {
			$mautic_base_url       = $_POST['bsfm-base-url'];
			$mautic_base_url       = rtrim( $mautic_base_url, '/' );
			$bsfm['bsfm-base-url'] = esc_url( $mautic_base_url );
		}

		if ( isset( $_POST['bsfm_mautic_type'] ) && ( 'mautic_user_pass' === $_POST['bsfm_mautic_type'] ) ) {

			if ( isset( $_POST['bsfm-username'] ) ) {
				$bsfm['bsfm-username'] = sanitize_user( $_POST['bsfm-username'] );
			}
			if ( isset( $_POST['bsfm-password'] ) ) {
				$bsfm['bsfm-password'] = $_POST['bsfm-password'];
			}

			$connect = bsfm_connect_mautic_username_password( $bsfm, 'off' );

			if ( '' !== $connect['error'] || ! empty( $connect['error'] ) ) {
				update_option( 'mautic_user_pass_error_msg', $connect['error'] );
			} else {
				update_option( 'mautic_user_pass_error_msg', $connect['error'] );
				$bsfm['total_count'] = $connect['body']->total;
				update_option( 'bsf_mautic_connection_type', 'mautic_user_pass' );
				update_option( '_bsf_mautic_cnt_credentials', $bsfm );
				update_option( '_bsf_mautic_cnt_config', $bsfm );
			}
		} else {

			if ( isset( $_POST['bsfm-public-key'] ) ) {
				$bsfm['bsfm-public-key'] = sanitize_key( $_POST['bsfm-public-key'] );
			}
			if ( isset( $_POST['bsfm-secret-key'] ) ) {
				$bsfm['bsfm-secret-key'] = sanitize_key( $_POST['bsfm-secret-key'] );
			}
			// Update the site-wide option since we're in the network admin.
			if ( is_network_admin() ) {
				update_site_option( '_bsf_mautic_cnt_config', $bsfm );
			} else {
				update_option( '_bsf_mautic_cnt_config', $bsfm );
			}
			$mautic_api_url  = '';
			$bsfm_public_key = '';
			$bsfm_secret_key = '';
			$post            = $_POST;
			$cpts_err        = false;
			$lists           = null;
			$ref_list_id     = null;
			$mautic_api_url  = isset( $post['bsfm-base-url'] ) ? esc_attr( $post['bsfm-base-url'] ) : '';
			$bsfm_public_key = isset( $post['bsfm-public-key'] ) ? esc_attr( $post['bsfm-public-key'] ) : '';
			$bsfm_secret_key = isset( $post['bsfm-secret-key'] ) ? esc_attr( $post['bsfm-secret-key'] ) : '';
			$mautic_api_url  = rtrim( $mautic_api_url, '/' );
			if ( '' == $mautic_api_url ) {
				$status = 'error';
				esc_html_e( 'API URL is missing.', 'contacts-in-mautic' );
				$cpts_err = true;
			}
			if ( '' == $bsfm_secret_key ) {
				$status = 'error';
				esc_html_e( 'Secret Key is missing.', 'contacts-in-mautic' );
				$cpts_err = true;
			}
			$settings = array(
				'baseUrl'       => $mautic_api_url,
				'version'       => 'OAuth2',
				'clientKey'     => $bsfm_public_key,
				'clientSecret'  => $bsfm_secret_key,
				'callback'      => admin_url( 'options-general.php?page=mautic-count' ),
				'response_type' => 'code',
			);

			update_option( '_bsf_mautic_cnt_credentials', $settings );
			update_option( 'bsf_mautic_connection_type', 'mautic_api' );
			update_option( 'mautic_user_pass_error_msg', '' );
			$authurl = $settings['baseUrl'] . '/oauth/v2/authorize';
			// OAuth 2.0.
			$authurl .= '?client_id=' . $settings['clientKey'] . '&redirect_uri=' . rawurlencode( $settings['callback'] );
			$state    = md5( time() . wp_rand() );
			$authurl .= '&state=' . $state;
			$authurl .= '&response_type=' . $settings['response_type'];

			wp_safe_redirect( $authurl );
			exit();
		}
	}

}

/**
 * Get Mautic data and update
 *
 * @since  1.0.4
 */
function bsf_get_mautic_data() {
	$credentials = get_option( '_bsf_mautic_cnt_credentials' );

	if ( ! isset( $credentials['access_code'] ) ) {
		return;
	}

	if ( ! isset( $credentials['access_token'] ) ) {
		$grant_type = 'authorization_code';

		$response = bsf_mautic_get_access_token( $grant_type );

		$access_details = json_decode( $response['body'] );

		if ( isset( $access_details->error ) ) {
			esc_html_e( 'Unable to Connect', 'contacts-in-mautic' );
			exit();
		}
		$expiration                   = time() + $access_details->expires_in;
		$credentials['access_token']  = $access_details->access_token;
		$credentials['expires_in']    = $expiration;
		$credentials['refresh_token'] = $access_details->refresh_token;
		update_option( '_bsf_mautic_cnt_credentials', $credentials );
	}
}

/**
 * Connect BSF Mautic with Username and Password
 *
 * @param  array $data form data.
 * @param  array $anonymous form anonymous.
 * @return array
 * @since  1.0.4
 */
function bsfm_connect_mautic_username_password( $data, $anonymous ) {
	$mautic_response = array(
		'error' => '',
		'body'  => '',
	);

	$mautic_base_url = $data['bsfm-base-url'];
	$mautic_username = $data['bsfm-username'];
	$mautic_password = wp_unslash( $data['bsfm-password'] );

	$auth_key = base64_encode( $mautic_username . ':' . $mautic_password ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

	$params = array(
		'timeout'     => 30,
		'httpversion' => '1.1',
		'headers'     => array(
			'Authorization' => 'Basic ' . $auth_key,
		),
	);

	if ( 'on' === $anonymous ) {
		$request = $mautic_base_url . '/api/contacts';
	} else {
		$request = $mautic_base_url . '/api/contacts?search=!is:anonymous';
	}
	$response = wp_remote_get( $request, $params );

	if ( is_wp_error( $response ) ) {
		$mautic_response['error'] = __( 'There appears to be an error with the configuration.', 'contacts-in-mautic' );
		return $mautic_response;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ) );

	if ( isset( $body->errors ) ) {

		if ( 404 == $body->errors[0]->code ) {
			/* translators: %s Error Message */
			$mautic_response['error'] = sprintf( __( '404 error. This sometimes happens when you\'ve just enabled the API, and your cache needs to be rebuilt. See <a href="https://mautic.org/docs/en/tips/troubleshooting.html" target="_blank">here for more info</a> - %s', 'contacts-in-mautic' ), $body->errors[0]->message );

			return $mautic_response;

		} elseif ( 403 == $body->errors[0]->code ) {
			/* translators: %s Error Message */
			$mautic_response['error'] = sprintf( __( '403 error. You need to enable the API from within Mautic\'s configuration settings to connect. - %s', 'contacts-in-mautic' ), $body->errors[0]->message );

			return $mautic_response;

		} else {
			/* translators: %s Error Message */
			$mautic_response['error'] = sprintf( __( '%1$s - %2$s', 'contacts-in-mautic' ), $body->errors[0]->code, $body->errors[0]->message );

			return $mautic_response;
		}
	}
	$mautic_response['body'] = $body;

	return $mautic_response;
}
