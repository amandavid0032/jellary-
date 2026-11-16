<?php
/**
 * Plugin Name: Aura Theme Layer
 * Description: Fonts, product-card details, "Add to Bag" labels, mobile bottom navigation and newsletter sign-up for the emerald/gold design.
 */

defined( 'ABSPATH' ) || exit;

/* Fonts: Playfair Display + Plus Jakarta Sans + Material Symbols icons. */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'aura-fonts', 'https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,500;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap', [], null );
	wp_enqueue_style( 'aura-icons', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,400,0..1,0&display=block', [], null );
}, 5 );

/* Card: "★ 4.5 (12)" above title, material/plating line below title. */
add_action( 'astra_woo_shop_title_before', function () {
	global $product;
	if ( $product && $product->get_review_count() ) {
		printf(
			'<div class="aura-rating"><span class="material-symbols-outlined">star</span><b>%s</b><span>(%d)</span></div>',
			esc_html( number_format( (float) $product->get_average_rating(), 1 ) ),
			(int) $product->get_review_count()
		);
	}
} );
add_action( 'astra_woo_shop_title_after', function () {
	global $product;
	if ( ! $product ) {
		return;
	}
	$parts    = [];
	$material = wc_get_product_terms( $product->get_id(), 'pa_material', [ 'fields' => 'names' ] );
	$colours  = wc_get_product_terms( $product->get_id(), 'pa_colour', [ 'fields' => 'names' ] );
	if ( $material ) {
		$parts[] = str_replace( 'American Diamond', 'AD', $material[0] );
	}
	if ( count( $colours ) > 1 ) {
		$parts[] = count( $colours ) . ' finishes';
	} elseif ( $colours ) {
		$parts[] = $colours[0] . ( 'Oxidised' === $colours[0] ? '' : ' plated' );
	}
	if ( $parts ) {
		echo '<p class="aura-meta">' . esc_html( implode( ' · ', $parts ) ) . '</p>';
	}
} );

/* "Add to Bag" everywhere (variable products link to the product page to pick a finish). */
add_filter( 'woocommerce_product_add_to_cart_text', function ( $text, $product ) {
	return $product->is_in_stock() ? __( 'Add to Bag' ) : __( 'Sold Out' );
}, 20, 2 );
add_filter( 'woocommerce_product_single_add_to_cart_text', fn() => __( 'Add to Bag' ) );

/* Mobile bottom navigation (Home · Categories · New In · Wishlist · Profile). */
add_action( 'wp_footer', function () {
	$wishlist_page = (int) ( get_option( 'tinvwl-page' )['wishlist'] ?? 0 );
	$items         = [
		[ 'home', 'Home', home_url( '/' ), is_front_page() ],
		[ 'grid_view', 'Categories', '#categories', is_product_category() || is_shop() && empty( $_GET['orderby'] ) ],
		[ 'auto_awesome', 'New In', add_query_arg( 'orderby', 'date', wc_get_page_permalink( 'shop' ) ), isset( $_GET['orderby'] ) && 'date' === $_GET['orderby'] ],
		[ 'favorite', 'Wishlist', $wishlist_page ? get_permalink( $wishlist_page ) : '#', $wishlist_page && is_page( $wishlist_page ) ],
		[ 'person', 'Profile', wc_get_page_permalink( 'myaccount' ), is_account_page() ],
	];
	echo '<nav class="aura-bottom-nav" aria-label="Quick navigation">';
	foreach ( $items as [ $icon, $label, $url, $active ] ) {
		if ( '#categories' === $url ) {
			printf( '<button type="button" class="aura-open-menu%s"><span class="material-symbols-outlined">%s</span>%s</button>', $active ? ' is-active' : '', esc_html( $icon ), esc_html( $label ) );
			continue;
		}
		printf( '<a href="%s" class="%s"><span class="material-symbols-outlined">%s</span>%s</a>', esc_url( $url ), $active ? 'is-active' : '', esc_html( $icon ), esc_html( $label ) );
	}
	echo '</nav>';
	?>
	<script>
	document.addEventListener('click', function (e) {
		if (!e.target.closest('.aura-open-menu')) return;
		var t = document.querySelector('.ast-mobile-header-wrap .menu-toggle, .ast-mobile-header-wrap .ast-mobile-menu-trigger-minimal, .menu-toggle');
		if (t) { t.click(); } else { location.href = <?php echo wp_json_encode( wc_get_page_permalink( 'shop' ) ); ?>; }
	});
	</script>
	<?php
} );

