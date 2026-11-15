<?php
/**
 * BigDinosaur theme functions.
 *
 * @package bigdinosaur
 */

defined( 'ABSPATH' ) || exit;

define( 'BIGDINOSAUR_VERSION', '0.7.18' );

/**
 * Theme setup.
 */
function bigdinosaur_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	// A featured image is only used as the post's share card (og:image); templates don't display it.
	add_theme_support( 'post-thumbnails', array( 'post' ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'bigdinosaur' ),
		)
	);

	// Block editor: mirror the site's look, offer the design tokens as presets.
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/editor.css' );
	add_theme_support(
		'editor-color-palette',
		array(
			array( 'name' => __( 'Background', 'bigdinosaur' ), 'slug' => 'paper', 'color' => '#ffffff' ),
			array( 'name' => __( 'Ink', 'bigdinosaur' ), 'slug' => 'ink', 'color' => '#222222' ),
			array( 'name' => __( 'Secondary', 'bigdinosaur' ), 'slug' => 'secondary', 'color' => '#575760' ),
			array( 'name' => __( 'Cerulean', 'bigdinosaur' ), 'slug' => 'accent', 'color' => '#1a6a9c' ),
			array( 'name' => __( 'Rule', 'bigdinosaur' ), 'slug' => 'rule', 'color' => '#b2b2be' ),
		)
	);
	add_theme_support(
		'editor-font-sizes',
		array(
			array( 'name' => __( 'Small', 'bigdinosaur' ), 'slug' => 'small', 'size' => 15 ),
			array( 'name' => __( 'Excerpt', 'bigdinosaur' ), 'slug' => 'excerpt', 'size' => 19 ),
			array( 'name' => __( 'Body', 'bigdinosaur' ), 'slug' => 'body', 'size' => 21 ),
			array( 'name' => __( 'Heading', 'bigdinosaur' ), 'slug' => 'heading', 'size' => 28 ),
			array( 'name' => __( 'Title', 'bigdinosaur' ), 'slug' => 'title', 'size' => 36 ),
		)
	);
}
add_action( 'after_setup_theme', 'bigdinosaur_setup' );

/**
 * Cache-busting version for a file inside the theme.
 */
function bigdinosaur_asset_version( $relative ) {
	$path = get_theme_file_path( $relative );
	return file_exists( $path ) ? (string) filemtime( $path ) : BIGDINOSAUR_VERSION;
}

/**
 * Front-end assets.
 */
