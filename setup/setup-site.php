<?php
/**
 * Site design: pages, homepage, menus, Astra header/footer, shop sidebar filters,
 * wishlist & WhatsApp settings, custom CSS.
 *
 * Run: ./wp.sh eval-file /setup/setup-site.php   (safe to re-run)
 */

require_once '/setup/lib-images.php';

$store = get_bloginfo( 'name' );

function jw_page( $slug, $title, $content, $meta = [] ) {
	$existing = get_page_by_path( $slug );
	$data     = [
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_name'    => $slug,
		'post_title'   => $title,
		'post_content' => $content,
	];
	if ( $existing ) {
		$data['ID'] = $existing->ID;
	}
	$id = wp_insert_post( $data );
	foreach ( $meta as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	return $id;
}

$full_width = [
	'ast-site-content-layout'  => 'full-width-container',
	'site-content-style'       => 'unboxed',
	'site-sidebar-layout'      => 'no-sidebar',
	'site-post-title'          => 'disabled',
	'ast-featured-img'         => 'disabled',
];

/* ------------------------------------------------------------------ */
/* Homepage                                                            */
/* ------------------------------------------------------------------ */
$banner_id = (int) get_option( 'jw_banner_id' );
if ( ! $banner_id || ! get_post( $banner_id ) ) {
	$banner_id = jw_banner( 'The Festive Edit', 'Up to 60% off', 'SHOP NOW', 'set', 'gold', '#9B1B30', 'banner-festive-edit.jpg' );
	update_option( 'jw_banner_id', $banner_id );
}
$banner2_id = (int) get_option( 'jw_banner2_id' );
if ( ! $banner2_id || ! get_post( $banner2_id ) ) {
	$banner2_id = jw_banner( 'Bridal Collection', 'New season', 'EXPLORE', 'necklace', 'gold', '#1E6B4F', 'banner-bridal.jpg', '#F3EDE3', '#E3D3BD' );
	update_option( 'jw_banner2_id', $banner2_id );
}
$banner_url  = wp_get_attachment_url( $banner_id );
$banner2_url = wp_get_attachment_url( $banner2_id );
$shop_url    = get_permalink( wc_get_page_id( 'shop' ) );
$sets_url    = get_term_link( 'jewellery-sets', 'product_cat' );

$cat_html = '';
foreach ( [ 'earrings', 'necklaces', 'bangles-bracelets', 'rings', 'anklets', 'jewellery-sets' ] as $slug ) {
	$t   = get_term_by( 'slug', $slug, 'product_cat' );
	$img = wp_get_attachment_image_url( get_term_meta( $t->term_id, 'thumbnail_id', true ), 'woocommerce_thumbnail' );
	$cat_html .= sprintf(
		'<a class="jw-cat" href="%s"><span class="jw-cat__img"><img src="%s" alt="%s" loading="lazy"/></span><span class="jw-cat__name">%s</span></a>',
		esc_url( get_term_link( $t ) ),
		esc_url( $img ),
		esc_attr( $t->name ),
		esc_html( $t->name )
	);
}

$home = <<<HTML
<!-- wp:image {"id":{$banner_id},"sizeSlug":"full","linkDestination":"custom","align":"full","className":"jw-hero"} -->
<figure class="wp-block-image alignfull size-full jw-hero"><a href="{$shop_url}"><img src="{$banner_url}" alt="The Festive Edit – up to 60% off" class="wp-image-{$banner_id}"/></a></figure>
<!-- /wp:image -->

<!-- wp:html -->
<div class="jw-usp">
<div><strong>Free Shipping</strong><span>On orders above ₹499</span></div>
<div><strong>Cash on Delivery</strong><span>Available across India</span></div>
<div><strong>Easy Returns</strong><span>7-day hassle-free returns</span></div>
<div><strong>Secure Payments</strong><span>UPI, cards &amp; net banking</span></div>
</div>
<!-- /wp:html -->

<!-- wp:heading {"textAlign":"center","className":"jw-section-title"} -->
<h2 class="wp-block-heading has-text-align-center jw-section-title">Shop by Category</h2>
<!-- /wp:heading -->

<!-- wp:html -->
<div class="jw-cats">{$cat_html}</div>
<!-- /wp:html -->

<!-- wp:heading {"textAlign":"center","className":"jw-section-title"} -->
<h2 class="wp-block-heading has-text-align-center jw-section-title">New Arrivals</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[products limit="8" columns="4" orderby="date" order="DESC"]
<!-- /wp:shortcode -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="{$shop_url}">View All Jewellery</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:image {"id":{$banner2_id},"sizeSlug":"full","linkDestination":"custom","className":"jw-banner2"} -->
<figure class="wp-block-image size-full jw-banner2"><a href="{$sets_url}"><img src="{$banner2_url}" alt="Bridal collection" class="wp-image-{$banner2_id}"/></a></figure>
<!-- /wp:image -->

<!-- wp:heading {"textAlign":"center","className":"jw-section-title"} -->
<h2 class="wp-block-heading has-text-align-center jw-section-title">Best Sellers</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[products limit="4" columns="4" best_selling="true"]
<!-- /wp:shortcode -->

<!-- wp:heading {"textAlign":"center","className":"jw-section-title"} -->
<h2 class="wp-block-heading has-text-align-center jw-section-title">Deals Under ₹499</h2>
<!-- /wp:heading -->

<!-- wp:shortcode -->
[sale_products limit="4" columns="4" orderby="price" order="ASC"]
<!-- /wp:shortcode -->

<!-- wp:html -->
<div class="jw-newsletter"><h3>Join the {$store} family</h3><p>Get 10% off your first order + early access to new launches.</p>[contact-form-placeholder]</div>
<!-- /wp:html -->
HTML;
$home    = str_replace( '[contact-form-placeholder]', '<p><a class="button" href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '">Sign up now</a></p>', $home );
$home_id = jw_page( 'home', 'Home', $home, $full_width );
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $home_id );

/* ------------------------------------------------------------------ */
/* Info & policy pages (fill in the [brackets] before launch)          */
/* ------------------------------------------------------------------ */
$pages = [
	'about-us'        => [ 'About Us', "<!-- wp:paragraph -->\n<p>Welcome to {$store}! We bring you trendy, affordable artificial jewellery – from everyday studs to bridal kundan sets – designed for the modern Indian woman.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>[Write your brand story here: who you are, where you are based, what makes your designs special.]</p>\n<!-- /wp:paragraph -->" ],
	'contact-us'      => [ 'Contact Us', "<!-- wp:paragraph -->\n<p><strong>Email:</strong> [support@yourdomain.com]<br><strong>Phone / WhatsApp:</strong> [+91 XXXXX XXXXX]<br><strong>Hours:</strong> Mon–Sat, 10 AM – 7 PM</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p><strong>Address:</strong> [Your registered business address]</p>\n<!-- /wp:paragraph -->" ],
	'shipping-policy' => [ 'Shipping Policy', "<!-- wp:list -->\n<ul class=\"wp-block-list\"><!-- wp:list-item -->\n<li>Free shipping on all prepaid and COD orders above ₹499. A flat ₹49 is charged on orders below ₹499.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Orders are dispatched within 1–2 business days.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>Delivery takes 3–7 business days depending on your location.</li>\n<!-- /wp:list-item -->\n\n<!-- wp:list-item -->\n<li>You will receive a tracking link by SMS/email once your order ships.</li>\n<!-- /wp:list-item --></ul>\n<!-- /wp:list -->" ],
	'returns-refunds' => [ 'Returns & Refunds', "<!-- wp:paragraph -->\n<p>[Review with your business before launch.] We accept returns within 7 days of delivery for unused products in original packaging. Earrings cannot be returned for hygiene reasons unless damaged.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:paragraph -->\n<p>Refunds for prepaid orders are made to the original payment method within 5–7 business days. COD refunds are made by bank transfer/UPI.</p>\n<!-- /wp:paragraph -->" ],
	'terms-conditions' => [ 'Terms & Conditions', "<!-- wp:paragraph -->\n<p>[Add your terms and conditions here. Have them reviewed before going live.]</p>\n<!-- /wp:paragraph -->" ],
	'faq'             => [ 'FAQ', "<!-- wp:heading {\"level\":4} -->\n<h4 class=\"wp-block-heading\">Is Cash on Delivery available?</h4>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Yes, COD is available across India.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":4} -->\n<h4 class=\"wp-block-heading\">Will the jewellery tarnish?</h4>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>Keep it away from water and perfume and store it in the pouch provided to keep the shine for longer.</p>\n<!-- /wp:paragraph -->\n\n<!-- wp:heading {\"level\":4} -->\n<h4 class=\"wp-block-heading\">How long does delivery take?</h4>\n<!-- /wp:heading -->\n\n<!-- wp:paragraph -->\n<p>3–7 business days after dispatch.</p>\n<!-- /wp:paragraph -->" ],
	'sale'            => [ 'Sale', "<!-- wp:shortcode -->\n[sale_products limit=\"24\" columns=\"4\" paginate=\"true\"]\n<!-- /wp:shortcode -->" ],
];
$page_ids = [];
foreach ( $pages as $slug => [ $title, $content ] ) {
	$page_ids[ $slug ] = jw_page( $slug, $title, $content, 'sale' === $slug ? [ 'site-sidebar-layout' => 'no-sidebar' ] : [] );
}
$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
if ( $privacy ) {
	wp_update_post( [ 'ID' => $privacy, 'post_status' => 'publish' ] );
	$page_ids['privacy-policy'] = $privacy;
}
update_option( 'woocommerce_terms_page_id', $page_ids['terms-conditions'] );
// Remove WP sample content.
foreach ( [ 'hello-world' => 'post', 'sample-page' => 'page' ] as $slug => $type ) {
	$p = get_page_by_path( $slug, OBJECT, $type );
	if ( $p ) {
		wp_delete_post( $p->ID, true );
	}
}

