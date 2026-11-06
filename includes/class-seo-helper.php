<?php
defined('ABSPATH') || exit;

/**
 * APCAI_SEO_Helper
 * Centralized SEO Manager for Yoast SEO & Rank Math
 * Handles Post & Taxonomy (Tag / Category) Focus Keyphrases across all layers:
 * 1. Post Meta / Term Meta
 * 2. Yoast Taxonomy Meta Options (wpseo_taxonomy_meta)
 * 3. Yoast Modern Indexables Table (wp_yoast_indexable)
 * 4. Rank Math Internal Meta
 */
class APCAI_SEO_Helper {

    /**
     * Set focus keyphrase for a Post (Yoast SEO + Rank Math + Indexables)
     */
    public static function set_post_focus_keyword(int $post_id, string $keyword): bool {
        $keyword = trim($keyword);
        if ($post_id <= 0 || empty($keyword)) {
            return false;
        }

        // 1. Standard Post Meta
        update_post_meta($post_id, '_yoast_wpseo_focuskw', $keyword);
        update_post_meta($post_id, 'rank_math_focus_keyword', $keyword);

        // 2. Yoast Native API if available
        if (class_exists('WPSEO_Meta')) {
            WPSEO_Meta::set_value('focuskw', $keyword, $post_id);
        }

        // 3. Yoast Modern Indexable Table Sync (v14+)
        global $wpdb;
        $table_indexable = $wpdb->prefix . 'yoast_indexable';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_indexable}'") === $table_indexable) {
            $row_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_indexable} WHERE object_id = %d AND object_type = 'post' LIMIT 1",
                $post_id
            ));
            if ($row_id) {
                $wpdb->update($table_indexable, [
                    'primary_focus_keyword' => $keyword,
                ], ['id' => $row_id]);
            }
        }

        // 4. Trigger Yoast Saved Post Action & Native Post Save Hooks (Simulate Open & Save Post)
        if (has_action('wpseo_saved_postdata')) {
            do_action('wpseo_saved_postdata', $post_id);
        }

        // 5. Trigger Native WordPress Post Update & Indexer Hooks (Re-index tanpa ubah revisi)
        $post = get_post($post_id);
        if ($post) {
            do_action('save_post', $post_id, $post, true);
            do_action('edit_post', $post_id, $post);
            do_action('post_updated', $post_id, $post, $post);
            clean_post_cache($post_id);
        }

        return true;
    }

    /**
     * Set focus keyphrase for a Term/Tag/Category (Yoast Options + Term Meta + Rank Math + Indexables)
     * NOTE: Yoast stores term SEO in the 'wpseo_taxonomy_meta' option, NOT in wp_termmeta!
     */
    public static function set_term_focus_keyword(int $term_id, string $keyword, string $taxonomy = 'post_tag'): bool {
        $keyword = trim($keyword);
        if ($term_id <= 0 || empty($keyword)) {
            return false;
        }

        // 1. Yoast Taxonomy Meta (The exact option Yoast reads for tags/categories)
        $tax_meta = get_option('wpseo_taxonomy_meta', []);
        if (!is_array($tax_meta)) {
            $tax_meta = [];
        }
        if (!isset($tax_meta[$taxonomy])) {
            $tax_meta[$taxonomy] = [];
        }
        if (!isset($tax_meta[$taxonomy][$term_id])) {
            $tax_meta[$taxonomy][$term_id] = [];
        }
        $tax_meta[$taxonomy][$term_id]['wpseo_focuskw'] = $keyword;
        update_option('wpseo_taxonomy_meta', $tax_meta);

        // 2. Yoast Native Taxonomy Meta Class if available
        if (class_exists('WPSEO_Taxonomy_Meta')) {
            WPSEO_Taxonomy_Meta::set_value($term_id, $taxonomy, 'focuskw', $keyword);
        }

        // 3. Rank Math & Fallback Term Meta
        update_term_meta($term_id, 'rank_math_focus_keyword', $keyword);
        update_term_meta($term_id, '_yoast_wpseo_focuskw', $keyword);

        // 4. Yoast Modern Indexable Table Sync for Terms
        global $wpdb;
        $table_indexable = $wpdb->prefix . 'yoast_indexable';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_indexable}'") === $table_indexable) {
            $row_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_indexable} WHERE object_id = %d AND object_type = 'term' LIMIT 1",
                $term_id
            ));
            if ($row_id) {
                $wpdb->update($table_indexable, [
                    'primary_focus_keyword' => $keyword,
                ], ['id' => $row_id]);
            }
        }

        // 5. Trigger Native Term Save / Edit Hooks (Simulate Open & Save Term)
        do_action('edited_term', $term_id, 0, $taxonomy);
        clean_term_cache($term_id, $taxonomy);

        return true;
    }

    /**
     * Get focus keyphrase for a Post
     */
    public static function get_post_focus_keyword(int $post_id): string {
        $yoast_kw = get_post_meta($post_id, '_yoast_wpseo_focuskw', true);
        if (!empty($yoast_kw)) {
            return trim((string)$yoast_kw);
        }
        $rm_kw = get_post_meta($post_id, 'rank_math_focus_keyword', true);
        if (!empty($rm_kw)) {
            return trim((string)$rm_kw);
        }

        // Check indexable table fallback
        global $wpdb;
        $table_indexable = $wpdb->prefix . 'yoast_indexable';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_indexable}'") === $table_indexable) {
            $kw = $wpdb->get_var($wpdb->prepare(
                "SELECT primary_focus_keyword FROM {$table_indexable} WHERE object_id = %d AND object_type = 'post' LIMIT 1",
                $post_id
            ));
            if (!empty($kw)) {
                return trim((string)$kw);
            }
        }

        return '';
    }

    /**
     * Get focus keyphrase for a Term (Tag / Category)
     */
    public static function get_term_focus_keyword(int $term_id, string $taxonomy = 'post_tag'): string {
        // 1. Check Yoast taxonomy meta option
        $tax_meta = get_option('wpseo_taxonomy_meta', []);
        if (is_array($tax_meta) && isset($tax_meta[$taxonomy][$term_id]['wpseo_focuskw']) && !empty($tax_meta[$taxonomy][$term_id]['wpseo_focuskw'])) {
            return trim((string)$tax_meta[$taxonomy][$term_id]['wpseo_focuskw']);
        }

        // 2. Check Rank Math term meta
        $rm_kw = get_term_meta($term_id, 'rank_math_focus_keyword', true);
        if (!empty($rm_kw)) {
            return trim((string)$rm_kw);
        }

        // 3. Check fallback term meta
        $fallback_kw = get_term_meta($term_id, '_yoast_wpseo_focuskw', true);
        if (!empty($fallback_kw)) {
            return trim((string)$fallback_kw);
        }

        // 4. Check Yoast indexable table
        global $wpdb;
        $table_indexable = $wpdb->prefix . 'yoast_indexable';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_indexable}'") === $table_indexable) {
            $kw = $wpdb->get_var($wpdb->prepare(
                "SELECT primary_focus_keyword FROM {$table_indexable} WHERE object_id = %d AND object_type = 'term' LIMIT 1",
                $term_id
            ));
            if (!empty($kw)) {
                return trim((string)$kw);
            }
        }

        return '';
    }

    /**
     * Algoritma Anti-Kanibalisasi: Hitung kemiripan semantik dua keyword (0.0 - 1.0)
     */
    public static function calculate_keyword_similarity(string $kw1, string $kw2): float {
        $kw1 = strtolower(trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $kw1)));
        $kw2 = strtolower(trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $kw2)));

        if (empty($kw1) || empty($kw2)) {
            return 0.0;
        }

        if ($kw1 === $kw2) {
            return 1.0;
        }

        // Substring containment check
        if (str_contains($kw1, $kw2) || str_contains($kw2, $kw1)) {
            $len1 = strlen($kw1);
            $len2 = strlen($kw2);
            $ratio = min($len1, $len2) / max($len1, $len2);
            if ($ratio > 0.6) {
                return 0.95;
            }
        }

        // Token Jaccard Overlap
        $tokens1 = array_filter(explode(' ', $kw1));
        $tokens2 = array_filter(explode(' ', $kw2));
        $intersect = array_intersect($tokens1, $tokens2);
        $union = array_unique(array_merge($tokens1, $tokens2));

        $jaccard = count($union) > 0 ? count($intersect) / count($union) : 0.0;

        // Levenshtein / similar_text ratio
        similar_text($kw1, $kw2, $sim_percent);
        $lev_ratio = min(1.0, max(0.0, (float) $sim_percent / 100.0));

        return min(1.0, max(0.0, (float) max($jaccard, $lev_ratio)));
    }

    /**
     * Ekstrak Frasa Kunci Spesifik dari Judul Konten (Universal Multi-Niche: 2-4 Kata)
     * Standar Yoast SEO & Rank Math: Focus Keyphrase idealnya 2-4 kata padat subjek.
     */
    public static function extract_focus_keyword_from_title(string $title, string $lang = 'id'): string {
        $title = trim($title);
        if (empty($title)) {
            return '';
        }

        // 1. Deteksi Pola Komparasi Lintas Niche (X vs Y)
        // Bekerja universal di otomotif, tech, SaaS, gadget, gadget, tools, dll.
        if (preg_match('/\b([A-Za-z0-9\s]{2,18})\s+(?:vs\.?|versus)\s+([A-Za-z0-9\s]{2,18})\b/ui', $title, $vs_match)) {
            $ent_a = trim(preg_replace('/^(?:komparasi|perbandingan|review|memilih|pilih|antara|battle|pilih mana)\s+/ui', '', trim($vs_match[1])));
            $ent_b = trim(preg_replace('/\s+(?:terbaru|terlengkap|mana|yang|paling|bagus|unggul|murah).*$/ui', '', trim($vs_match[2])));
            
            $vs_phrase = trim("{$ent_a} vs {$ent_b}");
            $vs_words = array_values(array_filter(explode(' ', $vs_phrase)));
            if (count($vs_words) >= 3 && count($vs_words) <= 5) {
                return $vs_phrase;
            }
        }

        // 2. Pemisahan Delimiter Judul (: | - – — ? !)
        // Sisi kiri tanda baca umumnya merupakan Subjek / Topik Inti
        $parts = preg_split('/[:|\-–—?!]+/u', $title);
        $candidate = !empty($parts[0]) ? trim($parts[0]) : $title;

        // Jika sisi kiri terlalu pendek (< 2 kata), coba gunakan sisi kanan
        $cand_words = array_values(array_filter(explode(' ', $candidate)));
        if (count($cand_words) < 2 && !empty($parts[1])) {
            $candidate = trim($parts[1]);
        }

        // 3. Bersihkan Listicle & Filler Awal Lintas Niche
        $leading_noise = [
            '/^\d+\s*[\.\+\-]?\s*/u', // Angka listicle di awal (10, 7, 5, dll)
            '/^(?:inilah|simak|daftar|kumpulan|rekomendasi|rahasia|mengenal|ulasan lengkap|panduan lengkap|panduan praktis|bocoran|fakta unik|fakta|alasan|top \d+|best)\s+/ui',
            '/^(?:top \d+|the best|\d+ best|\d+ top|\d+ ways to|\d+ tips for|guide to)\s+/ui',
        ];
        foreach ($leading_noise as $pattern) {
            $candidate = preg_replace($pattern, '', $candidate);
        }
        $candidate = trim($candidate);

        // 4. Bersihkan Modifier & Clickbait Akhir Lintas Niche
        $trailing_noise = [
            '/\s+(?:terbaru|terlengkap|terbaik|terpopuler|favorit|populer|murah|paling dicari|viral|kekinian)\s*$/ui',
            '/\s+(?:yang wajib dicoba|yang wajib diketahui|yang wajib dikunjungi|yang harus anda tahu|yang perlu anda tahu|yang perlu dipahami)\s*$/ui',
            '/\s+(?:untuk pemula|untuk bisnis|untuk sehari-hari|untuk anak|untuk kesehatan|untuk hidup sehat|untuk investasi)\s*$/ui',
            '/\s+(?:di era digital|di era modern|di tahun \d{4}|tahun \d{4}|masa kini|jaman now)\s*$/ui',
            '/\s+(?:agar awet|agar mesin awet|agar tetap prima|agar tahan lama|agar sukses|secara alami|dengan mudah|dengan cepat)\s*$/ui',
            '/\s+(?:mana yang cocok|mana yang lebih bagus|mana yang terbaik|pilih mana)\s*$/ui',
            '/\s+(?:for beginners|in \d{4}|step by step|made easy|explained|full guide|you should know)\s*$/ui',
        ];
        foreach ($trailing_noise as $pattern) {
            $candidate = preg_replace($pattern, '', $candidate);
        }
        $candidate = trim(preg_replace('/\s+/', ' ', $candidate));

        // 5. Batasi Ketat 2 sampai 4 Kata (Yoast / Rank Math Focus Keyphrase Rules)
        $words = array_values(array_filter(explode(' ', $candidate)));

        if (count($words) > 4) {
            $stop_words = [
                'di', 'ke', 'dari', 'pada', 'untuk', 'yang', 'dan', 'atau', 'ini', 'itu', 
                'dengan', 'secara', 'bisa', 'dapat', 'oleh', 'bagi', 'saat', 'dalam',
                'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'and', 'or', 'the', 'a', 'an'
            ];
            
            $filtered = [];
            foreach ($words as $idx => $w) {
                $lw = strtolower($w);
                if ($idx > 0 && in_array($lw, $stop_words, true) && (count($words) - count($filtered)) > 4) {
                    continue;
                }
                $filtered[] = $w;
                if (count($filtered) === 4) {
                    break;
                }
            }
            $words = $filtered;
        }

        // Fallback jika terlalu sedikit kata (< 2 kata)
        if (count($words) < 2) {
            $raw_words = array_values(array_filter(explode(' ', preg_replace('/[^\p{L}\p{N}\s]/u', '', $title))));
            $words = array_slice($raw_words, 0, 3);
        }

        // Batas absolut akhir: 2 - 4 kata
        if (count($words) > 4) {
            $words = array_slice($words, 0, 4);
        }

        return trim(implode(' ', $words));
    }

    /**
     * Terapkan Alur Revisi Editorial Alami (15 - 45 Menit Setelah Terbit)
     * Menginjeksi focus keyword, memperbarui post_modified secara natural,
     * dan men-trigger native WordPress save hooks agar Yoast & Rank Math mendeteksi revisi.
     */
    public static function apply_natural_editorial_revision(int $post_id, string $focus_kw, int $jitter_min = 15, int $jitter_max = 45): void {
        $post = get_post($post_id);
        if (!$post) return;

        // 1. Injeksi target keyword ke Yoast & Rank Math
        self::set_post_focus_keyword($post_id, $focus_kw);

        // 2. Hitung timestamp revisi alami: 15 sampai 45 menit setelah tanggal terbit
        $orig_time = strtotime($post->post_date);
        $jitter_sec = rand($jitter_min * 60, $jitter_max * 60);
        $mod_timestamp = $orig_time + $jitter_sec;

        $mod_date     = date('Y-m-d H:i:s', $mod_timestamp);
        $mod_date_gmt = gmdate('Y-m-d H:i:s', $mod_timestamp);

        global $wpdb;
        $wpdb->update($wpdb->posts, [
            'post_modified'     => $mod_date,
            'post_modified_gmt' => $mod_date_gmt,
        ], ['ID' => $post_id]);

        clean_post_cache($post_id);
        update_post_meta($post_id, '_apcai_editorial_revised', 1);
        update_post_meta($post_id, '_apcai_editorial_revision_time', $mod_date);

        // 3. Trigger hooks resmi WordPress agar indexables langsung tersinkronkan
        do_action('save_post', $post_id, get_post($post_id), true);
        do_action('edit_post', $post_id, get_post($post_id));
        do_action('post_updated', $post_id, get_post($post_id), $post);
    }

    /**
     * Resolusi Anti-Kanibalisasi Post vs Tag Keyword:
     * - Tag = Broad / Topic Hub / MOFU Curated ("Kumpulan Solusi", "Direktori", "Hub")
     * - Post = Specific Long-Tail / Intent-Driven ("Cara Memilih...", "Tips...", "Langkah...")
     * Menghasilkan keyword unik tanpa kanibalisasi internal.
     */
    public static function resolve_cannibalization(string $candidate_kw, string $type = 'post', int $current_id = 0, string $lang = 'id', string $title = ''): string {
        $candidate_kw = trim($candidate_kw);
        if (empty($candidate_kw) && !empty($title)) {
            $candidate_kw = self::extract_focus_keyword_from_title($title);
        }
        if (empty($candidate_kw)) {
            return $candidate_kw;
        }

        // 1. Dapatkan daftar keyword aktif (Cek Post sesama Post, Tag, dan Pages)
        $opponents = [];
        if ($type === 'post') {
            // Cek terhadap semua tags di database
            $all_tags = get_terms([
                'taxonomy'   => 'post_tag',
                'hide_empty' => false,
                'number'     => 150,
            ]);
            if (!empty($all_tags) && !is_wp_error($all_tags)) {
                foreach ($all_tags as $t) {
                    $t_kw = self::get_term_focus_keyword($t->term_id, 'post_tag') ?: $t->name;
                    $opponents[] = [
                        'id'   => $t->term_id,
                        'name' => $t->name,
                        'kw'   => $t_kw,
                    ];
                }
            }

            // CRITICAL: Cek juga terhadap sesama POST lain yang sudah ada agar tidak kembar sesama artikel!
            $other_posts = get_posts([
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => 150,
                'exclude'        => [$current_id],
                'fields'         => 'ids',
            ]);
            foreach ($other_posts as $other_id) {
                $p_kw = self::get_post_focus_keyword($other_id);
                if (!empty($p_kw)) {
                    $opponents[] = [
                        'id'   => $other_id,
                        'name' => get_the_title($other_id),
                        'kw'   => $p_kw,
                    ];
                }
            }
        } else {
            // Tipe tag: cek terhadap recent post keywords dan tag lainnya
            $recent_posts = get_posts([
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => 150,
                'fields'         => 'ids',
            ]);
            foreach ($recent_posts as $p_id) {
                $p_kw = self::get_post_focus_keyword($p_id);
                if (!empty($p_kw)) {
                    $opponents[] = [
                        'id'   => $p_id,
                        'name' => get_the_title($p_id),
                        'kw'   => $p_kw,
                    ];
                }
            }
        }

        // 2. Evaluasi kemiripan
        $has_cannibalization = false;
        $conflicting_kw = '';
        foreach ($opponents as $opp) {
            $sim = self::calculate_keyword_similarity($candidate_kw, $opp['kw']);
            if ($sim >= 0.70) {
                $has_cannibalization = true;
                $conflicting_kw = $opp['kw'];
                break;
            }
        }

        if (!$has_cannibalization) {
            return $candidate_kw;
        }

        // 3. Diferensiasi Intensional (Long-tail vs Hub)
        if ($type === 'post') {
            // Post butuh frasa aksi / spesifik / long-tail intent
            // Jika ada judul artikel, prioritaskan ekstraksi kata kunci spesifik dari judul
            if (!empty($title)) {
                $title_kw = self::extract_focus_keyword_from_title($title);
                if (!empty($title_kw)) {
                    $conflicts_title = false;
                    foreach ($opponents as $opp) {
                        if (self::calculate_keyword_similarity($title_kw, $opp['kw']) >= 0.70) {
                            $conflicts_title = true;
                            break;
                        }
                    }
                    if (!$conflicts_title) {
                        return $title_kw;
                    }

                    // Coba kombinasikan frasa judul dengan modifier intensi
                    $clean_words = array_values(array_filter(explode(' ', $title_kw)));
                    if (count($clean_words) >= 2) {
                        $short_title = implode(' ', array_slice($clean_words, 0, 3));
                        $test_title_pfx = ($lang === 'en') ? "Guide to {$short_title}" : "Panduan {$short_title}";
                        $conflicts_cand = false;
                        foreach ($opponents as $opp) {
                            if (self::calculate_keyword_similarity($test_title_pfx, $opp['kw']) >= 0.70) {
                                $conflicts_cand = true;
                                break;
                            }
                        }
                        if (!$conflicts_cand) {
                            return $test_title_pfx;
                        }
                    }
                }
            }

            // Fallback modifier long-tail intent per bahasa
            if ($lang === 'en') {
                $prefixes = ['How to Choose', 'Complete Review of', 'Best Tips for', 'Practical Steps for', 'Guide to'];
            } elseif ($lang === 'ms') {
                $prefixes = ['Cara Memilih', 'Ulasan Penuh', 'Tips Terbaik', 'Langkah Mudah', 'Panduan'];
            } else {
                $prefixes = ['Cara Memilih', 'Tips Terbaik', 'Langkah Mudah', 'Rekomendasi', 'Panduan'];
            }

            shuffle($prefixes);
            foreach ($prefixes as $pfx) {
                $test_candidate = "{$pfx} {$candidate_kw}";
                $still_conflicts = false;
                foreach ($opponents as $opp) {
                    if (self::calculate_keyword_similarity($test_candidate, $opp['kw']) >= 0.70) {
                        $still_conflicts = true;
                        break;
                    }
                }
                if (!$still_conflicts) {
                    return $test_candidate;
                }
            }

            return $prefixes[0] . ' ' . $candidate_kw;
        } else {
            // Tag butuh frasa payung / agregator / topic cluster
            if ($lang === 'en') {
                $suffixes = ['Hub & Guide', 'Topic Overview', 'Curated Directory', 'Solutions', 'Series'];
            } elseif ($lang === 'ms') {
                $suffixes = ['Koleksi & Panduan', 'Direktori Topik', 'Pusat Maklumat', 'Kompilasi'];
            } else {
                $suffixes = ['Pusat Informasi', 'Koleksi Topik', 'Direktori Pilihan', 'Kompilasi'];
            }

            shuffle($suffixes);
            foreach ($suffixes as $sfx) {
                $test_tag = "{$candidate_kw} {$sfx}";
                $still_conflicts = false;
                foreach ($opponents as $opp) {
                    if (self::calculate_keyword_similarity($test_tag, $opp['kw']) >= 0.70) {
                        $still_conflicts = true;
                        break;
                    }
                }
                if (!$still_conflicts) {
                    return $test_tag;
                }
            }

            return $candidate_kw . ' ' . $suffixes[0];
        }
    }

    /**
     * Audit & Deteksi Seluruh Duplikasi / Kanibalisasi Keyword (Post vs Page vs Tag)
     */
    public static function audit_duplicate_keywords(): array {
        $inventory = [];

        // 1. Ambil Semua Posts (Published)
        $posts = get_posts([
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 200,
            'fields'         => 'ids',
        ]);
        foreach ($posts as $p_id) {
            $kw = self::get_post_focus_keyword($p_id);
            if (!empty($kw)) {
                $inventory[] = [
                    'id'       => $p_id,
                    'type'     => 'post',
                    'title'    => get_the_title($p_id),
                    'keyword'  => $kw,
                    'view_url' => get_permalink($p_id),
                    'edit_url' => get_edit_post_link($p_id),
                ];
            }
        }

        // 2. Ambil Semua Pages (Published)
        $pages = get_posts([
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 100,
            'fields'         => 'ids',
        ]);
        foreach ($pages as $pg_id) {
            $kw = self::get_post_focus_keyword($pg_id);
            if (!empty($kw)) {
                $inventory[] = [
                    'id'       => $pg_id,
                    'type'     => 'page',
                    'title'    => get_the_title($pg_id),
                    'keyword'  => $kw,
                    'view_url' => get_permalink($pg_id),
                    'edit_url' => get_edit_post_link($pg_id),
                ];
            }
        }

        // 3. Ambil Semua Tags
        $tags = get_terms([
            'taxonomy'   => 'post_tag',
            'hide_empty' => false,
            'number'     => 150,
        ]);
        if (!empty($tags) && !is_wp_error($tags)) {
            foreach ($tags as $t) {
                $kw = self::get_term_focus_keyword($t->term_id, 'post_tag');
                if (!empty($kw)) {
                    $t_link = get_term_link($t);
                    $inventory[] = [
                        'id'       => $t->term_id,
                        'type'     => 'tag',
                        'title'    => $t->name,
                        'keyword'  => $kw,
                        'view_url' => (!is_wp_error($t_link) ? $t_link : ''),
                        'edit_url' => get_edit_term_link($t->term_id, 'post_tag'),
                    ];
                }
            }
        }

        // 4. Deteksi Konflik Berdasarkan Kemiripan Semantik (Threshold >= 70%)
        $conflicts = [];
        $count = count($inventory);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $a = $inventory[$i];
                $b = $inventory[$j];

                $sim = self::calculate_keyword_similarity($a['keyword'], $b['keyword']);
                if ($sim >= 0.70) {
                    $match_type = ($a['keyword'] === $b['keyword']) ? 'Identik (Exact Duplicate)' : 'Tinggi (Semantically Similar)';
                    $conflicts[] = [
                        'similarity'  => round($sim * 100),
                        'match_type'  => $match_type,
                        'item_a'      => $a,
                        'item_b'      => $b,
                    ];
                }
            }
        }

        // Urutkan konflik dari similarity tertinggi
        usort($conflicts, function ($x, $y) {
            return $y['similarity'] <=> $x['similarity'];
        });

        return [
            'total_scanned'   => $count,
            'total_conflicts' => count($conflicts),
            'conflicts'       => $conflicts,
        ];
    }
}
