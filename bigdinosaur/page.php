<?php
/**
 * Static page.
 *
 * @package bigdinosaur
 */

get_header();
?>
<main class="site-main site-main--single" id="main">
	<?php
	while ( have_posts() ) {
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php // Page titles are not shown (as on the old site); kept for assistive tech. ?>
			<h1 class="screen-reader-text"><?php the_title(); ?></h1>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
			<?php bigdinosaur_page_links(); ?>
		</article>
		<?php
	}
	?>
</main>
<?php
get_footer();
