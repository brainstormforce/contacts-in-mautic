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
		 * Class instance.
		 *
		 * @access private
		 * @var $instance Class instance.
		 */
		private static $instance;

		/**
		 * Initiator
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {

			// Plugin Updates.
			add_action( 'init', __CLASS__ . '::init' );
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
 * calling 'get_instance()' method
 */
Bsf_CM_Auto_Update::get_instance();
