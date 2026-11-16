<?php
/**
 * Plugin Name: Jewellery Store Tweaks
 * Description: "% OFF" sale badges and small store customisations.
 */

defined( 'ABSPATH' ) || exit;

/** Highest discount % for a product (works for variable products too). */
function jw_discount_percent( $product ) {
	$max = 0;
	$items = $product->is_type( 'variable' ) ? array_map( 'wc_get_product', $product->get_visible_children() ) : [ $product ];
	foreach ( array_filter( $items ) as $p ) {
		$reg  = (float) $p->get_regular_price();
		$sale = (float) $p->get_sale_price();
		if ( $reg > 0 && $sale > 0 && $sale < $reg ) {
			$max = max( $max, round( ( $reg - $sale ) / $reg * 100 ) );
		}
	}
	return (int) $max;
}

add_filter( 'woocommerce_sale_flash', function ( $html, $post, $product ) {
	$pct = jw_discount_percent( $product );
	return $pct ? '<span class="onsale">' . $pct . '% OFF</span>' : $html;
}, 20, 3 );

/** "You save ₹X" under the main price on the product page. */
add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( ! is_product() || $product->get_id() !== get_queried_object_id() || doing_action( 'woocommerce_after_single_product_summary' ) ) {
		return $html;
	}
	$note = 'Inclusive of all taxes';
	if ( $product->is_type( 'simple' ) && $product->is_on_sale() ) {
		$save = (float) $product->get_regular_price() - (float) $product->get_sale_price();
		$note = sprintf( 'You save %s (%d%% OFF) · Inclusive of all taxes', wc_price( $save ), jw_discount_percent( $product ) );
	} elseif ( $product->is_on_sale() ) {
		$note = sprintf( 'Up to %d%% OFF · Inclusive of all taxes', jw_discount_percent( $product ) );
	}
	return $html . '<span class="jw-save">' . $note . '</span>';
}, 20, 2 );

/** Trust strip below add-to-cart. */
add_action( 'woocommerce_after_add_to_cart_form', function () {
	echo '<ul class="jw-trust"><li>🚚 Free shipping above ₹499</li><li>💵 Cash on Delivery available</li><li>↩️ 7-day easy returns</li></ul>';
} );

/** When free shipping applies, hide paid shipping options. */
add_filter( 'woocommerce_package_rates', function ( $rates ) {
	$free = array_filter( $rates, fn( $r ) => 'free_shipping' === $r->method_id );
	return $free ?: $rates;
}, 100 );
