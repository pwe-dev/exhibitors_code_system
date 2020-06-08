<?php
	// Include Shortcodes
	include( plugin_dir_path( __FILE__ ) . 'shortcodes.php');
	// Exhibitors Codes List
	$ExhibitorsCodes = explode(', ', get_option('exhibit_code_list'));

	// Connect with Database
	$passcode = $_GET['input_value'];
	$check_code_query = "SELECT * FROM `wp_gf_entry_meta` WHERE `meta_value` = '$passcode'";
	require_once(ABSPATH . 'wp-config.php');
	$connect = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

	// If Clean $_GET request, do form shortcode
	if (empty($_GET["input_value"])){
		get_header();	
		// Show Check Form
		echo do_shortcode('[form_check]');
		get_footer();
	}else{
		// If $_GET code is ok
		if(mysqli_num_rows(mysqli_query($connect, $check_code_query)) > 0 || in_array($passcode, $ExhibitorsCodes) ){
			// FILTER CONTENT
			add_filter( 'the_content', 'add_additional_php_code'); 
			// ECHO PAGE SHORTCODE 
			echo do_shortcode("[phpinclude file='page']");	
		}else{
			get_header();	
			// Show Check Form
			echo do_shortcode('[form_check]');
			// Show Error Notification
			echo do_shortcode('[wrong_code]');
			get_footer();
		}
	}
?>