function bigdinosaur_enqueue() {
	wp_enqueue_style( 'bigdinosaur', get_stylesheet_uri(), array(), bigdinosaur_asset_version( 'style.css' ) );

	// Post lists: load older posts in place (falls back to the paginated links).
	if ( is_home() || is_archive() || is_search() ) {
		wp_enqueue_script( 'bigdinosaur-post-list', get_theme_file_uri( 'assets/js/post-list.js' ), array(), bigdinosaur_asset_version( 'assets/js/post-list.js' ), array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'bigdinosaur_enqueue' );

/**
 * Preload the faces every page paints above the fold (body serif roman and
 * italic, the Michroma title/headings face, the Jost tagline) so they download
 * alongside the stylesheet instead of after it. The italic is included because
 * the browser only requests it once layout meets italic text, which put it
 * behind the images in the waterfall and made it flash on reload. URLs must
 * match the @font-face url()s exactly (no version query) or the preload is
 * wasted; fonts need crossorigin even same-origin. Plex Mono loads on demand.
 */
function bigdinosaur_preload_fonts() {
	foreach ( array( 'crimsonpro-normal-latin', 'crimsonpro-italic-latin', 'michroma-400-latin', 'jost-roman-latin' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_theme_file_uri( 'assets/fonts/' . $font . '.woff2' ) ) );
	}
}
add_action( 'wp_head', 'bigdinosaur_preload_fonts', 1 );

/**
 * Head metadata (core emits only the title, feed links and site icons). One
 * block per page: description, color-scheme hints, a self-canonical on every
 * page including lists and /page/N/, Open Graph and Twitter cards, and the
 * article:* set on posts.
 *
 * Posts share their own image (featured image, else the first body image when
 * it is wide enough for a large card) with twitter:card summary_large_image;
 * everything else shares the site card with twitter:card summary. Search and
 * 404 pages get only the color-scheme hints.
 */
function bigdinosaur_head_meta() {
	$post      = is_singular() ? get_queried_object() : null;
	$protected = $post && post_password_required( $post );

	$description = $protected ? '' : bigdinosaur_description();
	bigdinosaur_meta( 'name', 'description', $description );

	echo '<meta name="color-scheme" content="light dark">' . "\n";
	echo '<meta name="theme-color" media="(prefers-color-scheme: light)" content="#ffffff">' . "\n";
	echo '<meta name="theme-color" media="(prefers-color-scheme: dark)" content="#1a1a1a">' . "\n";

	$canonical = bigdinosaur_canonical_url();
	if ( '' === $canonical ) {
		return;
	}
	printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canonical ) );

	$title = bigdinosaur_share_title();
	$image = ( $post && 'post' === $post->post_type && ! $protected ) ? bigdinosaur_post_image( $post ) : null;
	$own   = null !== $image;
	if ( ! $own ) {
		$image = array(
			'url'    => get_theme_file_uri( 'assets/images/card.png' ),
			'width'  => 1200,
			'height' => 630,
			'alt'    => 'A white line drawing of a Tyrannosaurus beside the words BigDinosaur.org on a dark background',
		);
	}

	bigdinosaur_meta( 'property', 'og:type', $post && 'post' === $post->post_type ? 'article' : 'website' );
	bigdinosaur_meta( 'property', 'og:site_name', get_bloginfo( 'name', 'display' ) );
	bigdinosaur_meta( 'property', 'og:locale', get_locale() );
	bigdinosaur_meta( 'property', 'og:title', $title );
	bigdinosaur_meta( 'property', 'og:description', $description );
	bigdinosaur_meta( 'property', 'og:url', $canonical );
	bigdinosaur_meta( 'property', 'og:image', $image['url'] );
	bigdinosaur_meta( 'property', 'og:image:width', $image['width'] );
	bigdinosaur_meta( 'property', 'og:image:height', $image['height'] );
	bigdinosaur_meta( 'property', 'og:image:alt', $image['alt'] );
	bigdinosaur_meta( 'name', 'twitter:card', $own ? 'summary_large_image' : 'summary' );
	bigdinosaur_meta( 'name', 'twitter:title', $title );
	bigdinosaur_meta( 'name', 'twitter:description', $description );
	bigdinosaur_meta( 'name', 'twitter:image', $image['url'] );
	bigdinosaur_meta( 'name', 'twitter:image:alt', $image['alt'] );

	if ( $post && 'post' === $post->post_type ) {
		bigdinosaur_meta( 'property', 'article:published_time', get_the_date( 'c', $post ) );
		bigdinosaur_meta( 'property', 'article:modified_time', get_the_modified_date( 'c', $post ) );
		$author_url = get_the_author_meta( 'url', $post->post_author );
		bigdinosaur_meta( 'property', 'article:author', $author_url ? $author_url : get_author_posts_url( $post->post_author ) );
		$categories = get_the_category( $post->ID );
		if ( $categories ) {
			bigdinosaur_meta( 'property', 'article:section', $categories[0]->name );
		}
		$tags = get_the_tags( $post->ID );
		foreach ( is_array( $tags ) ? $tags : array() as $tag ) {
			bigdinosaur_meta( 'property', 'article:tag', $tag->name );
		}
	}
}
add_action( 'wp_head', 'bigdinosaur_head_meta', 2 );
// Core's canonical covers singular pages only; bigdinosaur_head_meta() prints one for every page.
remove_action( 'wp_head', 'rel_canonical' );

