/**
 * Frontend JavaScript for WooOrder Status Manager
 */

(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        WOOSM_Frontend.init();
    });
    
    // Frontend object
    window.WOOSM_Frontend = {
        
        init: function() {
            this.initTrackingForm();
            this.initTimelineUpdates();
            this.initResponsiveTimeline();
        },
        
        // Initialize tracking form
        initTrackingForm: function() {
            $('#woosm-tracking-form').on('submit', function(e) {
                e.preventDefault();
                WOOSM_Frontend.trackOrder();
            });
        },
        
        // Track order
        trackOrder: function() {
            var orderKey = $('#woosm-order-key').val().trim();
            
            if (!orderKey) {
                WOOSM_Frontend.showError('Please enter your order key.');
                return;
            }
            
            $.ajax({
                url: woosm_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'woosm_track_order',
                    order_key: orderKey,
                    nonce: woosm_frontend.nonce
                },
                beforeSend: function() {
                    WOOSM_Frontend.showLoading();
                },
                success: function(response) {
                    if (response.success) {
                        WOOSM_Frontend.displayOrderInfo(response.data);
                        WOOSM_Frontend.loadOrderTimeline(response.data.order_id);
                    } else {
                        WOOSM_Frontend.showError(response.data.message || woosm_frontend.strings.tracking_not_found);
                    }
                },
                error: function() {
                    WOOSM_Frontend.showError('An error occurred. Please try again.');
                },
                complete: function() {
                    WOOSM_Frontend.hideLoading();
                }
            });
        },
        
        // Display order information
        displayOrderInfo: function(orderData) {
            var statusHtml = '';
            if (orderData.status.icon) {
                statusHtml = '<i class="' + orderData.status.icon + '"></i> ';
            }
            statusHtml += orderData.status.name;
            
            var orderInfoHtml = `
                <div class="woosm-order-info">
                    <h3>Order Information</h3>
                    <div class="woosm-order-details">
                        <div class="woosm-order-detail">
                            <div class="woosm-order-detail-label">Order Number</div>
                            <div class="woosm-order-detail-value">#${orderData.order_number}</div>
                        </div>
                        <div class="woosm-order-detail">
                            <div class="woosm-order-detail-label">Order Date</div>
                            <div class="woosm-order-detail-value">${orderData.order_date}</div>
                        </div>
                        <div class="woosm-order-detail">
                            <div class="woosm-order-detail-label">Total Amount</div>
                            <div class="woosm-order-detail-value">${orderData.order_total}</div>
                        </div>
                    </div>
                </div>
                
                <div class="woosm-current-status">
                    <span class="woosm-status-badge" style="background-color: ${orderData.status.color};">
                        ${statusHtml}
                    </span>
                </div>
            `;
            
            $('#woosm-order-info').html(orderInfoHtml).show();
        },
        
        // Load order timeline
        loadOrderTimeline: function(orderId) {
            $.ajax({
                url: woosm_frontend.ajax_url,
                type: 'POST',
                data: {
                    action: 'woosm_get_order_timeline',
                    order_id: orderId,
                    nonce: woosm_frontend.nonce
                },
                success: function(response) {
                    if (response.success) {
                        WOOSM_Frontend.displayTimeline(response.data);
                    }
                },
                error: function() {
                    console.log('Failed to load timeline');
                }
            });
        },
        
        // Display timeline
        displayTimeline: function(timeline) {
            if (!timeline || timeline.length === 0) {
                return;
            }
            
            var timelineHtml = '<div class="woosm-timeline"><h4>Order Timeline</h4><div class="woosm-timeline-list">';
            
            $.each(timeline, function(index, item) {
                var isLast = index === timeline.length - 1;
                var statusName = item.status_name || item.to_status.charAt(0).toUpperCase() + item.to_status.slice(1);
                var statusIcon = item.status_icon ? '<i class="' + item.status_icon + '"></i> ' : '';
                var statusColor = item.status_color || '#999';
                var date = new Date(item.created_at);
                var formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                
                timelineHtml += `
                    <div class="woosm-timeline-item ${isLast ? 'current' : 'completed'}">
                        <div class="woosm-timeline-content">
                            <div class="woosm-timeline-header">
                                <div class="woosm-timeline-status" style="color: ${statusColor};">
                                    ${statusIcon}${statusName}
                                </div>
                                <div class="woosm-timeline-date">${formattedDate}</div>
                            </div>
                            ${item.change_reason ? '<div class="woosm-timeline-note">' + item.change_reason + '</div>' : ''}
                        </div>
                    </div>
                `;
            });
            
            timelineHtml += '</div></div>';
            
            $('#woosm-timeline').html(timelineHtml).show();
        },
        
        // Initialize timeline updates
        initTimelineUpdates: function() {
            // Auto-refresh timeline every 30 seconds if on tracking page
            if (window.location.pathname.includes('order-tracking')) {
                setInterval(function() {
                    var orderId = $('#woosm-order-info').data('order-id');
                    if (orderId) {
                        WOOSM_Frontend.loadOrderTimeline(orderId);
                    }
                }, 30000);
            }
        },
        
        // Initialize responsive timeline
        initResponsiveTimeline: function() {
            // Handle timeline responsiveness
            $(window).on('resize', function() {
                WOOSM_Frontend.adjustTimelineForMobile();
            });
            
            WOOSM_Frontend.adjustTimelineForMobile();
        },
        
        // Adjust timeline for mobile
        adjustTimelineForMobile: function() {
            if ($(window).width() < 768) {
                $('.woosm-timeline-item').addClass('mobile-view');
            } else {
                $('.woosm-timeline-item').removeClass('mobile-view');
            }
        },
        
        // Show loading state
        showLoading: function() {
            var loadingHtml = `
                <div class="woosm-loading">
                    <div class="woosm-spinner"></div>
                    ${woosm_frontend.strings.loading}
                </div>
            `;
            
            $('#woosm-order-info, #woosm-timeline').hide();
            $('#woosm-loading').html(loadingHtml).show();
        },
        
        // Hide loading state
        hideLoading: function() {
            $('#woosm-loading').hide();
        },
        
        // Show error message
        showError: function(message) {
            var errorHtml = `
                <div class="woosm-error-message">
                    ${message}
                </div>
            `;
            
            $('#woosm-order-info, #woosm-timeline').hide();
            $('#woosm-loading').html(errorHtml).show();
            
            // Auto-hide error after 5 seconds
            setTimeout(function() {
                $('#woosm-loading').fadeOut();
            }, 5000);
        },
        
        // Show success message
        showSuccess: function(message) {
            var successHtml = `
                <div class="woosm-success-message">
                    ${message}
                </div>
            `;
            
            $('#woosm-loading').html(successHtml).show();
            
            // Auto-hide success after 3 seconds
            setTimeout(function() {
                $('#woosm-loading').fadeOut();
            }, 3000);
        },
        
        // Smooth scroll to element
        scrollToElement: function(selector) {
            $('html, body').animate({
                scrollTop: $(selector).offset().top - 100
            }, 500);
        },
        
        // Format date
        formatDate: function(dateString) {
            var date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        },
        
        // Validate order key format
        validateOrderKey: function(orderKey) {
            // Basic validation - adjust based on your order key format
            return orderKey.length >= 8 && /^[a-zA-Z0-9-_]+$/.test(orderKey);
        }
    };
    
    // Handle direct order tracking from URL parameters
    $(document).ready(function() {
        var urlParams = new URLSearchParams(window.location.search);
        var orderKey = urlParams.get('woosm_track');
        
        if (orderKey && $('#woosm-tracking-form').length) {
            $('#woosm-order-key').val(orderKey);
            WOOSM_Frontend.trackOrder();
        }
    });
    
    // Handle browser back/forward navigation
    window.addEventListener('popstate', function(event) {
        if (event.state && event.state.orderKey) {
            $('#woosm-order-key').val(event.state.orderKey);
            WOOSM_Frontend.trackOrder();
        }
    });
    
})(jQuery);
