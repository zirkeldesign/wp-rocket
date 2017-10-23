<?php
defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * Launch WP Rocket minification process (HTML, CSS and JavaScript)
 *
 * @since 2.10  New process for minification without concatenation
 * @since 1.3.0 This process is called via the new filter rocket_buffer
 * @since 1.1.6 Minify inline CSS and JavaScript
 * @since 1.0
 *
 * @param string $buffer HTML content.
 * @return string Modified HTML content
 */
function rocket_minify_process( $buffer ) {
	$enable_css          = get_rocket_option( 'minify_css' );
	$enable_js           = get_rocket_option( 'minify_js' );
	$enable_google_fonts = get_rocket_option( 'minify_google_fonts' );

	if ( $enable_css || $enable_js || $enable_google_fonts ) {
		list( $buffer, $conditionals ) = rocket_extract_ie_conditionals( $buffer );
	}

	// Minify JavaScript.
	if ( $enable_js && ( ! defined( 'DONOTMINIFYJS' ) || ! DONOTMINIFYJS ) && ! is_rocket_post_excluded_option( 'minify_js' ) ) {
		$buffer = rocket_minify_files( $buffer, 'css' );
	}

	// Minify CSS.
	if ( $enable_css && ( ! defined( 'DONOTMINIFYCSS' ) || ! DONOTMINIFYCSS ) && ! is_rocket_post_excluded_option( 'minify_css' ) ) {
		$buffer = rocket_minify_files( $buffer, 'js' );
	}
	
	// Concatenate Google Fonts.
	if ( $enable_google_fonts ) {
		$buffer = rocket_concatenate_google_fonts( $buffer );
	}

	if ( $enable_css || $enable_js || $enable_google_fonts ) {
		$buffer = rocket_inject_ie_conditionals( $buffer, $conditionals );
	}

	return $buffer;
}
add_filter( 'rocket_buffer', 'rocket_minify_process', 13 );

/**
 * Minifies inline HTML
 *
 * @since 2.10 Do the HTML minification independently and hook it later to prevent conflicts
 * @since 1.1.12
 *
 * @param string $buffer HTML content.
 * @return string Updated HTML content
 */
function rocket_minify_html( $buffer ) {
	if ( ! get_rocket_option( 'minify_html' ) || is_rocket_post_excluded_option( 'minify_html' ) ) {
		return $buffer;
	}

	$html_options = array(
		'cssMinifier' => 'rocket_minify_inline_css',
		'jsMinifier'  => 'rocket_minify_inline_js',
	);

	/**
	 * Filter options of minify inline HTML
	 *
	 * @since 1.1.12
	 *
	 * @param array $html_options Options of minify inline HTML.
	 */
	$html_options = apply_filters( 'rocket_minify_html_options', $html_options );

	return Minify_HTML::minify( $buffer, $html_options );
}
add_filter( 'rocket_buffer', 'rocket_minify_html', 20 );

/**
 * Fix issue with SSL and minification
 *
 * @since 2.3
 *
 * @param string $url An url to filter to set the scheme to https if needed.
 * @return string Updated URL
 */
function rocket_fix_ssl_minify( $url ) {
	if ( is_ssl() && false === strpos( $url, 'https://' ) && ! in_array( rocket_extract_url_component( $url, PHP_URL_HOST ), get_rocket_cnames_host( array( 'all', 'css_js', 'css', 'js' ) ), true ) ) {
		$url = str_replace( 'http://', 'https://', $url );
	}

	return $url;
}
add_filter( 'rocket_css_url', 'rocket_fix_ssl_minify' );
add_filter( 'rocket_js_url', 'rocket_fix_ssl_minify' );

/**
 * Compatibility with WordPress multisite with subfolders websites
 *
 * @since 2.6.5
 *
 * @param string $url minified file URL.
 * @return string Updated minified file URL
 */
function rocket_fix_minify_multisite_path_issue( $url ) {
	if ( ! is_multisite() || is_main_site() ) {
		return $url;
	}

	// Current blog infos.
	$blog_id  = get_current_blog_id();
	$bloginfo = get_blog_details( $blog_id, false );

	// Main blog infos.
	$main_blog_id = 1;

	if ( ! empty( $GLOBALS['current_site']->blog_id ) ) {
		$main_blog_id = absint( $GLOBALS['current_site']->blog_id );
	}
	elseif ( defined( 'BLOG_ID_CURRENT_SITE' ) ) {
		$main_blog_id = absint( BLOG_ID_CURRENT_SITE );
	}
	elseif ( defined( 'BLOGID_CURRENT_SITE' ) ) { // deprecated.
		$main_blog_id = absint( BLOGID_CURRENT_SITE );
	}

	$main_bloginfo = get_blog_details( $main_blog_id, false );

	if ( '/' !== $bloginfo->path ) {
		$first_path_pos = strpos( $url, $bloginfo->path );
		if ( false !== $first_path_pos ) {
			$url = substr_replace( $url, $main_bloginfo->path, $first_path_pos, strlen( $bloginfo->path ) );
		}
	}

	return $url;
}
//add_filter( 'rocket_pre_minify_path', 'rocket_fix_minify_multisite_path_issue' );

/**
 * Compatibility with multilingual plugins & multidomain configuration
 *
 * @since 2.6.13 Regression Fix: Apply CDN on minified CSS and JS files by checking the CNAME host
 * @since 2.6.8
 *
 * @param string $url Minified file URL.
 * @return string Updated minified file URL
 */
function rocket_minify_i18n_multidomain( $url ) {
	if ( ! rocket_has_i18n() ) {
		return $url;
	}

	$url_host = rocket_extract_url_component( $url, PHP_URL_HOST );
	$zone     = array( 'all', 'css_and_js' );
	$current_filter = current_filter();

	// Add only CSS zone.
	if ( 'rocket_css_url' === $current_filter ) {
		$zone[] = 'css';
	}

	// Add only JS zone.
	if ( 'rocket_js_url' === $current_filter ) {
		$zone[] = 'js';
	}

	$cnames = get_rocket_cdn_cnames( $zone );
	$cnames = array_map( 'rocket_remove_url_protocol' , $cnames );

	if ( $url_host !== $_SERVER['HTTP_HOST'] && in_array( $_SERVER['HTTP_HOST'], get_rocket_i18n_host(), true ) && ! in_array( $url_host, $cnames, true ) ) {
		$url = str_replace( $url_host, $_SERVER['HTTP_HOST'], $url );
	}

	return $url;
}
add_filter( 'rocket_css_url', 'rocket_minify_i18n_multidomain' );
add_filter( 'rocket_js_url' , 'rocket_minify_i18n_multidomain' );
