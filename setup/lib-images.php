<?php
/**
 * Placeholder jewellery image generator (PHP GD).
 * Draws simple gold/silver/rose-gold jewellery illustrations so the store
 * looks realistic locally. Replace with real product photos before launch.
 */

const JW_FONT        = '/setup/Georgia.ttf';
const JW_FONT_ITALIC = '/setup/Georgia Italic.ttf';

const JW_METALS = [
	'gold'      => [ '#F5DE96', '#C9A24A', '#8C6A22' ],
	'silver'    => [ '#F6F7F9', '#B9BEC6', '#7D838C' ],
	'rose-gold' => [ '#F8D3C3', '#D4927A', '#9C5D49' ],
	'oxidised'  => [ '#B0B0B0', '#6E6E6E', '#3B3B3B' ],
];

const JW_BACKGROUNDS = [
	[ '#FBF1EC', '#F1DDD3' ],
	[ '#FAF6EF', '#EDE3D2' ],
	[ '#F4F1F6', '#E3DCE9' ],
	[ '#F1F5F2', '#DCE7E0' ],
];

function jw_rgb( $hex ) {
	return sscanf( $hex, '#%02x%02x%02x' );
}

function jw_col( $im, $hex, $alpha = 0 ) {
	[ $r, $g, $b ] = jw_rgb( $hex );
	return imagecolorallocatealpha( $im, $r, $g, $b, $alpha );
}

function jw_mix( $im, $a, $b, $t ) {
	[ $r1, $g1, $b1 ] = jw_rgb( $a );
	[ $r2, $g2, $b2 ] = jw_rgb( $b );
	return imagecolorallocate(
		$im,
		(int) round( $r1 + ( $r2 - $r1 ) * $t ),
		(int) round( $g1 + ( $g2 - $g1 ) * $t ),
		(int) round( $b1 + ( $b2 - $b1 ) * $t )
	);
}

function jw_darker( $hex, $f = 0.6 ) {
	[ $r, $g, $b ] = jw_rgb( $hex );
	return sprintf( '#%02x%02x%02x', $r * $f, $g * $f, $b * $f );
}

function jw_canvas( $w, $h, $c1, $c2 ) {
	$im = imagecreatetruecolor( $w, $h );
	for ( $y = 0; $y < $h; $y++ ) {
		imageline( $im, 0, $y, $w, $y, jw_mix( $im, $c1, $c2, $y / $h ) );
	}
	return $im;
}

/** Shiny metal band (ring / bangle). */
function jw_band( $im, $cx, $cy, $w, $h, $th, $metal ) {
	[ $light, $mid, $dark ] = JW_METALS[ $metal ];
	imagesetthickness( $im, 3 );
	for ( $t = 0; $t <= $th; $t += 2 ) {
		$f   = 1 - abs( ( $t / $th ) - 0.4 ) * 1.6;
		$f   = max( 0, min( 1, $f ) );
		$col = $f > 0.5 ? jw_mix( $im, $mid, $light, ( $f - 0.5 ) * 2 ) : jw_mix( $im, $dark, $mid, $f * 2 );
		imagearc( $im, (int) $cx, (int) $cy, (int) ( $w - 2 * $t ), (int) ( $h - 2 * $t ), 0, 360, $col );
	}
	imagesetthickness( $im, 1 );
}

function jw_bead( $im, $x, $y, $r, $metal ) {
	[ $light, $mid, $dark ] = JW_METALS[ $metal ];
	imagefilledellipse( $im, (int) $x, (int) $y, (int) ( 2 * $r ), (int) ( 2 * $r ), jw_col( $im, $dark ) );
	imagefilledellipse( $im, (int) ( $x - $r * 0.08 ), (int) ( $y - $r * 0.08 ), (int) ( 1.75 * $r ), (int) ( 1.75 * $r ), jw_col( $im, $mid ) );
	imagefilledellipse( $im, (int) ( $x - $r * 0.32 ), (int) ( $y - $r * 0.32 ), (int) ( 0.6 * $r ), (int) ( 0.6 * $r ), jw_col( $im, $light ) );
}

