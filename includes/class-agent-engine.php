<?php
defined('ABSPATH') || exit;

class APCAI_Agent_Engine {

    /**
     * 1. Dynamic Temporal & Time Context (Time Server MCP)
     */
    public static function get_time_context(): array {
        $now = current_time('timestamp');
        $tz_string = get_option('timezone_string');
        if (empty($tz_string)) {
            $gmt_offset = get_option('gmt_offset', 0);
            $tz_string = 'UTC' . ($gmt_offset >= 0 ? '+' . $gmt_offset : $gmt_offset);
        }

        $formatted_date = date_i18n('l, d F Y', $now);
        $month_year     = date_i18n('F Y', $now);
        $year           = date_i18n('Y', $now);
        $time_clock     = date_i18n('H:i', $now);

        $prompt = "TEMPORAL TIME CONTEXT (NATIVE TIME SERVER):
- Waktu Saat Ini: {$formatted_date} pukul {$time_clock} ({$tz_string})
- Bulan & Tahun Aktif: {$month_year} (Tahun {$year})
- ATURAN WAKTU: Pastikan setiap referensi waktu, tren, data harga, atau update menggunakan tahun {$year}. DILARANG merujuk ke tahun masa lalu seperti 2023 atau 2024 seolah-olah waktu sekarang.";

        return [
            'date_formatted' => $formatted_date,
            'month_year'     => $month_year,
            'year'           => $year,
            'time_clock'     => $time_clock,
            'timezone'       => $tz_string,
            'prompt'         => $prompt,
        ];
    }

    /**
     * 2. Long-term Knowledge & Content Memory (Memory Server MCP)
     */
    public static function get_memory_context(int $category_id, string $topic_title = ''): array {
        global $wpdb;
        $t_rel = APCAI_DB::table_posts_meta();

        // Ambil riwayat artikel yang sudah terbit di kategori ini
        $recent_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT r.post_id, r.funnel_stage, p.post_title, p.post_excerpt, r.keywords
             FROM {$t_rel} r
             INNER JOIN {$wpdb->posts} p ON r.post_id = p.ID
             WHERE r.category_id = %d AND p.post_status = 'publish'
             ORDER BY r.id DESC LIMIT 6",
            $category_id
        ));

        $memory_items = [];
        $covered_angles = [];
        $used_keywords = [];

        if (!empty($recent_posts)) {
            foreach ($recent_posts as $p) {
                $kw = is_string($p->keywords) ? json_decode($p->keywords, true) : [];
                if (is_array($kw)) {
                    $used_keywords = array_merge($used_keywords, $kw);
                }
                $excerpt = !empty($p->post_excerpt) ? substr(trim($p->post_excerpt), 0, 120) . '...' : '';
                $memory_items[] = "- #{$p->post_id} [{$p->funnel_stage}] \"{$p->post_title}\"" . ($excerpt ? " (Konteks: {$excerpt})" : "");
                $covered_angles[] = $p->post_title;
            }
        }

        $used_keywords = array_unique(array_filter(array_slice($used_keywords, 0, 15)));

        $prompt = "LONG-TERM MEMORY CONTEXT (NATIVE MEMORY SERVER):
