<?php
/**
 * WK SAP CONNECTOR handler final class.
 *
 * @package SAP_Connector_For_WooCommerce
 */

namespace WKSAP\INCLUDES;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP' ) ) {
	/**
	 * Final class WKSAP.
	 */
	final class WKSAP {
		/**
		 * Synchronized Process User.
		 *
		 * @var $sync_user Synchronized Process User.
		 */
		protected static $sync_user;

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
			$this->wksap_define_constants();
			$this->wksap_init_hooks();
			add_action( 'before_woocommerce_init', array( $this, 'wksap_is_wc_hpos_compatible' ) );
			add_filter( 'plugin_row_meta', array( $this, 'wksap_plugin_row_meta' ), 10, 2 );
		}

		/**
		 * Defining plugin's constant.
		 *
		 * @return void
		 */
		public function wksap_define_constants() {
			defined( 'WKSAP_SAP_PLUGIN_URL' ) || define( 'WKSAP_SAP_PLUGIN_URL', plugin_dir_url( __DIR__ ) );
			defined( 'WKSAP_PLUGIN_URL' ) || define( 'WKSAP_PLUGIN_URL', plugins_url() );
			defined( 'WKSAP_VERSION' ) || define( 'WKSAP_VERSION', '1.0.0' );
			defined( 'WKSAP_SCRIPT_VERSION' ) || define( 'WKSAP_SCRIPT_VERSION', '1.0.0' );
		}

		/**
		 * Hook into actions and filters.
		 *
		 * @return void
		 */
		private function wksap_init_hooks() {
			add_action( 'init', array( $this, 'wksap_load_plugin' ) );
			self::$sync_user = new WKSAP_Synchronized_Process_User();
		}

		/**
		 * Plugin row data.
		 *
		 * @param string $links Links.
		 * @param string $file Filepath.
		 *
		 * @hooked 'plugin_row_meta' filter hook.
		 *
		 * @return array $links links.
		 */
		public function wksap_plugin_row_meta( $links, $file ) {
			if ( plugin_basename( WKSAP_PLUGIN_BASENAME ) === $file ) {
				$row_meta = array(
					'docs'    => '<a target="_blank" href="' . esc_url( 'https://eshopsync.com/how-to-integrate-woocommerce-sap-business-one/' ) . '" aria-label="' . esc_attr__( 'View documentation', 'sap-connector-for-woocommerce' ) . '">' . esc_html__( 'Docs', 'sap-connector-for-woocommerce' ) . '</a>',
					'support' => '<a target="_blank" href="' . esc_url( 'https://webkul.uvdesk.com/' ) . '" aria-label="' . esc_attr__( 'Visit customer support', 'sap-connector-for-woocommerce' ) . '">' . esc_html__( 'Support', 'sap-connector-for-woocommerce' ) . '</a>',
				);

				return array_merge( $links, $row_meta );
			}

			return (array) $links;
		}

		/**
		 * Check dependency.
		 *
		 * @return bool
		 */
		public function wksap_dependency_satisfied() {
			if ( class_exists( 'WooCommerce' ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Load message indication plugin.
		 *
		 * @return void
		 */
		public function wksap_load_plugin() {
			$this->wksap_dependency_satisfied() ? WKSAP_File_Handler::get_instance() : add_action( 'admin_notices', array( $this, 'wksap_show_wc_not_installed_notice' ) );
		}

		/**
		 * Show wc not installed notice.
		 *
		 * @return void
		 */
		public function wksap_show_wc_not_installed_notice() {
			?>
			<div class="error">
				<p>
					<?php
					/* translators: %1$s for a opening tag and %2$s for closing tag */
					printf( esc_html__( 'SAP Connector For WooCommerce is enabled but not effective. It requires %1$sWooCommerce Plugin%2$s in order to work.', 'sap-connector-for-woocommerce' ), '<a href="' . esc_url( '//wordpress.org/plugins/woocommerce/' ) . '" target="_blank">', '</a>' );
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Declares WooCommerce HPOS compatibility.
		 *
		 * @return void
		 */
		public function wksap_is_wc_hpos_compatible() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WKSAP_FILE_NAME, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WKSAP_FILE_NAME, true );
			}
		}

		/**
		 * Cloning is forbidden.
		 *
		 * @return void
		 */
		public function __clone() {
			wp_die( __FUNCTION__ . esc_html__( 'Cloning is forbidden.', 'sap-connector-for-woocommerce' ) );
		}

		/**
		 * Deserializing instances of this class is forbidden.
		 *
		 *  @return void
		 */
		public function __wakeup() {
			wp_die( __FUNCTION__ . esc_html__( 'Deserializing instances of this class is forbidden.', 'sap-connector-for-woocommerce' ) );
		}

		/**
		 * This is a singleton page, access the single instance just using this method.
		 *
		 * @return object
		 */
		public static function get_instance() {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}
}
