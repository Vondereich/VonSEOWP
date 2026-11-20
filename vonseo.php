<?php
/**
 * Plugin Name:       VonSEO
 * Description:       Lightweight SEO toolkit: metadata, schema, XML sitemaps, breadcrumbs, table of contents and local content analysis.
 * Version:           2.5.3
 * Requires at least: 6.0
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            Vondereich
 * Author URI:        https://www.facebook.com/kurama87
 * License:           GPLv3 or later
 * Text Domain:       vonseo
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

/**
 * WordPress Stubs for IDE (False Positives Fix)
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
if (false) {
    define('DOING_AUTOSAVE', false);
    require_once '_wp_stubs.php';
}

/**
 * PHP Version Check.
 */
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . esc_html__('VonSEO requires PHP 7.4 or higher to function properly. Please upgrade your PHP version.', 'vonseo') . '</p></div>';
    });
    return;
}

define('VONSEOWP_VERSION', '2.5.3');
define('VONSEOWP_PATH', plugin_dir_path(__FILE__));
define('VONSEOWP_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class.
 */
class VonSEOWP {
    /** @var VonSEOWP|null */
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('init', array($this, 'maybe_flush_rewrites'), 999);
        add_action('init', array($this, 'load_bundled_translations'), 0);
        register_activation_hook(__FILE__, array($this, 'activation'));
        register_deactivation_hook(__FILE__, array($this, 'deactivation'));
    }

    public function activation() {
        // Flush rewrite rules for Sitemap.
        if (!get_option('vonseowp_flush_sitemap')) {
            add_option('vonseowp_flush_sitemap', true);
        }
    }

    public function deactivation(): void {
        wp_unschedule_hook('vonseowp_indexnow_submit');
    }

    public function init() {
        // Initialize components.
        $admin = new VonSEOWP_Admin();
        new VonSEOWP_Settings_Transfer($admin);
        new VonSEOWP_Meta_Box();
        new VonSEOWP_Taxonomies();
        new VonSEOWP_Frontend();
        new VonSEOWP_Sitemap();
        new VonSEOWP_Redirects();
        new VonSEOWP_Image_SEO();
        new VonSEOWP_IndexNow();
        new VonSEOWP_LLM();
        new VonSEOWP_Cleanup();
        new VonSEOWP_Breadcrumbs();
        new VonSEOWP_Columns();
        new VonSEOWP_RSS();
        new VonSEOWP_TOC();
        new VonSEOWP_Site_Audit();


        // Only load Competitors module if required extensions are present.
        if (class_exists('DOMDocument') && class_exists('DOMXPath')) {
             new VonSEOWP_Competitors(); 
        }

    }

    /**
     * WordPress loads translate.wordpress.org language packs from WP_LANG_DIR on its own. Copies installed
     * from a release ZIP can also ship translations in /languages; load those only when no language pack exists.
     */
    public function load_bundled_translations(): void {
        $locale = function_exists('determine_locale') ? determine_locale() : get_locale();
        if (!is_string($locale) || !preg_match('/^[A-Za-z]{2,3}(?:_[A-Za-z0-9]+)*$/', $locale)) return;
        $pack = WP_LANG_DIR . '/plugins/vonseo-' . $locale;
        if (is_readable($pack . '.mo') || is_readable($pack . '.l10n.php')) return;
        $bundled = VONSEOWP_PATH . 'languages/vonseo-' . $locale . '.mo';
        if (is_readable($bundled)) {
            load_textdomain('vonseo', $bundled, $locale);
        }
    }

    public function maybe_flush_rewrites(): void {
        // Flush on update/init if needed.
        $current_version = get_option('vonseowp_version');
        if (VONSEOWP_VERSION !== $current_version) {
            flush_rewrite_rules();
            update_option('vonseowp_version', VONSEOWP_VERSION);
        }

        if (get_option('vonseowp_flush_sitemap')) {
            flush_rewrite_rules();
            delete_option('vonseowp_flush_sitemap');
        }
    }

    public static function get_option(string $key, $default = '') {
        $options = get_option('vonseowp_settings', array());
        if (!is_array($options)) {
            $options = array();
        }
        return isset($options[$key]) ? $options[$key] : $default;
    }
}

// Include modules (Moved below class definition for architectural consistency).
require_once VONSEOWP_PATH . 'includes/class-vonseowp-content-types.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-taxonomies.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-admin.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-settings-transfer.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-meta-box.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-frontend.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-sitemap.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-redirects.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-image-seo.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-competitors.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-indexnow.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-cleanup.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-llm.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-breadcrumbs.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-columns.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-rss.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-toc.php';
require_once VONSEOWP_PATH . 'includes/class-vonseowp-site-audit.php';


// --- Theme Helpers ---

/**
 * Get breadcrumbs markup.
 */
function vonseowp_get_breadcrumbs(array $args = array()): string {
    if (!class_exists('VonSEOWP_Breadcrumbs')) {
        return '';
    }
    return VonSEOWP_Breadcrumbs::get_markup($args);
}

/**
 * Display breadcrumbs.
 */
function vonseowp_breadcrumbs(array $args = array()): void {
    $allowed_html = array(
        'nav' => array(
            'class'      => array(),
            'aria-label' => array(),
        ),
        'a' => array(
            'class' => array(),
            'href'  => array(),
        ),
        'span' => array(
            'class'        => array(),
            'aria-current' => array(),
            'aria-hidden'  => array(),
        ),
    );
    echo wp_kses(vonseowp_get_breadcrumbs($args), $allowed_html);
}

// Aliases for backward compatibility.
if (!function_exists('vonseo_get_breadcrumbs')) {
    function vonseo_get_breadcrumbs(array $args = array()): string { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
        return vonseowp_get_breadcrumbs($args);
    }
}

if (!function_exists('vonseo_breadcrumbs')) {
    function vonseo_breadcrumbs(array $args = array()): void { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
        vonseowp_breadcrumbs($args);
    }
}

// Initialize the plugin.
VonSEOWP::instance();
