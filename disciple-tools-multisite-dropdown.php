<?php
/**
 * Plugin Name: Disciple Tools - Multisite Dropdown
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-multisite-dropdown
 * Description: Disciple Tools - Multisite Dropdown adds a dropdown list of other sites the user is connected to on a multisite network.
 * Version:  0.2
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-multisite-dropdown
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.5.1
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version 0.2 Version bump and confirmation of Disciple Tools 1.0 compatibility
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
$dt_multisite_dropdown_required_dt_theme_version = '0.31.0';

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

    global $dt_multisite_dropdown_required_dt_theme_version;
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = strpos( $wp_theme->get_template(), "disciple-tools-theme" ) !== false || $wp_theme->name === "Disciple Tools";
    if ( $is_theme_dt && version_compare( $version, $dt_multisite_dropdown_required_dt_theme_version, "<" ) ) {
        add_action( 'admin_notices', 'dt_multisite_dropdown_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
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
        return DT_Multisite_Dropdown::get_instance();
    }
    return false;
}
add_action( 'plugins_loaded', 'dt_multisite_dropdown' );

/**
 * Singleton class for setting up the plugin.
 *
 * @since  0.1
 * @access public
 */
class DT_Multisite_Dropdown {

    /**
     * Declares public variables
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public $token;
    public $version;
    public $dir_path = '';
    public $dir_uri = '';
    public $img_uri = '';
    public $includes_path;

    /**
     * Returns the instance.
     *
     * @since  0.1
     * @access public
     * @return object
     */
    public static function get_instance() {

        static $instance = null;

        if ( is_null( $instance ) ) {
            $instance = new dt_multisite_dropdown();
            $instance->setup();
            $instance->includes();
            $instance->setup_actions();
        }
        return $instance;
    }

    /**
     * Constructor method.
     *
     * @since  0.1
     * @access private
     * @return void
     */
    private function __construct() {

    }



    /**
     * Loads files needed by the plugin.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function includes() {

    }



    /**
     * Sets up globals.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup() {
        // Main plugin directory path and URI.
        $this->token             = 'dt_multisite_dropdown';
        $this->version             = '0.1';
    }

    /**
     * Sets up main plugin actions and filters.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    private function setup_actions() {

        add_action( 'dt_nav_add_post_settings', [ $this, 'network_sites' ] );

        if ( is_admin() ){
            // Check for plugin updates
            if ( ! class_exists( 'Puc_v4_Factory' ) ) {
                require( get_template_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' );
            }
            $hosted_json = "https://raw.githubusercontent.com/DiscipleTools/disciple-tools-multisite-dropdown/master/includes/admin/version-control.json"; // @todo change this url
            Puc_v4_Factory::buildUpdateChecker(
                $hosted_json,
                __FILE__,
                'disciple-tools-multisite-dropdown'
            );

        }

        // Internationalize the text strings used.
        add_action( 'init', array( $this, 'i18n' ), 2 );

        if ( is_admin() ) {
            // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }
    }

    public function network_sites() {
        $user_sites = get_blogs_of_user( get_current_user_id() );
        if ( count( $user_sites ) <= 1 ) {
            return;
        }
        ?>
        <li>
            <button>
                <i class="fi-web" style="color:white; font-size:2rem;"></i>
            </button>
            <ul class="submenu menu vertical">
                <?php
                foreach ( $user_sites as $site ){
                    echo '<li><a href="' . esc_url( $site->siteurl ) . '">'. esc_html( $site->blogname ) .'</a>';
                }
                ?>
            </ul>
        </li>
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
    public static function activation() {

        // Confirm 'Administrator' has 'manage_dt' privilege. This is key in 'remote' configuration when
        // Disciple Tools theme is not installed, otherwise this will already have been installed by the Disciple Tools Theme
        $role = get_role( 'administrator' );
        if ( !empty( $role ) ) {
            $role->add_cap( 'manage_dt' ); // gives access to dt plugin options
        }

    }

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
     * Loads the translation files.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function i18n() {
        load_plugin_textdomain( 'dt_multisite_dropdown', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
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
// end main plugin class

// Register activation hook.
register_activation_hook( __FILE__, [ 'DT_Multisite_Dropdown', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Multisite_Dropdown', 'deactivation' ] );

function dt_multisite_dropdown_hook_admin_notice() {
    global $dt_multisite_dropdown_required_dt_theme_version;
    $wp_theme = wp_get_theme();
    $current_version = $wp_theme->version;
    $message = __( "'Disciple Tools - Multisite Dropdown' plugin requires 'Disciple Tools' theme to work. Please activate 'Disciple Tools' theme or make sure it is latest version.", "dt_multisite_dropdown" );
    if ( $wp_theme->get_template() === "disciple-tools-theme" ){
        $message .= sprintf( esc_html__( 'Current Disciple Tools version: %1$s, required version: %2$s', 'dt_multisite_dropdown' ), esc_html( $current_version ), esc_html( $dt_multisite_dropdown_required_dt_theme_version ) );
    }
    // Check if it's been dismissed...
    if ( ! get_option( 'dismissed-dt-multisite-dropdown', false ) ) { ?>
        <div class="notice notice-error notice-dt-multisite-dropdown is-dismissible" data-notice="dt-multisite-dropdown">
            <p><?php echo esc_html( $message );?></p>
        </div>
        <script>
            jQuery(function($) {
                $( document ).on( 'click', '.notice-dt-multisite-dropdown .notice-dismiss', function () {
                    $.ajax( ajaxurl, {
                        type: 'POST',
                        data: {
                            action: 'dismissed_notice_handler',
                            type: 'dt-multisite-dropdown',
                            security: '<?php echo esc_html( wp_create_nonce( 'wp_rest_dismiss' ) ) ?>'
                        }
                    })
                });
            });
        </script>
    <?php }
}


/**
 * AJAX handler to store the state of dismissible notices.
 */
if ( !function_exists( "dt_hook_ajax_notice_handler" )){
    function dt_hook_ajax_notice_handler(){
        check_ajax_referer( 'wp_rest_dismiss', 'security' );
        if ( isset( $_POST["type"] ) ){
            $type = sanitize_text_field( wp_unslash( $_POST["type"] ) );
            update_option( 'dismissed-' . $type, true );
        }
    }
}
