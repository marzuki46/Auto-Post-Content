<?php
/**
 * Plugin Name: Auto Post Content AI & Topic Generator
 * Plugin URI: https://github.com/marzuki46/Auto-Post-Content
 * Description: Plugin WordPress canggih untuk riset topik AI, auto-generate artikel SEO mendalam (1 bahasa atau 2 bahasa ID & EN), integrasi 9Router/OpenAI, Entity Keywords, Custom CTA WhatsApp/Link, dan penjadwalan otomatis (jam terjadwal atau random harian).
 * Version: 1.1.7
 * Author: Marzuki Team
 * Text Domain: auto-post-content
 * Domain Path: /languages
 * License: GPL-2.0+
 * Requires PHP: 8.3
 * Requires at least: 6.0
 */

defined('ABSPATH') || exit;

// Proteksi versi PHP minimum 8.3
if (version_compare(PHP_VERSION, '8.3.0', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>' .
            esc_html__('Plugin Auto Post Content memerlukan PHP versi 8.3 ke atas demi keamanan dan performa.', 'auto-post-content') .
            ' ' . sprintf(esc_html__('Versi aktif server Anda: %s', 'auto-post-content'), esc_html(PHP_VERSION)) .
            '</p></div>';
    });
    return;
}

define('APCAI_VERSION', '1.1.7');
define('APCAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('APCAI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('APCAI_PLUGIN_FILE', __FILE__);

require_once APCAI_PLUGIN_DIR . 'includes/class-db.php';
require_once APCAI_PLUGIN_DIR . 'includes/class-ai-client.php';
require_once APCAI_PLUGIN_DIR . 'includes/class-image-manager.php';
require_once APCAI_PLUGIN_DIR . 'includes/class-generator.php';
require_once APCAI_PLUGIN_DIR . 'includes/class-indexer.php';
require_once APCAI_PLUGIN_DIR . 'includes/class-scheduler.php';
require_once APCAI_PLUGIN_DIR . 'includes/class-updater.php';
require_once APCAI_PLUGIN_DIR . 'includes/class-admin.php';

// Activation & Deactivation
register_activation_hook(__FILE__, ['APCAI_DB', 'install']);
register_deactivation_hook(__FILE__, ['APCAI_Scheduler', 'deactivate']);

// Boot
add_action('plugins_loaded', function () {
    load_plugin_textdomain('auto-post-content', false, dirname(plugin_basename(__FILE__)) . '/languages');
    APCAI_Scheduler::init();
    APCAI_Updater::init();
    APCAI_Indexer::init();
    if (is_admin()) {
        APCAI_Admin::init();
    }
});
