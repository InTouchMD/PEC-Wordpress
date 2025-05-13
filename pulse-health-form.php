<?php
/**
 * Plugin Name: Pulse Health Webform
 * Description: Embed a customizable Pulse Health form and post submissions to Pulse Health API.
 * Version: 1.0.1
 * Author: Pulse Health
 */

defined('ABSPATH') or die();

function pulse_health_enqueue_assets() {
    wp_enqueue_style('pulse-health-form-style', plugin_dir_url(__FILE__) . 'assets/css/style.css');

    if (file_exists(get_stylesheet_directory() . '/pulse-health-form-override.css')) {
        wp_enqueue_style('pulse-health-form-override', get_stylesheet_directory_uri() . '/pulse-health-form-override.css');
    }

    wp_enqueue_script('pulse-health-form-js', plugin_dir_url(__FILE__) . 'assets/js/form.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'pulse_health_enqueue_assets');

function pulse_health_activate_plugin() {
    add_option('pulse_health_do_activation_redirect', true);
}
register_activation_hook(__FILE__, 'pulse_health_activate_plugin');

function pulse_health_redirect_to_settings() {
    if (get_option('pulse_health_do_activation_redirect', false)) {
        delete_option('pulse_health_do_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            wp_safe_redirect(admin_url('options-general.php?page=pulse-health'));
            exit;
        }
    }
}
add_action('admin_init', 'pulse_health_redirect_to_settings');

function pulse_health_plugin_action_links($links) {
    $settings_link = '<a href="options-general.php?page=pulse-health">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'pulse_health_plugin_action_links');

require_once plugin_dir_path(__FILE__) . 'admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'form-render.php';
