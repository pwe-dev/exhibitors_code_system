<?php
/*
Plugin Name: Exhibitors Code System 
Description: Wtyczka umożliwiająca generowanie kodów zaproszeniowych dla wystawców oraz tworzenie 'reflinków'.
Version: 7.1.0
Author: pwe-dev (s)
Author URI: https://github.com/pwe-dev
*/

include( plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php');
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/pwe-dev/exhibitors_code_system',
	__FILE__,
	'exhibitors_code_system'
);
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

$functions_file = WP_PLUGIN_DIR . '/PWElements/pwefunctions.php';

if (file_exists($functions_file)) {
    require_once $functions_file;
} else {
    error_log('[Exhibitors Code System] Nie znaleziono pliku PWElements/pwefunctions.php');

    if (is_admin()) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>Exhibitors Code System:</strong> Nie znaleziono pliku <code>PWElements/pwefunctions.php</code>.</p></div>';
        });
    }
    return;
}

class PageTemplater {

	/**
	 * A reference to an instance of this class.
	 */
	private static $instance;

	/**
	 * The array of templates that this plugin tracks.
	 */
	protected $templates;

	/**
	 * Returns an instance of this class.
	 */
	public static function get_instance() {

		if ( null == self::$instance ) {
			self::$instance = new PageTemplater();
		}

		return self::$instance;

	}

	/**
	 * Initializes the plugin by setting filters and administration functions.
	 */
	private function __construct() {

		$this->templates = array();


		// Add a filter to the attributes metabox to inject template into the cache.
		if ( version_compare( floatval( get_bloginfo( 'version' ) ), '4.7', '<' ) ) {

			// 4.6 and older
			add_filter(
				'page_attributes_dropdown_pages_args',
				array( $this, 'register_project_templates' )
			);

		} else {

			// Add a filter to the wp 4.7 version attributes metabox
			add_filter(
				'theme_page_templates', array( $this, 'add_new_template' )
			);

		}

		// Add a filter to the save post to inject out template into the page cache
		add_filter(
			'wp_insert_post_data',
			array( $this, 'register_project_templates' )
		);


		// Add a filter to the template include to determine if the page has our
		// template assigned and return it's path
		add_filter(
			'template_include',
			array( $this, 'view_project_template')
		);


		// Add your templates to this array.
		$this->templates = array(
			'page-checkform.php' => 'Visitor Code Checker',
			'page-registerme.php' => 'Exhibitors Code Maker',
			'page-checkformexhibitor.php' => 'Check Exhibitor Code'
		);

	}

	/**
	 * Adds our template to the page dropdown for v4.7+
	 *
	 */
	public function add_new_template( $posts_templates ) {
		$posts_templates = array_merge( $posts_templates, $this->templates );
		return $posts_templates;
	}

	/**
	 * Adds our template to the pages cache in order to trick WordPress
	 * into thinking the template file exists where it doens't really exist.
	 */
	public function register_project_templates( $atts ) {

		// Create the key used for the themes cache
		$cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

		// Retrieve the cache list.
		// If it doesn't exist, or it's empty prepare an array
		$templates = wp_get_theme()->get_page_templates();
		if ( empty( $templates ) ) {
			$templates = array();
		}

		// New cache, therefore remove the old one
		wp_cache_delete( $cache_key , 'themes');

		// Now add our template to the list of templates by merging our templates
		// with the existing templates array from the cache.
		$templates = array_merge( $templates, $this->templates );

		// Add the modified cache to allow WordPress to pick it up for listing
		// available templates
		wp_cache_add( $cache_key, $templates, 'themes', 1800 );

		return $atts;

	}

	/**
	 * Checks if the template is assigned to the page
	 */
	public function view_project_template( $template ) {
		// Return the search template if we're searching (instead of the template for the first result)
		if ( is_search() ) {
			return $template;
		}

		// Get global post
		global $post;

		// Return template if post is empty
		if ( ! $post ) {
			return $template;
		}

		// Return default template if we don't have a custom one defined
		if ( ! isset( $this->templates[get_post_meta(
			$post->ID, '_wp_page_template', true
		)] ) ) {
			return $template;
		}

		// Allows filtering of file path
		$filepath = apply_filters( 'page_templater_plugin_dir_path', plugin_dir_path( __FILE__ ) );

		$file =  $filepath . get_post_meta(
			$post->ID, '_wp_page_template', true
		);

		// Just to be safe, we check if the file exist first
		if ( file_exists( $file ) ) {
			return $file;
		} else {
			echo $file;
		}

		// Return template
		return $template;

	}

}
add_action( 'plugins_loaded', array( 'PageTemplater', 'get_instance' ) );

