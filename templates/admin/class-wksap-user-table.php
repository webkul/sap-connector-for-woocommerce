<?php
/**
 * User Table handler class.
 *
 * @package SAP_Connector_For_WooCommerce
 */

namespace WKSAP\TEMPLATES\ADMIN;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

// Check if class exists.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Check if class exists.
if ( ! class_exists( 'WKSAP_User_Table' ) ) {
	/**
	 * Class WKSAP_User_Table.
	 */
	class WKSAP_User_Table extends \WP_List_Table {
		/**
		 * Cache for user meta data to reduce database queries.
		 *
		 * @var array
		 */
		private $user_meta_cache = array();

		/**
		 * Table Data - Optimized version.
		 *
		 * @return array
		 */
		private function table_data() {

			$orderby   = '';
			$item_type = 'A';
			$search    = '';
			$order     = '';

			if ( isset( $_REQUEST['wksap_user_nonce'] ) ) {
				$wksap_user_list_nonce = sanitize_text_field( wp_unslash( $_REQUEST['wksap_user_nonce'] ) );
				if ( ! empty( $wksap_user_list_nonce ) && wp_verify_nonce( $wksap_user_list_nonce, 'wksap_sync_user_action' ) && current_user_can( 'manage_options' ) ) {
					$orderby   = empty( $_REQUEST['orderby'] ) ? 'ID' : sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) );
					$item_type = isset( $_REQUEST['item_type'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['item_type'] ) ) : 'A';
					$search    = empty( $_REQUEST['s'] ) ? '' : sanitize_text_field( wp_unslash( $_REQUEST['s'] ) );
					$order     = empty( $_REQUEST['order'] ) ? 'ASC' : sanitize_text_field( wp_unslash( $_REQUEST['order'] ) );
				}
			}

			$current_page = $this->get_pagenum();
			$per_page     = $this->get_items_per_page( 'wksap_items_per_page' );

			$args = array(
				'item_type'    => $item_type,
				'per_page'     => $per_page,
				'current_page' => $current_page,
			);

			if ( ! empty( $orderby ) ) {
				$args['orderby'] = $orderby;
			}
			if ( ! empty( $search ) ) {
				$args['search'] = $search;
			}
			if ( ! empty( $order ) ) {
				$args['order'] = $order;
			}

			$user_ids    = $this->get_filtered_user_ids( $args );
			$total_users = $this->get_filtered_users_count( $args );

			// Set pagination.
			$this->set_pagination_args(
				array(
					'total_items' => $total_users,
					'per_page'    => $per_page,
					'total_pages' => ceil( $total_users / $per_page ),
				)
			);

			$this->preload_user_meta( $user_ids );

			$data     = array();
			$seen_ids = array();

			foreach ( $user_ids as $user_data ) {
				if ( ! in_array( $user_data->ID, $seen_ids, true ) ) {
					$data[]     = $this->build_user_row_data( $user_data );
					$seen_ids[] = $user_data->ID;
				}
			}

			return $data;
		}
		/**
		 * Get filtered user IDs using optimized SQL.
		 *
		 * @param array $args Request data.
		 *
		 * @return array
		 */
		private function get_filtered_user_ids( $args ) {
			$offset       = ( $args['current_page'] - 1 ) * $args['per_page'];
			$search       = isset( $args['search'] ) ? trim( sanitize_text_field( $args['search'] ) ) : '';
			$item_type    = isset( $args['item_type'] ) ? $args['item_type'] : 'A';
			$limit        = isset( $args['per_page'] ) ? $args['per_page'] : 20;
			$sort_orderby = isset( $args['orderby'] ) ? $args['orderby'] : 'ID';
			$sort_order   = isset( $args['order'] ) ? strtoupper( $args['order'] ) : 'ASC';

			$orderby = 'ID';

			$allowed_orderby = array(
				'username'     => 'user_login',
				'email'        => 'user_email',
				'woouserid'    => 'ID',
				'sapaccountid' => 'meta_value',
				'SyncedAt'     => 'meta_value',
			);

			if ( isset( $allowed_orderby[ $sort_orderby ] ) ) {
				$orderby = $allowed_orderby[ $sort_orderby ];
			}
			$meta_query = array( 'relation' => 'AND' );

			if ( 'U' === $item_type ) {
				$meta_query[] = array(
					'key'     => 'wk_sap_user_id',
					'compare' => 'NOT EXISTS',
				);
				$meta_query[] = array(
					'key'     => 'wk_sap_error',
					'compare' => 'NOT EXISTS',
				);
			} elseif ( 'E' === $item_type ) {
				$meta_query[] = array(
					'key'     => 'wk_sap_error',
					'compare' => 'EXISTS',
				);
			} elseif ( 'S' === $item_type ) {
				$meta_query[] = array(
					'key'     => 'wk_sap_user_id',
					'compare' => 'EXISTS',
				);
				$meta_query[] = array(
					'key'     => 'wk_sap_error',
					'compare' => 'NOT EXISTS',
				);
			}
			$query_args = array(
				'search'         => '*' . esc_attr( $search ) . '*',
				'search_columns' => array( 'user_login', 'user_email' ),
				'meta_query'     => $meta_query,
				'orderby'        => $orderby,
				'order'          => $sort_order,
				'number'         => $limit,
				'offset'         => $offset,
			);

			$user_query = new \WP_User_Query( $query_args );
			return $user_query->get_results();
		}
		/**
		 * Get total count of filtered users.
		 *
		 * @param array $args Request data.
		 *
		 * @return int
		 */
		private function get_filtered_users_count( $args ) {

			$search    = isset( $args['search'] ) ? trim( sanitize_text_field( $args['search'] ) ) : '';
			$item_type = isset( $args['item_type'] ) ? $args['item_type'] : 'A';

			$query_args = array(
				'count_total' => true,
				'fields'      => 'ID',
			);

			if ( ! empty( $search ) ) {
				$query_args['search']         = '*' . esc_attr( $search ) . '*';
				$query_args['search_columns'] = array( 'user_login', 'user_email' );
			}

			$meta_query = array( 'relation' => 'AND' );

			switch ( $item_type ) {
				case 'U':
					$meta_query[] = array(
						'key'     => 'wk_sap_user_id',
						'compare' => 'NOT EXISTS',
					);
					$meta_query[] = array(
						'key'     => 'wk_sap_error',
						'compare' => 'NOT EXISTS',
					);
					break;
				case 'E':
					$meta_query[] = array(
						'key'     => 'wk_sap_error',
						'compare' => 'EXISTS',
					);
					break;
				case 'S':
					$meta_query[] = array(
						'key'     => 'wk_sap_user_id',
						'compare' => 'EXISTS',
					);
					$meta_query[] = array(
						'key'     => 'wk_sap_error',
						'compare' => 'NOT EXISTS',
					);
					break;
				default:
					break;
			}
			if ( ! empty( $meta_query ) ) {
				$query_args['meta_query'] = $meta_query;
			}
			$user_query = new \WP_User_Query( $query_args );
			return (int) $user_query->get_total();
		}

		/**
		 * Preload user meta for all users to reduce database queries.
		 *
		 * @param array $user_data Array of user data objects.
		 *
		 * @return void
		 */
		private function preload_user_meta( $user_data ) {
			if ( empty( $user_data ) ) {
				return;
			}

			foreach ( $user_data as $user ) {
				$sap_user_id = get_user_meta( $user->ID, 'wk_sap_user_id', true );
				$sap_error   = get_user_meta( $user->ID, 'wk_sap_error', true );
				$sync_time   = get_user_meta( $user->ID, 'wk_sap_user_sync_time', true );
				$this->user_meta_cache[ $user->ID ] = array(
					'wk_sap_user_id'        => $sap_user_id,
					'wk_sap_error'          => $sap_error,
					'wk_sap_user_sync_time' => $sync_time,
				);
			}
		}

		/**
		 * Get user meta from cache.
		 *
		 * @param int    $user_id User ID.
		 * @param string $meta_key Meta key.
		 * @param mixed  $defaults Default value.
		 *
		 * @return mixed
		 */
		private function get_cached_user_meta( $user_id, $meta_key, $defaults = '' ) {
			if ( isset( $this->user_meta_cache[ $user_id ][ $meta_key ] ) ) {
				return $this->user_meta_cache[ $user_id ][ $meta_key ];
			}

			return $defaults;
		}

		/**
		 * Build row data for a single user.
		 *
		 * @param mixed $woouser User data object.
		 *
		 * @return array
		 */
		private function build_user_row_data( $woouser ) {
			$sap_account_id     = $this->get_cached_user_meta( $woouser->ID, 'wk_sap_user_id' );
			$sapacc_error       = $this->get_cached_user_meta( $woouser->ID, 'wk_sap_error' );
			$wooSapUserSyncTime = $this->get_cached_user_meta( $woouser->ID, 'wk_sap_user_sync_time' );
			$time = $wooSapUserSyncTime ?
				date_i18n( 'Y/m/d \a\t H:i:s', $wooSapUserSyncTime ) :
				'--';

			// Get avatar (with caching consideration).
			$img_src = get_avatar_url( $woouser->ID );
			if ( ! $img_src ) {
				$img_src = includes_url( 'images/blank.gif' );
			}

			$user_action = $this->build_user_actions( $woouser->ID, $sap_account_id, $sapacc_error );
			$stig        = '<';
			$edit_link   = add_query_arg( 'user_id', $woouser->ID, admin_url( 'user-edit.php' ) );
			$user_login  = sprintf(
				$stig . 'img src="%s" height="32" width="32"/><strong><a href="%s">%s</a></strong>',
				esc_url( $img_src ),
				esc_url( $edit_link ),
				esc_html( $woouser->user_login )
			);

			return array(
				'id'            => $woouser->ID,
				'username'      => $user_login,
				'email'         => $woouser->user_email,
				'woouserid'     => $woouser->ID,
				'sapaccountid'  => ! empty( $sap_account_id ) ? sprintf( '<span id="row_%d">%s</span>', esc_attr( $woouser->ID ), esc_html( $sap_account_id ) ) : '--',
				'SyncedAt'      => ! empty( $time ) ? sprintf( '<span id="row_%d">%s</span>', esc_attr( $woouser->ID ), esc_html( $time ) ) : '--',
				'woosapmergeid' => $woouser->ID,
				'actionsync'    => $user_action,
			);
		}

		/**
		 * Build user action buttons.
		 *
		 * @param int    $user_id User ID.
		 * @param string $sap_account_id SAP account ID.
		 * @param string $sapacc_error SAP error message.
		 *
		 * @return string
		 */
		private function build_user_actions( $user_id, $sap_account_id, $sapacc_error ) {
			$user_action = sprintf(
				'<span id="resync_%d" class="dashicons dashicons-image-rotate wksap-resync-user wksap-gray" title="%s"></span>',
				esc_attr( $user_id ),
				esc_attr__( 'Re-synchronize', 'sap-connector-for-woocommerce' )
			);

			if ( ! empty( $sapacc_error ) ) {
				$title        = wp_strip_all_tags( $sapacc_error );
				$title        = ! empty( $title ) ? $title : esc_attr( 'Error' );
				$user_action .= sprintf(
					'<span id="sync_status_icon_%d" class="dashicons dashicons-welcome-comments" title="%s"></span>',
					esc_attr( $user_id ),
					esc_attr( $title )
				);
			} elseif ( ! empty( $sap_account_id ) ) {
				$user_action .= sprintf(
					'<span id="sync_status_icon_%d" class="dashicons dashicons-yes" title="%s"></span>',
					esc_attr( $user_id ),
					esc_attr__( 'Successfully Synchronized', 'sap-connector-for-woocommerce' )
				);
			} else {
				$user_action .= sprintf(
					'<span id="sync_status_icon_%d" class="" title="%s"></span>',
					esc_attr( $user_id ),
					esc_attr__( 'Now Synchronize', 'sap-connector-for-woocommerce' )
				);
			}

			return $user_action;
		}

		/**
		 * Get table columns.
		 *
		 * @return array
		 */
		public function get_columns() {
			$columns = array(
				'cb'           => '<input type="checkbox" />',
				'username'     => esc_html__( 'Username', 'sap-connector-for-woocommerce' ),
				'email'        => esc_html__( 'Email', 'sap-connector-for-woocommerce' ),
				'woouserid'    => esc_html__( 'WooCommerce User ID', 'sap-connector-for-woocommerce' ),
				'sapaccountid' => esc_html__( 'SAP Business Partner ID', 'sap-connector-for-woocommerce' ),
				'SyncedAt'     => esc_html__( 'Synced At', 'sap-connector-for-woocommerce' ),
				'actionsync'   => esc_html__( 'Action', 'sap-connector-for-woocommerce' ),
			);
			return $columns;
		}

		/**
		 * Prepare items for display.
		 *
		 * @return void
		 */
		public function prepare_items() {
			$products_data = $this->table_data();
			$columns       = $this->get_columns();
			$hidden        = array();
			$sortable      = $this->get_sortable_columns();
			$this->_column_headers = array( $columns, $hidden, $sortable );
			$this->items           = $products_data;
		}

		/**
		 * Get sortable columns.
		 *
		 * @return array
		 */
		public function get_sortable_columns() {
			$sortable_columns = array(
				'username'  => array( 'username', false ),
				'email'     => array( 'email', false ),
				'woouserid' => array( 'woouserid', false ),
			);

			return $sortable_columns;
		}

		/**
		 * Render username column.
		 *
		 * @param array $item Column data.
		 *
		 * @return string
		 */
		public function column_username( $item ) {
			return $item['username'];
		}

		/**
		 * Render email column.
		 *
		 * @param array $item Column data.
		 *
		 * @return string
		 */
		public function column_email( $item ) {
			return esc_html( $item['email'] );
		}

		/**
		 * Render woo user id column.
		 *
		 * @param array $item Column data.
		 *
		 * @return string
		 */
		public function column_woouserid( $item ) {
			return esc_html( $item['woouserid'] );
		}

		/**
		 * Render sap account id column.
		 *
		 * @param array $item Column data.
		 *
		 * @return string
		 */
		public function column_sapaccountid( $item ) {
			return $item['sapaccountid'];
		}

		/**
		 * Render SyncedAt column.
		 *
		 * @param array $item Column data.
		 *
		 * @return string
		 */
		public function column_SyncedAt( $item ) {
			return $item['SyncedAt'];
		}

		/**
		 * Render action sync column.
		 *
		 * @param array $item Column data.
		 *
		 * @return string
		 */
		public function column_actionsync( $item ) {
			return $item['actionsync'];
		}

		/**
		 * Get bulk actions.
		 *
		 * @return array
		 */
		public function get_bulk_actions() {
			$wksap_globals = array( 'wksap_show_button_sync' => true );

			if ( isset( $wksap_globals['wksap_show_button_sync'] ) && $wksap_globals['wksap_show_button_sync'] ) {
				$actions = array(
					'exportUsers'     => esc_html__( 'Export Selected User(s)', 'sap-connector-for-woocommerce' ),
					'delete-user'     => esc_html__( 'Unlink Selected User(s)', 'sap-connector-for-woocommerce' ),
					'delete-all-user' => esc_html__( 'Unlink All Mapping', 'sap-connector-for-woocommerce' ),
				);
			} else {
				$actions = array(
					'exportUsers-dis' => esc_html__( 'Export Selected User(s)', 'sap-connector-for-woocommerce' ),
					'delete-user'     => esc_html__( 'Unlink Selected User(s)', 'sap-connector-for-woocommerce' ),
					'delete-all-user' => esc_html__( 'Unlink All Mapping', 'sap-connector-for-woocommerce' ),
				);
			}

			return $actions;
		}

		/**
		 * Render checkbox column.
		 *
		 * @param array $item Column data.
		 *
		 * @return string
		 */
		public function column_cb( $item ) {
			return sprintf(
				'<input type="checkbox" name="mergeduser[]" value="%s" />',
				esc_attr( $item['woouserid'] )
			);
		}

		/**
		 * Render search box.
		 *
		 * @param string $text Button text.
		 * @param string $input_id Input ID.
		 *
		 * @return void
		 */
		public function search_box( $text, $input_id ) {
			?>
			<p class="search-box">
				<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $text ); ?>:</label>
				<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
				<?php submit_button( $text, 'button', false, false, array( 'id' => 'search-submit' ) ); ?>
			</p>
			<?php
		}
	}
}
