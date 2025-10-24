<?php
/**
 * Uninstall script for WooOrder Status Manager
 *
 * @package WooOrderStatusManager
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include installation class
require_once plugin_dir_path(__FILE__) . 'includes/class-woosm-install.php';

// Run uninstall
WOOSM_Install::uninstall();
