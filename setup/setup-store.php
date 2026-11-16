<?php
/**
 * One-time store setup: WooCommerce settings (India / INR / GST / COD),
 * attributes, categories and sample products.
 *
 * Run: ./wp.sh eval-file /setup/setup-store.php
 * All sample data is tagged with meta _jw_sample=1 (see remove-sample-data.php).
 */

require_once '/setup/lib-images.php';

/* ------------------------------------------------------------------ */
/* 1. Core WooCommerce settings                                        */
/* ------------------------------------------------------------------ */
$options = [
	'woocommerce_store_address'           => '[Your shop address]',
	'woocommerce_store_city'              => 'New Delhi',
	'woocommerce_default_country'         => 'IN:DL',
	'woocommerce_store_postcode'          => '110001',
	'woocommerce_allowed_countries'       => 'specific',
	'woocommerce_specific_allowed_countries' => [ 'IN' ],
	'woocommerce_ship_to_countries'       => '',
	'woocommerce_currency'                => 'INR',
	'woocommerce_currency_pos'            => 'left',
	'woocommerce_price_thousand_sep'      => ',',
	'woocommerce_price_decimal_sep'       => '.',
	'woocommerce_price_num_decimals'      => 0,
	'woocommerce_weight_unit'             => 'g',
	'woocommerce_dimension_unit'          => 'cm',
	'woocommerce_calc_taxes'              => 'yes',
	'woocommerce_prices_include_tax'      => 'yes',
	'woocommerce_tax_display_shop'        => 'incl',
	'woocommerce_tax_display_cart'        => 'incl',
	'woocommerce_tax_total_display'       => 'single',
	'woocommerce_tax_based_on'            => 'shipping',
	'woocommerce_enable_guest_checkout'   => 'yes',
	'woocommerce_enable_checkout_login_reminder' => 'yes',
	'woocommerce_enable_signup_and_login_from_checkout' => 'yes',
	'woocommerce_enable_myaccount_registration' => 'yes',
	'woocommerce_enable_reviews'          => 'yes',
	'woocommerce_enable_review_rating'    => 'yes',
	'woocommerce_review_rating_required'  => 'yes',
	'woocommerce_review_rating_verification_label' => 'yes',
	'woocommerce_manage_stock'            => 'yes',
	'woocommerce_notify_low_stock_amount' => 3,
	'woocommerce_hide_out_of_stock_items' => 'no',
	'woocommerce_enable_ajax_add_to_cart' => 'yes',
	'woocommerce_cart_redirect_after_add' => 'no',
	'woocommerce_catalog_columns'         => 4,
	'woocommerce_catalog_rows'            => 4,
	'woocommerce_ship_to_destination'     => 'billing',
	'woocommerce_coming_soon'             => 'no',   // local dev: site visible
	'woocommerce_store_pages_only'        => 'no',
	'woocommerce_task_list_hidden'        => 'yes',
	'woocommerce_task_list_complete'      => 'yes',
	'woocommerce_onboarding_profile'      => [ 'skipped' => true, 'completed' => true ],
	'woocommerce_show_marketplace_suggestions' => 'no',
	'woocommerce_allow_tracking'          => 'no',
	'woocommerce_email_from_name'         => get_bloginfo( 'name' ),
	'blogdescription'                     => 'Trendy artificial & fashion jewellery',
	'timezone_string'                     => 'Asia/Kolkata',
	'date_format'                         => 'd/m/Y',
	'permalink_structure'                 => '/%postname%/',
	'woocommerce_permalinks'              => [ 'product_base' => '/product', 'category_base' => 'collections', 'tag_base' => 'product-tag', 'attribute_base' => '', 'use_verbose_page_rules' => false ],
];
foreach ( $options as $k => $v ) {
	update_option( $k, $v );
}
WC_Install::create_pages();
WP_CLI::log( 'Store settings saved (INR, India, GST incl. prices, guest checkout).' );

/* ------------------------------------------------------------------ */
/* 2. GST 3% (HSN 7117 – imitation jewellery)                          */
/* ------------------------------------------------------------------ */
global $wpdb;
$has_rate = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_name='GST'" );
if ( ! $has_rate ) {
	WC_Tax::_insert_tax_rate(
		[
			'tax_rate_country'  => 'IN',
			'tax_rate_state'    => '',
			'tax_rate'          => '3.0000',
			'tax_rate_name'     => 'GST',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 1,
			'tax_rate_order'    => 0,
			'tax_rate_class'    => '',
		]
	);
}

