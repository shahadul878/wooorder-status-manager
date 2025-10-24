<?php
/**
 * Status Manager Class
 * Handles custom status registration and management
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Status Manager Class
 */
class WOOSM_Status_Manager {
    
    /**
     * Single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Registered custom statuses
     */
    private $custom_statuses = array();
    
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
        add_action('init', array($this, 'register_custom_statuses'), 10);
        add_filter('wc_order_statuses', array($this, 'add_custom_statuses_to_wc'));
        add_action('woocommerce_order_status_changed', array($this, 'on_status_changed'), 10, 3);
        add_filter('woocommerce_reports_order_statuses', array($this, 'include_custom_statuses_in_reports'));
        add_filter('woocommerce_order_is_paid_statuses', array($this, 'custom_paid_statuses'));
        add_filter('woocommerce_order_is_download_permitted', array($this, 'custom_download_permitted_statuses'), 10, 2);
        
        // HPOS compatibility - only register post statuses if HPOS is not enabled
        if (!class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') || 
            !\Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            add_action('init', array($this, 'register_post_statuses'), 11);
        }
    }
    
    /**
     * Register custom statuses
     */
    public function register_custom_statuses() {
        $statuses = $this->get_custom_statuses();
        
        foreach ($statuses as $status) {
            $this->register_status($status);
        }
    }
    
    /**
     * Register post statuses (legacy support for non-HPOS environments)
     */
    public function register_post_statuses() {
        $statuses = $this->get_custom_statuses();
        
        foreach ($statuses as $status) {
            $this->register_post_status($status);
        }
    }
    
    /**
     * Register a single status
     */
    private function register_status($status) {
        $status_key = 'wc-' . $status['slug'];
        
        // For HPOS compatibility, we don't register post statuses
        // Instead, we just add them to WooCommerce's order statuses
        $this->custom_statuses[$status_key] = $status;
    }
    
    /**
     * Register a single post status (legacy support)
     */
    private function register_post_status($status) {
        $status_key = 'wc-' . $status['slug'];
        
        register_post_status($status_key, array(
            'label'                     => $status['name'],
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                $status['name'] . ' <span class="count">(%s)</span>',
                $status['name'] . ' <span class="count">(%s)</span>',
                'wooorder-status-manager'
            )
        ));
        
        $this->custom_statuses[$status_key] = $status;
    }
    
    /**
     * Add custom statuses to WooCommerce
     */
    public function add_custom_statuses_to_wc($order_statuses) {
        $custom_statuses = $this->get_custom_statuses();
        
        foreach ($custom_statuses as $status) {
            $status_key = 'wc-' . $status['slug'];
            $order_statuses[$status_key] = $status['name'];
        }
        
        return $order_statuses;
    }
    
    /**
     * Handle status change
     */
    public function on_status_changed($order_id, $old_status, $new_status) {
        // Log status change
        $this->log_status_change($order_id, $old_status, $new_status);
        
        // Trigger workflow automation
        do_action('woosm_status_changed', $order_id, $old_status, $new_status);
        
        // Send email notification if enabled
        $this->maybe_send_email_notification($order_id, $old_status, $new_status);
    }
    
    /**
     * Log status change
     */
    private function log_status_change($order_id, $old_status, $new_status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_order_status_history';
        
        $wpdb->insert(
            $table_name,
            array(
                'order_id' => $order_id,
                'from_status' => $old_status,
                'to_status' => $new_status,
                'changed_by' => get_current_user_id(),
                'change_reason' => 'Status changed via admin'
            )
        );
    }
    
    /**
     * Maybe send email notification
     */
    private function maybe_send_email_notification($order_id, $old_status, $new_status) {
        $status = $this->get_status_by_slug(str_replace('wc-', '', $new_status));
        
        if ($status && $status['email_template']) {
            $order = wc_get_order($order_id);
            
            if ($order) {
                $email_manager = WOOSM_Email::instance();
                $email_manager->send_status_notification($order, $status);
            }
        }
    }
    
    /**
     * Get custom statuses from database
     */
    public function get_custom_statuses() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_custom_statuses';
        $statuses = $wpdb->get_results("SELECT * FROM $table_name ORDER BY workflow_order ASC", ARRAY_A);
        
        return $statuses ? $statuses : array();
    }
    
    /**
     * Get status by slug
     */
    public function get_status_by_slug($slug) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_custom_statuses';
        $status = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE slug = %s", $slug), ARRAY_A);
        
        return $status;
    }
    
    /**
     * Create new custom status
     */
    public function create_status($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_custom_statuses';
        
        $insert_data = array(
            'name' => sanitize_text_field($data['name']),
            'slug' => sanitize_title($data['slug']),
            'color' => sanitize_hex_color($data['color']),
            'icon' => sanitize_text_field($data['icon']),
            'visibility' => sanitize_text_field($data['visibility']),
            'workflow_order' => intval($data['workflow_order'])
        );
        
        if (isset($data['email_template'])) {
            $insert_data['email_template'] = wp_kses_post($data['email_template']);
        }
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result) {
            // Flush rewrite rules to register new status
            flush_rewrite_rules();
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update custom status
     */
    public function update_status($id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_custom_statuses';
        
        $update_data = array();
        
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['color'])) {
            $update_data['color'] = sanitize_hex_color($data['color']);
        }
        if (isset($data['icon'])) {
            $update_data['icon'] = sanitize_text_field($data['icon']);
        }
        if (isset($data['visibility'])) {
            $update_data['visibility'] = sanitize_text_field($data['visibility']);
        }
        if (isset($data['workflow_order'])) {
            $update_data['workflow_order'] = intval($data['workflow_order']);
        }
        if (isset($data['email_template'])) {
            $update_data['email_template'] = wp_kses_post($data['email_template']);
        }
        
        $result = $wpdb->update(
            $table_name,
            $update_data,
            array('id' => $id),
            null,
            array('%d')
        );
        
        if ($result !== false) {
            flush_rewrite_rules();
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete custom status
     */
    public function delete_status($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_custom_statuses';
        $status = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
        
        if ($status) {
            // Update orders with this status to pending
            $this->update_orders_with_deleted_status($status['slug']);
            
            // Delete the status
            $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));
            
            if ($result) {
                flush_rewrite_rules();
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Update orders with deleted status
     */
    private function update_orders_with_deleted_status($status_slug) {
        // HPOS compatibility - use OrderUtil for order queries
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
            \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
            // Use HPOS-compatible order query
            $orders = wc_get_orders(array(
                'status' => 'wc-' . $status_slug,
                'limit' => -1,
                'return' => 'ids'
            ));
        } else {
            // Legacy query for non-HPOS environments
            $orders = wc_get_orders(array(
                'status' => 'wc-' . $status_slug,
                'limit' => -1,
                'return' => 'ids'
            ));
        }
        
        foreach ($orders as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status('pending', __('Status deleted, moved to pending', 'wooorder-status-manager'));
            }
        }
    }
    
    /**
     * Include custom statuses in reports
     */
    public function include_custom_statuses_in_reports($statuses) {
        $custom_statuses = $this->get_custom_statuses();
        
        foreach ($custom_statuses as $status) {
            $statuses[] = 'wc-' . $status['slug'];
        }
        
        return $statuses;
    }
    
    /**
     * Custom paid statuses
     */
    public function custom_paid_statuses($statuses) {
        $custom_statuses = $this->get_custom_statuses();
        
        foreach ($custom_statuses as $status) {
            // You can add logic here to determine which custom statuses should be considered "paid"
            // For now, we'll include all custom statuses
            $statuses[] = 'wc-' . $status['slug'];
        }
        
        return $statuses;
    }
    
    /**
     * Custom download permitted statuses
     */
    public function custom_download_permitted_statuses($permitted, $order) {
        $custom_statuses = $this->get_custom_statuses();
        
        foreach ($custom_statuses as $status) {
            if ($order->has_status('wc-' . $status['slug'])) {
                // You can add logic here to determine if downloads are permitted for specific custom statuses
                // For now, we'll allow downloads for all custom statuses
                return true;
            }
        }
        
        return $permitted;
    }
    
    /**
     * Get status display info
     */
    public function get_status_display_info($status_key) {
        $slug = str_replace('wc-', '', $status_key);
        $status = $this->get_status_by_slug($slug);
        
        if ($status) {
            return array(
                'name' => $status['name'],
                'color' => $status['color'],
                'icon' => $status['icon'],
                'visibility' => $status['visibility']
            );
        }
        
        return false;
    }
}
