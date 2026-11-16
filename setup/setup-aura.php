<?php
/**
 * Applies the emerald / amber-gold "Haute Joaillerie" design (Stitch screen.png + DESIGN.md)
 * to header, homepage, footer and WooCommerce pages – desktop and mobile.
 *
 * Run: ./wp.sh eval-file /setup/setup-aura.php   (safe to re-run)
 */

require_once '/setup/lib-images.php';

$brand   = 'Aura Jewels';
$tagline = 'Haute Joaillerie';
update_option( 'blogname', $brand );
update_option( 'blogdescription', $tagline );
update_option( 'woocommerce_email_from_name', $brand );

/* ------------------------------------------------------------------ */
/* Dark-emerald editorial images (placeholders, replace with photos)   */
/* ------------------------------------------------------------------ */
function aura_dark_image( $type, $metal, $gem, $w, $h, $s, $ox, $oy, $file, $title ) {
	$W  = $w * 2;
	$H  = $h * 2;
	$im = jw_canvas( $W, $H, '#0F3328', '#051510' );
	// soft amber glow behind the piece
	for ( $i = 40; $i > 0; $i-- ) {
		$r = (int) ( min( $W, $H ) * 0.9 * $i / 40 );
		imagefilledellipse( $im, (int) ( $ox + 1000 * $s ), (int) ( $oy + 900 * $s ), (int) ( $r * 1.4 ), $r, jw_col( $im, '#E29A26', 126 ) );
	}
	jw_draw( $im, $type, $metal, $gem, $s, $ox, $oy );
	$out = imagecreatetruecolor( $w, $h );
	imagecopyresampled( $out, $im, 0, 0, 0, 0, $w, $h, $W, $H );
	imagedestroy( $im );
	return jw_attach( $out, $file, $title );
}

$hero_img = (int) get_option( 'aura_hero_img' );
if ( ! $hero_img || ! get_post( $hero_img ) ) {
	$hero_img = aura_dark_image( 'set', 'gold', '#1E6B4F', 1920, 900, 1.05, 1700, -40, 'aura-hero-heritage.jpg', 'The Royal Heritage Edit' );
	update_option( 'aura_hero_img', $hero_img );
}
$bridal_img = (int) get_option( 'aura_bridal_img' );
if ( ! $bridal_img || ! get_post( $bridal_img ) ) {
	$bridal_img = aura_dark_image( 'necklace', 'gold', '#9B1B30', 1920, 900, 1.0, 1980, -40, 'aura-bridal-suite.jpg', 'Royal Kundan Set' );
	update_option( 'aura_bridal_img', $bridal_img );
}

$sale_img = (int) get_option( 'aura_sale_img' );
if ( ! $sale_img || ! get_post( $sale_img ) ) {
	$sale_img = aura_dark_image( 'earrings', 'gold', '#9B1B30', 1920, 900, 1.0, 1880, -120, 'aura-slide-festive-sale.jpg', 'Festive Sale' );
	update_option( 'aura_sale_img', $sale_img );
}

/* ------------------------------------------------------------------ */
/* Wishlist page                                                       */
/* ------------------------------------------------------------------ */
$wl = get_page_by_path( 'wishlist' );
$wl_id = $wl ? $wl->ID : wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'wishlist', 'post_title' => 'Wishlist', 'post_content' => '<!-- wp:shortcode -->[ti_wishlistsview]<!-- /wp:shortcode -->' ] );
update_option( 'tinvwl-page', array_merge( (array) get_option( 'tinvwl-page', [] ), [ 'wishlist' => $wl_id ] ) );

/* ------------------------------------------------------------------ */
/* Homepage                                                            */
/* ------------------------------------------------------------------ */
$shop    = wc_get_page_permalink( 'shop' );
$new_in  = add_query_arg( 'orderby', 'date', $shop );
$sets    = get_term_link( 'jewellery-sets', 'product_cat' );
$hero    = wp_get_attachment_image_url( $hero_img, 'full' );
$bridal  = wp_get_attachment_image_url( $bridal_img, 'full' );
$count   = (int) wp_count_posts( 'product' )->publish;
$wa      = 'https://wa.me/' . ( get_option( 'joinchat' )['telephone'] ?? '919999999999' ) . '?text=' . rawurlencode( "Hi {$brand}, I need help choosing a gift." );
$tel     = 'tel:+' . ( get_option( 'joinchat' )['telephone'] ?? '919999999999' );

