<?php
/**
 * Settings Admin Template
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('woosm_settings', array());
$default_settings = array(
    'enable_customer_tracking' => 1,
    'enable_email_notifications' => 1,
    'enable_timeline_view' => 1,
    'tracking_page_title' => __('Order Tracking', 'wooorder-status-manager'),
    'show_order_items' => 0,
    'default_email_template' => ''
);

$settings = wp_parse_args($settings, $default_settings);
?>

<div class="wrap">
    <h1><?php esc_html_e('WooOrder Status Manager Settings', 'wooorder-status-manager'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('woosm_settings_action'); ?>
        <input type="hidden" name="action" value="update_settings">
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="enable_customer_tracking"><?php esc_html_e('Customer Tracking', 'wooorder-status-manager'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="enable_customer_tracking" name="enable_customer_tracking" value="1" <?php checked($settings['enable_customer_tracking']); ?>>
                        <?php esc_html_e('Enable customer-facing order tracking', 'wooorder-status-manager'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Allow customers to view their order status and timeline on the frontend.', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="enable_email_notifications"><?php esc_html_e('Email Notifications', 'wooorder-status-manager'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="enable_email_notifications" name="enable_email_notifications" value="1" <?php checked($settings['enable_email_notifications']); ?>>
                        <?php esc_html_e('Enable email notifications for status changes', 'wooorder-status-manager'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Send automatic email notifications when order status changes.', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="enable_timeline_view"><?php esc_html_e('Timeline View', 'wooorder-status-manager'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="enable_timeline_view" name="enable_timeline_view" value="1" <?php checked($settings['enable_timeline_view']); ?>>
                        <?php esc_html_e('Show order timeline on customer tracking page', 'wooorder-status-manager'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Display a visual timeline of order status changes to customers.', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="show_order_items"><?php esc_html_e('Show Order Items', 'wooorder-status-manager'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="show_order_items" name="show_order_items" value="1" <?php checked($settings['show_order_items']); ?>>
                        <?php esc_html_e('Show order items on tracking page', 'wooorder-status-manager'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Display order items and totals on the customer tracking page.', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="tracking_page_title"><?php esc_html_e('Tracking Page Title', 'wooorder-status-manager'); ?></label>
                </th>
                <td>
                    <input type="text" id="tracking_page_title" name="tracking_page_title" value="<?php echo esc_attr($settings['tracking_page_title']); ?>" class="regular-text">
                    <p class="description">
                        <?php esc_html_e('Title displayed on the order tracking page.', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="tracking_page_id"><?php esc_html_e('Custom Tracking Page', 'wooorder-status-manager'); ?></label>
                </th>
                <td>
                    <?php
                    $tracking_page_id = isset($settings['tracking_page_id']) ? $settings['tracking_page_id'] : 0;
                    wp_dropdown_pages(array(
                        'name' => 'tracking_page_id',
                        'selected' => $tracking_page_id,
                        'show_option_none' => __('Use Default Tracking Page', 'wooorder-status-manager'),
                        'option_none_value' => 0
                    ));
                    ?>
                    <p class="description">
                        <?php esc_html_e('Select a custom page for order tracking. Leave empty to use the default tracking page.', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="default_email_template"><?php esc_html_e('Default Email Template', 'wooorder-status-manager'); ?></label>
                </th>
                <td>
                    <textarea id="default_email_template" name="default_email_template" rows="8" class="large-text"><?php echo esc_textarea($settings['default_email_template']); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Default email template used when no custom template is set for a status. Available placeholders:', 'wooorder-status-manager'); ?><br>
                        <code>{customer_name}</code>, <code>{order_number}</code>, <code>{status_name}</code>, <code>{order_date}</code>, <code>{order_total}</code>, <code>{tracking_url}</code>, <code>{site_name}</code>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php esc_html_e('Shortcodes', 'wooorder-status-manager'); ?></h2>
        <p><?php esc_html_e('Use these shortcodes to display order tracking on any page:', 'wooorder-status-manager'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Order Tracking', 'wooorder-status-manager'); ?></th>
                <td>
                    <code>[woosm_order_tracking order_key="YOUR_ORDER_KEY"]</code><br>
                    <code>[woosm_order_tracking order_id="ORDER_ID"]</code>
                    <p class="description">
                        <?php esc_html_e('Display order tracking information with current status.', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Order Timeline', 'wooorder-status-manager'); ?></th>
                <td>
                    <code>[woosm_order_timeline order_key="YOUR_ORDER_KEY"]</code><br>
                    <code>[woosm_order_timeline order_id="ORDER_ID"]</code>
                    <p class="description">
                        <?php esc_html_e('Display only the order timeline without other order information.', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php esc_html_e('Tracking URLs', 'wooorder-status-manager'); ?></h2>
        <p><?php esc_html_e('Order tracking URLs are automatically generated and can be accessed using these formats:', 'wooorder-status-manager'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Default Format', 'wooorder-status-manager'); ?></th>
                <td>
                    <code><?php echo home_url('order-tracking/ORDER_KEY'); ?></code>
                    <p class="description">
                        <?php esc_html_e('Direct tracking URL format (requires rewrite rules).', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Query Parameter Format', 'wooorder-status-manager'); ?></th>
                <td>
                    <code><?php echo home_url('?woosm_track=ORDER_KEY'); ?></code>
                    <p class="description">
                        <?php esc_html_e('Alternative URL format using query parameters.', 'wooorder-status-manager'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php esc_html_e('System Information', 'wooorder-status-manager'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Plugin Version', 'wooorder-status-manager'); ?></th>
                <td><?php echo esc_html(WOOSM_VERSION); ?></td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('WooCommerce Version', 'wooorder-status-manager'); ?></th>
                <td><?php echo esc_html(WC()->version); ?></td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Custom Statuses', 'wooorder-status-manager'); ?></th>
                <td>
                    <?php
                    $status_manager = WOOSM_Status_Manager::instance();
                    $statuses = $status_manager->get_custom_statuses();
                    echo esc_html(count($statuses));
                    ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Workflow Rules', 'wooorder-status-manager'); ?></th>
                <td>
                    <?php
                    $workflow = WOOSM_Workflow::instance();
                    $rules = $workflow->get_workflow_rules();
                    echo esc_html(count($rules));
                    ?>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <button type="submit" class="button button-primary">
                <?php esc_html_e('Save Settings', 'wooorder-status-manager'); ?>
            </button>
        </p>
    </form>
    
    <div class="woosm-admin-sidebar">
        <div class="woosm-dashboard-widget">
            <h3><?php esc_html_e('Quick Actions', 'wooorder-status-manager'); ?></h3>
            <p>
                <a href="<?php echo admin_url('admin.php?page=woosm-status-manager'); ?>" class="button">
                    <?php esc_html_e('Manage Statuses', 'wooorder-status-manager'); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=woosm-workflow-rules'); ?>" class="button">
                    <?php esc_html_e('Workflow Rules', 'wooorder-status-manager'); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo admin_url('edit.php?post_type=shop_order'); ?>" class="button">
                    <?php esc_html_e('View Orders', 'wooorder-status-manager'); ?>
                </a>
            </p>
        </div>
        
        <div class="woosm-dashboard-widget">
            <h3><?php esc_html_e('Documentation', 'wooorder-status-manager'); ?></h3>
            <p>
                <a href="https://github.com/shahadul878/wooorder-status-manager" target="_blank" class="button">
                    <?php esc_html_e('GitHub Repository', 'wooorder-status-manager'); ?>
                </a>
            </p>
            <p>
                <a href="https://wordpress.org/plugins/wooorder-status-manager/" target="_blank" class="button">
                    <?php esc_html_e('WordPress.org Page', 'wooorder-status-manager'); ?>
                </a>
            </p>
        </div>
    </div>
</div>

<style>
.woosm-admin-sidebar {
    position: fixed;
    right: 20px;
    top: 100px;
    width: 300px;
}

.woosm-admin-sidebar .woosm-dashboard-widget {
    margin-bottom: 20px;
}

@media (max-width: 1200px) {
    .woosm-admin-sidebar {
        position: static;
        width: 100%;
        margin-top: 20px;
    }
}
</style>
