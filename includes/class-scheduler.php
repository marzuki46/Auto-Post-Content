<?php
defined('ABSPATH') || exit;

class APCAI_Scheduler {

    const CRON_HOOK_DAILY   = 'apcai_cron_daily_planner';
    const CRON_HOOK_EXECUTE = 'apcai_cron_post_executor';

    public static function init() {
        add_filter('cron_schedules', [__CLASS__, 'add_cron_intervals']);

        add_action(self::CRON_HOOK_DAILY, [__CLASS__, 'run_daily_planner']);
        add_action(self::CRON_HOOK_EXECUTE, [__CLASS__, 'run_scheduled_posting']);

        if (!wp_next_scheduled(self::CRON_HOOK_DAILY)) {
            wp_schedule_event(time(), 'daily', self::CRON_HOOK_DAILY);
        }
    }

    public static function add_cron_intervals($schedules) {
        $schedules['apcai_ten_minutes'] = [
            'interval' => 600,
            'display'  => __('Setiap 10 Menit (Auto Post Content)', 'auto-post-content'),
        ];
        return $schedules;
    }

    public static function deactivate() {
        wp_clear_scheduled_hook(self::CRON_HOOK_DAILY);
        wp_clear_scheduled_hook(self::CRON_HOOK_EXECUTE);
    }

    public static function sync_schedule() {
        $settings = get_option('apcai_settings', []);
        wp_clear_scheduled_hook(self::CRON_HOOK_EXECUTE);

        if (empty($settings['schedule_enabled'])) {
            return;
        }

        $posts_per_day = max(1, min(24, (int)($settings['posts_per_day'] ?? 2)));
        $type = $settings['schedule_type'] ?? 'random';

        $timestamps = [];
        $now = current_time('timestamp');
        $today_start = strtotime('today midnight', $now);

        if ($type === 'fixed') {
            $hours_raw = explode(',', $settings['fixed_hours'] ?? '08:00, 13:00, 19:00');
            foreach ($hours_raw as $h_str) {
                $h_str = trim($h_str);
                if (empty($h_str)) continue;
                $t = strtotime($h_str, $today_start);
                if ($t > $now) {
                    $timestamps[] = $t;
                }
            }
        } else {
            $start_hour = 7;
            $end_hour = 22;
            $total_minutes = ($end_hour - $start_hour) * 60;
            $step = floor($total_minutes / $posts_per_day);

            for ($i = 0; $i < $posts_per_day; $i++) {
                $base_offset = $i * $step;
                $jitter = rand(0, max(5, $step - 10));
                $minute_offset = $base_offset + $jitter;
                
                $slot_time = $today_start + ($start_hour * 3600) + ($minute_offset * 60);
                if ($slot_time > $now) {
                    $timestamps[] = $slot_time;
                }
            }
        }

        update_option('apcai_scheduled_slots_today', $timestamps);

        if (!wp_next_scheduled(self::CRON_HOOK_EXECUTE)) {
            wp_schedule_event(time() + 60, 'apcai_ten_minutes', self::CRON_HOOK_EXECUTE);
        }
    }

    public static function run_daily_planner() {
        global $wpdb;
        $table_topics = APCAI_DB::table_topics();
        $unread_count = (int)$wpdb->get_var("SELECT COUNT(*) FROM $table_topics WHERE status = 'idea'");

        // Jika stok topik menipis (< 5), otomatis brainstorm AI untuk kategori aktif
        if ($unread_count < 5) {
            $categories = get_categories(['hide_empty' => false]);
            if (!empty($categories)) {
                $random_cat = $categories[array_rand($categories)]->term_id;
                APCAI_Generator::brainstorm_topics(5, $random_cat);
            }
        }

        self::sync_schedule();
    }

    public static function run_scheduled_posting() {
        $settings = get_option('apcai_settings', []);
        if (empty($settings['schedule_enabled'])) {
            return;
        }

        $now = current_time('timestamp');
        $slots = get_option('apcai_scheduled_slots_today', []);
        if (empty($slots) || !is_array($slots)) {
            return;
        }

        $due_slots = [];
        $remaining_slots = [];

        foreach ($slots as $timestamp) {
            if ($timestamp <= $now) {
                $due_slots[] = $timestamp;
            } else {
                $remaining_slots[] = $timestamp;
            }
        }

        if (empty($due_slots)) {
            return;
        }

        update_option('apcai_scheduled_slots_today', $remaining_slots);
        self::execute_one_post_job();
    }

