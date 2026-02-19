/**
 * Animal Farm Sales Table JavaScript
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Handle product selection change
        $('#product-selector').on('change', function() {
            var productId = $(this).val();
            
            if (!productId) {
                // Reset to initial state
                $('.sales-table').hide();
                $('.no-orders-message').hide();
                $('.no-selection-message').show();
                return;
            }
            
            // Show loading message
            $('.no-selection-message').hide();
            $('.sales-table').hide();
            $('.no-orders-message').hide();
            $('.loading-message').show();
            
            // Make AJAX request
            $.ajax({
                url: animalFarmSales.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_product_orders',
                    product_id: productId,
                    nonce: animalFarmSales.nonce
                },
                success: function(response) {
                    $('.loading-message').hide();
                    
                    if (response.success && response.data.orders && response.data.orders.length > 0) {
                        displayOrders(response.data.orders);
                        $('.sales-table').show();
                    } else {
                        $('.no-orders-message').show();
                    }
                },
                error: function(xhr, status, error) {
                    $('.loading-message').hide();
                    $('.no-orders-message').text('Error loading orders. Please try again.').show();
                    console.error('AJAX Error:', error);
                }
            });
        });
        
        /**
         * Display orders in the table
         */
        function displayOrders(orders) {
            var tbody = $('#sales-table-body');
            tbody.empty();
            
            $.each(orders, function(index, order) {
                var statusSlug = order.status.toLowerCase().replace(/\s+/g, '-');
                var row = $('<tr>').attr('data-status', statusSlug);
                
                row.append($('<td>').text(order.customer_name));
                row.append($('<td>').text(order.quantity));
                row.append($('<td>').text(order.payment_method));
                row.append($('<td>').text(order.status));
                
                tbody.append(row);
            });
        }
    });
    
})(jQuery);
