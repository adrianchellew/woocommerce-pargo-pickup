<?php

if ( ! defined( 'ABSPATH') ) {
	exit; // Exit if accessed directly
}

/**
 * Add Pargo pickup point selector to the cart and checkout pages
 */
function add_pargo_pickup_point_selector( $method, $index ) {

    // Get the chosen shipping method
    $chosen_shipping_method = WC()->session->get( 'chosen_shipping_methods' )[0];
    // Get the chosem Pargo pickup point
    $pargo_pickup_point = WC()->session->get( 'pargo_pickup_point' );

    // Check if the current shipping method is Pargo pickup
    if ( $method->get_method_id() === 'pargo_pickup' ) {
        // Check if the chosen shipping method is also Pargo pickup
        if ( strpos( $chosen_shipping_method, 'pargo_pickup' ) !== false ) {
            // Add a hidden field to store the Pargo pickup point details.
            echo '<input hidden name="pargo_pickup_point" id="pargo_pickup_point" value="'. $pargo_pickup_point .'" class="shipping_method">';
            // Display the link that launches the Pargo iframe.
            echo '<br><a class="pargo-iframe-launcher" href="#">Choose a pickup point</a><br>';
            
            // If a Pargo pickup point has been chosen, display its details.
            if ( $pargo_pickup_point ) {
                echo '<i><b>Selected Pargo pickup point:</b></i><br>' . $pargo_pickup_point;
            }
            
            // Hide the customer's shipping address on the cart page
            if ( is_cart() ) {
                echo '<style>.woocommerce-shipping-destination, .woocommerce-shipping-calculator {display:none;}</style>';
            }
        // Remove delete the Pargo pickup point data from the session when another shipping method is chosen
        } elseif ( strpos( $chosen_shipping_method, 'pargo_pickup' ) === false && $pargo_pickup_point ) {
            WC()->session->__unset( 'pargo_pickup_point' );
        }
    }

    
}
add_action( 'woocommerce_after_shipping_rate', 'add_pargo_pickup_point_selector', 20, 2 );


/**
 * Ajax return posted Pargo pickup data
 * when the cart / checkout shipping section updates
 * 
 */
function set_pargo_pickup_point() {
    if ( isset($_POST['value']) ){

        if(empty($_POST['value']) ) {
            $value = '';
        } else {
            $value = esc_attr( $_POST['value'] );
        }

        // Update the pargo pickup point session variable
        WC()->session->set( 'pargo_pickup_point', $value );

        // Send back the data to javascript (json encoded)
        echo $value;
        
        die();
    }
}
add_action( 'wp_ajax_pargo_pickup_point', 'set_pargo_pickup_point' );
add_action( 'wp_ajax_nopriv_pargo_pickup_point', 'set_pargo_pickup_point' );

/**
 * If the chosen shipping method is Pargo pickup, prevent
 * the user from checking out without choosing a pickup point
 */
function pargo_pickup_checkout_validation() {
    
    $field_id = 'pargo_pickup_point';

    if ( isset( $_POST[$field_id] ) && empty( $_POST[$field_id] ) ) {
        wc_add_notice( __('Please choose a Pargo pickup point.', 'woocommerce'), 'error');
    }
}
add_action('woocommerce_checkout_process', 'pargo_pickup_checkout_validation');


/**
 * Change the Pargo pickup shipping method title to the pickup point
 * details when the customer checks out
 */
function update_pargo_pickup_shipping_method_title( &$item, $package_key, $package, $order ) {

    $pargo_pickup_point = isset( $_POST['pargo_pickup_point'] ) ? sanitize_text_field( $_POST['pargo_pickup_point'] ) : '';
    
    if ( $item->get_method_id() === 'pargo_pickup' && $pargo_pickup_point ) {
        // Change the Pargo pickup shipping method title to the Pargo pickup point details
        $item->set_method_title( sprintf( '%s: %s', __('Pargo Pickup Point', 'woocommerce'), $pargo_pickup_point) );
        // Remove the pargo pickup point from the session data
        WC()->session->__unset( 'pargo_pickup_point' );
    }

}
add_action( 'woocommerce_checkout_create_order_shipping_item', 'update_pargo_pickup_shipping_method_title', 20, 4 );

