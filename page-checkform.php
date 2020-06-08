<?php
	// Include Shortcodes
	include( plugin_dir_path( __FILE__ ) . 'shortcodes.php');

	$maker_form_id = get_option('form_id');
	$vip_form_id = get_option('form_vip_id');	
	// Exhibitors Codes List
	$existExhibitors = explode(', ', get_option('code_list'));
	// Make CSV file Array great again
	$csv_array = array();
	if (($handle = fopen(get_option("csv_file"), "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 2048, ",")) !== FALSE) {
			$num = count($data);
			for ($c=0; $c < $num; $c++) {
				array_push($csv_array, $data[$c]);
			}
		}
	}    
	// VIP Codes
	$vipCodesManual = explode(', ', get_option('vip_code_list'));
	// Code Usage Limit
	$user_limit = get_option('vip_users');
	// Make CSV file Array great again 
	$vip_csv_array = array();
	if (($handle = fopen(get_option("vip_csv_file"), "r")) !== FALSE) {
		while (($data = fgetcsv($handle, 2048, ",")) !== FALSE) {
			$num = count($data);
			for ($c=0; $c < $num; $c++) {
				array_push($vip_csv_array, $data[$c]);
			}
		}
	}    
	// Connect with Database
	$passcode = strtoupper($_GET['input_value']);
	$check_code_added_automatically = "SELECT * FROM `wp_gf_entry_meta` WHERE `meta_value` = '$passcode' && `form_id` = '$maker_form_id'";
	$check_amount_of_vip = "SELECT * FROM `wp_gf_entry_meta` WHERE `meta_value` = '$passcode' && `form_id` = '$vip_form_id'";
	require_once(ABSPATH . 'wp-config.php');
	$connect = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	// If Clean $_GET request, do form shortcode
	if (empty($_GET["input_value"])){
		get_header();	
		echo do_shortcode('[form_check]');
		get_footer();
	}else{
		// If $_GET code is Visitor Normal Code
		if(mysqli_num_rows(mysqli_query($connect, $check_code_added_automatically)) > 0 || in_array($passcode, $existExhibitors) || in_array($passcode, $csv_array)){
			// FILTER CONTENT
			add_filter( 'the_content', 'prepend_user_form'); 
			// ECHO PAGE SHORTCODE 
			echo do_shortcode("[phpinclude file='page']");	
		}elseif((in_array($passcode, $vipCodesManual) || in_array($passcode, $vip_csv_array)) && mysqli_num_rows(mysqli_query($connect, $check_amount_of_vip)) < $user_limit){
			// If $_GET code is Visitor VIP Code
			// FILTER CONTENT
			add_filter( 'the_content', 'prepend_vip_form');  
			// ECHO PAGE SHORTCODE 
			echo do_shortcode("[phpinclude file='page']");	
		}elseif((in_array($passcode, $vipCodesManual) || in_array($passcode, $vip_csv_array)) && mysqli_num_rows(mysqli_query($connect, $check_amount_of_vip)) >= $user_limit){
			// If $_GET code is Visitor VIP Code and used too much times
			get_header();	
			// Show Check Form
			echo do_shortcode('[form_check]');
			// Show Error Notification
			echo do_shortcode('[error_code_multiple]');
			get_footer();
		}else{
			// If is wrong code
			get_header();	
			// Show Check Form
			echo do_shortcode('[form_check]');
			// Show Error Notification
			echo do_shortcode('[wrong_code]');
			get_footer();
		}
	}
?>