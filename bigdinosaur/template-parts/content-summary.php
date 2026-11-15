<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<h2 class="entry-title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
	<p class="entry-date"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></p>
	<div class="entry-summary">
		<?php // On lists the_content() stops at the More block and appends the read-more link (bigdinosaur_more_link). ?>
		<?php the_content( esc_html__( 'Continue reading', 'bigdinosaur' ) ); ?>
	</div>
</article>