/* ------------------------------------------------------------------ */
/* Menus                                                               */
/* ------------------------------------------------------------------ */
function jw_menu( $name, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		foreach ( wp_get_nav_menu_items( $menu->term_id ) ?: [] as $it ) {
			wp_delete_post( $it->ID, true );
		}
		$id = $menu->term_id;
	} else {
		$id = wp_create_nav_menu( $name );
	}
	foreach ( $items as $pos => $it ) {
		$args = [ 'menu-item-status' => 'publish', 'menu-item-position' => $pos + 1, 'menu-item-title' => $it[1] ];
		if ( 'cat' === $it[0] ) {
			$t     = get_term_by( 'slug', $it[2], 'product_cat' );
			$args += [ 'menu-item-type' => 'taxonomy', 'menu-item-object' => 'product_cat', 'menu-item-object-id' => $t->term_id ];
		} elseif ( 'page' === $it[0] ) {
			$args += [ 'menu-item-type' => 'post_type', 'menu-item-object' => 'page', 'menu-item-object-id' => $it[2] ];
		} else {
			$args += [ 'menu-item-type' => 'custom', 'menu-item-url' => $it[2] ];
		}
		if ( ! empty( $it[3] ) ) {
			$args['menu-item-classes'] = $it[3];
		}
		wp_update_nav_menu_item( $id, 0, $args );
	}
	return $id;
}