/* ------------------------------------------------------------------ */
/* 3. Shipping: India – free above ₹499, else ₹49                      */
/* ------------------------------------------------------------------ */
$zone_exists = false;
foreach ( WC_Shipping_Zones::get_zones() as $z ) {
	if ( 'India' === $z['zone_name'] ) {
		$zone_exists = true;
	}
}
if ( ! $zone_exists ) {
	$zone = new WC_Shipping_Zone();
	$zone->set_zone_name( 'India' );
	$zone->add_location( 'IN', 'country' );
	$zone->save();

	$free_id = $zone->add_shipping_method( 'free_shipping' );
	update_option( "woocommerce_free_shipping_{$free_id}_settings", [ 'title' => 'Free Shipping', 'requires' => 'min_amount', 'min_amount' => '499', 'ignore_discounts' => 'no' ] );

	$flat_id = $zone->add_shipping_method( 'flat_rate' );
	update_option( "woocommerce_flat_rate_{$flat_id}_settings", [ 'title' => 'Standard Shipping', 'tax_status' => 'taxable', 'cost' => '49' ] );
}

/* ------------------------------------------------------------------ */
/* 4. Payments: Cash on Delivery on (Razorpay needs your API keys)     */
/* ------------------------------------------------------------------ */
update_option(
	'woocommerce_cod_settings',
	[
		'enabled'            => 'yes',
		'title'              => 'Cash on Delivery',
		'description'        => 'Pay with cash or UPI when your order is delivered.',
		'instructions'       => 'Please keep exact change or UPI ready at the time of delivery.',
		'enable_for_methods' => [],
		'enable_for_virtual' => 'no',
	]
);
foreach ( [ 'bacs', 'cheque', 'paypal' ] as $gw ) {
	$s            = get_option( "woocommerce_{$gw}_settings", [] );
	$s['enabled'] = 'no';
	update_option( "woocommerce_{$gw}_settings", $s );
}

/* ------------------------------------------------------------------ */
/* 5. Attributes: Colour (variations) + Material + Occasion            */
/* ------------------------------------------------------------------ */
$attributes = [
	'colour'   => [ 'Colour', [ 'Gold' => 'gold', 'Silver' => 'silver', 'Rose Gold' => 'rose-gold', 'Oxidised' => 'oxidised' ] ],
	'material' => [ 'Material', [ 'Kundan' => 'kundan', 'American Diamond' => 'american-diamond', 'Pearl' => 'pearl', 'Brass' => 'brass', 'Alloy' => 'alloy', 'Oxidised Silver' => 'oxidised-silver' ] ],
	'occasion' => [ 'Occasion', [ 'Daily Wear' => 'daily-wear', 'Party Wear' => 'party-wear', 'Festive' => 'festive', 'Bridal' => 'bridal' ] ],
];
foreach ( $attributes as $slug => [ $label, $terms ] ) {
	if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
		wc_create_attribute( [ 'name' => $label, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ] );
	}
	$tax = 'pa_' . $slug;
	if ( ! taxonomy_exists( $tax ) ) {
		register_taxonomy( $tax, 'product', [ 'hierarchical' => false ] );
	}
	foreach ( $terms as $name => $tslug ) {
		if ( ! term_exists( $tslug, $tax ) ) {
			wp_insert_term( $name, $tax, [ 'slug' => $tslug ] );
		}
	}
}
delete_transient( 'wc_attribute_taxonomies' );
WP_CLI::log( 'Attributes: Colour, Material, Occasion.' );

