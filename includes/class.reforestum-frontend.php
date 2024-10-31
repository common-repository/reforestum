<?php 
/**
 * Reforestum Frontend
 * 
 * Functions for front end
 * 
 * @class	Reforestum_Frontend
 * @package	Reforestum\Classes
 * @version	1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reforestum_Frontend Class
 * 
 * @since 1.0
 */
class Reforestum_Frontend {

	/**
	 * Initialize class functions
	 * 
	 * @since 	1.0
	 */
	public static function init(){
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Enqueue Scripts
	 * 
	 * @since 1.0
	 */
	public static function enqueue(){
		wp_enqueue_style( 'reforestum', REFORESTUM_PLUGIN_URI . '/public/css/reforestum.css', array(), REFORESTUM_PLUGIN_VERSION, 'all' );
		wp_enqueue_script( 'reforestum', REFORESTUM_PLUGIN_URI . '/public/js/reforestum.js', array(), REFORESTUM_PLUGIN_VERSION, true );
	}

}