$bridal_caption = 'Royal Kundan Set';
$bp             = get_page_by_path( 'bridal-kundan-necklace-set', OBJECT, 'product' );
if ( $bp && ( $p = wc_get_product( $bp->ID ) ) ) {
	$bridal_caption .= ' • ₹' . number_format_i18n( (float) $p->get_price() );
	if ( $p->is_on_sale() ) {
		$bridal_caption .= ' <del>₹' . number_format_i18n( (float) $p->get_regular_price() ) . '</del>';
	}
	$sets = get_permalink( $bp->ID );
}

$cats = '';
$labels = [ 'earrings' => 'Earrings', 'necklaces' => 'Necklaces', 'bangles-bracelets' => 'Bangles', 'rings' => 'Rings', 'anklets' => 'Anklets', 'jewellery-sets' => 'Bridal Sets' ];
foreach ( $labels as $slug => $label ) {
	$t     = get_term_by( 'slug', $slug, 'product_cat' );
	$img   = wp_get_attachment_image_url( get_term_meta( $t->term_id, 'thumbnail_id', true ), 'woocommerce_thumbnail' );
	$cats .= sprintf( '<a class="aura-cat" href="%s"><span class="aura-cat__ring"><img src="%s" alt="%s" loading="lazy"></span><span>%s</span></a>', esc_url( get_term_link( $t ) ), esc_url( $img ), esc_attr( $t->name ), esc_html( $label ) );
}

$sale = wp_get_attachment_image_url( $sale_img, 'full' );
$deals = get_permalink( get_page_by_path( 'sale' ) );

$home = <<<HTML
<!-- wp:html -->
<section class="aura-slider" aria-label="Featured collections">
	<div class="aura-slide is-active">
		<img class="aura-slide__bg" src="{$hero}" alt="">
		<div class="aura-slide__inner"><div class="aura-slide__text">
			<span class="aura-pill">Kundan &amp; Emerald Hues</span>
			<h1>The Royal <em>Heritage Edit</em></h1>
			<p>Festive statement pieces with a rich gold-tone finish and emerald-green stones – made to be noticed.</p>
			<a class="aura-btn-gold" href="{$shop}">Discover Collection <span class="material-symbols-outlined">arrow_forward</span></a>
		</div></div>
	</div>
	<div class="aura-slide">
		<img class="aura-slide__bg" src="{$sale}" alt="" loading="lazy">
		<div class="aura-slide__inner"><div class="aura-slide__text">
			<span class="aura-pill">Festive Tyohar Sale</span>
			<h1>Up to <em>60% Off</em></h1>
			<p class="aura-slide__price">Prices starting at ₹199/-</p>
			<a class="aura-btn-gold" href="{$deals}">Shop the Sale <span class="material-symbols-outlined">arrow_forward</span></a>
		</div></div>
	</div>
	<div class="aura-slide">
		<img class="aura-slide__bg" src="{$bridal}" alt="" loading="lazy">
		<div class="aura-slide__inner"><div class="aura-slide__text">
			<span class="aura-pill">Royal Bridal Suite</span>
			<h1>Heirloom <em>Bridal Sets</em></h1>
			<p>Matching kundan necklace &amp; earring sets for your big day and every function around it.</p>
			<a class="aura-btn-gold" href="{$sets}">Explore Bridal <span class="material-symbols-outlined">arrow_forward</span></a>
		</div></div>
	</div>
	<button type="button" class="aura-slider__nav aura-slider__prev" aria-label="Previous slide"><span class="material-symbols-outlined">chevron_left</span></button>
	<button type="button" class="aura-slider__nav aura-slider__next" aria-label="Next slide"><span class="material-symbols-outlined">chevron_right</span></button>
	<div class="aura-slider__dots"></div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<div class="aura-wrap">
<div class="aura-strip"><span><span class="material-symbols-outlined">hotel_class</span>Festive Haute Edit</span><span>Up to 60% off</span></div>
</div>
<!-- /wp:html -->

<!-- wp:html -->
<div class="aura-wrap aura-section">
<div class="aura-trust">
	<div><i><span class="material-symbols-outlined">local_shipping</span></i><span><span class="t">Free Delivery</span><small>On orders above ₹499</small></span></div>
	<div><i><span class="material-symbols-outlined">verified</span></i><span><span class="t">Skin Safe</span><small>Lead &amp; nickel safe finish</small></span></div>
	<div><i><span class="material-symbols-outlined">cached</span></i><span><span class="t">7-Day Return</span><small>Easy, no-fuss returns</small></span></div>
	<div><i><span class="material-symbols-outlined">payments</span></i><span><span class="t">Secured COD</span><small>Pay when it arrives</small></span></div>