    /**
     * Eksekusi 1 Posting: Rotasi Kategori, Ambil Funneling, Injeksi Landing Page Link
     */
    public static function execute_one_post_job() {
        global $wpdb;
        $settings = get_option('apcai_settings', []);
        $table_topics = APCAI_DB::table_topics();
        $table_logs   = APCAI_DB::table_logs();

        // 1. Tentukan Kategori Prioritas / Rotasi Kategori
        $categories = get_categories(['hide_empty' => false]);
        $target_cat_id = 1;
        if (!empty($categories)) {
            // Ambil kategori yang paling sedikit diposting hari ini (fair distribution)
            $cat_ids = wp_list_pluck($categories, 'term_id');
            $t_rel = APCAI_DB::table_posts_meta();
            $least_cat = $wpdb->get_row("SELECT category_id, COUNT(*) as cnt FROM $t_rel GROUP BY category_id ORDER BY cnt ASC LIMIT 1");
            if ($least_cat && in_array((int)$least_cat->category_id, $cat_ids)) {
                $target_cat_id = (int)$least_cat->category_id;
            } else {
                $target_cat_id = $categories[array_rand($categories)]->term_id;
            }
        }

        // 2. Ambil 1 topik 'idea' dari kategori tersebut
        $topic = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_topics WHERE status = 'idea' AND category_id = %d ORDER BY id ASC LIMIT 1",
            $target_cat_id
        ));

        // Jika tidak ada di kategori ini, ambil topik idea manapun yang tersedia
        if (!$topic) {
            $topic = $wpdb->get_row("SELECT * FROM $table_topics WHERE status = 'idea' ORDER BY id ASC LIMIT 1");
        }

        // Kalau benar-benar habis, generate topik darurat
        if (!$topic) {
            $bs = APCAI_Generator::brainstorm_topics(3, $target_cat_id);
            if (!is_wp_error($bs) && $bs['created'] > 0) {
                $topic = $wpdb->get_row("SELECT * FROM $table_topics WHERE status = 'idea' ORDER BY id ASC LIMIT 1");
            }
        }

        if (!$topic) {
            $wpdb->insert($table_logs, [
                'action_type' => 'scheduled_post',
                'status'      => 'failed',
                'message'     => 'Tidak ada topik tersedia di antrian untuk dieksekusi.',
                'created_at'  => current_time('mysql'),
            ]);
            return;
        }

        $wpdb->update($table_topics, ['status' => 'used', 'updated_at' => current_time('mysql')], ['id' => $topic->id]);

        $default_lang = $settings['default_lang'] ?? 'id';
        $lang_mode    = $settings['lang_mode'] ?? 'dual';
        $cat_id       = $topic->category_id ?: $target_cat_id;
        $funnel_stage = $topic->funnel_stage ?: 'tofu';

        // 3. Generate Primary Content (Silo + Landing Page Link Otomatis)
        $primary_res = APCAI_Generator::generate_primary_content($topic->title, $cat_id, $funnel_stage);

        if (is_wp_error($primary_res)) {
            $wpdb->insert($table_logs, [
                'action_type' => 'scheduled_post',
                'status'      => 'failed',
                'message'     => 'Gagal generate primary content: ' . $primary_res->get_error_message(),
                'created_at'  => current_time('mysql'),
            ]);
            return;
        }

        // 4. Simpan Primary Post
        $post_id_primary = APCAI_Generator::save_article($primary_res, $default_lang, 0, $topic->id, '', $cat_id, $funnel_stage);

        if (is_wp_error($post_id_primary)) {
            $wpdb->insert($table_logs, [
                'action_type' => 'scheduled_post',
                'status'      => 'failed',
                'message'     => 'Gagal simpan primary post: ' . $post_id_primary->get_error_message(),
                'created_at'  => current_time('mysql'),
            ]);
            return;
        }

        $secondary_post_id = 0;

        // 5. Dual Language Mode (English Adaptation)
        if ($lang_mode === 'dual') {
            $target_second_lang = ($default_lang === 'id') ? 'en' : 'id';
            
            if ($target_second_lang === 'en') {
                $en_res = APCAI_Generator::generate_english_adaptation(
                    $primary_res['title'] ?? $topic->title,
                    $primary_res['content_md'] ?? '',
                    $primary_res['excerpt'] ?? '',
                    $primary_res['keywords'] ?? []
                );

                if (!is_wp_error($en_res)) {
                    $sec_id = APCAI_Generator::save_article($en_res, 'en', $post_id_primary, $topic->id, '', $cat_id, $funnel_stage);
                    if (!is_wp_error($sec_id)) {
                        $secondary_post_id = $sec_id;
                    }
                }
            }
        }

        // Catat Log Sukses
        $wpdb->insert($table_logs, [
            'action_type' => 'scheduled_post',
            'status'      => 'success',
            'message'     => "Berhasil posting artikel #{$post_id_primary} ({$default_lang} | Kategori #{$cat_id} | Funnel: " . strtoupper($funnel_stage) . ")" . ($secondary_post_id ? " & EN #{$secondary_post_id}" : ""),
            'data'        => wp_json_encode([
                'topic_id'         => $topic->id,
                'topic_title'      => $topic->title,
                'category_id'      => $cat_id,
                'funnel_stage'     => $funnel_stage,
                'primary_post_id'  => $post_id_primary,
                'secondary_post_id'=> $secondary_post_id,
                'title'            => $primary_res['title'] ?? '',
            ]),
            'created_at'  => current_time('mysql'),
        ]);
    }
}
