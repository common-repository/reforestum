<?php 
/**
 * Reforestum Admin
 * 
 * @class 	Reforestum_Admin
 * @package	Reforestum\Admin
 * @version	1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reforestum_Admin class
 */
class Reforestum_Admin {
	
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

		self::admin_includes();
		self::dependencies();
		self::setup_notice();

		// Load integration for product & product category
		Reforestum_Product::init();
		Reforestum_Product_Cat::init();

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );

		
	}

	/**
	 * Include required admin files
	 * 
	 * @since 1.0
	 */
	public static function admin_includes(){
		require_once( REFORESTUM_PLUGIN_DIR . 'includes/reforestum-admin-functions.php' );
	}

	/**
	 * Admin enqueue scripts
	 * 
	 * @since 1.0
	 */
	public static function enqueue(){
		$screen = get_current_screen();

		// Only enqueue custom CSS on WooCommerce settings and product page
		if( $screen->base == 'woocommerce_page_wc-settings' || $screen->post_type == 'product' ){
			wp_enqueue_style( 'reforestum', REFORESTUM_PLUGIN_URI . 'admin/css/admin.css', array(), REFORESTUM_PLUGIN_VERSION, 'all' );
			wp_enqueue_script( 'reforestum', REFORESTUM_PLUGIN_URI . 'admin/js/admin.js', array(), REFORESTUM_PLUGIN_VERSION, true );

			$plugin_sales_contract = get_option( 'reforestum_sales_contract' );
			
			// if post
			if( $screen->base == 'post' ){
				$vars['selected_sales_contract'] = Reforestum_WC::get_contract( get_the_ID() );
			}
			// if category
			elseif( $screen->base == 'term' ){
				if( isset( $_GET['tag_ID'] ) ){
					$category_sales_contract = get_term_meta( $_GET['tag_ID'], 'reforestum_sales_contract', true );
				}
				$vars['selected_sales_contract'] = $category_sales_contract ? $category_sales_contract : $plugin_sales_contract;
			} 
			// if plugin
			else {
				$vars['selected_sales_contract'] = $plugin_sales_contract;
			}

			$contracts 	= get_option( 'reforestum_sales_contracts' );
			if(!empty($contracts)){
				foreach( $contracts as $contract ){
					$vars['sales_contracts'][$contract->contract_key] = $contract;
				}
			}

			$vars['forest_required'] = __( 'Need at least 1 forest selected for project constraints', 'reforestum' );
  			$vars['ajaxurl'] = admin_url( 'admin-ajax.php' );

			wp_localize_script( 'reforestum', 'reforestum', $vars );
		}
	}

	/**
	 * Check Plugin Dependencies
	 * 
	 * Display notice if dependencies not exists
	 * 
	 * @since 1.0
	 */
	public static function dependencies(){
		if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
		}
	}

	/**
	 * Setup Notice
	 * 
	 * Display notices if there are unfinished setups
	 * 
	 * @since 1.0
	 */
	public static function setup_notice(){
		if( ! Reforestum_API::api_login_exists() ){
			add_action( 'admin_notices', array( __CLASS__, 'api_notice' ) );
		}

		if( ! Reforestum_API::sales_contract_exists() ){
			add_action( 'admin_notices', array( __CLASS__, 'sales_contracts_notice' ) );
		}
	}

	/**
	 * Dependency Notice
	 * 
	 * Display notice if there are missing dependencies
	 * 
	 * @since 1.0
	 */
	public static function dependency_notice(){
		$class = 'notice notice-error';
		$message = __( '<strong>Reforestum</strong> plugin requires WooCommerce to be installed & activated to work.', 'reforestum' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); 
	}

	/**
	 * API Notice
	 * 
	 * Display notice if API are not added to the settings
	 * 
	 * @since 1.0
	 */
	public static function api_notice(){
		$class = 'notice notice-warning';
		$message = __( 'Insert your API username & password to start using <strong>Reforestum</strong> plugin. <a href="' . add_query_arg( array( 'page' => 'wc-settings', 'tab' => 'reforestum' ), admin_url( 'admin.php' ) ) . '">Go to settings</a>', 'reforestum' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); 
	}

	/**
	 * Sales Contract Notice
	 * 
	 * Display notice if no sales contract are found
	 * 
	 * @since 1.0
	 */
	public static function sales_contracts_notice(){
		$class = 'notice notice-error';
		$message = __( '<strong>Reforestum</strong> plugin requires at least one sales contract to work, contact Reforestum to get your sales contract.', 'reforestum' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); 
	}

}

