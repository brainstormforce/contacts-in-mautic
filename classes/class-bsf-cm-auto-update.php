<?php
/**
 * Plugin auto update class.
 *
 * @package     Contacts in Mautic
 * @author      Brainstormforce
 * @link        http://brainstormforce.com
 * @since       1.0.3
 */

if ( ! class_exists( 'Bsf_CM_Auto_Update' ) ) :

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

			// Set the Mautic connection type option.
			$check_option = get_option( 'bsf_mautic_connection_type' );
			if ( false === $check_option ){
				add_option( 'bsf_mautic_connection_type', 'mautic_api' );
			}

			// Update auto saved version number.
			update_option( 'bsf_contact_mautic_version', BSF_CONTACT_MAUTIC_VERSION );
		}

}

endif;

/**
 * calling 'Bsf_CM_Auto_Update' Constructor
 */
new Bsf_CM_Auto_Update();