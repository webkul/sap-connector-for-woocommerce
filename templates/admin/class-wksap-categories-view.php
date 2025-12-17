<?php
/**
 * Categories View handler class.
 *
 * @package SAP_Connector_For_WooCommerce
 */

namespace WKSAP\TEMPLATES\ADMIN;

defined( 'ABSPATH' ) || exit();
// Exit if accessed directly.

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP_Categories_View' ) ) {
	/**
	 * Class WKSAP_Categories_View.
	 */
	class WKSAP_Categories_View {
		/**
		 * Instance variable
		 *
		 * @var $instance
		 */
		protected static $instance = null;

		/**
		 * Display method to create view elements.
		 *
		 * @return void
		 */
		public function wksap_display() {
			?>
		<div class="upgrade-alert">
			<p>
				<a target="_blank" href="<?php echo esc_url( 'https://store.webkul.com/SAP-WordPress-WooCommerce-Connector.html' ); ?>">
					<?php esc_html_e( 'Upgrade to the Full edition to use full version', 'sap-connector-for-woocommerce' ); ?>
				</a>
			</p>
			<?php esc_html_e( 'Do you have full version?', 'sap-connector-for-woocommerce' ); ?><br>
			<ol>
				<li><?php esc_html_e( 'Please uninstall this version.', 'sap-connector-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Remove files for this version', 'sap-connector-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Install new version', 'sap-connector-for-woocommerce' ); ?></li>
			</ol>
		</div>
			<?php
		}

		/**
		 * Return a singleton instance of class.
		 *
		 * @brief Singleton
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
