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

        // 4. Trigger Yoast Saved Post Action
        if (has_action('wpseo_saved_postdata')) {
            do_action('wpseo_saved_postdata', $post_id);
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
        $lev_ratio = $sim_percent / 100.0;

        return max($jaccard, $lev_ratio);
    }

    /**
     * Resolusi Anti-Kanibalisasi Post vs Tag Keyword:
     * Jika keyword post mirip/bentrok dengan salah satu tag (atau sebaliknya):
     * - Tag = Broad / Topic Hub / MOFU Curated ("Kumpulan Solusi", "Direktori", "Hub")
     * - Post = Specific Long-Tail / Intent-Driven ("Cara Memilih...", "Tips...", "Langkah...")
     * Menghasilkan keyword unik tanpa kanibalisasi internal.
     */
    public static function resolve_cannibalization(string $candidate_kw, string $type = 'post', int $current_id = 0, string $lang = 'id', string $title = ''): string {
        $candidate_kw = trim($candidate_kw);
        if (empty($candidate_kw)) {
            return $candidate_kw;
        }

        // 1. Dapatkan daftar keyword aktif di kubu lawan
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
        } else {
            // Tipe tag: cek terhadap recent post keywords
            $recent_posts = get_posts([
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'posts_per_page' => 150,
                'fields'         => 'ids',
            ]);
            foreach ($recent_posts as $p_id) {
                if ($p_id === $current_id) continue;
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
            // Jika ada judul artikel, ambil modifier spesifik dari judul
            if (!empty($title)) {
                $clean_t = preg_replace('/[|\-–—:]+.*$/', '', $title);
                $title_words = array_filter(explode(' ', trim($clean_t)));
                if (count($title_words) >= 3) {
                    $long_tail = implode(' ', array_slice($title_words, 0, 5));
                    if (self::calculate_keyword_similarity($long_tail, $conflicting_kw) < 0.75) {
                        return $long_tail;
                    }
                }
            }

            // Fallback modifier long-tail intent per bahasa
            if ($lang === 'en') {
                $prefixes = ['How to Choose', 'Complete Review of', 'Best Tips for', 'Practical Steps for'];
            } elseif ($lang === 'ms') {
                $prefixes = ['Cara Memilih', 'Ulasan Penuh', 'Tips Terbaik', 'Langkah'];
            } else {
                $prefixes = ['Cara Memilih', 'Tips Terbaik', 'Langkah Mudah', 'Rekomendasi'];
            }
            $pfx = $prefixes[array_rand($prefixes)];
            return "{$pfx} {$candidate_kw}";
        } else {
            // Tag butuh frasa payung / agregator / topic cluster
            if ($lang === 'en') {
                $suffixes = ['Hub & Guide', 'Topic Overview', 'Curated Directory', 'Solutions'];
            } elseif ($lang === 'ms') {
                $suffixes = ['Koleksi & Panduan', 'Direktori Topik', 'Pusat Maklumat'];
            } else {
                $suffixes = ['Pusat Informasi', 'Koleksi Topik', 'Direktori Pilihan'];
            }
            $sfx = $suffixes[array_rand($suffixes)];
            return "{$candidate_kw} {$sfx}";
        }
    }
}
