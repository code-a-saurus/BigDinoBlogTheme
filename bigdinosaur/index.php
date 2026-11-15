<?php
/**
 * Post list: home page and archives.
 *
 * @package bigdinosaur
 */

get_header();

if ( is_home() && ! is_paged() ) {
	bigdinosaur_intro();
}
bigdinosaur_archive_header();
?>
<main class="site-main" id="main">
	<?php if ( have_posts() ) : ?>
		<div class="post-list">
			<?php
			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/content', 'summary' );
			}
			bigdinosaur_posts_nav();
			?>
		</div>
	<?php else : ?>
		<p class="nothing-found"><?php esc_html_e( 'Nothing here.', 'bigdinosaur' ); ?></p>
	<?php endif; ?>
</main>
<?php
get_footer();
