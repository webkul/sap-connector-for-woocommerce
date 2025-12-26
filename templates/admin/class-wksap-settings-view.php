<?php
/**
 * Settings View handler class.
 *
 * @package SAP_Connector_For_WooCommerce
 */

namespace WKSAP\TEMPLATES\ADMIN;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

use WKSAP\INCLUDES;
use WKSAP\HELPERS;

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP_Settings_View' ) ) {
	/**
	 * Class WKSAP_Settings_View.
	 */
	class WKSAP_Settings_View {
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

			$this->wksap_init_admin();
		}

		/**
		 * Init admin method.
		 *
		 * @return void
		 */
		public function wksap_init_admin() {
			if ( isset( $_REQUEST['wksapnonce_connection_list'] ) ) {
				$wksap_connection_nonce = sanitize_text_field( wp_unslash( ( $_REQUEST['wksapnonce_connection_list'] ) ) );
				if ( ( ! empty( $_REQUEST['wksapnonce_connection_list'] ) ) && ( wp_verify_nonce( $wksap_connection_nonce, 'wksap_connection_table_action' ) && current_user_can( 'manage_options' ) ) ) {
					if ( isset( $_POST['wksap_connector_data'] ) ) {
						$json_string = str_replace( '\\', '', sanitize_text_field( wp_unslash( $_POST['wksap_connector_data'] ) ) );
						$sap_cred    = json_decode( $json_string );
						if ( ! empty( $sap_cred ) ) {
							$verify_license_key = HELPERS\WKSAP_Helper::wksap_verify_token( $sap_cred->sap_license_key, $sap_cred->instance );

							if ( ! empty( $verify_license_key ) ) {
								$data = HELPERS\WKSAP_Connector::wksap_generate_connection_with_sap( $sap_cred );

								if ( empty( $data ) ) {
									INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-error', esc_html__( 'Connection Status', 'sap-connector-for-woocommerce' ), esc_html__( 'Please fill the correct instance URL', 'sap-connector-for-woocommerce' ) );
									$log = esc_html__( 'Connection Status: Please fill the correct instance URL', 'sap-connector-for-woocommerce' );
									HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Connection', 'sap-connector-for-woocommerce' ), $log, true );
								} elseif ( isset( $data->error ) ) {
									INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-error', esc_html__( 'Connection Status', 'sap-connector-for-woocommerce' ), $data->error->message->value );
									$log = 'Connection Status:' . $data->error->message->value;
									HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Connection', 'sap-connector-for-woocommerce' ), $log, true );
								} else {
									update_option( 'wksap_config', $sap_cred );
									update_option( 'wksap_sap_connection', $data );
									INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-success', esc_html__( 'Connection Status', 'sap-connector-for-woocommerce' ), esc_html__( 'Successfully Connected', 'sap-connector-for-woocommerce' ) );
									$log = esc_html__( 'Connection Status: Successfully Connected', 'sap-connector-for-woocommerce' );
									HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Connection', 'sap-connector-for-woocommerce' ), $log, false );
								}
							} else {
								INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-error', esc_html__( 'Connection Status', 'sap-connector-for-woocommerce' ), esc_html__( 'License Key is not matched', 'sap-connector-for-woocommerce' ) );
								HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Connection', 'sap-connector-for-woocommerce' ), esc_html__( 'License Key is not matched', 'sap-connector-for-woocommerce' ), true );
							}
						}
					} elseif ( isset( $_POST['wksap_user_data_json'] ) ) {
						$json_string = str_replace( '\\', '', sanitize_text_field( wp_unslash( $_POST['wksap_user_data_json'] ) ) );
						$data        = json_decode( $json_string );
						update_option( 'wksap_user_config', $data );
						INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-success', esc_html__( 'User Configuration', 'sap-connector-for-woocommerce' ), esc_html__( 'User Configuration Updated', 'sap-connector-for-woocommerce' ) );
					}
				} else {
					wp_die( esc_html__( 'Security check failed', 'sap-connector-for-woocommerce' ) );
				}
			}
		}
		/**
		 * Display method to create view elements.
		 *
		 * @return void
		 */
		public function wksap_display() {
			$wk_action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( 'resetallmapp' === $wk_action ) {
				delete_metadata( 'user', 0, 'wk_sap_user_id', false, true );
				delete_metadata( 'user', 0, 'wk_sap_error', false, true );
				INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-success', esc_html__( 'Reset :', 'sap-connector-for-woocommerce' ), esc_html__( 'All mappings have been reset', 'sap-connector-for-woocommerce' ) );
			}

			if ( 'unlinkSAP' === $wk_action ) {
				delete_option( 'wksap_config' );
				delete_option( 'wooSAPInstance' );

				$delete_user_config = filter_input( INPUT_GET, 'delUserConfig', FILTER_SANITIZE_NUMBER_INT );

				if ( 1 === $delete_user_config ) {
					delete_option( 'wksap_user_config' );
				}

				if ( isset( $_SERVER['PHP_SELF'] ) ) {
					wp_safe_redirect( wp_unslash( $_SERVER['PHP_SELF'] ) . '?page=wksap_connector_settings' );
					exit;
				}
			}

			if ( 'testconnection' === $wk_action ) {
				$data = get_option( 'wksap_config' );
				$data = HELPERS\WKSAP_Connector::wksap_generate_connection_with_sap( $data );
				update_option( 'wksap_sap_connection', $data );
				INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-success', esc_html__( 'Connection Status : ', 'sap-connector-for-woocommerce' ), esc_html__( ' Connection Refreshed', 'sap-connector-for-woocommerce' ) );
			}

			if ( 'logsdeleted' === $wk_action ) {
				INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-success', esc_html__( 'Log Deletion:', 'sap-connector-for-woocommerce' ), esc_html__( 'Logs from before last seven days have been deleted', 'sap-connector-for-woocommerce' ) );
			}
			empty( get_option( 'wksap_config' ) ) ? $this->wksap_generate_connection_tab() : $this->wksap_generate_config_tabs();
		}

		/**
		 * Connection tab to allow SAP connection.
		 * This view will render before connection.
		 *
		 * @return void
		 */
		public function wksap_generate_connection_tab() {
			$wksap_config = get_option( 'wksap_config' );
			?>
			<h2>
				<u><?php esc_html_e( 'Establish Connection With SAP Business One', 'sap-connector-for-woocommerce' ); ?></u>
			</h2>

			<form name="myForm" method="post" id="wksap-form" onsubmit="return wkSAPvalidateform()">
				<table class="form-table full-width">
					<tr valign="top">
						<th  scope="row"><?php esc_html_e( 'SAP Username', 'sap-connector-for-woocommerce' ); ?></th>
						<td title="<?php esc_attr_e( 'Fill the SAP Business One Username', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
						<td>
							<input name="wwsconnector_username" type="text" id="wwsconnector_username" value="<?php echo isset( $wksap_config->UserName ) ? esc_html( $wksap_config->UserName ) : ''; ?>" required />
						</td>
					</tr>
					<tr valign="top">
						<th  scope="row"><?php esc_html_e( 'SAP Password', 'sap-connector-for-woocommerce' ); ?></th>
						<td title="<?php esc_attr_e( 'Fill the SAP Business One Password', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
						<td>
							<input name="wwsconnector_password" type="password" id="wwsconnector_password" value="<?php echo isset( $wksap_config->Password ) ? esc_html( $wksap_config->Password ) : ''; ?>" required />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'SAP Company DB', 'sap-connector-for-woocommerce' ); ?></th>
						<td title="<?php esc_attr_e( 'Fill the SAP Business One Company DB(Case-Sensitive)', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
						<td>
							<input name="wwsconnector_sapdb" type="text" id="wwsconnector_sapdb" value="<?php echo isset( $wksap_config->CompanyDB ) ? esc_attr( $wksap_config->CompanyDB ) : ''; ?>" required />
						</td>
					</tr>
					<tr valign="top">
						<th  scope="row"><?php esc_html_e( 'SAP Service Layer  URL', 'sap-connector-for-woocommerce' ); ?></th>
						<td title="<?php esc_attr_e( 'Fill the SAP Business One Service Layer URL', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
						<td>
							<input name="wwsconnector_instance" type="text" id="wwsconnector_instance" value="<?php echo isset( $wksap_config->instance ) ? esc_attr( $wksap_config->instance ) : ''; ?>" required />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'SAP Prefix', 'sap-connector-for-woocommerce' ); ?></th>
						<td title="<?php esc_attr_e( 'This prefix will be used on Item Number for uniqueness. It accepts a maximum of 3 characters', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
						<td>
							<input name="wwsconnector_sap_prefix" type="text" id="wwsconnector_sap_prefix" maxlength="3" value="<?php echo isset( $wksap_config->sap_prefix ) ? esc_attr( $wksap_config->sap_prefix ) : ''; ?>" required />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'License Key', 'sap-connector-for-woocommerce' ); ?></th>
						<td title="<?php esc_attr_e( 'Enter the License Key which is provided by Webkul', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
						<td>
							<input name="wwsconnector_sap_license_key" type="text" id="wwsconnector_sap_license_key" value="<?php echo isset( $wksap_config->sap_license_key ) ? esc_attr( $wksap_config->sap_license_key ) : ''; ?>" required />
							<div><span><?php esc_html_e( 'To get License Key, mail us at: ', 'sap-connector-for-woocommerce' ); ?><a href="<?php echo esc_url( 'mailto:support@webkul.com' ); ?>"><?php esc_html_e( 'support@webkul.com', 'sap-connector-for-woocommerce' ); ?></a> <?php esc_html_e( 'with your hostname:', 'sap-connector-for-woocommerce' ); ?> <strong>
									<?php
									echo isset( $_SERVER['SERVER_NAME'] ) ? esc_html( sanitize_text_field( wp_unslash( $_SERVER['SERVER_NAME'] ) ) ) : esc_html__( 'Not Available', 'sap-connector-for-woocommerce' );
									?>
								</strong>
								</span>
							</div>
						</td>
					</tr>
				<?php wp_nonce_field( 'wksap_connection_table_action', 'wksapnonce_connection_list' ); ?>
				</table>
				<button type="button" onclick="wkSAPinitilaizeConnection('P')" class="button button-primary"><?php esc_html_e( 'Verify Connection', 'sap-connector-for-woocommerce' ); ?></button>
				<input type="hidden" id="wksap_connector_data" name="wksap_connector_data" />
			</form>
			<?php
		}

		/**
		 * Generate configuration tabs.
		 * This view will render after connection successful.
		 *
		 * @return void
		 */
		public function wksap_generate_config_tabs() {
			$tabActive = 'general';
			$sap       = array();

			$wk_tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

			if ( ! empty( $wk_tab ) ) {
				if ( 'user' === $wk_tab ) {
					$tabActive = 'user';
				}
			}

			if ( isset( $sap['error'] ) ) {
				INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-error',  esc_html__( 'Error', 'sap-connector-for-woocommerce' ), $sap['error'] );
			} elseif ( ! empty( $sap['orgDetails'] ) ) {
				INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-success', esc_html__( 'Connection Status', 'sap-connector-for-woocommerce' ), esc_html__( 'Successfully Connected', 'sap-connector-for-woocommerce' ) );
			}
			?>
			<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wksap_connector_settings&tab=general' ) ); ?>" class="nav-tab
				<?php echo 'general' === $tabActive ? esc_html( 'nav-tab-active' ) : ''; ?>"><?php esc_html_e( 'Connection Settings', 'sap-connector-for-woocommerce' ); ?> </a>
				<a href="<?php echo esc_url( 'admin.php?page=wksap_connector_settings&tab=user' ); ?>" class="nav-tab
				<?php echo 'user' === $tabActive ? esc_html( 'nav-tab-active' ) : ''; ?>"><?php esc_html_e( 'User', 'sap-connector-for-woocommerce' ); ?></a>
			</nav>
			<div id="dialogbox"></div>
			<form method="post" action="#" onsubmit="return setJSONString()" id="config-form">
				<?php
				if ( 'user' === $tabActive ) {
					$this->wksap_generate_user_tab();
				} elseif ( 'general' === $tabActive ) {
					$this->wksap_generate_general_tab();
				} else {
					INCLUDES\WKSAP_MAIN::wksap_show_notice( 'notice notice-error', esc_html__( 'Unknown Tab', 'sap-connector-for-woocommerce' ), esc_html__( 'Please Recheck Tab in URL', 'sap-connector-for-woocommerce' ) );
				}
				?>
			</form>
			<?php
		}

		/**
		 * Create general configuration tab view.
		 *
		 * @return void
		 */
		public function wksap_generate_general_tab() {
			$wksap_config = get_option( 'wksap_config' );
			?>
			<h2>
				<u><?php esc_html_e( 'WooCommerce SAP Business One Connection Information', 'sap-connector-for-woocommerce' ); ?></u>
			</h2>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'SAP Username', 'sap-connector-for-woocommerce' ); ?></th>
					<td title="<?php esc_attr_e( 'Connected SAP Username', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span>
					</td>
					<td>
						<?php
						if ( ! empty( $wksap_config->UserName ) ) {
							echo esc_html( $wksap_config->UserName );
						}
						?>
					</td>
				</tr>
				<tr>
					<th> <?php esc_html_e( 'SAP Password', 'sap-connector-for-woocommerce' ); ?></th>
					<td title="<?php esc_attr_e( 'Connected SAP Password', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span>
					</td>
					<td>
						<?php
						if ( ! empty( $wksap_config->Password ) ) {
							$output_password = str_repeat( '*', strlen( $wksap_config->Password ) );
							echo esc_html( $output_password );
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'SAP Company DB', 'sap-connector-for-woocommerce' ); ?></th>
					<td title="<?php esc_attr_e( 'Connected SAP Company DB', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
					<td>
						<?php
						if ( ! empty( $wksap_config->CompanyDB ) ) {
							echo esc_html( $wksap_config->CompanyDB );
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'SAP Service Layer URL', 'sap-connector-for-woocommerce' ); ?></th>
					<td title="<?php esc_attr_e( 'Connected SAP Instance URL', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
					<td>
						<?php
						if ( ! empty( $wksap_config->instance ) ) {
							echo esc_html( $wksap_config->instance );
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'SAP Prefix', 'sap-connector-for-woocommerce' ); ?></th>
					<td title="<?php esc_attr_e( 'This prefix will be used on Item Number for uniqueness. It accepts a maximum of 3 characters ', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
					<td>
						<?php
						if ( ! empty( $wksap_config->sap_prefix ) ) {
							echo esc_html( $wksap_config->sap_prefix . '_' );
						}
						?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'License Key', 'sap-connector-for-woocommerce' ); ?></th>
					<td title="<?php esc_attr_e( 'Connected License Key with the Connector', 'sap-connector-for-woocommerce' ); ?>" class="ttip" >
						<span class="dashicons dashicons-editor-help"></span>
					</td>
					<td>
						<?php
						if ( ! empty( $wksap_config->sap_license_key ) ) {
							echo esc_html( $wksap_config->sap_license_key );
						}
						?>
					</td>
				</tr>
			</table>
			<table class="wksap-config-button-table">
				<tr>
					<td>
						<?php
							$php_self = isset( $_SERVER['PHP_SELF'] ) ? sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '';
						?>
						<a href="<?php echo esc_url( $php_self . '?page=wksap_connector_settings&action=testconnection' ); ?>" class="config-button button wksap-testconnection-button"><?php esc_html_e( 'Refresh Connection', 'sap-connector-for-woocommerce' ); ?></a>

					</td>
					<td>
						<a href="<?php echo esc_url( $php_self . '?page=wksap_connector_settings&action=unlinkSAP' ); ?>" class="config-button button button-primary disconnect"><?php esc_html_e( 'Disconnect Connection', 'sap-connector-for-woocommerce' ); ?></a>
					</td>
					<td>
						<a href="<?php echo esc_url( $php_self . '?page=wksap_connector_settings&action=resetallmapp' ); ?>" class="config-button button button-primary resetallmap"><?php esc_html_e( 'Reset All Mapping', 'sap-connector-for-woocommerce' ); ?></a>
					</td>
					<td>
						<?php echo "<a id='wksap-deletelog' href='#' title='" . esc_attr__( 'Delete Logs older than last 7 days', 'sap-connector-for-woocommerce' ) . "' class='config-button button button-primary wksap-deletelog'>" . esc_html__( 'Delete Logs', 'sap-connector-for-woocommerce' ) . '</a>'; ?>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Wksap generate user tab.
		 *
		 * @return void
		 */
		public function wksap_generate_user_tab() {
			wp_enqueue_script( 'wksap-user-ajax-script', WKSAP_SAP_PLUGIN_URL . '/assets/dist/js/user-config.min.js', array( 'jquery' ), WKSAP_SCRIPT_VERSION, true );
			$wksap_user_config = get_option( 'wksap_user_config' );
			if ( ! $wksap_user_config ) {
				$wksap_user_config = new \stdclass();
			}
			?>
			<h2>
				<u><?php esc_html_e( 'WooCommerce User Configurations', 'sap-connector-for-woocommerce' ); ?></u>
			</h2>
			<table class="form-table" id="wksap-user-setting-table">
				<tr>
					<th>
						<?php esc_html_e( 'Sync User to SAP', 'sap-connector-for-woocommerce' ); ?>
					</th>
					<td scope="row"  title="<?php esc_attr_e( 'Enable this to activate WooCommerce user Sync functionality.', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
					<td>
						<label class="wksap-switch">
							<input type="checkbox" data-name="wksap_sync_user" id="wksap_sync_user" name="wksap_sync_user" <?php echo ! empty( $wksap_user_config->wksap_sync_user ) && true === $wksap_user_config->wksap_sync_user ? 'checked="checked"' : ''; ?> />
							<div class="wksap-slider round"></div>
						</label>
					</td>
				</tr>

				<tr>
					<th>
						<?php esc_html_e( 'Enable Auto User Synchronization', 'sap-connector-for-woocommerce' ); ?>
					</th>
					<td scope="row" title="<?php esc_attr_e( 'Enable: WooCommerce User will gets Synced as SAP Business Patner in Real-Time', 'sap-connector-for-woocommerce' ); ?>" class="ttip" ><span class="dashicons dashicons-editor-help"></span></td>
					<td>
						<label class="wksap-switch">
							<input type="checkbox" data-name="auto_user_sync" id="auto_user_sync" name="auto_user_sync" <?php echo isset( $wksap_user_config->auto_user_sync ) && true === $wksap_user_config->auto_user_sync ? esc_attr( 'checked="checked"' ) : ''; ?> />
							<div class="wksap-slider round"></div>
						</label>
					</td>
				</tr>

				<?php wp_nonce_field( 'wksap_connection_table_action', 'wksapnonce_connection_list' ); ?>

			</table>
			<input type="hidden" name="wksap_user_data_json" id="wksap_user_data_json" />
			<?php
			submit_button();
		}

		/**
		 * Return a singleton instance of WKSAP_Settings_View class
		 *
		 * @brief Singleton
		 *
		 * @return WKSAP_Settings_View
		 */
		public static function get_instance() {
			static $instance = null;
			if ( is_null( $instance ) ) {
				$instance = new self();
			}
			return $instance;
		}
	}
}