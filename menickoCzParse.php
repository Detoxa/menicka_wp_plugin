<?php
/*
Plugin Name: Menicko CZ Parse
Plugin URI: http://www.safarik.dev/menicka-cz-parse
Description: Parse HTML from menicko.cz
Version: 1.0
Author: Vit Safarik
Author URI: https://www.safarik.dev
Text Domain: menickoczparse
*/

// Make sure we don't expose any info if called directly
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define('MENICKOCZPARSE_MENU_VERSION', '4.1.1');
define('MENICKOCZPARSE__MINIMUM_WP_VERSION', '4.7');
define('MENICKOCZPARSE__PLUGIN_DIR', plugin_dir_path(__FILE__));



/**
 * Registers a text field setting for Wordpress 4.7 and higher.
 **/
function register_menickoczparse_setting()
{
    $args = array(
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default' => NULL,
    );
    register_setting('menickoczparse_options_group', 'menickoczparse_option_url', $args);
}
add_action('admin_init', 'register_menickoczparse_setting');

add_action('admin_menu', 'add_menickoczparse_custom_options');

function add_menickoczparse_custom_options()
{
    add_options_page('MenickoCzParse', 'MenickoCzParse - Options', 'manage_options', 'menickoczparse_option_url', 'menickoczparse_custom_options');
}

function menickoczparse_custom_options()
{

    ?>
    <div class="wrap">
        <h2>Vložte URL vaší restaurace z menicka.cz</h2>
        <form method="post" action="options.php">
            <?php settings_fields('menickoczparse_options_group'); ?>
            <?php do_settings_sections('menickoczparse_options_group'); ?>
            <input type="text" name="menickoczparse_option_url" size="50" value="<?php echo esc_url(get_option('menickoczparse_option_url')); ?>" />
            <p><?php submit_button(); ?></p>
            <p>ShortCode: [menicko] -> vložte v editoru, tam kde potřebujete! :)</p>
            <p>Verze pluginu:<?php echo MENICKOCZPARSE_MENU_VERSION;?></p>
        </form>
    </div>
<?php
}

// Shortcode parse HTML from menicka.cz
function menickoczparse()
{
    
    if ( false === ( $htmlPart = get_transient( 'menickoczparse_transient' ) ) ) {

    libxml_use_internal_errors(true);

    $data = file_get_contents(esc_url(get_option('menickoczparse_option_url')));
    $dataWin = iconv('WINDOWS-1250', 'UTF-8', $data);

    $dom = new DOMDocument();
    $dom->loadHtml(mb_convert_encoding($dataWin, 'HTML-ENTITIES', 'UTF-8'));
    $finder = new DomXPath($dom);

    $classToFind = "menicka";
    $byClass = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' " . $classToFind . " ')]");

    $htmlPart = $dom->saveHtml($byClass->item(0));
    $htmlPart .= $dom->saveHtml($byClass->item(1));
    $htmlPart .= $dom->saveHtml($byClass->item(2));
    $htmlPart .= $dom->saveHtml($byClass->item(3));
    $htmlPart .= $dom->saveHtml($byClass->item(4));
    $htmlPart .= $dom->saveHtml($byClass->item(5));

    set_transient( 'menickoczparse_transient', $htmlPart, 12 * HOUR_IN_SECONDS );

        return $htmlPart;

    } else {

        return $htmlPart = get_transient( 'menickoczparse_transient' );

    }
}

add_shortcode('menicko', 'menickoczparse');

register_deactivation_hook( __FILE__, 'menickoczpars_deactivate' );

function menickoczpars_deactivate() {
    delete_option('menickoczparse_option_url');
}

