<?php

/**
 * Plugin Name: Shipment Integration Tracking
 * Text Domain: wc-shipment-integration-tracking
 * Description: Checks order notes for tracking numbers and adds to AST shipment tracking field
 * Version: 2.0.0
 */

/* Protect php code */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/* Make sure WC is loaded */
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
   return;
}

/* Loads shipment tracking integration */
add_action( 'plugins_loaded', 'init_shipment_tracking_integration', 11 );

/*
* AST: Set tracking number from order notes
*/
function init_shipment_tracking_integration() {
    add_action( 'woocommerce_rest_insert_order_note', 'check_note_for_tracking', 10, 3 );
    function check_note_for_tracking( $note, $request, $true ) {
        //check if AST is active
        if ( !function_exists( 'ast_insert_tracking_number' ) ) return;
        
        // Handle GoShippo Integration
        if( strpos( $note->comment_content, 'Shippo' ) !== false ){
            if( strpos( $note->comment_content, 'usps' ) !== false ){
                preg_match_all('!\d+!', $note->comment_content, $matches);
                $tracking_provider = 'USPS';
            } elseif( strpos( $note->comment_content, 'ups' ) !== false ){
                preg_match_all('\'\\b1Z[A-Z0-9]{16}\\b\'', $note->comment_content, $matches);
                $tracking_provider = 'UPS';
            }

            $tracking_number = implode(' ', $matches[0]);
        }

        // Handle Pirate Ship Integration
        if( strpos( $note->comment_content, 'Order shipped via' ) !== false ){
            if( strpos( $note->comment_content, 'Order shipped via USPS' ) !== false ){
                preg_match('!\d+!', $note->comment_content, $matches);
                $tracking_provider = 'USPS';
            } elseif( strpos( $note->comment_content, 'Order shipped via UPS' ) !== false ){
                preg_match('\'\\b1Z[A-Z0-9]{16}\\b\'', $note->comment_content, $matches);
                $tracking_provider = 'UPS';
            }

            $tracking_number = implode(' ', $matches);
        }

        // Set variables that apply to every integration
        $order_id = $request['order_id'];
        $status_shipped = 0;

        // Actually set tracking number with AST
        ast_insert_tracking_number($order_id, $tracking_number, $tracking_provider, $status_shipped );
    };
}