- Artikel yang sudah pernah ditulis & diterbitkan di kategori ini:
" . (!empty($memory_items) ? implode("\n", $memory_items) : "(Belum ada artikel sebelumnya)") . "
- Keyword/Entitas yang telah banyak dibahas: " . (!empty($used_keywords) ? implode(", ", $used_keywords) : "-") . "
- ATURAN MEMORY:
  1. JANGAN menduplikasi bahasan atau copy-paste poin dari artikel di atas.
  2. Sajikan sudut pandang (angle) baru yang melengkapi dan memperkaya artikel yang sudah ada di memori.
  3. Buat pembaca merasa topik ini adalah kelanjutan logis atau pelengkap dari pembahasan brand kami sebelumnya.";

        return [
            'recent_posts'   => $recent_posts,
            'covered_angles' => $covered_angles,
            'used_keywords'  => $used_keywords,
            'prompt'         => $prompt,
        ];
    }

    /**
     * 3. Sequential Thinking Engine (Tahap 1: Planning & Blueprint Architecture)
     */
    public static function plan_article_blueprint(string $topic, int $category_id, string $funnel_stage, array $context): array|WP_Error {
        $settings = get_option('apcai_settings', []);
        $model = $settings['model_planner'] ?? 'openai/gpt-4o-mini';
        $site_name = $context['site_name'] ?? get_bloginfo('name');
        $cat_name = $context['cat_name'] ?? 'General';

        $system_prompt = "Anda adalah Chief Content Strategist & Planning Agent dari {$site_name}.
Tugas Anda mengeksekusi TAHAP 1 SEQUENTIAL THINKING: Merancang blueprint & arsitektur outline artikel komprehensif sebelum penulisan penuh.

Aturan Penting:
1. Tahap Funnel: " . strtoupper($funnel_stage) . " (Kategori: {$cat_name}).
2. Susun 4-6 sub-heading (H2) logis dan mendalam dengan bullet point bahasan di masing-masing H2.
3. Tentukan 1 target tabel data komparasi / spesifikasi / estimasi biaya yang wajib disajikan di salah satu sub-heading.
4. Identifikasi keresahan utama audiens (Pain Point) dan solusi konkrit dari {$site_name}.
5. Tentukan sudut pandang (Unique Angle) agar 100% bebas dari pola robot AI. Gunakan kata 'Cara', BUKAN 'Panduan'.

Format Output WAJIB JSON murni:
{
  \"angle\": \"Sudut pandang tajam dan unik artikel\",
  \"target_audience_pain_point\": \"Masalah nyata yang dirasakan audiens\",
  \"recommended_table\": \"Ide tabel komparasi/data yang relevan\",
  \"sections\": [
    {
      \"heading\": \"## Judul Sub-heading H2\",
      \"key_points\": [\"poin bahasan 1\", \"poin bahasan 2\", \"poin bahasan 3\"]
    }
  ]
}";

        $user_prompt = "TOPIK UTAMA: {$topic}\n\n" .
            $context['time_prompt'] . "\n\n" .
            $context['memory_prompt'] . "\n\nRancang blueprint outline komprehensif untuk topik di atas:";

        $raw = APCAI_AI_Client::chat($system_prompt, $user_prompt, $model, 0.5);
        if (is_wp_error($raw)) {
            return $raw;
        }

        $parsed = APCAI_AI_Client::extract_json($raw);
        if (is_wp_error($parsed) || !is_array($parsed)) {
            return new WP_Error('blueprint_failed', 'Gagal memproses Sequential Thinking Blueprint: ' . (is_wp_error($parsed) ? $parsed->get_error_message() : 'Invalid JSON'));
        }

        return $parsed;
    }

    /**
     * Format Blueprint Outline menjadi instruksi terstruktur untuk Writer Model
     */
    public static function format_blueprint_instruction(array $blueprint): string {
        if (empty($blueprint['sections']) || !is_array($blueprint['sections'])) {
            return '';
        }

        $out = "BLUEPRINT HASIL SEQUENTIAL THINKING (TAHAP 1 PLANNER):\n";
        if (!empty($blueprint['angle'])) {
            $out .= "- Sudut Pandang (Unique Angle): {$blueprint['angle']}\n";
        }
        if (!empty($blueprint['target_audience_pain_point'])) {
            $out .= "- Keresahan Audiens (Pain Point): {$blueprint['target_audience_pain_point']}\n";
        }
        if (!empty($blueprint['recommended_table'])) {
            $out .= "- Rekomendasi Tabel Wajib: {$blueprint['recommended_table']}\n";
        }

        $out .= "\nSTRUKTUR SUB-HEADING WAJIB DIKEMBANGKAN:\n";
        foreach ($blueprint['sections'] as $i => $sec) {
            $h = $sec['heading'] ?? ('## Bagian ' . ($i + 1));
            $out .= "{$h}\n";
            if (!empty($sec['key_points']) && is_array($sec['key_points'])) {
                foreach ($sec['key_points'] as $kp) {
                    $out .= "  * {$kp}\n";
                }
            }
        }
        $out .= "\n(Kembangkan setiap sub-heading di atas secara mendalam dan berbobot tanpa mengurangi poin-poin yang telah dirancang).";

        return $out;
    }
}