$primary = jw_menu(
	'Main Menu',
	[
		[ 'page', 'All Jewellery', wc_get_page_id( 'shop' ) ],
		[ 'cat', 'Earrings', 'earrings' ],
		[ 'cat', 'Necklaces', 'necklaces' ],
		[ 'cat', 'Bangles & Bracelets', 'bangles-bracelets' ],
		[ 'cat', 'Rings', 'rings' ],
		[ 'cat', 'Anklets', 'anklets' ],
		[ 'cat', 'Sets', 'jewellery-sets' ],
		[ 'page', 'Sale', $page_ids['sale'], 'jw-menu-sale' ],
	]
);
$footer_shop = jw_menu(
	'Footer – Shop',
	[
		[ 'cat', 'Earrings', 'earrings' ],
		[ 'cat', 'Necklaces', 'necklaces' ],
		[ 'cat', 'Bangles & Bracelets', 'bangles-bracelets' ],
		[ 'cat', 'Rings', 'rings' ],
		[ 'cat', 'Jewellery Sets', 'jewellery-sets' ],
	]
);
$footer_help = jw_menu(
	'Footer – Help',
	[
		[ 'page', 'Shipping Policy', $page_ids['shipping-policy'] ],
		[ 'page', 'Returns & Refunds', $page_ids['returns-refunds'] ],
		[ 'page', 'FAQ', $page_ids['faq'] ],
		[ 'page', 'Track Order / My Account', wc_get_page_id( 'myaccount' ) ],
	]
);
$footer_co = jw_menu(
	'Footer – Company',
	[
		[ 'page', 'About Us', $page_ids['about-us'] ],
		[ 'page', 'Contact Us', $page_ids['contact-us'] ],
		[ 'page', 'Privacy Policy', $page_ids['privacy-policy'] ?? 0 ],
		[ 'page', 'Terms & Conditions', $page_ids['terms-conditions'] ],
	]
);
set_theme_mod( 'nav_menu_locations', [ 'primary' => $primary, 'mobile_menu' => $primary ] );

