<?php


if ( ! defined( 'ABSPATH') ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce Pargo Pickup shipping class.
 *
 */

class WC_Pargo_Pickup extends WC_Shipping_Method {
    /**
	 * Sets up the shipping class.
	 *
	 */
    public function __construct( $instance_id = 0 ) {
        $this->id                 = 'pargo_pickup';
        $this->instance_id        = absint( $instance_id );
        $this->method_title       = __( 'Pargo Pickup' );
        $this->title            =  __('Pargo Pickup');
        $this->method_description = __( 'Lets customers choose a Pargo pickup point.' ); // Description shown in admin

        $this->form_fields = array(
            'enabled' => array(
                'title' 		=> __( 'Enable/Disable' ),
                'type' 			=> 'checkbox',
                'label' 		=> __( 'Enable this shipping method' ),
                'default' 		=> 'yes',
            ),
            'title' => array(
                'title' 		=> __( 'Method Title' ),
                'type' 			=> 'text',
                'description' 	=> __( 'This controls the title which the user sees during checkout.' ),
                'default'		=> __( 'Pargo Pickup' ),
                'desc_tip'		=> true
            )
        );
        
        $this->enabled  = $this->get_option( 'enabled' );
        $this->title    = $this->get_option( 'title' );
        
        $this->init();
    }

    /**
     * Init settings
     *
     */
    function init() {
        // Load the settings API
        $this->init_form_fields();
        $this->init_settings();

        // Save settings in admin if any are defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Calculate_shipping method.
     */
    public function calculate_shipping( $package = array() ) {
        $rate = array(
            'label' => $this->title,
            'cost' => '0.00',
            'calc_tax' => 'per_item'
        );

        // Register the rate
        $this->add_rate( $rate );
    }
}
?>