</div>
</div>
<!-- /wp:html -->

<!-- wp:html -->
<section class="aura-wrap aura-section">
<div class="aura-head"><div><p class="aura-eyebrow">The Atelier Portfolio</p><h2>Curated Categories</h2></div><a class="aura-link" href="{$shop}">View all <span class="material-symbols-outlined">arrow_forward_ios</span></a></div>
<div class="aura-cats">{$cats}</div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section class="aura-wrap aura-section aura-new">
<div class="aura-head"><div><p class="aura-eyebrow garnet">Limited Festive Batch</p><h2>New Acquisitions</h2></div><a class="aura-link" href="{$new_in}">{$count} pieces <span class="material-symbols-outlined">arrow_forward_ios</span></a></div>
[products limit="8" columns="4" orderby="date" order="DESC"]
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section class="aura-wrap aura-section">
<div class="aura-dark">
	<div>
		<p class="aura-eyebrow">Royal Bridal Suite</p>
		<h3>Heirloom Kundan &amp; Solitaire Sets</h3>
		<p>Crafted for timeless celebrations – matching necklace and earring sets for your big day and every function around it.</p>
		<div class="aura-dark__actions"><a class="aura-btn-gold" href="{$sets}">Explore Bridal Sets</a><a class="aura-square" href="{$wa}" target="_blank" rel="noopener" aria-label="Book a styling chat"><span class="material-symbols-outlined">calendar_month</span></a></div>
	</div>
	<a class="aura-dark__img" href="{$sets}"><img src="{$bridal}" alt="Royal Kundan Set" loading="lazy"><span class="aura-img-caption">{$bridal_caption}</span></a>
</div>
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section class="aura-wrap aura-section aura-best">
<div class="aura-head"><div><p class="aura-eyebrow">Loved by Connoisseurs</p><h2>Best Sellers</h2></div><span class="aura-link">Top rated</span></div>
[products limit="4" columns="4" best_selling="true"]
</section>
<!-- /wp:html -->

<!-- wp:html -->
<section class="aura-wrap aura-section aura-duo" id="aura-circle">
<div class="aura-concierge">
	<span class="aura-concierge__icon"><span class="material-symbols-outlined">redeem</span></span>
	<span class="aura-eyebrow">Personal Styling</span>
	<h3>Need Gifting Assistance?</h3>
	<p>Chat with our stylists for curated picks, sizing help and complimentary gift wrapping.</p>
	<div class="aura-concierge__btns"><a class="aura-btn-white" href="{$wa}" target="_blank" rel="noopener"><span class="material-symbols-outlined">chat</span>WhatsApp Concierge</a><a class="aura-square" href="{$tel}" aria-label="Call us"><span class="material-symbols-outlined">call</span></a></div>
	<p class="aura-fine">Mon – Sat · 10 AM – 7 PM</p>
</div>
<div class="aura-dark aura-club">
	<span class="material-symbols-outlined big">diamond</span>
	<h3>Join The Aura Circle</h3>
	<p>Enjoy 10% off your first order &amp; early access to new launches.</p>
	[aura_newsletter_form]
	<p class="aura-fine">Handcrafted • Skin Safe • Cash on Delivery</p>
</div>
</section>
<!-- /wp:html -->
HTML;

$home_id = (int) get_option( 'page_on_front' );
wp_update_post( [ 'ID' => $home_id, 'post_content' => $home ] );

/* ------------------------------------------------------------------ */
/* Astra: header (search · centered logo · wishlist/cart/account)      */
/* ------------------------------------------------------------------ */
$row = fn( $prefix, $cols ) => array_fill_keys( array_map( fn( $c ) => "{$prefix}_{$c}", $cols ), [] );
$d5  = [ 'left', 'left_center', 'center', 'right_center', 'right' ];
$d3  = [ 'left', 'center', 'right' ];

$desktop                             = [ 'popup' => [ 'popup_content' => [ 'mobile-menu' ] ], 'above' => $row( 'above', $d5 ), 'primary' => $row( 'primary', $d5 ), 'below' => $row( 'below', $d5 ) ];
$desktop['above']['above_center']    = [ 'html-1' ];
$desktop['primary']['primary_left']  = [ 'search' ];
$desktop['primary']['primary_center'] = [ 'logo' ];
$desktop['primary']['primary_right'] = [ 'html-2', 'woo-cart', 'account' ];
$desktop['below']['below_center']    = [ 'menu-1' ];

