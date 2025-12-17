<?php
/**
 * Synchronized Process User class.
 *
 * @package SAP_Connector_For_WooCommerce
 **/

namespace WKSAP\INCLUDES;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

use WKSAP\HELPERS;

/**
 * WKSAP Synchronized Process User class.
 */
if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once WP_PLUGIN_DIR . '/woocommerce/includes/libraries/wp-async-request.php';
}

/**
 * WP_Background_Process class.
 */
if ( ! class_exists( 'WP_Background_Process', false ) ) {
	include_once WP_PLUGIN_DIR . '/woocommerce/includes/libraries/wp-background-process.php';
}

/**
 * WKSAP Synchronized Process User class.
 */
class WKSAP_Synchronized_Process_User extends \WP_Background_Process {
	/**
	 * WKSAP Synchronized Process User constructor.
	 *
	 * @var string
	 */
	protected $action = 'export_user_background';

	/**
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $data Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task( $data ) {
		$wooSap = new WKSAP_MAIN();
		if ( ! empty( $data['date_range'] ) && ! empty( $data['option'] ) ) {
			$msg = esc_html__( 'User Sync with ', 'sap-connector-for-woocommerce' ) . $data['option'] . esc_html__( ' Date Range: ', 'sap-connector-for-woocommerce' ) . '(' . implode( '-', $data['date_range'] ) . ')' . esc_html__( ' is Processed Successfully:', 'sap-connector-for-woocommerce' ) . get_option( 'wksap_users_processed' );
			HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $msg, false );
			$result = call_user_func( array( $wooSap, 'wksap_woo_export_user_data' ), $data['itemType'], $data['date_range'], $data['option'] );
		} else {
			$msg = esc_html__( 'User Sync Type: ', 'sap-connector-for-woocommerce' ) . '(' . $data['itemType'] . ')' . esc_html__( ' is Processed Successfully:', 'sap-connector-for-woocommerce' ) . get_option( 'wksap_users_processed' );
			HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $msg, false );
			if ( ! empty( $data['itemType'] ) ) {
				$result = call_user_func( array( $wooSap, 'wksap_woo_export_user_data' ), $data['itemType'] );
			}
		}

		return ! empty( $result ) ? $result : false;
	}

	/**
	 * Complete.
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 *
	 * @return void
	 */
	protected function complete() {
		HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), esc_html__( 'Background Process for User is Completed', 'sap-connector-for-woocommerce' ), false );
		delete_metadata( 'user', 0, 'user_Synced', '', true );
		parent::complete();
	}
}
