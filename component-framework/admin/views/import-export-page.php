<div class="wrap">

	<h2><?php _e("Import/Export Component Theme options", "component-theme"); ?></h2>

	<?php if ( isset($import_errors) && $import_errors ) : ?>
		
		<div id="message" class="error notice below-h2">
		<?php foreach ( $import_errors as $error ) : ?>
			
			<p><?php echo sanitize_text_field( $error ); ?></p>
		
		<?php endforeach; ?>
		</div>
	
	<?php endif; ?>

	<?php if ( isset($import_success) && $import_success ) : ?>
		
		<div id="message" class="updated notice below-h2">
		<?php foreach ( $import_success as $notice ) : ?>
			
			<p><?php echo sanitize_text_field( $notice ); ?></p>
		
		<?php endforeach; ?>
		</div>
	
	<?php endif; ?>
		
	<h3><?php _e("Export", "component-theme"); ?></h3>
	<p class="description"><?php _e("Copy code below to use on other install", "component-theme"); ?></p>

	<textarea cols="80" rows="10"><?php echo $export_json; ?></textarea>

	<h3><?php _e("Import", "component-theme"); ?></h3>
	<p class="description">
	<?php printf(__("Please upgrade to the \"%s\" to import previously exported settings, stylesheets, and selectors.", "component-theme"), '<a href="http://oxygenapp.com/" target="_blank">'.__('pro version of Oxygen', "component_theme").'</a>') ?>
	</p>
	
</div>