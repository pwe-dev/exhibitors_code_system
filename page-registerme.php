<?php
	// Include Shortcodes
	include( plugin_dir_path( __FILE__ ) . 'shortcodes.php');
	
	// FILTER CONTENT
	add_filter( 'the_content', 'add_additional_js_content'); 
	
	// Include page.php file
	echo do_shortcode("[phpinclude file='page']");
    ?>