/* ------------------------------------------------------------------ */
/* Widgets: shop sidebar filters + footer columns                      */
/* ------------------------------------------------------------------ */
$sidebars = [ 'wp_inactive_widgets' => [] ];
$w        = [];
$add      = function ( $base, $settings, $sidebar ) use ( &$w, &$sidebars ) {
	$w[ $base ]   = $w[ $base ] ?? [ '_multiwidget' => 1 ];
	$n            = count( $w[ $base ] );
	$w[ $base ][ $n ] = $settings;
	$sidebars[ $sidebar ][] = "{$base}-{$n}";
};
$add( 'woocommerce_layered_nav_filters', [ 'title' => 'Active filters' ], 'astra-woo-shop-sidebar' );
$add( 'woocommerce_product_categories', [ 'title' => 'Category', 'orderby' => 'order', 'count' => 1, 'hierarchical' => 1, 'dropdown' => 0, 'show_children_only' => 0, 'hide_empty' => 1, 'max_depth' => '' ], 'astra-woo-shop-sidebar' );
$add( 'woocommerce_price_filter', [ 'title' => 'Price' ], 'astra-woo-shop-sidebar' );
$add( 'woocommerce_layered_nav', [ 'title' => 'Colour', 'attribute' => 'colour', 'display_type' => 'list', 'query_type' => 'or' ], 'astra-woo-shop-sidebar' );
$add( 'woocommerce_layered_nav', [ 'title' => 'Material', 'attribute' => 'material', 'display_type' => 'list', 'query_type' => 'or' ], 'astra-woo-shop-sidebar' );
$add( 'woocommerce_layered_nav', [ 'title' => 'Occasion', 'attribute' => 'occasion', 'display_type' => 'list', 'query_type' => 'or' ], 'astra-woo-shop-sidebar' );
$add( 'custom_html', [ 'title' => $store, 'content' => '<p>Trendy artificial &amp; fashion jewellery, delivered across India.</p><p>[Instagram] · [Facebook] · [YouTube]</p>' ], 'footer-widget-1' );
$add( 'nav_menu', [ 'title' => 'Shop', 'nav_menu' => $footer_shop ], 'footer-widget-2' );
$add( 'nav_menu', [ 'title' => 'Help', 'nav_menu' => $footer_help ], 'footer-widget-3' );
$add( 'nav_menu', [ 'title' => 'Company', 'nav_menu' => $footer_co ], 'footer-widget-4' );
foreach ( $w as $base => $inst ) {
	update_option( "widget_{$base}", $inst );
}
$sidebars['array_version'] = 3;
update_option( 'sidebars_widgets', $sidebars );

