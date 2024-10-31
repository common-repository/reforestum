<?php 
/**
 * Reforestum API
 * 
 * @class	Reforestum_API
 * @package	Reforestum/Classes
 * @version	1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reforestum_API class
 * 
 * Functions that related to the integration with Reforestum API
 */
class Reforestum_API {
	
	/**
	 * @var		string	url of the API environment
	 */

	/**
	 * Get ENV URL
	 * 
	 * @since 1.0
	 */
	private static function env(){
		$sandbox_mode = get_option( 'reforestum_sandbox_mode' );
		if($sandbox_mode == 'yes'){
			return 'https://reforestum-api.herokuapp.com/v1/';
		}
		return 'https://api.reforestum.com/v1/';
	}

	/**
	 * API Authentication
	 * 
	 * @since 1.0
	 */
	public static function authentication(){

		if( ! self::api_login_exists() )
			return false;
		
		
		$response = wp_remote_post( self::env() . 'auth/login', [
			'body'			=> json_encode( self::api_login_exists() ),
			'headers'		=> [
				'Content-Type' => 'application/json',
			],
			'data_format' => 'body',
		] );

		if( is_wp_error( $response ) ){
			$error_message = $response->get_error_message();
			echo sprintf( __( 'Something went wrong: %s', 'reforestum' ), $error_message );
		} else {
			$result = json_decode( $response['body'] );
			if( $result->status == '200' ){
				set_transient( 'reforestum_api_token', $result->data->{'access-token'}, DAY_IN_SECONDS);
				set_transient( 'reforestum_auth_result', $result->data, DAY_IN_SECONDS);
				update_option( 'reforestum_api_connected', true );
			} else {
				delete_transient( 'reforestum_api_token' );
				delete_transient( 'reforestum_auth_result' );
				update_option( 'reforestum_api_connected', false );
			}
		}
	}

	/**
	 * Sales Contracts
	 * 
	 * @since 1.0
	 */
	public static function sales_contracts(){
		$token = get_transient( 'reforestum_api_token' );
		if( empty( $token ) ){
			self::authentication();
			$token = get_transient( 'reforestum_api_token' );
		}

		$response = wp_remote_get( self::env() . 'b2b/sales-contract', [
			'headers'		=> [
				'Authorization'	=> 'Bearer ' . $token
			]
		] );

		if( is_wp_error( $response ) ){
			$error_message = $response->get_error_message();
			echo sprintf( __( 'Something went wrong: %s', 'reforestum' ), $error_message );
		} else {
			$result = json_decode( $response['body'] );
			if( $result->status == '200' ){
				set_transient( 'reforestum_sales_contracts', $result->contracts, DAY_IN_SECONDS );
				update_option( 'reforestum_sales_contracts', $result->contracts );
			} else {
				if($result->error) {
					echo $result->error->message;
				}
			}
		}
	}

	/**
	 * Get credit of connected user
	 */
	public static function credit() {
		$token = get_transient( 'reforestum_api_token' );
		if( empty( $token ) ){
			self::authentication();
			$token = get_transient( 'reforestum_api_token' );
		}

		$response = wp_remote_get( self::env() . 'user/credit', [
			'headers'		=> [
				'Authorization'	=> 'Bearer ' . $token
			]
		] );

		if( is_wp_error( $response ) ){
			$error_message = $response->get_error_message();
			echo sprintf( __( 'Something went wrong: %s', 'reforestum' ), $error_message );
		} else {
			$result = json_decode( $response['body'] );
			if( $result->status == '200' ){
				return $result->credit;
			} else {
				if($result->error) {
					echo $result->error->message;
				}
			}
		}
	}

	/**
	 * Get list of available forests
	 * 
	 * @since 1.0
	 */
	public static function forests(){
		$response = wp_remote_get( self::env() . 'forests' );
		if( is_wp_error( $response ) ){
			$error_message = $response->get_error_message();
			echo sprintf( __( 'Something went wrong: %s', 'reforestum' ), $error_message );
		} else {
			$result = json_decode( $response['body'] );
			if( $result->status == '200' ){
				set_transient( 'reforestum_forests', $result->data->forests, DAY_IN_SECONDS );
				update_option( 'reforestum_forests', $result->data->forests );
			}
		}
	}

	/**
	 * Query offset
	 * 
	 * @param	int	$co2	the amount of Co2 in KG
	 * @return	array	list off the forests and the Co2 offset amount
	 * @since	1.0
	 */
	public static function query_offset( $co2 ){

		$token = get_transient( 'reforestum_api_token' );
		if( empty( $token ) ){
			self::authentication();
			$token = get_transient( 'reforestum_api_token' );
		}

		$response = wp_remote_post( self::env() . 'b2b/query-offset', [
			'body'			=> json_encode( [ 
				'co2'					=> $co2
			] ),
			'headers'		=> [
				'Content-Type' 		=> 'application/json',
				'Authorization'		=> 'Bearer ' . $token,
				'X-Accept-Currency'	=> self::get_currency()
			],
			'data_format' => 'body',
		] );

		if( is_wp_error( $response ) ){
			$error_message = $response->get_error_message();
			echo sprintf( __( 'Something went wrong: %s', 'reforestum' ), $error_message );
		} else {
			$result = json_decode( $response['body'] );
			if( $result->status == '200' ){
				return $result->forests;
			} else {
				if($result->error) {
					echo $result->error->message;
				}
			}
		}
	}

