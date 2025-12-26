<?php
/**
 * MAIN Handler handler class.
 *
 * @package SAP_Connector_For_WooCommerce
 */

namespace WKSAP\INCLUDES;

defined( 'ABSPATH' ) || wp_die(); // Exit if accessed directly.

use WKSAP\HELPERS;

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP_MAIN' ) ) {
	/**
	 * Main WooCommerce SAP Connector Class.
	 */
	class WKSAP_MAIN {
		/**
		 * WooCommerce SAP Connector.
		 *
		 * @var string
		 */
		public $version = '1.0.0';

		/**
		 * Instance variable.
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Process All.
		 *
		 * @var $process_all process all.
		 */
		protected static $process_all;

		/**
		 * Synchronized Process User.
		 *
		 * @var $sync_user sync_user.
		 */
		protected static $sync_user;

		/**
		 * User.
		 *
		 * @var object $wksap_user_config user config.
		 */
		protected static $wksap_user_config;

		/**
		 * WooCommerce SAP Connector Constructor.
		 *
		 * @return void
		 */
		public function __construct() {
			$this->wksap_define_constants();
			self::$sync_user = new WKSAP_Synchronized_Process_User();
			add_action( 'wp_ajax_wksap_woo_export_users', array( $this, 'wksap_woo_export_users' ) );
			// Export Object with Background Job.
			add_action( 'wp_ajax_wksap_export_object_with_background_job', array( $this, 'wksap_export_object_with_background_job' ) );
			add_action( 'wp_ajax_wksap_export_object_with_background_jobs', array( $this, 'wksap_export_object_with_background_jobs' ) );
			add_action( 'wp_ajax_wksap_stop_background_job', array( $this, 'wksap_stop_background_job' ) );
			add_action( 'wp_ajax_wksap_delete_mapping_data', array( $this, 'wksap_delete_mapping_data' ) );
			add_action( 'real_time_user', array( $this, 'wksap_woo_export_users' ), 10, 2 );

			// user config.
			self::$wksap_user_config = get_option( 'wksap_user_config' );

			if ( ! self::$wksap_user_config ) {
				self::$wksap_user_config = new \stdclass();
			}
			if ( isset( self::$wksap_user_config->auto_user_sync ) && self::$wksap_user_config->auto_user_sync ) {
				add_action( 'user_register', array( $this, 'wksap_woo_sap_new_user' ) );
				add_action( 'profile_update', array( $this, 'wksap_woo_sap_new_user' ), 12 );
				add_action( 'woocommerce_customer_save_address', array( $this, 'wksap_woo_sap_new_user' ), 12 );
			}

			// if disconnected then remove hooks.
			$wksap_config = get_option( 'wksap_config' );
			if ( ! $wksap_config ) {
				remove_action( 'user_register', array( $this, 'wksap_woo_sap_new_user' ) );
				remove_action( 'profile_update', array( $this, 'wksap_woo_sap_new_user' ) );
			}
		}

		/**
		 * Define WooCommerce SAP Connector Constants.
		 *
		 * @return void
		 */
		private function wksap_define_constants() {
			if ( ! function_exists( 'wksap_run_sap_connection_cron' ) ) {
				/**
				 * Run the cron job.
				 * This function will be executed every 15 minutes.
				 * This function will check the connection status with SAP.
				 * If the connection is not successful, it will send an email to the admin.
				 *
				 * @return void
				 */
				function wksap_run_sap_connection_cron() {
					$data = get_option( 'wksap_config', false );
					$data = HELPERS\WKSAP_Connector::wksap_generate_connection_with_sap( $data );

					if ( ! empty( $data->error ) ) {
						$log = esc_html__( 'Connection Status:', 'sap-connector-for-woocommerce' ) . $data->error->message->value;
						HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Connection ', 'sap-connector-for-woocommerce' ), $log, true );
					}

					update_option( 'wksap_sap_connection', $data );
				}
			}
		}

		/**
		 * Create Notice as a message.
		 *
		 * @param string $type    type of notice updated/warning/error.
		 * @param string $title   title of message.
		 * @param string $message message.
		 *
		 * @return void
		 */
		public static function wksap_show_notice( $type, $title, $message ) {
			?>
			<div class="<?php echo esc_attr( $type ); ?> notice is-dismissible">
				<p>
					<strong>
						<?php echo esc_html( $title ); ?>
					</strong>
					<?php echo esc_html( $message ); ?>.
				</p>
				<button class="notice-dismiss" type="button">
					<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'sap-connector-for-woocommerce' ); ?></span>
				</button>
			</div>
			<?php
		}

		/**
		 * Get WooCommerce User ID.
		 *
		 * @param string $sync_type $sync_type.
		 * @param string $post_per_page $sync_type.
		 *
		 * @return mixed
		 */
		public static function wksap_get_woo_user_ids( $sync_type, $post_per_page ) {
			global $wpdb;

			$sync      = ( isset( $sync_type['item_type'] ) && in_array( $sync_type['item_type'], array( 'S', 'U', 'E', 'A' ), true ) )
				? sanitize_text_field( $sync_type['item_type'] )
				: 'A';
			$post      = ( ! empty( $post_per_page ) && is_numeric( $post_per_page ) && $post_per_page > 0 )
				? intval( $post_per_page )
				: -1;
			$cache_key = 'wksap_user_ids_' . md5( wp_json_encode( $sync ) . $post );
			$results   = get_transient( $cache_key );

			if ( false !== $results ) {
				return $results;
			}

			$wpdbs      = $wpdb;
			$user_table = $wpdbs->users;
			$meta_table = $wpdbs->usermeta;
			$sql        = "SELECT DISTINCT u.ID FROM {$user_table} u";

			switch ( $sync ) {
				case 'S':
					$sql .= $wpdbs->prepare(
						" INNER JOIN {$meta_table} m1 ON u.ID = m1.user_id AND m1.meta_key = %s
                            LEFT JOIN {$meta_table} m2 ON u.ID = m2.user_id AND m2.meta_key = %s
                            LEFT JOIN {$meta_table} m3 ON u.ID = m3.user_id AND m3.meta_key = %s
                            WHERE m2.meta_key IS NULL AND m3.meta_key IS NULL",
						'wk_sap_user_id',
						'wk_sap_error',
						'user_Synced'
					);
					break;

				case 'U':
					$sql .= $wpdbs->prepare(
						" LEFT JOIN {$meta_table} m1 ON u.ID = m1.user_id AND m1.meta_key = %s
                            LEFT JOIN {$meta_table} m2 ON u.ID = m2.user_id AND m2.meta_key = %s
                            LEFT JOIN {$meta_table} m3 ON u.ID = m3.user_id AND m3.meta_key = %s
                            WHERE m1.meta_key IS NULL AND m2.meta_key IS NULL AND m3.meta_key IS NULL",
						'wk_sap_user_id',
						'wk_sap_error',
						'user_Synced'
					);
					break;

				case 'E':
					$sql .= $wpdbs->prepare(
						" LEFT JOIN {$meta_table} m1 ON u.ID = m1.user_id AND m1.meta_key = %s
                            LEFT JOIN {$meta_table} m2 ON u.ID = m2.user_id AND m2.meta_key = %s
                            LEFT JOIN {$meta_table} m3 ON u.ID = m3.user_id AND m3.meta_key = %s
                            WHERE m1.meta_key IS NULL AND (m2.meta_key IS NOT NULL OR m3.meta_key IS NULL)",
						'user_Synced',
						'wk_sap_error',
						'wk_sap_user_id'
					);
					break;

				case 'A':
				default:
					$sql .= $wpdbs->prepare(
						" LEFT JOIN {$meta_table} m1 ON u.ID = m1.user_id AND m1.meta_key = %s
                            WHERE m1.meta_key IS NULL",
						'user_Synced'
					);
					break;
			}

			if ( $post > 0 ) {
				$sql .= $wpdb->prepare( ' LIMIT %d', intval( $post ) );
			}

			$user_ids = $wpdbs->get_col( $sql );
			$results  = ! empty( $user_ids ) ? array_map( 'intval', $user_ids ) : false;

			set_transient( $cache_key, $results, 10 * MINUTE_IN_SECONDS );

			return $results;
		}

		/**
		 * Wksap woo export user data .
		 *
		 * @param string $sync_type $sync_type.
		 * @param string $date_range $date_range.
		 * @param string $option $option.
		 *
		 * @return mixed
		 */
		public static function wksap_woo_export_user_data( $sync_type = '', $date_range = '', $option = '' ) {

			if ( ! empty( $date_range ) && ! empty( $option ) ) {
				$user_ids = self::wksap_get_object_record_ids_by_date_range( 'user', $date_range, $option, $sync_type, -1 );

				if ( ! empty( $user_ids ) ) {
					foreach ( $user_ids as $id ) {
						if ( get_option( 'wksap_disable_stop_user_background_button', false ) ) {
							delete_option( 'wksap_disable_stop_user_background_button' );
							HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( 'end here', 'sap-connector-for-woocommerce' ) );
							return false;
						}
						HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( ' Before calling wooSAPExportUser via dateFiler', 'sap-connector-for-woocommerce' ) );
						self::wksap_woo_export_users( false, $id );
						HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( ' After calling wooSAPExportUser via dateFiler', 'sap-connector-for-woocommerce' ) );
						update_user_meta( $id, 'user_Synced', true );

						$processed_user = get_option( 'wksap_users_processed' );
						update_option( 'wksap_users_processed', $processed_user + 1 );
					}
				} else {
					return false;
				}

				$data = array(
					'itemType'   => $sync_type,
					'date_range' => $date_range,
					'option'     => $option,
				);
			} else {
				$user_ids = self::wksap_get_woo_user_ids( $sync_type, 2 );

				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), wp_json_encode( $user_ids ), false );
				if ( ! empty( $user_ids ) ) {
					foreach ( $user_ids as $id ) {
						if ( get_option( 'wksap_disable_stop_user_background_button', false ) ) {
							delete_option( 'wksap_disable_stop_user_background_button' );
							return false;
						}
						self::wksap_woo_export_users( false, $id );
						update_user_meta( $id, 'user_Synced', true );
						$processed_user = get_option( 'wksap_users_processed' );
						update_option( 'wksap_users_processed', $processed_user + 1 );
					}
				} else {
					return false;
				}

				$data = array( 'itemType' => $sync_type );
			}
			return $data;
		}

		/**
		 * Wksap export users.
		 *
		 * @param boolean $ajax_call $ajax_call.
		 * @param integer $user_id $user_id.
		 * @param boolean $order_sync $order_sync.
		 *
		 * @return mixed
		 */
		public static function wksap_woo_export_users( $ajax_call = true, $user_id = 0, $order_sync = false ) {
			self::wksap_get_woo_user_ids( 'A', '3' );

			if ( isset( self::$wksap_user_config->wksap_sync_user ) && self::$wksap_user_config->wksap_sync_user ) {
				$ajax_call    = true === $ajax_call ? true : false;
				$error_flag   = false;
				$error_list   = array();
				$wksap_config = get_option( 'wksap_config' );

				if ( ! check_ajax_referer( 'wksap_nonce', 'wksap_nonce', false ) || ! current_user_can( 'manage_options' ) ) {
					HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( 'nonce not verifyed', 'sap-connector-for-woocommerce' ), true );
					wp_send_json_error( esc_html__( 'Security check failed', 'sap-connector-for-woocommerce' ) );
					wp_die();
				}

				$user_id = isset( $_POST['user_id'] ) ? (string) intval( $_POST['user_id'] ) : (string) $user_id;

				if ( ! isset( $user_id ) ) {
					$error_list[] = esc_html__( 'No user id', 'sap-connector-for-woocommerce' );
					$error_flag   = true;
				}

				$ttime_nn       = time();
				$processed_item = array(
					'total'       => 0,
					'updated'     => 0,
					'added'       => 0,
					'errorsValue' => 0,
					'sap_user_id' => '',
					'syncc_time'  => '',
					'error'       => '',
				);

				try {
					$error_list = array();
					$user       = get_userdata( (int) $user_id );
					$user_meta  = get_user_meta( (int) $user_id );
					$billing_address_1  = isset( $user_meta['billing_address_1'][0] ) ? $user_meta['billing_address_1'][0] : '';
					$billing_address_2  = isset( $user_meta['billing_address_2'][0] ) ? $user_meta['billing_address_2'][0] : '';
					$shipping_address_1 = isset( $user_meta['shipping_address_1'][0] ) ? $user_meta['shipping_address_1'][0] : '';
					$shipping_address_2 = isset( $user_meta['shipping_address_2'][0] ) ? $user_meta['shipping_address_2'][0] : '';

					if ( ! empty( $user ) ) {
						$sap_account_id           = '';
						$processed_item['total'] += 1;
						$first_name = get_user_meta( $user->data->ID, 'first_name', true );
						$last_name  = get_user_meta( $user->data->ID, 'last_name', true );

						update_user_meta( $user->data->ID, 'wk_sap_user_sync_time', time() );

						$date_format = get_option( 'date_format' );
						$time_format = get_option( 'time_format' );
						$processed_item['syncc_time'] = gmdate( $date_format . ' ' . $time_format, $ttime_nn );
						if ( empty( $last_name ) ) {
							$last_name = $user->data->user_login;
						}
						if ( empty( $last_name ) ) {
							$last_name = $user->data->user_email;
						}

						$full_name       = $first_name . ' ' . $last_name;
						$billing_phone   = get_user_meta( $user->data->ID, 'billing_phone', true );
						$account_details = new \stdClass();
						$account_details->EmailAddress     = isset( $user->data->user_email ) ? $user->data->user_email : '';
						$account_details->U_Wk_Woo_User_Id = (string) $user_id;
						$urlparts = wp_parse_url( home_url() );
						$domain   = isset( $urlparts['host'] ) ? $urlparts['host'] : '';
						$account_details->U_Wk_Woo_Store_Key = $wksap_config->sap_prefix . '_' . $domain;
						$account_details->Cellular           = $billing_phone;
						$account_details->City        = isset( $user_meta['billing_city'][0] ) ? $user_meta['billing_city'][0] : '';
						$account_details->County      = isset( $user_meta['billing_country'][0] ) ? $user_meta['billing_country'][0] : '';
						$account_details->Country     = isset( $user_meta['billing_country'][0] ) ? $user_meta['billing_country'][0] : '';
						$account_details->ZipCode     = isset( $user_meta['billing_postcode'][0] ) ? $user_meta['billing_postcode'][0] : '';
						$account_details->BillToState = isset( $user_meta['billing_state'][0] ) ? $user_meta['billing_state'][0] : '';
						$account_details->Address     = $billing_address_1 . ' ' . $billing_address_2;
						$account_details->MailCity    = isset( $user_meta['shipping_city'][0] ) ? $user_meta['shipping_city'][0] : '';
						$account_details->MailCounty  = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
						$account_details->MailCountry = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
						$account_details->MailZipCode = isset( $user_meta['shipping_postcode'][0] ) ? $user_meta['shipping_postcode'][0] : '';
						$account_details->ShipToState = isset( $user_meta['shipping_state'][0] ) ? $user_meta['shipping_state'][0] : '';
						$account_details->MailAddress = $shipping_address_1 . ' ' . $shipping_address_2;
						$account_details->SubjectToWithholdingTax = esc_attr( 'boNO' );
						$sap_account_id = get_user_meta( $user_id, 'wk_sap_user_id', true );

						if ( ! empty( $sap_account_id ) ) {
							$account_details->CardCode = $sap_account_id;
						} elseif ( $wksap_config->sap_prefix ) {
							$account_details->CardCode = $wksap_config->sap_prefix . '_' . $user_id;
						} else {
							$account_details->CardCode = $user_id;
						}

						$account_details->CardName = $full_name;
						$account_details->CardType = esc_attr( 'cCustomer' );
						/* Contact Person */
						$contact_person_data           = new \stdClass();
						$contact_person_data->CardCode = $account_details->CardCode;
						$contact_person_data->Name     = $account_details->CardName;
						$contact_person_data->MobilePhone = $account_details->Cellular;
						$contact_person_data->E_Mail      = $account_details->EmailAddress;
						$contact_person_data->FirstName   = $first_name;
						$contact_person_data->LastName    = $last_name;
						$contact_employees = array();
						$contact_employees = $contact_person_data;
						/* Check Existence of User(Business Partner) at SAP end */

						$sap_bp_query = esc_html__( 'EmailAddress eq ', 'sap-connector-for-woocommerce' ) . $account_details->EmailAddress;

						$sap_bp_data = HELPERS\WKSAP_Connector::wksap_get_saps_object( 'BusinessPartners', rawurlencode( $sap_bp_query ) );

						if ( isset( $sap_bp_data->value ) ) {
							$sap_account_id = isset( $sap_bp_data->value[0]->CardCode ) ? $sap_bp_data->value[0]->CardCode : '';
						}
						// upsert.
						if ( ! $error_flag ) {
							if ( isset( $sap_account_id ) && ! empty( $sap_account_id ) ) {
								$result = HELPERS\WKSAP_Connector::wksap_update_saps_object( 'BusinessPartners', wp_json_encode( $account_details ), $sap_account_id );
								if ( isset( $result->error ) ) {
									$error_flag   = true;
									$error_list[] = esc_html__( 'Error in User Update:', 'sap-connector-for-woocommerce' ) . ( isset( $result->error->message->value ) ? $result->error->message->value : '' );
									update_user_meta( $user->data->ID, 'wk_sap_user_id', $wksap_config->sap_prefix . '_' . $user_id );
									delete_user_meta( $user->data->ID, 'wk_sap_error' );
									foreach ( $error_list as $error ) {
										HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $error, true );
									}
								} else {
									$msg = esc_html__( 'Export - User Id: ', 'sap-connector-for-woocommerce' ) . ( $user->data->ID ) . esc_html__( ' Updated, SAP BP Id: ', 'sap-connector-for-woocommerce' ) . $sap_account_id;
									update_user_meta( $user->data->ID, 'wk_sap_user_id', $wksap_config->sap_prefix . '_' . $user_id );
									delete_user_meta( $user->data->ID, 'wk_sap_error' );
									HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $msg, false );
								}
							} else {
								/*
								ContactEmployee only Get Created When Business Partner get Created.
								- Also need to find a way To update ContactEmployee.
								*/
								$account_details->ContactEmployees = $contact_employees;
								$result = HELPERS\WKSAP_Connector::wksap_insert_saps_object( 'BusinessPartners', wp_json_encode( $account_details ) );
								HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), esc_html__( 'LINE NUmber :- ', 'sap-connector-for-woocommerce' ) . __LINE__ . esc_html__( '   Here :- ', 'sap-connector-for-woocommerce' ) . wp_json_encode( $result ) );

								if ( ! empty( $result->error ) ) {
									$error_flag   = true;
									$error_list[] = esc_html__( 'Error in User Insert:', 'sap-connector-for-woocommerce' ) . $result->error->message->value;

									update_user_meta( $user->data->ID, 'wk_sap_user_id', $wksap_config->sap_prefix . '_' . $user_id );
									delete_user_meta( $user->data->ID, 'wk_sap_error' );
									foreach ( $error_list as $error ) {
										HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $error, true );
									}
								} else {
									$msg = esc_html__( 'Export - User Id: ', 'sap-connector-for-woocommerce' ) . ( $user->data->ID ) . esc_html__( ' inserted, SAP BP Id: ', 'sap-connector-for-woocommerce' ) . $sap_account_id;
									HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $msg, false );
								}
							}
						}

						if ( ! $error_flag ) {
							$wk_sap_user_id = get_user_meta( $user->data->ID, 'wk_sap_user_id', true );
							if ( ! empty( $wk_sap_user_id ) ) {
								if ( ! $error_flag ) {
									$processed_item['updated'] += 1;
									update_user_meta( $user->data->ID, 'wk_sap_user_id', $sap_account_id );
									delete_user_meta( $user->data->ID, 'wk_sap_error' );
								} else {
									$processed_item['errorsValue'] += 1;
									update_user_meta( $user->data->ID, 'wk_sap_user_id', $sap_account_id );
									update_user_meta( $user->data->ID, 'wk_sap_error', implode( ',', $error_list ) );
									delete_user_meta( $user->data->ID, 'wk_sap_error' );
								}
								$processed_item['sap_user_id'] = '';
							} else {
								if ( ! $error_flag ) {
									delete_user_meta( $user_id, 'wk_sap_error' );
								} else {
									add_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
									delete_user_meta( $user->data->ID, 'wk_sap_error' );
								}
								$sap_account_id = $wksap_config->sap_prefix . '_' . $user_id;
								add_user_meta( $user_id, 'wk_sap_user_id', $sap_account_id );
								$processed_item['added']      += 1;
								$processed_item['sap_user_id'] = $sap_account_id;
							}
						} else {
							if ( ! $error_flag ) {
								delete_user_meta( $user_id, 'wk_sap_error' );
								$sap_account_id = isset( $result->CardCode ) ? $result->CardCode : '';
								add_user_meta( $user_id, 'wk_sap_user_id', $sap_account_id );
							} else {
								add_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
								delete_user_meta( $user->data->ID, 'wk_sap_error' );
							}

							$processed_item['added']      += 1;
							$processed_item['sap_user_id'] = $sap_account_id;
						}
					}
				} catch ( \Exception $e ) {
					$error        = $e->getMessage();
					$error_list[] = $error;
					$error_flag   = true;
					$log          = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();

					HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $log, true );
				}
				if ( $error_flag ) {
					$error_arr = wksap_wp_sap_object_to_array( $error_list );

					if ( isset( $error_arr[0] ) && is_array( $error_arr[0] ) && count( $error_arr[0] ) > 0 ) {
						$error_arr = $error_arr[0];
					}

					$processed_item['error']  = implode( ',', $error_arr );
					$processed_item['status'] = '0';
				}
				if ( $user_id && ! empty( $error_list ) ) {
					$wk_sap_error = get_user_meta( $user_id, 'wk_sap_error', true );
					if ( isset( $wk_sap_error ) ) {
						$error_list = wksap_wp_sap_object_to_array( $error_list );
						update_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
					} else {
						add_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
						delete_user_meta( $user->data->ID, 'wk_sap_error' );
					}
				}

				if ( $error_flag ) {
					$processed_item['errorsValue'] += 1;
				}
				if ( ! $ajax_call || $order_sync ) {
					wp_send_json( wp_json_encode( $processed_item ) );
				} else {
					echo wp_json_encode( $processed_item );
					wp_die();
				}
				wp_die();
			} else {
				echo esc_html__( 'Kindly enable import settings.', 'sap-connector-for-woocommerce' );
			}
		}

		/**
		 * Export Background Job for the particular Object Users.
		 *
		 * @return void
		 */
		public static function wksap_export_object_with_background_jobs() {
			if ( ! isset( self::$wksap_user_config->wksap_sync_user ) || ! self::$wksap_user_config->wksap_sync_user ) {
				return;
			}
			if ( ! check_ajax_referer( 'wksap_nonce', 'wksap_nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( 'nonce not verifyed', 'sap-connector-for-woocommerce' ), true );
				wp_send_json_error( esc_html__( 'Invalid Nonce', 'sap-connector-for-woocommerce' ) );
				wp_die();
			}

			$sync     = isset( $_POST['sync'] ) ? sanitize_text_field( wp_unslash( $_POST['sync'] ) ) : esc_attr( 'N' );
			$s_object = isset( $_POST['sObject'] ) ? sanitize_text_field( wp_unslash( $_POST['sObject'] ) ) : '';

			if ( 'user' === $s_object ) {
				$user_ids = self::wksap_get_woo_user_ids( $sync, -1 );

				$user_ids    = is_array( $user_ids ) ? $user_ids : array();
				$total_users = count( $user_ids );
				update_option( 'wksap_users_total', $total_users );
				update_option( 'wksap_users_processed', 0 );
				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( ' User Ids :- ', 'sap-connector-for-woocommerce' ) . $total_users );

				if ( empty( $user_ids ) ) {
					$job_msg = array(
						'code'    => 'warning',
						'message' => esc_html__( 'There are no users to sync', 'sap-connector-for-woocommerce' ),
					);
					echo wp_json_encode( $job_msg );
					wp_die();
				}

				// Initialize summary counters.
				$summary = array(
					'total'          => $total_users,
					'updated'        => 0,
					'added'          => 0,
					'errors'         => 0,
					'error_messages' => array(),
				);

				$wksap_config = get_option( 'wksap_config' );

				// Process each user synchronously.
				foreach ( $user_ids as $user_id ) {
					$processed_item = array(
						'total'       => 0,
						'updated'     => 0,
						'added'       => 0,
						'errorsValue' => 0,
						'sap_user_id' => '',
						'syncc_time'  => '',
						'error'       => '',
					);

					$error_flag = false;
					$error_list = array();
					$ttime_nn   = time();

					try {
						$user      = get_userdata( (int) $user_id );
						$user_meta = get_user_meta( (int) $user_id );
						$billing_address_1  = isset( $user_meta['billing_address_1'][0] ) ? $user_meta['billing_address_1'][0] : '';
						$billing_address_2  = isset( $user_meta['billing_address_2'][0] ) ? $user_meta['billing_address_2'][0] : '';
						$shipping_address_1 = isset( $user_meta['shipping_address_1'][0] ) ? $user_meta['shipping_address_1'][0] : '';
						$shipping_address_2 = isset( $user_meta['shipping_address_2'][0] ) ? $user_meta['shipping_address_2'][0] : '';

						if ( ! empty( $user ) ) {
							$sap_account_id          = '';
							$processed_item['total'] = 1;
							$first_name = get_user_meta( $user->data->ID, 'first_name', true );
							$last_name  = get_user_meta( $user->data->ID, 'last_name', true );

							update_user_meta( $user->data->ID, 'wk_sap_user_sync_time', time() );

							$date_format = get_option( 'date_format' );
							$time_format = get_option( 'time_format' );
							$processed_item['syncc_time'] = gmdate( $date_format . ' ' . $time_format, $ttime_nn );

							if ( empty( $last_name ) ) {
								$last_name = $user->data->user_login;
							}
							if ( empty( $last_name ) ) {
								$last_name = $user->data->user_email;
							}

							$fullName        = $first_name . ' ' . $last_name;
							$billing_phone   = get_user_meta( $user->data->ID, 'billing_phone', true );
							$account_details = new \stdClass();
							$account_details->EmailAddress     = isset( $user->data->user_email ) ? $user->data->user_email : '';
							$account_details->U_Wk_Woo_User_Id = (string) $user_id;
							$urlparts = wp_parse_url( home_url() );
							$domain   = isset( $urlparts['host'] ) ? $urlparts['host'] : '';
							$account_details->U_Wk_Woo_Store_Key = $wksap_config->sap_prefix . '_' . $domain;
							$account_details->Cellular           = $billing_phone;
							$account_details->City        = isset( $user_meta['billing_city'][0] ) ? $user_meta['billing_city'][0] : '';
							$account_details->County      = isset( $user_meta['billing_country'][0] ) ? $user_meta['billing_country'][0] : '';
							$account_details->Country     = isset( $user_meta['billing_country'][0] ) ? $user_meta['billing_country'][0] : '';
							$account_details->ZipCode     = isset( $user_meta['billing_postcode'][0] ) ? $user_meta['billing_postcode'][0] : '';
							$account_details->BillToState = isset( $user_meta['billing_state'][0] ) ? $user_meta['billing_state'][0] : '';
							$account_details->Address     = $billing_address_1 . ' ' . $billing_address_2;
							$account_details->MailCity    = isset( $user_meta['shipping_city'][0] ) ? $user_meta['shipping_city'][0] : '';
							$account_details->MailCounty  = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
							$account_details->MailCountry = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
							$account_details->MailZipCode = isset( $user_meta['shipping_postcode'][0] ) ? $user_meta['shipping_postcode'][0] : '';
							$account_details->ShipToState = isset( $user_meta['shipping_state'][0] ) ? $user_meta['shipping_state'][0] : '';
							$account_details->MailAddress = $shipping_address_1 . ' ' . $shipping_address_2;
							$account_details->SubjectToWithholdingTax = esc_attr( 'boNO' );
							$sap_account_id = get_user_meta( $user_id, 'wk_sap_user_id', true );

							if ( ! empty( $sap_account_id ) ) {
								$account_details->CardCode = $sap_account_id;
							} elseif ( $wksap_config->sap_prefix ) {
								$account_details->CardCode = $wksap_config->sap_prefix . '_' . $user_id;
							} else {
								$account_details->CardCode = $user_id;
							}

							$account_details->CardName = $fullName;
							$account_details->CardType = esc_attr( 'cCustomer' );

							// Contact Person.
							$contact_person_data           = new \stdClass();
							$contact_person_data->CardCode = $account_details->CardCode;
							$contact_person_data->Name     = $account_details->CardName;
							$contact_person_data->MobilePhone = $account_details->Cellular;
							$contact_person_data->E_Mail      = $account_details->EmailAddress;
							$contact_person_data->FirstName   = $first_name;
							$contact_person_data->LastName    = $last_name;
							$contact_employees = $contact_person_data;

							// Check Existence at SAP.
							$sap_bp_query = esc_html__( 'EmailAddress eq ', 'sap-connector-for-woocommerce' ) . $account_details->EmailAddress;
							$sap_bp_data  = HELPERS\WKSAP_Connector::wksap_get_saps_object( 'BusinessPartners', rawurlencode( $sap_bp_query ) );

							if ( isset( $sap_bp_data->value ) ) {
								$sap_account_id = isset( $sap_bp_data->value[0]->CardCode ) ? $sap_bp_data->value[0]->CardCode : '';
							}

							// UPSERT operation.
							if ( ! $error_flag ) {
								if ( isset( $sap_account_id ) && ! empty( $sap_account_id ) ) {
									$result = HELPERS\WKSAP_Connector::wksap_update_saps_object( 'BusinessPartners', wp_json_encode( $account_details ), $sap_account_id );
									if ( isset( $result->error ) ) {
										$error_flag   = true;
										$error_list[] = esc_html__( 'Error in User Update:', 'sap-connector-for-woocommerce' ) . ( isset( $result->error->message->value ) ? $result->error->message->value : '' );
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $wksap_config->sap_prefix . '_' . $user_id );
										delete_user_meta( $user->data->ID, 'wk_sap_error' );
										foreach ( $error_list as $error ) {
											HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $error, true );
										}
									} else {
										$msg = esc_html__( 'Export - User Id: ', 'sap-connector-for-woocommerce' ) . ( $user->data->ID ) . esc_html__( ' Updated, SAP BP Id: ', 'sap-connector-for-woocommerce' ) . $sap_account_id;
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $wksap_config->sap_prefix . '_' . $user_id );
										delete_user_meta( $user->data->ID, 'wk_sap_error' );
										HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $msg, false );
									}
								} else {
									$account_details->ContactEmployees = $contact_employees;
									$result = HELPERS\WKSAP_Connector::wksap_insert_saps_object( 'BusinessPartners', wp_json_encode( $account_details ) );
									HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), esc_html__( 'LINE NUmber :- ', 'sap-connector-for-woocommerce' ) . __LINE__ . esc_html__( '   Here :- ', 'sap-connector-for-woocommerce' ) . wp_json_encode( $result ) );

									if ( ! empty( $result->error ) ) {
										$error_flag   = true;
										$error_list[] = esc_html__( 'Error in User Insert:', 'sap-connector-for-woocommerce' ) . $result->error->message->value;
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $wksap_config->sap_prefix . '_' . $user_id );
										delete_user_meta( $user->data->ID, 'wk_sap_error' );
										foreach ( $error_list as $error ) {
											HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $error, true );
										}
									} else {
										$msg = esc_html__( 'Export - User Id: ', 'sap-connector-for-woocommerce' ) . ( $user->data->ID ) . esc_html__( ' inserted, SAP BP Id: ', 'sap-connector-for-woocommerce' ) . $sap_account_id;
										HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $msg, false );
									}
								}
							}

							if ( ! $error_flag ) {
								$wk_sap_user_id = get_user_meta( $user->data->ID, 'wk_sap_user_id', true );
								if ( ! empty( $wk_sap_user_id ) ) {
									if ( ! $error_flag ) {
										$processed_item['updated'] = 1;
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $sap_account_id );
										delete_user_meta( $user->data->ID, 'wk_sap_error' );
									} else {
										$processed_item['errorsValue'] = 1;
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $sap_account_id );
										update_user_meta( $user->data->ID, 'wk_sap_error', implode( ',', $error_list ) );
									}
									$processed_item['sap_user_id'] = '';
								} else {
									if ( ! $error_flag ) {
										delete_user_meta( $user_id, 'wk_sap_error' );
									} else {
										add_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
									}
									$sap_account_id = $wksap_config->sap_prefix . '_' . $user_id;
									add_user_meta( $user_id, 'wk_sap_user_id', $sap_account_id );
									$processed_item['added']       = 1;
									$processed_item['sap_user_id'] = $sap_account_id;
								}
							} else {
								if ( ! $error_flag ) {
									delete_user_meta( $user_id, 'wk_sap_error' );
								} else {
									add_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
								}
								$sap_account_id = isset( $result->CardCode ) ? $result->CardCode : '';
								add_user_meta( $user_id, 'wk_sap_user_id', $sap_account_id );
								$processed_item['added']       = 1;
								$processed_item['sap_user_id'] = $sap_account_id;
							}
						}
					} catch ( \Exception $e ) {
						$error        = $e->getMessage();
						$error_list[] = $error;
						$error_flag   = true;
						$log          = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();
						HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $log, true );
					}

					if ( $error_flag ) {
						$error_arr = wksap_wp_sap_object_to_array( $error_list );
						if ( isset( $error_arr[0] ) && is_array( $error_arr[0] ) && count( $error_arr[0] ) > 0 ) {
							$error_arr = $error_arr[0];
						}
						$processed_item['error']  = implode( ',', $error_arr );
						$processed_item['status'] = '0';
					}

					if ( $user_id && ! empty( $error_list ) ) {
						$wk_sap_error = get_user_meta( $user_id, 'wk_sap_error', true );
						if ( isset( $wk_sap_error ) ) {
							$error_list = wksap_wp_sap_object_to_array( $error_list );
							update_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
						} else {
							add_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
						}
					}

					if ( $error_flag ) {
						$processed_item['errorsValue'] = 1;
					}

					// Update summary with current user's results.
					$summary['updated'] += $processed_item['updated'];
					$summary['added']   += $processed_item['added'];
					$summary['errors']  += $processed_item['errorsValue'];

					if ( ! empty( $processed_item['error'] ) ) {
						$summary['error_messages'][ $user_id ] = $processed_item['error'];
					}

					// Update progress.
					$processed = get_option( 'wksap_users_processed', 0 ) + 1;
					update_option( 'wksap_users_processed', $processed );
				}

				$message = sprintf(
					/* translators: 1: Total number of users processed 2: Number of users updated 3: Number of users added 4: Number of errors */
					_n(
						'Processed %1$d user: %2$d updated, %3$d added, %4$d error',
						'Processed %1$d users: %2$d updated, %3$d added, %4$d errors',
						$summary['total'],
						'sap-connector-for-woocommerce'
					),
					$summary['total'],
					$summary['updated'],
					$summary['added'],
					$summary['errors']
				);

				$job_msg = array(
					'code'    => 'success',
					'message' => $message,
					'summary' => $summary,
				);

				echo wp_json_encode( $job_msg );
				wp_die();
			}
		}

		/**
		 * Real time User sync.
		 *
		 * @param mixed $user_id User ID.
		 *
		 * @return void
		 */
		public function wksap_woo_sap_new_user( $user_id ) {
			$total_user_count = self::wksap_get_total_user();
			if ( $total_user_count > 50 ) {
				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( '  Only 50 Free Sync Allowed, For More Please Contact Us', 'sap-connector-for-woocommerce' ), true );
			} elseif ( ! wp_next_scheduled( 'real_time_user', array( false, $user_id ) ) ) {
				wp_schedule_single_event( time(), 'real_time_user', array( false, $user_id ) );
			}
		}

		/**
		 * Get Total User.
		 *
		 * @return int
		 */
		public static function wksap_get_total_user() {
			global $wpdb;

			// Get synced users count (users with SAP ID and no error).
			$wpdbs      = $wpdb;
			$user_table = $wpdbs->users;
			$meta_table = $wpdbs->usermeta;

			$synced_sql   = " SELECT COUNT(DISTINCT u.ID) FROM {$user_table} u INNER JOIN {$meta_table} m1 ON u.ID = m1.user_id AND m1.meta_key = %s
                LEFT JOIN {$meta_table} m2 ON u.ID = m2.user_id AND m2.meta_key = %s WHERE m2.meta_key IS NULL";
			$synced_items = intval( $wpdbs->get_var( $wpdbs->prepare( $synced_sql, 'wk_sap_user_id', 'wk_sap_error' ) ) );

			$error_sql   = " SELECT COUNT(DISTINCT u.ID) FROM {$user_table} u INNER JOIN {$meta_table} m1 ON u.ID = m1.user_id AND m1.meta_key = %s ";
			$error_items = intval( $wpdbs->get_var( $wpdbs->prepare( $error_sql, 'wk_sap_error' ) ) );

			return $synced_items + $error_items;
		}

		/**
		 * Export Background Job for the particular Object like Categories,Products,Users,Orders
		 *
		 * @return void
		 */
		public static function wksap_export_object_with_background_job() {
			if ( ! isset( self::$wksap_user_config->wksap_sync_user ) || ! self::$wksap_user_config->wksap_sync_user ) {
				return;
			}

			if ( ! check_ajax_referer( 'wksap_nonce', 'wksap_nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( 'nonce not verifyed', 'sap-connector-for-woocommerce' ), true );
				wp_send_json_error( esc_html__( 'Invalid Nonce', 'sap-connector-for-woocommerce' ) );
				wp_die();
			}

			$sync       = isset( $_POST['sync'] ) ? sanitize_text_field( wp_unslash( $_POST['sync'] ) ) : esc_attr( 'N' );
			$s_object   = isset( $_POST['sObject'] ) ? sanitize_text_field( wp_unslash( $_POST['sObject'] ) ) : '';
			$option     = isset( $_POST['option'] ) ? sanitize_text_field( wp_unslash( $_POST['option'] ) ) : '';
			$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
			$end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';
			$date_range = '';

			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				$date_range = array( $start_date, $end_date );
			}

			if ( 'user' === $s_object ) {
				// Debug: Get total users to see if any exist.
				$all_users = get_users( array( 'fields' => 'ID' ) );
				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), 'Total users in system: ' . count( $all_users ) );

				// Get user IDs based on date range or regular sync.
				if ( ! empty( $date_range ) && ! empty( $option ) ) {
					HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), 'Using date range method' );
					$user_ids = self::wksap_get_object_record_ids_by_date_range( 'user', $date_range, $option, $sync, -1 );
					HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), 'User IDs from date range: ' . wp_json_encode( $user_ids, true ) );
				} else {
					HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), 'Using regular sync method' );
					$user_ids = self::wksap_get_woo_user_ids( $sync, -1 );
					HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), 'User IDs from regular sync: ' . wp_json_encode( $user_ids, true ) );
				}

				$user_ids    = is_array( $user_ids ) ? $user_ids : array();
				$total_users = count( $user_ids );
				update_option( 'wksap_users_total', $total_users );
				update_option( 'wksap_users_processed', 0 );
				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( ' User Ids :- ', 'sap-connector-for-woocommerce' ) . $total_users );

				if ( empty( $user_ids ) ) {
					$warning_message = ! empty( $date_range )
						? esc_html__( 'There is no any user within the selected date range', 'sap-connector-for-woocommerce' )
						: esc_html__( 'There are no users to sync', 'sap-connector-for-woocommerce' );

					$job_msg = array(
						'code'    => 'warning',
						'message' => $warning_message,
					);
					echo wp_json_encode( $job_msg );
					wp_die();
				}

				// Initialize summary counters.
				$summary = array(
					'total'          => $total_users,
					'updated'        => 0,
					'added'          => 0,
					'errors'         => 0,
					'error_messages' => array(),
				);

				$wksap_config = get_option( 'wksap_config' );

				// Process each user synchronously.
				foreach ( $user_ids as $user_id ) {
					$processed_item = array(
						'total'       => 0,
						'updated'     => 0,
						'added'       => 0,
						'errorsValue' => 0,
						'sap_user_id' => '',
						'syncc_time'  => '',
						'error'       => '',
					);

					$error_flag = false;
					$error_list = array();
					$ttime_nn   = time();

					try {
						$user      = get_userdata( (int) $user_id );
						$user_meta = get_user_meta( (int) $user_id );
						$billing_address_1  = isset( $user_meta['billing_address_1'][0] ) ? $user_meta['billing_address_1'][0] : '';
						$billing_address_2  = isset( $user_meta['billing_address_2'][0] ) ? $user_meta['billing_address_2'][0] : '';
						$shipping_address_1 = isset( $user_meta['shipping_address_1'][0] ) ? $user_meta['shipping_address_1'][0] : '';
						$shipping_address_2 = isset( $user_meta['shipping_address_2'][0] ) ? $user_meta['shipping_address_2'][0] : '';

						if ( ! empty( $user ) ) {
							$sap_account_id          = '';
							$processed_item['total'] = 1;
							$first_name = get_user_meta( $user->data->ID, 'first_name', true );
							$last_name  = get_user_meta( $user->data->ID, 'last_name', true );

							update_user_meta( $user->data->ID, 'wk_sap_user_sync_time', time() );

							$date_format = get_option( 'date_format' );
							$time_format = get_option( 'time_format' );
							$processed_item['syncc_time'] = gmdate( $date_format . ' ' . $time_format, $ttime_nn );

							if ( empty( $last_name ) ) {
								$last_name = $user->data->user_login;
							}
							if ( empty( $last_name ) ) {
								$last_name = $user->data->user_email;
							}

							$fullName        = $first_name . ' ' . $last_name;
							$billing_phone   = get_user_meta( $user->data->ID, 'billing_phone', true );
							$account_details = new \stdClass();
							$account_details->EmailAddress     = isset( $user->data->user_email ) ? $user->data->user_email : '';
							$account_details->U_Wk_Woo_User_Id = (string) $user_id;
							$urlparts = wp_parse_url( home_url() );
							$domain   = isset( $urlparts['host'] ) ? $urlparts['host'] : '';
							$account_details->U_Wk_Woo_Store_Key = $wksap_config->sap_prefix . '_' . $domain;
							$account_details->Cellular           = $billing_phone;
							$account_details->City        = isset( $user_meta['billing_city'][0] ) ? $user_meta['billing_city'][0] : '';
							$account_details->County      = isset( $user_meta['billing_country'][0] ) ? $user_meta['billing_country'][0] : '';
							$account_details->Country     = isset( $user_meta['billing_country'][0] ) ? $user_meta['billing_country'][0] : '';
							$account_details->ZipCode     = isset( $user_meta['billing_postcode'][0] ) ? $user_meta['billing_postcode'][0] : '';
							$account_details->BillToState = isset( $user_meta['billing_state'][0] ) ? $user_meta['billing_state'][0] : '';
							$account_details->Address     = $billing_address_1 . ' ' . $billing_address_2;
							$account_details->MailCity    = isset( $user_meta['shipping_city'][0] ) ? $user_meta['shipping_city'][0] : '';
							$account_details->MailCounty  = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
							$account_details->MailCountry = isset( $user_meta['shipping_country'][0] ) ? $user_meta['shipping_country'][0] : '';
							$account_details->MailZipCode = isset( $user_meta['shipping_postcode'][0] ) ? $user_meta['shipping_postcode'][0] : '';
							$account_details->ShipToState = isset( $user_meta['shipping_state'][0] ) ? $user_meta['shipping_state'][0] : '';
							$account_details->MailAddress = $shipping_address_1 . ' ' . $shipping_address_2;
							$account_details->SubjectToWithholdingTax = esc_attr( 'boNO' );
							$sap_account_id = get_user_meta( $user_id, 'wk_sap_user_id', true );

							if ( ! empty( $sap_account_id ) ) {
								$account_details->CardCode = $sap_account_id;
							} elseif ( $wksap_config->sap_prefix ) {
								$account_details->CardCode = $wksap_config->sap_prefix . '_' . $user_id;
							} else {
								$account_details->CardCode = $user_id;
							}

							$account_details->CardName = $fullName;
							$account_details->CardType = esc_attr( 'cCustomer' );

							// Contact Person.
							$contact_person_data           = new \stdClass();
							$contact_person_data->CardCode = $account_details->CardCode;
							$contact_person_data->Name     = $account_details->CardName;
							$contact_person_data->MobilePhone = $account_details->Cellular;
							$contact_person_data->E_Mail      = $account_details->EmailAddress;
							$contact_person_data->FirstName   = $first_name;
							$contact_person_data->LastName    = $last_name;
							$contact_employees = $contact_person_data;

							// Check Existence at SAP.
							$sap_bp_query = esc_html__( 'EmailAddress eq ', 'sap-connector-for-woocommerce' ) . $account_details->EmailAddress;
							$sap_bp_data  = HELPERS\WKSAP_Connector::wksap_get_saps_object( 'BusinessPartners', rawurlencode( $sap_bp_query ) );

							if ( isset( $sap_bp_data->value ) ) {
								$sap_account_id = isset( $sap_bp_data->value[0]->CardCode ) ? $sap_bp_data->value[0]->CardCode : '';
							}

							// UPSERT operation.
							if ( ! $error_flag ) {
								if ( isset( $sap_account_id ) && ! empty( $sap_account_id ) ) {
									$result = HELPERS\WKSAP_Connector::wksap_update_saps_object( 'BusinessPartners', wp_json_encode( $account_details ), $sap_account_id );
									if ( isset( $result->error ) ) {
										$error_flag   = true;
										$error_list[] = esc_html__( 'Error in User Update:', 'sap-connector-for-woocommerce' ) . ( isset( $result->error->message->value ) ? $result->error->message->value : '' );
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $wksap_config->sap_prefix . '_' . $user_id );
										delete_user_meta( $user->data->ID, 'wk_sap_error' );
										foreach ( $error_list as $error ) {
											HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $error, true );
										}
									} else {
										$msg = esc_html__( 'Export - User Id: ', 'sap-connector-for-woocommerce' ) . ( $user->data->ID ) . esc_html__( ' Updated, SAP BP Id: ', 'sap-connector-for-woocommerce' ) . $sap_account_id;
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $wksap_config->sap_prefix . '_' . $user_id );
										delete_user_meta( $user->data->ID, 'wk_sap_error' );
										HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $msg, false );
									}
								} else {
									$account_details->ContactEmployees = $contact_employees;
									$result = HELPERS\WKSAP_Connector::wksap_insert_saps_object( 'BusinessPartners', wp_json_encode( $account_details ) );
									HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), esc_html__( 'LINE NUmber :- ', 'sap-connector-for-woocommerce' ) . __LINE__ . esc_html__( '   Here :- ', 'sap-connector-for-woocommerce' ) . wp_json_encode( $result ) );

									if ( ! empty( $result->error ) ) {
										$error_flag   = true;
										$error_list[] = esc_html__( 'Error in User Insert:', 'sap-connector-for-woocommerce' ) . $result->error->message->value;
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $wksap_config->sap_prefix . '_' . $user_id );
										delete_user_meta( $user->data->ID, 'wk_sap_error' );
										foreach ( $error_list as $error ) {
											HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $error, true );
										}
									} else {
										$msg = esc_html__( 'Export - User Id: ', 'sap-connector-for-woocommerce' ) . ( $user->data->ID ) . esc_html__( ' inserted, SAP BP Id: ', 'sap-connector-for-woocommerce' ) . $sap_account_id;
										HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $msg, false );
									}
								}
							}

							if ( ! $error_flag ) {
								$wk_sap_user_id = get_user_meta( $user->data->ID, 'wk_sap_user_id', true );
								if ( ! empty( $wk_sap_user_id ) ) {
									if ( ! $error_flag ) {
										$processed_item['updated'] = 1;
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $sap_account_id );
										delete_user_meta( $user->data->ID, 'wk_sap_error' );
									} else {
										$processed_item['errorsValue'] = 1;
										update_user_meta( $user->data->ID, 'wk_sap_user_id', $sap_account_id );
										update_user_meta( $user->data->ID, 'wk_sap_error', implode( ',', $error_list ) );
									}
									$processed_item['sap_user_id'] = '';
								} else {
									if ( ! $error_flag ) {
										delete_user_meta( $user_id, 'wk_sap_error' );
									} else {
										add_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
									}
									$sap_account_id = $wksap_config->sap_prefix . '_' . $user_id;
									add_user_meta( $user_id, 'wk_sap_user_id', $sap_account_id );
									$processed_item['added']       = 1;
									$processed_item['sap_user_id'] = $sap_account_id;
								}
							} else {
								if ( ! $error_flag ) {
									delete_user_meta( $user_id, 'wk_sap_error' );
								} else {
									add_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
								}
								$sap_account_id = isset( $result->CardCode ) ? $result->CardCode : '';
								add_user_meta( $user_id, 'wk_sap_user_id', $sap_account_id );
								$processed_item['added']       = 1;
								$processed_item['sap_user_id'] = $sap_account_id;
							}
						}
					} catch ( \Exception $e ) {
						$error        = $e->getMessage();
						$error_list[] = $error;
						$error_flag   = true;
						$log          = $e->getFile() . ' ' . $e->getLine() . ' ' . $e->getMessage();
						HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), $log, true );
					}

					if ( $error_flag ) {
						$error_arr = wksap_wp_sap_object_to_array( $error_list );
						if ( isset( $error_arr[0] ) && is_array( $error_arr[0] ) && count( $error_arr[0] ) > 0 ) {
							$error_arr = $error_arr[0];
						}
						$processed_item['error']  = implode( ',', $error_arr );
						$processed_item['status'] = '0';
					}

					if ( $user_id && ! empty( $error_list ) ) {
						$wk_sap_error = get_user_meta( $user_id, 'wk_sap_error', true );
						if ( isset( $wk_sap_error ) ) {
							$error_list = wksap_wp_sap_object_to_array( $error_list );
							update_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
						} else {
							add_user_meta( $user_id, 'wk_sap_error', implode( ',', $error_list ) );
						}
					}

					if ( $error_flag ) {
						$processed_item['errorsValue'] = 1;
					}

					// Update summary with current user's results.
					$summary['updated'] += $processed_item['updated'];
					$summary['added']   += $processed_item['added'];
					$summary['errors']  += $processed_item['errorsValue'];

					if ( ! empty( $processed_item['error'] ) ) {
						$summary['error_messages'][ $user_id ] = $processed_item['error'];
					}

					// Update progress.
					$processed = get_option( 'wksap_users_processed', 0 ) + 1;
					update_option( 'wksap_users_processed', $processed );
				}

				// Prepare final response.
				$message = sprintf(
					/* translators: 1: Total number of users processed 2: Number of users updated 3: Number of users added 4: Number of errors */
					_n(
						'Processed %1$d user: %2$d updated, %3$d added, %4$d error',
						'Processed %1$d users: %2$d updated, %3$d added, %4$d errors',
						$summary['total'],
						'sap-connector-for-woocommerce'
					),
					$summary['total'],
					$summary['updated'],
					$summary['added'],
					$summary['errors']
				);

				$job_msg = array(
					'code'    => 'success',
					'message' => $message,
					'summary' => $summary,
				);

				echo wp_json_encode( $job_msg );
				wp_die();
			}
		}

		/**
		 * Wksap create background job for objects.
		 *
		 * @param  mixed $s_object $s_object.
		 * @param  mixed $item_type $item_type.
		 * @param  mixed $date_range $date_range.
		 * @param  mixed $option $option.
		 *
		 * @return void
		 */
		public static function wksap_create_background_job_for_objects( $s_object, $item_type, $date_range = '', $option = '' ) {
			$sync_all = '';

			if ( 'user' === $s_object ) {
				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . ' ' . wp_json_encode( $date_range ) . ' ' . wp_json_encode( $option ) );
				if ( ! empty( $date_range ) && ! empty( $option ) ) {
					$sync_all = new WKSAP_Synchronized_Process_User();
					$user_ids = self::wksap_get_object_record_ids_by_date_range( 'user', $date_range, $option, $item_type, -1 );
					HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), esc_html__( ' in date wksap_create_background_job_for_objects File Export user Ids :- ', 'sap-connector-for-woocommerce' ) . wp_json_encode( $user_ids ), true );
					if ( ! empty( $user_ids ) ) {
						HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( '  here 1', 'sap-connector-for-woocommerce' ) );
						update_option( 'wksap_users_total', count( $user_ids ) );
						update_option( 'wksap_users_processed', count( $user_ids ) );
						if ( ! empty( $sync_all ) ) {
							$data = array(
								'date_range' => $date_range,
								'option'     => $option,
								'itemType'   => $item_type,
							);
							HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( ' data ', 'sap-connector-for-woocommerce' ) . wp_json_encode( $data ) );
							HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( ' $item_type ', 'sap-connector-for-woocommerce' ) . wp_json_encode( $item_type ) );

							$sync_all->push_to_queue( $data );
							$sync_all->save()->dispatch();
						} else {
							HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( '   here 3', 'sap-connector-for-woocommerce' ) );
						}
					}
				} else {
					$sync_all = new WKSAP_Synchronized_Process_User();
					$user_ids = self::wksap_get_woo_user_ids( $item_type, -1 );
					HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), esc_html__( ' in wksap_create_background_job_for_objects File Export user Ids :- ', 'sap-connector-for-woocommerce' ) . wp_json_encode( $user_ids ) );
					if ( ! empty( $user_ids ) ) {
						update_option( 'wksap_users_total', count( $user_ids ) );
						update_option( 'wksap_users_processed', count( $user_ids ) );
						if ( ! empty( $sync_all ) ) {
							$data = array( 'itemType' => $item_type );
							$sync_all->push_to_queue( $data );
							$sync_all->save()->dispatch();
						}
					}
				}
			}
		}

		/**
		 * Wksap stop background job.
		 *
		 * @return void
		 */
		public static function wksap_stop_background_job() {

			if ( ! check_ajax_referer( 'wksap_nonce', 'wksap_nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( 'nonce not verifyed', 'sap-connector-for-woocommerce' ), true );
				wp_send_json_error( esc_html__( 'Invalid Nonce', 'sap-connector-for-woocommerce' ) );
				wp_die();
			}

			$s_object = isset( $_POST['sObject'] ) ? sanitize_text_field( wp_unslash( $_POST['sObject'] ) ) : '';

			if ( isset( $s_object ) && 'user' === $s_object ) {
				wp_clear_scheduled_hook( 'wksap_user_background_process_cron' );
				update_option( 'wksap_disable_stop_user_background_button', true );
				$stop_job_msg = array(
					'code'    => 'success',
					'message' => esc_html__( 'Background Process For Order is Stopped', 'sap-connector-for-woocommerce' ),
				);
				echo wp_json_encode( $stop_job_msg );
				wp_die();
			}
		}

		/**
		 * Wksap get object record ids by date range.
		 *
		 * @param mixed $s_object $s_object.
		 * @param mixed $date_range $date_range.
		 * @param mixed $option $option.
		 * @param mixed $sync $sync.
		 * @param mixed $post_per_page $post_per_page.
		 *
		 * @return mixed
		 */
		public static function wksap_get_object_record_ids_by_date_range( $s_object, $date_range, $option, $sync, $post_per_page = -1 ) {
			global $wpdb;

			$wpdbs         = $wpdb;
			$sync          = isset( $sync ) ? $sync : 'A';
			$post_per_page = ! empty( $post_per_page ) ? absint( $post_per_page ) : -1;

			if ( empty( $s_object ) || 'user' !== $s_object || empty( $date_range ) || count( $date_range ) < 2 ) {
				return false;
			}

			// Parse and validate dates.
			$fromdate_raw = strtotime( trim( $date_range[0] ) );
			$enddate_raw  = strtotime( trim( $date_range[1] ) );

			if ( false === $fromdate_raw || false === $enddate_raw ) {
				return false;
			}

			$fromdate = gmdate( 'Y-m-d H:i:s', $fromdate_raw );
			$enddate  = gmdate( 'Y-m-d H:i:s', $enddate_raw );

			if ( 'A' === $sync ) {
				$user_table = $wpdbs->users;
				$meta_table = $wpdbs->usermeta;

				$limit_sql = '';
				$query     = $wpdbs->prepare(
					"SELECT DISTINCT u.ID
					 FROM {$user_table} u
					 INNER JOIN {$meta_table} um ON u.ID = um.user_id
					 WHERE u.user_registered BETWEEN %s AND %s
					 {$limit_sql}",
					$fromdate,
					$enddate
				);

				$results = $wpdbs->get_col( $query );

				return ! empty( $results ) ? $results : false;
			}

			// Invalid sync type.
			wp_send_json( '' );
		}

		/**
		 * Delete Mapping Data.
		 *
		 * @return mixed
		 */
		public static function wksap_delete_mapping_data() {
			if ( ! isset( self::$wksap_user_config->wksap_sync_user ) || ! self::$wksap_user_config->wksap_sync_user ) {
				return;
			}
			global $wpdb;
			$wpdbs = $wpdb;
			if ( ! check_ajax_referer( 'wksap_nonce', 'wksap_nonce', false ) || ! current_user_can( 'manage_options' ) ) {
				HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'Debug', 'sap-connector-for-woocommerce' ), __LINE__ . esc_html__( ' nonce verification failed  ', 'sap-connector-for-woocommerce' ) );
				wp_send_json_error( esc_html__( 'Invalid Nonce', 'sap-connector-for-woocommerce' ) );
				wp_die();
			}

			$item_ids = isset( $_REQUEST['deleteIds'] ) ? array_map( 'absint', $_REQUEST['deleteIds'] ) : 0;
			$process  = isset( $_REQUEST['process'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['process'] ) ) : '';
			$s_object = isset( $_REQUEST['sObject'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['sObject'] ) ) : '';

			HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), esc_html__( ' wksap_delete_mapping_data process :- ', 'sap-connector-for-woocommerce' ) . $process );
			HELPERS\WKSAP_Helper::wksap_generate_log( esc_html__( 'User', 'sap-connector-for-woocommerce' ), esc_html__( ' wksap_delete_mapping_data user :- ', 'sap-connector-for-woocommerce' ) . $s_object );

			if ( 'user' === $s_object ) {
				if ( 'delete-user' === $process ) {
					foreach ( $item_ids as $id ) {
						delete_user_meta( $id, 'wk_sap_user_id' );
						delete_user_meta( $id, 'wk_sap_error' );
						delete_user_meta( $id, 'wk_sap_user_sync_time' );
					}
					wp_send_json_success( esc_html__( 'User Deleted Successfully, Please Refresh Page', 'sap-connector-for-woocommerce' ) );
				}
				if ( 'delete-all-user' === $process ) {
					$is_exist = $wpdbs->get_results( $wpdbs->prepare( "SELECT umeta_id FROM {$wpdbs->usermeta} WHERE meta_key = %s OR meta_key = %s OR meta_key = %s", 'wk_sap_user_id', 'wk_sap_error', 'wk_sap_user_sync_time' ) );

					if ( ! empty( $is_exist ) ) {
						delete_metadata( 'user', 0, 'wk_sap_user_id', false, true );
						delete_metadata( 'user', 0, 'wk_sap_error', false, true );
						delete_metadata( 'user', 0, 'wk_sap_user_sync_time', false, true );
						wp_send_json_success( esc_html__( 'All User Deleted Successfully, Please Refresh Page', 'sap-connector-for-woocommerce' ) );
					} else {
						wp_send_json_error( esc_html__( 'No User Found', 'sap-connector-for-woocommerce' ) );
					}
				}
			}
			wp_die();
		}

		/**
		 * Main WooCommerce SAP Connector Instance.
		 *
		 * Ensures only one instance of WooCommerce SAP Connector is loaded or can be loaded.
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