/* ------------------------------------------------------------------ */
/* Astra theme settings                                                */
/* ------------------------------------------------------------------ */
$row = fn( $prefix, $cols ) => array_fill_keys( array_map( fn( $c ) => "{$prefix}_{$c}", $cols ), [] );
$d5  = [ 'left', 'left_center', 'center', 'right_center', 'right' ];
$d3  = [ 'left', 'center', 'right' ];

$desktop                          = [ 'popup' => [ 'popup_content' => [ 'mobile-menu' ] ], 'above' => $row( 'above', $d5 ), 'primary' => $row( 'primary', $d5 ), 'below' => $row( 'below', $d5 ) ];
$desktop['above']['above_center'] = [ 'html-1' ];
$desktop['primary']['primary_left']  = [ 'logo' ];
$desktop['primary']['primary_right'] = [ 'search', 'account', 'woo-cart' ];
$desktop['below']['below_center']    = [ 'menu-1' ];

$mobile                            = [ 'popup' => [ 'popup_content' => [ 'search', 'mobile-menu', 'account' ] ], 'above' => $row( 'above', $d3 ), 'primary' => $row( 'primary', $d3 ), 'below' => $row( 'below', $d3 ) ];
$mobile['above']['above_center']   = [ 'html-1' ];
$mobile['primary']['primary_left'] = [ 'mobile-trigger', 'logo' ];
$mobile['primary']['primary_right'] = [ 'search', 'woo-cart' ];

$footer = [
	'above'   => [ 'above_1' => [], 'above_2' => [], 'above_3' => [], 'above_4' => [], 'above_5' => [] ],
	'primary' => [ 'primary_1' => [ 'widget-1' ], 'primary_2' => [ 'widget-2' ], 'primary_3' => [ 'widget-3' ], 'primary_4' => [ 'widget-4' ], 'primary_5' => [] ],
	'below'   => [ 'below_1' => [ 'copyright' ], 'below_2' => [], 'below_3' => [], 'below_4' => [], 'below_5' => [] ],
];

