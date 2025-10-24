<?php
/**
 * AJAX Class
 * Handles AJAX requests
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX Class
 */
class WOOSM_Ajax {
    
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
        // Admin AJAX actions
        add_action('wp_ajax_woosm_create_status', array($this, 'ajax_create_status'));
        add_action('wp_ajax_woosm_update_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_woosm_delete_status', array($this, 'ajax_delete_status'));
        add_action('wp_ajax_woosm_get_status', array($this, 'ajax_get_status'));
        add_action('wp_ajax_woosm_bulk_update_orders', array($this, 'ajax_bulk_update_orders'));
        
        // Workflow AJAX actions
        add_action('wp_ajax_woosm_create_workflow_rule', array($this, 'ajax_create_workflow_rule'));
        add_action('wp_ajax_woosm_update_workflow_rule', array($this, 'ajax_update_workflow_rule'));
        add_action('wp_ajax_woosm_delete_workflow_rule', array($this, 'ajax_delete_workflow_rule'));
        
        // Frontend AJAX actions
        add_action('wp_ajax_nopriv_woosm_track_order', array($this, 'ajax_track_order'));
        add_action('wp_ajax_woosm_track_order', array($this, 'ajax_track_order'));
        add_action('wp_ajax_woosm_get_order_timeline', array($this, 'ajax_get_order_timeline'));
    }
    
    /**
     * AJAX: Create status
     */
    public function ajax_create_status() {
        check_ajax_referer('woosm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'wooorder-status-manager'));
        }
        
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'slug' => sanitize_title($_POST['slug']),
            'color' => sanitize_hex_color($_POST['color']),
            'icon' => sanitize_text_field($_POST['icon']),
            'visibility' => sanitize_text_field($_POST['visibility']),
            'workflow_order' => intval($_POST['workflow_order']),
            'email_template' => wp_kses_post($_POST['email_template'])
        );
        
        $status_manager = WOOSM_Status_Manager::instance();
        $result = $status_manager->create_status($data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Status created successfully!', 'wooorder-status-manager'),
                'status_id' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to create status. Please try again.', 'wooorder-status-manager')
            ));
        }
    }
    
    /**
     * AJAX: Update status
     */
    public function ajax_update_status() {
        check_ajax_referer('woosm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'wooorder-status-manager'));
        }
        
        $id = intval($_POST['status_id']);
        $data = array(
            'name' => sanitize_text_field($_POST['name']),
            'color' => sanitize_hex_color($_POST['color']),
            'icon' => sanitize_text_field($_POST['icon']),
            'visibility' => sanitize_text_field($_POST['visibility']),
            'workflow_order' => intval($_POST['workflow_order']),
            'email_template' => wp_kses_post($_POST['email_template'])
        );
        
        $status_manager = WOOSM_Status_Manager::instance();
        $result = $status_manager->update_status($id, $data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Status updated successfully!', 'wooorder-status-manager')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update status. Please try again.', 'wooorder-status-manager')
            ));
        }
    }
    
    /**
     * AJAX: Delete status
     */
    public function ajax_delete_status() {
        check_ajax_referer('woosm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'wooorder-status-manager'));
        }
        
        $id = intval($_POST['status_id']);
        $status_manager = WOOSM_Status_Manager::instance();
        $result = $status_manager->delete_status($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Status deleted successfully!', 'wooorder-status-manager')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete status. Please try again.', 'wooorder-status-manager')
            ));
        }
    }
    
    /**
     * AJAX: Get status
     */
    public function ajax_get_status() {
        check_ajax_referer('woosm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'wooorder-status-manager'));
        }
        
        $id = intval($_POST['status_id']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'woosm_custom_statuses';
        $status = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
        
        if ($status) {
            wp_send_json_success($status);
        } else {
            wp_send_json_error(array(
                'message' => __('Status not found.', 'wooorder-status-manager')
            ));
        }
    }
    
    /**
     * AJAX: Bulk update orders
     */
    public function ajax_bulk_update_orders() {
        check_ajax_referer('woosm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'wooorder-status-manager'));
        }
        
        $order_ids = array_map('intval', $_POST['order_ids']);
        $status_slug = sanitize_text_field($_POST['status_slug']);
        
        $updated = 0;
        
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            if ($order) {
                $order->update_status('wc-' . $status_slug, __('Status changed via bulk action', 'wooorder-status-manager'));
                $updated++;
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d orders updated successfully!', 'wooorder-status-manager'), $updated),
            'updated_count' => $updated
        ));
    }
    
    /**
     * AJAX: Create workflow rule
     */
    public function ajax_create_workflow_rule() {
        check_ajax_referer('woosm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'wooorder-status-manager'));
        }
        
        $data = array(
            'from_status' => sanitize_text_field($_POST['from_status']),
            'to_status' => sanitize_text_field($_POST['to_status']),
            'trigger_type' => sanitize_text_field($_POST['trigger_type']),
            'trigger_condition' => sanitize_text_field($_POST['trigger_condition']),
            'email_notification' => isset($_POST['email_notification']) ? 1 : 0,
            'email_template' => wp_kses_post($_POST['email_template'])
        );
        
        $workflow = WOOSM_Workflow::instance();
        $result = $workflow->create_rule($data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Workflow rule created successfully!', 'wooorder-status-manager'),
                'rule_id' => $result
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to create workflow rule. Please try again.', 'wooorder-status-manager')
            ));
        }
    }
    
    /**
     * AJAX: Update workflow rule
     */
    public function ajax_update_workflow_rule() {
        check_ajax_referer('woosm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'wooorder-status-manager'));
        }
        
        $id = intval($_POST['rule_id']);
        $data = array(
            'from_status' => sanitize_text_field($_POST['from_status']),
            'to_status' => sanitize_text_field($_POST['to_status']),
            'trigger_type' => sanitize_text_field($_POST['trigger_type']),
            'trigger_condition' => sanitize_text_field($_POST['trigger_condition']),
            'email_notification' => isset($_POST['email_notification']) ? 1 : 0,
            'email_template' => wp_kses_post($_POST['email_template'])
        );
        
        $workflow = WOOSM_Workflow::instance();
        $result = $workflow->update_rule($id, $data);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Workflow rule updated successfully!', 'wooorder-status-manager')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to update workflow rule. Please try again.', 'wooorder-status-manager')
            ));
        }
    }
    
    /**
     * AJAX: Delete workflow rule
     */
    public function ajax_delete_workflow_rule() {
        check_ajax_referer('woosm_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions.', 'wooorder-status-manager'));
        }
        
        $id = intval($_POST['rule_id']);
        $workflow = WOOSM_Workflow::instance();
        $result = $workflow->delete_rule($id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => __('Workflow rule deleted successfully!', 'wooorder-status-manager')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete workflow rule. Please try again.', 'wooorder-status-manager')
            ));
        }
    }
    
    /**
     * AJAX: Track order (frontend)
     */
    public function ajax_track_order() {
        check_ajax_referer('woosm_frontend_nonce', 'nonce');
        
        $order_key = sanitize_text_field($_POST['order_key']);
        
        global $wpdb;
        $order_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_order_key' 
            AND meta_value = %s
        ", $order_key));
        
        if (!$order_id) {
            wp_send_json_error(array(
                'message' => __('Order not found or invalid tracking key.', 'wooorder-status-manager')
            ));
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array(
                'message' => __('Order not found.', 'wooorder-status-manager')
            ));
        }
        
        $status_manager = WOOSM_Status_Manager::instance();
        $status_info = $status_manager->get_status_display_info($order->get_status());
        
        $response = array(
            'order_id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'status' => array(
                'name' => $status_info ? $status_info['name'] : ucfirst(str_replace('wc-', '', $order->get_status())),
                'color' => $status_info ? $status_info['color'] : '#999',
                'icon' => $status_info ? $status_info['icon'] : '',
                'visibility' => $status_info ? $status_info['visibility'] : 'both'
            ),
            'order_date' => $order->get_date_created()->format('F j, Y'),
            'order_total' => $order->get_formatted_order_total()
        );
        
        wp_send_json_success($response);
    }
    
    /**
     * AJAX: Get order timeline
     */
    public function ajax_get_order_timeline() {
        check_ajax_referer('woosm_frontend_nonce', 'nonce');
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(array(
                'message' => __('Order not found.', 'wooorder-status-manager')
            ));
        }
        
        $workflow = WOOSM_Workflow::instance();
        $timeline = $workflow->get_workflow_timeline($order_id);
        
        wp_send_json_success($timeline);
    }
}
