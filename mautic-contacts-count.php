<?php
/**
 * Plugin Name:       Mautic Conatacts Count
 * Plugin URI:        http://brainstormforce.com
 * Description:       Get All Mautic Contacts Count using simple shortcode [mauticcount]
 * Version:           1.0.0
 * Author:            Brainstorm Force
 * Author URI:        http://brainstormforce.com
 * Text Domain:       mautic-contacts-count
 */
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
add_action( 'admin_init', 'bsf_mautic_cnt_set_code' );
add_action( 'admin_menu', 'bsf_mautic_menu' );
add_action( 'wp_loaded', 'bsf_cnt_authenticate_update' );
add_shortcode( 'mauticcount', 'bsf_mautic_cnt_scode' );

function bsf_mautic_cnt_set_code() {
	if ( isset( $_GET['code'] ) ) {
		$credentials                = get_option( '_bsf_mautic_cnt_credentials' );
		$credentials['access_code'] = esc_attr( $_GET['code'] );
		update_option( '_bsf_mautic_cnt_credentials', $credentials );
		bsf_get_mautic_data();
	}
}

function bsf_mautic_cnt_scode() {
	$mautic_count_trans = get_transient( 'bsf_mautic_contact_count' );

	if ( $mautic_count_trans ) {
		return $mautic_count_trans;
	}

	$method           = "GET";
	$status           = 'success';
	$contacts_details = '';
	$credentials      = get_option( '_bsf_mautic_cnt_credentials' );

	// if token expired, get new access token
	if ( isset( $credentials['expires_in'] ) && $credentials['expires_in'] < time() ) {
		$grant_type = 'refresh_token';
		$response   = bsf_mautic_get_access_token( $grant_type );

		if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
			$errorMsg = $response->get_error_message();
			$status   = 'error';
		} else {
			$access_details               = json_decode( $response['body'] );
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
		$url      = $credentials['baseUrl'] . '/api/contacts?access_token=' . $access_token;
		$response = wp_remote_get( $url );
		if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
			$response_body    = $response['body'];
			$contacts_details = json_decode( $response_body );
		}
	}

	if ( is_wp_error( $response ) ) {
		$errorMsg = $response->get_error_message();
		$status   = 'error';
	} else {
		if ( is_array( $response ) ) {
			$response_code = $response['response']['code'];
			if ( $response_code != 201 ) {
				if ( $response_code != 200 ) {
					$ret      = false;
					$status   = 'error';
					$errorMsg = isset( $response['response']['message'] ) ? $response['response']['message'] : '';
					echo $errorMsg;
				}
			}
		}
	}

	if ( isset( $contacts_details->total ) ) {
		set_transient( 'bsf_mautic_contact_count', $contacts_details->total, DAY_IN_SECONDS );

		return $contacts_details->total;
	} else {

		return _e( 'Something is wrong with mautic authentication. Please authenticate Mautic.', 'mautic-contacts-count' );
	}
}

function bsf_mautic_menu() {
	add_options_page( 'Mautic Contacts Count', 'Mautic Contacts Count', 'manage_options', 'mautic-count', 'bsf_mautic_contact_setting_page' );
}

function bsf_mautic_contact_setting_page() {
	if ( isset( $_POST['bsf-mautic-clr-cnt-nonce'] ) && wp_verify_nonce( $_POST['bsf-mautic-clr-cnt-nonce'], 'bsfclrmauticcnt' ) ) {
		delete_transient( 'bsf_mautic_contact_count' );
	}
	?>
	<h3> Configure Mautic</h3>
	<form id="mautic-config-form" action="<?php echo admin_url( 'options-general.php?page=mautic-count' ); ?>"
	      method="post">
		<?php
		$bsfm          = get_option( '_bsf_mautic_cnt_config' );
		$bsfm_base_url = $bsfm_public_key = $bsfm_secret_key = '';
		if ( is_array( $bsfm ) ) {
			$bsfm_base_url   = ( array_key_exists( 'bsfm-base-url', $bsfm ) ) ? $bsfm['bsfm-base-url'] : '';
			$bsfm_public_key = ( array_key_exists( 'bsfm-public-key', $bsfm ) ) ? $bsfm['bsfm-public-key'] : '';
			$bsfm_secret_key = ( array_key_exists( 'bsfm-secret-key', $bsfm ) ) ? $bsfm['bsfm-secret-key'] : '';
		}
		?>
		<!-- Base Url -->
		<div class="form-setting">
			<h4><?php _e( 'Base URL', 'mautic-contacts-count' ); ?></h4>
			<input type="text" class="regular-text" name="bsfm-base-url" value="<?php echo $bsfm_base_url; ?>"
			       class="mautic-contacts-count-text"/>

			<p class="admin-help">
				<?php _e( 'URL of your Mautic instance you want to connect with. eg. https://*your-name*.mautic.net', 'mautic-contacts-count' ); ?>
			</p>
		</div>
		<!-- Client Public Key -->
		<div class="form-setting">
			<h4><?php _e( 'Public Key', 'mautic-contacts-count' ); ?></h4>
			<input type="text" class="regular-text" name="bsfm-public-key" value="<?php echo $bsfm_public_key; ?>"
			       class="mautic-contacts-count-text"/>
		</div>
		<!-- Client Secret Key -->
		<div class="form-setting">
			<h4><?php _e( 'Secret Key', 'mautic-contacts-count' ); ?></h4>
			<input type="text" class="regular-text" name="bsfm-secret-key" value="<?php echo $bsfm_secret_key; ?>"
			       class="mautic-contacts-count-text"/>
		</div>
		<p class="admin-help">
			<?php _e( 'Need help to get Mautic API credentials? Read <a target="_blank" href="http://docs.sharkz.in/how-to-get-mautic-api-credentials/">this article</a> for more information.', 'mautic-contacts-count' ); ?>
		</p>

		<p class="submit">
			<input type="submit" name="bsfm-save-authenticate" class="button-primary"
			       value="<?php esc_attr_e( 'Save and Authenticate', 'mautic-contacts-count' ); ?>"/>
		</p>
		<?php wp_nonce_field( 'bsfmauticcnt', 'bsf-mautic-cnt-nonce' ); ?></h4>
	</form>
	<p class="admin-help"> <?php _e( 'If shortcode is not displaying correct count, Refresh contacts count.', 'mautic-contacts-count' ); ?> </p>
	<form id="mautic-config-clearcount" action="<?php echo admin_url( 'options-general.php?page=mautic-count' ); ?>"
	      method="post">
		<p class="submit">
			<input type="submit" name="bsfm-refresh-count" class="button-primary"
			       value="<?php esc_attr_e( 'Refresh Contact Count', 'mautic-contacts-count' ); ?>"/>
			<?php wp_nonce_field( 'bsfclrmauticcnt', 'bsf-mautic-clr-cnt-nonce' ); ?>
		</p>
	</form>
	<h4><?php _e( 'Get Mautic Contacts Count using simple shortcode [mauticcount]', 'mautic-contacts-count' );
}

