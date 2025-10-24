# WooOrder Status Manager

A powerful WordPress plugin that extends WooCommerce with custom order statuses, workflow automation, and customer tracking features.

## Features

### Free Version
- ✅ Create unlimited custom order statuses with colors and icons
- ✅ Customer-facing order tracking with timeline view
- ✅ Email notifications for status changes
- ✅ Basic workflow automation
- ✅ WooCommerce admin integration
- ✅ Shortcode support for tracking pages
- ✅ Responsive design for mobile devices

### Pro Version
- ✅ Advanced workflow automation with conditional logic
- ✅ SMS notifications (Twilio, Nexmo, MessageBird support)
- ✅ Bulk order operations and export/import
- ✅ Dashboard widgets with analytics
- ✅ WooCommerce Subscriptions integration
- ✅ WooCommerce Bookings integration
- ✅ Multi-vendor marketplace support
- ✅ REST API endpoints
- ✅ Advanced email templates
- ✅ Export/import functionality
- ✅ License management system

## Installation

1. Upload the plugin files to `/wp-content/plugins/wooorder-status-manager/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Ensure WooCommerce is installed and activated
4. Go to 'Order Statuses' in your WordPress admin menu

## Quick Start

### Creating Custom Statuses

1. Navigate to **Order Statuses > Custom Statuses**
2. Click "Add New Status"
3. Fill in the status details:
   - **Name**: Display name (e.g., "Packed", "Out for Delivery")
   - **Slug**: Unique identifier (auto-generated from name)
   - **Color**: Hex color code for status labels
   - **Icon**: FontAwesome icon class (optional)
   - **Visibility**: Who can see this status (admin/customer/both)
   - **Workflow Order**: Sequence in workflow
   - **Email Template**: Custom email template (optional)

### Setting Up Workflows

1. Go to **Order Statuses > Workflow Rules**
2. Create rules to automate status transitions:
   - **From Status**: Starting status (or "Any Status")
   - **To Status**: Target status
   - **Trigger Type**: When to trigger (manual/automatic/payment/stock)
   - **Email Notification**: Send email when triggered
   - **Email Template**: Custom email content

### Customer Tracking

Enable customer tracking in **Order Statuses > Settings**:
- ✅ Enable customer-facing order tracking
- ✅ Show order timeline
- ✅ Display order items
- ✅ Custom tracking page title

## Use Cases

### Local Pickup Stores
```
Order Received → Packed → Ready for Pickup → Collected
```

### Food Delivery
```
Order Received → Preparing → Out for Delivery → Delivered
```

### B2B Wholesalers
```
Pending Approval → Confirmed → Packed → Shipped → Delivered
```

### Subscription Products
Automate status changes on renewal or failed payments.

## Shortcodes

### Order Tracking
```
[woosm_order_tracking order_key="YOUR_ORDER_KEY"]
[woosm_order_tracking order_id="ORDER_ID"]
```

### Order Timeline
```
[woosm_order_timeline order_key="YOUR_ORDER_KEY"]
[woosm_order_timeline order_id="ORDER_ID"]
```

## Tracking URLs

### Default Format
```
https://yoursite.com/order-tracking/ORDER_KEY
```

### Query Parameter Format
```
https://yoursite.com/?woosm_track=ORDER_KEY
```

## API Endpoints (Pro Version)

### Custom Statuses
```
GET    /wp-json/woosmp/v1/statuses
POST   /wp-json/woosmp/v1/statuses
GET    /wp-json/woosmp/v1/statuses/{id}
PUT    /wp-json/woosmp/v1/statuses/{id}
DELETE /wp-json/woosmp/v1/statuses/{id}
```

### Orders
```
GET /wp-json/woosmp/v1/orders/{id}/status
PUT /wp-json/woosmp/v1/orders/{id}/status
GET /wp-json/woosmp/v1/orders/{id}/timeline
```

### Bulk Operations
```
POST /wp-json/woosmp/v1/orders/bulk-status
```

## Email Templates

Use these placeholders in email templates:
- `{customer_name}` - Customer's full name
- `{order_number}` - Order number
- `{status_name}` - Current status name
- `{order_date}` - Order creation date
- `{order_total}` - Order total amount
- `{tracking_url}` - Order tracking URL
- `{site_name}` - Website name
- `{site_url}` - Website URL

## SMS Notifications (Pro)

### Supported Providers
- **Twilio**: SMS via Twilio API
- **Nexmo**: SMS via Nexmo API
- **MessageBird**: SMS via MessageBird API
- **Custom Webhook**: Custom SMS provider integration

### SMS Template Placeholders
- `{customer_name}` - Customer's first name
- `{order_number}` - Order number
- `{status_name}` - Status name
- `{tracking_url}` - Tracking URL
- `{site_name}` - Website name

## Hooks and Filters

### Actions
```php
// Triggered when order status changes
do_action('woosm_status_changed', $order_id, $old_status, $new_status);

// Triggered when workflow rule is executed
do_action('woosmp_workflow_rule_executed', $order_id, $rule);
```

### Filters
```php
// Modify order statuses in WooCommerce
add_filter('wc_order_statuses', 'your_custom_function');

// Include custom statuses in reports
add_filter('woocommerce_reports_order_statuses', 'your_custom_function');
```

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Support

- **Documentation**: [GitHub Repository](https://github.com/shahadul878/wooorder-status-manager)
- **Support Email**: shahadul.islam1@gmail.com
- **WordPress.org**: [Plugin Page](https://wordpress.org/plugins/wooorder-status-manager/)

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Custom order status creation and management
- Workflow automation with triggers
- Customer tracking page with timeline view
- Email notifications system
- Bulk order operations
- Admin integration with WooCommerce
- Responsive frontend design
- Shortcode support
- REST API endpoints

## Credits

Developed by **H M Shahadul Islam**  
Author URI: https://github.com/shahadul878  
Company: Codereyes
