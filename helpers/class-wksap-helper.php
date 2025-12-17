<?php
/**
 * Helper class.
 *
 * @package SAP_Connector_For_WooCommerce
 **/

namespace WKSAP\HELPERS;

// Exit if access directly.
defined( 'ABSPATH' ) || exit();

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP_Helper' ) ) {
	/**
	 * Class WKSAP Helper.
	 */
	class WKSAP_Helper {
		/**
		 * Import SAP check.
		 *
		 * @var $import_sap_check
		 */
		public static $import_sap_check = false;

		/**
		 * Instance variable.
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
			add_action( 'wp_ajax_wksap_delete_generated_log_files', array( $this, 'wksap_delete_generated_log_files' ) );
		}

		/**
		 * Generate Log using WooCommerce logger.
		 *
		 * @param mixed  $object_ex Object type (User, Connection, etc.).
		 * @param string $message Message to log.
		 * @param string $isError Error flag (0 for info, 1 for error).
		 *
		 * @return void
		 */
		public static function wksap_generate_log( $object_ex, $message, $isError = 0 ) {
			// Check if WooCommerce is active.
			if ( ! function_exists( 'wc_get_logger' ) ) {
				return;
			}

			// Get WooCommerce logger instance.
			$logger = wc_get_logger();

			// Determine log source based on object type.
			$log_source = 'wksap-sap-connector';
			switch ( $object_ex ) {
				case 'User':
					$log_source = 'wksap-user';
					break;
				case 'Connection':
					$log_source = 'wksap-connection';
					break;
				default:
					$log_source = 'wksap-general';
					break;
			}

			// Determine log level.
			$log_level = $isError ? 'error' : 'info';

			// Create context array for additional information.
			$context = array(
				'source'      => $log_source,
				'object_type' => $object_ex,
			);

			// Log the message using WooCommerce logger.
			$logger->log( $log_level, $message, $context );
		}

		/**
		 * Alternative method with more specific log levels.
		 *
		 * @param string $object_ex Object type.
		 * @param string $message Message to log.
		 * @param string $level Log level (debug, info, notice, warning, error, critical, alert, emergency).
		 *
		 * @return void
		 */
		public static function wksap_log_with_level( $object_ex, $message, $level = 'info' ) {
			if ( ! function_exists( 'wc_get_logger' ) ) {
				return;
			}

			$logger = wc_get_logger();

			$log_source = 'wksap-sap-connector';
			switch ( $object_ex ) {
				case 'User':
					$log_source = 'wksap-user';
					break;
				case 'Connection':
					$log_source = 'wksap-connection';
					break;
				default:
					$log_source = 'wksap-general';
					break;
			}

			$context = array(
				'source'      => $log_source,
				'object_type' => $object_ex,
			);

			$logger->log( $level, $message, $context );
		}

		/**
		 * Delete old log entries (WooCommerce manages this automatically).
		 * This method is kept for compatibility but WC handles log rotation.
		 *
		 * @return void
		 */
		public static function wksap_delete_generated_log_files() {
			if ( ! function_exists( 'wc_get_logger' ) ) {
				return;
			}

			$log_sources = array( 'wksap-user', 'wksap-connection', 'wksap-general' );

			foreach ( $log_sources as $source ) {
				// WooCommerce will handle the cleanup automatically.
				// But you can log a cleanup event if needed.
				self::wksap_generate_log( esc_html__( 'System', 'sap-connector-for-woocommerce' ),  esc_html__( 'Log cleanup performed for source: ', 'sap-connector-for-woocommerce' ) . $source, 0 );
			}

			if ( wp_doing_ajax() ) {
				wp_send_json_success(
					array(
						'message' => esc_html__( 'Log cleanup completed. WooCommerce manages log rotation automatically.', 'sap-connector-for-woocommerce' ),
					)
				);
			}
		}

		/**
		 * Helper method to log debug information
		 *
		 * @param string $object_ex Object type.
		 * @param string $message Debug message.
		 *
		 * @return void
		 */
		public static function wksap_debug_log( $object_ex, $message ) {
			// Only log debug messages if WP_DEBUG is enabled.
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				self::wksap_log_with_level( $object_ex, $message, 'debug' );
			}
		}

		/**
		 * Helper method to log errors
		 *
		 * @param string $object_ex Object type.
		 * @param string $message Error message.
		 *
		 * @return void
		 */
		public static function wksap_error_log( $object_ex, $message ) {
			self::wksap_log_with_level( $object_ex, $message, 'error' );
		}

		/**
		 * Helper method to log warnings
		 *
		 * @param string $object_ex Object type.
		 * @param string $message Warning message.
		 *
		 * @return void
		 */
		public static function wksap_warning_log( $object_ex, $message ) {
			self::wksap_log_with_level( $object_ex, $message, 'warning' );
		}

		/**
		 * Verify Token.
		 *
		 * @param  mixed $token - License Key shared by Webkul to Client.
		 * @param  mixed $service_layer_url - Service Layer URL of the SAP Business One.
		 *
		 * @return boolean
		 */
		public static function wksap_verify_token( $token, $service_layer_url ) {
			$system_token = self::wksap_generate_token( $service_layer_url );
			if ( ! empty( $token ) && $system_token === $token ) {
				return true;
			}
			return false;
		}

		/**
		 * Generate Token.
		 *
		 * @param mixed $service_layer_url Service Layer URL of the SAP Business One.
		 *
		 * @return mixed - generated token with the help of Server Name and Service Layer URL of the SAP Business One.
		 */
		public static function wksap_generate_token( $service_layer_url ) {
			$wc_host = isset( $_SERVER['SERVER_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '';

			if ( ! empty( $service_layer_url ) ) {
				$pass = md5( $wc_host . $service_layer_url );
				return $pass;
			}
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