function jw_gem( $im, $x, $y, $r, $hex, $metal = null ) {
	if ( $metal ) {
		foreach ( [ 45, 135, 225, 315 ] as $a ) {
			jw_bead( $im, $x + cos( deg2rad( $a ) ) * $r * 1.02, $y + sin( deg2rad( $a ) ) * $r * 1.02, $r * 0.22, $metal );
		}
		imagefilledellipse( $im, (int) $x, (int) $y, (int) ( 2.24 * $r ), (int) ( 2.24 * $r ), jw_col( $im, JW_METALS[ $metal ][1] ) );
	}
	imagefilledellipse( $im, (int) $x, (int) $y, (int) ( 2 * $r ), (int) ( 2 * $r ), jw_col( $im, jw_darker( $hex, 0.55 ) ) );
	imagefilledellipse( $im, (int) $x, (int) ( $y - $r * 0.05 ), (int) ( 1.7 * $r ), (int) ( 1.7 * $r ), jw_col( $im, $hex ) );
	imagefilledellipse( $im, (int) ( $x - $r * 0.3 ), (int) ( $y - $r * 0.35 ), (int) ( 0.55 * $r ), (int) ( 0.4 * $r ), jw_col( $im, '#FFFFFF', 40 ) );
}

function jw_teardrop( $im, $cx, $tipy, $cy, $r, $metal ) {
	[ , $mid, $dark ] = JW_METALS[ $metal ];
	$pts = [ $cx, $tipy, $cx - $r * 0.86, $cy - $r * 0.5, $cx + $r * 0.86, $cy - $r * 0.5 ];
	imagefilledpolygon( $im, array_map( 'intval', $pts ), jw_col( $im, $dark ) );
	imagefilledellipse( $im, (int) $cx, (int) $cy, (int) ( 2 * $r ), (int) ( 2 * $r ), jw_col( $im, $dark ) );
	$pts2 = [ $cx, $tipy + 14, $cx - $r * 0.78, $cy - $r * 0.45, $cx + $r * 0.78, $cy - $r * 0.45 ];
	imagefilledpolygon( $im, array_map( 'intval', $pts2 ), jw_col( $im, $mid ) );
	imagefilledellipse( $im, (int) $cx, (int) $cy, (int) ( 1.86 * $r ), (int) ( 1.86 * $r ), jw_col( $im, $mid ) );
}

function jw_earring( $im, $cx, $top, $s, $metal, $gem ) {
	jw_bead( $im, $cx, $top, 55 * $s, $metal );
	for ( $y = $top + 60 * $s; $y < $top + 200 * $s; $y += 30 * $s ) {
		jw_bead( $im, $cx, $y, 13 * $s, $metal );
	}
	$c = $top + 430 * $s;
	jw_teardrop( $im, $cx, $top + 190 * $s, $c, 190 * $s, $metal );
	jw_gem( $im, $cx, $c, 105 * $s, $gem );
	for ( $a = 25; $a <= 155; $a += 13 ) {
		jw_bead( $im, $cx + cos( deg2rad( $a ) ) * 218 * $s, $c + sin( deg2rad( $a ) ) * 218 * $s, 22 * $s, $metal );
	}
}

function jw_necklace( $im, $cx, $cy, $rx, $ry, $s, $metal, $gem ) {
	for ( $a = 8; $a <= 172; $a += 4.2 ) {
		jw_bead( $im, $cx + cos( deg2rad( $a ) ) * $rx, $cy + sin( deg2rad( $a ) ) * $ry, 26 * $s, $metal );
	}
	$bottom = $cy + $ry;
	$c      = $bottom + 160 * $s;
	jw_teardrop( $im, $cx, $bottom + 10 * $s, $c, 120 * $s, $metal );
	jw_gem( $im, $cx, $c, 70 * $s, $gem );
	foreach ( [ 60, 120 ] as $a ) {
		$x = $cx + cos( deg2rad( $a ) ) * $rx;
		$y = $cy + sin( deg2rad( $a ) ) * $ry;
		jw_gem( $im, $x, $y + 70 * $s, 40 * $s, $gem, $metal );
	}
}

