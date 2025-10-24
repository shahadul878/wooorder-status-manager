/**
 * Admin JavaScript for WooOrder Status Manager
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        WOOSM_Admin.init();
    });
    
    // Admin object
    window.WOOSM_Admin = {
        
        init: function() {
            this.initColorPicker();
            this.initModal();
            this.initFormHandlers();
            this.initBulkActions();
            this.initSlugGeneration();
            this.initIconPreview();
        },
        
        // Initialize color picker
        initColorPicker: function() {
            if ($.fn.wpColorPicker) {
                $('.color-picker').wpColorPicker({
                    change: function(event, ui) {
                        $(this).trigger('change');
                    }
                });
            }
        },
        
        // Initialize modal functionality
        initModal: function() {
            // Open modal
            $(document).on('click', '.woosm-edit-status', function(e) {
                e.preventDefault();
                var statusId = $(this).data('status-id');
                WOOSM_Admin.openEditModal(statusId);
            });
            
            // Close modal
            $(document).on('click', '.woosm-modal-close', function(e) {
                e.preventDefault();
                WOOSM_Admin.closeModal();
            });
            
            // Close modal when clicking outside
            $(document).on('click', '.woosm-modal', function(e) {
                if (e.target === this) {
                    WOOSM_Admin.closeModal();
                }
            });
            
            // Close modal with Escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('.woosm-modal').is(':visible')) {
                    WOOSM_Admin.closeModal();
                }
            });
        },
        
        // Open edit modal
        openEditModal: function(statusId) {
            $.ajax({
                url: woosm_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'woosm_get_status',
                    status_id: statusId,
                    nonce: woosm_admin.nonce
                },
                beforeSend: function() {
                    $('#woosm-edit-modal').show();
                    $('#woosm-edit-modal .woosm-modal-body').html('<div class="woosm-loading"><div class="woosm-spinner"></div>Loading...</div>');
                },
                success: function(response) {
                    if (response.success) {
                        var status = response.data;
                        WOOSM_Admin.populateEditForm(status);
                    } else {
                        alert(response.data.message || woosm_admin.strings.error_occurred);
                    }
                },
                error: function() {
                    alert(woosm_admin.strings.error_occurred);
                }
            });
        },
        
        // Populate edit form
        populateEditForm: function(status) {
            $('#edit_status_id').val(status.id);
            $('#edit_status_name').val(status.name);
            $('#edit_status_color').val(status.color);
            $('#edit_status_icon').val(status.icon);
            $('#edit_status_visibility').val(status.visibility);
            $('#edit_status_workflow_order').val(status.workflow_order);
            $('#edit_status_email_template').val(status.email_template);
            
            // Update color picker
            $('#edit_status_color').wpColorPicker('color', status.color);
            
            // Show the form
            $('#woosm-edit-modal .woosm-modal-body').html($('#woosm-edit-modal .woosm-modal-body form').parent().html());
        },
        
        // Close modal
        closeModal: function() {
            $('.woosm-modal').hide();
            $('.woosm-modal .woosm-modal-body').empty();
        },
        
        // Initialize form handlers
        initFormHandlers: function() {
            // AJAX form submission
            $(document).on('submit', '#woosm-status-form', function(e) {
                e.preventDefault();
                WOOSM_Admin.submitStatusForm($(this), 'create');
            });
            
            $(document).on('submit', '#woosm-edit-form', function(e) {
                e.preventDefault();
                WOOSM_Admin.submitStatusForm($(this), 'update');
            });
            
            // Form validation
            $(document).on('input', '#status_name, #edit_status_name', function() {
                var name = $(this).val();
                var slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                $('#status_slug').val(slug);
            });
        },
        
        // Submit status form
        submitStatusForm: function(form, action) {
            var formData = form.serialize();
            var actionName = action === 'create' ? 'woosm_create_status' : 'woosm_update_status';
            
            $.ajax({
                url: woosm_admin.ajax_url,
                type: 'POST',
                data: formData + '&action=' + actionName,
                beforeSend: function() {
                    form.addClass('woosm-loading');
                    form.find('button[type="submit"]').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || woosm_admin.strings.error_occurred);
                    }
                },
                error: function() {
                    alert(woosm_admin.strings.error_occurred);
                },
                complete: function() {
                    form.removeClass('woosm-loading');
                    form.find('button[type="submit"]').prop('disabled', false);
                }
            });
        },
        
        // Initialize bulk actions
        initBulkActions: function() {
            $(document).on('click', '.woosm-bulk-update', function(e) {
                e.preventDefault();
                WOOSM_Admin.showBulkUpdateModal();
            });
        },
        
        // Show bulk update modal
        showBulkUpdateModal: function() {
            var modalHtml = `
                <div class="woosm-modal" id="woosm-bulk-modal" style="display: block;">
                    <div class="woosm-modal-content">
                        <div class="woosm-modal-header">
                            <h3>Bulk Update Orders</h3>
                            <span class="woosm-modal-close">&times;</span>
                        </div>
                        <div class="woosm-modal-body">
                            <form id="woosm-bulk-form">
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="bulk_order_ids">Order IDs</label>
                                        </th>
                                        <td>
                                            <textarea id="bulk_order_ids" name="order_ids" rows="5" class="large-text" placeholder="Enter order IDs separated by commas or new lines"></textarea>
                                            <p class="description">Enter order IDs separated by commas or new lines</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="bulk_status_slug">New Status</label>
                                        </th>
                                        <td>
                                            <select id="bulk_status_slug" name="status_slug" class="regular-text">
                                                <option value="">Select Status</option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                                <p class="submit">
                                    <button type="submit" class="button button-primary">Update Orders</button>
                                    <button type="button" class="button woosm-modal-close">Cancel</button>
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            // Load statuses for dropdown
            WOOSM_Admin.loadStatusesForBulk();
        },
        
        // Load statuses for bulk update
        loadStatusesForBulk: function() {
            $.ajax({
                url: woosm_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'woosm_get_custom_statuses',
                    nonce: woosm_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var select = $('#bulk_status_slug');
                        $.each(response.data, function(index, status) {
                            select.append('<option value="' + status.slug + '">' + status.name + '</option>');
                        });
                    }
                }
            });
        },
        
        // Initialize slug generation
        initSlugGeneration: function() {
            $('#status_name').on('input', function() {
                var name = $(this).val();
                var slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                $('#status_slug').val(slug);
            });
        },
        
        // Initialize icon preview
        initIconPreview: function() {
            $('#status_icon, #edit_status_icon').on('input', function() {
                var icon = $(this).val();
                var preview = $(this).siblings('.woosm-icon-preview');
                
                if (preview.length === 0) {
                    preview = $('<span class="woosm-icon-preview"></span>');
                    $(this).after(preview);
                }
                
                if (icon) {
                    preview.html('<i class="' + icon + '"></i>');
                } else {
                    preview.empty();
                }
            });
        },
        
        // Show notification
        showNotification: function(message, type) {
            type = type || 'success';
            
            var notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after(notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notification.fadeOut();
            }, 5000);
        },
        
        // Confirm action
        confirmAction: function(message, callback) {
            if (confirm(message)) {
                callback();
            }
        }
    };
    
    // Bulk form submission
    $(document).on('submit', '#woosm-bulk-form', function(e) {
        e.preventDefault();
        
        var orderIds = $('#bulk_order_ids').val().split(/[,\n]/).map(function(id) {
            return id.trim();
        }).filter(function(id) {
            return id.length > 0;
        });
        
        var statusSlug = $('#bulk_status_slug').val();
        
        if (orderIds.length === 0) {
            alert('Please enter at least one order ID.');
            return;
        }
        
        if (!statusSlug) {
            alert('Please select a status.');
            return;
        }
        
        $.ajax({
            url: woosm_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'woosm_bulk_update_orders',
                order_ids: orderIds,
                status_slug: statusSlug,
                nonce: woosm_admin.nonce
            },
            beforeSend: function() {
                $('#woosm-bulk-form button[type="submit"]').prop('disabled', true).text('Updating...');
            },
            success: function(response) {
                if (response.success) {
                    WOOSM_Admin.showNotification(response.data.message, 'success');
                    $('#woosm-bulk-modal').remove();
                } else {
                    alert(response.data.message || woosm_admin.strings.error_occurred);
                }
            },
            error: function() {
                alert(woosm_admin.strings.error_occurred);
            },
            complete: function() {
                $('#woosm-bulk-form button[type="submit"]').prop('disabled', false).text('Update Orders');
            }
        });
    });
    
})(jQuery);
