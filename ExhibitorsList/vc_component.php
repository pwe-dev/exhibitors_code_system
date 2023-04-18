<?php
/*
VC component
*/
add_action( 'vc_before_init', 'vc_exhibitors_list' );
function vc_exhibitors_list() {
  vc_map( array(
      "name" => __( "Exhibitors List", "my-text-domain" ),
      "base" => "exhibitors_list",
      "class" => "exhibitors",
	  "category" => __( "Content", "my-text-domain"),
	  "params" => array(
		array(
		  "type" => "checkbox",
		  "heading" => __( "Show Company Name", "my-text-domain" ),
		  "param_name" => "show_company",
		  "description" => __( "If You want to show Exhibitor's Name set 'Yes'", "my-text-domain" ),
		  "value" => Array(
			esc_html__("Yes, please", 'uncode') => 'yes'
		  ) ,
		),
		array(
		  "type" => "checkbox",
		  "heading" => __( "Show Stand", "my-text-domain" ),
		  "param_name" => "show_stand",
		  "value" => Array(
			esc_html__("Yes, please", 'uncode') => 'yes'
		  ) ,
		  "description" => __( "If You want to show Exhibitor's Stand set 'Yes'", "my-text-domain" )
		),
		array(
		  "type" => "checkbox",
		  "heading" => __( "Show Email", "my-text-domain" ),
		  "param_name" => "show_email",
		  "value" => Array(
			esc_html__("Yes, please", 'uncode') => 'yes'
		  ) ,
		  "description" => __( "If You want to show Exhibitor's Email set 'Yes'", "my-text-domain" ),
		),
		array(
		  "type" => "checkbox",
		  "heading" => __( "Show Website", "my-text-domain" ),
		  "param_name" => "show_website",
		  "description" => __( "If You want to show Exhibitor's website set 'Yes'", "my-text-domain" )
		),
		array(
			"type" => "checkbox",
			"heading" => __( "Show Hall", "my-text-domain" ),
			"param_name" => "show_hall",
			"description" => __( "If You want to show Exhibitor's Hall set 'Yes'", "my-text-domain" )
		  ),
		array(
			"type" => "checkbox",
			"heading" => __( "Show Phone Number", "my-text-domain" ),
			"param_name" => "show_phone",
			"description" => __( "If You want to show Exhibitor's Phone Number set 'Yes'", "my-text-domain" )
		),
		array(
			"type" => "checkbox",
			"heading" => __( "Show Excerpt", "my-text-domain" ),
			"param_name" => "show_excerpt",
			"description" => __( "If You want to show Exhibitor's Excerpt set 'Yes'", "my-text-domain" )
		),
	  )
		)); 
	}
	// Main Shortcode
	add_shortcode( 'exhibitors_list', 'exhibitors_list_shortcode' );
	function exhibitors_list_shortcode($atts) {
		extract( shortcode_atts( array(
			'show_company' => 'false',
			'show_stand' => 'false',
			'show_email' => 'false',
			'show_website' => 'false',
			'show_hall' => 'false',
			'show_phone' => 'false',
			'show_excerpt' => 'false',
		), $atts ) );
	ob_start();
	// The taxonomy maker
	function show_taxes($tax){
		return strip_tags(get_the_term_list( $post->ID, $tax));
	}
	// Query Args
	$query_args = array(
		'post_type' => 'exhibitor',
		'posts_per_page' => -1
	);
	// The Query
	$the_query = new WP_Query( $query_args );
	// The Loop
	if ( $the_query->have_posts() ) {
		// Table heders
		$show_me_company = ($show_company == 'false') ? "" : "<th>Firma</th>";
		$show_me_stand = ($show_stand == 'false') ? "" : "<th>Nr Stoiska</th>";
		$show_me_email = ($show_email == 'false') ? "" : "<th>Email</th>";
		$show_me_website = ($show_website == 'false') ? "" : "<th>Strona internetowa</th>";
		$show_me_hall = ($show_hall == 'false') ? "" : "<th>Hala</th>";
		$show_me_phone = ($show_phone == 'false') ? "" : "<th>Telefon</th>";
		$show_me_excerpt = ($show_excerpt == 'false') ? "" : "<th>Opis</th>";
		// PRINT OUTPUT
		//$wiget_output = '<input type="text" id="myInput" placeholder="Szukaj wystawców.." title="Exhibitors Search">';
		$wiget_output = '<table id="table-exhibitor" class="exhibitors-table">';
		$wiget_output .= '<thead><tr>';
		// Exhibitors Data List
		$wiget_output .=  $show_me_company . $show_me_stand . $show_me_email . $show_me_website . $show_me_hall . $show_me_phone . $show_me_excerpt;
		$wiget_output .= '</tr></thead>';
		$wiget_output .= '<tbody id="table-body">';
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			// Table data columns
			$show_me_company_details = ($show_company == 'false') ? "" : "<td><a style='color: #777777; text-decoration: underline' href='".get_the_permalink($post->ID)."'>".get_the_title()."</a></td>";
			$show_me_stand_details = ($show_stand == 'false') ? "" : '<td>'.show_taxes('stoisko').'</td>';
			$show_me_email_details = ($show_email == 'false') ? "" : '<td>'.show_taxes('email').'</td>';
			$show_me_website_details = ($show_website == 'false') ? "" : '<td>'.show_taxes('strona').'</td>';
			$show_me_hall_details = ($show_hall == 'false') ? "" : '<td>'.show_taxes('hall').'</td>';
			$show_me_phone_details = ($show_phone == 'false') ? "" : '<td>'.show_taxes('telefon').'</td>';
			$show_me_excerpt_details = ($show_excerpt == 'false') ? "" : '<td>'.get_the_excerpt().'</td>';
			// Current Exhibitors Output
			$wiget_output .= '<tr>';
			$wiget_output .=  $show_me_company_details . $show_me_stand_details . $show_me_email_details . $show_me_website_details . $show_me_hall_details . $show_me_phone_details . $show_me_excerpt_details;
			$wiget_output .= '</tr>';	
		}
		$wiget_output .= '</tbody></table>';
		/* Restore original Post Data */
		wp_reset_postdata();
	} else {
		$wiget_output .= '<h3>Brak wystawców</h3>';
	}
	// END of OUTPUT
	ob_get_clean();
	return $wiget_output;
}
?>