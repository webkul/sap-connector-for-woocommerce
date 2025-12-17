<?php
/**
 * CONNECTOR File Handler handler.
 *
 * @package SAP_Connector_For_WooCommerce
 */

namespace WKSAP\INCLUDES;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

use WKSAP\INCLUDES\Admin;

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP_File_Handler' ) ) {
	/**
	 * Class WKSAP File Handler.
	 */
	class WKSAP_File_Handler {
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
			$this->wksap_native_files();
		}

		/**
		 * Native files.
		 *
		 * @return void
		 */
		public function wksap_native_files() {
			if ( true === is_admin() ) {
				Admin\WKSAP_Admin_Hook_Handler::get_instance();
			}
			WKSAP_MAIN::instance();
		}

		/**
		 * This is a singleton page, access the single instance just using this method.
		 *
		 * @return object
		 */
		public static function get_instance() {
			if ( ! static::$instance ) {
				static::$instance = new self();
			}
			return static::$instance;
		}
	}
}