$mobile                              = [ 'popup' => [ 'popup_content' => [ 'mobile-menu' ] ], 'above' => $row( 'above', $d3 ), 'primary' => $row( 'primary', $d3 ), 'below' => $row( 'below', $d3 ) ];
$mobile['above']['above_center']     = [ 'html-1' ];
$mobile['primary']['primary_left']   = [ 'mobile-trigger', 'search' ];
$mobile['primary']['primary_center'] = [ 'logo' ];
$mobile['primary']['primary_right']  = [ 'html-2', 'woo-cart', 'account' ];

$wl_url = get_permalink( $wl_id );
$astra  = array_merge(
	(array) get_option( 'astra-settings', [] ),
	[
		'global-color-palette'         => [ 'palette' => [ '#09241C', '#835500', '#000A06', '#424845', '#F6FBF5', '#FFFFFF', '#F0EAE1', '#181D1A', '#FEB23E' ] ],
		'header-desktop-items'         => $desktop,
		'header-mobile-items'          => $mobile,
		'header-html-1'                => '<p>Complimentary delivery above ₹499 &nbsp;·&nbsp; Cash on Delivery available</p>',
		'header-html-2'                => '<a class="aura-icon" href="' . esc_url( $wl_url ) . '" aria-label="Wishlist"><span class="material-symbols-outlined">favorite</span></a>',
		'hba-header-height'            => [ 'desktop' => 30, 'tablet' => 28, 'mobile' => 28 ],
		'header-search-box-type'       => 'slide-search',
		'display-site-title-responsive' => [ 'desktop' => 1, 'tablet' => 1, 'mobile' => 1 ],
		'display-site-tagline-responsive' => [ 'desktop' => 1, 'tablet' => 1, 'mobile' => 1 ],
		'logo-title-inline'            => 0,
		'woo-header-cart-icon'         => 'bag',
		'woo-header-cart-icon-style'   => 'none',
		'woo-header-cart-badge-display' => true,
		'body-font-family'             => "'Plus Jakarta Sans', sans-serif",
		'body-font-weight'             => '400',
		'body-font-variant'            => '400',
		'headings-font-family'         => "'Playfair Display', serif",
		'headings-font-weight'         => '500',
		'headings-font-variant'        => '500',
		'site-content-width'           => 1440,
		'shop-product-structure'       => [ 'title', 'price', 'add_cart' ],
		'shop-grid'                    => [ 'desktop' => 4, 'tablet' => 3, 'mobile' => 2 ],
		'footer-copyright-editor'      => 'Copyright © [current_year] [site_title] · Handcrafted fashion jewellery',
	]
);
update_option( 'astra-settings', $astra );

/* Footer brand widget text */
$w = get_option( 'widget_custom_html', [] );
foreach ( $w as $k => $inst ) {
	if ( is_array( $inst ) && isset( $inst['title'] ) ) {
		$w[ $k ]['title']   = $brand;
		$w[ $k ]['content'] = '<p>Handcrafted fashion jewellery with a heirloom feel – kundan, polki-look, American diamond and more, delivered across India.</p><div class="aura-social"><a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a><a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M14 8h3V4h-3c-2.8 0-4 1.8-4 4.3V10H7v4h3v7h4v-7h3l1-4h-4V8.6c0-.4.3-.6.6-.6z"/></svg></a><a href="#" aria-label="YouTube"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor"><path d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.6 12 5.6 12 5.6s-6 0-7.9.5A3 3 0 0 0 2 8.2 31 31 0 0 0 1.6 12a31 31 0 0 0 .4 3.8 3 3 0 0 0 2.1 2.1c1.9.5 7.9.5 7.9.5s6 0 7.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .4-3.8 31 31 0 0 0-.4-3.8zM10 15V9l5.2 3z"/></svg></a></div>';
	}
}
update_option( 'widget_custom_html', $w );

/* CSS: replace old maroon theme with the Aura design */
wp_update_custom_css_post( file_get_contents( '/setup/aura.css' ), [ 'stylesheet' => 'astra' ] );

wc_delete_product_transients();
WP_CLI::success( "Aura design applied (home page {$home_id}, wishlist page {$wl_id})." );