/* ------------------------------------------------------------------ */
/* 6. Categories with round images                                     */
/* ------------------------------------------------------------------ */
$categories = [
	'earrings'            => [ 'Earrings', 'earrings', 'gold', '#1E6B4F', 'Jhumkas, studs, chandbalis and drops for every look.' ],
	'necklaces'           => [ 'Necklaces', 'necklace', 'gold', '#9B1B30', 'Chokers, pendants and layered necklaces.' ],
	'bangles-bracelets'   => [ 'Bangles & Bracelets', 'bangle', 'gold', '#9B1B30', 'Kadas, bangle sets and dainty bracelets.' ],
	'rings'               => [ 'Rings', 'ring', 'rose-gold', '#F3EFE6', 'Adjustable cocktail and everyday rings.' ],
	'anklets'             => [ 'Anklets', 'anklet', 'silver', '#1F3A7A', 'Payals and anklets with ghungroo charms.' ],
	'jewellery-sets'      => [ 'Jewellery Sets', 'set', 'gold', '#9B1B30', 'Matching necklace & earring sets for festive and bridal wear.' ],
];
$cat_ids = [];
foreach ( $categories as $slug => [ $name, $type, $metal, $gem, $desc ] ) {
	$term = term_exists( $slug, 'product_cat' );
	if ( ! $term ) {
		$term = wp_insert_term( $name, 'product_cat', [ 'slug' => $slug, 'description' => $desc ] );
		$img  = jw_product_image( $type, $metal, $gem, $name, crc32( $slug ), false, 600 );
		update_term_meta( $term['term_id'], 'thumbnail_id', $img );
	}
	$cat_ids[ $slug ] = (int) $term['term_id'];
	update_term_meta( $cat_ids[ $slug ], 'order', count( $cat_ids ) );
}
// Remove default "Uncategorized" from view by renaming it.
$uncat = get_term_by( 'slug', 'uncategorized', 'product_cat' );
if ( $uncat ) {
	wp_update_term( $uncat->term_id, 'product_cat', [ 'name' => 'Other', 'slug' => 'other' ] );
}
WP_CLI::log( 'Categories created: ' . count( $cat_ids ) );

/* ------------------------------------------------------------------ */
/* 7. Sample products                                                  */
/* ------------------------------------------------------------------ */
// [name, category, image type, colours (first = default), gem, material, occasion, regular, sale, sales count, days old, featured]
$products = [
	[ 'Kundan Pearl Jhumka Earrings', 'earrings', 'earrings', [ 'gold' ], '#9B1B30', 'kundan', 'festive', 1299, 549, 340, 40, true ],
	[ 'American Diamond Drop Earrings', 'earrings', 'earrings', [ 'gold', 'silver', 'rose-gold' ], '#FFFFFF', 'american-diamond', 'party-wear', 999, 449, 280, 3, true ],
	[ 'Oxidised Chandbali Earrings', 'earrings', 'earrings', [ 'oxidised' ], '#1E6B4F', 'oxidised-silver', 'daily-wear', 799, 349, 190, 25, false ],
	[ 'Emerald Kundan Choker Necklace', 'necklaces', 'necklace', [ 'gold' ], '#1E6B4F', 'kundan', 'festive', 2499, 1199, 150, 2, true ],
	[ 'Layered Pearl Necklace', 'necklaces', 'necklace', [ 'gold', 'silver' ], '#F3EFE6', 'pearl', 'party-wear', 1499, 699, 120, 12, false ],
	[ 'Minimal Heart Pendant Necklace', 'necklaces', 'necklace', [ 'rose-gold', 'gold', 'silver' ], '#C2185B', 'alloy', 'daily-wear', 699, 299, 410, 1, true ],
	[ 'Ruby Kada Bangle Set of 2', 'bangles-bracelets', 'bangle', [ 'gold' ], '#9B1B30', 'brass', 'festive', 1599, 799, 230, 30, true ],
	[ 'AD Tennis Bracelet', 'bangles-bracelets', 'bangle', [ 'silver', 'rose-gold' ], '#FFFFFF', 'american-diamond', 'party-wear', 1199, 549, 95, 5, false ],
	[ 'Solitaire AD Adjustable Ring', 'rings', 'ring', [ 'silver', 'gold', 'rose-gold' ], '#FFFFFF', 'american-diamond', 'daily-wear', 599, 249, 520, 8, true ],
	[ 'Oxidised Statement Ring', 'rings', 'ring', [ 'oxidised' ], '#1F3A7A', 'oxidised-silver', 'daily-wear', 499, 199, 160, 45, false ],
	[ 'Ghungroo Anklet Pair', 'anklets', 'anklet', [ 'silver' ], '#1F3A7A', 'alloy', 'daily-wear', 699, 349, 210, 18, false ],
	[ 'Oxidised Beaded Anklets', 'anklets', 'anklet', [ 'oxidised' ], '#9B1B30', 'oxidised-silver', 'festive', 549, 0, 60, 4, false ],
	[ 'Bridal Kundan Necklace Set', 'jewellery-sets', 'set', [ 'gold' ], '#9B1B30', 'kundan', 'bridal', 4999, 2299, 85, 6, true ],
	[ 'AD Necklace & Earrings Set', 'jewellery-sets', 'set', [ 'silver', 'gold' ], '#1F3A7A', 'american-diamond', 'party-wear', 2999, 1399, 140, 10, true ],
	[ 'Temple Coin Necklace Set', 'jewellery-sets', 'set', [ 'gold' ], '#1E6B4F', 'brass', 'festive', 3499, 1699, 70, 22, false ],
	[ 'Pearl Stud Earrings', 'earrings', 'earrings', [ 'gold', 'silver' ], '#F3EFE6', 'pearl', 'daily-wear', 399, 199, 600, 0, false ],
];

