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
        'https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400..700&family=Silkscreen:wght@400;700&family=VT323&family=Press+Start+2P&family=Jersey+15&family=DM+Sans:wght@400;500;600;700&display=swap',
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
        'https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400..700&family=Silkscreen:wght@400;700&family=VT323&family=Press+Start+2P&family=Jersey+15&family=DM+Sans:wght@400;500;600;700&display=swap',
        array(),
        null
    );
} );

/**
 * Sticky header scroll detection — uses IntersectionObserver to toggle
 * an .is-scrolled class on the header once the user scrolls past the
 * very top of the page. Pairs with the .is-scrolled rule in style.css
 * which adds the offset shadow only when the class is present, so the
 * top of the page renders cleanly without any separator.
 *
 * Implementation: prepends a 1x1px transparent sentinel at the top of
 * the body and observes its viewport intersection. When the sentinel
 * is no longer intersecting (scrolled past), the header is in "stuck"
 * mode and gets the .is-scrolled class.
 */
add_action( 'wp_enqueue_scripts', function () {
    $script = <<<'JS'
(function () {
  if (!('IntersectionObserver' in window)) return;
  function init() {
    var header = document.querySelector('header.wp-block-template-part');
    if (!header) return;
    var sentinel = document.createElement('div');
    sentinel.style.cssText = 'position:absolute;top:0;left:0;height:1px;width:1px;pointer-events:none;';
    sentinel.setAttribute('aria-hidden', 'true');
    document.body.prepend(sentinel);
    var observer = new IntersectionObserver(function (entries) {
      header.classList.toggle('is-scrolled', !entries[0].isIntersecting);
    }, { threshold: 0 });
    observer.observe(sentinel);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
JS;

    wp_register_script(
        'ttfp-sticky-header',
        false,
        array(),
        wp_get_theme()->get( 'Version' ),
        array( 'in_footer' => true )
    );
    wp_enqueue_script( 'ttfp-sticky-header' );
    wp_add_inline_script( 'ttfp-sticky-header', $script );
}, 20 );
