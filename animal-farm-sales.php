<?php
/**
 * Plugin Name: Animal Farm Sales Display
 * Plugin URI: https://github.com/impact2021/Animal-Farm
 * Description: Display WooCommerce sales in a table with product selection dropdown
 * Version: 1.0.0
 * Author: Animal Farm
 * Author URI: https://github.com/impact2021/Animal-Farm
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Text Domain: animal-farm-sales
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

class Animal_Farm_Sales {
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        add_shortcode('sales_table', array($this, 'render_sales_table'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_get_product_orders', array($this, 'ajax_get_product_orders'));
        add_action('wp_ajax_nopriv_get_product_orders', array($this, 'ajax_get_product_orders'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_admin()) {
            wp_enqueue_style('animal-farm-sales-css', plugin_dir_url(__FILE__) . 'assets/css/sales-table.css', array(), '1.0.0');
            wp_enqueue_script('animal-farm-sales-js', plugin_dir_url(__FILE__) . 'assets/js/sales-table.js', array('jquery'), '1.0.0', true);
            
            wp_localize_script('animal-farm-sales-js', 'animalFarmSales', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('animal_farm_sales_nonce')
            ));
        }
    }
    
    /**
     * Render the sales table shortcode
     */
    public function render_sales_table($atts) {
        $atts = shortcode_atts(array(
            'products' => '', // Optional: comma-separated product IDs
        ), $atts);
        
        ob_start();
        ?>
        <div class="animal-farm-sales-container">
            <div class="product-selector-wrapper">
                <label for="product-selector"><?php _e('Select Product:', 'animal-farm-sales'); ?></label>
                <select id="product-selector" class="product-selector">
                    <option value=""><?php _e('-- Select a Product --', 'animal-farm-sales'); ?></option>
                    <?php echo $this->get_products_dropdown_options($atts['products']); ?>
                </select>
            </div>
            
            <div class="sales-table-wrapper">
                <div class="loading-message" style="display:none;">
                    <?php _e('Loading...', 'animal-farm-sales'); ?>
                </div>
                <div class="no-selection-message">
                    <?php _e('Please select a product to view orders.', 'animal-farm-sales'); ?>
                </div>
                <table class="sales-table" style="display:none;">
                    <thead>
                        <tr>
                            <th><?php _e('Customer Name', 'animal-farm-sales'); ?></th>
                            <th><?php _e('Quantity', 'animal-farm-sales'); ?></th>
                            <th><?php _e('Payment Method', 'animal-farm-sales'); ?></th>
                            <th><?php _e('Payment Status', 'animal-farm-sales'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="sales-table-body">
                    </tbody>
                </table>
                <div class="no-orders-message" style="display:none;">
                    <?php _e('No orders found for this product.', 'animal-farm-sales'); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get products dropdown options
     */
    private function get_products_dropdown_options($product_ids = '') {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        );
        
        // If specific products are specified, filter by them
        if (!empty($product_ids)) {
            $ids = array_map('trim', explode(',', $product_ids));
            $args['post__in'] = $ids;
        }
        
        $products = get_posts($args);
        $options = '';
        
        foreach ($products as $product) {
            $options .= sprintf(
                '<option value="%d">%s</option>',
                $product->ID,
                esc_html($product->post_title)
            );
        }
        
        return $options;
    }
    
    /**
     * AJAX handler to get product orders
     */
    public function ajax_get_product_orders() {
        check_ajax_referer('animal_farm_sales_nonce', 'nonce');
        
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        
        if (!$product_id) {
            wp_send_json_error(array('message' => __('Invalid product ID', 'animal-farm-sales')));
        }
        
        $orders = $this->get_orders_by_product($product_id);
        
        wp_send_json_success(array('orders' => $orders));
    }
    
    /**
     * Get all orders containing a specific product
     */
    private function get_orders_by_product($product_id) {
        global $wpdb;
        
        $orders_data = array();
        
        // Query to get all orders that contain the product
        $order_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT order_items.order_id
            FROM {$wpdb->prefix}woocommerce_order_items as order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
            WHERE order_items.order_item_type = 'line_item'
            AND order_item_meta.meta_key = '_product_id'
            AND order_item_meta.meta_value = %d
        ", $product_id));
        
        if (empty($order_ids)) {
            return $orders_data;
        }
        
        foreach ($order_ids as $order_id) {
            $order = wc_get_order($order_id);
            
            if (!$order) {
                continue;
            }
            
            // Get items from the order
            foreach ($order->get_items() as $item_id => $item) {
                $item_product_id = $item->get_product_id();
                
                // Only include if it matches our product
                if ($item_product_id == $product_id) {
                    $first_name = $order->get_billing_first_name();
                    $last_name = $order->get_billing_last_name();
                    $customer_name = trim($first_name . ' ' . $last_name);
                    
                    if (empty($customer_name)) {
                        $customer_name = __('Guest', 'animal-farm-sales');
                    }
                    
                    $payment_method = $order->get_payment_method_title();
                    if (empty($payment_method)) {
                        $payment_method = __('N/A', 'animal-farm-sales');
                    }
                    
                    $status = wc_get_order_status_name($order->get_status());
                    
                    $orders_data[] = array(
                        'customer_name' => $customer_name,
                        'quantity' => $item->get_quantity(),
                        'payment_method' => $payment_method,
                        'status' => $status,
                        'order_id' => $order_id
                    );
                }
            }
        }
        
        return $orders_data;
    }
}

// Initialize the plugin
new Animal_Farm_Sales();
