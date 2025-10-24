<?php
/**
 * Workflow Class
 * Handles workflow automation and rules
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Workflow Class
 */
class WOOSM_Workflow {
    
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
        add_action('woosm_status_changed', array($this, 'check_workflow_rules'), 10, 3);
        add_action('woocommerce_payment_complete', array($this, 'on_payment_complete'));
        add_action('woocommerce_order_status_processing', array($this, 'on_order_processing'));
        add_action('woocommerce_reduce_order_stock', array($this, 'on_stock_reduction'));
    }
    
    /**
     * Check workflow rules when status changes
     */
    public function check_workflow_rules($order_id, $old_status, $new_status) {
        $rules = $this->get_workflow_rules();
        
        foreach ($rules as $rule) {
            if ($this->should_trigger_rule($order_id, $rule, $old_status, $new_status)) {
                $this->execute_rule($order_id, $rule);
            }
        }
    }
    
    /**
     * Check if rule should be triggered
     */
    private function should_trigger_rule($order_id, $rule, $old_status, $new_status) {
        // Check if this rule applies to the current status change
        if ($rule['from_status'] !== str_replace('wc-', '', $old_status) && 
            $rule['from_status'] !== '*') {
            return false;
        }
        
        if ($rule['to_status'] !== str_replace('wc-', '', $new_status)) {
            return false;
        }
        
        // Check trigger conditions
        switch ($rule['trigger_type']) {
            case 'automatic':
                return true;
                
            case 'payment':
                $order = wc_get_order($order_id);
                return $order && $order->is_paid();
                
            case 'stock':
                return $this->check_stock_condition($order_id, $rule['trigger_condition']);
                
            default:
                return false;
        }
    }
    
    /**
     * Execute workflow rule
     */
    private function execute_rule($order_id, $rule) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // Send email notification if enabled
        if ($rule['email_notification'] && $rule['email_template']) {
            $this->send_workflow_email($order, $rule);
        }
        
        // Add order note
        $this->add_workflow_note($order, $rule);
        
        // Trigger custom action
        do_action('woosm_workflow_rule_executed', $order_id, $rule);
    }
    
    /**
     * Send workflow email
     */
    private function send_workflow_email($order, $rule) {
        $email_manager = WOOSM_Email::instance();
        $email_manager->send_workflow_email($order, $rule);
    }
    
    /**
     * Add workflow note to order
     */
    private function add_workflow_note($order, $rule) {
        $note = sprintf(
            __('Workflow rule triggered: %s -> %s', 'wooorder-status-manager'),
            $rule['from_status'],
            $rule['to_status']
        );
        
        $order->add_order_note($note);
    }
    
    /**
     * Check stock condition
     */
    private function check_stock_condition($order_id, $condition) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        switch ($condition) {
            case 'in_stock':
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product && !$product->is_in_stock()) {
                        return false;
                    }
                }
                return true;
                
            case 'low_stock':
                foreach ($order->get_items() as $item) {
                    $product = $item->get_product();
                    if ($product && $product->is_in_stock() && $product->get_stock_quantity() <= 5) {
                        return true;
                    }
                }
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * Handle payment complete
     */
    public function on_payment_complete($order_id) {
        $rules = $this->get_workflow_rules_by_trigger('payment');
        
        foreach ($rules as $rule) {
            $this->execute_rule($order_id, $rule);
        }
    }
    
    /**
     * Handle order processing
     */
    public function on_order_processing($order_id) {
        $rules = $this->get_workflow_rules_by_trigger('automatic');
        
        foreach ($rules as $rule) {
            if ($rule['from_status'] === 'pending') {
                $this->execute_rule($order_id, $rule);
            }
        }
    }
    
    /**
     * Handle stock reduction
     */
    public function on_stock_reduction($order) {
        $order_id = $order->get_id();
        $rules = $this->get_workflow_rules_by_trigger('stock');
        
        foreach ($rules as $rule) {
            if ($this->check_stock_condition($order_id, $rule['trigger_condition'])) {
                $this->execute_rule($order_id, $rule);
            }
        }
    }
    
    /**
     * Get workflow rules from database
     */
    public function get_workflow_rules() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_workflow_rules';
        $rules = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A);
        
        return $rules ? $rules : array();
    }
    
    /**
     * Get workflow rules by trigger type
     */
    public function get_workflow_rules_by_trigger($trigger_type) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_workflow_rules';
        $rules = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE trigger_type = %s", $trigger_type), ARRAY_A);
        
        return $rules ? $rules : array();
    }
    
    /**
     * Create workflow rule
     */
    public function create_rule($data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_workflow_rules';
        
        $insert_data = array(
            'from_status' => sanitize_text_field($data['from_status']),
            'to_status' => sanitize_text_field($data['to_status']),
            'trigger_type' => sanitize_text_field($data['trigger_type']),
            'trigger_condition' => sanitize_text_field($data['trigger_condition']),
            'email_notification' => intval($data['email_notification']),
            'email_template' => wp_kses_post($data['email_template'])
        );
        
        $result = $wpdb->insert($table_name, $insert_data);
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Update workflow rule
     */
    public function update_rule($id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_workflow_rules';
        
        $update_data = array();
        
        if (isset($data['from_status'])) {
            $update_data['from_status'] = sanitize_text_field($data['from_status']);
        }
        if (isset($data['to_status'])) {
            $update_data['to_status'] = sanitize_text_field($data['to_status']);
        }
        if (isset($data['trigger_type'])) {
            $update_data['trigger_type'] = sanitize_text_field($data['trigger_type']);
        }
        if (isset($data['trigger_condition'])) {
            $update_data['trigger_condition'] = sanitize_text_field($data['trigger_condition']);
        }
        if (isset($data['email_notification'])) {
            $update_data['email_notification'] = intval($data['email_notification']);
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
        
        return $result !== false;
    }
    
    /**
     * Delete workflow rule
     */
    public function delete_rule($id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'woosm_workflow_rules';
        $result = $wpdb->delete($table_name, array('id' => $id), array('%d'));
        
        return $result !== false;
    }
    
    /**
     * Get workflow timeline for order
     */
    public function get_workflow_timeline($order_id) {
        global $wpdb;
        
        $history_table = $wpdb->prefix . 'woosm_order_status_history';
        $status_table = $wpdb->prefix . 'woosm_custom_statuses';
        
        $timeline = $wpdb->get_results($wpdb->prepare("
            SELECT 
                h.*,
                s.name as status_name,
                s.color as status_color,
                s.icon as status_icon
            FROM $history_table h
            LEFT JOIN $status_table s ON h.to_status = s.slug
            WHERE h.order_id = %d
            ORDER BY h.created_at ASC
        ", $order_id), ARRAY_A);
        
        return $timeline ? $timeline : array();
    }
    
    /**
     * Get next possible statuses for order
     */
    public function get_next_possible_statuses($current_status) {
        global $wpdb;
        
        $rules_table = $wpdb->prefix . 'woosm_workflow_rules';
        $status_table = $wpdb->prefix . 'woosm_custom_statuses';
        
        $current_slug = str_replace('wc-', '', $current_status);
        
        $next_statuses = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT s.*
            FROM $rules_table r
            JOIN $status_table s ON r.to_status = s.slug
            WHERE r.from_status = %s OR r.from_status = '*'
            ORDER BY s.workflow_order ASC
        ", $current_slug), ARRAY_A);
        
        return $next_statuses ? $next_statuses : array();
    }
}
