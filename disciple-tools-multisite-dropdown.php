<?php
/**
 *Plugin Name: Disciple.Tools - Multisite Dropdown
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-multisite-dropdown
 * Description: Disciple.Tools - Multisite Dropdown adds a dropdown list of other sites the user is connected to on a multisite network.
 * Version:  1.7
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-multisite-dropdown
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 6.2
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version 0.2 Version bump and confirmation of Disciple.Tools 1.0 compatibility
 */

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Gets the instance of the `DT_Multisite_Dropdown` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_multisite_dropdown() {

    // must be multisite
    if ( ! is_multisite() ) {
        return false;
    }

    $dt_multisite_dropdown_required_dt_theme_version = '1.0';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = class_exists( "Disciple_Tools" );
    if ( $is_theme_dt && version_compare( $version, $dt_multisite_dropdown_required_dt_theme_version, "<" ) ) {
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }
    /*
     * Don't load the plugin on every rest request. Only those with the 'sample' namespace
     */
    $is_rest = dt_is_rest();
    if ( ! $is_rest ){
        return DT_Multisite_Dropdown::instance();
    }
    return false;
}
add_action( 'after_setup_theme', 'dt_multisite_dropdown' );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Multisite_Dropdown {

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    private function __construct() {

        add_action( 'dt_nav_add_post_settings', [ $this, 'network_sites' ] );

        if ( is_admin() ) {
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }
    }

    public function network_sites() {
        $user_sites = get_blogs_of_user( get_current_user_id() );

        /**
         * Filter to modify default links or add additional links
         */
        $user_sites = apply_filters( 'dt_multisite_dropdown_sites', $user_sites );

        if ( count( $user_sites ) <= 1 ) {
            return;
        }
        ?>
        <li>
            <button>
                <i class="fi-web" style="color:white; font-size:2rem;"></i>
            </button>
            <ul class="submenu menu vertical" id="multisite-dropdown-ul" style="overflow-x: hidden; overflow-y: auto;">
                <?php
                foreach ( $user_sites as $site ){
                    echo '<li><a href="' . esc_url( trailingslashit( $site->siteurl ) ) . '">'. esc_html( $site->blogname ) .'</a></li>';
                }
                ?>
            </ul>
        </li>
        <?php
        $height_needed = count( $user_sites ) * 50 + 100;
        ?>
        <script>
            if ( window.innerHeight < <?php echo esc_html( $height_needed ) ?> ) {
                jQuery(document).ready(function(){
                    jQuery('#multisite-dropdown-ul').css('height', window.innerHeight - 50)
                })
            }
        </script>
        <?php
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     *
     * @access  public
     * @param   array       $links_array            An array of the plugin's metadata
     * @param   string      $plugin_file_name       Path to the plugin file
     * @param   array       $plugin_data            An array of plugin data
     * @param   string      $status                 Status of the plugin
     * @return  array       $links_array
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>';
            $links_array[] = '<a href="https://github.com/DiscipleTools/disciple-tools-multisite-dropdown">Github Project</a>';

            // add other links here
        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {}

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        delete_option( 'dismissed-dt-multisite-dropdown' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'dt_multisite_dropdown';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( "dt_multisite_dropdown::" . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}


/**
 * Check for plugin updates even when the active theme is not Disciple.Tools
 *
 * Below is the publicly hosted .json file that carries the version information. This file can be hosted
 * anywhere as long as it is publicly accessible. You can download the version file listed below and use it as
 * a template.
 * Also, see the instructions for version updating to understand the steps involved.
 * @see https://github.com/DiscipleTools/disciple-tools-version-control/wiki/How-to-Update-the-Starter-Plugin
 */
add_action( 'plugins_loaded', function (){
    if ( is_admin() && !( is_multisite() && class_exists( "DT_Multisite" ) ) || wp_doing_cron() ){
        if ( ! class_exists( 'Puc_v4_Factory' ) ) {
            // find the Disciple.Tools theme and load the plugin update checker.
            foreach ( wp_get_themes() as $theme ){
                if ( $theme->get( 'TextDomain' ) === "disciple_tools" && file_exists( $theme->get_stylesheet_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' ) ){
                    require( $theme->get_stylesheet_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
                }
            }
        }
        if ( class_exists( 'Puc_v4_Factory' ) ){
            $hosted_json = "https://raw.githubusercontent.com/DiscipleTools/disciple-tools-multisite-dropdown/master/version-control.json";
            Puc_v4_Factory::buildUpdateChecker(
                $hosted_json,
                __FILE__,
                'disciple-tools-multisite-dropdown'
            );

        }
    }
} );
