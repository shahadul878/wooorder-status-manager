<?php
/**
 * Frontend Class
 * Handles customer-facing functionality
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend Class
 */
class WOOSM_Frontend {
    
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_tracking_page'));
        add_action('woocommerce_view_order', array($this, 'display_order_tracking'), 5);
        add_action('woocommerce_thankyou', array($this, 'display_order_tracking'), 5);
        add_shortcode('woosm_order_tracking', array($this, 'order_tracking_shortcode'));
        add_shortcode('woosm_order_timeline', array($this, 'order_timeline_shortcode'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        if (is_account_page() || is_page() || isset($_GET['woosm_track'])) {
            wp_enqueue_style('woosm-frontend', WOOSM_PLUGIN_URL . 'assets/css/frontend.css', array(), WOOSM_VERSION);
            wp_enqueue_script('woosm-frontend', WOOSM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), WOOSM_VERSION, true);
            
            // Enqueue FontAwesome for icons
            wp_enqueue_style('fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css', array(), '6.0.0');
            
            wp_localize_script('woosm-frontend', 'woosm_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('woosm_frontend_nonce'),
                'strings' => array(
                    'tracking_not_found' => __('Order not found or invalid tracking key.', 'wooorder-status-manager'),
                    'loading' => __('Loading...', 'wooorder-status-manager')
                )
            ));
        }
    }
    
    /**
     * Add rewrite rules for tracking page
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^order-tracking/([^/]+)/?$',
            'index.php?woosm_track=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'woosm_track';
        return $vars;
    }
    
    /**
     * Handle tracking page
     */
    public function handle_tracking_page() {
        if (get_query_var('woosm_track')) {
            $order_key = get_query_var('woosm_track');
            $this->display_tracking_page($order_key);
        }
    }
    
    /**
     * Display tracking page
     */
    private function display_tracking_page($order_key) {
        $order = $this->get_order_by_key($order_key);
        
        if (!$order) {
            wp_die(__('Order not found or invalid tracking key.', 'wooorder-status-manager'));
        }
        
        // Set up page content
        $settings = get_option('woosm_settings', array());
        $page_title = isset($settings['tracking_page_title']) ? $settings['tracking_page_title'] : __('Order Tracking', 'wooorder-status-manager');
        
        // Start output buffering
        ob_start();
        
        // Include tracking template
        include WOOSM_PLUGIN_DIR . 'templates/frontend/order-tracking.php';
        
        $content = ob_get_clean();
        
        // Display the page
        echo $this->wrap_tracking_page($page_title, $content);
        exit;
    }
    
    /**
     * Wrap tracking page with theme structure
     */
    private function wrap_tracking_page($title, $content) {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . esc_html($title) . ' - ' . get_bloginfo('name') . '</title>
            ' . wp_head() . '
        </head>
        <body>
            <div class="woosm-tracking-page">
                <div class="woosm-tracking-container">
                    <h1>' . esc_html($title) . '</h1>
                    ' . $content . '
                </div>
            </div>
            ' . wp_footer() . '
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Display order tracking on account page
     */
    public function display_order_tracking($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        $settings = get_option('woosm_settings', array());
        
        if (!isset($settings['enable_customer_tracking']) || !$settings['enable_customer_tracking']) {
            return;
        }
        
        echo '<div class="woosm-order-tracking">';
        echo '<h3>' . esc_html__('Order Tracking', 'wooorder-status-manager') . '</h3>';
        
        // Display current status
        $this->display_current_status($order);
        
        // Display timeline if enabled
        if (isset($settings['enable_timeline_view']) && $settings['enable_timeline_view']) {
            $this->display_order_timeline($order);
        }
        
        echo '</div>';
    }
    
    /**
     * Display current order status
     */
    private function display_current_status($order) {
        $status_manager = WOOSM_Status_Manager::instance();
        $status_info = $status_manager->get_status_display_info($order->get_status());
        
        if ($status_info) {
            $color = $status_info['color'];
            $icon = $status_info['icon'] ? '<i class="' . esc_attr($status_info['icon']) . '"></i> ' : '';
            $name = $status_info['name'];
            
            echo '<div class="woosm-current-status">';
            echo '<span class="woosm-status-badge" style="background-color: ' . esc_attr($color) . '; color: white; padding: 8px 12px; border-radius: 4px; font-size: 14px; font-weight: bold;">';
            echo $icon . esc_html($name);
            echo '</span>';
            echo '</div>';
        } else {
            echo '<div class="woosm-current-status">';
            echo '<span class="woosm-status-badge" style="background-color: #999; color: white; padding: 8px 12px; border-radius: 4px; font-size: 14px; font-weight: bold;">';
            echo esc_html(ucfirst(str_replace('wc-', '', $order->get_status())));
            echo '</span>';
            echo '</div>';
        }
    }
    
    /**
     * Display order timeline
     */
    private function display_order_timeline($order) {
        $workflow = WOOSM_Workflow::instance();
        $timeline = $workflow->get_workflow_timeline($order->get_id());
        
        if (empty($timeline)) {
            return;
        }
        
        echo '<div class="woosm-timeline">';
        echo '<h4>' . esc_html__('Order Timeline', 'wooorder-status-manager') . '</h4>';
        
        foreach ($timeline as $index => $item) {
            $is_last = ($index === count($timeline) - 1);
            $status_color = $item['status_color'] ? $item['status_color'] : '#999';
            $status_icon = $item['status_icon'] ? '<i class="' . esc_attr($item['status_icon']) . '"></i> ' : '';
            
            echo '<div class="woosm-timeline-item' . ($is_last ? ' active' : '') . '">';
            echo '<div class="woosm-timeline-content">';
            echo '<div class="woosm-timeline-header">';
            echo '<span class="woosm-timeline-status" style="color: ' . esc_attr($status_color) . ';">';
            echo $status_icon . esc_html($item['status_name'] ? $item['status_name'] : ucfirst($item['to_status']));
            echo '</span>';
            echo '<span class="woosm-timeline-date">' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['created_at']))) . '</span>';
            echo '</div>';
            
            if ($item['change_reason']) {
                echo '<div class="woosm-timeline-note">' . esc_html($item['change_reason']) . '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Order tracking shortcode
     */
    public function order_tracking_shortcode($atts) {
        $atts = shortcode_atts(array(
            'order_key' => '',
            'order_id' => ''
        ), $atts);
        
        if ($atts['order_key']) {
            $order = $this->get_order_by_key($atts['order_key']);
        } elseif ($atts['order_id']) {
            $order = wc_get_order($atts['order_id']);
        } else {
            return '<p>' . esc_html__('Please provide order key or order ID.', 'wooorder-status-manager') . '</p>';
        }
        
        if (!$order) {
            return '<p>' . esc_html__('Order not found.', 'wooorder-status-manager') . '</p>';
        }
        
        ob_start();
        echo '<div class="woosm-order-tracking-shortcode">';
        $this->display_current_status($order);
        $this->display_order_timeline($order);
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Order timeline shortcode
     */
    public function order_timeline_shortcode($atts) {
        $atts = shortcode_atts(array(
            'order_key' => '',
            'order_id' => ''
        ), $atts);
        
        if ($atts['order_key']) {
            $order = $this->get_order_by_key($atts['order_key']);
        } elseif ($atts['order_id']) {
            $order = wc_get_order($atts['order_id']);
        } else {
            return '<p>' . esc_html__('Please provide order key or order ID.', 'wooorder-status-manager') . '</p>';
        }
        
        if (!$order) {
            return '<p>' . esc_html__('Order not found.', 'wooorder-status-manager') . '</p>';
        }
        
        ob_start();
        $this->display_order_timeline($order);
        
        return ob_get_clean();
    }
    
    /**
     * Get order by key
     */
    private function get_order_by_key($order_key) {
        global $wpdb;
        
        $order_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_order_key' 
            AND meta_value = %s
        ", $order_key));
        
        if ($order_id) {
            return wc_get_order($order_id);
        }
        
        return false;
    }
    
    /**
     * Get tracking URL for order
     */
    public function get_tracking_url($order) {
        $settings = get_option('woosm_settings', array());
        $tracking_page = isset($settings['tracking_page_id']) ? $settings['tracking_page_id'] : 0;
        
        if ($tracking_page) {
            return add_query_arg(array(
                'order_id' => $order->get_id(),
                'order_key' => $order->get_order_key()
            ), get_permalink($tracking_page));
        }
        
        return home_url('order-tracking/' . $order->get_order_key());
    }
}
