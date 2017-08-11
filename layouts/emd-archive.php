<?php
if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
}

get_header('emdplugins');  ?>
<div id="container">
<div id="emd-primary" class="emd-site-content emd-row" role="main">
<?php 
	$has_sidebar = apply_filters( 'emd_show_temp_sidebar', 'right', 'wp_easy_events', 'archive');
	if($has_sidebar ==  'left'){
		do_action( 'emd_sidebar', 'wp-easy-events' );
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
	while ( have_posts() ) : the_post(); ?>
			<div id="post-<?php the_ID(); ?>" style="padding:10px;" <?php post_class(); ?>>
			<?php emd_get_template_part('wp-easy-events', 'archive', str_replace("_","-",$post->post_type)); ?>
			</div>
                <?php endwhile; // end of the loop. ?>
<?php	$has_navigation = apply_filters( 'emd_show_temp_navigation', true, 'wp_easy_events', 'archive');
	if($has_navigation){
		global $wp_query;
		$big = 999999999; // need an unlikely integer

	?>
		<nav role="navigation" id="nav-below" class="site-navigation paging-navigation">
		<h3 class="assistive-text"><?php esc_html_e( 'Post navigation', 'wpas' ); ?></h3>

	<?php	if ( $wp_query->max_num_pages > 1 ) { ?>

		<?php echo paginate_links( array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => '?paged=%#%',
			'current' => max( 1, get_query_var( 'paged' ) ),
			'total' => $wp_query->max_num_pages,
			'type' => 'list',
			'prev_text' => wp_kses( __( '<i class="fa fa-angle-left"></i> Previous', 'wpas' ), array( 'i' => array( 
			'class' => array() ) ) ),
			'next_text' => wp_kses( __( 'Next <i class="fa fa-angle-right"></i>', 'wpas' ), array( 'i' => array( 
			'class' => array() ) ) )
		) ); ?>

	<?php } ?>

		</nav>
<?php 	}
?>
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
