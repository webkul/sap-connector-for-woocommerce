<?php
/**
 * Users View handler.
 *
 * @package SAP_Connector_For_WooCommerce
 */

namespace WKSAP\TEMPLATES\ADMIN;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

use WKSAP\INCLUDES;

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP_Users_View' ) ) {
	/**
	 * Class WKSAP_Users_View.
	 */
	class WKSAP_Users_View {
		/**
		 * Instance variable.
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Display method to create view elements,
		 *
		 * @return void
		 */
		public function wksap_display() {
			global $wpdb;

			$wpdbs = $wpdb;
			$wksap_user_config = get_option( 'wksap_user_config', array() );

			if ( empty( $wksap_user_config ) || is_array( $wksap_user_config ) ) {
				$wksap_user_config = (object) array( 'wksap_sync_user' => false );
			}

			$wksap_globals = array(
				'wksap_show_button_sync' => $wksap_user_config->wksap_sync_user ?? false,
			);

			add_screen_option(
				'per_page',
				array(
					'default' => 100,
					'option'  => 'users_per_page',
				)
			);

			$wksap_list_table = new WKSAP_User_Table();

			// Background process data.
			$total_jobs      = get_option( 'wksap_users_total', 0 );
			$processed_jobs  = get_option( 'wksap_users_processed', 0 );
			$table_data      = $wksap_list_table->table_data();
			$total_user_data = is_array( $table_data ) ? count( $table_data ) : 0;

			$args       = array( 'fields' => 'ID' );
			$user_query = new \WP_User_Query( $args );
			$total_user_count = $user_query->get_total();

			$synced_items = $wpdbs->get_var(
				$wpdbs->prepare(
					"SELECT COUNT(DISTINCT u.ID) FROM {$wpdbs->users} u INNER JOIN {$wpdbs->usermeta} um1 ON u.ID = %1s AND um1.meta_key = %s
				    LEFT JOIN {$wpdbs->usermeta} um2 ON u.ID = %2s AND um2.meta_key = %s WHERE um2.umeta_id IS NULL",
					'um1.user_id',
					'wk_sap_user_id',
					'um2.user_id',
					'wk_sap_error'
				)
			);

			// Error items query.
			$error_items = $wpdbs->get_var(
				$wpdbs->prepare(
					"SELECT COUNT(DISTINCT u.ID) FROM {$wpdbs->users} u
                    INNER JOIN {$wpdbs->usermeta} um ON u.ID = %1s AND um.meta_key = %s",
					'um.user_id',
					'wk_sap_error'
				)
			);

			// Unsynced items query.
			$unsynced_items = $wpdbs->get_var(
				$wpdbs->prepare(
					"SELECT COUNT(DISTINCT u.ID)
                    FROM {$wpdbs->users} u
                    LEFT JOIN {$wpdbs->usermeta} um1 ON u.ID = %1s AND um1.meta_key = %s
                    LEFT JOIN {$wpdbs->usermeta} um2 ON u.ID = %2s AND um2.meta_key = %s
                    WHERE um1.umeta_id IS NULL AND um2.umeta_id IS NULL",
					'um1.user_id',
					'wk_sap_user_id',
					'um2.user_id',
					'wk_sap_error'
				)
			);
			$synced_info    = array(
				'total_items'    => $total_user_count,
				'synced_items'   => $synced_items,
				'unsynced_items' => $unsynced_items,
				'error_items'    => $error_items,
			);

			$total_user_export = $synced_info['synced_items'] + $synced_info['error_items'];
			$item_type         = 'A';

			if ( $item_type ) {
				if ( isset( $_REQUEST['wksap_user_nonce'] ) ) {
					$wksap_user_list_nonce = sanitize_text_field( wp_unslash( $_REQUEST['wksap_user_nonce'] ) );
					if ( ! empty( $wksap_user_list_nonce ) && wp_verify_nonce( $wksap_user_list_nonce, 'wksap_sync_user_action' ) && current_user_can( 'manage_options' ) ) {
						isset( $_REQUEST['item_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['item_type'] ) ) : 'A';
					}
				}
			}

			?>
			<div class="wrap" id="wksap_my_wrap">
				<div class="wksap-view wrap clearfix" id="wksap-first-view-controller-view-root" data-type="wksap-view">
					<div class="wksap-view clearfix wksap-header-view clearfix" id="wksap-first-view-controller-header-view" data-type="wksap-view">
						<div class="wksap-vc-header-icon" data-type="wksap-header-view"></div>
						<h1><?php esc_html_e( 'Synchronize Users', 'sap-connector-for-woocommerce' ); ?></h1>
						<div class="wksap-vc-header-after-title"></div>
						<br>
						<?php
						if ( $total_user_count > 0 ) {
							if ( ! $wksap_user_config->wksap_sync_user ) {
								INCLUDES\WKSAP_MAIN::wksap_show_notice(
									'notice notice-error',
									esc_html__( 'Sync Error:', 'sap-connector-for-woocommerce' ),
									esc_html__( 'User Sync Disabled in Settings all actions runs but not perform.', 'sap-connector-for-woocommerce' )
								);
								$wksap_globals['wksap_show_button_sync'] = false;
							} elseif ( $total_user_export > 50 ) {
								INCLUDES\WKSAP_MAIN::wksap_show_notice(
									'notice notice-error',
									esc_html__( 'Sync Error:', 'sap-connector-for-woocommerce' ),
									sprintf(
										/* translators: %s: Support email link */
										esc_html__( 'Only 50 Free Sync Allowed, For More Please Contact Us %s', 'sap-connector-for-woocommerce' ),
										'<a href="mailto:support@webkul.com">support@webkul.com</a>'
									)
								);
								$wksap_globals['wksap_show_button_sync'] = false;
							}

							if ( $wksap_globals['wksap_show_button_sync'] ) {
								$is_background_running = wp_next_scheduled( 'wksap_user_background_process_cron' );

								if ( $is_background_running && get_option( 'wksap_disable_stop_user_background_button', true ) ) {
									INCLUDES\WKSAP_MAIN::wksap_show_notice(
										'notice notice-success',
										esc_html__( 'Background process for user is running', 'sap-connector-for-woocommerce' ),
										sprintf(
											/* translators: %1$d: Number of processed jobs, %2$d: Total number of jobs */
											esc_html__( 'Job processed %1$d out of %2$d', 'sap-connector-for-woocommerce' ),
											$processed_jobs,
											$total_jobs
										)
									);
									printf(
										'<a href="javascript:void(0);" id="wksap-export-stop-user-button" class="page-title-action wksap-mr-25">
											<span class="dashicons dashicons-dismiss wksap-mr-100"></span> %s
										</a>',
										esc_html__( 'Stop Background Job', 'sap-connector-for-woocommerce' )
									);
								} else {
									if ( $is_background_running ) {
										INCLUDES\WKSAP_MAIN::wksap_show_notice(
											'notice notice-success',
											esc_html__( 'Background process for user is', 'sap-connector-for-woocommerce' ),
											esc_html__( 'Stopped', 'sap-connector-for-woocommerce' )
										);
									}
									$this->wksap_output_export_button( $total_user_data );
								}
							} else {
								$this->wksap_output_disabled_export_button();
							}
						}
						?>
					</div>
				</div>
				<?php
				if ( $total_user_count > 0 ) :
					$admin_url = admin_url( 'admin.php' );
					?>
					<p>
						<ul class="subsubsub">
							<li class="all">
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'item_type', 'A', $admin_url . '?page=wksap_sync_user' ), 'wksap_sync_user_action', 'wksap_user_nonce' ) ); ?>">
									<input type="checkbox" name="total_items" id="elm_total_items" value="<?php echo esc_attr( 'Y' ); ?>"
										<?php checked( $item_type, 'A' ); ?> disabled>
									<?php
									printf(
										/* translators: %d: Total number of all items */
										esc_html__( 'All (%d)', 'sap-connector-for-woocommerce' ),
										esc_attr( $synced_info['total_items'] )
									);
									?>
								</a> |
							</li>
							<li class="mine">
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'item_type', 'S', $admin_url . '?page=wksap_sync_user' ), 'wksap_sync_user_action', 'wksap_user_nonce' ) ); ?>">
									<input type="checkbox" name="synced_items" id="wksap_elm_synced_items" value="<?php echo esc_attr( 'Y' ); ?>"
										<?php checked( in_array( $item_type, array( 'A', 'S' ), true ) ); ?> disabled>
									<?php
									printf(
										/* translators: %d: Total number of synced items */
										esc_html__( 'Synced items (%d)', 'sap-connector-for-woocommerce' ),
										esc_attr( $synced_info['synced_items'] )
									);
									?>
								</a> |
							</li>
							<li class="publish">
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'item_type', 'U', $admin_url . '?page=wksap_sync_user' ), 'wksap_sync_user_action', 'wksap_user_nonce' ) ); ?>">
									<input type="checkbox" name="unsynced_items" id="wksap_elm_unsynced_items" value="<?php echo esc_attr( 'Y' ); ?>"
										<?php checked( in_array( $item_type, array( 'A', 'U' ), true ) ); ?> disabled>
									<?php
									printf(
										/* translators: %d: Total number of unsynced items */
										esc_html__( 'Unsynced items (%d)', 'sap-connector-for-woocommerce' ),
										esc_attr( $synced_info['unsynced_items'] )
									);
									?>
								</a> |
							</li>
							<li class="error_type">
								<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'item_type', 'E', $admin_url . '?page=wksap_sync_user' ), 'wksap_sync_user_action', 'wksap_user_nonce' ) ); ?>">
									<input type="checkbox" name="error_items" id="wksap_elm_error_items" value="<?php echo esc_attr( 'Y' ); ?>"
										<?php checked( in_array( $item_type, array( 'A', 'E' ), true ) ); ?> disabled>
									<?php
									printf(
										/* translators: %d: Total number of items with sync errors */
										esc_html__( 'Error in User Sync (%d)', 'sap-connector-for-woocommerce' ),
										esc_attr( $synced_info['error_items'] )
									);
									?>
								</a>
							</li>
						</ul>
					</p>
					<form method="get">
						<input type="hidden" name="page" value="wksap_sync_user" />
						<?php wp_nonce_field( 'wksap_sync_user_action', 'wksap_user_nonce' ); ?>
						<?php $wksap_list_table->search_box( esc_html__( 'Search', 'sap-connector-for-woocommerce' ), 'usersearch' ); ?>
						<?php
							$wksap_list_table->prepare_items();
							$wksap_list_table->display();
						?>
					</form>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Helper method for export button.
		 *
		 * @param array $total_user_data Total user data.
		 *
		 * @return void
		 */
		private function wksap_output_export_button( $total_user_data ) {
			$class = $total_user_data > 0 ? '' : '';
			printf(
				'<a href="javascript:void(0);" id="wksap_syncronize_user_button" class="wksap-mr-15 page-title-action %s">
					%s
					<span class="spinner wksap-pf-100" id="wksap_syncronizeUser_id"></span>
				</a>',
				esc_attr( $class ),
				esc_html__( 'Export Users', 'sap-connector-for-woocommerce' )
			);
		}

		/**
		 * Helper method for disabled export button.
		 *
		 * @return void
		 */
		private function wksap_output_disabled_export_button() {
			$total_jobs = get_option( 'wksap_users_total', 0 );
			printf(
				'<a href="javascript:void(0);" id="" class="page-title-action wksap-button-disabled wksap-mr-25">
					%s
					<span class="spinner wksap-pf-100" id=""></span>
				</a>',
				esc_html__( 'Export Users', 'sap-connector-for-woocommerce' )
			);
			if ( ! wp_next_scheduled( 'wksap_user_background_process_cron' ) && $total_jobs ) {
				$processed_jobs = get_option( 'wksap_users_processed', 0 );
				INCLUDES\WKSAP_MAIN::wksap_show_notice(
					'notice notice-success',
					esc_html__( 'Background process for user is running', 'sap-connector-for-woocommerce' ),
					sprintf(
						/* translators: %1$d: Number of processed jobs, %2$d: Total number of jobs */
						esc_html__( 'Job processed %1$d out of %2$d', 'sap-connector-for-woocommerce' ),
						$processed_jobs,
						$total_jobs
					)
				);
			}
		}

		/**
		 * Return a singleton instance of class.
		 *
		 * @brief Singleton.
		 *
		 * @return object
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
