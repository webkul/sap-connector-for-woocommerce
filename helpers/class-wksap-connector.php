<?php
/**
 * WKSAP_Connector class.
 *
 * @package SAP_Connector_For_WooCommerce
 **/

namespace WKSAP\HELPERS;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit();

/**Check if class exists.*/
if ( ! class_exists( 'WKSAP_Connector' ) ) {
	/**
	 * Class WKSAP_Connector.
	 */
	class WKSAP_Connector {
		/**
		 * Access Token.
		 *
		 * @var $access_token
		 */
		public static $access_token = '';

		/**
		 * Instance url.
		 *
		 * @var $instance_url
		 */
		public static $instance_url = '';

		/**
		 * Token Type.
		 *
		 * @var $token_type
		 */
		public static $token_type = '';

		/**
		 * Token Type.
		 *
		 * @var $token_type
		 */
		public static $timeout = 45;

		/**
		 * Constructor.
		 *
		 * @return void
		 */
		public function __construct() {
			$wksap_config = get_option( 'wksap_config' );
			if ( ! empty( $wksap_config ) ) {
				self::$access_token = isset( $wksap_config->access_token ) && ! empty( $wksap_config->access_token ) ? $wksap_config->access_token : '';
				self::$instance_url = isset( $wksap_config->instance_url ) && ! empty( $wksap_config->instance_url ) ? $wksap_config->instance_url : '';
				self::$token_type   = isset( $wksap_config->token_type ) && ! empty( $wksap_config->token_type ) ? $wksap_config->token_type : '';
			}
			self::$timeout = 45;
		}

		/**
		 * Get connection with SAP.
		 *
		 * @param  mixed $data Data.
		 *
		 * @return mixed Response from SAP.
		 */
		public static function wksap_generate_connection_with_sap( $data ) {
			if ( ! empty( $data ) ) {
				$args = array(
					'body'      => wp_json_encode(
						array(
							'CompanyDB' => $data->CompanyDB,
							'Password'  => $data->Password,
							'UserName'  => $data->UserName,
						)
					),
					'headers'   => array( 'Content-Type' => 'application/json' ),
					'timeout'   => self::$timeout,
					'sslverify' => false,
				);

				$response = wp_remote_post( $data->instance . '/b1s/v1/Login', $args );

				if ( is_wp_error( $response ) ) {
					return false;
				}
				$response_body  = wp_remote_retrieve_body( $response );
				$returnResponse = json_decode( $response_body );

				return $returnResponse;
			}
		}

		/**
		 * Get connection with SAP.
		 *
		 * @param object $sObject SAP Object.
		 * @param string $query Query.
		 *
		 * @return mixed Response from SAP.
		 */
		public static function wksap_get_saps_object( $sObject, $query ) {
			if ( ! empty( $sObject ) ) {
				$wksap_config         = get_option( 'wksap_config' );
				$wksap_sap_connection = get_option( 'wksap_sap_connection' );

				if ( ! empty( $wksap_sap_connection ) ) {
					$url = $wksap_config->instance . '/b1s/v1/' . $sObject . '?$filter=' . $query;

					$response = wp_remote_get(
						$url,
						array(
							'headers'   => array(
								'Cookie' => 'B1SESSION=' . $wksap_sap_connection->SessionId,
							),
							'timeout'   => self::$timeout,
							'sslverify' => false,
						)
					);

					if ( is_wp_error( $response ) ) {
						return false;
					}

					$body    = wp_remote_retrieve_body( $response );
					$decoded = json_decode( $body );
					return $decoded;
				} else {
					return esc_html__( 'Error: SAP connection not configured.', 'sap-connector-for-woocommerce' );
				}
			} else {
				return esc_html__( 'Error: sObject not set.', 'sap-connector-for-woocommerce' );
			}
		}

		/**
		 * Insert SAP Object.
		 *
		 * @param mixed $sobject SAP Object.
		 * @param mixed $sobjectData SAP Object Data.
		 *
		 * @return mixed Response from SAP.
		 */
		public static function wksap_insert_saps_object( $sobject, $sobjectData ) {
			if ( empty( $sobject ) ) {
				return esc_html__( 'Error: sObject not set.', 'sap-connector-for-woocommerce' );
			}
			$wksap_config         = get_option( 'wksap_config' );
			$wksap_sap_connection = get_option( 'wksap_sap_connection' );
			$url = $wksap_config->instance . '/b1s/v1/' . $sobject;

			if ( empty( $wksap_sap_connection ) ) {
				return esc_html__( 'Error: SAP connection not available.', 'sap-connector-for-woocommerce' );
			}

			$args         = array(
				'headers'   => array(
					'Content-Type' => 'application/json',
					'Cookie'       => 'B1SESSION=' . $wksap_sap_connection->SessionId,
				),
				'body'      => $sobjectData,
				'timeout'   => self::$timeout,
				'sslverify' => false,
			);
				$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$response_body  = wp_remote_retrieve_body( $response );
			$returnResponse = json_decode( $response_body );

			return $returnResponse;
		}

		/**
		 * Update SAP Object.
		 *
		 * @param mixed $sobject SAP Object.
		 * @param mixed $sobjectData SAP Object Data.
		 * @param mixed $updateId SAP Object ID.
		 *
		 * @return mixed Response from SAP.
		 */
		public static function wksap_update_saps_object( $sobject, $sobjectData, $updateId ) {
			if ( empty( $sobject ) ) {
				return esc_html__( 'Error: sObject not set.', 'sap-connector-for-woocommerce' );
			}

			$wksap_config         = get_option( 'wksap_config' );
			$wksap_sap_connection = get_option( 'wksap_sap_connection' );

			if ( 'Items' === $sobject || 'BusinessPartners' === $sobject ) {
				$instance_url = $wksap_config->instance . '/b1s/v1/' . $sobject . "('" . $updateId . "')";
			} else {
				$instance_url = $wksap_config->instance . '/b1s/v1/' . $sobject . '(' . $updateId . ')';
			}

			if ( empty( $wksap_sap_connection ) ) {
				return esc_html__( 'Error: SAP connection not available.', 'sap-connector-for-woocommerce' );
			}

			$args     = array(
				'method'  => 'PATCH',
				'headers' => array(
					'Content-Type' => 'application/json',
					'Cookie'       => 'B1SESSION=' . $wksap_sap_connection->SessionId,
				),
				'body'    => $sobjectData,
				'timeout' => self::$timeout,
			);
			$response = wp_remote_request( $instance_url, $args );

			if ( is_wp_error( $response ) ) {
				return false;
			}

			$body = wp_remote_retrieve_body( $response );

			return json_decode( $body );
		}
	}
}
