<?php

/**
 * The single template specifically designed for the shortcode.
 * This file may be overridden by copying it into your theme directory, into a folder titled wpinventory/views/single-loop-all.php
 * While inventory does not use the WP post types, it does model functions after the WP core functions
 * to provide similar functionality.
 * */

global $inventory_display;
$inventory_display = apply_filters( 'wpim_display_listing_settings', $inventory_display );

?>
<div class="<?php wpinventory_class(); ?>">
	<?php foreach ( $inventory_display AS $sort => $field ) {
		$open_tag  = apply_filters( 'wpim_listing_open_link_tag', '<a href="' . wpinventory_get_permalink() . '">', $field );
		$close_tag = apply_filters( 'wpim_listing_close_link_tag', '</a>', $field );
		?>
        <p class="<?php echo $field; ?>"><?php echo $open_tag . wpinventory_get_field( $field ) . $close_tag; ?></p>
	<?php } ?>
</div>