function jw_ring( $im, $cx, $cy, $s, $metal, $gem ) {
	jw_band( $im, $cx, $cy, 660 * $s, 660 * $s, 72 * $s, $metal );
	jw_gem( $im, $cx, $cy - 330 * $s, 125 * $s, $gem, $metal );
}

function jw_bangle( $im, $cx, $cy, $s, $metal, $gem ) {
	foreach ( [ -170, 170 ] as $dx ) {
		jw_band( $im, $cx + $dx * $s, $cy, 820 * $s, 820 * $s, 85 * $s, $metal );
	}
	for ( $a = 0; $a < 360; $a += 30 ) {
		jw_gem( $im, $cx - 170 * $s + cos( deg2rad( $a ) ) * 368 * $s, $cy + sin( deg2rad( $a ) ) * 368 * $s, 22 * $s, $gem );
	}
}

function jw_anklet( $im, $cx, $cy, $s, $metal, $gem ) {
	[ , $mid, $dark ] = JW_METALS[ $metal ];
	imagesetthickness( $im, (int) max( 2, 7 * $s ) );
	$i = 0;
	for ( $x = $cx - 760 * $s; $x <= $cx + 760 * $s; $x += 36 * $s, $i++ ) {
		$y = $cy + 110 * $s * sin( ( $x - $cx ) / ( 320 * $s ) );
		imagearc( $im, (int) $x, (int) $y, (int) ( 44 * $s ), (int) ( 28 * $s ), 0, 360, jw_col( $im, $i % 2 ? $mid : $dark ) );
		if ( 0 === $i % 5 ) {
			imageline( $im, (int) $x, (int) $y, (int) $x, (int) ( $y + 60 * $s ), jw_col( $im, $dark ) );
			jw_bead( $im, $x, $y + 90 * $s, 30 * $s, $metal );
		}
		if ( 2 === $i % 10 ) {
			jw_gem( $im, $x, $y, 20 * $s, $gem );
		}
	}
	imagesetthickness( $im, 1 );
}

/** Draw a piece of jewellery of $type onto $im (2000px reference canvas, scaled by $s). */
function jw_draw( $im, $type, $metal, $gem, $s = 1.0, $ox = 0, $oy = 0 ) {
	$X = fn( $v ) => $ox + $v * $s;
	$Y = fn( $v ) => $oy + $v * $s;
	switch ( $type ) {
		case 'ring':
			jw_ring( $im, $X( 1000 ), $Y( 1180 ), $s, $metal, $gem );
			break;
		case 'earrings':
			jw_earring( $im, $X( 640 ), $Y( 600 ), $s, $metal, $gem );
			jw_earring( $im, $X( 1360 ), $Y( 600 ), $s, $metal, $gem );
			break;
		case 'necklace':
			jw_necklace( $im, $X( 1000 ), $Y( 420 ), 660 * $s, 760 * $s, $s, $metal, $gem );
			break;
		case 'bangle':
			jw_bangle( $im, $X( 1000 ), $Y( 1000 ), $s, $metal, $gem );
			break;
		case 'anklet':
			jw_anklet( $im, $X( 1000 ), $Y( 980 ), $s, $metal, $gem );
			break;
		case 'set':
			jw_necklace( $im, $X( 1000 ), $Y( 330 ), 520 * $s, 600 * $s, 0.85 * $s, $metal, $gem );
			jw_earring( $im, $X( 300 ), $Y( 960 ), 0.62 * $s, $metal, $gem );
			jw_earring( $im, $X( 1700 ), $Y( 960 ), 0.62 * $s, $metal, $gem );
			break;
	}
}

