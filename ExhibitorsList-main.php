<?php
/*
Plugin Name: Exhibitors List
Description: Wtyczka umożliwiająca tworzenie oraz wyświetlanie listy wystawców (AutoUpdate). 
Version: 1.0.1
Author: Szymon Kaluga
Author URI: http://skaluga.pl/
*/

function exhibitors_list_styles_and_scripts() 
{
    wp_enqueue_style( 'exhibitors_list_css', plugins_url( 'ExhibitorsList/exhibitors_list.css', __FILE__ ) );
    wp_enqueue_script( 'exhibitors_list_js', plugins_url( 'ExhibitorsList/exhibitors_list.js', __FILE__ ) , array( 'jquery' ) );
}add_action('wp_enqueue_scripts', 'exhibitors_list_styles_and_scripts'); 

// Register Taxonomy
function create_exhibitors_tax() {
  // Hall labels
	$hall_labels = array(
		'name'              => _x( 'Hall', 'taxonomy general name', 'textdomain' ),
		'singular_name'     => _x( 'Hall', 'taxonomy singular name', 'textdomain' ),
		'search_items'      => __( 'Search Hall', 'textdomain' ),
		'all_items'         => __( 'All Hall', 'textdomain' ),
		'parent_item'       => __( 'Parent Hall', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Hall:', 'textdomain' ),
		'edit_item'         => __( 'Edit Hall', 'textdomain' ),
		'update_item'       => __( 'Update Hall', 'textdomain' ),
		'add_new_item'      => __( 'Add New Hall', 'textdomain' ),
		'new_item_name'     => __( 'New Hall Name', 'textdomain' ),
		'menu_name'         => __( 'Hall', 'textdomain' ),
  );
  // Hall args
	$hall_args = array(
		'labels' => $hall_labels,
		'description' => __( 'Hala na której będzie wystawca', 'textdomain' ),
		'hierarchical' => false,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud' => true,
		'show_in_quick_edit' => true,
		'show_admin_column' => true,
		'show_in_rest' => true,
  );
  // Email Labels
	$email_labels = array(
		'name'              => _x( 'Email', 'taxonomy general name', 'textdomain' ),
		'singular_name'     => _x( 'Email', 'taxonomy singular name', 'textdomain' ),
		'search_items'      => __( 'Search Email', 'textdomain' ),
		'all_items'         => __( 'All Email', 'textdomain' ),
		'parent_item'       => __( 'Parent Email', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Email:', 'textdomain' ),
		'edit_item'         => __( 'Edit Email', 'textdomain' ),
		'update_item'       => __( 'Update Email', 'textdomain' ),
		'add_new_item'      => __( 'Add New Email', 'textdomain' ),
		'new_item_name'     => __( 'New Email Name', 'textdomain' ),
		'menu_name'         => __( 'Email', 'textdomain' ),
  );
  // Email Args
	$email_args = array(
		'labels' => $email_labels,
		'description' => __( 'Email wystawcy', 'textdomain' ),
		'hierarchical' => false,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud' => true,
		'show_in_quick_edit' => true,
		'show_admin_column' => true,
		'show_in_rest' => true,
	);
  // Stoisko Labels
	$stoisko_labels = array(
		'name'              => _x( 'Stoisko wystawcy', 'taxonomy general name', 'textdomain' ),
		'singular_name'     => _x( 'Stoisko', 'taxonomy singular name', 'textdomain' ),
		'search_items'      => __( 'Search Stoisko wystawcy', 'textdomain' ),
		'all_items'         => __( 'All Stoisko wystawcy', 'textdomain' ),
		'parent_item'       => __( 'Parent Stoisko', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Stoisko:', 'textdomain' ),
		'edit_item'         => __( 'Edit Stoisko', 'textdomain' ),
		'update_item'       => __( 'Update Stoisko', 'textdomain' ),
		'add_new_item'      => __( 'Add New Stoisko', 'textdomain' ),
		'new_item_name'     => __( 'New Stoisko Name', 'textdomain' ),
		'menu_name'         => __( 'Stoisko', 'textdomain' ),
  );
  // Stoisko Args
	$stoisko_args = array(
		'labels' => $stoisko_labels,
		'description' => __( 'Stoisko wystawcy', 'textdomain' ),
		'hierarchical' => false,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud' => true,
		'show_in_quick_edit' => true,
		'show_admin_column' => true,
		'show_in_rest' => true,
  );
  // Telefon labels
	$telefon_labels = array(
		'name'              => _x( 'Numer Telefonu', 'taxonomy general name', 'textdomain' ),
		'singular_name'     => _x( 'Telefon', 'taxonomy singular name', 'textdomain' ),
		'search_items'      => __( 'Search Numer Telefonu', 'textdomain' ),
		'all_items'         => __( 'All Numer Telefonu', 'textdomain' ),
		'parent_item'       => __( 'Parent Telefon', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Telefon:', 'textdomain' ),
		'edit_item'         => __( 'Edit Telefon', 'textdomain' ),
		'update_item'       => __( 'Update Telefon', 'textdomain' ),
		'add_new_item'      => __( 'Add New Telefon', 'textdomain' ),
		'new_item_name'     => __( 'New Telefon Name', 'textdomain' ),
		'menu_name'         => __( 'Telefon', 'textdomain' ),
  );
  // Telefon Args
	$telefon_args = array(
		'labels' => $telefon_labels,
		'description' => __( 'Numer telefonu', 'textdomain' ),
		'hierarchical' => false,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud' => true,
		'show_in_quick_edit' => true,
		'show_admin_column' => true,
		'show_in_rest' => true,
	);
	// Strona Labels
	$strona_labels = array(
		'name'              => _x( 'Strona internetowa', 'taxonomy general name', 'textdomain' ),
		'singular_name'     => _x( 'Strona', 'taxonomy singular name', 'textdomain' ),
		'search_items'      => __( 'Search Strona internetowa', 'textdomain' ),
		'all_items'         => __( 'All Strona internetowa', 'textdomain' ),
		'parent_item'       => __( 'Parent Strona', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Strona:', 'textdomain' ),
		'edit_item'         => __( 'Edit Strona', 'textdomain' ),
		'update_item'       => __( 'Update Strona', 'textdomain' ),
		'add_new_item'      => __( 'Add New Strona', 'textdomain' ),
		'new_item_name'     => __( 'New Strona Name', 'textdomain' ),
		'menu_name'         => __( 'Strona', 'textdomain' ),
  );
  // Strona Args
	$strona_args = array(
		'labels' => $strona_labels,
		'description' => __( 'Strona internetowa wystawcy', 'textdomain' ),
		'hierarchical' => false,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud' => true,
		'show_in_quick_edit' => true,
		'show_admin_column' => true,
		'show_in_rest' => true,
	);
	


  // Register all taxonomies
	register_taxonomy( 'hall', array('exhibitor'), $hall_args );
  register_taxonomy( 'email', array('exhibitor'), $email_args );
  register_taxonomy( 'stoisko', array('exhibitor'), $stoisko_args ); 
  register_taxonomy( 'telefon', array('exhibitor'), $telefon_args ); 
  register_taxonomy( 'strona', array('exhibitor'), $strona_args );
}
add_action( 'init', 'create_exhibitors_tax' );

// Register Custom Post Type Exhibitor
function create_exhibitor_cpt() {

	$labels = array(
		'name' => _x( 'Exhibitors', 'Post Type General Name', 'textdomain' ),
		'singular_name' => _x( 'Exhibitor', 'Post Type Singular Name', 'textdomain' ),
		'menu_name' => _x( 'Exhibitors', 'Admin Menu text', 'textdomain' ),
		'name_admin_bar' => _x( 'Exhibitor', 'Add New on Toolbar', 'textdomain' ),
		'archives' => __( 'Exhibitor Archives', 'textdomain' ),
		'attributes' => __( 'Exhibitor Attributes', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Exhibitor:', 'textdomain' ),
		'all_items' => __( 'All Exhibitors', 'textdomain' ),
		'add_new_item' => __( 'Add New Exhibitor', 'textdomain' ),
		'add_new' => __( 'Add New', 'textdomain' ),
		'new_item' => __( 'New Exhibitor', 'textdomain' ),
		'edit_item' => __( 'Edit Exhibitor', 'textdomain' ),
		'update_item' => __( 'Update Exhibitor', 'textdomain' ),
		'view_item' => __( 'View Exhibitor', 'textdomain' ),
		'view_items' => __( 'View Exhibitors', 'textdomain' ),
		'search_items' => __( 'Search Exhibitor', 'textdomain' ),
		'not_found' => __( 'Not found', 'textdomain' ),
		'not_found_in_trash' => __( 'Not found in Trash', 'textdomain' ),
		'featured_image' => __( 'Featured Image', 'textdomain' ),
		'set_featured_image' => __( 'Set featured image', 'textdomain' ),
		'remove_featured_image' => __( 'Remove featured image', 'textdomain' ),
		'use_featured_image' => __( 'Use as featured image', 'textdomain' ),
		'insert_into_item' => __( 'Insert into Exhibitor', 'textdomain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this Exhibitor', 'textdomain' ),
		'items_list' => __( 'Exhibitors list', 'textdomain' ),
		'items_list_navigation' => __( 'Exhibitors list navigation', 'textdomain' ),
		'filter_items_list' => __( 'Filter Exhibitors list', 'textdomain' ),
	);
	$args = array(
		'label' => __( 'Exhibitor', 'textdomain' ),
		'description' => __( 'Wystawcy, którzy biorą udział w targach', 'textdomain' ),
		'labels' => $labels,
		'menu_icon' => 'dashicons-universal-access',
		'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
		'taxonomies' => array('hall', 'email', 'stoisko', 'telefon', 'strona', 'category'),
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'show_in_admin_bar' => true,
		'show_in_nav_menus' => true,
		'can_export' => true,
		'has_archive' => true,
		'hierarchical' => true,
		'exclude_from_search' => false,
		'show_in_rest' => true,
		'publicly_queryable' => true,
		'capability_type' => 'post',
	);
	register_post_type( 'exhibitor', $args );

}
add_action( 'init', 'create_exhibitor_cpt', 0 ); 

// INLCUDE CV COMPONENT
include( plugin_dir_path( __FILE__ ) . 'ExhibitorsList/vc_component.php');

function load_exhibitor_template($template) {
    global $post;

    if ($post->post_type == "exhibitor" && $template !== locate_template(array("ExhibitorsList/single-exhibitor.php"))){
        /* This is a "exhibitor" post 
         * AND a 'single exhibitor template' is not found on 
         * theme or child theme directories, so load it 
         * from our plugin directory
         */
        return plugin_dir_path( __FILE__ ) . "ExhibitorsList/single-exhibitor.php";
    }

    return $template;
}

add_filter('single_template', 'load_exhibitor_template');
?>