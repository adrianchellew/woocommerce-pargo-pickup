<?php
/**
 * WooCommerce Pargo Pickup
 * 
 * @package           WooCommerce-Pargo-Pickup
 * @author            Adrian Chellew
 * @copyright         Copyright (c) 2021 Adrian Chellew.
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name: WooCommerce Pargo Pickup
 * Description: A WooCommerce shipping method that implements the Pargo Points page via an iframe and allows customers to select a Pargo pickup point.
 * Version: 1.0.0
 * Requires at least: 5.2
 * Author: Adrian Chellew
 * Author URI: https://github.com/adrianchellew
 * 
 * WC tested up to: 5.1.0
 * 
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (  ! defined( 'ABSPATH') ) {
	exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
    /**
     * Set up the Pargo pickup shipping method class
     */
    function pargo_pickup_shipping_method_init() {
		
        if ( ! class_exists( 'WC_Pargo_Pickup' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'includes/wc-class-pargo-pickup.php' );
		}
	}
	add_action( 'woocommerce_shipping_init', 'pargo_pickup_shipping_method_init' );

    /**
     * Add the Pargo pickup shipping method to the list of WooCommerce shipping methods
     */
    function add_pargo_pickup_shipping_method( $methods ) {
		$methods['pargo_pickup'] = 'WC_Pargo_Pickup';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_pargo_pickup_shipping_method' );

    /**
     * Pargo pickup functions file
     */
    require_once( plugin_dir_path( __FILE__ ) . 'includes/functions.php' );

}
?>