<?php

// No direct access allowed.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 * Activate license:
 * http://YOURSITE.com/?edd_action=activate_license
 *        &item_name=EDD+Product+Name
 *        &license=cc22c1ec86304b36883440e2e84cddff
 *        &url=http://licensedsite.com
 *
 * Responses:
 * VALID:
 * {
 * "license": "valid",
 * "item_name": "EDD Product name",
 * "expires": "2014-10-23 00:00:00",
 * "payment_id": 54224,
 * "customer_name": "John Doe",
 * "customer_email": "john@sample.com"
 * }
 *
 * INVALID:
 *
 * {
 * "license": "invalid",
 * "item_name": "EDD Product name",
 * "expires": "2014-10-23 00:00:00",
 * "payment_id": 54224,
 * "customer_name": "John Doe",
 * "customer_email": "john@sample.com"
 * }
 *
 * Check license:
 * http://YOURSITE.com/?edd_action=check_license
 *            &item_name=EDD+Product+Name
 *            &license=cc22c1ec86304b36883440e2e84cddff
 *            &url=http://licensedsite.com
 *
 * Responses:
 * VALID:
 * {
 * "license": "valid",
 * "item_name": "EDD Product name",
 * "expires": "2014-10-23 00:00:00",
 * "payment_id": 54224,
 * "customer_name": "John Doe",
 * "customer_email": "john@sample.com"
 * }
 *
 * INVALID:
 * {
 * "license": "invalid",
 * "item_name": "EDD Product name",
 * "expires": "2014-10-23 00:00:00",
 * "payment_id": 54224,
 * "customer_name": "John Doe",
 * "customer_email": "john@sample.com"
 * }
 * @author Cale
 *
 */
class WPIMAPI {

	private static $instance;

	private static $config;

	private $error;

	const API_URL = 'https://www.wpinventory.com/license_api/'; // 'http://wpinventory.mrwpress.com'; //

	const REG_ITEM_NAME = 'WP Inventory Manager';
	const DEV_ITEM_NAME = 'Developer Bundle';

	public function __construct() {
		self::$config = WPIMConfig::getInstance();
	}

	public static function getInstance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate( $data, $key, $type="activate" ) {
		// data to send in our API request
		if ( ! $data ) {
			$item_name = self::REG_ITEM_NAME;
			$item_key  = 'core';
			$item_id   = 675;
		} else {
			$item_name = $data->item_name;
			$item_key  = $data->key;
			$item_id   = ( ! empty( $data->item_id ) ) ? $data->item_id : NULL;
		}

		$action = ('activate' == $type) ? 'activate_license' : 'check_license';

		$api_params = [
			'edd_action' => $action,
			'license'    => $key,
			'url'        => home_url()
		];

		if ( $item_id ) {
			$api_params['item_id'] = $item_id;
		} else {
			$api_params['item_name']  = urlencode( $item_name ); // the name of the product in EDD
		}

		$api_params = apply_filters( 'wpim_license_activation_api_params', $api_params, self::API_URL, $item_name );

		$response = wp_remote_post( self::API_URL, [
			'timeout'   => 15,
			'sslverify' => FALSE,
			'body'      => $api_params
		] );

		$response = apply_filters( 'wpim_license_activation_response', $response, $item_name );

		if ( is_wp_error( $response ) ) {
			// Try again with post
			$response = wp_remote_get( add_query_arg( $api_params, self::API_URL ), [
				'timeout'   => 15,
				'sslverify' => FALSE
			] );

			$response = apply_filters( 'wpim_license_activation_retry_response', $response, $item_name );

			if ( is_wp_error( $response ) ) {
				echo '<div class="error"><p>' . WPIMCore::__( 'When attempting to activate license, could not reach license site.' ) . '</p></div>';

				return FALSE;
			}
		}

		if ( ! $response ) {
			echo '<div class="error"><p>' . WPIMCore::__( 'When attempting to activate license, no response was received.' ) . '</p></div>';
		} else {
			if ( wp_remote_retrieve_response_code( $response ) != 200 ) {
				echo '<div class="error"><p>' . sprintf( WPIMCore::__( 'When attempting to activate license, a response code of %d was returned.' ), wp_remote_retrieve_response_code( $response ) ) . '</p>';
				echo '<p><strong>Debugging Information: Please provide the information below to support.</strong></p>';
				var_dump( $response );
				echo '</div>';
			} else {
				$response = json_decode( wp_remote_retrieve_body( $response ) );
				$expires  = NULL;
				if ( ! empty( $response->expires ) ) {
					$expires = ( 'lifetime' == $response->expires ) ? strtotime( '12/31/2020' ) : strtotime( $response->expires );
				}

				$expires = apply_filters( 'wpim_license_update_expires', $expires, $item_name );

				$key = [
					'key'     => $key,
					'expires' => $expires,
					'valid'   => FALSE
				];

				if ( $response->license != 'valid' ) {
					if ( ( $expires && $expires < time() ) || $response->error == 'expired' ) {
						$key['valid'] = TRUE;
						echo '<div class="error"><p>' . sprintf( WPIMCore::__( 'The license entered for %s is expired. To continue receiving updates and support, please renew.' ), $item_name ) . '</p></div>';
					} else {
						$key['key']     = '';
						$key['expires'] = NULL;
						echo '<div class="error"><p>' . sprintf( WPIMCore::__( 'The license entered for %s is invalid. (%s)' ), $item_name, $response->error ) . '</p></div>';
					}
				} else {
					echo '<div class="updated"><p>' . sprintf( WPIMCore::__( 'Congratulations! The %s license entered is valid.' ), $item_name );
					if ( $item_key != 'core' ) {
						echo ' <a href="' . admin_url( 'admin.php?page=wpim_manage_settings' ) . '">' . sprintf( WPIMCore::__( '(Click to refresh if %s is not visible.)' ), $item_name ) . '</a>';
					}

					echo '</p></div>';
					$key['valid'] = TRUE;
				}
			}

			$key = apply_filters( 'wpim_license_key_update_info', $key, $item_name );

			$all_reg_info              = WPIMCore::get_reg_info();
			$all_reg_info[ $item_key ] = $key;
			update_option( 'wpim_license', $all_reg_info );
		}
	}

	// This method is used for checking add-on status, etc.
	public static function make_call( $method ) {
		$params   = [ 'api_call' => [ 'method' => 'wpim_' . $method ] ];
		$response = '';
		// Purely to hook into logging add-on
		$url = add_query_arg( $params, self::API_URL );
		do_action( 'wpim_pre_api_call', $url );
		$results = wp_remote_get( $url, [
			'timeout'   => 15,
			'sslverify' => FALSE
		] );

		if ( wp_remote_retrieve_response_code( $results ) == 200 ) {
			$response = wp_remote_retrieve_body( $results );
			$json     = @json_decode( $response );
			if ( ! $json ) {
				echo 'INVALID RESPONSE JSON';
				var_dump( $response );
				$response = '';
			} else {
				if ( ! empty( $json->data ) ) {
					$response = $json->data;
				} else {
					$response = FALSE;
				}
			}
		} else {
//			echo "API CALL FAILED";
		}

		return $response;
	}

	public function get_error() {
		return $this->error;
	}
}
