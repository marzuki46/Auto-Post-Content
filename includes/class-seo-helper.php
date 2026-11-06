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
}
