<?php
/**
 * Admin Class
 * Handles admin interface and functionality
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Class
 */
class WOOSM_Admin {
    
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('manage_shop_order_posts_columns', array($this, 'add_status_column'));
        add_action('manage_shop_order_posts_custom_column', array($this, 'display_status_column'), 10, 2);
        add_action('bulk_actions-edit-shop_order', array($this, 'add_bulk_actions'));
        add_action('handle_bulk_actions-edit-shop_order', array($this, 'handle_bulk_actions'), 10, 3);
        add_action('admin_notices', array($this, 'admin_notices'));
        add_filter('woocommerce_order_actions', array($this, 'add_order_actions'));
        add_action('woocommerce_order_action_custom_status_change', array($this, 'handle_order_action'));
        
        // AJAX handlers
        add_action('wp_ajax_woosm_create_status', array($this, 'ajax_create_status'));
        add_action('wp_ajax_woosm_update_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_woosm_delete_status', array($this, 'ajax_delete_status'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Check if Pro version is active
        if (in_array('wooorder-status-manager-pro/wooorder-status-manager-pro.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            return; // Don't show free version menu when Pro is active
        }
        
        add_menu_page(
            __('Order Status Manager', 'wooorder-status-manager'),
            __('Order Statuses', 'wooorder-status-manager'),
            'manage_woocommerce',
            'woosm-status-manager',
            array($this, 'status_manager_page'),
            'dashicons-admin-generic',
            56
        );
        
        add_submenu_page(
            'woosm-status-manager',
            __('Custom Statuses', 'wooorder-status-manager'),
            __('Custom Statuses', 'wooorder-status-manager'),
            'manage_woocommerce',
            'woosm-status-manager',
            array($this, 'status_manager_page')
        );
        
        add_submenu_page(
            'woosm-status-manager',
            __('Workflow Rules', 'wooorder-status-manager'),
            __('Workflow Rules', 'wooorder-status-manager'),
            'manage_woocommerce',
            'woosm-workflow-rules',
            array($this, 'workflow_rules_page')
        );
        
        add_submenu_page(
            'woosm-status-manager',
            __('Settings', 'wooorder-status-manager'),
            __('Settings', 'wooorder-status-manager'),
            'manage_woocommerce',
            'woosm-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'woosm') !== false || $hook === 'edit.php') {
            wp_enqueue_style('woosm-admin', WOOSM_PLUGIN_URL . 'assets/css/admin.css', array(), WOOSM_VERSION);
            wp_enqueue_script('woosm-admin', WOOSM_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'wp-color-picker'), WOOSM_VERSION, true);
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // Enqueue FontAwesome for icons
            wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
            
            wp_localize_script('woosm-admin', 'woosm_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('woosm_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this status?', 'wooorder-status-manager'),
                    'status_updated' => __('Status updated successfully!', 'wooorder-status-manager'),
                    'status_created' => __('Status created successfully!', 'wooorder-status-manager'),
                    'status_deleted' => __('Status deleted successfully!', 'wooorder-status-manager'),
                    'error_occurred' => __('An error occurred. Please try again.', 'wooorder-status-manager')
                )
            ));
        }
    }
    
    /**
     * Status manager page
     */
    public function status_manager_page() {
        $status_manager = WOOSM_Status_Manager::instance();
        $statuses = $status_manager->get_custom_statuses();
        
        // Handle form submissions
        if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'woosm_admin_action')) {
            $this->handle_status_form_submission();
        }
        
        include WOOSM_PLUGIN_DIR . 'templates/admin/status-manager.php';
    }
    
    /**
     * Workflow rules page
     */
    public function workflow_rules_page() {
        $workflow = WOOSM_Workflow::instance();
        $rules = $workflow->get_workflow_rules();
        $statuses = WOOSM_Status_Manager::instance()->get_custom_statuses();
        
        // Handle form submissions
        if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'woosm_workflow_action')) {
            $this->handle_workflow_form_submission();
        }
        
        include WOOSM_PLUGIN_DIR . 'templates/admin/workflow-rules.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings = get_option('woosm_settings', array());
        
        // Handle form submissions
        if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'woosm_settings_action')) {
            $this->handle_settings_form_submission();
        }
        
        include WOOSM_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * Handle status form submission
     */
    private function handle_status_form_submission() {
        $status_manager = WOOSM_Status_Manager::instance();
        $action = sanitize_text_field($_POST['action']);
        
        switch ($action) {
            case 'create_status':
                $data = array(
                    'name' => sanitize_text_field($_POST['name']),
                    'slug' => sanitize_title($_POST['slug']),
                    'color' => sanitize_hex_color($_POST['color']),
                    'icon' => sanitize_text_field($_POST['icon']),
                    'visibility' => sanitize_text_field($_POST['visibility']),
                    'workflow_order' => intval($_POST['workflow_order']),
                    'email_template' => wp_kses_post($_POST['email_template'])
                );
                
                if ($status_manager->create_status($data)) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Status created successfully!', 'wooorder-status-manager') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Failed to create status. Please try again.', 'wooorder-status-manager') . '</p></div>';
                    });
                }
                break;
                
            case 'update_status':
                $id = intval($_POST['status_id']);
                $data = array(
                    'name' => sanitize_text_field($_POST['name']),
                    'color' => sanitize_hex_color($_POST['color']),
                    'icon' => sanitize_text_field($_POST['icon']),
                    'visibility' => sanitize_text_field($_POST['visibility']),
                    'workflow_order' => intval($_POST['workflow_order']),
                    'email_template' => wp_kses_post($_POST['email_template'])
                );
                
                if ($status_manager->update_status($id, $data)) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Status updated successfully!', 'wooorder-status-manager') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Failed to update status. Please try again.', 'wooorder-status-manager') . '</p></div>';
                    });
                }
                break;
                
            case 'delete_status':
                $id = intval($_POST['status_id']);
                if ($status_manager->delete_status($id)) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Status deleted successfully!', 'wooorder-status-manager') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Failed to delete status. Please try again.', 'wooorder-status-manager') . '</p></div>';
                    });
                }
                break;
        }
    }
    
    /**
     * Handle workflow form submission
     */
    private function handle_workflow_form_submission() {
        $workflow = WOOSM_Workflow::instance();
        $action = sanitize_text_field($_POST['action']);
        
        switch ($action) {
            case 'create_rule':
                $data = array(
                    'from_status' => sanitize_text_field($_POST['from_status']),
                    'to_status' => sanitize_text_field($_POST['to_status']),
                    'trigger_type' => sanitize_text_field($_POST['trigger_type']),
                    'trigger_condition' => sanitize_text_field($_POST['trigger_condition']),
                    'email_notification' => isset($_POST['email_notification']) ? 1 : 0,
                    'email_template' => wp_kses_post($_POST['email_template'])
                );
                
                if ($workflow->create_rule($data)) {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Workflow rule created successfully!', 'wooorder-status-manager') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>' . __('Failed to create workflow rule. Please try again.', 'wooorder-status-manager') . '</p></div>';
                    });
                }
                break;
        }
    }
    
    /**
     * Handle settings form submission
     */
    private function handle_settings_form_submission() {
        $settings = array(
            'enable_customer_tracking' => isset($_POST['enable_customer_tracking']) ? 1 : 0,
            'enable_email_notifications' => isset($_POST['enable_email_notifications']) ? 1 : 0,
            'default_email_template' => wp_kses_post($_POST['default_email_template']),
            'tracking_page_title' => sanitize_text_field($_POST['tracking_page_title']),
            'enable_timeline_view' => isset($_POST['enable_timeline_view']) ? 1 : 0
        );
        
        update_option('woosm_settings', $settings);
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'wooorder-status-manager') . '</p></div>';
        });
    }
    
    /**
     * Add status column to orders table
     */
    public function add_status_column($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $column) {
            $new_columns[$key] = $column;
            
            if ($key === 'order_status') {
                $new_columns['custom_status'] = __('Custom Status', 'wooorder-status-manager');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Display status column content
     */
    public function display_status_column($column, $post_id) {
        if ($column === 'custom_status') {
            // HPOS compatibility - use OrderUtil to get order ID
            if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
                \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
                $order_id = $post_id; // In HPOS, $post_id is actually the order ID
            } else {
                $order_id = $post_id;
            }
            
            $order = wc_get_order($order_id);
            $status_manager = WOOSM_Status_Manager::instance();
            
            if ($order) {
                $status_info = $status_manager->get_status_display_info($order->get_status());
                
                if ($status_info) {
                    $color = $status_info['color'];
                    $icon = $status_info['icon'] ? '<i class="' . esc_attr($icon) . '"></i> ' : '';
                    $name = $status_info['name'];
                    
                    echo '<span class="woosm-status-badge" style="background-color: ' . esc_attr($color) . '; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">';
                    echo $icon . esc_html($name);
                    echo '</span>';
                } else {
                    echo '<span class="woosm-status-badge" style="background-color: #999; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">';
                    echo esc_html(ucfirst(str_replace('wc-', '', $order->get_status())));
                    echo '</span>';
                }
            }
        }
    }
    
    /**
     * Add bulk actions
     */
    public function add_bulk_actions($actions) {
        $status_manager = WOOSM_Status_Manager::instance();
        $statuses = $status_manager->get_custom_statuses();
        
        foreach ($statuses as $status) {
            $actions['set_status_' . $status['slug']] = sprintf(__('Set to %s', 'wooorder-status-manager'), $status['name']);
        }
        
        return $actions;
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if (strpos($doaction, 'set_status_') === 0) {
            $status_slug = str_replace('set_status_', '', $doaction);
            $status_manager = WOOSM_Status_Manager::instance();
            $status = $status_manager->get_status_by_slug($status_slug);
            
            if ($status) {
                $updated = 0;
                
                foreach ($post_ids as $post_id) {
                    $order = wc_get_order($post_id);
                    if ($order) {
                        $order->update_status('wc-' . $status_slug);
                        $updated++;
                    }
                }
                
                $redirect_to = add_query_arg('bulk_updated', $updated, $redirect_to);
            }
        }
        
        return $redirect_to;
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        if (isset($_GET['bulk_updated'])) {
            $updated = intval($_GET['bulk_updated']);
            echo '<div class="notice notice-success"><p>' . sprintf(__('%d orders updated successfully!', 'wooorder-status-manager'), $updated) . '</p></div>';
        }
    }
    
    /**
     * Add order actions
     */
    public function add_order_actions($actions) {
        $status_manager = WOOSM_Status_Manager::instance();
        $statuses = $status_manager->get_custom_statuses();
        
        foreach ($statuses as $status) {
            $actions['custom_status_' . $status['slug']] = sprintf(__('Mark as %s', 'wooorder-status-manager'), $status['name']);
        }
        
        return $actions;
    }
    
    /**
     * Handle order action
     */
    public function handle_order_action($order) {
        $action = current_action();
        
        if (strpos($action, 'custom_status_') === 0) {
            $status_slug = str_replace('woocommerce_order_action_custom_status_', '', $action);
            $order->update_status('wc-' . $status_slug, __('Status changed via order action', 'wooorder-status-manager'));
        }
    }
    
    /**
     * AJAX handler for creating status
     */
    public function ajax_create_status() {
        check_ajax_referer('woosm_admin_action', '_wpnonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wooorder-status-manager')));
        }
        
        $status_manager = WOOSM_Status_Manager::instance();
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'slug' => sanitize_title($_POST['slug']),
            'color' => sanitize_hex_color($_POST['color']),
            'icon' => sanitize_text_field($_POST['icon']),
            'visibility' => sanitize_text_field($_POST['visibility']),
            'workflow_order' => intval($_POST['workflow_order']),
            'email_template' => wp_kses_post($_POST['email_template'])
        );
        
        if ($status_manager->create_status($data)) {
            wp_send_json_success(array('message' => __('Status created successfully!', 'wooorder-status-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to create status. Please try again.', 'wooorder-status-manager')));
        }
    }
    
    /**
     * AJAX handler for updating status
     */
    public function ajax_update_status() {
        check_ajax_referer('woosm_admin_action', '_wpnonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wooorder-status-manager')));
        }
        
        $status_manager = WOOSM_Status_Manager::instance();
        $id = intval($_POST['status_id']);
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'color' => sanitize_hex_color($_POST['color']),
            'icon' => sanitize_text_field($_POST['icon']),
            'visibility' => sanitize_text_field($_POST['visibility']),
            'workflow_order' => intval($_POST['workflow_order']),
            'email_template' => wp_kses_post($_POST['email_template'])
        );
        
        if ($status_manager->update_status($id, $data)) {
            wp_send_json_success(array('message' => __('Status updated successfully!', 'wooorder-status-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to update status. Please try again.', 'wooorder-status-manager')));
        }
    }
    
    /**
     * AJAX handler for deleting status
     */
    public function ajax_delete_status() {
        check_ajax_referer('woosm_admin_action', '_wpnonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'wooorder-status-manager')));
        }
        
        $status_manager = WOOSM_Status_Manager::instance();
        $id = intval($_POST['status_id']);
        
        if ($status_manager->delete_status($id)) {
            wp_send_json_success(array('message' => __('Status deleted successfully!', 'wooorder-status-manager')));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete status. Please try again.', 'wooorder-status-manager')));
        }
    }
}