/**
 * Add the Pargo iframe to the cart and checkout pages
 * 
 */
function pargo_pickup_iframe() {
    
    if ( is_cart() || ( is_checkout() && ! is_wc_endpoint_url() ) ) : ?>

        <div id="pargo-modal" style="display:none; height: 100%; width:100%;position: fixed; left:0; right:0; top:0; bottom:0; background-color: rgba(105, 105, 105, 0.9); z-index: 999;">
            <div style="width:100%;text-align:center;margin:auto;padding: 30px 15px;height: 100%;">
                <iframe style="max-height: 100%; border:1px solid #EBEBEB;margin:auto;overflow:hidden;background-color: #ffffff;" name="iframe" id="iframe" height="600px" width="90%" >
                </iframe>
            </div>
        </div>
    
    <?php endif;
}
add_action('wp_footer', 'pargo_pickup_iframe');

/**
 * Add the Pargo pickup front-end JavaScript to 
 * the cart and checkout pages
 */
function pargo_pickup_js() {

    if ( is_cart() || ( is_checkout() && ! is_wc_endpoint_url() ) ) : ?>
        
        <?php $ajax_url_var = is_cart() ? 'wc_cart_params' : 'wc_checkout_params'; ?>
        <?php $ajax_update_trigger = is_cart() ? 'wc_update_cart' : 'update_checkout'; ?>

        <script>
            
            jQuery(document).ready(function($) {

                // Listener which waits for a response returned from the Pargo points iframe
                if (window.addEventListener) {
                    window.addEventListener("message", selectPargoPoint, false);
                } else {
                    window.attachEvent("onmessage", selectPargoPoint);
                }

                // Pargo points iframe launcher function
                function displayIFrame(){
                    $('#iframe').attr('src', 'https://map.pargo.co.za/?token=YQw7kd9fQAdkxKefS3GW8PNCRXBuqg').show();
                    $('#pargo-modal').show();
                }

                // Launch Pargo points iframe on checkout page
                $('#order_review').on('click', function(e){
                    if (e.target.classList.contains('pargo-iframe-launcher')){
                        e.preventDefault();
                        displayIFrame();
                    }
                });

                // Launch Pargo points iframe on cart page
                $('.cart-collaterals').on('click', function(e){
                    if (e.target.classList.contains('pargo-iframe-launcher')){
                        e.preventDefault();
                        displayIFrame();
                    }
                });

                // Function called by the Pargo points iframe when a user selects a particular point.
                function selectPargoPoint(item){
                    if (typeof item.data === 'object') {
                        
                        $('#pargo-modal').hide();
                        $('#iframe').hide();
                        
                        // Get the returned data
                        let pargoPickupPoint = (item.data['storeName'] !== '') ? item.data['storeName'] + ', ' : '';
                        pargoPickupPoint += (item.data['pargoPointCode'] !== '') ? item.data['pargoPointCode'] + ', ' : '';
                        pargoPickupPoint += (item.data['addressSms'] !== '') ? item.data['address1'] + ', ' : '';
                        pargoPickupPoint += (item.data['city'] !== '') ? item.data['city'] + ', ' : '';
                        pargoPickupPoint += (item.data['businessHours'] !== '') ? item.data['businessHours'] : '';
                        
                        // Populate Pargo pickup point field and get field value
                        $('body').find('#pargo_pickup_point').val(pargoPickupPoint)
                        const value = $('body').find('#pargo_pickup_point').val();

                        // Post the Pargo pickup point field value
                        $.ajax({
                            type: 'POST',
                            url: <?php echo $ajax_url_var; ?>.ajax_url,
                            data: {
                                'action': 'pargo_pickup_point',
                                'value': value
                            },
                            success: function (result) {
                                $('body').trigger('<?php echo $ajax_update_trigger; ?>');
                            }
                        });

                    }
                    return false;
                }

            });
        </script>

    <?php endif;
}
add_action('wp_footer', 'pargo_pickup_js');
?>