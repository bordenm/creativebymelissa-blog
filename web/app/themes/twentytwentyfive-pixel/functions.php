<?php
/**
 * Twenty Twenty-Five Pixel — child theme functions.
 *
 * @package twentytwentyfive-pixel
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue Google Fonts and the child theme stylesheet on the front end.
 */
add_action( 'wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'ttfp-google-fonts',
        'https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400..700&family=Silkscreen:wght@400;700&family=VT323&family=Press+Start+2P&family=Jersey+15&family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );

    wp_enqueue_style(
        'twentytwentyfive-pixel-style',
        get_stylesheet_uri(),
        array( 'ttfp-google-fonts' ),
        wp_get_theme()->get( 'Version' )
    );
}, 20 );

/**
 * Make the pixel styles visible inside the block editor too.
 */
add_action( 'after_setup_theme', function () {
    add_editor_style( 'style.css' );
} );

/**
 * Load the same Google Fonts in the block editor for accurate previews.
 */
add_action( 'enqueue_block_editor_assets', function () {
    wp_enqueue_style(
        'ttfp-google-fonts-editor',
        'https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400..700&family=Silkscreen:wght@400;700&family=VT323&family=Press+Start+2P&family=Jersey+15&family=Inter:wght@400;500;600;700&display=swap',
        array(),
        null
    );
} );
