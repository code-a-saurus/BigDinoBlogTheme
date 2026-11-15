<?php
/**
 * Single post.
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
			<header class="entry-header">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<p class="entry-date"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
			</header>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
			<?php bigdinosaur_page_links(); ?>
		</article>
		<?php bigdinosaur_post_nav(); ?>
		<?php
	}
	?>
</main>
<?php
get_footer();
