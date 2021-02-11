<?php

defined('WP_UNINSTALL_PLUGIN') or exit();

global $wpdb;

// Delete plugin options
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce_roskassa%';");