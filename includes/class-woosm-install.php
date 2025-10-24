<?php
/**
 * Installation related functions and actions
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Installation Class
 */
class WOOSM_Install {
    
    /**
     * Install plugin
     */
    public static function install() {
        global $wpdb;
        
        // Create custom statuses table
        $table_name = $wpdb->prefix . 'woosm_custom_statuses';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            slug varchar(50) NOT NULL,
            color varchar(7) DEFAULT '#0073aa',
            icon varchar(50) DEFAULT '',
            email_template longtext,
            visibility enum('admin','customer','both') DEFAULT 'both',
            workflow_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Create workflow rules table
        $workflow_table = $wpdb->prefix . 'woosm_workflow_rules';
        
        $workflow_sql = "CREATE TABLE $workflow_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            from_status varchar(50) NOT NULL,
            to_status varchar(50) NOT NULL,
            trigger_type enum('manual','automatic','payment','stock') DEFAULT 'manual',
            trigger_condition varchar(255) DEFAULT '',
            email_notification tinyint(1) DEFAULT 0,
            email_template longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY status_flow (from_status, to_status)
        ) $charset_collate;";
        
        dbDelta($workflow_sql);
        
        // Create order status history table
        $history_table = $wpdb->prefix . 'woosm_order_status_history';
        
        $history_sql = "CREATE TABLE $history_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id bigint(20) NOT NULL,
            from_status varchar(50),
            to_status varchar(50) NOT NULL,
            changed_by bigint(20),
            change_reason varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY to_status (to_status)
        ) $charset_collate;";
        
        dbDelta($history_sql);
        
        // Insert default custom statuses
        self::insert_default_statuses();
        
        // Set plugin version
        update_option('woosm_version', WOOSM_VERSION);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Insert default custom statuses
     */
    private static function insert_default_statuses() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_custom_statuses';
        
        $default_statuses = array(
            array(
                'name' => 'Packed',
                'slug' => 'packed',
                'color' => '#ff9500',
                'icon' => 'fas fa-box',
                'visibility' => 'both',
                'workflow_order' => 3
            ),
            array(
                'name' => 'Out for Delivery',
                'slug' => 'out-for-delivery',
                'color' => '#00a32a',
                'icon' => 'fas fa-shipping-fast',
                'visibility' => 'both',
                'workflow_order' => 4
            ),
            array(
                'name' => 'Ready for Pickup',
                'slug' => 'ready-for-pickup',
                'color' => '#8c8f94',
                'icon' => 'fas fa-hand-holding',
                'visibility' => 'both',
                'workflow_order' => 5
            )
        );
        
        foreach ($default_statuses as $status) {
            $wpdb->insert($table_name, $status);
        }
    }
    
    /**
     * Deactivate plugin
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Uninstall plugin
     */
    public static function uninstall() {
        global $wpdb;
        
        // Drop tables
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}woosm_custom_statuses");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}woosm_workflow_rules");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}woosm_order_status_history");
        
        // Delete options
        delete_option('woosm_version');
        delete_option('woosm_settings');
        
        // Remove order meta
        $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_woosm_%'");
    }
}
