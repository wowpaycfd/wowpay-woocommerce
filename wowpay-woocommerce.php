<?php
/**
 * Plugin Name: WooCommerce WowPay Gateway
 * Plugin URI: https://github.com/wowpaycfd/wowpay-woocommerce
 * Description: Accept payments through WowPay payment gateway.
 * Version: 1.0.0
 * Author: Chinuchai
 * Author URI: https://wowpay.cfd
 * Text Domain: wowpay-woocommerce
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 7.0
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WOWPAY_WC_VERSION', '1.0.0');
define('WOWPAY_WC_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WOWPAY_WC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WOWPAY_WC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', function() {
        echo '<div class="error notice"><p>' . esc_html__('WooCommerce WowPay Gateway requires WooCommerce to be installed and active.', 'wowpay-woocommerce') . '</p></div>';
    });
    return;
}

// Load the main payment gateway class
add_action('plugins_loaded', 'wowpay_wc_init_gateway', 0);
function wowpay_wc_init_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Load required files
    require_once WOWPAY_WC_PLUGIN_PATH . 'includes/class-wowpay-gateway.php';
    require_once WOWPAY_WC_PLUGIN_PATH . 'includes/class-wowpay-webhook-handler.php';
    require_once WOWPAY_WC_PLUGIN_PATH . 'includes/wowpay-functions.php';

    // Register the gateway
    add_filter('woocommerce_payment_gateways', 'wowpay_wc_add_gateway');
    function wowpay_wc_add_gateway($methods) {
        $methods[] = 'WC_WowPay_Gateway';
        return $methods;
    }
}

// Add settings link
add_filter('plugin_action_links_' . WOWPAY_WC_PLUGIN_BASENAME, 'wowpay_wc_plugin_action_links');
function wowpay_wc_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=wowpay') . '">' . __('Settings', 'wowpay-woocommerce') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// Register webhook endpoint
add_action('init', 'wowpay_wc_register_webhook_endpoint');
function wowpay_wc_register_webhook_endpoint() {
    add_rewrite_endpoint('wowpay-webhook', EP_ROOT);
}

// Flush rewrite rules on activation
register_activation_hook(__FILE__, 'wowpay_wc_activate');
function wowpay_wc_activate() {
    wowpay_wc_register_webhook_endpoint();
    flush_rewrite_rules();
}

// Flush rewrite rules on deactivation
register_deactivation_hook(__FILE__, 'wowpay_wc_deactivate');
function wowpay_wc_deactivate() {
    flush_rewrite_rules();
}