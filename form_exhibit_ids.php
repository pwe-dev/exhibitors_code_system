<?php
/*
Plugin Name: Form Exhibitors Code System 3.8
Description: Wtyczka umożliwiająca generowanie kodów zaproszeniowych dla wystawców oraz tworzenie 'reflinków'.
Version: 3.8
Author: Szymon Kaluga
Author URI: http://skaluga.pl/
*/

include( plugin_dir_path( __FILE__ ) . 'plugin-update-checker/plugin-update-checker.php');
$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/pwe-dev/exhibitors_code_system',
	__FILE__,
	'exhibitors_code_system'
);
$myUpdateChecker->getVcsApi()->enableReleaseAssets();

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

// create custom plugin settings menu
add_action('admin_menu', 'my_cool_plugin_create_menu');


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
				<div id="col-right" class="postbox-container">
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
		
		add_settings_field("trade_fair_name", "Nazwa Targów<hr><p>Wpisz nazwę targów <br>[trade_fair_name]</p>", "display_trade_fair_name", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_name");

		add_settings_field("trade_fair_datetotimer", "Data targow do licznika<hr><p>Wpisz date targow do licznika<br>[trade_fair_datetotimer]</p>", "display_trade_fair_datetotimer", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_datetotimer");

		add_settings_field("trade_fair_date", "Data Targów<hr><p>Wpisz datę targów <br>[trade_fair_date]</p>", "display_trade_fair_date", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_date");

		add_settings_field("trade_fair_date_eng", "Data Targów (ENG)<hr><p>Wpisz datę targów (ENG)<br>[trade_fair_date_eng]</p>", "display_trade_fair_date_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_date_eng");

		add_settings_field("trade_fair_date_ru", "Data Targów (RU)<hr><p>Wpisz datę targów (RU)<br>[trade_fair_date_ru]</p>", "display_trade_fair_date_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "trade_fair_date_ru");

		add_settings_field("first_day", "Pierwszy dzień targów<hr><p>Wpisz pierwszy dzień targów<br>[first_day]</p>", "display_first_day", "code-checker", "code_checker");      
		register_setting("code_checker", "first_day");

		add_settings_field("second_day", "Drugi dzień targów<hr><p>Wpisz drugi dzień targów<br>[second_day]</p>", "display_second_day", "code-checker", "code_checker");      
		register_setting("code_checker", "second_day");

		add_settings_field("third_day", "Trzeci dzień targów<hr><p>Wpisz trzeci dzień targów<br>[third_day]</p>", "display_third_day", "code-checker", "code_checker");      
		register_setting("code_checker", "third_day");

		add_settings_field("first_day_eng", "Pierwszy dzień targów (ENG)<hr><p>Wpisz pierwszy dzień targów<br>[first_day_eng]</p>", "display_first_day_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "first_day_eng");

		add_settings_field("second_day_eng", "Drugi dzień targów (ENG)<hr><p>Wpisz pierwszy dzień targów<br>[second_day_eng]</p>", "display_second_day_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "second_day_eng");

		add_settings_field("third_day_eng", "Trzeci dzień targów (ENG)<hr><p>Wpisz pierwszy dzień targów<br>[third_day_eng]</p>", "display_third_day_eng", "code-checker", "code_checker");      
		register_setting("code_checker", "third_day_eng");
		
		add_settings_field("first_day_ru", "Pierwszy dzień targów (RU)<hr><p>Wpisz pierwszy dzień targów<br>[first_day_ru]</p>", "display_first_day_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "first_day_ru");

		add_settings_field("second_day_ru", "Drugi dzień targów (RU)<hr><p>Wpisz pierwszy dzień targów<br>[second_day_ru]</p>", "display_second_day_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "second_day_ru");

		add_settings_field("third_day_ru", "Trzeci dzień targów (RU)<hr><p>Wpisz pierwszy dzień targów<br>[third_day_ru]</p>", "display_third_day_ru", "code-checker", "code_checker");      
		register_setting("code_checker", "third_day_ru");

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

	function display_trade_fair_name()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_name" id="trade_fair_name" value="<?php echo get_option('trade_fair_name'); ?>" />
				<p>"np. Warsaw Fleet Expo"</p>
			</div>
        <?php
	}

	function display_trade_fair_date()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_date" id="trade_fair_date" value="<?php echo get_option('trade_fair_date'); ?>" />
				<p>"np. 15-16 grudnia 2020"</p>
			</div>
        <?php
	}

	function display_trade_fair_datetotimer()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_datetotimer" id="trade_fair_datetotimer" value="<?php echo get_option('trade_fair_datetotimer'); ?>" />
				<p>"2020/10/14 (Y:M:D)"</p>
			</div>
        <?php
	}
	function display_trade_fair_date_eng()
    {
        ?>
			<div class="form-field">
				<input type="text" name="trade_fair_date_eng" id="trade_fair_date_eng" value="<?php echo get_option('trade_fair_date_eng'); ?>" />
				<p>"np. December 15-16, 2020"</p>
			</div>
        <?php
	}


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
	function show_trade_fair_name(){
		$result = get_option('trade_fair_name');
		return $result;
	}
	add_shortcode( 'trade_fair_name', 'show_trade_fair_name' );
	
	//datetotimer
	function show_trade_fair_datetotimer(){
		$result = get_option('trade_fair_datetotimer');
		return $result;
	}
	add_shortcode( 'trade_fair_datetotimer', 'show_trade_fair_datetotimer' );

	// Date of the fair
	function show_trade_fair_date(){
		$result = get_option('trade_fair_date');
		return $result;
	}
	add_shortcode( 'trade_fair_date', 'show_trade_fair_date' );

	// Date of the fair ENG
	function show_trade_fair_date_eng(){
		$result = get_option('trade_fair_date_eng');
		return $result;
	}
	add_shortcode( 'trade_fair_date_eng', 'show_trade_fair_date_eng' );

	// Date of the fair RU
	function show_trade_fair_date_ru(){
		$result = get_option('trade_fair_date_ru');
		return $result;
	}
	add_shortcode( 'trade_fair_date_ru', 'show_trade_fair_date_ru' );
	
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