/* Newsletter / "Circle" sign-up – stores emails in an option (view under Tools → Aura Subscribers). */
function aura_newsletter_submit() {
	$back = wp_get_referer() ?: home_url( '/' );
	if ( ! isset( $_POST['aura_nonce'] ) || ! wp_verify_nonce( $_POST['aura_nonce'], 'aura_newsletter' ) ) {
		wp_safe_redirect( add_query_arg( 'circle', 'error', $back ) . '#aura-circle' );
		exit;
	}
	$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'circle', 'invalid', $back ) . '#aura-circle' );
		exit;
	}
	$list           = (array) get_option( 'aura_subscribers', [] );
	$list[ $email ] = current_time( 'mysql' );
	update_option( 'aura_subscribers', $list, false );
	wp_safe_redirect( add_query_arg( 'circle', 'joined', $back ) . '#aura-circle' );
	exit;
}
add_action( 'admin_post_nopriv_aura_newsletter', 'aura_newsletter_submit' );
add_action( 'admin_post_aura_newsletter', 'aura_newsletter_submit' );

add_shortcode( 'aura_newsletter_form', function () {
	$status = sanitize_key( $_GET['circle'] ?? '' );
	$msg    = [ 'joined' => 'Welcome to the Circle ✨ Your 10% code is on its way.', 'invalid' => 'Please enter a valid email address.', 'error' => 'Something went wrong, please try again.' ][ $status ] ?? '';
	ob_start();
	?>
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="aura_newsletter">
		<?php wp_nonce_field( 'aura_newsletter', 'aura_nonce' ); ?>
		<input type="email" name="email" placeholder="Enter your email address" required>
		<button type="submit" class="aura-btn-gold">Claim 10% Off</button>
	</form>
	<?php if ( $msg ) : ?><p class="aura-ok"><?php echo esc_html( $msg ); ?></p><?php endif; ?>
	<?php
	return ob_get_clean();
} );

add_action( 'admin_menu', function () {
	add_management_page( 'Aura Subscribers', 'Aura Subscribers', 'manage_options', 'aura-subscribers', function () {
		$list = (array) get_option( 'aura_subscribers', [] );
		echo '<div class="wrap"><h1>Newsletter subscribers (' . count( $list ) . ')</h1><table class="widefat striped"><thead><tr><th>Email</th><th>Joined</th></tr></thead><tbody>';
		foreach ( array_reverse( $list, true ) as $email => $date ) {
			echo '<tr><td>' . esc_html( $email ) . '</td><td>' . esc_html( $date ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	} );
} );

/* Mobile "Filter" toggle on shop / category pages. */
add_action( 'astra_content_top', function () {
	if ( ! ( is_shop() || is_product_taxonomy() ) ) {
		return;
	}
	echo '<button type="button" class="aura-filter-toggle" onclick="document.body.classList.toggle(\'aura-filters-open\')"><span class="material-symbols-outlined">tune</span>Filter &amp; Refine</button>';
} );

/* Homepage hero slider: autoplay, arrows, dots, swipe. */
add_action( 'wp_footer', function () {
	if ( ! is_front_page() ) {
		return;
	}
	?>
	<script>
	(function () {
		var s = document.querySelector('.aura-slider'); if (!s) return;
		var slides = s.querySelectorAll('.aura-slide'), dots = s.querySelector('.aura-slider__dots'), i = 0, timer;
		slides.forEach(function (_, n) { var b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Go to slide ' + (n + 1)); b.onclick = function () { go(n, true); }; dots.appendChild(b); });
		function go(n, user) {
			slides[i].classList.remove('is-active'); dots.children[i].classList.remove('is-active');
			i = (n + slides.length) % slides.length;
			slides[i].classList.add('is-active'); dots.children[i].classList.add('is-active');
			if (user) restart();
		}
		function restart() { clearInterval(timer); timer = setInterval(function () { go(i + 1); }, 5500); }
		s.querySelector('.aura-slider__prev').onclick = function () { go(i - 1, true); };
		s.querySelector('.aura-slider__next').onclick = function () { go(i + 1, true); };
		var x0 = null;
		s.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
		s.addEventListener('touchend', function (e) { if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; if (Math.abs(dx) > 40) go(i + (dx < 0 ? 1 : -1), true); x0 = null; });
		s.addEventListener('mouseenter', function () { clearInterval(timer); });
		s.addEventListener('mouseleave', restart);
		go(0); restart();
	})();
	</script>
	<?php
} );
