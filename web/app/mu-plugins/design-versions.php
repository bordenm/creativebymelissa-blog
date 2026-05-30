<?php
/**
 * Plugin Name: CBM Design Versions
 * Description: Public, session-only switching between design versions of the blog
 *              via a row of labeled pills in the site header. Newest version is the
 *              default; switching is client-side only (no cookies, no cache-vary) so
 *              a reload returns to the default. Each version is a CSS bundle scoped
 *              under a body.design--{slug} class. Add a new version by dropping its
 *              CSS in the theme and adding one entry to versions() below.
 *
 * @package cbm-design-versions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class CBM_Design_Versions {

	/**
	 * Registered design versions, NEWEST FIRST. The first entry is the default
	 * shown to every first-time visitor (and after any reload).
	 *
	 *   slug => [ label, icon (sprite id), css (theme-relative path or '' for base theme) ]
	 */
	public static function versions() {
		return array(
			'berry' => array(
				'label' => 'Berry',
				'icon'  => 'berry',
				'css'   => 'assets/designs/berry/berry.css',
			),
			'pixel' => array(
				'label' => 'Pixel',
				'icon'  => 'moon',
				'css'   => '', // the theme's own style.css IS the Pixel look — no extra bundle.
			),
		);
	}

	public static function default_slug() {
		return array_key_first( self::versions() );
	}

	public static function boot() {
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 30 );
		add_filter( 'render_block', array( __CLASS__, 'inject_switcher' ), 10, 2 );
		add_action( 'wp_body_open', array( __CLASS__, 'print_decoration' ) );
		add_filter( 'render_block_core/latest-posts', array( __CLASS__, 'tag_latest_posts_categories' ) );
	}

	/** Default body class = the newest version, so reloads land on the default. */
	public static function body_class( $classes ) {
		$classes[] = 'has-design-switcher';
		$classes[] = 'design--' . self::default_slug();
		return $classes;
	}

	/** Enqueue every version's scoped CSS bundle + the switcher's own base CSS/JS. */
	public static function enqueue() {
		$base = get_stylesheet_directory();
		$uri  = get_stylesheet_directory_uri();
		$ver  = wp_get_theme()->get( 'Version' );

		foreach ( self::versions() as $slug => $v ) {
			if ( empty( $v['css'] ) ) {
				continue;
			}
			$path = $base . '/' . $v['css'];
			wp_enqueue_style(
				'cbm-design-' . $slug,
				$uri . '/' . $v['css'],
				array( 'twentytwentyfive-pixel-style' ),
				file_exists( $path ) ? filemtime( $path ) : $ver
			);
		}

		// Switcher chrome + behaviour live inline so the plugin is self-contained.
		wp_register_style( 'cbm-switcher', false, array(), $ver );
		wp_enqueue_style( 'cbm-switcher' );
		wp_add_inline_style( 'cbm-switcher', self::switcher_css() );

		wp_register_script( 'cbm-switcher', false, array(), $ver, array( 'in_footer' => true ) );
		wp_enqueue_script( 'cbm-switcher' );
		wp_add_inline_script( 'cbm-switcher', self::switcher_js() );
	}

	/** Inline SVG sprite of the pill icons (pixel-art moon + gem strawberry + plus). */
	public static function sprite() {
		return '<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">'
			. '<symbol id="cbm-i-moon" viewBox="0 0 12 12" fill="currentColor"><rect x="3" y="0" width="4" height="1"/><rect x="2" y="1" width="6" height="1"/><rect x="1" y="2" width="5" height="1"/><rect x="0" y="3" width="5" height="1"/><rect x="0" y="4" width="4" height="2"/><rect x="0" y="6" width="2" height="1"/><rect x="3" y="6" width="1" height="1"/><rect x="0" y="7" width="4" height="1"/><rect x="0" y="8" width="5" height="1"/><rect x="1" y="9" width="5" height="1"/><rect x="2" y="10" width="6" height="1"/><rect x="3" y="11" width="4" height="1"/><rect x="6" y="5" width="1" height="1"/><rect x="5" y="6" width="3" height="1"/><rect x="6" y="7" width="1" height="1"/></symbol>'
			. '<symbol id="cbm-i-berry" viewBox="0 0 24 24"><defs><linearGradient id="cbmBerryGrad" x1="5" y1="7" x2="19" y2="22" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#4FE3CE"/><stop offset="1" stop-color="#5AA6F0"/></linearGradient></defs><rect x="11.4" y="2.4" width="1.2" height="3" rx=".6" fill="#9BE25A"/><g fill="#9BE25A"><ellipse cx="12" cy="6.2" rx="1.5" ry="3"/><ellipse cx="8.7" cy="7" rx="3" ry="1.4" transform="rotate(-28 8.7 7)"/><ellipse cx="15.3" cy="7" rx="3" ry="1.4" transform="rotate(28 15.3 7)"/></g><path d="M12 21.6C6.7 19 4.2 14.6 5 10.3 5.5 7.5 8.4 6.5 12 8 15.6 6.5 18.5 7.5 19 10.3 19.8 14.6 17.3 19 12 21.6Z" fill="url(#cbmBerryGrad)"/><g fill="#EAF3D8"><circle cx="9" cy="12" r=".62"/><circle cx="12" cy="11" r=".62"/><circle cx="15" cy="12" r=".62"/><circle cx="10.4" cy="14.4" r=".62"/><circle cx="13.6" cy="14.4" r=".62"/><circle cx="12" cy="16.6" r=".62"/></g></symbol>'
			. '</svg>';
	}

	/** Build the switcher markup (sprite + pills). */
	public static function markup() {
		$versions = self::versions();
		$default  = self::default_slug();

		$pills = '';
		foreach ( $versions as $slug => $v ) {
			$selected = ( $slug === $default ) ? 'true' : 'false';
			$pills   .= sprintf(
				'<button type="button" class="pill" role="tab" aria-selected="%1$s" data-design-target="%2$s" title="%3$s"><svg class="pi" aria-hidden="true"><use href="#cbm-i-%4$s"></use></svg><span>%3$s</span></button>',
				esc_attr( $selected ),
				esc_attr( $slug ),
				esc_html( $v['label'] ),
				esc_attr( $v['icon'] )
			);
		}

		return self::sprite()
			. '<nav class="cbm-switcher" aria-label="Choose a design version">'
			. '<span class="cbm-switcher__label">Design</span>'
			. '<div class="cbm-switcher__pills" role="tablist">' . $pills . '</div>'
			. '</nav>';
	}

	/**
	 * Drop the switcher into the header by appending it after the FIRST
	 * Site Title block (which lives in the header template part). A static
	 * guard ensures it is only added once even if the title appears again
	 * in the footer.
	 */
	public static function inject_switcher( $block_content, $block ) {
		static $done = false;
		if ( $done || empty( $block['blockName'] ) || 'core/site-title' !== $block['blockName'] ) {
			return $block_content;
		}
		if ( is_admin() ) {
			return $block_content;
		}
		$done = true;
		return $block_content . self::markup();
	}

	/**
	 * Add data-cat="{slug}" to each Latest Posts <li> so Berry (and future
	 * versions) can colour posts by category. Core's Latest Posts block does
	 * not emit category classes, so we resolve each item's post by its title
	 * link and stamp its primary category slug.
	 */
	public static function tag_latest_posts_categories( $content ) {
		if ( is_admin() || false === strpos( $content, 'wp-block-latest-posts__list' ) ) {
			return $content;
		}
		return preg_replace_callback( '#<li(\s[^>]*)?>(.*?)</li>#is', function ( $m ) {
			$attrs = isset( $m[1] ) ? $m[1] : '';
			$inner = $m[2];
			if ( false !== strpos( $attrs, 'data-cat=' ) ) {
				return $m[0];
			}
			if ( preg_match( '#href="([^"]+)"#', $inner, $h ) ) {
				$pid = url_to_postid( html_entity_decode( $h[1] ) );
				if ( $pid ) {
					$cats = get_the_category( $pid );
					if ( ! empty( $cats ) ) {
						$attrs .= ' data-cat="' . esc_attr( $cats[0]->slug ) . '"';
					}
				}
			}
			return '<li' . $attrs . '>' . $inner . '</li>';
		}, $content );
	}

	/** Berry's fixed geometric backdrop. Always in the DOM; only shown under body.design--berry via CSS. */
	public static function print_decoration() {
		echo '<div class="berry-deco" aria-hidden="true">'
			. '<span class="bshape target g1"></span>'
			. '<span class="bshape diamond g3"></span>'
			. '<span class="bshape arc g5"></span>'
			. '<span class="bshape lines g7"></span>'
			. '<span class="bshape hex g9"></span>'
			. '<span class="bshape chevron g10"></span>'
			. '<span class="bshape diamond g4"></span>'
			. '<span class="bshape target g2"></span>'
			. '</div>';
	}

	/** Base switcher chrome (layout + per-version pill looks). Skin colours live in each bundle. */
	public static function switcher_css() {
		return <<<CSS
.cbm-switcher{display:inline-flex;align-items:center;gap:.6rem;flex-wrap:wrap;margin-left:auto;}
.cbm-switcher__label{font-family:'Silkscreen',monospace;font-size:.6rem;letter-spacing:.18em;text-transform:uppercase;opacity:.75;}
.cbm-switcher__pills{display:inline-flex;align-items:center;gap:.4rem;flex-wrap:wrap;}
.cbm-switcher .pill{display:inline-flex;align-items:center;gap:.4rem;cursor:pointer;border:0;background:none;color:inherit;line-height:1;font-family:'Silkscreen',monospace;font-size:.68rem;letter-spacing:.06em;text-transform:uppercase;padding:.55em .85em;transition:transform .08s ease,box-shadow .1s ease,background .12s ease,color .12s ease,border-color .12s ease;}
.cbm-switcher .pill .pi{width:1.05em;height:1.05em;}
@media(max-width:640px){.cbm-switcher__label{display:none;}}

/* Pixel pills — match the theme's blocky cards. */
body.design--pixel .cbm-switcher__label{color:#3A4A5C;}
body.design--pixel .cbm-switcher .pill{background:#EFF1F4;color:#3A4A5C;border:3px solid #3A4A5C;border-radius:12px;box-shadow:4px 4px 0 0 #EFF1F4;}
body.design--pixel .cbm-switcher .pill:hover{transform:translate(2px,2px);box-shadow:2px 2px 0 0 #EFF1F4;color:#6B4A7A;}
body.design--pixel .cbm-switcher .pill[aria-selected="true"]{background:#6B4A7A;color:#fff;box-shadow:4px 4px 0 0 #EFF1F4;}
body.design--pixel .cbm-switcher .pill[aria-selected="true"]:hover{transform:none;color:#fff;}
CSS;
	}

	/** Client-side, session-only switching. Reload returns to the server default. */
	public static function switcher_js() {
		$slugs = array_keys( self::versions() );
		$list  = "'" . implode( "','", array_map( 'esc_js', $slugs ) ) . "'";
		return <<<JS
(function(){
  var SLUGS=[$list];
  function apply(slug){
    var b=document.body;
    SLUGS.forEach(function(s){b.classList.toggle('design--'+s, s===slug);});
    document.querySelectorAll('.cbm-switcher .pill').forEach(function(p){
      p.setAttribute('aria-selected', p.getAttribute('data-design-target')===slug ? 'true':'false');
    });
    window.scrollTo(0,0);
  }
  document.addEventListener('click', function(e){
    var pill=e.target.closest('.cbm-switcher .pill[data-design-target]');
    if(!pill) return;
    e.preventDefault();
    apply(pill.getAttribute('data-design-target'));
  });
})();
JS;
	}
}

CBM_Design_Versions::boot();
