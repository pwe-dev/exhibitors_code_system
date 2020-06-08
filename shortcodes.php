<?php 
// ====== CODE CHECKERS PAGES ======

// JavaScript Shortcode
function js_hide_check_input() {
    ob_start();
    if(!empty($_GET["input_value"])){ ?>
    <style>
        .dummy, .row-breadcrumb {
            display: none !important;
        }
    </style>
        <script type="text/javascript"> 
            document.querySelector("li.invitation_code div.ginput_container input").value = '<?= $_GET["input_value"] ?>';
            document.querySelector("li.invitation_code").style.display = 'none';
        </script>
    <?php }
    return ob_get_clean();
}
add_shortcode( 'js_hide_check', 'js_hide_check_input' );

// Check Form Shortcode
function form_shortcode() {
    ob_start();  ?>
    <style>
        .container.relative.text-center.form-check-container{
            margin-top: 70px;
        }
        #check_user {
            height: 164px;
            width: 300px;
            position: relative;
            left: calc(50% - 150px);
            border-radius: 10px;
            border-color: transparent;
            margin-top: 30px;
             text-align:center;
        }
        #check_user > input{
            margin-top: 22%;
            width: 95%;
            margin-left: auto;
            margin-right: auto;
            margin-bottom: 20px;
            -webkit-box-shadow: 5px 5px 10px 0px rgba(221,221,221,1);
            -moz-box-shadow: 5px 5px 10px 0px rgba(221,221,221,1);
            box-shadow: 5px 5px 10px 0px rgba(221,221,221,1);
            border: solid 1px #ccc;
        }
    </style>
        <div class="container relative text-center form-check-container">
        <h1><?php echo get_option('h1_heading'); ?></h1>
        <h3><?php echo get_option('h3_heading'); ?></h3>
            <form id="check_user" method="GET" action="">
                <input style="text-transform: uppercase;" type="text" id="input_value" name="input_value" />
                <button type="submit" class="btn btn-success"><?php echo get_option('button_text'); ?></button>
            </form>
        </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'form_check', 'form_shortcode' );

// include PHP file
function PHP_Include($params = array()) {
    extract(shortcode_atts(array(
        'file' => 'default'
    ), $params));
    ob_start();
    include(get_theme_root() . '/' . get_template() . "/$file.php");
    return ob_get_clean();
}
// register shortcode
add_shortcode('phpinclude', 'PHP_Include');

function error_shortcode_code_not_in() {
    ob_start();
    echo '<center><h4>'.  get_option('h2_heading', 'Błędny kod, spróbuj ponownie') .'</h4></center>';
    return ob_get_clean();
}
// register shortcode
add_shortcode('error_code_not_in', 'error_shortcode_code_not_in');

function error_shortcode_code_multiple() {
    ob_start();
    echo '<center><h4>Podany kod został już użyty maksymalną liczbę razy</h4></center>';
    return ob_get_clean();
}
// register shortcode
add_shortcode('error_code_multiple', 'error_shortcode_code_multiple');

function wrong_code_shortcode() {
    ob_start();
    echo '<center><h4>'. get_option('h2_heading' , 'Błędny kod, spróbuj ponownie').'</h4></center>';
    return ob_get_clean();
}
// register shortcode
add_shortcode('wrong_code', 'wrong_code_shortcode');

// ====== CODE MAKER PAGE ======

function javascript_shortcode() {
    ob_start();
    // $form_id = mysqli_real_escape_string ($form_id);
    $form_id = get_option('form_id'); //FORM PREFIX
    $code_prefix = get_option('code_prefix'); // PASSWORD PREFIX
    $query = "SELECT * FROM `wp_gf_entry` WHERE `form_id` = '$form_id'";
    require_once(ABSPATH . 'wp-config.php');
    $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($result = mysqli_query($connection, $query)) {
        $user_number = mysqli_num_rows($result) + 1;
        mysqli_free_result($result);
    } ?>
              <script type="text/javascript">
                document.querySelector("li.code div.ginput_container input").value = '<?= $code_prefix.$user_number; ?>';
                document.querySelector("li.code").style.display = 'none';
            </script>
    <?php 
    return ob_get_clean();
}

add_shortcode( 'javascript', 'javascript_shortcode' );

// FILTER CONTENT
function add_additional_js_content($content) {
    $original = $content;
    $content .= $original;
    $content .= do_shortcode('[javascript]');
    return $content;
    }

// ======== HELPER FUNCTIONS ==========
// FILTER CONTENT
function prepend_user_form($content) {
    $original = $content;
    $content .= $original;
    $content .= do_shortcode('[gravityform id="'.get_option('form_user_id').'" title="false" description="false" ajax="false"]');
    $content .= do_shortcode('[js_hide_check]');
    return $content;
    }

// FILTER CONTENT
function prepend_vip_form($content) {
    $original = $content;
    $content .= $original;
    $content .= do_shortcode('[gravityform id="'.get_option('form_vip_id').'" title="false" description="false" ajax="false"]');
    $content .= do_shortcode('[js_hide_check]');
    return $content;
    }


?>