function jw_text_center( $im, $text, $size, $y, $hex, $font = JW_FONT ) {
	$box = imagettfbbox( $size, 0, $font, $text );
	$w   = $box[2] - $box[0];
	imagettftext( $im, $size, 0, (int) ( ( imagesx( $im ) - $w ) / 2 ), (int) $y, jw_col( $im, $hex ), $font, $text );
}

/** Save a GD image into the media library; returns attachment ID. */
function jw_attach( $im, $filename, $title, $parent = 0 ) {
	require_once ABSPATH . 'wp-admin/includes/image.php';
	ob_start();
	imagejpeg( $im, null, 88 );
	$bits = ob_get_clean();
	imagedestroy( $im );
	$up = wp_upload_bits( $filename, null, $bits );
	if ( ! empty( $up['error'] ) ) {
		WP_CLI::error( $up['error'] );
	}
	$id = wp_insert_attachment(
		[
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $title,
			'post_status'    => 'inherit',
		],
		$up['file'],
		$parent
	);
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $up['file'] ) );
	update_post_meta( $id, '_wp_attachment_image_alt', $title );
	update_post_meta( $id, '_jw_sample', 1 );
	return $id;
}

/** Square product image with optional caption. */
function jw_product_image( $type, $metal, $gem, $title, $bg = 0, $caption = true, $size = 1000 ) {
	$big = 2000;
	[ $c1, $c2 ] = JW_BACKGROUNDS[ $bg % count( JW_BACKGROUNDS ) ];
	$im = jw_canvas( $big, $big, $c1, $c2 );
	// Soft floor shadow.
	imagefilledellipse( $im, 1000, 1690, 1300, 100, jw_col( $im, '#000000', 118 ) );
	jw_draw( $im, $type, $metal, $gem, $caption ? 1.1 : 1.18, $caption ? -100 : -180, $caption ? -170 : -190 );
	$out = imagecreatetruecolor( $size, $size );
	imagecopyresampled( $out, $im, 0, 0, 0, 0, $size, $size, $big, $big );
	imagedestroy( $im );
	if ( $caption ) {
		jw_text_center( $out, $title, (int) ( $size * 0.028 ), $size * 0.93, '#6B5B55' );
	}
	return jw_attach( $out, sanitize_title( $title ) . '-' . $metal . '-' . $bg . '.jpg', $title );
}

/** Wide homepage banner with baked-in text (like most Indian jewellery stores). */
function jw_banner( $heading, $sub, $cta, $type, $metal, $gem, $filename, $c1 = '#F7E6DE', $c2 = '#E8C9BC' ) {
	$W  = 2400;
	$H  = 900;
	$im = jw_canvas( $W * 2, $H * 2, $c1, $c2 );
	jw_draw( $im, $type, $metal, $gem, 0.85, 2650, 120 );
	$out = imagecreatetruecolor( $W, $H );
	imagecopyresampled( $out, $im, 0, 0, 0, 0, $W, $H, $W * 2, $H * 2 );
	imagedestroy( $im );
	imagettftext( $out, 34, 0, 180, 260, jw_col( $out, '#8B2346' ), JW_FONT, strtoupper( $sub ) );
	imagettftext( $out, 104, 0, 170, 430, jw_col( $out, '#2A1E1B' ), JW_FONT_ITALIC, $heading );
	imagettftext( $out, 40, 0, 180, 530, jw_col( $out, '#4A3F3B' ), JW_FONT, 'Handcrafted artificial jewellery for every occasion' );
	imagefilledrectangle( $out, 180, 610, 560, 700, jw_col( $out, '#8B2346' ) );
	$box = imagettfbbox( 30, 0, JW_FONT, $cta );
	imagettftext( $out, 30, 0, (int) ( 370 - ( $box[2] - $box[0] ) / 2 ), 670, jw_col( $out, '#FFFFFF' ), JW_FONT, $cta );
	return jw_attach( $out, $filename, $heading );
}