$colour_names = [ 'gold' => 'Gold', 'silver' => 'Silver', 'rose-gold' => 'Rose Gold', 'oxidised' => 'Oxidised' ];
$material_names = [ 'kundan' => 'Kundan', 'american-diamond' => 'American Diamond', 'pearl' => 'Pearl', 'brass' => 'Brass', 'alloy' => 'Alloy', 'oxidised-silver' => 'Oxidised Silver' ];

function jw_attr( $slug, $term_slugs, $variation, $pos ) {
	$a = new WC_Product_Attribute();
	$a->set_id( wc_attribute_taxonomy_id_by_name( $slug ) );
	$a->set_name( 'pa_' . $slug );
	$ids = [];
	foreach ( (array) $term_slugs as $t ) {
		$ids[] = get_term_by( 'slug', $t, 'pa_' . $slug )->term_id;
	}
	$a->set_options( $ids );
	$a->set_position( $pos );
	$a->set_visible( true );
	$a->set_variation( $variation );
	return $a;
}

$sku = 1001;
foreach ( $products as $i => [ $name, $cat, $type, $colours, $gem, $material, $occasion, $regular, $sale, $sales, $days, $featured ] ) {
	$slug = sanitize_title( $name );
	if ( get_page_by_path( $slug, OBJECT, 'product' ) ) {
		$sku++;
		continue;
	}
	$variable = count( $colours ) > 1;
	$p        = $variable ? new WC_Product_Variable() : new WC_Product_Simple();
	$mat      = $material_names[ $material ];

	$p->set_name( $name );
	$p->set_slug( $slug );
	$p->set_status( 'publish' );
	$p->set_sku( 'JW-' . $sku );
	$p->set_category_ids( [ $cat_ids[ $cat ] ] );
	$p->set_featured( $featured );
	$p->set_date_created( time() - $days * DAY_IN_SECONDS );
	$p->set_weight( (string) wp_rand( 8, 60 ) );
	$p->set_short_description(
		"<ul><li>Material: {$mat}, high-quality plating</li><li>Lightweight & comfortable for long wear</li><li>Skin-friendly, lead &amp; nickel safe finish</li><li>Comes in a gift-ready box</li></ul>"
	);
	$p->set_description(
		"<p>Add instant elegance to your look with our <strong>{$name}</strong>. Crafted in {$mat} with a premium finish, this piece pairs beautifully with both ethnic and western outfits.</p>"
		. '<h4>Product details</h4><ul><li>Plating: ' . implode( ' / ', array_map( fn( $c ) => $colour_names[ $c ], $colours ) ) . '</li><li>Occasion: ' . ucwords( str_replace( '-', ' ', $occasion ) ) . '</li><li>Package contents: 1 ' . strtolower( $type === 'set' ? 'set' : ( in_array( $type, [ 'earrings', 'anklet' ], true ) ? 'pair' : 'piece' ) ) . '</li></ul>'
		. '<h4>Care instructions</h4><ul><li>Keep away from water, perfume and moisture</li><li>Store in the zip-lock pouch / box provided</li><li>Wipe gently with a soft dry cloth after use</li></ul>'
	);
	$p->set_reviews_allowed( true );
	$p->set_attributes(
		[
			jw_attr( 'colour', $colours, $variable, 0 ),
			jw_attr( 'material', [ $material ], false, 1 ),
			jw_attr( 'occasion', [ $occasion ], false, 2 ),
		]
	);

	if ( ! $variable ) {
		$p->set_regular_price( (string) $regular );
		if ( $sale ) {
			$p->set_sale_price( (string) $sale );
		}
		$p->set_manage_stock( true );
		$p->set_stock_quantity( wp_rand( 8, 60 ) );
	} else {
		$p->set_default_attributes( [ 'pa_colour' => $colours[0] ] );
	}

	$main    = jw_product_image( $type, $colours[0], $gem, $name, $i );
	$gallery = [ jw_product_image( $type, $colours[0], $gem, $name, $i + 1, false ) ];
	$p->set_image_id( $main );
	$p->set_gallery_image_ids( $gallery );
	$pid = $p->save();
	update_post_meta( $pid, '_jw_sample', 1 );
	update_post_meta( $pid, 'total_sales', $sales );

	if ( $variable ) {
		foreach ( $colours as $ci => $c ) {
			$v = new WC_Product_Variation();
			$v->set_parent_id( $pid );
			$v->set_attributes( [ 'pa_colour' => $c ] );
			$v->set_sku( 'JW-' . $sku . '-' . strtoupper( substr( $c, 0, 2 ) ) );
			$v->set_regular_price( (string) $regular );
			if ( $sale ) {
				$v->set_sale_price( (string) $sale );
			}
			$v->set_manage_stock( true );
			$v->set_stock_quantity( wp_rand( 5, 40 ) );
			$v->set_image_id( 0 === $ci ? $main : jw_product_image( $type, $c, $gem, $name, $i ) );
			$v->save();
		}
		WC_Product_Variable::sync( $pid );
	}
	WP_CLI::log( sprintf( '  + %-36s %s ₹%d%s', $name, $variable ? '(' . count( $colours ) . ' colours)' : '', $sale ?: $regular, $sale ? " (MRP ₹{$regular})" : '' ) );
	$sku++;
}