$astra = array_merge(
	(array) get_option( 'astra-settings', [] ),
	[
		'global-color-palette'          => [ 'palette' => [ '#8B2346', '#6B1A36', '#1E1B1A', '#4A4543', '#FBF6F1', '#FFFFFF', '#EFE3DA', '#2A2422', '#C9A24A' ] ],
		'header-desktop-items'          => $desktop,
		'header-mobile-items'           => $mobile,
		'header-html-1'                 => '<p>✨ Free shipping on orders above ₹499 &nbsp;|&nbsp; Cash on Delivery available ✨</p>',
		'hba-header-height'             => [ 'desktop' => 38, 'tablet' => 34, 'mobile' => 34 ],
		'header-mobile-menu-items'      => [],
		'hbb-header-height'             => [ 'desktop' => 50, 'tablet' => '', 'mobile' => '' ],
		'header-search-box-type'        => 'search-box',
		'header-account-type'           => 'woocommerce',
		'header-account-login-style'    => 'icon',
		'header-account-logout-style'   => 'icon',
		'woo-header-cart-icon'          => 'bag',
		'woo-header-cart-label-display' => '',
		'woo-header-cart-click-action'  => 'flyout',
		'footer-desktop-items'          => $footer,
		'hb-footer-column'              => '4',
		'hb-footer-layout'              => [ 'desktop' => '4-equal', 'tablet' => '2-equal', 'mobile' => 'full' ],
		'hbb-footer-column'             => '1',
		'hbb-footer-layout'             => [ 'desktop' => 'full', 'tablet' => 'full', 'mobile' => 'full' ],
		'footer-copyright-editor'       => 'Copyright © [current_year] [site_title] | All rights reserved',
		'body-font-family'              => "'Jost', sans-serif",
		'body-font-weight'              => '400',
		'body-font-variant'             => '400',
		'headings-font-family'          => "'Playfair Display', serif",
		'headings-font-weight'          => '500',
		'headings-font-variant'         => '500',
		'site-content-layout'           => 'normal-width-container',
		'site-content-width'            => 1280,
		'archive-product-sidebar-layout' => 'left-sidebar',
		'single-product-sidebar-layout' => 'no-sidebar',
		'woocommerce-content-layout'    => 'normal-width-container',
		'shop-grid'                     => [ 'desktop' => 4, 'tablet' => 3, 'mobile' => 2 ],
		'shop-no-of-products'           => 12,
		'shop-product-structure'        => [ 'title', 'ratings', 'price', 'add_cart' ],
		'shop-style'                    => 'shop-page-modern-style',
		'shop-hover-style'              => 'swap',
		'product-sale-notification'     => 'sale-percentage',
		'product-sale-percent-value'    => '[value]% OFF',
		'product-sale-style'            => 'square',
		'single-product-sticky-add-to-cart' => true,
		'single-product-payments'       => true,
		'single-product-tabs-layout'    => 'accordion',
		'single-product-related-display' => true,
		'single-product-breadcrumb-disable' => false,
		'checkout-layout-type'          => 'modern',
		'cart-cross-sell-enable'        => true,
		'woo-enable-cart-button-text'   => false,
		'ast-scroll-to-top'             => true,
	]
);
update_option( 'astra-settings', $astra );

/* ------------------------------------------------------------------ */
/* Custom CSS (brand look)                                             */
/* ------------------------------------------------------------------ */
$css = file_get_contents( '/setup/store.css' );
wp_update_custom_css_post( $css, [ 'stylesheet' => 'astra' ] );

/* ------------------------------------------------------------------ */
/* Plugins: WhatsApp chat + wishlist                                   */
/* ------------------------------------------------------------------ */
$jc = (array) get_option( 'joinchat', [] );
update_option(
	'joinchat',
	array_merge(
		$jc,
		[
			'telephone'      => '919999999999',
			'message_text'   => 'Hi 👋 Need help choosing jewellery? Chat with us!',
			'message_send'   => 'Hi {SITE}, I have a question about {URL}',
			'button_tip'     => 'Chat with us',
			'position'       => 'right',
			'message_delay'  => 6,
			'whatsapp_web'   => 'no',
		]
	)
);

update_option( 'tinvwl-general', array_merge( (array) get_option( 'tinvwl-general', [] ), [ 'default_title' => 'My Wishlist', 'show_notice' => 'on', 'simple_flow' => 'off' ] ) );
update_option( 'tinvwl-add_to_wishlist', array_merge( (array) get_option( 'tinvwl-add_to_wishlist', [] ), [ 'position' => 'after', 'type' => 'link', 'show_text' => 'on', 'text' => 'Add to Wishlist', 'text_remove' => 'Remove from Wishlist' ] ) );
update_option( 'tinvwl-add_to_wishlist_catalog', array_merge( (array) get_option( 'tinvwl-add_to_wishlist_catalog', [] ), [ 'show_in_loop' => 'on', 'position' => 'above_thumb', 'type' => 'link', 'show_text' => 'off' ] ) );
update_option( 'tinvwl-wizard', true );

flush_rewrite_rules();
WP_CLI::success( 'Site design applied. Home page ID ' . $home_id );
