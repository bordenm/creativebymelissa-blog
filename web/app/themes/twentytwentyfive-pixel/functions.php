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
 * Pixel moon-and-star favicon. The same shape used for the site title
 * wordmark gets reused as the favicon so the tab icon, bookmark, and
 * header all carry the same visual identity. Served as SVG (which all
 * modern browsers including iOS Safari 14+ render in browser tabs).
 * Versioned via theme version so cache busts on theme updates.
 */
add_action( 'wp_head', function () {
    $favicon_url = get_stylesheet_directory_uri() . '/assets/icons/favicon.svg';
    $version     = wp_get_theme()->get( 'Version' );
    printf(
        "<link rel=\"icon\" type=\"image/svg+xml\" href=\"%s?v=%s\">\n",
        esc_url( $favicon_url ),
        esc_attr( $version )
    );
}, 1 );

/**
 * Sticky header scroll detection — uses IntersectionObserver to toggle
 * an .is-scrolled class on the header. Pairs with the .is-scrolled rule
 * in style.css which adds the offset shadow only when the class is
 * present, so the top of the page renders cleanly without a separator.
 *
 * Two strategies depending on the page:
 *
 * 1. Homepage (any page containing svg.pixel-logo): observe the logo
 *    card itself with a rootMargin equal to the header's height. The
 *    shadow appears the moment the logo card is fully hidden behind
 *    the sticky header — so you only get the separator after you've
 *    scrolled past the hero, not on the very first scroll pixel.
 *
 * 2. Every other page (posts, archives, search, etc.): fall back to
 *    a 1x1px sentinel prepended to the body. As soon as the user
 *    scrolls past the very top, the sentinel leaves the viewport and
 *    the shadow appears. (No big hero on these pages, so a delayed
 *    trigger doesn't make sense.)
 */
add_action( 'wp_enqueue_scripts', function () {
    $script = <<<'JS'
(function () {
  if (!('IntersectionObserver' in window)) return;
  function init() {
    var header = document.querySelector('header.wp-block-template-part');
    if (!header) return;

    var logo = document.querySelector('svg.pixel-logo');
    var logoCard = logo ? logo.closest('.wp-block-html') : null;
    var target = logoCard;
    var rootMargin = '0px';

    if (target) {
      // Homepage: shrink the intersection root by the sticky header
      // height so the logo is considered "out of view" the moment it
      // disappears behind the header (not when its bottom finally
      // crosses y=0 of the raw viewport).
      var headerHeight = header.offsetHeight || 80;
      rootMargin = '-' + headerHeight + 'px 0px 0px 0px';
    } else {
      // Other pages: 1x1px sentinel at the very top of body.
      target = document.createElement('div');
      target.style.cssText = 'position:absolute;top:0;left:0;height:1px;width:1px;pointer-events:none;';
      target.setAttribute('aria-hidden', 'true');
      document.body.prepend(target);
    }

    var observer = new IntersectionObserver(function (entries) {
      header.classList.toggle('is-scrolled', !entries[0].isIntersecting);
    }, { threshold: 0, rootMargin: rootMargin });
    observer.observe(target);
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
