<?php
/**
 * Admin Function Handler class.
 *
 * @package SAP_Connector_For_WooCommerce
 */

namespace WKSAP\INCLUDES\ADMIN;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

use WKSAP\TEMPLATES\ADMIN;

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP_Admin_Function_Handler' ) ) {
	/**
	 * WKSAP Admin Function Handler class.
	 */
	class WKSAP_Admin_Function_Handler {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Adds a settings page link to a menu.
		 *
		 * @param string $status $status.
		 * @param string $option $option.
		 * @param string $value $value.
		 *
		 * @return mixed
		 */
		public static function wksap_set_option( $status, $option, $value ) {
			if ( 'wksap_items_per_page' === $option ) {
				return $value;
			}

			return $status;
		}

		/**
		 * Add menu items.
		 *
		 * @return void
		 */
		public function wksap_admin_menu() {
			add_menu_page(
				esc_html__( 'SAP Connector', 'sap-connector-for-woocommerce' ),
				esc_html__( 'SAP Connector', 'sap-connector-for-woocommerce' ),
				'manage_options',
				'wksap_connector_settings',
				'',
				'dashicons-admin-plugins',
				50
			);

			add_submenu_page(
				'wksap_connector_settings',
				esc_html__( 'Configuration', 'sap-connector-for-woocommerce' ),
				esc_html__( 'Configuration', 'sap-connector-for-woocommerce' ),
				'manage_options',
				'wksap_connector_settings',
				array( $this, 'wksap_settings' )
			);

			if ( get_option( 'wksap_config', false ) ) {
				$hook1 = add_submenu_page(
					'wksap_connector_settings',
					esc_html__( 'Synchronize Users', 'sap-connector-for-woocommerce' ),
					esc_html__( 'Synchronize Users', 'sap-connector-for-woocommerce' ),
					'manage_options',
					'wksap_sync_user',
					array( $this, 'wksap_user_ex' )
				);
				add_action( 'load-' . $hook1, array( $this, 'wksap_sf_add_user_option' ), 100 );

				add_submenu_page(
					'wksap_connector_settings',
					esc_html__( 'Synchronize Categories', 'sap-connector-for-woocommerce' ),
					esc_html__( 'Synchronize Categories', 'sap-connector-for-woocommerce' ) . ' <span class="wksap-pro-version">' . esc_html__( 'full version only', 'sap-connector-for-woocommerce' ) . ' </span>',
					'manage_options',
					'wksap_sync_cat',
					array( $this, 'wksap_woo_sap_categories' )
				);
				add_submenu_page(
					'wksap_connector_settings',
					esc_html__( 'Synchronize Products', 'sap-connector-for-woocommerce' ),
					esc_html__( 'Synchronize Products', 'sap-connector-for-woocommerce' ) . ' <span class="wksap-pro-version">' . esc_html__( 'full version only', 'sap-connector-for-woocommerce' ) . '</span>',
					'manage_options',
					'wksap_sync_prod',
					array( $this, 'wksap_products_ex' )
				);
				add_submenu_page(
					'wksap_connector_settings',
					esc_html__( 'Synchronize Orders', 'sap-connector-for-woocommerce' ),
					esc_html__( 'Synchronize Orders', 'sap-connector-for-woocommerce' ) . ' <span class="wksap-pro-version">' . esc_html__( 'full version only', 'sap-connector-for-woocommerce' ) . '</span>',
					'manage_options',
					'wksap_sync_order',
					array( $this, 'wksap_order_ex' )
				);
			}
		}

		/**
		 * Settings page.
		 *
		 * @return void
		 */
		public static function wksap_settings() {
			$template_call = Admin\WKSAP_Settings_View::get_instance();
			$template_call->wksap_display();
		}

		/**
		 * Categories page.
		 *
		 * @return void
		 */
		public static function wksap_woo_sap_categories() {
			$template_call = Admin\WKSAP_Categories_View::get_instance();
			$template_call->wksap_display();
		}

		/**
		 * Products page.
		 *
		 * @return void
		 */
		public static function wksap_products_ex() {
			$template_call = Admin\WKSAP_Products_View::get_instance();
			$template_call->wksap_display();
		}

		/**
		 * Orders page.
		 *
		 * @return void
		 */
		public static function wksap_order_ex() {
			$template_call = Admin\WKSAP_Orders_View::get_instance();
			$template_call->wksap_display();
		}

		/**
		 * Users page.
		 *
		 * @return void
		 */
		public static function wksap_user_ex() {
			$template_call = Admin\WKSAP_Users_View::get_instance();
			$template_call->wksap_display();
		}

		/**
		 * Add user option.
		 *
		 * @return void
		 */
		public static function wksap_sf_add_user_option() {
			$option = 'per_page';
			$args   = array(
				'label'   => esc_html__( 'Items Per Page', 'sap-connector-for-woocommerce' ),
				'default' => 15,
				'option'  => 'wksap_items_per_page',
			);
			add_screen_option( $option, $args );
		}

		/**
		 * Enqueue scripts.
		 *
		 * @return void
		 */
		public static function wksap_admin_enqueue_scripts() {
			wp_enqueue_script( 'wksap-ajax-js-script', WKSAP_SAP_PLUGIN_URL . 'assets/dist/js/wksap-js-script.min.js', array( 'jquery' ), WKSAP_SCRIPT_VERSION, true );
			wp_enqueue_script( 'wksap-ajax-script', WKSAP_PLUGIN_URL . '/woocommerce/assets/js/jquery-blockui/jquery.blockUI.min.js', array(), 2.7, true );
			wp_register_style( 'wksap-css', WKSAP_SAP_PLUGIN_URL . 'assets/dist/css/style.min.css', array(), WKSAP_SCRIPT_VERSION, 'all' );
			wp_enqueue_style( 'wksap-css' );
			wp_enqueue_script( 'wksap-sweetalert2', WKSAP_SAP_PLUGIN_URL . 'assets/dist/js/sweetalert2.min.js', array(), WKSAP_SCRIPT_VERSION, true );
			wp_enqueue_script( 'wksap-datepicker-js', WKSAP_SAP_PLUGIN_URL . 'assets/dist/js/datepicker.min.js', array(), WKSAP_SCRIPT_VERSION, true );
			wp_enqueue_style( 'wksap-datepicker-css', WKSAP_SAP_PLUGIN_URL . 'assets/dist/css/datepicker.min.css', array(), WKSAP_SCRIPT_VERSION );

			$wksap_user_config = get_option( 'wksap_user_config', array() );
			$wksap_user_config = ! empty( $wksap_user_config->wksap_sync_user );
			$admin_ajax_object = array(
				'wksap_ajax'   => admin_url( 'admin-ajax.php' ),
				'wksap_nonce'  => wp_create_nonce( 'wksap_nonce' ),
				'wksap_enable' => $wksap_user_config,
				'translation'  => array(
					'confirmationMessage'          => esc_html__( 'It looks like you have been synchronizing.', 'sap-connector-for-woocommerce' ),
					'confirmationMessage2'         => esc_html__( 'If you leave before completion, some of the data might not be saved.', 'sap-connector-for-woocommerce' ),
					'want_continue'                => esc_html__( 'Want to Continue?', 'sap-connector-for-woocommerce' ),
					'are_you_sure'                 => esc_html__( 'Are you sure?', 'sap-connector-for-woocommerce' ),
					'you_want_sync'                => esc_html__( 'You want to Synchronize User', 'sap-connector-for-woocommerce' ),
					'sync_it'                      => esc_html__( 'Yes, Sync it!', 'sap-connector-for-woocommerce' ),
					'export_start'                 => esc_html__( 'User(s) Export Started..', 'sap-connector-for-woocommerce' ),
					'cannot_start'                 => esc_html__( 'Cannot start new Sync process', 'sap-connector-for-woocommerce' ),
					'another_is_going'             => esc_html__( 'While another is going on', 'sap-connector-for-woocommerce' ),
					'export_option'                => esc_html__( 'Select Export Filer Option', 'sap-connector-for-woocommerce' ),
					'date_created'                 => esc_html__( 'Date Created', 'sap-connector-for-woocommerce' ),
					'export_all'                   => esc_html__( 'Export all', 'sap-connector-for-woocommerce' ),
					'export_basis'                 => esc_html__( 'Export on Basis of', 'sap-connector-for-woocommerce' ),
					'next'                         => esc_html__( 'Next', 'sap-connector-for-woocommerce' ),
					'select_option'                => esc_html__( 'Please Select an Option', 'sap-connector-for-woocommerce' ),
					'order_create_between'         => esc_html__( 'User`s Created Between', 'sap-connector-for-woocommerce' ),
					'order_modified_between'       => esc_html__( 'User`s Modified Between', 'sap-connector-for-woocommerce' ),
					'pick_date_range'              => esc_html__( 'Pick date rage', 'sap-connector-for-woocommerce' ),
					'back'                         => esc_html__( 'Back', 'sap-connector-for-woocommerce' ),
					'confirm'                      => esc_html__( 'Confirm', 'sap-connector-for-woocommerce' ),
					'cancel'                       => esc_html__( 'Cancel', 'sap-connector-for-woocommerce' ),
					'invalid_date'                 => esc_html__( 'Invalid date selection', 'sap-connector-for-woocommerce' ),
					'before_date'                  => esc_html__( 'From date should be before To date', 'sap-connector-for-woocommerce' ),
					'last_seven_days'              => esc_html__( 'Are you Sure you want to Delete logs older than last seven days?', 'sap-connector-for-woocommerce' ),
					'are_you_sure_delete_log_file' => esc_html__( 'Are you sure you want to dismiss this block. Import/export is going on..', 'sap-connector-for-woocommerce' ),
					'are_you_sure_export_user'     => esc_html__( 'You want to perform this action. There is already import/export going on', 'sap-connector-for-woocommerce' ),
					'select_user'                  => esc_html__( 'Please Select User!', 'sap-connector-for-woocommerce' ),
					'sap_partner'                  => esc_html__( 'To Sync the record at SAP end of Business Partners', 'sap-connector-for-woocommerce' ),
					'select_category'              => esc_html__( 'Please Select Category!', 'sap-connector-for-woocommerce' ),
					'sap_group'                    => esc_html__( 'To Sync the record at SAP end of Item Groups', 'sap-connector-for-woocommerce' ),
					'category_export_start'        => esc_html__( 'Category(ies) Export Started..', 'sap-connector-for-woocommerce' ),
					'order_export'                 => esc_html__( 'Order(s) Export Started..', 'sap-connector-for-woocommerce' ),
					'select_order'                 => esc_html__( 'Please Select Orders!', 'sap-connector-for-woocommerce' ),
					'sap_sales_order'              => esc_html__( 'To Sync the record at SAP Sales Order end', 'sap-connector-for-woocommerce' ),
					'product_export_start'         => esc_html__( 'Product(s) Export Started..', 'sap-connector-for-woocommerce' ),
					'product_select'               => esc_html__( 'Please Select Products!', 'sap-connector-for-woocommerce' ),
					'sap_end_item'                 => esc_html__( 'To Sync the record at SAP end of Items', 'sap-connector-for-woocommerce' ),
					'records_already'              => esc_html__( 'Records have already been queued', 'sap-connector-for-woocommerce' ),
					'records_already_stop'         => esc_html__( 'Do you want to stop the background job?', 'sap-connector-for-woocommerce' ),
					'stop_it'                      => esc_html__( 'Yes, Stop it!', 'sap-connector-for-woocommerce' ),
					'are_you_sure_import_category' => esc_html__( 'You want to import all item groups(Categories)', 'sap-connector-for-woocommerce' ),
					'yes_import'                   => esc_html__( 'Yes, Import it!', 'sap-connector-for-woocommerce' ),
					'are_you_sure_going'           => esc_html__( 'Import/Export going on.Are you sure you want to perform this action.?', 'sap-connector-for-woocommerce' ),
					'are_you_sure_going_import'    => esc_html__( 'Import/Export going on. Are you sure you want to perform this action?', 'sap-connector-for-woocommerce' ),
					'order_export_stated'          => esc_html__( '"Order(s) Export Started..', 'sap-connector-for-woocommerce' ),
					'error_ex'                     => esc_html__( 'Error', 'sap-connector-for-woocommerce' ),
					'error'                        => esc_html__( 'User Sync Disabled in Settings', 'sap-connector-for-woocommerce' ),
					'enable_plugin'                => esc_html__( ' User Sync Disabled in Settings all actions runs but not perform...', 'sap-connector-for-woocommerce' ),
					'dismiss_notice'               => esc_html__( 'Dismiss this notice.', 'sap-connector-for-woocommerce' ),
					'completed'                    => esc_html__( 'Completed', 'sap-connector-for-woocommerce' ),
					'product_import_start'         => esc_html__( 'Product(s) Import Started..', 'sap-connector-for-woocommerce' ),
					'category_import_start'        => esc_html__( 'Category(ies) Import Started..', 'sap-connector-for-woocommerce' ),
					'error_occur'                  => esc_html__( 'Error Occurred', 'sap-connector-for-woocommerce' ),
					'background_job'               => esc_html__( 'Background Job!', 'sap-connector-for-woocommerce' ),
					'select_unlink'                => esc_html__( 'Selected For Unlink', 'sap-connector-for-woocommerce' ),
					'select_export'                => esc_html__( 'Selected For Export', 'sap-connector-for-woocommerce' ),
					'select_import'                => esc_html__( 'Selected For Import', 'sap-connector-for-woocommerce' ),
					'no'                           => esc_html__( 'No ', 'sap-connector-for-woocommerce' ),
					'warning'                      => esc_html__( 'Alert', 'sap-connector-for-woocommerce' ),
					'selected_unlink_mapping'      => esc_html__( 'Selected Mapping Record has been Unlinked.', 'sap-connector-for-woocommerce' ),
					'all_mapping_unlink'           => esc_html__( 'All Mapping Record has been Unlinked', 'sap-connector-for-woocommerce' ),
					'no_mapping_available'         => esc_html__( 'No Mapping Available', 'sap-connector-for-woocommerce' ),
					'success'                      => esc_html__( 'Success', 'sap-connector-for-woocommerce' ),
					'all_unlinked'                 => esc_html__( 'All items have been unlinked.', 'sap-connector-for-woocommerce' ),
					'no_records_mapping'           => esc_html__( 'No records are available to unlink', 'sap-connector-for-woocommerce' ),
					'unlinked'                     => esc_html__( 'Unlinked!', 'sap-connector-for-woocommerce' ),
					'time_elapsed'                 => esc_html__( 'Time Elapsed:', 'sap-connector-for-woocommerce' ),
					'fetch_logs'                   => esc_html__( 'Fetching total records...', 'sap-connector-for-woocommerce' ),
					''                             => esc_html__( 'Importing products might create duplicates in WooCommerce if post id is not populated in SAP. Continue?', 'sap-connector-for-woocommerce' ),
					'username'                     => esc_html__( 'UserName can`t be blank', 'sap-connector-for-woocommerce' ),
					'password'                     => esc_html__( 'Password can`t be blank', 'sap-connector-for-woocommerce' ),
					'company'                      => esc_html__( 'Company DB can`t be blank', 'sap-connector-for-woocommerce' ),
					'service'                      => esc_html__( 'Service Layer API  URL  can`t be blank', 'sap-connector-for-woocommerce' ),
					'sap_prefix'                   => esc_html__( 'Please enter the SAP Prefix', 'sap-connector-for-woocommerce' ),
					'licence_key'                  => esc_html__( 'Please enter the License Key which is provided by Webkul', 'sap-connector-for-woocommerce' ),
					'prefix_3'                     => esc_html__( 'Prefix length not be greater than 3', 'sap-connector-for-woocommerce' ),
					'prefix_2'                     => esc_html__( 'Prefix length must be 3', 'sap-connector-for-woocommerce' ),
					'finding'                      => esc_html__( 'Please wait export going on..', 'sap-connector-for-woocommerce' ),
					'wait'                         => esc_html__( 'Please wait export going on..', 'sap-connector-for-woocommerce' ),
				),
			);

			wp_localize_script(
				'wksap-ajax-script',
				'wksap_ajax_object',
				$admin_ajax_object
			);
		}

		/**
		 * Main Instance.
		 *
		 * @return object
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}
}
