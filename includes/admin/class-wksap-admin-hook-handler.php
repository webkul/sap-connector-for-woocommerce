<?php
/**
 * Admin Hook Handler handler class.
 *
 * @package SAP_Connector_For_WooCommerce
 */

namespace WKSAP\INCLUDES\ADMIN;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP_Admin_Hook_Handler' ) ) {
	/**
	 * WKSAP Admin Hook Handler class.
	 */
	class WKSAP_Admin_Hook_Handler {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Constructor.
		 *
		 * @return void
		 */
		public function __construct() {
			$function_handler = new WKSAP_Admin_Function_Handler();
			add_action( 'admin_menu', array( $function_handler, 'wksap_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $function_handler, 'wksap_admin_enqueue_scripts' ) );
			add_filter( 'set-screen-option', array( $function_handler, 'wksap_set_option' ), 10, 3 );
		}

		/**
		 * Main Instance.
		 *
		 * @return object
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}
}
