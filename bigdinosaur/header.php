<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="site" id="page">
	<a class="skip-link screen-reader-text" href="#main"><?php esc_html_e( 'Skip to content', 'bigdinosaur' ); ?></a>
	<header class="site-header" id="masthead">
		<div class="masthead">
			<a class="site-mascot" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
				<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/bigdino-left-cleanbg-square.png' ) ); ?>" width="100" height="100" alt="">
			</a>
			<?php // The site title is the page's h1 only on the blog front page; elsewhere the content's title is. ?>
			<?php if ( is_front_page() && is_home() ) : ?>
				<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
			<?php else : ?>
				<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
			<?php endif; ?>
		</div>
		<nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'bigdinosaur' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'container'      => false,
					'depth'          => 1,
					'fallback_cb'    => false,
					'items_wrap'     => '<ul id="%1$s" class="%2$s">%3$s</ul>',
				)
			);
			?>
		</nav>
		<?php // Label and aria-pressed are kept in sync by the toggle script in functions.php. ?>
		<button type="button" id="colormode" class="scheme-toggle" aria-pressed="false">&#9790; dark mode</button>
		<div class="site-header-rule"></div>
	</header>
