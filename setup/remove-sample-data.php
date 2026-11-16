<?php
/**
 * Deletes all sample products, sample reviews and generated placeholder images
 * (everything tagged _jw_sample). Run BEFORE adding real products / going live:
 *   ./wp.sh eval-file /setup/remove-sample-data.php
 * Categories, pages, menus and settings are kept.
 */
$n = 0;
foreach ( get_comments( [ 'meta_key' => '_jw_sample', 'type' => 'review', 'status' => 'all' ] ) as $c ) {
	wp_delete_comment( $c->comment_ID, true );
	$n++;
}
WP_CLI::log( "Deleted {$n} sample reviews." );
$n = 0;
foreach ( get_posts( [ 'post_type' => 'product', 'numberposts' => -1, 'meta_key' => '_jw_sample', 'post_status' => 'any' ] ) as $p ) {
	$prod = wc_get_product( $p->ID );
	if ( $prod && $prod->is_type( 'variable' ) ) {
		foreach ( $prod->get_children() as $vid ) {
			wp_delete_post( $vid, true );
		}
	}
	wp_delete_post( $p->ID, true );
	$n++;
}
WP_CLI::log( "Deleted {$n} sample products." );
$keep = array_filter( array_map( 'intval', [ get_option( 'jw_banner_id' ), get_option( 'jw_banner2_id' ) ] ) );
foreach ( get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] ) as $t ) {
	$keep[] = (int) get_term_meta( $t->term_id, 'thumbnail_id', true );
}
$n = 0;
foreach ( get_posts( [ 'post_type' => 'attachment', 'numberposts' => -1, 'meta_key' => '_jw_sample', 'fields' => 'ids' ] ) as $id ) {
	if ( ! in_array( $id, $keep, true ) ) {
		wp_delete_attachment( $id, true );
		$n++;
	}
}
WP_CLI::log( "Deleted {$n} placeholder product images (kept banners & category images – replace them in Media)." );
wc_delete_product_transients();
WP_CLI::success( 'Sample data removed.' );
