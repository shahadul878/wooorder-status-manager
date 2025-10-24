<?php
/**
 * Email Class
 * Handles email notifications for status changes
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Class
 */
class WOOSM_Email {
    
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
        add_action('woocommerce_email_styles', array($this, 'add_email_styles'));
    }
    
    /**
     * Send status notification email
     */
    public function send_status_notification($order, $status) {
        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        
        $subject = sprintf(
            __('Order #%s Status Update: %s', 'wooorder-status-manager'),
            $order->get_order_number(),
            $status['name']
        );
        
        $message = $this->prepare_email_template($order, $status);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($customer_email, $subject, $message, $headers);
        
        // Log email sent
        $order->add_order_note(sprintf(
            __('Status notification email sent to %s', 'wooorder-status-manager'),
            $customer_email
        ));
    }
    
    /**
     * Send workflow email
     */
    public function send_workflow_email($order, $rule) {
        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        
        $subject = sprintf(
            __('Order #%s Update: %s', 'wooorder-status-manager'),
            $order->get_order_number(),
            $rule['to_status']
        );
        
        $message = $this->prepare_workflow_email_template($order, $rule);
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        wp_mail($customer_email, $subject, $message, $headers);
        
        // Log email sent
        $order->add_order_note(sprintf(
            __('Workflow notification email sent to %s', 'wooorder-status-manager'),
            $customer_email
        ));
    }
    
    /**
     * Prepare email template
     */
    private function prepare_email_template($order, $status) {
        $template = $status['email_template'];
        
        if (empty($template)) {
            $template = $this->get_default_email_template();
        }
        
        $placeholders = array(
            '{customer_name}' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '{order_number}' => $order->get_order_number(),
            '{status_name}' => $status['name'],
            '{status_color}' => $status['color'],
            '{order_date}' => $order->get_date_created()->format('F j, Y'),
            '{order_total}' => $order->get_formatted_order_total(),
            '{tracking_url}' => $this->get_tracking_url($order),
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url()
        );
        
        $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);
        
        return $this->wrap_email_template($message, $status);
    }
    
    /**
     * Prepare workflow email template
     */
    private function prepare_workflow_email_template($order, $rule) {
        $template = $rule['email_template'];
        
        if (empty($template)) {
            $template = $this->get_default_workflow_email_template();
        }
        
        $placeholders = array(
            '{customer_name}' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            '{order_number}' => $order->get_order_number(),
            '{from_status}' => $rule['from_status'],
            '{to_status}' => $rule['to_status'],
            '{order_date}' => $order->get_date_created()->format('F j, Y'),
            '{order_total}' => $order->get_formatted_order_total(),
            '{tracking_url}' => $this->get_tracking_url($order),
            '{site_name}' => get_bloginfo('name'),
            '{site_url}' => home_url()
        );
        
        $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);
        
        return $this->wrap_workflow_email_template($message, $rule);
    }
    
    /**
     * Wrap email template with HTML structure
     */
    private function wrap_email_template($content, $status) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . esc_html__('Order Status Update', 'wooorder-status-manager') . '</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="color: ' . esc_attr($status['color']) . '; margin: 0;">' . esc_html($status['name']) . '</h1>
                </div>
                
                <div style="background-color: ' . esc_attr($status['color']) . '; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    ' . ($status['icon'] ? '<i class="' . esc_attr($status['icon']) . '" style="font-size: 24px; margin-right: 10px;"></i>' : '') . '
                    <strong style="font-size: 18px;">' . esc_html($status['name']) . '</strong>
                </div>
                
                <div style="line-height: 1.6; color: #333;">
                    ' . wp_kses_post($content) . '
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 12px;">
                    <p>' . sprintf(esc_html__('This email was sent from %s', 'wooorder-status-manager'), get_bloginfo('name')) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Wrap workflow email template
     */
    private function wrap_workflow_email_template($content, $rule) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . esc_html__('Order Update', 'wooorder-status-manager') . '</title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 20px;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="color: #333; margin: 0;">' . esc_html__('Order Update', 'wooorder-status-manager') . '</h1>
                </div>
                
                <div style="background-color: #0073aa; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    <strong style="font-size: 18px;">' . esc_html($rule['to_status']) . '</strong>
                </div>
                
                <div style="line-height: 1.6; color: #333;">
                    ' . wp_kses_post($content) . '
                </div>
                
                <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 12px;">
                    <p>' . sprintf(esc_html__('This email was sent from %s', 'wooorder-status-manager'), get_bloginfo('name')) . '</p>
                </div>
            </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Get default email template
     */
    private function get_default_email_template() {
        return '
        <p>' . esc_html__('Hello {customer_name},', 'wooorder-status-manager') . '</p>
        
        <p>' . sprintf(
            esc_html__('We wanted to let you know that your order #%s has been updated to: %s', 'wooorder-status-manager'),
            '{order_number}',
            '{status_name}'
        ) . '</p>
        
        <p>' . esc_html__('Order Details:', 'wooorder-status-manager') . '</p>
        <ul>
            <li>' . sprintf(esc_html__('Order Number: %s', 'wooorder-status-manager'), '{order_number}') . '</li>
            <li>' . sprintf(esc_html__('Order Date: %s', 'wooorder-status-manager'), '{order_date}') . '</li>
            <li>' . sprintf(esc_html__('Total: %s', 'wooorder-status-manager'), '{order_total}') . '</li>
            <li>' . sprintf(esc_html__('Current Status: %s', 'wooorder-status-manager'), '{status_name}') . '</li>
        </ul>
        
        <p>' . sprintf(
            esc_html__('You can track your order status at: %s', 'wooorder-status-manager'),
            '<a href="{tracking_url}">{tracking_url}</a>'
        ) . '</p>
        
        <p>' . esc_html__('Thank you for your business!', 'wooorder-status-manager') . '</p>
        
        <p>' . sprintf(esc_html__('Best regards,<br>The %s Team', 'wooorder-status-manager'), '{site_name}') . '</p>';
    }
    
    /**
     * Get default workflow email template
     */
    private function get_default_workflow_email_template() {
        return '
        <p>' . esc_html__('Hello {customer_name},', 'wooorder-status-manager') . '</p>
        
        <p>' . sprintf(
            esc_html__('Your order #%s status has changed from %s to %s', 'wooorder-status-manager'),
            '{order_number}',
            '{from_status}',
            '{to_status}'
        ) . '</p>
        
        <p>' . sprintf(
            esc_html__('You can track your order status at: %s', 'wooorder-status-manager'),
            '<a href="{tracking_url}">{tracking_url}</a>'
        ) . '</p>
        
        <p>' . esc_html__('Thank you for your business!', 'wooorder-status-manager') . '</p>';
    }
    
    /**
     * Get tracking URL for order
     */
    private function get_tracking_url($order) {
        $settings = get_option('woosm_settings', array());
        $tracking_page = isset($settings['tracking_page_id']) ? $settings['tracking_page_id'] : 0;
        
        if ($tracking_page) {
            return add_query_arg(array(
                'order_id' => $order->get_id(),
                'order_key' => $order->get_order_key()
            ), get_permalink($tracking_page));
        }
        
        return add_query_arg(array(
            'woosm_track' => $order->get_order_key()
        ), home_url());
    }
    
    /**
     * Add email styles
     */
    public function add_email_styles($styles) {
        $styles .= '
        .woosm-status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            color: white;
            font-size: 11px;
            font-weight: bold;
        }
        .woosm-timeline {
            border-left: 2px solid #ddd;
            padding-left: 20px;
            margin: 20px 0;
        }
        .woosm-timeline-item {
            position: relative;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        .woosm-timeline-item:before {
            content: "";
            position: absolute;
            left: -25px;
            top: 5px;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #ddd;
        }
        .woosm-timeline-item.active:before {
            background-color: #0073aa;
        }
        ';
        
        return $styles;
    }
}
