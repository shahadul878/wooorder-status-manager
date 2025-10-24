<?php
/**
 * Workflow Rules Admin Template
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Workflow Rules', 'wooorder-status-manager'); ?></h1>
    
    <div class="woosm-workflow-rules">
        <div class="woosm-workflow-main">
            <div class="woosm-rules-list">
                <h2><?php esc_html_e('Existing Rules', 'wooorder-status-manager'); ?></h2>
                
                <?php if (empty($rules)) : ?>
                    <p><?php esc_html_e('No workflow rules found. Create your first rule below.', 'wooorder-status-manager'); ?></p>
                <?php else : ?>
                    <div class="woosm-rules-container">
                        <?php foreach ($rules as $rule) : ?>
                            <div class="woosm-workflow-rule">
                                <div class="woosm-workflow-rule-header">
                                    <div class="woosm-workflow-rule-title">
                                        <?php echo esc_html(ucfirst($rule['from_status'])); ?> → <?php echo esc_html(ucfirst($rule['to_status'])); ?>
                                    </div>
                                    <div class="woosm-workflow-rule-actions">
                                        <button class="button button-small woosm-edit-rule" data-rule-id="<?php echo esc_attr($rule['id']); ?>">
                                            <?php esc_html_e('Edit', 'wooorder-status-manager'); ?>
                                        </button>
                                        <button class="button button-small button-link-delete woosm-delete-rule" data-rule-id="<?php echo esc_attr($rule['id']); ?>">
                                            <?php esc_html_e('Delete', 'wooorder-status-manager'); ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="woosm-workflow-rule-details">
                                    <p><strong><?php esc_html_e('Trigger:', 'wooorder-status-manager'); ?></strong> <?php echo esc_html(ucfirst($rule['trigger_type'])); ?></p>
                                    <?php if ($rule['trigger_condition']) : ?>
                                        <p><strong><?php esc_html_e('Condition:', 'wooorder-status-manager'); ?></strong> <?php echo esc_html($rule['trigger_condition']); ?></p>
                                    <?php endif; ?>
                                    <p><strong><?php esc_html_e('Email Notification:', 'wooorder-status-manager'); ?></strong> 
                                        <?php echo $rule['email_notification'] ? __('Yes', 'wooorder-status-manager') : __('No', 'wooorder-status-manager'); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="woosm-workflow-sidebar">
            <div class="woosm-rule-form">
                <h2><?php esc_html_e('Add New Rule', 'wooorder-status-manager'); ?></h2>
                
                <form method="post" id="woosm-workflow-form">
                    <?php wp_nonce_field('woosm_workflow_action'); ?>
                    <input type="hidden" name="action" value="create_rule">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="from_status"><?php esc_html_e('From Status', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <select id="from_status" name="from_status" class="regular-text" required>
                                    <option value=""><?php esc_html_e('Select Status', 'wooorder-status-manager'); ?></option>
                                    <option value="*"><?php esc_html_e('Any Status', 'wooorder-status-manager'); ?></option>
                                    <option value="pending"><?php esc_html_e('Pending', 'wooorder-status-manager'); ?></option>
                                    <option value="processing"><?php esc_html_e('Processing', 'wooorder-status-manager'); ?></option>
                                    <?php foreach ($statuses as $status) : ?>
                                        <option value="<?php echo esc_attr($status['slug']); ?>">
                                            <?php echo esc_html($status['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="to_status"><?php esc_html_e('To Status', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <select id="to_status" name="to_status" class="regular-text" required>
                                    <option value=""><?php esc_html_e('Select Status', 'wooorder-status-manager'); ?></option>
                                    <?php foreach ($statuses as $status) : ?>
                                        <option value="<?php echo esc_attr($status['slug']); ?>">
                                            <?php echo esc_html($status['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="trigger_type"><?php esc_html_e('Trigger Type', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <select id="trigger_type" name="trigger_type" class="regular-text" required>
                                    <option value="manual"><?php esc_html_e('Manual', 'wooorder-status-manager'); ?></option>
                                    <option value="automatic"><?php esc_html_e('Automatic', 'wooorder-status-manager'); ?></option>
                                    <option value="payment"><?php esc_html_e('Payment Complete', 'wooorder-status-manager'); ?></option>
                                    <option value="stock"><?php esc_html_e('Stock Update', 'wooorder-status-manager'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('When this rule should be triggered', 'wooorder-status-manager'); ?></p>
                            </td>
                        </tr>
                        
                        <tr id="trigger_condition_row" style="display: none;">
                            <th scope="row">
                                <label for="trigger_condition"><?php esc_html_e('Trigger Condition', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <select id="trigger_condition" name="trigger_condition" class="regular-text">
                                    <option value=""><?php esc_html_e('Select Condition', 'wooorder-status-manager'); ?></option>
                                    <option value="in_stock"><?php esc_html_e('All Items In Stock', 'wooorder-status-manager'); ?></option>
                                    <option value="low_stock"><?php esc_html_e('Low Stock Alert', 'wooorder-status-manager'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Additional condition for the trigger', 'wooorder-status-manager'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="email_notification"><?php esc_html_e('Email Notification', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" id="email_notification" name="email_notification" value="1">
                                    <?php esc_html_e('Send email notification when this rule is triggered', 'wooorder-status-manager'); ?>
                                </label>
                            </td>
                        </tr>
                        
                        <tr id="email_template_row" style="display: none;">
                            <th scope="row">
                                <label for="email_template"><?php esc_html_e('Email Template', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <textarea id="email_template" name="email_template" rows="5" class="large-text"></textarea>
                                <p class="description"><?php esc_html_e('Custom email template for this workflow rule', 'wooorder-status-manager'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Create Rule', 'wooorder-status-manager'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/hide trigger condition based on trigger type
    $('#trigger_type').on('change', function() {
        if ($(this).val() === 'stock') {
            $('#trigger_condition_row').show();
        } else {
            $('#trigger_condition_row').hide();
        }
    });
    
    // Show/hide email template based on email notification
    $('#email_notification').on('change', function() {
        if ($(this).is(':checked')) {
            $('#email_template_row').show();
        } else {
            $('#email_template_row').hide();
        }
    });
    
    // Delete rule confirmation
    $('.woosm-delete-rule').on('click', function(e) {
        e.preventDefault();
        
        if (confirm('<?php esc_js(__('Are you sure you want to delete this workflow rule?', 'wooorder-status-manager')); ?>')) {
            var ruleId = $(this).data('rule-id');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'woosm_delete_workflow_rule',
                    rule_id: ruleId,
                    nonce: '<?php echo wp_create_nonce('woosm_admin_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php esc_js(__('An error occurred.', 'wooorder-status-manager')); ?>');
                    }
                },
                error: function() {
                    alert('<?php esc_js(__('An error occurred.', 'wooorder-status-manager')); ?>');
                }
            });
        }
    });
});
</script>