function connectToDatabase($fair_name) {
    // $databases = [
    //     [
    //         'host' => 'localhost',
    //         'name' => 'warsawexpo_dodatkowa',
    //         'user' => 'warsawexpo_admin-dodatkowy',
    //         'password' => 'N4c-TsI+I4-C56@q'
    //     ],
    //     [
    //         'host' => 'localhost',
    //         'name' => 'automechanicawar_dodatkowa',
    //         'user' => 'automechanicawar_admin-dodatkowa',
    //         'password' => '9tL-2-88UAnO_x2e'
    //     ]
    // ];
    // if ($_SERVER['SERVER_ADDR'] != '94.152.207.180') {
    //     $custom_db = new wpdb($databases[0]['user'], $databases[0]['password'], $databases[0]['name'], $databases[0]['host']);
        
    //     if (empty($custom_db->error)) {
    //         $prepared_query = $custom_db->prepare("SELECT fair_kw FROM fairs WHERE fair_name = %s", $fair_name);
    //         $results = $custom_db->get_results($prepared_query);
            
    //         if (!empty($results)) {
    //             return $results[0]->fair_kw;
    //         } else {
    //             echo '<script>console.log("No results found for the given fair name.")</script>';
    //             return null;
    //         }
    //     } else {
    //         echo '<script>console.log("'.$custom_db->error.'")</script>';
    //     }
    // } else {
	// 	$custom_db = new wpdb($databases[1]['user'], $databases[1]['password'], $databases[1]['name'], $databases[1]['host']);
	// 	// echo '<pre style="width:500px;">';
    //     // var_dump($custom_db);
	// 	// echo '<pre>';
    //     if (empty($custom_db->error)) {
    //         $prepared_query = $custom_db->prepare("SELECT fair_kw FROM fairs WHERE fair_name = %s", $fair_name);
    //         $results = $custom_db->get_results($prepared_query);

    //         if (!empty($results)) {
    //             return $results[0]->fair_kw;
    //         } else {
    //             echo '<script>console.log("No results found for the given fair name.")</script>';
    //             return null;
    //         }
    //     } else {
    //         //var_dump($custom_db->error);
    //     }
	// }
    
    // echo '<script>console.log("Failed to connect to all databases.")</script>';
    return null;
}


    function add_new_menu_items()
    {
        add_menu_page(
            "Exhibitors Code System Settings",
            "Exhibitors Code System Settings",
            "manage_options",
            "code-maker",
            "theme_options_page",
            plugins_url('icon_small.png', __FILE__) ,
            100
        );

    }

    function theme_options_page()
    {
        ?>
            <div class="wrap" style="margin-top:40px">
            <div id="icon-options-general" class="icon32"></div>
           
            <!-- run the settings_errors() function here. -->
            <?php settings_errors(); ?>
				<div id="col-left" class="postbox-container">
					<div class="col-wrap">
						<div class="postbox">
							<div class="inside">
								<div class="main">
									<p><center><strong>O Exhibitors Code System:</strong></center><hr>
									Wtyczka umożliwa <strong>automatyczne generowanie kodów zaproszeń</strong> dla wystawców, który później <strong>zostaje wryfikowany przy rejestracji</strong> osoby zaproszonej. Dla uprzednio zarejestrowanych wystawców jest możliwośc dodania własnej <strong><em>(nieograniczonej znakowo)</em> puli kodów</strong>, która również zostaje weryfikowana podczas rejestracji osoby zaproszonej.</p>
								</div>
							</div>
						</div>
						<div class="postbox">
							<div class="inside">
								<div class="main">
									<p><center><strong>Instrukcja:</strong></center><hr></p>
									<ol>
										<li>Dodanie do <strong>formularza wystawcy</strong> pola <code>type="text"</code> o klasie <code>code</code>,</li>
										<li>Załączenie tego pola oraz linka do odpowiedniej podstrony w mailu potwierdzającym <strong>dla wystawcy</strong>,</li>
										<li>Dodanie do <strong>formularza rejestracji dla użytkowników</strong> pola <code>type="text"</code> o klasie <code>invitation_code</code>,</li>
										<li>Załączenie tego pola w mailu potwierdzającym <strong>dla nas</strong>,</li>
										<li>Ustawienie <strong>szablonu podstrony z rejestracją wystawców</strong> na <code>Exhibitors Code Maker</code></li>
										<li>Ustawienie <strong>szablonu podstrony z rejestracją odwiedzających</strong> na <code>Visitor Code Checker</code></li>
										<li>Uzupełnienie ustawień wtyczki odpowiednimi danymi.</li>
									</ol>
									<p><center><strong>UWAGA!</strong></center><hr></p>
									<p>Teraz wystarczy ustawić <strong>tylko</strong> szablon strony dla VIP-ów i zwykłej rejestracji z kodem na <code>Visitor Code Checker</code>. <strong>Nie należy już dodawać na stronie odpowiedniego formularza</strong> - zostaje on wybrany automatycznie, w zależności czy kod jest dla VIPa czy zwykłego odwiedzającego posiadającego kod od wystawcy lub od nas.</p>
									<p><center><strong>Zasady tworzenia formularza VIP są identyczne jak dla odwiedzającego z kodem!</strong></center></p>
									<p>Licznik wszystkich rejestracji dodajemy za pomocą shortcoda <strong><code>[visitors_counter]</code></strong> - można go dodać wszędzie gdzie jest <strong>edytorze tekstowym</strong>.</p>
									<p>Nazwę targów dodajemy za pomocą shortcoda <strong><code>[trade_fair_name]</code></strong></p>
									<p>Opis targów dodajemy za pomocą shortcoda <strong><code>[trade_fair_desc]</code></strong></p>
									<p>Datę targów dodajemy za pomocą shortcoda <strong><code>[trade_fair_date]</code></strong></p>
									<p>Datę targów (ENG) dodajemy za pomocą shortcoda <strong><code>[trade_fair_date_eng]</code></strong></p>
									<p>Datę targów (RU) dodajemy za pomocą shortcoda <strong><code>[trade_fair_date_ru]</code></strong></p>
								</div>
							</div>
						</div>
						<div class="postbox">
							<div class="inside">
								<div class="main">
								<p><center><strong>Pomoc:</strong></center><hr></p>
									<center>Potrzebujesz pomocy, nowej funkcjonalności, zauważyłeś błąd? Napisz:<br>
									<strong>Autor wtyczki:</strong> Szymon Kaluga<br>
									<em>s.kaluga@warsawexpo.eu</em></center>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div id="col-right" class="postbox-container exhibitors-code-system">
					<div class="col-wrap">
						<div class="form-wrap">
							<form method="POST" action="options.php" enctype="multipart/form-data">
								<div class="postbox">
									<div class="inside">
										<div class="main">
											<?php
											settings_fields("code_checker");
											do_settings_sections("code-checker");
											submit_button();
											?>    
										</div>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
        	</div>
        <?php
    }

    add_action("admin_menu", "add_new_menu_items");

    function display_options()
    {	
		add_settings_section("code_checker", "Code System Checker", "display_header_options_content", "code-checker");
		
		add_settings_field("trade_fair_name", "Nazwa Targów PL<hr><p class='half-tab-code-system'>Wpisz nazwę targów PL<br>[trade_fair_name]</p>", "display_trade_fair_name", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_name");
		
		add_settings_field("trade_fair_name_eng", "Nazwa Targów EN<hr><p class='half-tab-code-system'>Wpisz nazwę targów EN<br>[trade_fair_name_eng]</p>", "display_trade_fair_name_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_name_eng");
		
		
		add_settings_field("trade_fair_catalog", "Numer aktualnych targów do katalogu wystawców<hr><p class='half-tab-code-system' >Wpisz numer targów expo-planu <br>[trade_fair_catalog]</p>", "display_trade_fair_catalog", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_catalog");

		add_settings_field("trade_fair_catalog_id", "ID katalogu wystawców (NEW)<hr><p class='half-tab-code-system' >Shortcode <br>[trade_fair_catalog_id]</p>", "display_trade_fair_catalog_id", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_catalog_id");

        add_settings_field("trade_fair_catalog_year", "Data do aktualnego katalogu wystawców<hr><p class='half-tab-code-system' >Wpisz rok który będzie się wyświetlał w nagłówkach <br>[trade_fair_catalog_year]</p>", "display_trade_fair_catalog_year", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_catalog_year");
		/*END */

		add_settings_field("trade_fair_name_ru", "Nazwa Targów<hr><p class='dont-show-code-system'>Wpisz nazwę targów RU<br>[trade_fair_name_ru]</p>", "display_trade_fair_name_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_name_ru");

		add_settings_field("trade_fair_desc", "Opis targów PL<hr><p class='full-tab-code-system'>Wpisz opis targów PL<br>[trade_fair_desc]</p>", "display_trade_fair_desc", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_desc");

		add_settings_field("trade_fair_desc_eng", "Opis targów EN<hr><p class='full-tab-code-system'>Wpisz opis targów EN<br>[trade_fair_desc_eng]</p>", "display_trade_fair_desc_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_desc_eng");

		
		add_settings_field("trade_fair_desc_short", "Skrócony Opis targów PL<hr><p class='half-tab-code-system'>Wpisz skrócony opis targów PL<br>[trade_fair_desc_short]</p>", "display_trade_fair_desc_short", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_desc_short");

		add_settings_field("trade_fair_desc_short_eng", "Skrócony Opis targów EN<hr><p class='half-tab-code-system'>Wpisz skrócony opis targów EN<br>[trade_fair_desc_short_eng]</p>", "display_trade_fair_desc_short_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_desc_short_eng");
		/*END*/

		add_settings_field("trade_fair_desc_ru", "Opis targów<hr><p class='dont-show-code-system'>Wpisz opis targów RU<br>[trade_fair_desc_ru]</p>", "display_trade_fair_desc_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_desc_ru");

		add_settings_field("trade_fair_datetotimer", "Data targów do licznika<hr><p class='half-tab-code-system'>Wpisz date targow do licznika<br>[trade_fair_datetotimer]</p>", "display_trade_fair_datetotimer", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_datetotimer");
		
		
		add_settings_field("trade_fair_enddata", "Data zakończenia targów do licznika<hr><p class='half-tab-code-system'>Wpisz date zakończenia targow do licznika<br>[trade_fair_enddata]</p>", "display_trade_fair_enddata", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_enddata");
		/*END*/

		add_settings_field("trade_fair_date_custom_format", "Data targów [D-D|M|Y]<hr><p class='full-tab-code-system'>Wpisz date targow<br>[trade_fair_date_custom_format]</p>", "display_trade_fair_date_custom_format", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_date_custom_format");

		add_settings_field("trade_fair_date", "Data Targów PL<hr><p class='half-tab-code-system'>Wpisz datę targów <br>[trade_fair_date]</p>", "display_trade_fair_date", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_date");

		add_settings_field("trade_fair_date_eng", "Data Targów EN<hr><p class='half-tab-code-system'>Wpisz datę targów (ENG)<br>[trade_fair_date_eng]</p>", "display_trade_fair_date_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_date_eng");


		add_settings_field("trade_fair_edition", "Numer Edycji targów<hr><p class='full-tab-code-system'>Wpisz aktualny numer edycji<br>[trade_fair_edition]</p>", "display_trade_fair_edition", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_edition");

		add_settings_field("trade_fair_accent", "Kolor akcentu strony<hr><p class='half-tab-code-system'>Wpisz color akcentu -> (#hex) <br>[trade_fair_accent]</p>", "display_trade_fair_accent", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_accent");

		add_settings_field("trade_fair_main2", "Kolor Main2 <hr><p class='half-tab-code-system'>Wpisz color main2 -> (#hex) <br>[trade_fair_main2]</p>", "display_trade_fair_main2", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_main2");
		/*END*/

		add_settings_field("trade_fair_date_ru", "Data Targów (RU)<hr><p class='dont-show-code-system'>Wpisz datę targów (RU)<br>[trade_fair_date_ru]</p>", "display_trade_fair_date_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_date_ru");

		
		add_settings_field("trade_fair_conferance", "Główna nazwa konferencji <hr><p class='full-tab-code-system'>Wpisz główną nazwę konferencji<br>[trade_fair_conferance]</p>", "display_trade_fair_conferance", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_conferance");

		add_settings_field("trade_fair_conferance_eng", "Główna nazwa konferencji (ENG) <hr><p class='full-tab-code-system'>Wpisz główną nazwę konferencji<br>[trade_fair_conferance_eng]</p>", "display_trade_fair_conferance_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_conferance_eng");

		add_settings_field("trade_fair_1stbuildday", "Data pierwszego dnia zabudowy<hr><p class='half-tab-code-system'>Wpisz date pierwszego dnia zabudowy<br>[trade_fair_1stbuildday]</p>", "display_trade_fair_1stbuildday", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_1stbuildday");

		add_settings_field("trade_fair_2ndbuildday", "Data drugiego dnia zabudowy<hr><p class='half-tab-code-system'>Wpisz date drugiego dnia zabudowy<br>[trade_fair_2ndbuildday]</p>", "display_trade_fair_2ndbuildday", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_2ndbuildday");

		add_settings_field("trade_fair_1stdismantlday", "Data pierwszego dnia rozbiórki<hr><p class='half-tab-code-system'>Wpisz date pierwszego dnia rozbiórki zabudowy<br>[trade_fair_1stdismantlday]</p>", "display_trade_fair_1stdismantlday", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_1stdismantlday");

        add_settings_field("trade_fair_2nddismantlday", "Data drugiego dnia rozbiórki<hr><p class='half-tab-code-system'>Wpisz date drugiego dnia rozbiórki zabudowy<br>[trade_fair_2nddismantlday]</p>", "display_trade_fair_2nddismantlday", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_2nddismantlday");

		add_settings_field("trade_fair_branzowy", "Data dni branżowych targów<hr><p class='half-tab-code-system'>Wpisz date dni branżowych<br>[trade_fair_branzowy]</p>", "display_trade_fair_branzowy", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_branzowy");

        add_settings_field("trade_fair_branzowy_eng", "Data dni branżowych targów (ENG)<hr><p class='half-tab-code-system'>Wpisz date dni branżowych (ENG)<br>[trade_fair_branzowy_eng]</p>", "display_trade_fair_branzowy_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_branzowy_eng");

		add_settings_field("trade_fair_badge", "Początek nazwy badge -> ..._gosc_a6 <hr><p class='half-tab-code-system'>[trade_fair_badge]</p>", "display_trade_fair_badge", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_badge");

		add_settings_field("trade_fair_opisbranzy", "Krótki opis branży <hr><p class='full-tab-code-system'>[trade_fair_opisbranzy]</p>", "display_trade_fair_opisbranzy", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_opisbranzy");

		add_settings_field("trade_fair_opisbranzy_eng", "Krótki opis branży ENG <hr><p class='full-tab-code-system'>[trade_fair_opisbranzy_eng]</p>", "display_trade_fair_opisbranzy_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_opisbranzy_eng");

		add_settings_field("trade_fair_facebook", "Adres wydarzenia na facebook <hr><p class='half-tab-code-system'>[trade_fair_facebook]</p>", "display_trade_fair_facebook", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_facebook");

		add_settings_field("trade_fair_instagram", "Adres wydarzenia na instagram <hr><p class='half-tab-code-system'>[trade_fair_instagram]</p>", "display_trade_fair_instagram", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_instagram");

		/*Dodane przez Piotrek*/
		add_settings_field("trade_fair_linkedin", "Adres wydarzenia na linkedin <hr><p class='half-tab-code-system'>[trade_fair_linkedin]</p>", "display_trade_fair_linkedin", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_linkedin");

		add_settings_field("trade_fair_youtube", "Adres wydarzenia na youtube <hr><p class='half-tab-code-system'>[trade_fair_youtube]</p>", "display_trade_fair_youtube", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_youtube");
		/*END */

		add_settings_field("first_day", "Pierwszy dzień targów<hr><p>Wpisz pierwszy dzień targów<hr><p class='half-tab-code-system'>[first_day]</p>", "display_first_day", "code-checker", "code_checker");      
		register_setting("code_checker", "first_day");

		add_settings_field("second_day", "Drugi dzień targów<hr><p>Wpisz drugi dzień targów<hr><p class='half-tab-code-system'>[second_day]</p>", "display_second_day", "code-checker", "code_checker");      
		register_setting("code_checker", "second_day");

		add_settings_field("third_day", "Trzeci dzień targów<hr><p>Wpisz trzeci dzień targów<hr><p class='half-tab-code-system'>[third_day]</p>", "display_third_day", "code-checker", "code_checker");      
		register_setting("code_checker", "third_day");

		add_settings_field("first_day_eng", "Pierwszy dzień targów (ENG)<hr><p>Wpisz pierwszy dzień targów<hr><p class='half-tab-code-system'>[first_day_eng]</p>", "display_first_day_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "first_day_eng");

		add_settings_field("second_day_eng", "Drugi dzień targów (ENG)<hr><p>Wpisz pierwszy dzień targów<hr><p class='half-tab-code-system'>[second_day_eng]</p>", "display_second_day_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "second_day_eng");

		add_settings_field("third_day_eng", "Trzeci dzień targów (ENG)<hr><p>Wpisz pierwszy dzień targów<hr><p class='half-tab-code-system'>[third_day_eng]</p>", "display_third_day_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "third_day_eng");
		
		add_settings_field("first_day_ru", "Pierwszy dzień targów (RU)<hr><p>Wpisz pierwszy dzień targów<hr><p class='dont-show-code-system'>[first_day_ru]</p>", "display_first_day_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "first_day_ru");

		add_settings_field("second_day_ru", "Drugi dzień targów (RU)<hr><p>Wpisz pierwszy dzień targów<hr><p class='dont-show-code-system'>[second_day_ru]</p>", "display_second_day_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "second_day_ru");

		add_settings_field("third_day_ru", "Trzeci dzień targów (RU)<hr><p>Wpisz pierwszy dzień targów<hr><p class='dont-show-code-system'>[third_day_ru]</p>", "display_third_day_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "third_day_ru");

		add_settings_field("super_shortcode_1", "Shortcode dodatkowy 1<hr><p class='full-tab-code-system'>Dodatkowy shortcode na cokolwiek 1<br>[super_shortcode_1]</p>", "display_super_shortcode_1", "code-checker", "code_checker");      
		register_setting("code_checker", "super_shortcode_1");

		add_settings_field("super_shortcode_2", "Shortcode dodatkowy 2<hr><p class='full-tab-code-system'>Dodatkowy shortcode na cokolwiek 2<br>[super_shortcode_2]</p>", "display_super_shortcode_2", "code-checker", "code_checker");      
		register_setting("code_checker", "super_shortcode_2");

		add_settings_field("code_prefix", "Code Prefix", "display_code_prefix", "code-checker", "code_checker");
		register_setting("code_checker", "code_prefix");
		
		add_settings_field("form_id", "Form Exhibitor ID", "display_form_id", "code-checker", "code_checker");
		register_setting("code_checker", "form_id");

		add_settings_field("form_vip_id", "Form VIP ID", "display_form_vip_id", "code-checker", "code_checker");
		register_setting("code_checker", "form_vip_id");

		add_settings_field("form_user_id", "Form Visitor ID", "display_form_user_id", "code-checker", "code_checker");
		register_setting("code_checker", "form_user_id");

		add_settings_field("code_list", "List of Visitor Codes<hr><p>Lista kodów dla <strong>odwiedzających</strong></p>", "display_code_list", "code-checker", "code_checker");      
		register_setting("code_checker", "code_list");

		add_settings_field("exhibit_code_list", "List of Codes for Exhibitors <hr><p>Lista kodów dla <strong>wystawców</strong></p>", "display_exhibit_code_list", "code-checker", "code_checker");      
		register_setting("code_checker", "exhibit_code_list");
		
		add_settings_field("csv_file", "Exhibitors code list in .csv<hr><p>Plik .csv w którym znajdują się kody wystawców.</p>", "display_csv_file_upload", "code-checker", "code_checker");
		register_setting("code_checker", "csv_file", "csv_file_upload");

		add_settings_field("vip_code_list", "List of VIP Codes<hr><p>Lista kodów VIP</p>", "display_vip_code_list", "code-checker", "code_checker");      
		register_setting("code_checker", "vip_code_list");

		add_settings_field("vip_csv_file", "VIP code list in .csv<hr><p>Plik .csv w którym znajdują się kody wystawców.</p>", "display_vip_csv_file_upload", "code-checker", "code_checker");
		register_setting("code_checker", "vip_csv_file", "vip_csv_file_upload");

		add_settings_field("vip_users", "Max VIP users<hr><p>Ilość maksymalnych wejść na <b>jeden</b> kod VIP</p>", "display_vip_users", "code-checker", "code_checker");
		register_setting("code_checker", "vip_users");
		
		add_settings_field("h1_heading", "Form Heading<hr><p>Napis, który pojawia się na samej górze nad polem do wpisania kodu.</p>", "display_h1_heading", "code-checker", "code_checker");      
		register_setting("code_checker", "h1_heading");

		add_settings_field("h3_heading", "Form Subheading<hr><p>Napis, który pojawia się tuż nad polem do wpisania kodu.</p>", "display_h3_heading", "code-checker", "code_checker");      
		register_setting("code_checker", "h3_heading");

		add_settings_field("h2_heading", "Error Text<hr><p>Napis błędu, który pojawia się po złym wpisaniu kodu.</p>", "display_h2_heading", "code-checker", "code_checker");      
		register_setting("code_checker", "h2_heading");

		add_settings_field("button_text", "Button save text<hr><p>Napis, który pojawia się na przycisku.</p>", "display_button_text", "code-checker", "code_checker");      
		register_setting("code_checker", "button_text");  

		add_settings_field("list_of_forms", "Lista formularzy<hr><p>Wpisz ID formularzy z jakich ma być liczona suma rejestracji.</p>", "display_list_of_forms", "code-checker", "code_checker");      
		register_setting("code_checker", "list_of_forms");

		add_settings_field("users_no_in_forms", "Liczba rejestracji poza formularzem<hr><p>Wpisz ile rejestracji jest poza osobami, które wypełniły formularz.</p>", "display_users_no_in_forms", "code-checker", "code_checker");      
		register_setting("code_checker", "users_no_in_forms");

		add_settings_field("users_multiplier", "Mnożnik<hr><p>Wpisz liczbę przez którą przemnożysz wszystkich zareejstrowanych użytkowników</p>", "display_users_multiplier", "code-checker", "code_checker");      
		register_setting("code_checker", "users_multiplier");
		
		
		add_settings_field("trade_fair_domainadress", "Adres strony<hr><p>Nie zmieniać<br>[trade_fair_domainadress]</p>", "display_trade_fair_domainadress", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_domainadress");

		add_settings_field("trade_fair_actualyear", "Aktualny rok<hr><p>Nie zminiać<br>[trade_fair_actualyear]</p>", "display_trade_fair_actualyear", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_actualyear");
		
		add_settings_field("trade_fair_group", "Grupa targów<hr><p>[trade_fair_group]</p>", "display_trade_fair_group", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_group");

		add_settings_field("trade_fair_rejestracja", "Adres email do automatycznej odpowiedzi<hr><p>[trade_fair_rejestracja]</p>", "display_trade_fair_rejestracja", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_rejestracja");

		add_settings_field("trade_fair_contact", "Adres email do formularza kontaktu<hr><p>[trade_fair_contact]</p>", "display_trade_fair_contact", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_contact");

		add_settings_field("trade_fair_contact_tech", "Adres email do formularza kontaktu działu technicznego<hr><p>[trade_fair_contact_tech]</p>", "display_trade_fair_contact_tech", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_contact_tech");

		add_settings_field("trade_fair_contact_media", "Adres email do formularza kontaktu działu marketingowego i media<hr><p>[trade_fair_contact_media]</p>", "display_trade_fair_contact_media", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_contact_media");

		add_settings_field("trade_fair_lidy", "Adres email do wysyłania lidów<hr><p>[trade_fair_lidy]</p>", "display_trade_fair_lidy", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_lidy");



		add_settings_field("trade_fair_registration_benefits_pl", "Benefity rejestracyjne PL<hr><p>[trade_fair_registration_benefits_pl]</p>", "display_trade_fair_registration_benefits_pl", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_registration_benefits_pl");

		add_settings_field("trade_fair_registration_benefits_en", "Benefity rejestracyjne EN<hr><p>[trade_fair_registration_benefits_en]</p>", "display_trade_fair_registration_benefits_en", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_registration_benefits_en");

		add_settings_field("trade_fair_ticket_benefits_pl", "Benefity biletowe PL<hr><p>[trade_fair_ticket_benefits_pl]</p>", "display_trade_fair_ticket_benefits_pl", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_ticket_benefits_pl");

		add_settings_field("trade_fair_ticket_benefits_en", "Benefity biletowe EN<hr><p>[trade_fair_ticket_benefits_en]</p>", "display_trade_fair_ticket_benefits_en", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_ticket_benefits_en");

		register_setting("code_checker", "trade_fair_gf_coder");
		/*END */
    }

    function csv_file_upload($options)
    {
        if(!empty($_FILES["csv_file"]["tmp_name"]))
        {
            $urls = wp_handle_upload($_FILES["csv_file"], array('test_form' => FALSE));
            $temp = $urls["url"];
            return $temp;  
        }

        return get_option("csv_file");
    }

	function vip_csv_file_upload($options)
    {
        if(!empty($_FILES["vip_csv_file"]["tmp_name"]))
        {
            $urls = wp_handle_upload($_FILES["vip_csv_file"], array('test_form' => FALSE));
            $temp = $urls["url"];
            return $temp;  
        }

        return get_option("vip_csv_file");
    }

    function display_header_options_content(){echo "";}
    function display_csv_file_upload()
    {
        ?>
			<div class="form-field">
				<input type="file" name="csv_file" id="csv_file"  value="<?php echo get_option('csv_file'); ?>" />
				<p>Aktualny plik csv: <code><?php echo get_option("csv_file"); ?></code></p>
			</div>
        <?php
	}
	function display_vip_csv_file_upload()
    {
        ?>
			<div class="form-field">
				<input type="file" name="vip_csv_file" id="vip_csv_file"  value="<?php echo get_option('vip_csv_file'); ?>" />
				<p>Aktualny plik csv: <code><?php echo get_option("vip_csv_file"); ?></code></p>
			</div>
        <?php
    }
    function display_code_prefix()
    {
        ?>
			<div class="form-field">
				<input type="text" name="code_prefix" id="code_prefix" value="<?php echo get_option('code_prefix'); ?>" />
				<p>Prefix do generowania <strong>nowych kodów</strong> dla wystawców.<br>Działanie: <code>PREFIX__</code> gdzie <code>'__'</code> numer wystawcy z kolei liczony od zera.</p>
			</div>
            
        <?php
	}
	function display_vip_users()
    {
        ?>
			<div class="form-field">
				<input type="number" name="vip_users" id="vip_users" value="<?php echo get_option('vip_users'); ?>" />
				<p>Maksymalna liczba użyć jednego kodu VIP</p>
			</div>
            
        <?php
	}
	function display_form_id()
    {
        ?>	
			<div class="form-field">
				<input type="text" name="form_id" id="form_id" value="<?php echo get_option('form_id'); ?>" />
				<p>ID formularza Gravity Forms który generuje kod wystawcy.</p>
			</div>
        <?php
	}
	function display_form_user_id()
    {
        ?>	
			<div class="form-field">
				<input type="text" name="form_user_id" id="form_user_id" value="<?php echo get_option('form_user_id'); ?>" />
				<p>ID formularza Gravity Forms który rejestruje odwiedzającego po wpisaniu kodu.</p>
			</div>
        <?php
	}
	function display_form_vip_id()
    {
        ?>	
			<div class="form-field">
				<input type="text" name="form_vip_id" id="form_vip_id" value="<?php echo get_option('form_vip_id'); ?>" />
				<p>ID formularza Gravity Forms który rejestruje VIP'a po wpisaniu kodu.</p>
			</div>
        <?php
    }
    function display_code_list()
    {
        ?>
		<div class="form-field">
			<input type="text" name="code_list" id="code_list" value="<?php echo get_option('code_list'); ?>" />
			<p>Odziel kody przecinkami i spacją, ostatni kod bez przecinka na końcu przykład: XXX, YYY, ZZZ lub zostaw puste.</p>
		</div>
        <?php
	}

	function display_exhibit_code_list()
    {
        ?>
		<div class="form-field">
			<input type="text" name="exhibit_code_list" id="exhibit_code_list" value="<?php echo get_option('exhibit_code_list'); ?>" />
			<p>Odziel kody przecinkami i spacją, ostatni kod bez przecinka na końcu przykład: XXX, YYY, ZZZ lub zostaw puste.</p>
		</div>
        <?php
	}

	function display_vip_code_list()
    {
        ?>
		<div class="form-field">
			<input type="text" name="vip_code_list" id="vip_code_list" value="<?php echo get_option('vip_code_list'); ?>" />
			<p>Odziel kody przecinkami i spacją, ostatni kod bez przecinka na końcu przykład: XXX, YYY, ZZZ lub zostaw puste.</p>
		</div>
        <?php
	}
	
	function display_h1_heading()
    {
        ?>
		<div class="form-field">
			<input type="text" name="h1_heading" id="h1_heading" value="<?php echo get_option('h1_heading'); ?>" />
			<p>"Wpisz kod zaproszenia, który otrzymałeś"</p>
		</div>
        <?php
	}

	function display_h2_heading()
    {
        ?>
			<div class="form-field">
				<input type="text" name="h2_heading" id="h2_heading" value="<?php echo get_option('h2_heading'); ?>" />
				<p>"Błędny kod, spróbuj ponownie"</p>
			</div>
        <?php
	}
	
	function display_h3_heading()
    {
        ?>
			<div class="form-field">
            	<input type="text" name="h3_heading" id="h3_heading" value="<?php echo get_option('h3_heading'); ?>" />
				<p>"Zostaniesz przekierowany wtedy do formularza rejestracji"</p>
			</div>	
        <?php
	}
	
	function display_button_text()
    {
        ?>
			<div class="form-field">
				<input type="text" name="button_text" id="button_text" value="<?php echo get_option('button_text'); ?>" />
				<p>"WYŚLIJ"</p>
			</div>
        <?php
	}

	function display_list_of_forms()
    {
        ?>
			<div class="form-field">
				<input type="text" name="list_of_forms" id="list_of_forms" value="<?php echo get_option('list_of_forms'); ?>" />
				<p>"np. 1, 2, 3"</p>
			</div>
        <?php
	}

	function display_users_no_in_forms()
    {
        ?>
			<div class="form-field">
				<input type="text" name="users_no_in_forms" id="users_no_in_forms" value="<?php echo get_option('users_no_in_forms'); ?>" />
				<p>"np. 1254"</p>
			</div>
        <?php
	}

	function display_users_multiplier()
    {
        ?>
			<div class="form-field">
				<input type="text" name="users_multiplier" id="users_multiplier" value="<?php echo get_option('users_multiplier'); ?>" />
				<p>Domyślnie 2</p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_name()
    {
		$pwe_name_pl = shortcode_exists("pwe_name_pl") ? do_shortcode('[pwe_name_pl]') : "";
		$pwe_name_pl_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_name_pl) && $pwe_name_pl !== "");
        ?>
			<div class="form-field half-tab-code-system">
				<input 
					<?php echo $pwe_name_pl_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text" 
					name="trade_fair_name" 
					id="trade_fair_name" 
					value="<?php echo $pwe_name_pl_available ? $pwe_name_pl : get_option('trade_fair_name'); ?>" 
				/>
				<p><?php echo $pwe_name_pl_available ? "Dane pobrane z CAP DB" : "np. Warsaw Fleet Expo"; ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_name_eng()
    {
		$pwe_name_pl = shortcode_exists("pwe_name_pl") ? do_shortcode('[pwe_name_pl]') : "";
        $pwe_name_en = shortcode_exists("pwe_name_en") ? do_shortcode('[pwe_name_en]') : "";
        $pwe_name_en = !empty($pwe_name_en) ? $pwe_name_en : $pwe_name_pl; 
		$pwe_name_en_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_name_en) && $pwe_name_en !== "");
        ?>
			<div class="form-field half-tab-code-system">
				<input 
					<?php echo $pwe_name_en_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text" 
					name="trade_fair_name_eng" 
					id="trade_fair_name_eng" 
					value="<?php echo $pwe_name_en_available ? $pwe_name_pl : get_option('trade_fair_name_eng'); ?>" 
				/>
				<p><?php echo $pwe_name_en_available ? "Dane pobrane z CAP DB" : "np. Warsaw Fleet Expo"; ?></p>
			</div>
        <?php
	}

	function display_trade_fair_name_ru()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_name_ru" id="trade_fair_name_ru" value="<?php echo get_option('trade_fair_name_ru'); ?>" />
				<p>"np. Warsaw Fleet Expo"</p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_desc()
    {
		$pwe_desc_pl = shortcode_exists("pwe_desc_pl") ? do_shortcode('[pwe_desc_pl]') : "";
		$pwe_desc_pl_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_desc_pl) && $pwe_desc_pl !== "");
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_desc_pl_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>  
					type="text" 
					name="trade_fair_desc" 
					id="trade_fair_desc" 
					value="<?php echo $pwe_desc_pl_available ? $pwe_desc_pl : get_option('trade_fair_desc'); ?>" 
				/>
				<p><?php echo $pwe_desc_pl_available ? "Dane pobrane z CAP DB" : "np. Międzynarodowe targi bla bla bla"; ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_desc_eng()
    {
		$pwe_desc_en = shortcode_exists("pwe_desc_en") ? do_shortcode('[pwe_desc_en]') : "";
		$pwe_desc_en_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_desc_en) && $pwe_desc_en !== "");
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_desc_en_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>  
					type="text" 
					name="trade_fair_desc_eng" 
					id="trade_fair_desc_eng" 
					value="<?php echo $pwe_desc_en_available ? $pwe_desc_en : get_option('trade_fair_desc_eng'); ?>" 
				/>
				<p><?php echo $pwe_desc_en_available ? "Dane pobrane z CAP DB" : "np. Międzynarodowe targi bla bla bla"; ?></p>
			</div>
        <?php
	}

	function display_trade_fair_desc_short()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_desc_short" id="trade_fair_desc_short" value="<?php echo get_option('trade_fair_desc_short'); ?>" />
				<p>"np. Międzynarodowe targi bla bla bla"</p>
			</div>
        <?php
	}

	function display_trade_fair_desc_short_eng()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_desc_short_eng" id="trade_fair_desc_short_eng" value="<?php echo get_option('trade_fair_desc_short_eng'); ?>" />
				<p>"np. Międzynarodowe targi bla bla bla"</p>
			</div>
        <?php
	}

	function display_trade_fair_desc_ru()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_desc_ru" id="trade_fair_desc_ru" value="<?php echo get_option('trade_fair_desc_ru'); ?>" />
				<p>"np. Międzynarodowe targi bla bla bla"</p>
			</div>
        <?php
	}

	function get_trade_fair_dates() {
		$pwe_shortcodes_available = empty(get_option("pwe_general_options", [])["pwe_dp_shortcodes_unactive"]);

		$pwe_date_start = shortcode_exists("pwe_date_start") ? do_shortcode("[pwe_date_start]") : "";
		$pwe_date_start_available = (empty(get_option("pwe_general_options", [])["pwe_dp_shortcodes_unactive"]) && !empty($pwe_date_start));
		$pwe_date_end = shortcode_exists("pwe_date_end") ? do_shortcode("[pwe_date_end]") : "";
		$pwe_date_end_available = (empty(get_option("pwe_general_options", [])["pwe_dp_shortcodes_unactive"]) && !empty($pwe_date_end));
	
		// Getting dates or default values
		$start_date = $pwe_date_start_available ? $pwe_date_start : get_option("trade_fair_datetotimer");
		$end_date = $pwe_date_end_available ? $pwe_date_end : get_option("trade_fair_enddata");
	
		// Remove time from date if exists
		$start_date = preg_replace("/^(\d{4}\/\d{2}\/\d{2}) \d{2}:\d{2}$/", "$1", $start_date);
		$end_date = preg_replace("/^(\d{4}\/\d{2}\/\d{2}) \d{2}:\d{2}$/", "$1", $end_date);
	
		return [$start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available];
	}
	
	function format_trade_fair_date($start_date, $end_date, $lang = "pl") {
		$months = [
			"pl" => [
				"01" => "stycznia", 
				"02" => "lutego", 
				"03" => "marca", 
				"04" => "kwietnia",
				"05" => "maja", 
				"06" => "czerwca", 
				"07" => "lipca", 
				"08" => "sierpnia",
				"09" => "września", 
				"10" => "października", 
				"11" => "listopada", 
				"12" => "grudnia",
			],
			"en" => [
				"01" => "January", 
				"02" => "February", 
				"03" => "March", 
				"04" => "April",
				"05" => "May", 
				"06" => "June", 
				"07" => "July", 
				"08" => "August",
				"09" => "September", 
				"10" => "October", 
				"11" => "November", 
				"12" => "December",
			]
		];
	
		$start_parts = explode("/", $start_date);
		$end_parts = explode("/", $end_date);
	
		$start_day = intval($start_parts[2]);
		$start_month = $start_parts[1];
		$start_year = $start_parts[0];
	
		$end_day = intval($end_parts[2]);
		$end_month = $end_parts[1];
		$end_year = $end_parts[0];
	
		$start_month_name = $months[$lang][$start_month] ?? "";
		$end_month_name = $months[$lang][$end_month] ?? "";
	
		// Same day
		if ($start_date === $end_date) {
			return ($lang === "pl") ? "$start_day $start_month_name $start_year" : "$start_month_name $start_day, $start_year";
		}
	
		// Same month and year
		if ($start_month === $end_month && $start_year === $end_year) {
			return ($lang === "pl") ? "$start_day - $end_day $start_month_name $start_year" : "$start_month_name $start_day-$end_day, $start_year";
		}
	
		// Different months, same year
		if ($start_year === $end_year) {
			return ($lang === "pl") ? "$start_day $start_month_name - $end_day $end_month_name $start_year" : "$start_month_name $start_day - $end_month_name $end_day, $start_year";
		}
	
		// Different years
		return ($lang === "pl") ? "$start_day $start_month_name $start_year - $end_day $end_month_name $end_year" : "$start_month_name $start_day, $start_year - $end_month_name $end_day, $end_year";
	}

	function display_trade_fair_date_field($lang = "pl") {
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$current_time = strtotime("now");
		$new_date_comming_soon = ($lang === "pl") ? "Nowa data wkrótce" : "New date comming soon";
		$formatted_date = (empty($start_date) || (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time))
			? $new_date_comming_soon
			: format_trade_fair_date($start_date, $end_date, $lang);

		$option_name = ($lang === "pl") ? "trade_fair_date" : "trade_fair_date_eng";
		$placeholder = ($lang === "pl") ? "np. 15-16 grudnia 2026" : "e.g. December 15-16, 2026";

		?>
		<div class="form-field">
			<input
				<?php echo $pwe_shortcodes_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
				type="text"
				name="<?php echo $option_name; ?>"
				id="<?php echo $option_name; ?>"
				placeholder="<?php echo $pwe_shortcodes_available ? $formatted_date : get_option($option_name) ?>"
				value="<?php echo !$pwe_shortcodes_available ? get_option($option_name) : "" ?>"
			/>
			<p>
				<?php echo ($pwe_date_start_available && $pwe_date_end_available) ? "Dane pobrane z CAP DB" : $placeholder; ?>
			</p>
		</div>
		<?php
	}
	
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_date() {
		display_trade_fair_date_field("pl");
	}
	
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_date_eng() {
		display_trade_fair_date_field("en");
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_datetotimer() {
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$lang = strtolower(ICL_LANGUAGE_CODE);
		$current_time = strtotime("now");

		$date = (empty($start_date) || (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time))
			? ""
			: $start_date;

		// Check if the result is in YYYY/MM/DD format (10 characters)
		if (is_string($date) && preg_match('/^\d{4}[\/-]\d{2}[\/-]\d{2}$/', $date)) {
			$date .= " 10:00"; // Add hour 10:00
		}

		$option_name = "trade_fair_datetotimer";

		?>
			<div class="form-field">
				<input 
					<?php echo $pwe_shortcodes_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text"
					name="<?php echo $option_name; ?>"
					id="<?php echo $option_name; ?>"
					placeholder="<?php echo $pwe_shortcodes_available ? $date : get_option($option_name) ?>"
					value="<?php echo !$pwe_shortcodes_available ? get_option($option_name) : "" ?>"
				/>
				<p><?php echo $pwe_date_start_available ? "Dane pobrane z CAP DB" : "2025/10/14 10:00 (Y:M:D H:M)"; ?></p>
			</div>
        <?php
	}
	
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_enddata() {
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$lang = strtolower(ICL_LANGUAGE_CODE);
		$current_time = strtotime("now");

		$date = (empty($start_date) || (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time))
			? ""
			: $end_date;

		// Check if the result is in YYYY/MM/DD format (10 characters)
		if (is_string($date) && preg_match('/^\d{4}[\/-]\d{2}[\/-]\d{2}$/', $date)) {
			$date .= " 17:00"; // Add hour 17:00
		}

		$option_name = "trade_fair_enddata";

		?>
			<div class="form-field">
				<input 
					<?php echo $pwe_shortcodes_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text"
					name="<?php echo $option_name; ?>"
					id="<?php echo $option_name; ?>"
					placeholder="<?php echo $pwe_shortcodes_available ? $date : get_option($option_name) ?>"
					value="<?php echo !$pwe_shortcodes_available ? get_option($option_name) : "" ?>"
				/>
				<p><?php echo $pwe_date_end_available ? "Dane pobrane z CAP DB" : "2025/10/16 10:00 (Y:M:D H:M)"; ?></p>
			</div>
		<?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_date_custom_format() {
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$lang = strtolower(ICL_LANGUAGE_CODE);
		$new_date_comming_soon = "Nowa data wkrótce / New date comming soon";

		$current_time = strtotime("now");

		$custom_date = (empty($start_date) || (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time))
			? $new_date_comming_soon
			: PWECommonFunctions::transform_dates($start_date, $end_date, false);

		$option_name = "trade_fair_date_custom_format";
		$placeholder = "14-16|10|2025 (D-D|M|Y)";
		
		?>
			<div class="form-field">
				<input
					<?php echo $pwe_shortcodes_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text"
					name="<?php echo $option_name; ?>"
					id="<?php echo $option_name; ?>"
					placeholder="<?php echo $pwe_shortcodes_available ? $custom_date : get_option($option_name) ?>"
					value="<?php echo !$pwe_shortcodes_available ? get_option($option_name) : "" ?>"
				/>
				<p><?php echo ($pwe_date_start_available && $pwe_date_end_available) ? "Dane pobrane z CAP DB" : $placeholder; ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_catalog()
	{
		$pwe_catalog = shortcode_exists("pwe_catalog") ? do_shortcode('[pwe_catalog]') : "";
		$pwe_catalog_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_catalog) && $pwe_catalog !== "");
		?>
			<div class="form-field">
				<input 	
					<?php echo $pwe_catalog_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text" 
					name="trade_fair_catalog" 
					id="trade_fair_catalog" 
					value="<?php echo $pwe_catalog_available ? $pwe_catalog : get_option('trade_fair_catalog'); ?>" 
				/>
				<p><?php echo $pwe_catalog_available ? "Dane pobrane z CAP DB" : "np. 69"; ?></p>
			</div>
		<?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_catalog_id()
	{
		$pwe_catalog_id = shortcode_exists("pwe_catalog_id") ? do_shortcode('[pwe_catalog_id]') : "";
		$pwe_catalog_id_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_catalog_id) && $pwe_catalog_id !== "");
		?>
			<div class="form-field">
				<input 	
					<?php echo $pwe_catalog_id_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text" 
					name="trade_fair_catalog_id" 
					id="trade_fair_catalog_id" 
					value="<?php echo $pwe_catalog_id_available ? $pwe_catalog_id : get_option('trade_fair_catalog_id'); ?>" 
				/>
				<p><?php echo $pwe_catalog_id_available ? "Dane pobrane z CAP DB" : "np. 69"; ?></p>
			</div>
		<?php
	}

    function display_trade_fair_catalog_year()
	{
		$pwe_date_start = shortcode_exists("pwe_date_start") ? do_shortcode('[pwe_date_start]') : "";
		$pwe_date_start_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_start));
		$result = $pwe_date_start_available ? date('Y', strtotime($pwe_date_start)) : get_option('trade_fair_catalog_year');
		?>
			<div class="form-field">
				<input 
					<?php echo $pwe_date_start_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text" 
					name="trade_fair_catalog_year" 
					id="trade_fair_catalog_year" 
					value="<?php echo $result ?>" 
				/>
				<p><?php echo $pwe_date_start_available ? "Dane pobrane z CAP DB" : "2026"; ?></p>
			</div>
		<?php
	}

	function display_trade_fair_conferance()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_conferance" id="trade_fair_conferance" value="<?php echo get_option('trade_fair_conferance'); ?>" />
				<p>"Przykład -> <?php echo get_option('trade_fair_name') ?> Innowations"</p>
			</div>
        <?php
	}

	function display_trade_fair_conferance_eng()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_conferance_eng" id="trade_fair_conferance_eng" value="<?php echo get_option('trade_fair_conferance_eng'); ?>" />
				<p>"Przykład -> <?php echo get_option('trade_fair_name') ?> Innowations"</p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_1stbuildday()
    {
		$pwe_date_start = shortcode_exists("pwe_date_start") ? do_shortcode('[pwe_date_start]') : "";
		$pwe_date_start_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_start));
		$result = $pwe_date_start_available ? $pwe_date_start : get_option('trade_fair_datetotimer');
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_date_start_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>  
					type="text" 
					name="trade_fair_1stbuildday" 
					id="trade_fair_1stbuildday" 
					value="<?php echo $pwe_date_start_available ? (date('d.m.Y', strtotime($result . ' -2 day')) . ' 8:00-18:00') : get_option('trade_fair_1stbuildday') ?>" 
				/>
				<p><?php echo $pwe_date_start_available ? "Dane pobrane z CAP DB" : 'wartość domyślna -> ' . date('d.m.Y', strtotime($result . ' -2 day')) . ' 8:00-18:00' ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_2ndbuildday()
    {
		$pwe_date_start = shortcode_exists("pwe_date_start") ? do_shortcode('[pwe_date_start]') : "";
		$pwe_date_start_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_start));
		$result = $pwe_date_start_available ? (date('d.m.Y', strtotime($pwe_date_start . ' -1 day')) . ' 8:00-20:00') : get_option('trade_fair_2ndbuildday');
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_date_start_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>  
					type="text" 
					name="trade_fair_2ndbuildday" 
					id="trade_fair_2ndbuildday" 
					value="<?php echo $result ?>" 
					/>
				<p><?php echo $pwe_date_start_available ? "Dane pobrane z CAP DB" : 'wartość domyślna -> ' . date('d.m.Y', strtotime($result . ' -1 day')) . ' 8:00-18:00' ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_1stdismantlday()
    {
		$pwe_date_end = shortcode_exists("pwe_date_end") ? do_shortcode('[pwe_date_end]') : "";
		$pwe_date_end_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_end));
		$result = $pwe_date_end_available ? $pwe_date_end : get_option('trade_fair_enddata');
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_date_end_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>  
					type="text" 
					name="trade_fair_1stdismantlday" 
					id="trade_fair_1stdismantlday" 
					value="<?php echo $pwe_date_end_available ? date('d.m.Y', strtotime($result)) . ' 17:00-24:00' : get_option('trade_fair_1nddismantlday'); ?>" 
				/>
				<p><?php echo $pwe_date_end_available ? "Dane pobrane z CAP DB" : 'wartość domyślna -> ' . date('d.m.Y', strtotime($result)) . ' 17:00-24:00' ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_2nddismantlday()
    {
		$pwe_date_end = shortcode_exists("pwe_date_end") ? do_shortcode('[pwe_date_end]') : "";
		$pwe_date_end_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_end));
        $result = $pwe_date_end_available ? $pwe_date_end : get_option('trade_fair_enddata');
		?>
			<div class="form-field">
				<input 
					<?php echo $pwe_date_end_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>  
					type="text" 
					name="trade_fair_2nddismantlday" 
					id="trade_fair_2nddismantlday" 
					value="<?php echo $pwe_date_end_available ? date('d.m.Y', strtotime($result . ' +1 day')) . ' 8:00-12:00' : get_option('trade_fair_2nddismantlday'); ?>" 
				/>
				<p><?php echo $pwe_date_end_available ? "Dane pobrane z CAP DB" : 'wartość domyślna -> ' . date('d.m.Y', strtotime($result . ' +1 day')) . ' 8:00-12:00' ?></p>
			</div>
        <?php
	}

	function display_trade_fair_actualyear()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_actualyear" id="trade_fair_actualyear" value="<?php echo date('Y') ?>" disabled/>
				<p>"Automatycznie pobierany aktulny rok"</p>
			</div>
        <?php
	}

	function display_trade_fair_branzowy_field($lang = "pl")
	{
		$pwe_shortcodes_available = empty(get_option("pwe_general_options", [])["pwe_dp_shortcodes_unactive"]);
		$new_date_comming_soon = ($lang === "pl") ? "Nowa data wkrótce" : "New date comming soon";

		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available) = get_trade_fair_dates();

		$current_time = strtotime("now");
		$new_date_comming_soon = ($lang === "pl") ? "Nowa data wkrótce" : "New date comming soon";

		$option_name = ($lang === "pl") ? "trade_fair_branzowy" : "trade_fair_branzowy_eng";
		$placeholder = ($lang === "pl") ? "np. 15 grudnia 2020" : "e.g. December 15, 2020";

		// no dates → immediate message
		if (empty($start_date)) {
			$industry_day = $new_date_comming_soon;
		} else if (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time) {
			$industry_day = $new_date_comming_soon;
		} else {
			if ($lang === "pl") {
				setlocale(LC_TIME, "pl_PL.UTF-8");
				$industry_day = strftime("%e %B %Y", strtotime($start_date));
			} else {
				$industry_day = date("F j, Y", strtotime($start_date)); // US format
			}
		}

		?>
		<div class="form-field">
			<input
				<?php echo $pwe_shortcodes_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>
				type="text"
				name="<?php echo $option_name; ?>"
				id="<?php echo $option_name; ?>"
				placeholder="<?php echo $pwe_shortcodes_available ? $industry_day : "" ?>"
				value="<?php echo $pwe_shortcodes_available ? $industry_day : get_option($option_name) ?>"
			/>
			<p>
				<?php
				echo $pwe_shortcodes_available ? "Dane pobrane z CAP DB" : $placeholder;
				?>
			</p>
		</div>
		<?php
	}

	function display_trade_fair_branzowy() {
		display_trade_fair_branzowy_field("pl");
	}

	function display_trade_fair_branzowy_eng() {
		display_trade_fair_branzowy_field("en");
	}


	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_edition()
    {
		$pwe_edition = shortcode_exists("pwe_edition") ? do_shortcode('[pwe_edition]') : "";
		$pwe_edition_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_edition) && $pwe_edition !== "");
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_edition_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>  
					type="text" 
					name="trade_fair_edition" 
					id="trade_fair_edition" 
					value="<?php echo $pwe_edition_available ? $pwe_edition : get_option('trade_fair_edition'); ?>" 
				/>
				<p><?php echo $pwe_edition_available ? "Dane pobrane z CAP DB" : "np -> 2"; ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_accent()
    {
		$pwe_color_accent = shortcode_exists("pwe_color_accent") ? do_shortcode('[pwe_color_accent]') : "";
		$pwe_color_accent_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_color_accent) && $pwe_color_accent !== "");
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_color_accent_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>   
					type="text" 
					name="trade_fair_accent" 
					id="trade_fair_accent" 
					value="<?php echo $pwe_color_accent_available ? $pwe_color_accent : get_option('trade_fair_accent'); ?>" 
				/>
				<p><?php echo $pwe_color_accent_available ? "Dane pobrane z CAP DB" : "np -> #84gj64"; ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_main2()
    {
		$pwe_color_main2 = shortcode_exists("pwe_color_main2") ? do_shortcode('[pwe_color_main2]') : "";
		$pwe_color_main2_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_color_main2) && $pwe_color_main2 !== "");
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_color_main2_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text" 
					name="trade_fair_main2" 
					id="trade_fair_main2" 
					value="<?php echo $pwe_color_main2_available ? $pwe_color_main2 : get_option('trade_fair_main2'); ?>" />
				<p><?php echo $pwe_color_main2_available ? "Dane pobrane z CAP DB" : "np -> #84gj64"; ?></p>
			</div>
        <?php
	}

	function display_trade_fair_badge()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_badge" id="trade_fair_badge" value="<?php echo get_option('trade_fair_badge'); ?>" />
				<p>"Początek nazwy badge -> ..._gosc_a6 "</p>
			</div>
        <?php
	}

	function display_trade_fair_opisbranzy()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_opisbranzy" id="trade_fair_opisbranzy" value="<?php echo get_option('trade_fair_opisbranzy'); ?>" />
				<p>"np. uprawa i przetwórstwo warzyw"</p>
			</div>
        <?php
	}

	function display_trade_fair_opisbranzy_eng()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_opisbranzy_eng" id="trade_fair_opisbranzy_eng" value="<?php echo get_option('trade_fair_opisbranzy_eng'); ?>" />
				<p>"np. cultivation and processing of vegetables"</p>
			</div>
        <?php
	}
	
	function display_trade_fair_domainadress()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_domainadress" id="trade_fair_domainadress" value="<?php echo str_replace('https://', '', home_url()); ?>" disabled/>
				<p>"Automatycznie pobierany adres strony"</p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_facebook()
    {
		$pwe_facebook = shortcode_exists("pwe_facebook") ? do_shortcode('[pwe_facebook]') : "";
		$pwe_facebook_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_facebook) && $pwe_facebook !== "");
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_facebook_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text" 
					name="trade_fair_facebook" 
					id="trade_fair_facebook" 
					value="<?php echo $pwe_facebook_available ? $pwe_facebook : get_option('trade_fair_facebook'); ?>"
				/>
				<p><?php echo $pwe_facebook_available ? "Dane pobrane z CAP DB" : "https://facebook/..."; ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_instagram()
    {
		$pwe_instagram = shortcode_exists("pwe_instagram") ? do_shortcode('[pwe_instagram]') : "";
		$pwe_instagram_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_instagram) && $pwe_instagram !== "");
        ?>
			<div class="form-field">
				<input 
					<?php echo $pwe_instagram_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>  
					type="text" 
					name="trade_fair_instagram" 
					id="trade_fair_instagram" 
					value="<?php echo $pwe_instagram_available ? $pwe_instagram : get_option('trade_fair_instagram'); ?>"
				/>
				<p><?php echo $pwe_instagram_available ? "Dane pobrane z CAP DB" : "https://instagram/..."; ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_linkedin()
    {
		$pwe_linkedin = shortcode_exists("pwe_linkedin") ? do_shortcode('[pwe_linkedin]') : "";
		$pwe_linkedin_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_linkedin) && $pwe_linkedin !== "");
        ?>
			<div class="form-field">
				<input
					<?php echo $pwe_linkedin_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>  
					type="text" 
					name="trade_fair_linkedin" 
					id="trade_fair_linkedin" 
					value="<?php echo $pwe_linkedin_available ? $pwe_linkedin : get_option('trade_fair_linkedin'); ?>"
				/>
				<p><?php echo $pwe_linkedin_available ? "Dane pobrane z CAP DB" : "https://linkedin/..."; ?></p>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_youtube()
	{
		$pwe_youtube = shortcode_exists("pwe_youtube") ? do_shortcode('[pwe_youtube]') : "";
		$pwe_youtube_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_youtube) && $pwe_youtube !== "");
		?>
			<div class="form-field">
				<input 
					<?php echo $pwe_youtube_available ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?> 
					type="text" 
					name="trade_fair_youtube" 
					id="trade_fair_youtube" 
					value="<?php echo $pwe_youtube_available ? $pwe_youtube : get_option('trade_fair_youtube'); ?>"
				/>
				<p><?php echo $pwe_youtube_available ? "Dane pobrane z CAP DB" : "https://youtube/..."; ?></p>
			</div>
		<?php
	}
	/*END*/

	function display_trade_fair_rejestracja()
    {
        ?>
			<div class="form-field full-tab-code-system">
				<input type="text" name="trade_fair_rejestracja" id="trade_fair_rejestracja" value="<?php echo get_option('trade_fair_rejestracja'); ?>"/>
				<p>"wartość domyślna -> rejestracja@<?php echo $_SERVER['HTTP_HOST']; ?>"</p>
			</div>
        <?php
	}

	function display_trade_fair_contact() 
    {
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 
        $pwe_groups_contacts_data = PWECommonFunctions::get_database_groups_contacts_data();  

        // Get domain address
        $current_domain = $_SERVER['HTTP_HOST'];

		if (!empty($pwe_groups_data) && !empty($pwe_groups_contacts_data)) {
			foreach ($pwe_groups_data as $group) {
				if ($current_domain == $group->fair_domain) {
					foreach ($pwe_groups_contacts_data as $group_contact) {
						if ($group->fair_group == $group_contact->groups_name) {
							if ($group_contact->groups_slug == "biuro-ob") {
								$service_contact_data = json_decode($group_contact->groups_data);
								$service_email = trim($service_contact_data->email);
							}
						} 
					}
				}
			} 
		}

        ?>
			<div class="form-field full-tab-code-system">
				<input 
					type="text" 
					name="trade_fair_contact" 
					id="trade_fair_contact" 
					value="<?php echo get_option('trade_fair_contact'); ?>"
				/>
				<p>"wartość domyślna -> <?php echo !empty($service_email) ? $service_email : ''; ?>"</p>
			</div>
        <?php
	}

	function display_trade_fair_contact_tech() 
    {
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 
        $pwe_groups_contacts_data = PWECommonFunctions::get_database_groups_contacts_data();  

        // Get domain address
        $current_domain = $_SERVER['HTTP_HOST'];

		if (!empty($pwe_groups_data) && !empty($pwe_groups_contacts_data)) {
			foreach ($pwe_groups_data as $group) {
				if ($current_domain == $group->fair_domain) {
					foreach ($pwe_groups_contacts_data as $group_contact) {
						if ($group->fair_group == $group_contact->groups_name) {
							if ($group_contact->groups_slug == "ob-tech-wyst") {
								$tech_contact_data = json_decode($group_contact->groups_data);
								$tech_email = trim($tech_contact_data->email);
							}
						} 
					}
				}
			} 
		}

        ?>
			<div class="form-field full-tab-code-system">
				<input 
					type="text" 
					name="trade_fair_contact_tech" 
					id="trade_fair_contact_tech" 
					value="<?php echo get_option('trade_fair_contact_tech'); ?>"
				/>
				<p>"wartość domyślna -> <?php echo !empty($tech_email) ? $tech_email : ''; ?>"</p>
			</div>
        <?php
	}

	function display_trade_fair_contact_media() 
    {
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 
        $pwe_groups_contacts_data = PWECommonFunctions::get_database_groups_contacts_data();  

        // Get domain address
        $current_domain = $_SERVER['HTTP_HOST'];

		if (!empty($pwe_groups_data) && !empty($pwe_groups_contacts_data)) {
			foreach ($pwe_groups_data as $group) {
				if ($current_domain == $group->fair_domain) {
					foreach ($pwe_groups_contacts_data as $group_contact) {
						if ($group->fair_group == $group_contact->groups_name) {
							if ($group_contact->groups_slug == "ob-marketing-media") {
								$media_contact_data = json_decode($group_contact->groups_data);
								$media_email = trim($media_contact_data->email);
							}
						} 
					}
				}
			} 
		}

        ?>
			<div class="form-field full-tab-code-system">
				<input 
					type="text" 
					name="trade_fair_contact_media" 
					id="trade_fair_contact_media" 
					value="<?php echo get_option('trade_fair_contact_media'); ?>"
				/>
				<p>"wartość domyślna -> <?php echo !empty($media_email) ? $media_email : ''; ?>"</p>
			</div>
        <?php
	}

	function display_trade_fair_lidy() 
    {
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 
        $pwe_groups_contacts_data = PWECommonFunctions::get_database_groups_contacts_data();  

        // Get domain address
        $current_domain = $_SERVER['HTTP_HOST'];

		if (!empty($pwe_groups_data) && !empty($pwe_groups_contacts_data)) {
			foreach ($pwe_groups_data as $group) {
				if ($current_domain == $group->fair_domain) {
					foreach ($pwe_groups_contacts_data as $group_contact) {
						if ($group->fair_group == $group_contact->groups_name) {
							if ($group_contact->groups_slug == "lidy") {
								$lidy_contact_data = json_decode($group_contact->groups_data);
								$lidy_email = trim($lidy_contact_data->email);
							}
						} 
					}
				}
			}
		}

        ?>
			<div class="form-field full-tab-code-system">
				<input 
					type="text" 
					name="trade_fair_lidy" 
					id="trade_fair_lidy" 
					value="<?php echo get_option('trade_fair_lidy'); ?>"
				/>
				<p>"wartość domyślna -> <?php echo !empty($lidy_email) ? $lidy_email : ''; ?>"</p>
			</div>
        <?php
	}
	/*END*/

	function display_trade_fair_date_ru()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_date_ru" id="trade_fair_date_ru" value="<?php echo get_option('trade_fair_date_ru'); ?>" />
				<p>"np. 3 - 5 марта 2020"</p>
			</div>
        <?php
	}
	
	function display_first_day()
    {
        ?>
			<div class="form-field">
				<input type="text" name="first_day" id="first_day" value="<?php echo get_option('first_day'); ?>" />
				<p>"np. 1 marca (piątek) 15:00 - 16:00"</p>
			</div>
        <?php
	}

	function display_first_day_eng()
    {
        ?>
			<div class="form-field">
				<input type="text" name="first_day_eng" id="first_day_eng" value="<?php echo get_option('first_day_eng'); ?>" />
				<p>"np. March 1 (friday) 15:00 - 16:00"</p>
			</div>
        <?php
	}

	function display_first_day_ru()
    {
        ?>
			<div class="form-field">
				<input type="text" name="first_day_ru" id="first_day_ru" value="<?php echo get_option('first_day_ru'); ?>" />
				<p>"np. 3 марта (суббота) 15:00 - 16:00"</p>
			</div>
        <?php
	}

	function display_second_day()
    {
        ?>
			<div class="form-field">
				<input type="text" name="second_day" id="second_day" value="<?php echo get_option('second_day'); ?>" />
				<p>"np. 2 marca (sobota) 15:00 - 16:00"</p>
			</div>
        <?php
	}

	function display_second_day_eng()
    {
        ?>
			<div class="form-field">
				<input type="text" name="second_day_eng" id="second_day_eng" value="<?php echo get_option('second_day_eng'); ?>" />
				<p>"np. March 2 (friday) 15:00 - 16:00"</p>
			</div>
        <?php
	}

	function display_second_day_ru()
    {
        ?>
			<div class="form-field">
				<input type="text" name="second_day_ru" id="second_day_ru" value="<?php echo get_option('second_day_ru'); ?>" />
				<p>"np. 3 марта (суббота) 15:00 - 16:00"</p>
			</div>
        <?php
	}

	function display_third_day()
    {
        ?>
			<div class="form-field">
				<input type="text" name="third_day" id="third_day" value="<?php echo get_option('third_day'); ?>" />
				<p>"np. 3 marca (sobota) 15:00 - 15:30"</p>
			</div>
        <?php
	}

	function display_third_day_eng()
    {
        ?>
			<div class="form-field">
				<input type="text" name="third_day_eng" id="third_day_eng" value="<?php echo get_option('third_day_eng'); ?>" />
				<p>"np. March 3 (friday) 15:00 - 15:30"</p>
			</div>
        <?php
	}

	function display_third_day_ru()
    {
        ?>
			<div class="form-field">
				<input type="text" name="third_day_ru" id="third_day_ru" value="<?php echo get_option('third_day_ru'); ?>" />
				<p>"np. 3 марта (суббота) 15:00 - 16:00"</p>
			</div>
        <?php
	}

	function display_super_shortcode_1()
    {
        ?>
			<div class="form-field">
				<input type="text" name="super_shortcode_1" id="super_shortcode_1" value="<?php echo get_option('super_shortcode_1'); ?>" />
				<p>"np. cokolwiek"</p>
			</div>
        <?php
	}

	function display_super_shortcode_2()
    {
        ?>
			<div class="form-field">
				<input type="text" name="super_shortcode_2" id="super_shortcode_2" value="<?php echo get_option('super_shortcode_2'); ?>" />
				<p>"np. cokolwiek"</p>
			</div>
        <?php
	}


	function days_difference() {
		$trade_fair_date = do_shortcode('[trade_fair_date_custom_format]');
		
		if (preg_match('/(\d{2})-(\d{2})\|(\d{2})\|(\d{4})/', $trade_fair_date, $matches)) {
			// $matches[1] = starting day
			// $matches[2] = end day
			// $matches[3] = month
			// $matches[4] = year
			$start_date = DateTime::createFromFormat('d-m-Y', $matches[1] . '-' . $matches[3] . '-' . $matches[4]);
			$end_date = DateTime::createFromFormat('d-m-Y', $matches[2] . '-' . $matches[3] . '-' . $matches[4]);
			
			// Calculate the difference in days
			$interval = $start_date->diff($end_date);
			$days_difference = $interval->days + 1;
		} else {
			$days_difference = 3;
		}

		return $days_difference;
	}

	function display_trade_fair_registration_benefits_pl()
    {
		if (empty(get_option('trade_fair_registration_benefits_pl'))) {
			$html_code = '
			<ul>
              <li><strong>wejścia na targi po rejestracji przez '. days_difference() .' dni</strong></li>
              <li><strong>możliwość udziału w konferencjach</strong> lub warsztatach na zasadzie “wolnego słuchacza”</li>
              <li>darmowy parking</li>
            </ul>';
		} else {
			$html_code = get_option('trade_fair_registration_benefits_pl');
		}
        ?>
			<div class="form-field">
				<textarea id="trade_fair_registration_benefits_pl" name="trade_fair_registration_benefits_pl" rows="5" cols="100"><?php echo $html_code; ?></textarea>
			</div>
        <?php
	}
	function display_trade_fair_registration_benefits_en()
    {
		if (empty(get_option('trade_fair_registration_benefits_en'))) {
			$html_code = '
			<ul>
              <li><strong>access to the trade fair for all '. days_difference() .' days upon registration</strong></li>
              <li><strong>the chance to join conferences</strong> or workshops as a listener</li>
              <li>free parking</li>
            </ul>';
		} else {
			$html_code = get_option('trade_fair_registration_benefits_en');
		}
        ?>
			<div class="form-field">
				<textarea id="trade_fair_registration_benefits_en" name="trade_fair_registration_benefits_en" rows="5" cols="100"><?php echo $html_code; ?></textarea>
			</div>
        <?php
	}
	function display_trade_fair_ticket_benefits_pl()
    {
		if (empty(get_option('trade_fair_ticket_benefits_pl'))) {
			$html_code = '
			<ul>
				<li><strong>fast track</strong> - szybkie wejście na targi dedykowaną bramką przez '. days_difference() .' dni</li>
				<li><strong>imienny pakiet</strong> - targowy przesyłany kurierem przed wydarzeniem</li>
				<li><strong>welcome pack</strong> - przygotowany specjalnie przez wystawców</li>
				<li>obsługa concierge</li>
				<li>możliwość udziału w konferencjach i&nbsp; warsztatach</li>
				<li>darmowy parking</li>
			</ul>';
		} else {
			$html_code = get_option('trade_fair_ticket_benefits_pl');
		}
        ?>
			<div class="form-field">
				<textarea id="trade_fair_ticket_benefits_pl" name="trade_fair_ticket_benefits_pl" rows="5" cols="100"><?php echo $html_code; ?></textarea>
			</div>
        <?php
	}
	function display_trade_fair_ticket_benefits_en()
    {
		if (empty(get_option('trade_fair_ticket_benefits_en'))) {
			$html_code = '
			<ul>
				<li><strong>fast track access</strong> – skip the line and enter the trade fair through a dedicated priority gate for all '. days_difference() .' days</li>
				<li><strong>Personalized trade fair package</strong> - delivered by courier to your address before the event</li>
				<li><strong>welcome pack</strong> - a special set of materials and gifts prepared by exhibitors</li>
				<li>Concierge service</li>
				<li>Access to conferences and workshops</li>
				<li>Free parking</li>
			</ul>';
		} else {
			$html_code = get_option('trade_fair_ticket_benefits_en');
		}
        ?>
			<div class="form-field">
				<textarea id="trade_fair_ticket_benefits_en" name="trade_fair_ticket_benefits_en" rows="5" cols="100"><?php echo $html_code; ?></textarea>
			</div>
        <?php
	}

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function display_trade_fair_group()
    {
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 

		foreach ($pwe_groups_data as $group) {
			if ($_SERVER['HTTP_HOST'] == $group->fair_domain) {
				$current_group = $group->fair_group;
			}
		}  
        ?>
			<div class="form-field">
				<input 
					<?php echo !empty($current_group) ? "style='pointer-events: none; opacity: 0.5;'" : ""; ?>   
					type="text" 
					name="trade_fair_accent" 
					id="trade_fair_accent" 
					value="<?php echo !empty($current_group) ? $current_group : get_option('trade_fair_group'); ?>" 
				/>
				<p><?php echo !empty($current_group) ? "Dane pobrane z CAP DB" : "np -> gr2"; ?></p>
			</div>
        <?php
	}




	add_action("admin_init", "display_options");
	
	// Visitor Counter
	//include( '../gravityforms/includes/api.php');

	function show_visitors(){
		$form_id = explode(", ", get_option('list_of_forms'));
		$result = GFAPI::count_entries( $form_id );
		$adds = (int)get_option('users_no_in_forms');
		$result = ($result + $adds) * (int)get_option('users_multiplier');
		return $result;
	}
	add_shortcode( 'visitors_counter', 'show_visitors' );

	// Name of the fair
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_name(){
		$pwe_name_pl = shortcode_exists("pwe_name_pl") ? do_shortcode('[pwe_name_pl]') : "";
		$pwe_name_pl_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_name_pl) && $pwe_name_pl !== "");
		$result = $pwe_name_pl_available ? $pwe_name_pl : get_option('trade_fair_name');

		$result = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		return $result;
	}
	add_shortcode( 'trade_fair_name', 'show_trade_fair_name' );

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_name_eng(){
		$pwe_name_pl = shortcode_exists("pwe_name_pl") ? do_shortcode('[pwe_name_pl]') : "";
        $pwe_name_en = shortcode_exists("pwe_name_en") ? do_shortcode('[pwe_name_en]') : "";
        $pwe_name_en = !empty($pwe_name_en) ? $pwe_name_en : $pwe_name_pl; 
		$pwe_name_en_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_name_en) && $pwe_name_en !== "");
		$result = $pwe_name_en_available ? $pwe_name_pl : get_option('trade_fair_name_eng');

		$result = html_entity_decode($result, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		
		return $result;
	}
	add_shortcode( 'trade_fair_name_eng', 'show_trade_fair_name_eng' );

	function show_trade_fair_name_ru(){
		$result = get_option('trade_fair_name_ru');
		return $result;
	}
	add_shortcode( 'trade_fair_name_ru', 'show_trade_fair_name_ru' );

	// Desc of the fair
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_desc(){
		$pwe_desc_pl = shortcode_exists("pwe_desc_pl") ? do_shortcode('[pwe_desc_pl]') : "";
		$pwe_desc_pl_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_desc_pl) && $pwe_desc_pl !== "");
		$result = $pwe_desc_pl_available ? $pwe_desc_pl : get_option('trade_fair_desc');
		return $result;
	}
	add_shortcode( 'trade_fair_desc', 'show_trade_fair_desc' );

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_desc_eng(){
		$pwe_desc_en = shortcode_exists("pwe_desc_en") ? do_shortcode('[pwe_desc_en]') : "";
		$pwe_desc_en_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_desc_en) && $pwe_desc_en !== "");
		$result = $pwe_desc_en_available ? $pwe_desc_en : get_option('trade_fair_desc_eng'); 
		return $result;
	}
	add_shortcode( 'trade_fair_desc_eng', 'show_trade_fair_desc_eng' );

	function show_trade_fair_desc_short(){
		$result = get_option('trade_fair_desc_short');
		if (empty($result)) {
			return  get_option('trade_fair_desc');
		}
		return $result;
	}
	add_shortcode( 'trade_fair_desc_short', 'show_trade_fair_desc_short' );

	function show_trade_fair_desc_short_eng(){
		$result = get_option('trade_fair_desc_short_eng');
		if (empty($result)) {
			return  get_option('trade_fair_desc_eng');
		}
		return $result;
	}
	add_shortcode( 'trade_fair_desc_short_eng', 'show_trade_fair_desc_short_eng' );

	function show_trade_fair_desc_ru(){
		$result = get_option('trade_fair_desc_ru');
		return $result;
	}
	add_shortcode( 'trade_fair_desc_ru', 'show_trade_fair_desc_ru' );
	
	// datetotimer
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_datetotimer(){
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$lang = strtolower(ICL_LANGUAGE_CODE);
		$current_time = strtotime("now");
		$new_date_comming_soon = ($lang === "pl") ? "Nowa data wkrótce" : "New date comming soon";

		$date = (empty($start_date) || (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time))
			? ($pwe_shortcodes_available ? "" : get_option('trade_fair_datetotimer'))
			: $start_date;

		// Check if the result is in YYYY/MM/DD format (10 characters)
		if (is_string($date) && preg_match('/^\d{4}[\/-]\d{2}[\/-]\d{2}$/', $date)) {
			$date .= " 10:00"; // Add hour 10:00
		}

		return $date;
	}
	add_shortcode( 'trade_fair_datetotimer', 'show_trade_fair_datetotimer' );

	// enddata
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_enddata(){
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$lang = strtolower(ICL_LANGUAGE_CODE);
		$current_time = strtotime("now");
		$new_date_comming_soon = ($lang === "pl") ? "Nowa data wkrótce" : "New date comming soon";

		$date = (empty($start_date) || (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time))
			? ($pwe_shortcodes_available ? "" : get_option('trade_fair_enddata'))
			: $end_date;

		// Check if the result is in YYYY/MM/DD format (10 characters)
		if (is_string($date) && preg_match('/^\d{4}[\/-]\d{2}[\/-]\d{2}$/', $date)) {
			$date .= " 17:00"; // Add hour 10:00
		}

		return $date;
	}
	add_shortcode( 'trade_fair_enddata', 'show_trade_fair_enddata' );

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_date_custom_format(){
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$lang = strtolower(ICL_LANGUAGE_CODE);
		$current_time = strtotime("now");
		$new_date_comming_soon = ($lang === "pl") ? "Nowa data wkrótce" : "New date comming soon";

		$date = (empty($start_date) || (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time))
			? ($pwe_shortcodes_available ? $new_date_comming_soon : get_option('trade_fair_date_custom_format'))
			:  PWECommonFunctions::transform_dates($start_date, $end_date, false);

		return $date;
	}
	add_shortcode( 'trade_fair_date_custom_format', 'show_trade_fair_date_custom_format' );

	// Catalog ID
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_catalog(){
		$pwe_catalog = shortcode_exists("pwe_catalog") ? do_shortcode('[pwe_catalog]') : "";
		$pwe_catalog_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_catalog) && $pwe_catalog !== "");
		$result = $pwe_catalog_available ? $pwe_catalog : get_option('trade_fair_catalog');
		return $result;
	}
	add_shortcode( 'trade_fair_catalog', 'show_trade_fair_catalog' );

	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_catalog_id(){
		$pwe_catalog_id = shortcode_exists("pwe_catalog_id") ? do_shortcode('[pwe_catalog_id]') : "";
		$pwe_catalog_id_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_catalog_id) && $pwe_catalog_id !== "");
		$result = $pwe_catalog_id_available ? $pwe_catalog_id : get_option('trade_fair_catalog_id');
		return $result;
	}
	add_shortcode( 'trade_fair_catalog_id', 'show_trade_fair_catalog_id' );

    function show_trade_fair_catalog_year(){
		$pwe_date_start = shortcode_exists("pwe_date_start") ? do_shortcode('[pwe_date_start]') : "";
		$pwe_date_start_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_start));
		$result = $pwe_date_start_available ? date('Y', strtotime($pwe_date_start)) : get_option('trade_fair_catalog_year');
		return $result;
	}
	add_shortcode( 'trade_fair_catalog_year', 'show_trade_fair_catalog_year' );

	// conference
	function show_trade_fair_conferance(){
		$result = get_option('trade_fair_conferance');
		if (empty($result)) {
			return  get_option('trade_fair_desc');
		}
		return $result;
	}
	add_shortcode( 'trade_fair_conferance', 'show_trade_fair_conferance' );

	// conferance eng
	function show_trade_fair_conferance_eng(){
		$result = get_option('trade_fair_conferance_eng');
		if (empty($result)) {
			return  get_option('trade_fair_desc_eng');
		}
		return $result;
	}
	add_shortcode( 'trade_fair_conferance_eng', 'show_trade_fair_conferance_eng' );

	// 1stbuildday
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_1stbuildday(){
		$pwe_date_start = shortcode_exists("pwe_date_start") ? do_shortcode('[pwe_date_start]') : "";
		$pwe_date_start_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_start));
		$result = $pwe_date_start_available ? (date('d.m.Y', strtotime($pwe_date_start . ' -2 day')) . ' 8:00-18:00') : get_option('trade_fair_1stbuildday');	
		
		return $result;
	}	
	add_shortcode( 'trade_fair_1stbuildday', 'show_trade_fair_1stbuildday' );
	
	// 2ndbuildday
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_2ndbuildday(){
		$pwe_date_start = shortcode_exists("pwe_date_start") ? do_shortcode('[pwe_date_start]') : "";
		$pwe_date_start_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_start));
		$result = $pwe_date_start_available ? (date('d.m.Y', strtotime($pwe_date_start . ' -1 day')) . ' 8:00-20:00') : get_option('trade_fair_2ndbuildday');	
		
		return $result;
	}
	add_shortcode( 'trade_fair_2ndbuildday', 'show_trade_fair_2ndbuildday' );

	// 1stdismantlday
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_1stdismantlday(){
		$pwe_date_end = shortcode_exists("pwe_date_end") ? do_shortcode('[pwe_date_end]') : "";
		$pwe_date_end_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_end));
		// if (!empty(get_option('trade_fair_1nddismantlday'))) {
		// 	$result = get_option('trade_fair_1nddismantlday');
		// } else {
			$result = date('d.m.Y', strtotime($pwe_date_end_available ? $pwe_date_end : get_option('trade_fair_enddata'))) . ' 17:00-24:00';
		// }
		return $result;
	}
	add_shortcode( 'trade_fair_1stdismantlday', 'show_trade_fair_1stdismantlday' );
	
	// 2nddismantlday
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_2nddismantlday(){
		$pwe_date_end = shortcode_exists("pwe_date_end") ? do_shortcode('[pwe_date_end]') : "";
		$pwe_date_end_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_date_end));
		// if (!empty(get_option('trade_fair_2nddismantlday'))) {
		// 	$result = get_option('trade_fair_2nddismantlday');
		// } else {
			$result = date('d.m.Y', strtotime(($pwe_date_end_available ? $pwe_date_end : get_option('trade_fair_enddata')) . ' +1 day')) . ' 8:00-12:00';
		// }
		return $result;
	}
	add_shortcode( 'trade_fair_2nddismantlday', 'show_trade_fair_2nddismantlday' );
	/*END*/
	
	// Date of the fair
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_date() {
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$lang = strtolower(ICL_LANGUAGE_CODE);
		$current_time = strtotime("now");
		$new_date_comming_soon = ($lang === "pl") ? "Nowa data wkrótce" : "New date comming soon";

		$date = (empty($start_date) || (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time))
			? ($pwe_shortcodes_available ? $new_date_comming_soon : get_option('trade_fair_date'))
			:  format_trade_fair_date($start_date, $end_date, "pl");

		return $date;
	}
	add_shortcode('trade_fair_date', 'show_trade_fair_date');


	// Date of the fair ENG
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_date_eng() {
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$lang = strtolower(ICL_LANGUAGE_CODE);
		$current_time = strtotime("now");
		$new_date_comming_soon = ($lang === "pl") ? "Nowa data wkrótce" : "New date comming soon";

		$date = (empty($start_date) || (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time))
			? ($pwe_shortcodes_available ? $new_date_comming_soon : get_option('trade_fair_date_eng'))
			:  format_trade_fair_date($start_date, $end_date, "en");

		return $date;
	}
	add_shortcode('trade_fair_date_eng', 'show_trade_fair_date_eng');

	// Date of the fair RU
	function show_trade_fair_date_ru(){
		$result = get_option('trade_fair_date_ru');
		return $result;
	}
	add_shortcode( 'trade_fair_date_ru', 'show_trade_fair_date_ru' );
	
	 
	/*nr edycji*/
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_edition($entry = null, $fields = null) {
		$result = '';
		$lang = '';
	
		// Konwersja $entry na obiekt, jeśli jest tablicą
		if (is_array($entry)) {
			$entry = (object) $entry;
		}
	
		// Konwersja $fields na obiekt, jeśli jest tablicą
		if (is_array($fields)) {
			$fields = (object) $fields;
		}
	
		// Sprawdzenie, czy $entry i $fields są obiektami
		if (is_object($entry) && is_object($fields)) {
			if (isset($fields->{4}) && isset($fields->{4}->label) && is_string($fields->{4}->label)) {
				if (strpos(strtolower($fields->{4}->label), 'wysy') !== false) {
					if (isset($entry->{$fields->{4}->id}) && is_string($entry->{$fields->{4}->id})) {
						$lang = (strpos(strtolower($entry->{$fields->{4}->id}), 'eng') !== false) ? 'en' : 'pl';
					}
				}
			} else {
				foreach ($fields as $key => $field) {
					if (isset($field->label) && is_string($field->label)) {
						if (strpos(strtolower($field->label), 'wysy') !== false) {
							if (isset($entry->{$field->id}) && is_string($entry->{$field->id})) {
								$lang = (strpos(strtolower($entry->{$field->id}), 'eng') !== false) ? 'en' : 'pl';
							}
							break;
						}
					}
				}
			}
		}

		$pwe_edition = shortcode_exists("pwe_edition") ? do_shortcode('[pwe_edition]') : "";
		$pwe_edition_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_edition) && $pwe_edition !== "");
		
		$trade_fair_edition = $pwe_edition_available ? $pwe_edition : get_option('trade_fair_edition');
	
		// Sprawdzenie wartości i ustawienie wyniku
		if ($trade_fair_edition === '1') {
			$result = (get_locale() === "pl_PL" && $lang !== 'en') ? 'Premierowa' : 'Premier';
		} else {
			$result = $trade_fair_edition . '.';
		}
	
		return $result;
	}	
	
	add_shortcode( 'trade_fair_edition', 'show_trade_fair_edition' );

	/*color accent*/
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_accent(){
		$pwe_color_accent = shortcode_exists("pwe_color_accent") ? do_shortcode('[pwe_color_accent]') : "";
		$pwe_color_accent_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_color_accent) && $pwe_color_accent !== "");
		$result = $pwe_color_accent_available ? $pwe_color_accent : get_option('trade_fair_accent');
		return $result;
	}
	add_shortcode( 'trade_fair_accent', 'show_trade_fair_accent' );

	/*color main2*/
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_main2(){
		$pwe_color_main2 = shortcode_exists("pwe_color_main2") ? do_shortcode('[pwe_color_main2]') : "";
		$pwe_color_main2_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_color_main2) && $pwe_color_main2 !== "");
		$result = $pwe_color_main2_available ? $pwe_color_main2 : get_option('trade_fair_main2');
		return $result;
	}
	add_shortcode( 'trade_fair_main2', 'show_trade_fair_main2' );

	function trade_fair_branzowy_result($lang = "pl") {
		list($start_date, $end_date, $pwe_date_start_available, $pwe_date_end_available, $pwe_shortcodes_available) = get_trade_fair_dates();

		$new_date_comming_soon = ($lang === "pl") ? "Nowa data wkrótce" : "New date comming soon"; 
		$current_time = strtotime("now");

		// no dates → immediate message
		if (empty($start_date)) {
			return $new_date_comming_soon;
		}

		// end date expired → message
		if (!empty($end_date) && (strtotime($end_date . " +20 hours")) < $current_time) {
			return $new_date_comming_soon;
		}

		// correct date → we format only the first day
		if ($lang === "pl") {
			setlocale(LC_TIME, "pl_PL.UTF-8");
			$industry_day = strftime("%e %B %Y", strtotime($start_date));
		} else {
			$industry_day = date("F j, Y", strtotime($start_date)); // US format
		}

		return $pwe_shortcodes_available ? $industry_day : (($lang === "pl") ? get_option('trade_fair_branzowy') : get_option('trade_fair_branzowy_eng'));
	}

	/* dzień branżowy */
	function show_trade_fair_branzowy() {
		$result = trade_fair_branzowy_result("pl");

		if (empty($result)) {
			return get_option('trade_fair_date');
		}
		return $result;
	}
	add_shortcode('trade_fair_branzowy', 'show_trade_fair_branzowy');

	/* dzień branżowy ENG */
	function show_trade_fair_branzowy_eng() {
		$result = trade_fair_branzowy_result("en");

		if (empty($result)) {
			return get_option('trade_fair_date_eng');
		}
		return $result;
	}
	add_shortcode('trade_fair_branzowy_eng', 'show_trade_fair_branzowy_eng');

	/*początek badge*/
	function show_trade_fair_badge(){
		$result = get_option('trade_fair_badge');
		return $result;
	}
	add_shortcode( 'trade_fair_badge', 'show_trade_fair_badge' );

	/*opis branzowy*/
	function show_trade_fair_opisbranzy(){
		$result = get_option('trade_fair_opisbranzy');
		return $result;
	}
	add_shortcode( 'trade_fair_opisbranzy', 'show_trade_fair_opisbranzy' );

	/*opis branzowy ENG*/
	function show_trade_fair_opisbranzy_eng(){
		$result = get_option('trade_fair_opisbranzy_eng');
		return $result;
	}
	add_shortcode( 'trade_fair_opisbranzy_eng', 'show_trade_fair_opisbranzy_eng' );

	/*adres facebook*/
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_facebook(){
		$pwe_facebook = shortcode_exists("pwe_facebook") ? do_shortcode('[pwe_facebook]') : "";
		$pwe_facebook_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_facebook) && $pwe_facebook !== "");
		$result = $pwe_facebook_available ? $pwe_facebook : get_option('trade_fair_facebook');
		if (empty($result)) {
			return "https://warsawexpo.eu";
		}
		return $result;
	}
	add_shortcode( 'trade_fair_facebook', 'show_trade_fair_facebook' );

	/*adres instagram*/
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_instagram(){
		$pwe_instagram = shortcode_exists("pwe_instagram") ? do_shortcode('[pwe_instagram]') : "";
		$pwe_instagram_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_instagram) && $pwe_instagram !== "");
		$result = $pwe_instagram_available ? $pwe_instagram : get_option('trade_fair_instagram');
		if (empty($result)) {
			return "https://warsawexpo.eu";
		}
		return $result;
	}
	add_shortcode( 'trade_fair_instagram', 'show_trade_fair_instagram' );

	/*adres linkedin*/
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_linkedin(){
		$pwe_linkedin = shortcode_exists("pwe_linkedin") ? do_shortcode('[pwe_linkedin]') : "";
		$pwe_linkedin_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_linkedin) && $pwe_linkedin !== "");
		$result = $pwe_linkedin_available ? $pwe_linkedin : get_option('trade_fair_linkedin');
		if (empty($result)) {
			return "https://warsawexpo.eu";
		}
		return $result;
	}
	add_shortcode( 'trade_fair_linkedin', 'show_trade_fair_linkedin' );

	/*adres youtube*/
	// Added option from CAP DB <-------------------------------------------------------------------------------------------------<
	function show_trade_fair_youtube(){
		$pwe_youtube = shortcode_exists("pwe_youtube") ? do_shortcode('[pwe_youtube]') : "";
		$pwe_youtube_available = (empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']) && !empty($pwe_youtube) && $pwe_youtube !== "");
		$result = $pwe_youtube_available ? $pwe_youtube : get_option('trade_fair_youtube');
		if (empty($result)) {
			return "https://warsawexpo.eu";
		}
		return $result;
	}
	add_shortcode( 'trade_fair_youtube', 'show_trade_fair_youtube' );
	/*END*/

	// First day 
	function show_first_day(){
		$result = get_option('first_day');
		return $result;
	}
	add_shortcode( 'first_day', 'show_first_day' );

	// First day ENG
	function show_first_day_eng(){
		$result = get_option('first_day_eng');
		return $result;
	}
	add_shortcode( 'first_day_eng', 'show_first_day_eng' );

	// First day RU
	function show_first_day_ru(){
		$result = get_option('first_day_ru');
		return $result;
	}
	add_shortcode( 'first_day_ru', 'show_first_day_ru' );

	// Second day 
	function show_second_day(){
		$result = get_option('second_day');
		return $result;
	}
	add_shortcode( 'second_day', 'show_second_day' );

	// Second day ENG
	function show_second_day_eng(){
		$result = get_option('second_day_eng');
		return $result;
	}
	add_shortcode( 'second_day_eng', 'show_second_day_eng' );

	// Second day RU
	function show_second_day_ru(){
		$result = get_option('second_day_ru');
		return $result;
	}
	add_shortcode( 'second_day_ru', 'show_second_day_ru' );

	// third day 
	function show_third_day(){
		$result = get_option('third_day');
		return $result;
	}
	add_shortcode( 'third_day', 'show_third_day' );

	// third day ENG
	function show_third_day_eng(){
		$result = get_option('third_day_eng');
		return $result;
	}
	add_shortcode( 'third_day_eng', 'show_third_day_eng' );

	// third day RU
	function show_third_day_ru(){
		$result = get_option('third_day_ru');
		return $result;
	}
	add_shortcode( 'third_day_ru', 'show_third_day_ru' );

	
	// super shortcode 1
	function show_super_shortcode_1(){
		$result = get_option('super_shortcode_1');
		return $result;
	}
	add_shortcode( 'super_shortcode_1', 'show_super_shortcode_1' );

	// super shortcode 2
	function show_super_shortcode_2(){
		$result = get_option('super_shortcode_2');
		return $result;
	}
	add_shortcode( 'super_shortcode_2', 'show_super_shortcode_2' );

	// Adres strony 
	function show_trade_fair_domainadress(){
		$result = $_SERVER['HTTP_HOST'];
		if(empty($result)){
			return str_replace('https://', '', home_url());
		}
		return $result;
	}
	add_shortcode( 'trade_fair_domainadress', 'show_trade_fair_domainadress' );

	// Actual Year 
	function show_trade_fair_actualyear(){
		$result = date('Y');
		return $result;
	}
	add_shortcode( 'trade_fair_actualyear', 'show_trade_fair_actualyear' );

	// Email Rejestracji 
	function show_trade_fair_rejestracja(){
		if (empty($result)) {
			return 'rejestracja@' . $_SERVER['HTTP_HOST'];
		}
		return $result;
	}
	add_shortcode( 'trade_fair_rejestracja', 'show_trade_fair_rejestracja' );

	// Email kontaktu
	function show_trade_fair_contact(){
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 
        $pwe_groups_contacts_data = PWECommonFunctions::get_database_groups_contacts_data();  

        // Get domain address
        $current_domain = $_SERVER['HTTP_HOST'];
		$result = '';

		if (!empty($pwe_groups_data) && !empty($pwe_groups_contacts_data)) {
			foreach ($pwe_groups_data as $group) {
				if ($current_domain == $group->fair_domain) {
					foreach ($pwe_groups_contacts_data as $group_contact) {
						if ($group->fair_group == $group_contact->groups_name) {
							if ($group_contact->groups_slug == "biuro-ob") {
								$service_contact_data = json_decode($group_contact->groups_data);
								$service_email = trim($service_contact_data->email);
							}
						} 
					}
				}
			}
		}

		$result = !empty($service_email) ? $service_email : get_option('trade_fair_contact');

		return $result;
	}
	add_shortcode( 'trade_fair_contact', 'show_trade_fair_contact' );

	// Email kontaktu do działu technicznego
	function show_trade_fair_contact_tech(){
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 
        $pwe_groups_contacts_data = PWECommonFunctions::get_database_groups_contacts_data();  

        // Get domain address
        $current_domain = $_SERVER['HTTP_HOST'];
		$result = '';

		if (!empty($pwe_groups_data) && !empty($pwe_groups_contacts_data)) {
			foreach ($pwe_groups_data as $group) {
				if ($current_domain == $group->fair_domain) {
					foreach ($pwe_groups_contacts_data as $group_contact) {
						if ($group->fair_group == $group_contact->groups_name) {
							if ($group_contact->groups_slug == "ob-tech-wyst") {
								$tech_contact_data = json_decode($group_contact->groups_data);
								$tech_email = trim($tech_contact_data->email);
							}
						} 
					}
				}
			}
		}

		$result = !empty($tech_email) ? $tech_email : get_option('trade_fair_contact_tech');

		return $result;
	}
	add_shortcode( 'trade_fair_contact_tech', 'show_trade_fair_contact_tech' );

	// Email kontaktu do działu marketingu i media
	function show_trade_fair_contact_media(){
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 
        $pwe_groups_contacts_data = PWECommonFunctions::get_database_groups_contacts_data();  

        // Get domain address
        $current_domain = $_SERVER['HTTP_HOST'];
		$result = '';

		if (!empty($pwe_groups_data) && !empty($pwe_groups_contacts_data)) {
			foreach ($pwe_groups_data as $group) {
				if ($current_domain == $group->fair_domain) {
					foreach ($pwe_groups_contacts_data as $group_contact) {
						if ($group->fair_group == $group_contact->groups_name) {
							if ($group_contact->groups_slug == "ob-marketing-media") {
								$media_contact_data = json_decode($group_contact->groups_data);
								$media_email = trim($media_contact_data->email);
							}
						} 
					}
				}
			}
		}

		$result = !empty($media_email) ? $media_email : get_option('trade_fair_contact_media');

		return $result;
	}
	add_shortcode( 'trade_fair_contact_media', 'show_trade_fair_contact_media' );

	function show_trade_fair_lidy(){
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 
        $pwe_groups_contacts_data = PWECommonFunctions::get_database_groups_contacts_data();  

        // Get domain address
        $current_domain = $_SERVER['HTTP_HOST'];
		$result = '';

		if (!empty($pwe_groups_data) && !empty($pwe_groups_contacts_data)) {
			foreach ($pwe_groups_data as $group) {
				if ($current_domain == $group->fair_domain) {
					foreach ($pwe_groups_contacts_data as $group_contact) {
						if ($group->fair_group == $group_contact->groups_name) {
							if ($group_contact->groups_slug == "lidy") {
								$lidy_contact_data = json_decode($group_contact->groups_data);
								$lidy_email = trim($lidy_contact_data->email);
							}
						} 
					}
				}
			}
		}

		$result = !empty($lidy_email) ? $lidy_email : get_option('trade_fair_lidy');

		return $result;
	}
	add_shortcode( 'trade_fair_lidy', 'show_trade_fair_lidy' );

	function show_trade_fair_group(){
		$pwe_groups_data = PWECommonFunctions::get_database_groups_data(); 

		foreach ($pwe_groups_data as $group) {
			if ($_SERVER['HTTP_HOST'] == $group->fair_domain) {
				$current_group = $group->fair_group;
			}
		}  

		return $current_group;
	}
	add_shortcode( 'trade_fair_group', 'show_trade_fair_group' );

	



	// trade_fair_registration_benefits_pl
	function show_trade_fair_registration_benefits_pl(){
		if (empty(get_option('trade_fair_registration_benefits_pl'))) {
			$result = '
			<ul>
              <li><strong>wejścia na targi po rejestracji przez '. days_difference() .' dni</strong></li>
              <li><strong>możliwość udziału w konferencjach</strong> lub warsztatach na zasadzie “wolnego słuchacza”</li>
              <li>darmowy parking</li>
            </ul>';
		} else {
			$result = get_option('trade_fair_registration_benefits_pl');
		}
		return $result;
	}
	add_shortcode( 'trade_fair_registration_benefits_pl', 'show_trade_fair_registration_benefits_pl' );

	// trade_fair_registration_benefits_en
	function show_trade_fair_registration_benefits_en(){
		if (empty(get_option('trade_fair_registration_benefits_en'))) {
			$result = '
			<ul>
              <li><strong>access to the trade fair for all '. days_difference() .' days upon registration</strong></li>
              <li><strong>the chance to join conferences</strong> or workshops as a listener</li>
              <li>free parking</li>
            </ul>';
		} else {
			$result = get_option('trade_fair_registration_benefits_en');
		}
		return $result;
	}
	add_shortcode( 'trade_fair_registration_benefits_en', 'show_trade_fair_registration_benefits_en' );

	// trade_fair_ticket_benefits_pl
	function show_trade_fair_ticket_benefits_pl(){
		if (empty(get_option('trade_fair_ticket_benefits_pl'))) {
			$result = '
			<ul>
				<li><strong>fast track</strong> - szybkie wejście na targi dedykowaną bramką przez '. days_difference() .' dni</li>
				<li><strong>imienny pakiet</strong> - targowy przesyłany kurierem przed wydarzeniem</li>
				<li><strong>welcome pack</strong> - przygotowany specjalnie przez wystawców</li>
				<li>obsługa concierge</li>
				<li>możliwość udziału w konferencjach i&nbsp; warsztatach</li>
				<li>darmowy parking</li>
			</ul>';
		} else {
			$result = get_option('trade_fair_ticket_benefits_pl');
		}
		return $result;
	}
	add_shortcode( 'trade_fair_ticket_benefits_pl', 'show_trade_fair_ticket_benefits_pl' );

	// trade_fair_ticket_benefits_en
	function show_trade_fair_ticket_benefits_en(){
		if (empty(get_option('trade_fair_ticket_benefits_en'))) {
			$result = '
			<ul>
				<li><strong>fast track access</strong> – skip the line and enter the trade fair through a dedicated priority gate for all '. days_difference() .' days</li>
				<li><strong>Personalized trade fair package</strong> - delivered by courier to your address before the event</li>
				<li><strong>welcome pack</strong> - a special set of materials and gifts prepared by exhibitors</li>
				<li>Concierge service</li>
				<li>Access to conferences and workshops</li>
				<li>Free parking</li>
			</ul>';
		} else {
			$result = get_option('trade_fair_ticket_benefits_en');
		}
		return $result;
	}
	add_shortcode( 'trade_fair_ticket_benefits_en', 'show_trade_fair_ticket_benefits_en' );




	//Zakodowanie danych uzytkownika tylko dla GF
	function show_trade_fair_gf_coder($form, $entry){
		return rtrim(base64_encode($form . ',' . $entry), '=');
	}
	add_shortcode( 'trade_fair_gf_coder', 'show_trade_fair_gf_coder' );

	//* Shortcode to display form success on another page 
	add_shortcode('form_data', 'form_data_function'); 
	function form_data_function( $atts ) { 
		$datas = shortcode_atts( array( 'data' => '', ), $atts );	

		$pageURL = $_SERVER['REQUEST_URI'];
		if(strpos($pageURL, 'registration-confirmation') !== false)  { $downloadQRbtn = "Download QR"; }
		else if(strpos($pageURL, 'podtverzhdeniye-registratsii') !== false) { $downloadQRbtn = "Скачать QR"; }
		else { $downloadQRbtn = "Pobierz QR"; }
		
		// Get var from parameter in Gravity Forms confirmation page url
		if($datas['data'] == 'myqrcode'){
			extract($datas);
			$qr_code = substr($_GET[$data], 11, -5);
			ob_start(); ?>
			<center><span class="btn-container"><a href="<?= $qr_code ?>" class="custom-link btn border-width-0 btn-default btn-icon-left" download><?php _e($downloadQRbtn, "ty-page") ?></a></span></center>
			<?php return ob_get_clean();
		}else{
			extract($datas);
			return $_GET[$data];
		}
	}


	// FOR YOAST SEO START <----------------------------------------------------------------------<

	// Rozrzeżony opis targów
	function sc_pwe_trade_fair_full_desc() {
		$domain = $_SERVER['HTTP_HOST'];
		$shortcodes_active = empty(get_option('pwe_general_options', [])['pwe_dp_shortcodes_unactive']);
		$lang = strtolower(ICL_LANGUAGE_CODE);

        if (!function_exists('get_translated_field')) {
            function get_translated_field($fair, $field_base_name) {
                // Get the language in the format e.g. "de", "pl"
                $lang = strtolower(ICL_LANGUAGE_CODE); // "de"

                // Check if a specific translation exists (e.g. fair_name_{lang})
                $field_with_lang = "{$field_base_name}_{$lang}";

                if (!empty($fair[$field_with_lang])) {
                    return $fair[$field_with_lang];
                }

                // Fallback to English
                $fallback = "{$field_base_name}_en";
                return $fair[$fallback] ?? '';
            }
        }

        if (!function_exists('get_pwe_shortcode')) {
            function get_pwe_shortcode($shortcode, $domain) {
                return shortcode_exists($shortcode) ? do_shortcode('[' . $shortcode . ' domain="' . $domain . '"]') : "";
            }
        }

        if (!function_exists('check_available_pwe_shortcode')) {
            function check_available_pwe_shortcode($shortcodes_active, $shortcode) {
                return $shortcodes_active && !empty($shortcode) && $shortcode !== "Brak danych";
            }
        }

        $translates = PWECommonFunctions::get_database_translations_data($domain);

        $shortcode_full_desc = get_pwe_shortcode("pwe_full_desc_$lang", $domain);
        $shortcode_full_desc_available = check_available_pwe_shortcode($shortcodes_active, $shortcode_full_desc);
        $fair_full_desc = $shortcode_full_desc_available ? get_translated_field($translates[0], 'fair_full_desc') : '';

        if (!empty($fair_full_desc)) {
            $description = strstr($fair_full_desc, '<br>', true);
            
            // If strstr returned false (i.e. no <br>), we assign the entire content
            if ($description === false) {
                $description = $fair_full_desc;
            }
        }

		return $description;

	}
	add_shortcode('trade_fair_full_desc', 'sc_pwe_trade_fair_full_desc');

	// Aktualności
	function sc_pwe_text_news() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Bądź na bieżąco z wydarzeniami i nowościami związanymi z '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		} else {
			return 'Stay up to date with events and news related to '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		}
	}
	add_shortcode('sc_pwe_text_news', 'sc_pwe_text_news');

	// Dla odwiedzających
	function sc_pwe_text_for_visitors() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Sprawdź, dlaczego warto odwiedzić '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .' – znajdziesz tu najnowsze trendy, innowacje i inspirujące rozwiązania.';
		} else {
			return 'Check out why you should visit '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .' – discover the latest trends, innovations, and inspiring solutions.';
		}
	}
	add_shortcode('sc_pwe_text_for_visitors', 'sc_pwe_text_for_visitors');

	// Dla wystawców
	function sc_pwe_text_for_exhibitors() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Zdobądź nowych klientów i pokaż swoją markę na '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		} else {
			return 'Gain new customers and showcase your brand at '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		}
	}
	add_shortcode('sc_pwe_text_for_exhibitors', 'sc_pwe_text_for_exhibitors');

	// Dodaj do kalendarza
	function sc_pwe_text_add_calendar() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Nie przegap '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]').'! Dodaj wydarzenie do swojego kalendarza.';
		} else {
			return 'Don\'t miss '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]').'! Add the event to your calendar.';
		}
	}
	add_shortcode('sc_pwe_text_add_calendar', 'sc_pwe_text_add_calendar');

	// Galeria
	function sc_pwe_text_gallery() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Zobacz galerię '. do_shortcode('[trade_fair_name]') .' – sprawdź jak wyglądają targi z perspektywy obiektywu.';
		} else {
			return 'See the gallery of '. do_shortcode('[trade_fair_name_eng]') .' – check out the fair through the lens of the camera.';
		}
	}
	add_shortcode('sc_pwe_text_gallery', 'sc_pwe_text_gallery');

	// Informacje organizacyjne
	function sc_pwe_text_org_info() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Wszystkie niezbędne informacje organizacyjne dla wystawców '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		} else {
			return 'All necessary organizational information for exhibitors at '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		}
	}
	add_shortcode('sc_pwe_text_org_info', 'sc_pwe_text_org_info');

	// Katalog wystawców
	function sc_pwe_text_exh_catalog() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Poznaj firmy i marki obecne na '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		} else {
			return 'Get to know the companies and brands present at '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		}
	}
	add_shortcode('sc_pwe_text_exh_catalog', 'sc_pwe_text_exh_catalog');

	// Wydarzenia
	function sc_pwe_text_events() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Sprawdź wydarzenia towarzyszące '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .' – konferencje, prelekcje, spotkania.';
		} else {
			return 'Check out the events accompanying '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .' – conferences, lectures, meetings.';
		}
	}
	add_shortcode('sc_pwe_text_events', 'sc_pwe_text_events');

	// Kontakt
	function sc_pwe_text_contact() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Skontaktuj się z organizatorami '. do_shortcode('[trade_fair_name]') .' i uzyskaj potrzebne informacje o wydarzeniu.';
		} else {
			return 'Contact the organizers of '. do_shortcode('[trade_fair_name_eng]') .' to get the information you need about the event.';
		}
	}
	add_shortcode('sc_pwe_text_contact', 'sc_pwe_text_contact');

	// Plan targów
	function sc_pwe_text_fair_plan() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Zobacz plan stoisk i atrakcji '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		} else {
			return 'See the booth and attraction plan for '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		}
	}
	add_shortcode('sc_pwe_text_fair_plan', 'sc_pwe_text_fair_plan');

	// Rejestracja
	function sc_pwe_text_registration() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Zarejestruj się na '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .' i odbierz swój bilet na targi.';
		} else {
			return 'Register for '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .' and get your ticket to the fair.';
		}
	}
	add_shortcode('sc_pwe_text_registration', 'sc_pwe_text_registration');

	// Wypromuj się
	function sc_pwe_text_promote_yourself() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Zwiększ rozpoznawalność swojej marki – wypromuj się na '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		} else {
			return 'Increase your brand visibility – promote yourself at '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .'.';
		}
	}
	add_shortcode('sc_pwe_text_promote_yourself', 'sc_pwe_text_promote_yourself');

	// Zostań wystawcą
	function sc_pwe_text_become_an_exhibitor() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Dołącz do grona wystawców '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .' i zaprezentuj swoją ofertę.';
		} else {
			return 'Join the exhibitors at '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .' and present your offer.';
		}
	}
	add_shortcode('sc_pwe_text_become_an_exhibitor', 'sc_pwe_text_become_an_exhibitor');

	// Sklep
	function sc_pwe_text_store() {
		if (ICL_LANGUAGE_CODE == "pl") {
			return 'Zamów bilety lub pakiety promocyjne związane z '. do_shortcode('[trade_fair_name]') .' '. do_shortcode('[trade_fair_catalog_year]') .' w naszym sklepie online.';
		} else {
			return 'Order tickets or promotional packages related to '. do_shortcode('[trade_fair_name_eng]') .' '. do_shortcode('[trade_fair_catalog_year]') .' in our online store.';
		}
	}
	add_shortcode('sc_pwe_text_store', 'sc_pwe_text_store');

	// Lista zmiennych i odpowiadających im funkcji zwracających wartość
	function get_yoast_custom_shortcodes_map() {
		$lang = ICL_LANGUAGE_CODE;
		return [
			'sc_pwe_trade_fair_year'          => 'show_trade_fair_catalog_year', // %%sc_pwe_trade_fair_year%% || [trade_fair_catalog_year]
			'sc_pwe_trade_fair_desc'          => $lang === 'pl' ? 'show_trade_fair_desc' : 'show_trade_fair_desc_eng', // %%sc_pwe_trade_fair_desc%% || [trade_fair_desc] && [trade_fair_desc_eng]
			'sc_pwe_trade_fair_full_desc'     => 'sc_pwe_trade_fair_full_desc', // %%sc_pwe_trade_fair_full_desc%% || [trade_fair_full_desc]

			// Shortcode'y SEO
			'sc_pwe_text_news'                => 'sc_pwe_text_news', // %%sc_pwe_text_news%% || [sc_pwe_text_news]
			'sc_pwe_text_for_visitors'        => 'sc_pwe_text_for_visitors', // %%sc_pwe_text_for_visitors%% || [sc_pwe_text_for_visitors]
			'sc_pwe_text_for_exhibitors'      => 'sc_pwe_text_for_exhibitors', // %%sc_pwe_text_for_exhibitors%% || [sc_pwe_text_for_exhibitors]
			'sc_pwe_text_add_calendar'        => 'sc_pwe_text_add_calendar', // %%sc_pwe_text_add_calendar%% || [sc_pwe_text_add_calendar]
			'sc_pwe_text_gallery'             => 'sc_pwe_text_gallery', // %%sc_pwe_text_gallery%% || [sc_pwe_text_gallery]
			'sc_pwe_text_org_info'            => 'sc_pwe_text_org_info', // %%sc_pwe_text_org_info%% || [sc_pwe_text_org_info]
			'sc_pwe_text_exh_catalog'         => 'sc_pwe_text_exh_catalog', // %%sc_pwe_text_exh_catalog%% || [sc_pwe_text_exh_catalog]
			'sc_pwe_text_events'              => 'sc_pwe_text_events', // %%sc_pwe_text_events%% || [sc_pwe_text_events]
			'sc_pwe_text_contact'             => 'sc_pwe_text_contact', // %%sc_pwe_text_contact%% || [sc_pwe_text_contact]
			'sc_pwe_text_fair_plan'           => 'sc_pwe_text_fair_plan', // %%sc_pwe_text_fair_plan%% || [sc_pwe_text_fair_plan]
			'sc_pwe_text_registration'        => 'sc_pwe_text_registration', // %%sc_pwe_text_registration%% || [sc_pwe_text_registration]
			'sc_pwe_text_promote_yourself'    => 'sc_pwe_text_promote_yourself', // %%sc_pwe_text_promote_yourself%% || [sc_pwe_text_promote_yourself]
			'sc_pwe_text_become_an_exhibitor' => 'sc_pwe_text_become_an_exhibitor', // %%sc_pwe_text_become_an_exhibitor%% || [sc_pwe_text_become_an_exhibitor]
			'sc_pwe_text_store'               => 'sc_pwe_text_store', // %%sc_pwe_text_store%% || [sc_pwe_text_store]
		];
	}

	// Rejestracja zmiennych Yoast
	add_filter('wpseo_register_extra_replacements', function() {
		$shortcode_map = get_yoast_custom_shortcodes_map();
		$keys = array_map(function($key) {
			return '%%' . $key . '%%';
		}, array_keys($shortcode_map));
		return $keys;
	});

	// Podstawienie wartości do zmiennych
	add_filter('wpseo_replacements', function($replacements) {
		$shortcode_map = get_yoast_custom_shortcodes_map();

		foreach ($shortcode_map as $yoast_key => $callback) {
			if (is_callable($callback)) {
				$value = call_user_func($callback);
				$replacements['%%' . $yoast_key . '%%'] = $value;
			} else {
				$replacements['%%' . $yoast_key . '%%'] = '';
			}
		}

		return $replacements;
	});

	// FOR YOAST SEO END <----------------------------------------------------------------------<


	function enqueue_form_exhibit() {
		$css_file = plugins_url('form_exhibit.css', __FILE__);
		$css_version = filemtime(plugin_dir_path( __FILE__ ) . 'form_exhibit.css');
		wp_enqueue_style('form_exhibit', $css_file, array(), $css_version);
	}

	add_filter('gform_replace_merge_tags', 'GF_shortcodes', 10, 7 );

	function GF_shortcodes($text, $form, $entry, $url_encode, $esc_html, $nl2br, $format) {
		// Define the merge tags and their replacements
		$merge_tags = array(
			'{trade_fair_name}' => show_trade_fair_name(),
			'{trade_fair_name_eng}' => show_trade_fair_name_eng(),
			'{trade_fair_desc}' => show_trade_fair_desc(),
			'{trade_fair_desc_eng}' => show_trade_fair_desc_eng(),
			'{trade_fair_desc_short}' => show_trade_fair_desc_short(),
			'{trade_fair_desc_short_eng}' => show_trade_fair_desc_short_eng(),
			'{trade_fair_datetotimer}' => show_trade_fair_datetotimer(),
			'{trade_fair_enddata}' => show_trade_fair_enddata(),
			'{trade_fair_catalog}' => show_trade_fair_catalog(),
			'{trade_fair_catalog_year}' => show_trade_fair_catalog_year(),
			'{trade_fair_conferance}' => show_trade_fair_conferance(),
			'{trade_fair_conferance_eng}' => show_trade_fair_conferance_eng(),
			'{trade_fair_1stbuildday}' => show_trade_fair_1stbuildday(),
			'{trade_fair_2ndbuildday}' => show_trade_fair_2ndbuildday(),
			'{trade_fair_1stdismantlday}' => show_trade_fair_1stdismantlday(),
			'{trade_fair_2nddismantlday}' => show_trade_fair_2nddismantlday(),
			'{trade_fair_date}' => show_trade_fair_date(),
			'{trade_fair_date_eng}' => show_trade_fair_date_eng(),
			'{trade_fair_accent}' => show_trade_fair_accent(),
			'{trade_fair_edition}' => (isset($form['field']) && isset($entry)) ? show_trade_fair_edition($entry, $form['fields']) : show_trade_fair_edition(),
			'{trade_fair_main2}' => show_trade_fair_main2(),
			'{trade_fair_branzowy}' => show_trade_fair_branzowy(),
			'{trade_fair_branzowy_eng}' => show_trade_fair_branzowy_eng(),
			'{trade_fair_badge}' => show_trade_fair_badge(),
			'{trade_fair_opisbranzy}' => show_trade_fair_opisbranzy(),
			'{trade_fair_opisbranzy_eng}' => show_trade_fair_opisbranzy_eng(),
			'{trade_fair_facebook}' => show_trade_fair_facebook(),
			'{trade_fair_instagram}' => show_trade_fair_instagram(),
			'{trade_fair_linkedin}' => show_trade_fair_linkedin(),
			'{trade_fair_youtube}' => show_trade_fair_youtube(),
			'{trade_fair_domainadress}' => show_trade_fair_domainadress(),
			'{trade_fair_actualyear}' => show_trade_fair_actualyear(),
			'{trade_fair_rejestracja}' => show_trade_fair_rejestracja(),
			'{trade_fair_contact}' => show_trade_fair_contact(),
			'{trade_fair_contact_tech}' => show_trade_fair_contact_tech(),
			'{trade_fair_contact_media}' => show_trade_fair_contact_media(),
			'{trade_fair_lidy}' => show_trade_fair_lidy(),
			'{trade_fair_gf_coder}' => (isset($form['id']) && isset($entry['id'])) ? show_trade_fair_gf_coder($form['id'], $entry['id']) : '',
			'{trade_fair_registration_benefits_pl}' => show_trade_fair_registration_benefits_pl(),
			'{trade_fair_registration_benefits_en}' => show_trade_fair_registration_benefits_en(),
			'{trade_fair_ticket_benefits_pl}' => show_trade_fair_ticket_benefits_pl(),
			'{trade_fair_ticket_benefits_en}' => show_trade_fair_ticket_benefits_en(),
		);

		// Loop through each merge tag and replace it in the text
		foreach ($merge_tags as $tag => $replacement) { 
			if ( strpos($text, $tag) !== false ) { 
				$text = str_replace($tag, $replacement, $text); 
			} 
		}

		return $text;
	}

	add_action( 'admin_enqueue_scripts', 'enqueue_form_exhibit' );
	?>