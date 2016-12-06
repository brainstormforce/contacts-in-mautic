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
add_shortcode( 'mauticcount', 'bsf_mautic_cnt_scode' );
add_action( 'admin_menu', 'bsf_mautic_menu' );
add_action( 'wp_loaded', 'bsf_cnt_authenticate_update' );
function bsf_mautic_cnt_set_code() {
	if( isset($_GET['code']) ) {
		$credentials = get_option( '_bsf_mautic_cnt_credentials' );
		$credentials['access_code'] =  esc_attr( $_GET['code'] );
		update_option( '_bsf_mautic_cnt_credentials', $credentials );
		bsf_get_mautic_data();
	}
}
function bsf_mautic_cnt_scode() {
		$method = "GET";
		$status = 'success';
		$credentials = get_option( '_bsf_mautic_cnt_credentials' );
			// if token expired, get new access token
			if( isset($credentials['expires_in']) && $credentials['expires_in'] < time() ) {
				$grant_type = 'refresh_token';
				$response = bsf_mautic_get_access_token( $grant_type );

				if ( ! is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) === 200 ) {
				  	$errorMsg = $response->get_error_message();
				    $status = 'error';
				} else {
					$access_details = json_decode( $response['body'] );
					$expiration = time() + $access_details->expires_in;
					$credentials['access_token'] = $access_details->access_token;
					$credentials['expires_in'] = $expiration;
					$credentials['refresh_token'] = $access_details->refresh_token;
					update_option( '_bsf_mautic_cnt_credentials', $credentials );
				}
			} // refresh code token ends

			// add contacts
			$credentials = get_option( '_bsf_mautic_cnt_credentials' );
			$access_token = isset($credentials['access_token'])?$credentials['access_token']:'';

			if( ! empty($access_token) ) {
				$url = $credentials['baseUrl'] .'/api/contacts?access_token='.$access_token;
				$response = wp_remote_get( $url );

				echo "<pre>";
				print_r($response);
				echo "</pre>";


				if( $response->body ) {
					$response_body = $response['body'];
					$contacts_details = json_decode($response_body);
				}
			}
			else {
					return;
			}
			
			if ( is_wp_error( $response ) ) {
				$errorMsg = $response->get_error_message();
				$status = 'error';
			} else {
				if( is_array($response) ) {
					$response_code = $response['response']['code'];
					if( $response_code != 201 ) {
						if( $response_code != 200 ) {
							$ret = false;
							$status = 'error';
							$errorMsg = isset( $response['response']['message'] ) ? $response['response']['message'] : '';
							echo $errorMsg;
						}
					}
				}
			}
	return $contacts_details->total;
}
function bsf_mautic_menu() {
	add_options_page('Mautic Contacts Count', 'Mautic Contacts Count', 'manage_options', 'mautic-count', 'bsf_mautic_contact_setting_page');
}
function bsf_mautic_contact_setting_page() {

	if ( isset( $_POST['bsf-mautic-cnt-nonce'] ) && wp_verify_nonce( $_POST['bsf-mautic-cnt-nonce'], 'bsfmauticcnt' ) ) {
		if( isset( $_POST['bsfm-base-url'] ) ) {	
			$bsfm['bsfm-base-url'] = esc_url( $_POST['bsfm-base-url'] ); 
		}
		if( isset( $_POST['bsfm-public-key'] ) ) {	
			$bsfm['bsfm-public-key'] = sanitize_key( $_POST['bsfm-public-key'] ); 
		}
		if( isset( $_POST['bsfm-secret-key'] ) ) {	
			$bsfm['bsfm-secret-key'] = sanitize_key( $_POST['bsfm-secret-key'] ); 
		}
		// Update the site-wide option since we're in the network admin.
		if ( is_network_admin() ) {
			update_site_option( '_bsf_mautic_cnt_config', $bsfm );
		}
		else {
			update_option( '_bsf_mautic_cnt_config', $bsfm );
		}
		bsf_cnt_authenticate_update();
	}
?>
	<h3> Configure Mautic</h3>
	<form id="mautic-config-form" action="<?php echo admin_url( 'options-general.php?page=mautic-count'); ?>" method="post">
		<?php
			//$bsfm = get_option('_bsf_mautic_config');
			$bsfm = get_option('_bsf_mautic_cnt_config');
			$bsfm_base_url = $bsfm_public_key = $bsfm_secret_key = '';
			if( is_array($bsfm) ) {
				$bsfm_base_url = ( array_key_exists( 'bsfm-base-url', $bsfm ) ) ? $bsfm['bsfm-base-url'] : '';
				$bsfm_public_key = ( array_key_exists( 'bsfm-public-key', $bsfm ) ) ? $bsfm['bsfm-public-key'] : '';
				$bsfm_secret_key = ( array_key_exists( 'bsfm-secret-key', $bsfm ) ) ? $bsfm['bsfm-secret-key'] : '';
			}
		?>
		<!-- Base Url -->
		<div class="form-setting">
			<h4><?php _e( 'Base URL', 'mautic-contacts-count' ); ?></h4>
			<input type="text" class="regular-text" name="bsfm-base-url" value="<?php echo $bsfm_base_url; ?>" class="mautic-contacts-count-text" />
			<p class="admin-help">
				<?php _e('The Base URL is the URL where Mautic is installed.<br>The base URL is something like https://*your-name*.mautic.net', 'mautic-contacts-count'); ?>
			</p>
		</div>
		<!-- Client Public Key -->
		<div class="form-setting">
			<h4><?php _e( 'Public Key', 'mautic-contacts-count' ); ?></h4>
			<input type="text" class="regular-text" name="bsfm-public-key" value="<?php echo $bsfm_public_key; ?>" class="mautic-contacts-count-text" />
		</div>
		<!-- Client Secret Key -->
		<div class="form-setting">
			<h4><?php _e( 'Secret Key', 'mautic-contacts-count' ); ?></h4>	
			<input type="text" class="regular-text" name="bsfm-secret-key" value="<?php echo $bsfm_secret_key; ?>" class="mautic-contacts-count-text" />
		</div>
		<p class="admin-help">
			<?php _e('First Go to Mautic Configuration / API Settings and set ‘API enabled’ to ‘Yes’. Save changes. <br> then go to API Credentials and create public and secret key', 'mautic-contacts-count'); ?>
		</p>
		<p class="submit">
			<input type="submit" name="bsfm-save-authenticate" class="button-primary" value="<?php esc_attr_e( 'Save and Authenticate', 'mautic-contacts-count' ); ?>" />
		</p>
		<h4><?php _e('Get All Mautic Contacts Count using simple shortcode [mauticcount]', 'mautic-contacts-count');
		wp_nonce_field('bsfmauticcnt', 'bsf-mautic-cnt-nonce'); ?></h4>
	</form>
<?php
}
function bsf_mautic_get_access_token($grant_type) {
	$credentials = get_option('_bsf_mautic_cnt_credentials');
	if( ! isset($credentials['baseUrl']) ) return;
	$url = $credentials['baseUrl'] . "/oauth/v2/token";
	$body = array(	
		"client_id" => $credentials['clientKey'],
		"client_secret" => $credentials['clientSecret'],
		"grant_type" => $grant_type,
		"redirect_uri" => $credentials['callback'],
		'sslverify' => false
	);
	if( $grant_type == 'authorization_code' ) {
		$body["code"] = $credentials['access_code'];
	} else {
		$body["refresh_token"] = $credentials['refresh_token'];
	}
	// Request to get access token 
	$response = wp_remote_post( $url, array(
		'method' => 'POST',
		'timeout' => 45,
		'redirection' => 5,
		'httpversion' => '1.0',
		'blocking' => true,
		'headers' => array(),
		'body' => $body,
		'cookies' => array()
	    )	
	);
	return $response;
}
function bsf_cnt_authenticate_update() {
	if ( ! isset( $_POST['bsf-mautic-cnt-nonce'] ) ) {
		return;
	}

	$mautic_api_url = $bsfm_public_key = $bsfm_secret_key = '';
	$post = $_POST;
	$cpts_err = false;
	$lists = null;
	$ref_list_id = null;
	$mautic_api_url = isset( $post['bsfm-base-url'] ) ? esc_attr( $post['bsfm-base-url'] ) : '';
	$bsfm_public_key = isset( $post['bsfm-public-key'] ) ? esc_attr( $post['bsfm-public-key'] ) : '';
	$bsfm_secret_key = isset( $post['bsfm-secret-key'] ) ? esc_attr( $post['bsfm-secret-key'] ) : '';
	if( $mautic_api_url == '' ) {	
		$status = 'error';
		$message = 'API URL is missing.';
		$cpts_err = true;
	}
	if( $bsfm_secret_key == '' ) {
		$status = 'error';
		$message = 'Secret Key is missing.';
		$cpts_err = true;
	}
	$settings = array(
		'baseUrl'      => $mautic_api_url,
		'version'      => 'OAuth2',
		'clientKey'    => $bsfm_public_key,
		'clientSecret' => $bsfm_secret_key, 
		'callback'     => admin_url( 'options-general.php?page=mautic-count'),
		'response_type'=> 'code'
	);
	update_option( '_bsf_mautic_cnt_credentials', $settings );
	$authurl = $settings['baseUrl'] . '/oauth/v2/authorize';
	//OAuth 2.0
	$authurl .= '?client_id='.$settings['clientKey'].'&redirect_uri='.urlencode( $settings['callback'] );
	$state    = md5(time().mt_rand());
	$authurl .= '&state='.$state;
	$authurl .= '&response_type='.$settings['response_type'];
	wp_redirect($authurl);
	exit();
}
function bsf_get_mautic_data() {
	$credentials = get_option( '_bsf_mautic_cnt_credentials' );
	if( ! isset( $credentials['access_code']) ) return;

	if( !isset( $credentials['access_token'] ) ) {

			$grant_type = 'authorization_code';
			
			$response = bsf_mautic_get_access_token( $grant_type );

			$access_details = json_decode( $response['body'] );

			if( isset( $access_details->error ) ) {
				echo json_encode($result);
				exit('unable to connect');
			}
			$expiration = time() + $access_details->expires_in;
			$credentials['access_token'] = $access_details->access_token;
			$credentials['expires_in'] = $expiration;
			$credentials['refresh_token'] = $access_details->refresh_token;
			update_option( '_bsf_mautic_cnt_credentials', $credentials );
	}
}