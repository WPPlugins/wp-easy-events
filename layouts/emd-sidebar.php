<?php
/**
 * The template for the sidebar containing the main widget area
 *
 */
?>
<?php if ( is_active_sidebar( 'sidebar-emd' ) ) : ?>
	<div class="col grid_4_of_12" id="emd-sidebar">
		<?php dynamic_sidebar( 'sidebar-emd' ); ?>
	</div><!-- #secondary -->
<?php endif; ?>