/* ------------------------------------------------------------------ */
/* 8. Sample reviews (local testing only – removed by cleanup script)  */
/* ------------------------------------------------------------------ */
$reviewers = [ 'Priya S.', 'Ananya R.', 'Neha K.', 'Ritika M.', 'Sneha P.', 'Kavya J.', 'Pooja D.', 'Meera T.' ];
$texts     = [
	5 => [ 'Looks exactly like the pictures, very pretty!', 'Got so many compliments at the wedding.', 'Lightweight and comfortable, loved it.', 'Beautiful finish and quick delivery.' ],
	4 => [ 'Nice quality for the price.', 'Good product, packaging could be better.', 'Pretty design, slightly smaller than expected.' ],
	3 => [ 'Okay product, colour a little different.' ],
];
$product_ids = get_posts( [ 'post_type' => 'product', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => '_jw_sample' ] );
foreach ( $product_ids as $pid ) {
	if ( get_comments( [ 'post_id' => $pid, 'count' => true ] ) ) {
		continue;
	}
	$n = wp_rand( 2, 5 );
	for ( $k = 0; $k < $n; $k++ ) {
		$rating = [ 5, 5, 5, 4, 4, 3 ][ wp_rand( 0, 5 ) ];
		$cid    = wp_insert_comment(
			[
				'comment_post_ID'      => $pid,
				'comment_author'       => $reviewers[ wp_rand( 0, count( $reviewers ) - 1 ) ],
				'comment_author_email' => 'sample' . wp_rand( 1, 999 ) . '@example.com',
				'comment_content'      => $texts[ $rating ][ wp_rand( 0, count( $texts[ $rating ] ) - 1 ) ],
				'comment_type'         => 'review',
				'comment_approved'     => 1,
				'comment_date'         => gmdate( 'Y-m-d H:i:s', time() - wp_rand( 1, 30 ) * DAY_IN_SECONDS ),
			]
		);
		update_comment_meta( $cid, 'rating', $rating );
		update_comment_meta( $cid, 'verified', 1 );
		update_comment_meta( $cid, '_jw_sample', 1 );
	}
	$prod   = wc_get_product( $pid );
	$counts = WC_Comments::get_rating_counts_for_product( $prod );
	$prod->set_rating_counts( $counts );
	$prod->set_average_rating( WC_Comments::get_average_rating_for_product( $prod ) );
	$prod->set_review_count( WC_Comments::get_review_count_for_product( $prod ) );
	$prod->save();
}
wc_delete_product_transients();
WP_CLI::success( 'Store setup complete: ' . count( $product_ids ) . ' sample products.' );
