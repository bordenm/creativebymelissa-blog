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
 * Lightweight check for whether an email address has a real Gravatar.
 * Hits gravatar.com/avatar/{hash}?d=404 once per email and caches the
 * 200/404 result in a transient for 24 hours to avoid repeating the
 * lookup. On network errors we fail open (return true) and cache that
 * briefly so the rendering path falls through to the normal Gravatar
 * URL — at worst the user sees Gravatar's mystery person, which is
 * fine.
 */
function pixel_has_gravatar( $email ) {
    if ( empty( $email ) ) {
        return false;
    }
    $hash      = md5( strtolower( trim( $email ) ) );
    $cache_key = 'pxhg_' . $hash;
    $cached    = get_transient( $cache_key );
    if ( false !== $cached ) {
        return '1' === $cached;
    }
    $response = wp_remote_head(
        'https://www.gravatar.com/avatar/' . $hash . '?d=404',
        array(
            'timeout'     => 3,
            'redirection' => 0,
        )
    );
    if ( is_wp_error( $response ) ) {
        // Network hiccup — assume yes and cache briefly so we don't
        // hammer Gravatar on every render.
        set_transient( $cache_key, '1', 5 * MINUTE_IN_SECONDS );
        return true;
    }
    $has = ( 200 === wp_remote_retrieve_response_code( $response ) );
    set_transient( $cache_key, $has ? '1' : '0', DAY_IN_SECONDS );
    return $has;
}

/**
 * Themed pixel avatar for comment authors WITHOUT a Gravatar. Real
 * Gravatars (like Melissa's own) pass through unchanged so commenters
 * who put effort into setting one up are honored. Anyone else gets a
 * consistent themed circle: their first initial in Jersey 15 uppercase
 * on one of four palette colors (eggplant, navy, sage, gold), picked
 * via a stable hash of their name so the same person always lands on
 * the same color across comments. Jersey 15 (the same font as post
 * titles) reads cleanly at avatar size where Pixelify Sans's lowercase
 * "e" was too curly.
 *
 * Detection is via pixel_has_gravatar() — a HEAD request to Gravatar
 * with d=404, cached in a 24-hour transient per email so we only do
 * the lookup once per commenter per day.
 *
 * The CSS lives in style.css under .pixel-avatar; this function only
 * emits the markup with three CSS custom properties (--pa-size,
 * --pa-bg, --pa-fg) so layout and palette are fully owned by CSS.
 */
add_filter( 'get_avatar', function ( $avatar, $id_or_email, $size, $default, $alt ) {
    $email = '';
    $name  = '';

    if ( is_numeric( $id_or_email ) ) {
        $user = get_userdata( (int) $id_or_email );
        if ( $user ) {
            $email = $user->user_email;
            $name  = $user->display_name;
        }
    } elseif ( is_string( $id_or_email ) ) {
        // Common case: email passed in as string (e.g., from comments).
        $email = $id_or_email;
        $user  = get_user_by( 'email', $email );
        if ( $user ) {
            $name = $user->display_name;
        } else {
            global $wpdb;
            $name = $wpdb->get_var( $wpdb->prepare(
                "SELECT comment_author FROM {$wpdb->comments} WHERE comment_author_email = %s ORDER BY comment_ID DESC LIMIT 1",
                $email
            ) );
        }
    } elseif ( is_object( $id_or_email ) ) {
        // Pull email from comment objects, user objects, or WP_User.
        if ( ! empty( $id_or_email->user_email ) ) {
            $email = $id_or_email->user_email;
        } elseif ( ! empty( $id_or_email->comment_author_email ) ) {
            $email = $id_or_email->comment_author_email;
        }
        if ( ! empty( $id_or_email->comment_author ) ) {
            $name = $id_or_email->comment_author;
        } elseif ( ! empty( $id_or_email->display_name ) ) {
            $name = $id_or_email->display_name;
        } elseif ( $email ) {
            $user = get_user_by( 'email', $email );
            if ( $user ) {
                $name = $user->display_name;
            }
        }
    }

    // If a real Gravatar exists for this email, return WordPress's
    // default markup unchanged so commenters who set up a Gravatar
    // see their photo. The expensive HEAD request is cached.
    if ( $email && pixel_has_gravatar( $email ) ) {
        return $avatar;
    }

    // Otherwise, render the themed pixel circle.
    if ( empty( $name ) ) {
        $name = '?';
    }
    $initial = mb_strtoupper( mb_substr( trim( (string) $name ), 0, 1 ) );

    // Theme palette — eggplant, navy, sage, gold. Pick by stable hash
    // so the same name always gets the same color.
    $palette = array( '#6B4A7A', '#3A4A5C', '#93B198', '#E0BB72' );
    $bg      = $palette[ abs( crc32( $name ) ) % count( $palette ) ];
    // Gold needs darker text for contrast; the others use cream.
    $fg = ( '#E0BB72' === $bg ) ? '#1F1F1F' : '#FFFFFF';

    $size = absint( $size );
    if ( ! $size ) {
        $size = 96;
    }

    return sprintf(
        '<span class="pixel-avatar avatar avatar-%1$d photo" aria-label="%2$s" style="--pa-size:%1$dpx;--pa-bg:%3$s;--pa-fg:%4$s;">%5$s</span>',
        $size,
        esc_attr( $name ),
        esc_attr( $bg ),
        esc_attr( $fg ),
        esc_html( $initial )
    );
}, 10, 5 );

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