/**
 * One meta tag, omitted when the content is empty.
 */
function bigdinosaur_meta( $attribute, $key, $content ) {
	$content = (string) $content;
	if ( '' !== $content ) {
		printf( '<meta %s="%s" content="%s">' . "\n", $attribute, esc_attr( $key ), esc_attr( $content ) );
	}
}

/**
 * Robots: tag and category archives are thin pages of excerpts, so noindex
 * but follow; everything else, /page/N/ included, is index, follow. Core's
 * max-image-preview:large stays.
 */
function bigdinosaur_robots( $robots ) {
	if ( is_category() || is_tag() || is_tax() ) {
		$robots['noindex'] = true;
	} elseif ( empty( $robots['noindex'] ) && ! is_search() && ! is_404() ) {
		$robots['index'] = true;
	}
	if ( empty( $robots['nofollow'] ) ) {
		$robots['follow'] = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'bigdinosaur_robots' );

/**
 * The meta description text, or '' where there is none. Posts and pages use
 * the manual excerpt when one is set, otherwise the opening of the content;
 * the blog index uses the homepage intro pattern, falling back to the
 * tagline; term archives use the term description or "Posts about X"; date
 * archives "Posts from May 2026"; other archives their description.
 */
function bigdinosaur_description() {
	$text = '';
	if ( is_home() ) {
		$block = get_page_by_path( 'homepage-intro', OBJECT, 'wp_block' );
		$text  = ( $block && 'publish' === $block->post_status ) ? $block->post_content : '';
		if ( '' === bigdinosaur_description_text( $text ) ) {
			$text = get_bloginfo( 'description', 'display' );
		}
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		$text = has_excerpt( $post ) ? $post->post_excerpt : $post->post_content;
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$text = term_description();
		if ( '' === bigdinosaur_description_text( $text ) ) {
			/* translators: %s: category or tag name */
			$text = sprintf( __( 'Posts about %s', 'bigdinosaur' ), single_term_title( '', false ) );
		}
	} elseif ( is_date() ) {
		/* translators: %s: archive date, e.g. "May 2026" */
		$text = sprintf( __( 'Posts from %s', 'bigdinosaur' ), get_the_archive_title() );
	} elseif ( is_archive() ) {
		$text = get_the_archive_description();
	}
	return bigdinosaur_description_text( $text );
}

/**
 * The bare title for og:title and twitter:title: the site name on the blog
 * index, else the post, page, term or archive title (no site name, no
 * "Page N"; that is the <title>'s job).
 */
function bigdinosaur_share_title() {
	if ( is_home() ) {
		$title = get_bloginfo( 'name', 'display' );
	} elseif ( is_singular() ) {
		$title = get_the_title( get_queried_object() );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$title = single_term_title( '', false );
	} else {
		$title = get_the_archive_title();
	}
	return trim( wp_strip_all_tags( html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
}

/**
 * The page's canonical URL: core's for singular content (it handles paged
 * posts and comment pages), the pretty permalink plus /page/N/ for the blog
 * index and archives, '' for search and 404.
 */
function bigdinosaur_canonical_url() {
	global $wp_rewrite;
	if ( is_singular() ) {
		return (string) wp_get_canonical_url();
	}
	if ( is_home() ) {
		$url = is_front_page() ? home_url( '/' ) : get_permalink( (int) get_option( 'page_for_posts' ) );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_day() ) {
		$url = get_day_link( get_query_var( 'year' ), get_query_var( 'monthnum' ), get_query_var( 'day' ) );
	} elseif ( is_month() ) {
		$url = get_month_link( get_query_var( 'year' ), get_query_var( 'monthnum' ) );
	} elseif ( is_year() ) {
		$url = get_year_link( get_query_var( 'year' ) );
	} elseif ( is_author() ) {
		$url = get_author_posts_url( get_queried_object_id() );
	} else {
		return '';
	}
	if ( ! is_string( $url ) || '' === $url ) {
		return '';
	}
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	if ( $paged > 1 ) {
		$url = trailingslashit( $url ) . user_trailingslashit( $wp_rewrite->pagination_base . '/' . $paged, 'paged' );
	}
	return $url;
}

/** Narrowest body image used as a post's own share image (Twitter's large card wants 300px or more; 600 keeps the 2011-2019 title icons out). */
define( 'BIGDINOSAUR_MIN_CARD_WIDTH', 600 );

/**
 * A post's own share image as url/width/height/alt, or null to use the site
 * card. The featured image always wins; otherwise the first <img> in the
 * content, resolved through its wp-image-N class to the attachment's "large"
 * size (block markup carries no width/height attributes), or taken from the
 * tag's own attributes when the attachment is unknown. A body image narrower
 * than BIGDINOSAUR_MIN_CARD_WIDTH is skipped; an unknown width passes.
 */
function bigdinosaur_post_image( $post ) {
	if ( has_post_thumbnail( $post ) ) {
		return bigdinosaur_attachment_image( get_post_thumbnail_id( $post ) );
	}
	if ( ! preg_match( '/<img\b[^>]*>/i', $post->post_content, $m ) ) {
		return null;
	}
	$tag   = $m[0];
	$image = preg_match( '/\bwp-image-(\d+)\b/', $tag, $id ) ? bigdinosaur_attachment_image( (int) $id[1] ) : null;
	if ( ! $image ) {
		$attr = function ( $name ) use ( $tag ) {
			return preg_match( '/\b' . $name . '="([^"]*)"/i', $tag, $a ) ? html_entity_decode( $a[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) : '';
		};
		if ( '' === $attr( 'src' ) ) {
			return null;
		}
		$image = array(
			'url'    => $attr( 'src' ),
			'width'  => (int) $attr( 'width' ) ?: '',
			'height' => (int) $attr( 'height' ) ?: '',
			'alt'    => $attr( 'alt' ),
		);
	}
	if ( $image['width'] && $image['width'] < BIGDINOSAUR_MIN_CARD_WIDTH ) {
		return null;
	}
	return $image;
}

/**
 * An attachment's "large" rendition (the original when it is smaller) as
 * url/width/height/alt, or null when the attachment is missing.
 */
function bigdinosaur_attachment_image( $id ) {
	$src = wp_get_attachment_image_src( $id, 'large' );
	if ( ! $src ) {
		return null;
	}
	return array(
		'url'    => $src[0],
		'width'  => (int) $src[1] ?: '',
		'height' => (int) $src[2] ?: '',
		'alt'    => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
	);
}

/**
 * Block content to plain description text: drop non-text blocks (images,
 * More, embeds), shortcodes and tags, collapse whitespace, cut at a word
 * boundary within 155 characters.
 */
function bigdinosaur_description_text( $text, $max = 155 ) {
	$text = wp_strip_all_tags( strip_shortcodes( excerpt_remove_blocks( (string) $text ) ) );
	$text = trim( preg_replace( '/\s+/u', ' ', html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
	if ( mb_strlen( $text ) > $max ) {
		$cut  = mb_substr( $text, 0, $max );
		$text = rtrim( mb_substr( $cut, 0, (int) mb_strrpos( $cut, ' ' ) ), ' ,;:-' ) . '…';
	}
	return $text;
}

/**
 * Trim head cruft we don't need.
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );

/**
 * Color scheme: set html[data-scheme] before first paint from the stored
 * preference (localStorage key "color-scheme", same as the previous site) or
 * the OS preference.
 */
function bigdinosaur_scheme_head_script() {
	?>
<script>(function(){var k='color-scheme',v=null;try{v=localStorage.getItem(k)}catch(e){}if(v!=='light'&&v!=='dark'){v=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light'}document.documentElement.setAttribute('data-scheme',v)})();</script>
	<?php
}
add_action( 'wp_head', 'bigdinosaur_scheme_head_script', 0 );

/**
 * Color scheme toggle behavior (button#colormode in the masthead).
 */
function bigdinosaur_scheme_toggle_script() {
	?>
<script>(function(){var k='color-scheme',b=document.getElementById('colormode'),d=document.documentElement;if(!b)return;function sync(){var dark=d.getAttribute('data-scheme')==='dark';b.textContent=dark?'☀ light mode':'☾ dark mode';b.setAttribute('aria-pressed',dark?'true':'false')}sync();b.addEventListener('click',function(){var v=d.getAttribute('data-scheme')==='dark'?'light':'dark';d.setAttribute('data-scheme',v);try{localStorage.setItem(k,v)}catch(e){}sync()})})();</script>
	<?php
}
add_action( 'wp_footer', 'bigdinosaur_scheme_toggle_script', 20 );

/**
 * Inline envelope icon (replaces the Dashicons dependency: one icon is not
 * worth a 36 KB stylesheet).
 */
function bigdinosaur_icon_email() {
	return '<svg class="icon-email" width="1em" height="1em" viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path fill="currentColor" d="M2.5 4h15A1.5 1.5 0 0 1 19 5.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 1 14.5v-9A1.5 1.5 0 0 1 2.5 4Zm.5 2.1v.9l7 4.4 7-4.4v-.9l-7 4.4Z"/></svg>';
}

/**
 * Tagline. Site description is plain text in Settings; on output the author
 * name becomes an e-mail link with an envelope icon, as on the previous site.
 */
function bigdinosaur_tagline() {
	$text = esc_html( get_bloginfo( 'description', 'display' ) );
	$link = '<a href="' . esc_url( home_url( '/email/' ) ) . '">Lee Hutchinson ' . bigdinosaur_icon_email() . '<span class="screen-reader-text"> (e-mail)</span></a>';
	$html = str_replace( 'Lee Hutchinson', $link, $text );
	$html = str_replace( ' stomping on things, ', ' stomping&nbsp;on<br>things, ', $html );
	return apply_filters( 'bigdinosaur_tagline', $html, $text );
}

/**
 * Homepage intro: the synced pattern with slug "homepage-intro".
 */
function bigdinosaur_intro() {
	$block = get_page_by_path( 'homepage-intro', OBJECT, 'wp_block' );
	if ( ! $block || 'publish' !== $block->post_status ) {
		return;
	}
	echo '<div class="site-intro">' . apply_filters( 'the_content', $block->post_content ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * "Continue reading" link emitted where a post's More block sits.
 */
function bigdinosaur_more_link( $link, $text ) {
	return '<p class="read-more"><a class="more-link" href="' . esc_url( get_permalink() ) . '">' . esc_html__( 'Continue reading', 'bigdinosaur' ) . '</a></p>';
}
add_filter( 'the_content_more_link', 'bigdinosaur_more_link', 10, 2 );

/**
 * Older / newer navigation for the post list.
 */
function bigdinosaur_posts_nav() {
	$older = get_next_posts_link( esc_html__( 'Older posts', 'bigdinosaur' ) . ' <span aria-hidden="true">&rarr;</span>' );
	$newer = get_previous_posts_link( '<span aria-hidden="true">&larr;</span> ' . esc_html__( 'Newer posts', 'bigdinosaur' ) );
	if ( ! $older && ! $newer ) {
		return;
	}
	echo '<nav class="posts-nav" aria-label="' . esc_attr__( 'Posts', 'bigdinosaur' ) . '">';
	if ( $newer ) {
		echo '<span class="posts-nav-newer">' . $newer . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	if ( $older ) {
		echo '<span class="posts-nav-older">' . $older . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</nav>';
}

/**
 * Numbered navigation within a post or page containing Page Break blocks.
 * Core emits nothing for single-page content; hide links while password-locked.
 */
function bigdinosaur_page_links() {
	if ( post_password_required() ) {
		return;
	}
	wp_link_pages(
		array(
			'before' => '<nav class="page-links" aria-label="' . esc_attr__( 'Content pages', 'bigdinosaur' ) . '"><span>' . esc_html__( 'Pages:', 'bigdinosaur' ) . '</span> ',
			'after'  => '</nav>',
		)
	);
}

/**
 * Prism syntax highlighting on posts tagged "code" (same rule as the old
 * mu-plugin). Assets are the prod build, bundled with the theme.
 */
function bigdinosaur_prism() {
	if ( ! is_singular( 'post' ) || ! has_tag( 'code' ) ) {
		return;
	}
	wp_enqueue_style( 'bigdinosaur-prism', get_theme_file_uri( 'assets/css/prism.css' ), array( 'bigdinosaur' ), bigdinosaur_asset_version( 'assets/css/prism.css' ) );
	wp_enqueue_script( 'bigdinosaur-prism', get_theme_file_uri( 'assets/js/prism.js' ), array(), bigdinosaur_asset_version( 'assets/js/prism.js' ), array( 'in_footer' => true ) );
}
add_action( 'wp_enqueue_scripts', 'bigdinosaur_prism' );

/**
 * Older / newer links under a single post.
 */
function bigdinosaur_post_nav() {
	$older = get_previous_post();
	$newer = get_next_post();
	if ( ! $older && ! $newer ) {
		return;
	}
	echo '<nav class="post-nav" aria-label="' . esc_attr__( 'Adjacent posts', 'bigdinosaur' ) . '">';
	if ( $older ) {
		echo '<div class="post-nav-older"><span class="post-nav-label"><span aria-hidden="true">&larr;</span> ' . esc_html__( 'Older', 'bigdinosaur' ) . '</span><a href="' . esc_url( get_permalink( $older ) ) . '" rel="prev">' . esc_html( get_the_title( $older ) ) . '</a></div>';
	}
	if ( $newer ) {
		echo '<div class="post-nav-newer"><span class="post-nav-label">' . esc_html__( 'Newer', 'bigdinosaur' ) . ' <span aria-hidden="true">&rarr;</span></span><a href="' . esc_url( get_permalink( $newer ) ) . '" rel="next">' . esc_html( get_the_title( $newer ) ) . '</a></div>';
	}
	echo '</nav>';
}

/**
 * Archive / search header above the post list.
 */
// No "Category:"-style prefix in the title; the label line above it names the archive type.
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

function bigdinosaur_archive_header() {
	if ( ! is_archive() && ! is_search() ) {
		return;
	}
	if ( is_search() ) {
		$label = __( 'Search', 'bigdinosaur' );
		/* translators: %s: search query */
		$title = sprintf( __( 'Results for &ldquo;%s&rdquo;', 'bigdinosaur' ), esc_html( get_search_query() ) );
	} else {
		if ( is_category() ) {
			$label = __( 'Category', 'bigdinosaur' );
		} elseif ( is_tag() ) {
			$label = __( 'Tag', 'bigdinosaur' );
		} elseif ( is_author() ) {
			$label = __( 'Author', 'bigdinosaur' );
		} elseif ( is_date() ) {
			$label = __( 'Posts from', 'bigdinosaur' );
		} else {
			$label = __( 'Archive', 'bigdinosaur' );
		}
		$title = get_the_archive_title();
	}
	echo '<header class="archive-header"><p class="archive-label">' . esc_html( $label ) . '</p><h1 class="archive-title">' . $title . '</h1>'; // phpcs:ignore WordPress.Security.EscapeOutput
	the_archive_description( '<div class="archive-description">', '</div>' );
	echo '</header>';
}
