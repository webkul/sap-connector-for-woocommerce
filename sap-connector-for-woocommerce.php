<?php
/**
 * Plugin Name: SAP Connector for WooCommerce
 * Plugin URI: https://webkul.com/
 * Description: Connector that lets your SAP Business One Connect to WooCommerce store.
 * Version: 1.0.0
 * Author: Webkul
 * Author URI: http://webkul.com
 * Text Domain: sap-connector-for-woocommerce
 * Domain Path: /languages
 *
 * Requires at least: 6.7
 * Requires PHP: 7.4
 * WC requires at least: 10.0
 * WC tested up to: 10.4
 *
 * SAP Connector for WooCommerce is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * SAP Connector for WooCommerce is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WP Rollback. If not, see <http://www.gnu.org/licenses/>.
 *
 * Requires Plugins: woocommerce
 *
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package SAP_Connector_For_WooCommerce
 **/

// Exit if access directly.
defined( 'ABSPATH' ) || exit();

use WKSAP\INCLUDES;

// Define Constants.
defined( 'WKSAP_FILE_NAME' ) || define( 'WKSAP_FILE_NAME', __FILE__ );
defined( 'WKSAP_PLUGIN_FILE' ) || define( 'WKSAP_PLUGIN_FILE', plugin_dir_path( __FILE__ ) );
defined( 'WKSAP_PLUGIN_BASENAME' ) || define( 'WKSAP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load core auto-loader.
require WKSAP_PLUGIN_FILE . '/includes/class-wksap-connect-autoload.php';

/**
 * Convert object to array.
 *
 * @param object $object_px object.
 *
 * @return array
 */
function wksap_wp_sap_object_to_array( $object_px ) {
	if ( ! is_object( $object_px ) && ! is_array( $object_px ) ) {
		return $object_px;
	}
	if ( is_object( $object_px ) ) {
		$object_px = get_object_vars( $object_px );
	}

	return array_map( 'wksap_wp_sap_object_to_array', $object_px );
}

// Check class exists.
if ( ! class_exists( 'WKSAP' ) ) {
	INCLUDES\WKSAP::get_instance();
}

// Check function exists.
if ( ! function_exists( 'wksap_add_every_fifteen_minutes' ) ) {
	add_filter( 'cron_schedules', 'wksap_add_every_fifteen_minutes' );
	/**
	 * Add custom interval.
	 *
	 * @param array $schedules Schedules.
	 * @return array
	 */
	function wksap_add_every_fifteen_minutes( $schedules ) {
		$schedules['every_fifteen_minutes'] = array(
			'interval' => 900,
			'display'  => esc_html__( 'Every 15 Minutes', 'sap-connector-for-woocommerce' ),
		);
		return $schedules;
	}
}
