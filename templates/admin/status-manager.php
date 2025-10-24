<?php
/**
 * Status Manager Admin Template
 *
 * @package WooOrderStatusManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e('Custom Order Statuses', 'wooorder-status-manager'); ?></h1>
    
    <div class="woosm-admin-container">
        <div class="woosm-admin-main">
            <div class="woosm-status-list">
                <h2><?php esc_html_e('Existing Statuses', 'wooorder-status-manager'); ?></h2>
                
                <?php if (empty($statuses)) : ?>
                    <p><?php esc_html_e('No custom statuses found. Create your first status below.', 'wooorder-status-manager'); ?></p>
                <?php else : ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Name', 'wooorder-status-manager'); ?></th>
                                <th><?php esc_html_e('Slug', 'wooorder-status-manager'); ?></th>
                                <th><?php esc_html_e('Color', 'wooorder-status-manager'); ?></th>
                                <th><?php esc_html_e('Icon', 'wooorder-status-manager'); ?></th>
                                <th><?php esc_html_e('Visibility', 'wooorder-status-manager'); ?></th>
                                <th><?php esc_html_e('Workflow Order', 'wooorder-status-manager'); ?></th>
                                <th><?php esc_html_e('Actions', 'wooorder-status-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($statuses as $status) : ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($status['name']); ?></strong>
                                    </td>
                                    <td>
                                        <code><?php echo esc_html($status['slug']); ?></code>
                                    </td>
                                    <td>
                                        <span class="woosm-color-preview" style="background-color: <?php echo esc_attr($status['color']); ?>;"></span>
                                        <?php echo esc_html($status['color']); ?>
                                    </td>
                                    <td>
                                        <?php if ($status['icon']) : ?>
                                            <i class="<?php echo esc_attr($status['icon']); ?>"></i>
                                            <?php echo esc_html($status['icon']); ?>
                                        <?php else : ?>
                                            <span class="woosm-no-icon"><?php esc_html_e('No icon', 'wooorder-status-manager'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(ucfirst($status['visibility'])); ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html($status['workflow_order']); ?>
                                    </td>
                                    <td>
                                        <button class="button button-small woosm-edit-status" data-status-id="<?php echo esc_attr($status['id']); ?>">
                                            <?php esc_html_e('Edit', 'wooorder-status-manager'); ?>
                                        </button>
                                        <form method="post" style="display: inline-block;" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this status?', 'wooorder-status-manager'); ?>');">
                                            <?php wp_nonce_field('woosm_admin_action'); ?>
                                            <input type="hidden" name="action" value="delete_status">
                                            <input type="hidden" name="status_id" value="<?php echo esc_attr($status['id']); ?>">
                                            <button type="submit" class="button button-small button-link-delete">
                                                <?php esc_html_e('Delete', 'wooorder-status-manager'); ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="woosm-admin-sidebar">
            <div class="woosm-status-form">
                <h2><?php esc_html_e('Add New Status', 'wooorder-status-manager'); ?></h2>
                
                <form method="post" id="woosm-status-form">
                    <?php wp_nonce_field('woosm_admin_action'); ?>
                    <input type="hidden" name="action" value="create_status">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="status_name"><?php esc_html_e('Name', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="status_name" name="name" class="regular-text" required>
                                <p class="description"><?php esc_html_e('Display name for the status (e.g., "Packed", "Out for Delivery")', 'wooorder-status-manager'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status_slug"><?php esc_html_e('Slug', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="status_slug" name="slug" class="regular-text" required>
                                <p class="description"><?php esc_html_e('Unique identifier (will be auto-generated from name)', 'wooorder-status-manager'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status_color"><?php esc_html_e('Color', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="status_color" name="color" class="color-picker" value="#0073aa" required>
                                <p class="description"><?php esc_html_e('Color for the status label in admin and frontend', 'wooorder-status-manager'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status_icon"><?php esc_html_e('Icon', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="status_icon" name="icon" class="regular-text" placeholder="fas fa-box">
                                <p class="description">
                                    <?php esc_html_e('FontAwesome icon class (optional)', 'wooorder-status-manager'); ?><br>
                                    <small><?php esc_html_e('Examples: fas fa-box, fas fa-shipping-fast, fas fa-check-circle', 'wooorder-status-manager'); ?></small>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status_visibility"><?php esc_html_e('Visibility', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <select id="status_visibility" name="visibility">
                                    <option value="both"><?php esc_html_e('Both Admin & Customer', 'wooorder-status-manager'); ?></option>
                                    <option value="admin"><?php esc_html_e('Admin Only', 'wooorder-status-manager'); ?></option>
                                    <option value="customer"><?php esc_html_e('Customer Only', 'wooorder-status-manager'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Who can see this status', 'wooorder-status-manager'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status_workflow_order"><?php esc_html_e('Workflow Order', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="status_workflow_order" name="workflow_order" value="0" min="0" class="small-text">
                                <p class="description"><?php esc_html_e('Order in workflow sequence (0 = first)', 'wooorder-status-manager'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="status_email_template"><?php esc_html_e('Email Template', 'wooorder-status-manager'); ?></label>
                            </th>
                            <td>
                                <textarea id="status_email_template" name="email_template" rows="5" class="large-text"></textarea>
                                <p class="description"><?php esc_html_e('Optional email template when this status is set', 'wooorder-status-manager'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e('Create Status', 'wooorder-status-manager'); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Status Modal -->
<div id="woosm-edit-modal" class="woosm-modal" style="display: none;">
    <div class="woosm-modal-content">
        <div class="woosm-modal-header">
            <h3><?php esc_html_e('Edit Status', 'wooorder-status-manager'); ?></h3>
            <span class="woosm-modal-close">&times;</span>
        </div>
        
        <div class="woosm-modal-body">
            <form method="post" id="woosm-edit-form">
                <?php wp_nonce_field('woosm_admin_action'); ?>
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="status_id" id="edit_status_id">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="edit_status_name"><?php esc_html_e('Name', 'wooorder-status-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit_status_name" name="name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edit_status_color"><?php esc_html_e('Color', 'wooorder-status-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit_status_color" name="color" class="color-picker" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edit_status_icon"><?php esc_html_e('Icon', 'wooorder-status-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit_status_icon" name="icon" class="regular-text" placeholder="fas fa-box">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edit_status_visibility"><?php esc_html_e('Visibility', 'wooorder-status-manager'); ?></label>
                        </th>
                        <td>
                            <select id="edit_status_visibility" name="visibility">
                                <option value="both"><?php esc_html_e('Both Admin & Customer', 'wooorder-status-manager'); ?></option>
                                <option value="admin"><?php esc_html_e('Admin Only', 'wooorder-status-manager'); ?></option>
                                <option value="customer"><?php esc_html_e('Customer Only', 'wooorder-status-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edit_status_workflow_order"><?php esc_html_e('Workflow Order', 'wooorder-status-manager'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="edit_status_workflow_order" name="workflow_order" min="0" class="small-text">
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="edit_status_email_template"><?php esc_html_e('Email Template', 'wooorder-status-manager'); ?></label>
                        </th>
                        <td>
                            <textarea id="edit_status_email_template" name="email_template" rows="5" class="large-text"></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php esc_html_e('Update Status', 'wooorder-status-manager'); ?>
                    </button>
                    <button type="button" class="button woosm-modal-close">
                        <?php esc_html_e('Cancel', 'wooorder-status-manager'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>
</div>
