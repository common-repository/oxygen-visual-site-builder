<div class="wrap">

	<h2><?php _e("SVG Sets", "component-theme"); ?></h2>
	<?php if ( ! empty( $svg_sets ) ) : ?>
		
	<h3><?php _e("Uploaded Sets", "component-theme"); ?></h3>
		<form action="" method="post">
		<?php foreach ( $svg_sets as $name => $set ) : ?>
			
			<?php echo sanitize_text_field( $name ); ?><br/>

		<?php endforeach; ?>
		</form>

	<?php endif; ?>

	<h3><?php _e("Add New Set", "component-theme"); ?></h3>
	<p class="description">
	<?php printf(__("Please upgrade to the %s to import your own SVG icon sets.", "component-theme"), '<a href="https://oxygenapp.com/" target="_blank">'.__('pro version of Oxygen', "component_theme").'</a>') ?>
	</p>
</div>
