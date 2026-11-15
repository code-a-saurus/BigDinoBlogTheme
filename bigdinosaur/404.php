<?php
/**
 * 404 page. Copy and image carried over from the old "404 Not Found" Element.
 *
 * @package bigdinosaur
 */

get_header();
?>
<main class="site-main site-main--single error-404" id="main">
	<header class="entry-header">
		<p class="entry-date">404</p>
		<h1 class="entry-title"><?php esc_html_e( 'Not Found', 'bigdinosaur' ); ?></h1>
	</header>
	<div class="entry-content">
		<figure class="error-404-mascot"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/bigdino-left-cleanbg-square.png' ) ); ?>" width="280" height="280" alt=""></figure>
		<p>Sorry, friend&mdash;nothing exists at the URL you've requested.<br>Maybe the dinosaur ate what you were looking for.<br>Tell me that face doesn't look suspicious.</p>
		<p class="read-more"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><span aria-hidden="true">&larr;</span> <?php esc_html_e( 'Back to the front page', 'bigdinosaur' ); ?></a></p>
	</div>
</main>
<?php
get_footer();