	/**
	 * Purchase on behalf
	 * 
	 * Send purchase request of the order with the purchase query
	 * 
	 * @param	int	$order_id	Id of WooCommerce order
	 * @param	array	$query		Query of the purchase
	 * @since 	1.0
	 */
	public static function purchase_on_behalf( $order_id, array $query ){

		$token = get_transient( 'reforestum_api_token' );
		if( empty( $token ) ){
			self::authentication();
			$token = get_transient( 'reforestum_api_token' );
		}

		$response = wp_remote_post( self::env() . 'b2b/purchase-on-behalf', [
			'body'			=> json_encode( $query ),
			'headers'		=> [
				'Content-Type' 		=> 'application/json',
				'Authorization'		=> 'Bearer ' . $token
			],
			'data_format' => 'body',
		] );

		if( is_wp_error( $response ) ){
			$error_message = $response->get_error_message();
			add_post_meta( $order_id, 'reforestum_purchase_response_error', $error_message );
			echo sprintf( __( 'Something went wrong: %s', 'reforestum' ), $error_message );
		} else {
			$result = json_decode( $response['body'] );
			add_post_meta( $order_id, 'reforestum_purchase_response', $result );
		}
	}

	/**
	 * Get Contract
	 * 
	 * Get contract detail by contract key
	 * 
	 * @param	int	Key of the contract
	 * @since	1.0
	 */
	public static function get_contract( $contract_key ){
		$contracts 	= get_option( 'reforestum_sales_contracts' );
		$key			= array_search( $contract_key, array_column( $contracts, 'contract_key' ) );
		return $contracts[$key];
	}

	/**
	 * Get Currency
	 * 
	 * Get WooCommerce active currency to be used by API
	 * 
	 * @return 	string	active currency
	 * @return 	string	EUR if active currency are not in the supported currencies
	 * @since 	1.0
	 */
	public static function get_currency(){
		$WC_Currency = get_woocommerce_currency();
		$supported_currency = array( 'EUR', 'USD', 'GBP' );
		return in_array( $WC_Currency, $supported_currency ) ? $WC_Currency : 'EUR';
	}

	/**
	 * Get Forest By ID
	 * 
	 * @param 	int	$id	ID of the forest
	 * @return	array	detail of the forest
	 * @since	1.0
	 */
	public static function get_forest_by_id( $id ){
		$forests = get_option( 'reforestum_forests' );
		if( empty( $forests ) ){
			Reforestum_API::forests();
			$forests = get_option( 'reforestum_forests' );
		}

		$key = array_search( $id, array_column( $forests, 'id' ) );
		return $forests[$key];
	}

	/**
	 * API Login Exists
	 * 
	 * Check if API username and password are exists
	 * 
	 * @return 	false	API username and password are not specified
	 * @return	array	API username and password
	 * @since	1.0
	 */
	public static function api_login_exists(){
		$username	= get_option( 'reforestum_api_username' );
		$password	= get_option( 'reforestum_api_password' );
		return empty( $username ) || empty( $password ) ? false : [ 'username' => $username, 'password'	=> $password ];
	}

	/**
	 * Sales Contract Exists
	 * 
	 * Check if current user has sales contract
	 * 
	 * @return	bool	sales contract exists or not
	 * @since	1.0
	 */
	public static function sales_contract_exists(){
		$sales_contracts = get_option( 'reforestum_sales_contract' );
		return (bool)$sales_contracts;
	}

	/**
	 * Get forests id
	 * 
	 * @return	array
	 * @since	1.0
	 */
	public static function get_forests_id(){
		$forests = get_option( 'reforestum_forests' );
		if( empty( $forests ) ){
			Reforestum_API::forests();
			$forests = get_option( 'reforestum_forests' );
		}
		return array_column( $forests, 'id' );
	}

	/**
	 * Forest Options
	 * 
	 * Forest options for select in settings
	 * 
	 * @return	array	list of forest id and name
	 * @since	1.0
	 */
	public static function forests_options(){
		$selected_forests = get_option( 'reforestum_forests' );
		if( empty( $selected_forests ) ){
			Reforestum_API::forests();
			$selected_forests = get_option( 'reforestum_forests' );
		}

		foreach( $selected_forests as $forest ){
			if( $forest->available )
				$options[$forest->id]	= esc_attr( $forest->name . ', ' . $forest->location_desc );
		}

		return $options;
	}

	/**
	 * Contract Options
	 * 
	 * Sales contract options for select in settings
	 * 
	 * @return	array	list of sales contract id and name
	 * @since	1.0
	 */
	public static function contracts_option( $level = 'product' ){
		$contracts = get_option( 'reforestum_sales_contracts' );
		if( $level == 'product' ){
			$get_contract = Reforestum_WC::get_contract( get_the_ID() );
			$contract_detail = self::get_contract( $get_contract );
			$inherited_contract = empty( $get_contract ) ? '' : '(' . $contract_detail->contract_alias . ')';
			$options[''] = __( 'Use default plugin general settings', 'reforestum' ) . ' ' . $inherited_contract;

		} elseif( $level == 'category' ){
			$plugin_contract_key = get_option( 'reforestum_sales_contract' );
			$plugin_contract_detail = self::get_contract( $plugin_contract_key );
			$plugin_contract = empty( $plugin_contract_key ) ? '' : '(' . $plugin_contract_detail->contract_alias . ')';
			$options[''] = __( 'Use default plugin general settings', 'reforestum' ) . ' ' . $plugin_contract;
		} else {
			$options[''] = __( 'Select sales contract', 'reforestum' );
		}

		if( ! empty( $contracts ) ){
			foreach( $contracts as $contract ){
				unset( $coptions );
				$options[$contract->contract_key] = $contract->contract_alias;
			}
		}
		return $options;
	}

}