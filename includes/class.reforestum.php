<?php 
/**
 * Reforestum setup
 * 
 * @package Reforestum
 * @since 	1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Reforestum class
 * 
 * @class Reforestum
 */
final class Reforestum {
	
	/**
	 * Instance of this class
	 * 
	 * @var bool
	 */
	private static $initiated = false;

	/**
	 * Initialize class
	 * 
	 * Make sure only one instance is loaded
	 * 
	 * @since 1.0
	 */
	public static function init() {
		if ( ! self::$initiated ) {
			self::init_hooks();
		}
	}

	/**
	 * Load required functions on initialize
	 * 
	 * @since 1.0
	 */
	private static function init_hooks() {
		self::$initiated = true;

		self::load_textdomain();

		/**
		 * Load only in front end
		 */
		if( ! is_admin() ){
			Reforestum_WC::init();
			Reforestum_Frontend::init();
		}

	}

	/**
	 * Load plugin text domain for translation
	 * 
	 * @since 1.0
	 */
	public static function load_textdomain(){
		load_plugin_textdomain( 'reforestum', false, basename( REFORESTUM_PLUGIN_DIR ) . '/languages' );
	}

	/**
	 * Run functions on plugin uninstall
	 * 
	 * @since 1.0
	 */
	public function plugin_uninstall(){
		delete_option( 'reforestum_api_username' );
		delete_option( 'reforestum_api_password' );
		delete_option( 'reforestum_sandbox_mode' );
		delete_option( 'reforestum_payer' );
		delete_option( 'reforestum_product_unit' );
		delete_option( 'reforestum_shipping_unit' );
		delete_option( 'reforestum_sales_contracts' );
		delete_option( 'reforestum_project_restricted' );
		delete_option( 'reforestum_selected_forests' );
		delete_option( 'reforestum_sales_contract' );
		delete_option( 'reforestum_display_in_product' );
		delete_option( 'reforestum_display_in_cart' );
		delete_option( 'reforestum_display_in_checkout' );
		delete_option( 'reforestum_api_connected' );
		delete_option( 'reforestum_forests' );
	}
}