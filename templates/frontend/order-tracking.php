<?php
/**
 * Order Tracking Frontend Template
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$order = wc_get_order($order_id);
if (!$order) {
    return;
}

$status_manager = WOOSM_Status_Manager::instance();
$workflow = WOOSM_Workflow::instance();
$frontend = WOOSM_Frontend::instance();

$status_info = $status_manager->get_status_display_info($order->get_status());
$timeline = $workflow->get_workflow_timeline($order->get_id());
?>

<div class="woosm-order-tracking-page">
    <!-- Order Information -->
    <div class="woosm-order-info" id="woosm-order-info" style="display: none;">
        <h3><?php esc_html_e('Order Information', 'wooorder-status-manager'); ?></h3>
        <div class="woosm-order-details">
            <div class="woosm-order-detail">
                <div class="woosm-order-detail-label"><?php esc_html_e('Order Number', 'wooorder-status-manager'); ?></div>
                <div class="woosm-order-detail-value">#<?php echo esc_html($order->get_order_number()); ?></div>
            </div>
            <div class="woosm-order-detail">
                <div class="woosm-order-detail-label"><?php esc_html_e('Order Date', 'wooorder-status-manager'); ?></div>
                <div class="woosm-order-detail-value"><?php echo esc_html($order->get_date_created()->format('F j, Y')); ?></div>
            </div>
            <div class="woosm-order-detail">
                <div class="woosm-order-detail-label"><?php esc_html_e('Total Amount', 'wooorder-status-manager'); ?></div>
                <div class="woosm-order-detail-value"><?php echo wp_kses_post($order->get_formatted_order_total()); ?></div>
            </div>
            <?php if ($order->get_billing_email()) : ?>
            <div class="woosm-order-detail">
                <div class="woosm-order-detail-label"><?php esc_html_e('Email', 'wooorder-status-manager'); ?></div>
                <div class="woosm-order-detail-value"><?php echo esc_html($order->get_billing_email()); ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Current Status -->
    <div class="woosm-current-status">
        <?php if ($status_info) : ?>
            <span class="woosm-status-badge" style="background-color: <?php echo esc_attr($status_info['color']); ?>; color: white;">
                <?php if ($status_info['icon']) : ?>
                    <i class="<?php echo esc_attr($status_info['icon']); ?>"></i>
                <?php endif; ?>
                <?php echo esc_html($status_info['name']); ?>
            </span>
        <?php else : ?>
            <span class="woosm-status-badge" style="background-color: #999; color: white;">
                <?php echo esc_html(ucfirst(str_replace('wc-', '', $order->get_status()))); ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Order Timeline -->
    <?php if (!empty($timeline)) : ?>
    <div class="woosm-timeline" id="woosm-timeline">
        <h4><?php esc_html_e('Order Timeline', 'wooorder-status-manager'); ?></h4>
        <div class="woosm-timeline-list">
            <?php foreach ($timeline as $index => $item) : ?>
                <?php $is_last = ($index === count($timeline) - 1); ?>
                <?php $status_color = $item['status_color'] ? $item['status_color'] : '#999'; ?>
                <?php $status_icon = $item['status_icon'] ? '<i class="' . esc_attr($item['status_icon']) . '"></i> ' : ''; ?>
                <?php $status_name = $item['status_name'] ? $item['status_name'] : ucfirst($item['to_status']); ?>
                
                <div class="woosm-timeline-item <?php echo $is_last ? 'current' : 'completed'; ?>">
                    <div class="woosm-timeline-content">
                        <div class="woosm-timeline-header">
                            <div class="woosm-timeline-status" style="color: <?php echo esc_attr($status_color); ?>;">
                                <?php echo $status_icon . esc_html($status_name); ?>
                            </div>
                            <div class="woosm-timeline-date">
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['created_at']))); ?>
                            </div>
                        </div>
                        <?php if ($item['change_reason']) : ?>
                            <div class="woosm-timeline-note">
                                <?php echo esc_html($item['change_reason']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Order Items (if enabled in settings) -->
    <?php 
    $settings = get_option('woosm_settings', array());
    if (isset($settings['show_order_items']) && $settings['show_order_items']) : 
    ?>
    <div class="woosm-order-items">
        <h4><?php esc_html_e('Order Items', 'wooorder-status-manager'); ?></h4>
        <div class="woosm-items-list">
            <?php foreach ($order->get_items() as $item_id => $item) : ?>
                <div class="woosm-item">
                    <div class="woosm-item-name">
                        <?php echo wp_kses_post($item->get_name()); ?>
                        <?php if ($item->get_quantity() > 1) : ?>
                            <span class="woosm-item-quantity">× <?php echo esc_html($item->get_quantity()); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="woosm-item-total">
                        <?php echo wp_kses_post($order->get_formatted_line_subtotal($item)); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="woosm-order-totals">
            <div class="woosm-total-line">
                <span><?php esc_html_e('Subtotal:', 'wooorder-status-manager'); ?></span>
                <span><?php echo wp_kses_post($order->get_subtotal_to_display()); ?></span>
            </div>
            
            <?php if ($order->get_total_tax() > 0) : ?>
            <div class="woosm-total-line">
                <span><?php esc_html_e('Tax:', 'wooorder-status-manager'); ?></span>
                <span><?php echo wp_kses_post(wc_price($order->get_total_tax())); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($order->get_total_shipping() > 0) : ?>
            <div class="woosm-total-line">
                <span><?php esc_html_e('Shipping:', 'wooorder-status-manager'); ?></span>
                <span><?php echo wp_kses_post($order->get_shipping_to_display()); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="woosm-total-line woosm-total-final">
                <span><?php esc_html_e('Total:', 'wooorder-status-manager'); ?></span>
                <span><?php echo wp_kses_post($order->get_formatted_order_total()); ?></span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Contact Information -->
    <div class="woosm-contact-info">
        <h4><?php esc_html_e('Need Help?', 'wooorder-status-manager'); ?></h4>
        <p><?php esc_html_e('If you have any questions about your order, please contact us:', 'wooorder-status-manager'); ?></p>
        <div class="woosm-contact-details">
            <?php if (get_option('admin_email')) : ?>
            <div class="woosm-contact-item">
                <strong><?php esc_html_e('Email:', 'wooorder-status-manager'); ?></strong>
                <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>">
                    <?php echo esc_html(get_option('admin_email')); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <?php 
            $phone = get_option('woocommerce_store_phone');
            if ($phone) : 
            ?>
            <div class="woosm-contact-item">
                <strong><?php esc_html_e('Phone:', 'wooorder-status-manager'); ?></strong>
                <a href="tel:<?php echo esc_attr($phone); ?>">
                    <?php echo esc_html($phone); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tracking URL for sharing -->
    <div class="woosm-share-tracking">
        <h4><?php esc_html_e('Share Tracking', 'wooorder-status-manager'); ?></h4>
        <p><?php esc_html_e('Share this tracking link with others:', 'wooorder-status-manager'); ?></p>
        <div class="woosm-share-input">
            <input type="text" value="<?php echo esc_url($frontend->get_tracking_url($order)); ?>" readonly>
            <button type="button" class="woosm-copy-button" onclick="navigator.clipboard.writeText(this.previousElementSibling.value)">
                <?php esc_html_e('Copy', 'wooorder-status-manager'); ?>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide loading state and show content
    setTimeout(function() {
        document.getElementById('woosm-order-info').style.display = 'block';
        document.getElementById('woosm-timeline').style.display = 'block';
    }, 500);
    
    // Copy to clipboard functionality
    document.querySelectorAll('.woosm-copy-button').forEach(function(button) {
        button.addEventListener('click', function() {
            var input = this.previousElementSibling;
            input.select();
            input.setSelectionRange(0, 99999); // For mobile devices
            
            try {
                document.execCommand('copy');
                this.textContent = '<?php esc_js(__('Copied!', 'wooorder-status-manager')); ?>';
                setTimeout(function() {
                    button.textContent = '<?php esc_js(__('Copy', 'wooorder-status-manager')); ?>';
                }, 2000);
            } catch (err) {
                console.log('Failed to copy text');
            }
        });
    });
});
</script>
