<?php
defined('ABSPATH') || exit;

class APCAI_DB {

    public static function table_topics() {
        global $wpdb;
        return $wpdb->prefix . 'apcai_topics';
    }

    public static function table_logs() {
        global $wpdb;
        return $wpdb->prefix . 'apcai_generation_logs';
    }

    public static function table_posts_meta() {
        global $wpdb;
        return $wpdb->prefix . 'apcai_post_relations';
    }

    public static function table_landing_pages() {
        global $wpdb;
        return $wpdb->prefix . 'apcai_landing_pages';
    }

    public static function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // 1. Tabel Topik Konten (Ditambah category_id & funnel_stage)
        $t_topics = self::table_topics();
        $sql_topics = "CREATE TABLE $t_topics (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            category_id bigint(20) unsigned DEFAULT 1,
            funnel_stage varchar(20) DEFAULT 'tofu',
            title varchar(255) NOT NULL,
            primary_keyword varchar(255) DEFAULT '',
            intent varchar(50) DEFAULT 'informational',
            angle text DEFAULT NULL,
            status varchar(20) DEFAULT 'idea',
            source varchar(50) DEFAULT 'ai_brainstorm',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY funnel_stage (funnel_stage),
            KEY status (status)
        ) $charset_collate;";

        // 2. Tabel Relasi Post Multi-Bahasa & Metadata
        $t_relations = self::table_posts_meta();
        $sql_relations = "CREATE TABLE $t_relations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            category_id bigint(20) unsigned DEFAULT 1,
            funnel_stage varchar(20) DEFAULT 'tofu',
            translation_of bigint(20) unsigned DEFAULT 0,
            language varchar(10) NOT NULL DEFAULT 'id',
            topic_id bigint(20) unsigned DEFAULT 0,
            seo_title varchar(255) DEFAULT '',
            seo_description text DEFAULT NULL,
            keywords text DEFAULT NULL,
            word_count int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_lang (post_id),
            KEY category_id (category_id),
            KEY funnel_stage (funnel_stage),
            KEY translation_of (translation_of),
            KEY language (language)
        ) $charset_collate;";

        // 3. Tabel Target Landing Page (Money Page / Silo Anchor Hub)
        $t_landing = self::table_landing_pages();
        $sql_landing = "CREATE TABLE $t_landing (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            category_id bigint(20) unsigned NOT NULL,
            page_title varchar(255) DEFAULT '',
            page_url text NOT NULL,
            anchor_keywords text NOT NULL,
            cta_button_text varchar(100) DEFAULT 'Kunjungi Halaman Resmi',
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category_id (category_id),
            KEY status (status)
        ) $charset_collate;";

        // 4. Tabel Log Eksekusi & Riwayat AI
        $t_logs = self::table_logs();
        $sql_logs = "CREATE TABLE $t_logs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            action_type varchar(50) NOT NULL,
            status varchar(20) NOT NULL,
            message text DEFAULT NULL,
            data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY action_type (action_type)
        ) $charset_collate;";

        dbDelta($sql_topics);
        dbDelta($sql_relations);
        dbDelta($sql_landing);
        dbDelta($sql_logs);

        // Default plugin options jika belum ada
        if (!get_option('apcai_settings')) {
            $default_settings = [
                'ninerouter_url'      => 'https://api.9router.com',
                'ninerouter_key'      => '',
                'model_writer'        => 'openai/gpt-4o',
                'model_planner'       => 'openai/gpt-4o-mini',
                'lang_mode'           => 'dual',
                'default_lang'        => 'id',
                'keyword_prompt'      => "Ekstrak dan kembangkan kata kunci entitas SEO:\n1. Target kata kunci utama (Primary Keyword)\n2. 5-8 Entity keywords (LSI / semantic terms) yang relevan\n3. Klasifikasi search intent pengunjung",
                'content_prompt'      => "Tulis artikel dengan gaya bahasa jurnalis atau praktisi profesional manusia (100% natural, bebas klise robot AI, minimal 800-1200 kata).\n- HINDARI pembuka klise seperti 'Di era digital yang serba cepat...'\n- HINDARI sub-judul 'Kesimpulan', ganti dengan langkah aksi praktis\n- JANGAN gunakan garis mendatar (---)\n- Sub-heading jelas (H2, H3) dengan studi kasus/contoh konkret\n- Sisipkan Call To Action (CTA) secara persuasif dan mengalir\n- Optimasi kaidah Google Helpful Content & E-E-A-T",
                'cta_type'            => 'whatsapp',
                'cta_text'            => 'Hubungi Kami via WhatsApp',
                'cta_target'          => '',
                'cta_instruction'     => 'Arahkan pembaca yang membutuhkan bantuan / konsultasi / promo khusus untuk menghubungi via WhatsApp atau klik tautan penawaran resmi. Berikan kalimat pengantar yang persuasif di tengah artikel dan kalimat penutup yang kuat di akhir artikel.',
                'niche'               => 'Teknologi Informasi, Software & Web Development',
                'brand_voice'         => 'Otoritatif, Solutif, Informatif, Profesional',
                'audience'            => 'Pebisnis, Developer, Praktisi IT, Pemilik Website',
                'site_name'           => get_bloginfo('name'),
                'schedule_enabled'    => 0,
                'posts_per_day'       => 2,
                'schedule_type'       => 'random',
                'fixed_hours'         => '08:00, 13:00, 19:00',
                'post_status'         => 'publish',
                'post_author'         => 1,
                'category_mode'       => 'rotate', // 'rotate' across all categories, or 'specific'
                'target_categories'   => [],
                'enable_internal_links' => 1,
                'github_repo'         => 'marzuki46/Auto-Post-Content',
                'github_token'        => '',
            ];
            update_option('apcai_settings', $default_settings);
        }
    }
}
