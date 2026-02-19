# Animal Farm Sales Display

A WordPress plugin for WooCommerce that displays sales data in a table format with product selection.

## Description

This plugin provides a shortcode `[sales_table]` that displays WooCommerce order information for selected products. Perfect for displaying ticket sales or any product sales data on a public page.

## Features

- Product dropdown selector
- Sales data table with:
  - Customer name (first name + last name)
  - Quantity purchased
  - Payment method
  - Payment status (On Hold, Processing, Completed, etc.)
- AJAX-powered for smooth user experience
- Responsive design for mobile devices
- Color-coded payment statuses

## Installation

1. Upload the plugin files to the `/wp-content/plugins/animal-farm-sales` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure WooCommerce is installed and active.

## Usage

### Basic Usage

Add the shortcode to any page or post:

```
[sales_table]
```

This will display a dropdown with all published products and a table that updates when a product is selected.

### Advanced Usage

You can limit the products shown in the dropdown by specifying product IDs:

```
[sales_table products="123,456,789"]
```

This will only show products with IDs 123, 456, and 789 in the dropdown.

## Requirements

- WordPress 5.0 or higher
- WooCommerce plugin installed and active
- PHP 7.0 or higher

## Files

- `animal-farm-sales.php` - Main plugin file
- `assets/css/sales-table.css` - Stylesheet for the sales table
- `assets/js/sales-table.js` - JavaScript for AJAX functionality

## Support

For issues and questions, please visit the [GitHub repository](https://github.com/impact2021/Animal-Farm).
