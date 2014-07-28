<?php
/**
 * WooCommerce Boleto Template.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wp_query, $woocommerce;

// Support for plugin older versions.
$boleto_code = isset( $_GET['ref'] ) ? $_GET['ref'] : $wp_query->query_vars['boleto'];

// Test if exist ref.
if ( isset( $boleto_code ) ) {

	// Sanitize the ref.
	$ref = sanitize_title( $boleto_code );

	// Gets Order id.
	$order_id = woocommerce_get_order_id_by_order_key( $ref );

	if ( $order_id ) {
		// Gets the data saved from boleto.
		$order = new WC_Order( $order_id );
		$order_data = get_post_meta( $order_id, 'wc_boleto_data', true );

		// Gets current bank.
		$settings = get_option( 'woocommerce_boleto_settings' );
		$bank = sanitize_text_field( $settings['bank'] );

		if ( $bank ) {

			// Sets the boleto details.
			$logo = sanitize_text_field( $settings['boleto_logo'] );
			$shop_name = get_bloginfo( 'name' );

			// Sets the boleto data.
			$data = array();
			foreach ( $order_data as $key => $value ) {
				$data[ $key ] = sanitize_text_field( $value );
			}

			// Sets the settings data.
			foreach ( $settings as $key => $value ) {
				if ( in_array( $key, array( 'demonstrativo1', 'demonstrativo2', 'demonstrativo3' ) ) ) {
					$data[ $key ] = str_replace( '[number]', '#' . $data['nosso_numero'], sanitize_text_field( $value ) );
				} else {
					$data[ $key ] = sanitize_text_field( $value );
				}
			}

			// Set the ticket total.
			$data['valor_boleto'] = number_format( $order->order_total, 2, ',', '' );

			// Shop data.
			$data['identificacao'] = $shop_name;

			// Client data.
			$data['sacado'] = $order->billing_first_name . ' ' . $order->billing_last_name;

			// Formatted Addresses
			$address_fields = apply_filters( 'woocommerce_order_formatted_billing_address', array(
				'first_name' => '',
				'last_name'  => '',
				'company'    => $order->billing_company,
				'address_1'  => $order->billing_address_1,
				'address_2'  => $order->billing_address_2,
				'city'       => $order->billing_city,
				'state'      => $order->billing_state,
				'postcode'   => $order->billing_postcode,
				'country'    => $order->billing_country
			), $order );

			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) {
				$address = WC()->countries->get_formatted_address( $address_fields );
			} else {
				$address = $woocommerce->countries->get_formatted_address( $address_fields );
			}

			$data['endereco1'] = sanitize_text_field( str_replace( array( '<br />', '<br/>' ), ', ', $address ) );
			$data['endereco2'] = '';

			$dadosboleto = apply_filters( 'wcboleto_data', $data, $order );

			// Include bank templates.
			include plugin_dir_path( dirname( __FILE__ ) ) . 'banks/' . $bank . '/functions.php';
			include plugin_dir_path( dirname( __FILE__ ) ) . 'banks/' . $bank . '/layout.php';

			exit;
		}
	}
}

// If an error occurred is redirected to the homepage.
wp_redirect( home_url() );
exit;
