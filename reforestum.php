<?php
/**
 * Plugin Name: Reforestum - Carbon neutral products at checkout
 * Plugin URI: https://reforestum.com/wp-woocommerce-plugin/
 * Description: Turn your e-shop into a sustainable business. Reforestum’s plugin allows you to offer carbon neutral products. Decide whether you or your customers will pay for the offset and let them pick reforestation or conservation projects around the world.
 * Version: 1.2.2
 * Author: Reforestum
 * Author URI: https://reforestum.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.en.html
 * Domain Path: /languages
 * Text Domain: reforestum
 * WC requires at least: 3.0
 * WC tested up to: 4.0.1
 * 
 * @package Reforestum
 */

/**
 * Copyright (C) 2020 Reforestum
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Constants & definitions
 */
define( 'REFORESTUM_PLUGIN_VERSION', '1.0' );
define( 'REFORESTUM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'REFORESTUM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'REFORESTUM_PLUGIN_URI', plugin_dir_url( __FILE__ ) );

/**
 * Inlude required files
 */
require_once( REFORESTUM_PLUGIN_DIR . 'includes/class.reforestum.php' );
require_once( REFORESTUM_PLUGIN_DIR . 'includes/class.reforestum-settings.php' );
require_once( REFORESTUM_PLUGIN_DIR . 'includes/class.reforestum-api.php' );
require_once( REFORESTUM_PLUGIN_DIR . 'includes/class.reforestum-frontend.php' );
require_once( REFORESTUM_PLUGIN_DIR . 'includes/class.reforestum-product.php' );
require_once( REFORESTUM_PLUGIN_DIR . 'includes/class.reforestum-product-cat.php' );
require_once( REFORESTUM_PLUGIN_DIR . 'includes/class.reforestum-woocommerce.php' );

add_action( 'init', array( 'Reforestum', 'init' ) );

register_uninstall_hook( __FILE__, array( 'Reforestum', 'plugin_uninstall' ) );

if( is_admin() ){
	/**
	 * Included required admin files
	 */
	require_once( REFORESTUM_PLUGIN_DIR . 'includes/class.reforestum-admin.php' );

	/**
	 * Init Admin class
	 */
	add_action( 'init', array( 'Reforestum_Admin', 'init' ) );
}