function bsf_mautic_get_access_token( $grant_type ) {
	$credentials = get_option( '_bsf_mautic_cnt_credentials' );

	if ( ! isset( $credentials['baseUrl'] ) ) {

		return;
	}

	$url  = $credentials['baseUrl'] . "/oauth/v2/token";
	$body = array(
		"client_id"     => $credentials['clientKey'],
		"client_secret" => $credentials['clientSecret'],
		"grant_type"    => $grant_type,
		"redirect_uri"  => $credentials['callback'],
		'sslverify'     => false
	);

	if ( $grant_type == 'authorization_code' ) {
		$body["code"] = $credentials['access_code'];
	} else {
		$body["refresh_token"] = $credentials['refresh_token'];
	}

	// Request to get access token 
	$response = wp_remote_post( $url, array(
			'method'      => 'POST',
			'timeout'     => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => array(),
			'body'        => $body,
			'cookies'     => array()
		)
	);

	return $response;
}

function bsf_cnt_authenticate_update() {

	if ( ! isset( $_POST['bsf-mautic-cnt-nonce'] ) ) {

		return;
	}

	if ( isset( $_POST['bsf-mautic-cnt-nonce'] ) && wp_verify_nonce( $_POST['bsf-mautic-cnt-nonce'], 'bsfmauticcnt' ) ) {
		if ( isset( $_POST['bsfm-base-url'] ) ) {
			$bsfm['bsfm-base-url'] = esc_url( $_POST['bsfm-base-url'] );
		}
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
	}

	$mautic_api_url  = $bsfm_public_key = $bsfm_secret_key = '';
	$post            = $_POST;
	$cpts_err        = false;
	$lists           = null;
	$ref_list_id     = null;
	$mautic_api_url  = isset( $post['bsfm-base-url'] ) ? esc_attr( $post['bsfm-base-url'] ) : '';
	$bsfm_public_key = isset( $post['bsfm-public-key'] ) ? esc_attr( $post['bsfm-public-key'] ) : '';
	$bsfm_secret_key = isset( $post['bsfm-secret-key'] ) ? esc_attr( $post['bsfm-secret-key'] ) : '';
	if ( $mautic_api_url == '' ) {
		$status = 'error';
		_e( 'API URL is missing.', 'mautic-contacts-count' );
		$cpts_err = true;
	}
	if ( $bsfm_secret_key == '' ) {
		$status = 'error';
		_e( 'Secret Key is missing.', 'mautic-contacts-count' );
		$cpts_err = true;
	}
	$settings = array(
		'baseUrl'       => $mautic_api_url,
		'version'       => 'OAuth2',
		'clientKey'     => $bsfm_public_key,
		'clientSecret'  => $bsfm_secret_key,
		'callback'      => admin_url( 'options-general.php?page=mautic-count' ),
		'response_type' => 'code'
	);

	update_option( '_bsf_mautic_cnt_credentials', $settings );
	$authurl = $settings['baseUrl'] . '/oauth/v2/authorize';
	//OAuth 2.0
	$authurl .= '?client_id=' . $settings['clientKey'] . '&redirect_uri=' . urlencode( $settings['callback'] );
	$state = md5( time() . mt_rand() );
	$authurl .= '&state=' . $state;
	$authurl .= '&response_type=' . $settings['response_type'];
	wp_redirect( $authurl );
	exit();
}

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
			echo json_encode( $result );
			_e( 'Unable to Connect', 'mautic-contacts-count' );
			exit();
		}
		$expiration                   = time() + $access_details->expires_in;
		$credentials['access_token']  = $access_details->access_token;
		$credentials['expires_in']    = $expiration;
		$credentials['refresh_token'] = $access_details->refresh_token;
		update_option( '_bsf_mautic_cnt_credentials', $credentials );
	}
}