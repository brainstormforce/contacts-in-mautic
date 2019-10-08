<?php
/**
 * Plugin auto update class.
 *
 * @package     Contacts in Mautic
 * @author      Brainstormforce
 * @link        http://brainstormforce.com
 * @since       1.0.3
 */

/**
 * Bsf_CM_Auto_Update initial setup
 *
 * @since 1.0.3
 */
class Bsf_CM_Auto_Update {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Plugin Updates.
		add_action( 'admin_init', __CLASS__ . '::init' );
	}

	/**
	 * Implement plugin auto update logic.
	 *
	 * @since 1.0.3
	 * @return void
	 */
	public static function init() {

		// Get auto saved version number.
		$saved_version = get_option( 'bsf_contact_mautic_version' );

		// If the version option not present then just create it.
		if ( false === $saved_version ) {
			add_option( 'bsf_contact_mautic_version', BSF_CONTACT_MAUTIC_VERSION );
		}

		// If equals then return.
		if ( version_compare( $saved_version, BSF_CONTACT_MAUTIC_VERSION, '=' ) ) {
			return;
		}

		// Set the Mautic connection type option.
		$check_option = get_option( 'bsf_mautic_connection_type' );
		if ( false === $check_option ){
			add_option( 'bsf_mautic_connection_type', 'mautic_api' );
		}

		// create options for Mautic username password and mautic connection error message.
		if ( version_compare( $saved_version, BSF_CONTACT_MAUTIC_VERSION, '<' ) ) {

			// Set the Mautic username password credentials option.
			$mautic_up_credentials_option = get_option( '_bsf_mautic_cnt_user_pass_credentials' );
			if ( false === $mautic_up_credentials_option ){
				add_option( '_bsf_mautic_cnt_user_pass_credentials', '' );
			}

			// Set the Mautic connection error message option.
			$mautic_user_pass_error_msg = get_option( 'mautic_user_pass_error_msg' );
			if ( false === $mautic_user_pass_error_msg ){
				add_option( 'mautic_user_pass_error_msg', '' );
			}

		}

		// Update auto saved version number.
		update_option( 'bsf_contact_mautic_version', BSF_CONTACT_MAUTIC_VERSION );
	}

}

/**
 * calling 'Bsf_CM_Auto_Update' Constructor
 */
new Bsf_CM_Auto_Update();