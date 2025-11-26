<?php
/**
 * Plugin Name: CF7 File URL Replacer
 * Plugin URI: https://wordpress.org/plugins/cf7-file-url-replacer/
 * Description: Automatically replaces Contact Form 7 file field tags with clickable download URLs in email notifications. Files are permanently saved to WordPress Media Library.
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Bonny Elangbam
 * Author URI: https://profile.wordpress.org/bonnyelangbam
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: cf7-file-url-replacer
 * Domain Path: /languages
 * 
 * @package CF7_File_URL_Replacer
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Current plugin version.
 */
define('CF7_FILE_URL_REPLACER_VERSION', '1.0.0');

/**
 * Plugin base name.
 */
define('CF7_FILE_URL_REPLACER_BASENAME', plugin_basename(__FILE__));

/**
 * Plugin directory path.
 */
define('CF7_FILE_URL_REPLACER_PATH', plugin_dir_path(__FILE__));

/**
 * Plugin directory URL.
 */
define('CF7_FILE_URL_REPLACER_URL', plugin_dir_url(__FILE__));

/**
 * Check if Contact Form 7 is active
 */
function cf7_fur_check_cf7_active() {
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php') && !function_exists('wpcf7')) {
        add_action('admin_notices', 'cf7_fur_cf7_missing_notice');
        deactivate_plugins(CF7_FILE_URL_REPLACER_BASENAME);
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- This is checking activation status, not processing form data
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
}
add_action('admin_init', 'cf7_fur_check_cf7_active');

/**
 * Admin notice if Contact Form 7 is not active
 */
function cf7_fur_cf7_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('CF7 File URL Replacer requires Contact Form 7 to be installed and activated.', 'cf7-file-url-replacer'); ?></p>
    </div>
    <?php
}

/**
 * Include the main plugin class.
 */
require_once CF7_FILE_URL_REPLACER_PATH . 'includes/class-cf7-file-url-replacer.php';

/**
 * Initialize the plugin.
 */
function cf7_fur_init() {
    $plugin = new CF7_File_URL_Replacer();
    $plugin->init();
}
add_action('plugins_loaded', 'cf7_fur_init');

/**
 * Activation hook.
 */
function cf7_fur_activate() {
    // Check minimum PHP version
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        deactivate_plugins(CF7_FILE_URL_REPLACER_BASENAME);
        wp_die(
            esc_html__('CF7 File URL Replacer requires PHP 7.4 or higher.', 'cf7-file-url-replacer'),
            esc_html__('Plugin Activation Error', 'cf7-file-url-replacer'),
            array('back_link' => true)
        );
    }
    
    // Check if Contact Form 7 is active
    if (!is_plugin_active('contact-form-7/wp-contact-form-7.php') && !function_exists('wpcf7')) {
        deactivate_plugins(CF7_FILE_URL_REPLACER_BASENAME);
        wp_die(
            esc_html__('CF7 File URL Replacer requires Contact Form 7 to be installed and activated.', 'cf7-file-url-replacer'),
            esc_html__('Plugin Activation Error', 'cf7-file-url-replacer'),
            array('back_link' => true)
        );
    }
}
register_activation_hook(__FILE__, 'cf7_fur_activate');

/**
 * Deactivation hook.
 */
function cf7_fur_deactivate() {
    // Cleanup if needed
}
register_deactivation_hook(__FILE__, 'cf7_fur_deactivate');
