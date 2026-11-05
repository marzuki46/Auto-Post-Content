<?php
defined('ABSPATH') || exit;

class APCAI_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menus']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('admin_init', [__CLASS__, 'send_anti_cache_headers']);

        // AJAX handlers
        add_action('wp_ajax_apcai_test_api', [__CLASS__, 'ajax_test_api']);
        add_action('wp_ajax_apcai_save_settings', [__CLASS__, 'ajax_save_settings']);
        add_action('wp_ajax_apcai_brainstorm_topics', [__CLASS__, 'ajax_brainstorm_topics']);
        add_action('wp_ajax_apcai_generate_outline_now', [__CLASS__, 'ajax_generate_outline_now']);
        add_action('wp_ajax_apcai_generate_now', [__CLASS__, 'ajax_generate_now']);
        add_action('wp_ajax_apcai_translate_en_now', [__CLASS__, 'ajax_translate_en_now']);
        add_action('wp_ajax_apcai_translate_ms_now', [__CLASS__, 'ajax_translate_ms_now']);
        add_action('wp_ajax_apcai_publish_article', [__CLASS__, 'ajax_publish_article']);
        add_action('wp_ajax_apcai_delete_topic', [__CLASS__, 'ajax_delete_topic']);
        add_action('wp_ajax_apcai_trigger_cron_now', [__CLASS__, 'ajax_trigger_cron_now']);
        
        // Target Landing Pages (Money Pages) AJAX
        add_action('wp_ajax_apcai_save_landing_page', [__CLASS__, 'ajax_save_landing_page']);
        add_action('wp_ajax_apcai_delete_landing_page', [__CLASS__, 'ajax_delete_landing_page']);

        // Check GitHub Update AJAX
        add_action('wp_ajax_apcai_check_update_now', [__CLASS__, 'ajax_check_update_now']);

        // Pool Image Upload & Delete AJAX
        add_action('wp_ajax_apcai_upload_pool_images', [__CLASS__, 'ajax_upload_pool_images']);
        add_action('wp_ajax_apcai_delete_pool_image', [__CLASS__, 'ajax_delete_pool_image']);

        // Backfill Missing SEO Focus Keywords (Posts & Tags)
        add_action('wp_ajax_apcai_scan_missing_keywords', [__CLASS__, 'ajax_scan_missing_keywords']);
        add_action('wp_ajax_apcai_fix_single_keyword', [__CLASS__, 'ajax_fix_single_keyword']);

        // Clear Activity Logs AJAX
        add_action('wp_ajax_apcai_clear_logs', [__CLASS__, 'ajax_clear_logs']);

        // Filter kolom di All Posts WP
        add_filter('manage_posts_columns', [__CLASS__, 'add_custom_post_columns']);
        add_action('manage_posts_custom_column', [__CLASS__, 'render_custom_post_columns'], 10, 2);
    }

    public static function register_menus() {
        add_menu_page(
            'Auto Post AI',
            'Auto Post AI',
            'manage_options',
            'apcai-dashboard',
            [__CLASS__, 'page_dashboard'],
            'dashicons-superhero-alt',
            26
        );

        add_submenu_page(
            'apcai-dashboard',
            'AI Content Studio',
            'Content Studio',
            'manage_options',
            'apcai-studio',
            [__CLASS__, 'page_studio']
        );

        add_submenu_page(
            'apcai-dashboard',
            'Topics & Funneling',
            'Topics & Funneling',
            'manage_options',
            'apcai-topics',
            [__CLASS__, 'page_topics']
        );

        add_submenu_page(
            'apcai-dashboard',
            'Target Landing Pages',
            'Landing Pages',
            'manage_options',
            'apcai-landing-pages',
            [__CLASS__, 'page_landing_pages']
        );

        add_submenu_page(
            'apcai-dashboard',
            'Auto Scheduler',
            'Scheduler',
            'manage_options',
            'apcai-schedule',
            [__CLASS__, 'page_schedule']
        );

        add_submenu_page(
            'apcai-dashboard',
            'Media Photo Pool',
            'Photo Pool',
            'manage_options',
            'apcai-gallery',
            [__CLASS__, 'page_gallery']
        );

        add_submenu_page(
            'apcai-dashboard',
            'Tag Copywriting Hub',
            'Tag Copywriting',
            'manage_options',
            'apcai-tags',
            [__CLASS__, 'page_tags']
        );

        add_submenu_page(
            'apcai-dashboard',
            'AI & System Settings',
            'Settings',
            'manage_options',
            'apcai-settings',
            [__CLASS__, 'page_settings']
        );

        add_submenu_page(
            'apcai-dashboard',
            'Activity Logs',
            'Logs',
            'manage_options',
            'apcai-logs',
            [__CLASS__, 'page_logs']
        );
    }

    public static function send_anti_cache_headers() {
        $page = sanitize_text_field(wp_unslash($_GET['page'] ?? ''));
        if (is_admin() && !empty($page) && str_starts_with($page, 'apcai')) {
            if (!headers_sent()) {
                nocache_headers();
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
                header('Pragma: no-cache');
                header('Expires: Wed, 11 Jan 1984 05:00:00 GMT');
                header('X-LiteSpeed-Cache-Control: no-cache');
            }
            if (defined('LSCWP_V')) {
                do_action('litespeed_control_set_nocache', 'APC AI Admin Anti Cache');
            }
        }
    }

    public static function enqueue_assets($hook) {
        if (strpos($hook, 'apcai') === false) {
            return;
        }

        $css_file = APCAI_PLUGIN_DIR . 'assets/css/admin.css';
        $js_file  = APCAI_PLUGIN_DIR . 'assets/js/admin.js';
        $ver_css  = file_exists($css_file) ? (string) filemtime($css_file) : APCAI_VERSION;
        $ver_js   = file_exists($js_file) ? (string) filemtime($js_file) : APCAI_VERSION;

        wp_enqueue_style('apcai-admin-style', APCAI_PLUGIN_URL . 'assets/css/admin.css', [], $ver_css);
        wp_enqueue_script('apcai-admin-script', APCAI_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], $ver_js, true);

        wp_localize_script('apcai-admin-script', 'apcai_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('apcai_admin_nonce'),
        ]);
    }

    // ----------------------------------------------------
    // UI Pages
    // ----------------------------------------------------
    public static function page_dashboard() {
        global $wpdb;
        $t_topics  = APCAI_DB::table_topics();
        $t_rel     = APCAI_DB::table_posts_meta();
        $t_landing = APCAI_DB::table_landing_pages();

        $topics_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $t_topics WHERE status = 'idea'");
        $posts_id_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $t_rel WHERE language = 'id'");
        $posts_en_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $t_rel WHERE language = 'en'");
        $lp_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $t_landing WHERE status = 'active'");
        
        $tofu_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $t_rel WHERE funnel_stage = 'tofu'");
        $mofu_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $t_rel WHERE funnel_stage = 'mofu'");
        $bofu_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $t_rel WHERE funnel_stage = 'bofu'");

        $settings = get_option('apcai_settings', []);
        $scheduled_slots = get_option('apcai_scheduled_slots_today', []);

        include APCAI_PLUGIN_DIR . 'views/dashboard.php';
    }

    public static function page_studio() {
        $settings = get_option('apcai_settings', []);
        $categories = get_categories(['hide_empty' => false]);
        include APCAI_PLUGIN_DIR . 'views/studio.php';
    }

    public static function page_topics() {
        global $wpdb;
        $t_topics = APCAI_DB::table_topics();
        $topics = $wpdb->get_results("SELECT * FROM $t_topics ORDER BY id DESC LIMIT 100");
        $categories = get_categories(['hide_empty' => false]);
        include APCAI_PLUGIN_DIR . 'views/topics.php';
    }

    public static function page_landing_pages() {
        global $wpdb;
        $t_landing = APCAI_DB::table_landing_pages();
        $landing_pages = $wpdb->get_results("SELECT * FROM $t_landing ORDER BY id DESC");
        $categories = get_categories(['hide_empty' => false]);
        include APCAI_PLUGIN_DIR . 'views/landing-pages.php';
    }

    public static function page_schedule() {
        wp_cache_delete('apcai_settings', 'options');
        $settings = get_option('apcai_settings', []);
        $slots = get_option('apcai_scheduled_slots_today', []);
        $categories = get_categories(['hide_empty' => false]);
        include APCAI_PLUGIN_DIR . 'views/schedule.php';
    }

    public static function page_gallery() {
        $pool_ids = get_option('apcai_custom_images_pool', []);
        $pool_images = [];
        foreach ($pool_ids as $id) {
            $url = wp_get_attachment_image_url($id, 'medium');
            if ($url) {
                $file = get_attached_file($id);
                $size = file_exists($file) ? size_format(filesize($file)) : 'WebP';
                $pool_images[] = [
                    'id'   => $id,
                    'url'  => $url,
                    'size' => $size,
                ];
            }
        }
        include APCAI_PLUGIN_DIR . 'views/gallery.php';
    }

    public static function page_tags() {
        include APCAI_PLUGIN_DIR . 'views/tags.php';
    }

    public static function page_settings() {
        wp_cache_delete('apcai_settings', 'options');
        $settings = get_option('apcai_settings', []);
        include APCAI_PLUGIN_DIR . 'views/settings.php';
    }

    public static function page_logs() {
        global $wpdb;
        $t_logs = APCAI_DB::table_logs();
        $logs = $wpdb->get_results("SELECT * FROM $t_logs ORDER BY id DESC LIMIT 100");
        include APCAI_PLUGIN_DIR . 'views/logs.php';
    }

    public static function ajax_clear_logs() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $t_logs = APCAI_DB::table_logs();
        $wpdb->query("TRUNCATE TABLE $t_logs");

        wp_send_json_success('Semua riwayat log berhasil dibersihkan.');
    }

    // ----------------------------------------------------
    // AJAX Controllers
    // ----------------------------------------------------
    public static function ajax_test_api() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $res = APCAI_AI_Client::test_connection();
        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success($res);
    }

    public static function ajax_save_settings() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        if (!headers_sent()) {
            nocache_headers();
            header('X-LiteSpeed-Cache-Control: no-cache');
        }
        if (defined('LSCWP_V')) {
            do_action('litespeed_control_set_nocache', 'APC AI Settings Save Ajax');
        }

        // Hapus cache object options agar selalu sinkron dengan database
        wp_cache_delete('apcai_settings', 'options');
        $current = get_option('apcai_settings', []);
        $input = $_POST['settings'] ?? [];

        foreach ($input as $k => $v) {
            if (is_array($v)) {
                $current[$k] = $v;
            } elseif (in_array($k, ['keyword_prompt', 'content_prompt', 'cta_instruction', 'angle', 'knowledge_data'])) {
                $current[$k] = sanitize_textarea_field(wp_unslash($v));
            } else {
                $current[$k] = sanitize_text_field(wp_unslash($v));
            }
        }

        // HANYA update boolean keys jika kuncinya dikirim oleh form yang bersangkutan
        $boolean_keys = ['schedule_enabled', 'auto_research_topics', 'auto_thumbnail', 'enable_internal_links', 'enable_instant_indexing', 'enable_cognitive_engine'];
        foreach ($boolean_keys as $b_key) {
            if (array_key_exists($b_key, $input)) {
                $current[$b_key] = (!empty($input[$b_key]) && $input[$b_key] !== '0') ? '1' : '0';
            }
        }

        // Target Word Count Range (hanya jika ada di input)
        if (isset($input['word_count_min'])) {
            $current['word_count_min'] = max(300, absint($input['word_count_min']));
        }
        if (isset($input['word_count_max'])) {
            $min = $current['word_count_min'] ?? 800;
            $current['word_count_max'] = max($min + 100, absint($input['word_count_max']));
        }

        // Sinkronisasi otomatis dengan plugin nerd-for-wp jika diset
        if (isset($input['nerd_email'])) {
            $nerd_email = sanitize_email(wp_unslash($input['nerd_email']));
            $current['nerd_email'] = $nerd_email;
            update_option('nerd_email', $nerd_email);
        }
        if (isset($input['nerd_api_key'])) {
            $nerd_key = sanitize_text_field(wp_unslash($input['nerd_api_key']));
            $current['nerd_api_key'] = $nerd_key;
            update_option('nerd_api_key', $nerd_key);
        }

        update_option('apcai_settings', $current);
        APCAI_Scheduler::sync_schedule();

        // Pembersihan Anti-Cache Menyeluruh
        self::purge_all_caches();

        wp_send_json_success('Pengaturan berhasil disimpan dan seluruh cache telah dibersihkan!');
    }

    /**
     * Purge Seluruh Lapisan Cache di WordPress & Server
     */
    public static function purge_all_caches() {
        // 1. WordPress Internal Cache & Options Cache
        wp_cache_delete('apcai_settings', 'options');
        wp_cache_delete('alloptions', 'options');
        if (function_exists('wp_cache_flush')) {
            @wp_cache_flush();
        }

        // 2. LiteSpeed Cache
        if (defined('LSCWP_V')) {
            do_action('litespeed_purge_all');
        }

        // 3. WP Rocket
        if (function_exists('rocket_clean_domain')) {
            rocket_clean_domain();
        }

        // 4. W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
        }

        // 5. WP Super Cache
        if (function_exists('wp_cache_clean_cache')) {
            global $file_prefix;
            wp_cache_clean_cache($file_prefix, true);
        }

        // 6. Autoptimize
        if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
            autoptimizeCache::clearall();
        }

        // 7. WP Fastest Cache
        if (isset($GLOBALS['wp_fastest_cache']) && method_exists($GLOBALS['wp_fastest_cache'], 'deleteCache')) {
            $GLOBALS['wp_fastest_cache']->deleteCache(true);
        }
    }

    public static function ajax_brainstorm_topics() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        $count = isset($_POST['count']) ? absint($_POST['count']) : 5;
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
        $funnel = sanitize_text_field(wp_unslash($_POST['funnel_filter'] ?? ''));

        $res = APCAI_Generator::brainstorm_topics($count, $category_id, $funnel);

        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success($res);
    }

    public static function ajax_generate_outline_now() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        if (!headers_sent()) {
            nocache_headers();
            header('X-LiteSpeed-Cache-Control: no-cache');
        }
        if (defined('LSCWP_V')) {
            do_action('litespeed_control_set_nocache', 'APC AI Studio Outline Ajax');
        }

        $topic = sanitize_textarea_field(wp_unslash($_POST['topic'] ?? ''));
        $category_id = absint($_POST['category_id'] ?? 1);
        $funnel_stage = sanitize_text_field(wp_unslash($_POST['funnel_stage'] ?? 'tofu'));

        if (empty($topic)) {
            wp_send_json_error('Topik atau judul wajib diisi.');
        }

        $settings = get_option('apcai_settings', []);
        $site_name = !empty($settings['site_name']) ? $settings['site_name'] : get_bloginfo('name');
        $cat_obj = get_category($category_id);
        $cat_name = $cat_obj ? $cat_obj->name : 'General';

        $time_ctx = class_exists('APCAI_Agent_Engine') ? APCAI_Agent_Engine::get_time_context() : ['prompt' => ''];
        $mem_ctx  = class_exists('APCAI_Agent_Engine') ? APCAI_Agent_Engine::get_memory_context($category_id, $topic) : ['prompt' => ''];

        $blueprint = APCAI_Agent_Engine::plan_article_blueprint(
            $topic,
            $category_id,
            $funnel_stage,
            [
                'site_name'     => $site_name,
                'cat_name'      => $cat_name,
                'time_prompt'   => $time_ctx['prompt'],
                'memory_prompt' => $mem_ctx['prompt'],
            ]
        );

        if (is_wp_error($blueprint)) {
            wp_send_json_error($blueprint->get_error_message());
        }

        $formatted_text = APCAI_Agent_Engine::format_blueprint_instruction($blueprint);

        wp_send_json_success([
            'blueprint'      => $blueprint,
            'formatted_text' => $formatted_text,
        ]);
    }

    public static function ajax_generate_now() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        // Cegah LiteSpeed Cache menyimpan respons dinamis AJAX
        if (!headers_sent()) {
            nocache_headers();
            header('X-LiteSpeed-Cache-Control: no-cache');
        }
        if (defined('LSCWP_V')) {
            do_action('litespeed_control_set_nocache', 'APC AI Studio Ajax');
        }

        $topic = sanitize_textarea_field(wp_unslash($_POST['topic'] ?? ''));
        $category_id = absint($_POST['category_id'] ?? 1);
        $funnel_stage = sanitize_text_field(wp_unslash($_POST['funnel_stage'] ?? 'tofu'));

        if (empty($topic)) {
            wp_send_json_error('Topik atau paragraf awal wajib diisi.');
        }

        $opts = [
            'keyword_prompt'  => sanitize_textarea_field(wp_unslash($_POST['keyword_prompt'] ?? '')),
            'content_prompt'  => sanitize_textarea_field(wp_unslash($_POST['content_prompt'] ?? '')),
            'custom_outline'  => sanitize_textarea_field(wp_unslash($_POST['custom_outline'] ?? '')),
            'cta_type'        => sanitize_text_field(wp_unslash($_POST['cta_type'] ?? '')),
            'cta_text'        => sanitize_text_field(wp_unslash($_POST['cta_text'] ?? '')),
            'cta_target'      => sanitize_text_field(wp_unslash($_POST['cta_target'] ?? '')),
            'cta_instruction' => sanitize_textarea_field(wp_unslash($_POST['cta_instruction'] ?? '')),
        ];

        $res = APCAI_Generator::generate_primary_content($topic, $category_id, $funnel_stage, $opts);
        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success(['result' => $res]);
    }

    public static function ajax_translate_en_now() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        if (!headers_sent()) {
            nocache_headers();
            header('X-LiteSpeed-Cache-Control: no-cache');
        }
        if (defined('LSCWP_V')) {
            do_action('litespeed_control_set_nocache', 'APC AI Translate Ajax');
        }

        $title   = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $content = wp_unslash($_POST['content_md'] ?? '');
        $excerpt = sanitize_textarea_field(wp_unslash($_POST['excerpt'] ?? ''));
        $kw      = isset($_POST['keywords']) ? (array)$_POST['keywords'] : [];

        if (empty($title) || empty($content)) {
            wp_send_json_error('Judul dan konten ID harus ada sebelum adaptasi EN.');
        }

        $res = APCAI_Generator::generate_english_adaptation($title, $content, $excerpt, $kw);
        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success(['result' => $res]);
    }

    public static function ajax_translate_ms_now() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        if (!headers_sent()) {
            nocache_headers();
            header('X-LiteSpeed-Cache-Control: no-cache');
        }
        if (defined('LSCWP_V')) {
            do_action('litespeed_control_set_nocache', 'APC AI Translate MS Ajax');
        }

        $title   = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $content = wp_unslash($_POST['content_md'] ?? '');
        $excerpt = sanitize_textarea_field(wp_unslash($_POST['excerpt'] ?? ''));
        $kw      = isset($_POST['keywords']) ? (array)$_POST['keywords'] : [];

        if (empty($title) || empty($content)) {
            wp_send_json_error('Judul dan konten sumber harus ada sebelum adaptasi Bahasa Melayu.');
        }

        $res = APCAI_Generator::generate_malay_adaptation($title, $content, $excerpt, $kw);
        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        wp_send_json_success(['result' => $res]);
    }

    public static function ajax_publish_article() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Unauthorized: insufficient permissions.');
        }

        if (!headers_sent()) {
            nocache_headers();
            header('X-LiteSpeed-Cache-Control: no-cache');
        }
        if (defined('LSCWP_V')) {
            do_action('litespeed_control_set_nocache', 'APC AI Publish Ajax');
        }

        $raw_id = $_POST['id_data'] ?? null;
        $raw_en = $_POST['en_data'] ?? null;
        $raw_ms = $_POST['ms_data'] ?? null;
        $primary_lang = sanitize_text_field(wp_unslash($_POST['primary_lang'] ?? 'id'));
        $publish_mode = sanitize_text_field(wp_unslash($_POST['publish_mode'] ?? 'both'));
        $category_id = absint($_POST['category_id'] ?? 1);
        $funnel_stage = sanitize_text_field(wp_unslash($_POST['funnel_stage'] ?? 'tofu'));

        if (!is_array($raw_id) || empty($raw_id['title'])) {
            wp_send_json_error('Data artikel utama tidak valid.');
        }

        $sanitize_data = function(array $d): array {
            $kw = [];
            if (!empty($d['keywords']) && is_array($d['keywords'])) {
                $kw = array_map('sanitize_text_field', $d['keywords']);
            }
            return [
                'title'           => sanitize_text_field($d['title'] ?? ''),
                'slug'            => sanitize_title($d['slug'] ?? $d['title'] ?? ''),
                'seo_title'       => sanitize_text_field($d['seo_title'] ?? ''),
                'seo_description' => sanitize_text_field($d['seo_description'] ?? ''),
                'excerpt'         => sanitize_textarea_field($d['excerpt'] ?? ''),
                'keywords'        => $kw,
                'content_md'      => wp_kses_post($d['content_md'] ?? ''),
            ];
        };

        $id_data = $sanitize_data($raw_id);
        $status = ($publish_mode === 'draft') ? 'draft' : 'publish';
        $post_id_primary = APCAI_Generator::save_article($id_data, $primary_lang, 0, 0, $status, $category_id, $funnel_stage);

        if (is_wp_error($post_id_primary)) {
            wp_send_json_error($post_id_primary->get_error_message());
        }

        $post_id_en = 0;
        if (is_array($raw_en) && !empty($raw_en['title']) && ($publish_mode === 'both' || $publish_mode === 'id_only')) {
            $en_data = $sanitize_data($raw_en);
            $en_status = ($publish_mode === 'id_only') ? 'draft' : 'publish';
            $en_id = APCAI_Generator::save_article($en_data, 'en', $post_id_primary, 0, $en_status, $category_id, $funnel_stage);
            if (!is_wp_error($en_id)) {
                $post_id_en = $en_id;
            }
        }

        $post_id_ms = 0;
        if (is_array($raw_ms) && !empty($raw_ms['title']) && ($publish_mode === 'both' || $publish_mode === 'id_only')) {
            $ms_data = $sanitize_data($raw_ms);
            $ms_status = ($publish_mode === 'id_only') ? 'draft' : 'publish';
            $ms_id = APCAI_Generator::save_article($ms_data, 'ms', $post_id_primary, 0, $ms_status, $category_id, $funnel_stage);
            if (!is_wp_error($ms_id)) {
                $post_id_ms = $ms_id;
            }
        }

        wp_send_json_success([
            'primary_id' => $post_id_primary,
            'en_id'      => $post_id_en,
            'ms_id'      => $post_id_ms,
            'view_url'   => get_permalink($post_id_primary),
            'edit_url'   => get_edit_post_link($post_id_primary, ''),
        ]);
    }

    public static function ajax_upload_pool_images() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('Unauthorized');

        if (empty($_FILES['files'])) {
            wp_send_json_error('Tidak ada file yang diunggah.');
        }

        $files = $_FILES['files'];
        $uploaded = [];
        $errors = [];

        // Loop multi-file upload
        $count = count($files['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

            $single = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            $res = APCAI_Image_Manager::process_local_upload($single);
            if (is_wp_error($res)) {
                $errors[] = $single['name'] . ': ' . $res->get_error_message();
            } else {
                $uploaded[] = $res;
            }
        }

        $pool = get_option('apcai_custom_images_pool', []);

        wp_send_json_success([
            'uploaded'    => $uploaded,
            'errors'      => $errors,
            'total_count' => count($pool),
        ]);
    }

    public static function ajax_delete_pool_image() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('upload_files')) wp_send_json_error('Unauthorized');

        $attach_id = absint($_POST['attach_id'] ?? 0);
        if (!$attach_id) wp_send_json_error('ID lampiran tidak valid.');

        $pool = get_option('apcai_custom_images_pool', []);
        $pool = array_values(array_diff($pool, [$attach_id]));
        update_option('apcai_custom_images_pool', $pool);

        wp_delete_attachment($attach_id, true);

        wp_send_json_success([
            'deleted_id'  => $attach_id,
            'total_count' => count($pool),
        ]);
    }

    public static function ajax_delete_topic() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        global $wpdb;
        $id = absint($_POST['topic_id'] ?? 0);
        if ($id) {
            $wpdb->delete(APCAI_DB::table_topics(), ['id' => $id]);
            wp_send_json_success();
        }
        wp_send_json_error('Invalid topic ID');
    }

    public static function ajax_save_landing_page() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        global $wpdb;
        $category_id = absint($_POST['category_id'] ?? 1);
        $page_title  = sanitize_text_field(wp_unslash($_POST['page_title'] ?? ''));
        $page_url    = esc_url_raw(wp_unslash($_POST['page_url'] ?? ''));
        $anchors     = sanitize_textarea_field(wp_unslash($_POST['anchor_keywords'] ?? ''));
        $btn_text    = sanitize_text_field(wp_unslash($_POST['cta_button_text'] ?? 'Beli Sekarang'));

        if (empty($page_url) || empty($anchors)) {
            wp_send_json_error('URL Landing Page dan Variasi Kata Kunci wajib diisi.');
        }

        $t_landing = APCAI_DB::table_landing_pages();
        $wpdb->insert($t_landing, [
            'category_id'     => $category_id,
            'page_title'      => $page_title,
            'page_url'        => $page_url,
            'anchor_keywords' => $anchors,
            'cta_button_text' => $btn_text,
            'status'          => 'active',
            'created_at'      => current_time('mysql'),
            'updated_at'      => current_time('mysql'),
        ]);

        wp_send_json_success('Target Landing Page berhasil ditambahkan!');
    }

    public static function ajax_delete_landing_page() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        global $wpdb;
        $id = absint($_POST['lp_id'] ?? 0);
        if ($id) {
            $wpdb->delete(APCAI_DB::table_landing_pages(), ['id' => $id]);
            wp_send_json_success();
        }
        wp_send_json_error('Invalid LP ID');
    }

    public static function ajax_trigger_cron_now() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

        APCAI_Scheduler::execute_one_post_job();
        wp_send_json_success('Eksekusi posting manual selesai diproses! Periksa tab Riwayat Log.');
    }

    public static function ajax_check_update_now() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('update_plugins')) {
            wp_send_json_error('Unauthorized: insufficient permissions.');
        }

        $res = APCAI_Updater::force_check_update();
        wp_send_json_success($res);
    }

    /**
     * AJAX: Scan posts & tags yang belum memiliki Focus Keyword SEO
     */
    public static function ajax_scan_missing_keywords() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;

        // 1. Scan Posts (Published) yang belum punya focus keyword
        $posts_missing = [];
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
        ]);

        foreach ($posts as $p_id) {
            $yoast_kw = get_post_meta($p_id, '_yoast_wpseo_focuskw', true);
            $rm_kw    = get_post_meta($p_id, 'rank_math_focus_keyword', true);
            if (empty(trim((string)$yoast_kw)) && empty(trim((string)$rm_kw))) {
                $posts_missing[] = [
                    'type'  => 'post',
                    'id'    => $p_id,
                    'title' => get_the_title($p_id),
                ];
            }
        }

        // 2. Scan Tags yang belum punya focus keyword
        $tags_missing = [];
        $tags = get_terms([
            'taxonomy'   => 'post_tag',
            'hide_empty' => false,
            'number'     => 200,
        ]);

        if (!empty($tags) && !is_wp_error($tags)) {
            foreach ($tags as $t) {
                $yoast_kw = get_term_meta($t->term_id, '_yoast_wpseo_focuskw', true);
                $rm_kw    = get_term_meta($t->term_id, 'rank_math_focus_keyword', true);
                if (empty(trim((string)$yoast_kw)) && empty(trim((string)$rm_kw))) {
                    $tags_missing[] = [
                        'type'  => 'tag',
                        'id'    => $t->term_id,
                        'title' => $t->name,
                    ];
                }
            }
        }

        wp_send_json_success([
            'posts_count' => count($posts_missing),
            'tags_count'  => count($tags_missing),
            'items'       => array_merge($posts_missing, $tags_missing),
        ]);
    }

    /**
     * AJAX: Perbaiki / Input target keyword ke item spesifik (post atau tag)
     */
    public static function ajax_fix_single_keyword() {
        check_ajax_referer('apcai_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        global $wpdb;
        $type      = sanitize_text_field(wp_unslash($_POST['item_type'] ?? 'post'));
        $id        = absint($_POST['item_id'] ?? 0);
        $date_mode = sanitize_text_field(wp_unslash($_POST['date_mode'] ?? 'smart_organic'));

        if ($id <= 0) {
            wp_send_json_error('Invalid ID');
        }

        if ($type === 'tag') {
            $term = get_term($id, 'post_tag');
            if (!$term || is_wp_error($term)) {
                wp_send_json_error('Tag not found');
            }
            $target_kw = $term->name;
            update_term_meta($id, '_yoast_wpseo_focuskw', $target_kw);
            update_term_meta($id, 'rank_math_focus_keyword', $target_kw);

            $tag_url = get_term_link($term);
            $tag_link_html = (!empty($tag_url) && !is_wp_error($tag_url)) ? " • <a href='" . esc_url($tag_url) . "' target='_blank' style='color:#2563eb;text-decoration:underline;'>View Tag Archive &rarr;</a>" : '';
            $edit_url = get_edit_term_link($id, 'post_tag');
            $edit_link_html = !empty($edit_url) ? " • <a href='" . esc_url($edit_url) . "' target='_blank' style='color:#64748b;text-decoration:underline;'>Edit &rarr;</a>" : '';

            // Catat ke log
            $t_logs = APCAI_DB::table_logs();
            $wpdb->insert($t_logs, [
                'action_type' => 'seo_backfill',
                'status'      => 'success',
                'message'     => "Injected SEO Focus Keyphrase to Tag: <strong>{$term->name}</strong> &rarr; Target: <code>{$target_kw}</code>{$tag_link_html}{$edit_link_html}",
                'created_at'  => current_time('mysql'),
            ]);

            wp_send_json_success([
                'type'    => 'tag',
                'id'      => $id,
                'keyword' => $target_kw,
                'message' => "Tag '{$term->name}' assigned focus keyword: '{$target_kw}'",
            ]);
        }

        // Tipe Post
        $post = get_post($id);
        if (!$post) {
            wp_send_json_error('Post not found');
        }

        $target_kw = '';

        // 1. Cek dari tags post yang sudah ada
        $tags = wp_get_post_tags($id);
        if (!empty($tags) && !is_wp_error($tags)) {
            $target_kw = $tags[0]->name;
        }

        // 2. Cek dari database relasi APCAI topics jika ada
        if (empty($target_kw)) {
            $t_meta = APCAI_DB::table_posts_meta();
            $t_topics = APCAI_DB::table_topics();
            $topic_row = $wpdb->get_row($wpdb->prepare(
                "SELECT t.primary_keyword FROM {$t_topics} t INNER JOIN {$t_meta} m ON t.id = m.topic_id WHERE m.post_id = %d LIMIT 1",
                $id
            ));
            if ($topic_row && !empty($topic_row->primary_keyword)) {
                $target_kw = $topic_row->primary_keyword;
            }
        }

        // 3. Fallback: Ekstrak kata kunci inti dari judul post
        if (empty($target_kw)) {
            $title = $post->post_title;
            // Bersihkan stop words umum dan simbol
            $clean_title = preg_replace('/[|\-–—:]+.*$/', '', $title); // hilangkan modifier brand di ujung
            $target_kw = trim($clean_title);
        }

        if (empty($target_kw)) {
            $target_kw = $post->post_title;
        }

        // Simpan ke meta Yoast SEO dan Rank Math
        update_post_meta($id, '_yoast_wpseo_focuskw', $target_kw);
        update_post_meta($id, 'rank_math_focus_keyword', $target_kw);

        // Pastikan post memiliki tag Title-Cased jika belum ada
        if (empty($tags) || is_wp_error($tags)) {
            $formatted_tag = mb_convert_case($target_kw, MB_CASE_TITLE, 'UTF-8');
            wp_set_post_tags($id, [$formatted_tag], true);
        }

        // Kalkulasi Tanggal Post Modified Organik Berdasarkan Pilihan User
        $orig_time = strtotime($post->post_date);
        $now_time  = current_time('timestamp');
        $new_mod_time = $orig_time;

        if ($date_mode === 'smart_organic') {
            $age_seconds = $now_time - $orig_time;
            if ($age_seconds <= 86400) {
                // Artikel baru (terbit < 24 jam lalu): jeda alami 15-45 menit setelah post_date
                $jitter = rand(15 * 60, 45 * 60);
                $new_mod_time = min($now_time, $orig_time + $jitter);
            } else {
                // Artikel lama: jeda acak 2 jam s/d 12 jam setelah tanggal terbit aslinya
                $jitter = rand(2 * 3600, 12 * 3600);
                $new_mod_time = min($now_time, $orig_time + $jitter);
            }
        } elseif ($date_mode === 'gradual_recent') {
            // Pembaruan bertahap acak dalam 1-3 hari terakhir
            $days_back = rand(1, 3);
            $hours_back = rand(1, 23);
            $mins_back = rand(5, 55);
            $candidate = $now_time - ($days_back * 86400) - ($hours_back * 3600) - ($mins_back * 60);
            $new_mod_time = max($orig_time, $candidate);
        } else {
            // keep_original
            $new_mod_time = $orig_time;
        }

        $formatted_mod = date_i18n('Y-m-d H:i:s', $new_mod_time);
        $formatted_mod_gmt = gmdate('Y-m-d H:i:s', $new_mod_time);

        // Update post modified secara aman langsung ke DB tanpa mengubah tanggal terbit asli
        $wpdb->update($wpdb->posts, [
            'post_modified'     => $formatted_mod,
            'post_modified_gmt' => $formatted_mod_gmt,
        ], ['ID' => $id]);
        clean_post_cache($id);

        // Catat ke riwayat log aktivitas
        $post_url = get_permalink($id);
        $post_link_html = (!empty($post_url) && !is_wp_error($post_url)) ? " • <a href='" . esc_url($post_url) . "' target='_blank' style='color:#2563eb;text-decoration:underline;'>View Post &rarr;</a>" : '';
        $edit_url = get_edit_post_link($id);
        $edit_link_html = !empty($edit_url) ? " • <a href='" . esc_url($edit_url) . "' target='_blank' style='color:#64748b;text-decoration:underline;'>Edit &rarr;</a>" : '';

        $t_logs = APCAI_DB::table_logs();
        $wpdb->insert($t_logs, [
            'action_type' => 'seo_backfill',
            'status'      => 'success',
            'message'     => "Injected SEO Focus Keyphrase to Post #{$id}: <strong>{$post->post_title}</strong> &rarr; Target: <code>{$target_kw}</code> (Modified Date: {$formatted_mod} [Strategy: {$date_mode}]){$post_link_html}{$edit_link_html}",
            'created_at'  => current_time('mysql'),
        ]);

        wp_send_json_success([
            'type'    => 'post',
            'id'      => $id,
            'keyword' => $target_kw,
            'message' => "Post '{$post->post_title}' assigned focus keyword: '{$target_kw}'",
        ]);
    }

    // ----------------------------------------------------
    // Custom Columns di All Posts
    // ----------------------------------------------------
    public static function add_custom_post_columns($columns) {
        $columns['apcai_lang'] = __('Bahasa AI', 'auto-post-content');
        $columns['apcai_funnel'] = __('Funnel Stage', 'auto-post-content');
        return $columns;
    }

    public static function render_custom_post_columns($column, $post_id) {
        global $wpdb;
        $table = APCAI_DB::table_posts_meta();

        if ($column === 'apcai_lang') {
            $rel = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE post_id = %d", $post_id));
            if ($rel) {
                $lang_badge = ($rel->language === 'id') 
                    ? '<span class="apcai-badge apcai-badge-blue">ID (Indo)</span>'
                    : '<span class="apcai-badge apcai-badge-amber">EN (English)</span>';
                
                if ($rel->translation_of > 0) {
                    $lang_badge .= '<br><span style="font-size:11px;color:#64748b;">Relasi: #' . $rel->translation_of . '</span>';
                }
                echo $lang_badge;
            } else {
                echo '<span style="color:#94a3b8;font-size:12px;">-</span>';
            }
        } elseif ($column === 'apcai_funnel') {
            $stage = $wpdb->get_var($wpdb->prepare("SELECT funnel_stage FROM $table WHERE post_id = %d", $post_id));
            if ($stage === 'tofu') {
                echo '<span class="apcai-badge apcai-badge-cyan">TOFU (Edu)</span>';
            } elseif ($stage === 'mofu') {
                echo '<span class="apcai-badge apcai-badge-purple">MOFU (Review)</span>';
            } elseif ($stage === 'bofu') {
                echo '<span class="apcai-badge apcai-badge-green">BOFU (Money)</span>';
            } else {
                echo '<span style="color:#94a3b8;font-size:12px;">-</span>';
            }
        }
    }
}
