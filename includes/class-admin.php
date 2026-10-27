<?php
defined('ABSPATH') || exit;

class APCAI_Admin {

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'register_menus']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_apcai_test_api', [__CLASS__, 'ajax_test_api']);
        add_action('wp_ajax_apcai_save_settings', [__CLASS__, 'ajax_save_settings']);
        add_action('wp_ajax_apcai_brainstorm_topics', [__CLASS__, 'ajax_brainstorm_topics']);
        add_action('wp_ajax_apcai_generate_now', [__CLASS__, 'ajax_generate_now']);
        add_action('wp_ajax_apcai_translate_en_now', [__CLASS__, 'ajax_translate_en_now']);
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

        // Filter kolom di All Posts WP
        add_filter('manage_posts_columns', [__CLASS__, 'add_custom_post_columns']);
        add_action('manage_posts_custom_column', [__CLASS__, 'render_custom_post_columns'], 10, 2);
    }

    public static function register_menus() {
        add_menu_page(
            __('Auto Post AI', 'auto-post-content'),
            __('Auto Post AI', 'auto-post-content'),
            'manage_options',
            'apcai-dashboard',
            [__CLASS__, 'page_dashboard'],
            'dashicons-superhero-alt',
            26
        );

        add_submenu_page(
            'apcai-dashboard',
            __('AI Content Studio', 'auto-post-content'),
            __('Content Studio', 'auto-post-content'),
            'manage_options',
            'apcai-studio',
            [__CLASS__, 'page_studio']
        );

        add_submenu_page(
            'apcai-dashboard',
            __('Topik & Funneling', 'auto-post-content'),
            __('Topik & Funneling', 'auto-post-content'),
            'manage_options',
            'apcai-topics',
            [__CLASS__, 'page_topics']
        );

        add_submenu_page(
            'apcai-dashboard',
            __('Target Landing Pages', 'auto-post-content'),
            __('Target Landing Page', 'auto-post-content'),
            'manage_options',
            'apcai-landing-pages',
            [__CLASS__, 'page_landing_pages']
        );

        add_submenu_page(
            'apcai-dashboard',
            __('Auto Schedule', 'auto-post-content'),
            __('Auto Schedule', 'auto-post-content'),
            'manage_options',
            'apcai-schedule',
            [__CLASS__, 'page_schedule']
        );

        add_submenu_page(
            'apcai-dashboard',
            __('Pool Foto Sendiri', 'auto-post-content'),
            __('Pool Foto Sendiri', 'auto-post-content'),
            'manage_options',
            'apcai-gallery',
            [__CLASS__, 'page_gallery']
        );

        add_submenu_page(
            'apcai-dashboard',
            __('Pengaturan AI & Prompt', 'auto-post-content'),
            __('Pengaturan', 'auto-post-content'),
            'manage_options',
            'apcai-settings',
            [__CLASS__, 'page_settings']
        );

        add_submenu_page(
            'apcai-dashboard',
            __('Riwayat & Log', 'auto-post-content'),
            __('Riwayat Log', 'auto-post-content'),
            'manage_options',
            'apcai-logs',
            [__CLASS__, 'page_logs']
        );
    }

    public static function enqueue_assets($hook) {
        if (strpos($hook, 'apcai') === false) {
            return;
        }

        wp_enqueue_style('apcai-admin-style', APCAI_PLUGIN_URL . 'assets/css/admin.css', [], APCAI_VERSION);
        wp_enqueue_script('apcai-admin-script', APCAI_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], APCAI_VERSION, true);

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

    public static function page_settings() {
        $settings = get_option('apcai_settings', []);
        include APCAI_PLUGIN_DIR . 'views/settings.php';
    }

    public static function page_logs() {
        global $wpdb;
        $t_logs = APCAI_DB::table_logs();
        $logs = $wpdb->get_results("SELECT * FROM $t_logs ORDER BY id DESC LIMIT 50");
        include APCAI_PLUGIN_DIR . 'views/logs.php';
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

        $current['schedule_enabled'] = !empty($input['schedule_enabled']) ? 1 : 0;
        $current['enable_internal_links'] = !empty($input['enable_internal_links']) ? 1 : 0;
        $current['enable_instant_indexing'] = !empty($input['enable_instant_indexing']) ? 1 : 0;

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

        wp_send_json_success('Pengaturan berhasil disimpan!');
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
        $post_id_primary = APCAI_Generator::save_article($id_data, 'id', 0, 0, $status, $category_id, $funnel_stage);

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

        wp_send_json_success([
            'primary_id' => $post_id_primary,
            'en_id'      => $post_id_en,
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
