<?php
if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
}
get_header('emdplugins'); 
?>
<div id="container">
<div id="emd-primary" class="emd-site-content emd-row" role="main">
<?php 
	$has_sidebar = apply_filters( 'emd_show_temp_sidebar', 'right', 'wp_easy_events', 'single');
	if($has_sidebar ==  'left'){
		do_action( 'emd_sidebar', 'wp_easy_events' );
	}
	if($has_sidebar == 'full'){
?>
<div class="col grid_12_of_12">
<?php
	}
	else {
?>
<div class="col grid_8_of_12">
<?php
	}
	edit_post_link( esc_html__( 'Edit', 'wpas' ) . ' <i class="fa fa-angle-right"></i>', '<div class="emd-edit-link" style="padding:20px">', '</div>' );
	while ( have_posts() ) : the_post(); ?>
		<div id="post-<?php the_ID(); ?>" style="padding:10px;" <?php post_class(); ?>>
		<?php emd_get_template_part('wp-easy-events', 'single', str_replace("_","-",$post->post_type)); ?>
		</div>
		<?php
			// If comments are open or we have at least one comment, load up the comment template
			if ( comments_open() || '0' != get_comments_number() ) {
				comments_template( '', true );
			}
		?>
<?php 	$has_navigation = apply_filters( 'emd_show_temp_navigation', true, 'wp_easy_events', 'single');
	if($has_navigation){
		global $wp_query;
		$big = 999999999; // need an unlikely integer
	?>
		<nav role="navigation" id="emd-nav-below" class="site-navigation post-navigation nav-single">
		<h3 class="assistive-text"><?php esc_html_e( 'Post navigation', 'wpas' ); ?></h3>

		<?php previous_post_link( '<div class="nav-previous">%link</div>', '<span class="meta-nav">' . _x( '<i class="fa fa-angle-left"></i>', 'Previous post link', 'wpas' ) . '</span> %title' ); ?>
		<?php next_post_link( '<div class="nav-next">%link</div>', '%title <span class="meta-nav">' . _x( '<i class="fa fa-angle-right"></i>', 'Next post link', 'wpas' ) . '</span>' ); ?>

		</nav>
<?php 	}
	endwhile; // end of the loop. ?>
</div>
<?php if($has_sidebar ==  'right'){
?>
<?php
	do_action( 'emd_sidebar', 'wp_easy_events' );
?>
<?php
}
?>
</div>
</div>
<?php get_footer('emdplugins'); ?>
