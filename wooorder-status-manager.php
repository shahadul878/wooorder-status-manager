<?php
/**
 * Plugin Name: WooOrder Status Manager
 * Plugin URI: https://github.com/shahadul878/wooorder-status-manager
 * Description: Create and manage custom order statuses for WooCommerce with workflow automation and customer tracking.
 * Version: 1.0.0
 * Author: H M Shahadul Islam
 * Author URI: https://github.com/shahadul878
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wooorder-status-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.5
 * HPOS Compatible: Yes
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WOOSM_VERSION', '1.0.0');
define('WOOSM_PLUGIN_FILE', __FILE__);
define('WOOSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WOOSM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOOSM_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'woosm_woocommerce_missing_notice');
    return;
}

/**
 * WooCommerce missing notice
 */
function woosm_woocommerce_missing_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php esc_html_e('WooOrder Status Manager requires WooCommerce to be installed and active.', 'wooorder-status-manager'); ?></p>
    </div>
    <?php
}

/**
 * Main plugin class
 */
class WooOrderStatusManager {
    
    /**
     * Single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Main instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Define constants
     */
    private function define_constants() {
        if (!defined('WOOSM_ABSPATH')) {
            define('WOOSM_ABSPATH', dirname(WOOSM_PLUGIN_FILE) . '/');
        }
        if (!defined('WOOSM_PLUGIN_BASENAME')) {
            define('WOOSM_PLUGIN_BASENAME', plugin_basename(WOOSM_PLUGIN_FILE));
        }
        if (!defined('WOOSM_VERSION')) {
            define('WOOSM_VERSION', '1.0.0');
        }
    }
    
    /**
     * Include required files
     */
    public function includes() {
        // Core classes
        include_once WOOSM_ABSPATH . 'includes/class-woosm-install.php';
        include_once WOOSM_ABSPATH . 'includes/class-woosm-status-manager.php';
        include_once WOOSM_ABSPATH . 'includes/class-woosm-admin.php';
        include_once WOOSM_ABSPATH . 'includes/class-woosm-frontend.php';
        include_once WOOSM_ABSPATH . 'includes/class-woosm-ajax.php';
        include_once WOOSM_ABSPATH . 'includes/class-woosm-email.php';
        include_once WOOSM_ABSPATH . 'includes/class-woosm-workflow.php';
    }
    
    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('plugins_loaded', array($this, 'on_plugins_loaded'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array('WOOSM_Install', 'install'));
        register_deactivation_hook(__FILE__, array('WOOSM_Install', 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Load plugin text domain
        $this->load_plugin_textdomain();
        
        // Initialize classes
        WOOSM_Status_Manager::instance();
        WOOSM_Admin::instance();
        WOOSM_Frontend::instance();
        WOOSM_Ajax::instance();
        WOOSM_Email::instance();
        WOOSM_Workflow::instance();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        $locale = apply_filters('plugin_locale', get_locale(), 'wooorder-status-manager');
        load_textdomain('wooorder-status-manager', WP_LANG_DIR . '/wooorder-status-manager/wooorder-status-manager-' . $locale . '.mo');
        load_plugin_textdomain('wooorder-status-manager', false, plugin_basename(dirname(__FILE__)) . '/languages');
    }
    
    /**
     * On plugins loaded
     */
    public function on_plugins_loaded() {
        // Check for Pro version
        if (class_exists('WooOrderStatusManagerPro')) {
            add_action('admin_notices', array($this, 'pro_version_notice'));
        }
        
        // Declare HPOS compatibility
        add_action('before_woocommerce_init', array($this, 'declare_hpos_compatibility'));
    }
    
    /**
     * Declare HPOS compatibility
     */
    public function declare_hpos_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', WOOSM_PLUGIN_FILE, true);
        }
    }
    
    /**
     * Pro version notice
     */
    public function pro_version_notice() {
        ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e('WooOrder Status Manager Pro is active. Free version features are disabled.', 'wooorder-status-manager'); ?></p>
        </div>
        <?php
    }
}

/**
 * Returns the main instance of WooOrderStatusManager
 */
function WOOSM() {
    return WooOrderStatusManager::instance();
}

// Initialize the plugin
WOOSM();
