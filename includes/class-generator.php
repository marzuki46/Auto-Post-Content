<?php
defined('ABSPATH') || exit;

class APCAI_Generator {

    /**
     * Riset Topik Baru via AI dengan Tag Kategori & Funneling (TOFU / MOFU / BOFU)
     */
    public static function brainstorm_topics($count = 5, $category_id = 0, $funnel_filter = '') {
        global $wpdb;
        $settings = get_option('apcai_settings', []);
        $table_topics = APCAI_DB::table_topics();

        // Tentukan Kategori Target
        if (empty($category_id)) {
            $categories = get_categories(['hide_empty' => false]);
            if (!empty($categories)) {
                $category_id = $categories[array_rand($categories)]->term_id;
            } else {
                $category_id = 1;
            }
        }
        $cat_obj = get_category($category_id);
        $cat_name = $cat_obj ? $cat_obj->name : 'Umum';

        // Ambil riwayat judul agar tidak duplikat
        $existing_topics = $wpdb->get_col($wpdb->prepare(
            "SELECT title FROM $table_topics WHERE category_id = %d ORDER BY id DESC LIMIT 100",
            $category_id
        ));
        $existing_posts  = $wpdb->get_col($wpdb->prepare(
            "SELECT post_title FROM {$wpdb->posts} p 
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id 
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
             WHERE tt.term_id = %d AND p.post_type = 'post' AND p.post_status != 'trash' 
             ORDER BY p.ID DESC LIMIT 100",
            $category_id
        ));
        $existing = array_unique(array_filter(array_merge($existing_topics, $existing_posts)));
        $existing_text = !empty($existing) ? "- " . implode("\n- ", array_slice($existing, 0, 40)) : "(belum ada)";

        $brand_ctx = [];
        if (!empty($settings['site_name']))   $brand_ctx[] = "Website: " . $settings['site_name'];
        if (!empty($settings['niche']))       $brand_ctx[] = "Niche Utama: " . $settings['niche'];
        $brand_ctx[] = "Fokus Kategori / Sub-Niche: " . $cat_name;
        if (!empty($settings['audience']))    $brand_ctx[] = "Target audience: " . $settings['audience'];
        if (!empty($settings['brand_voice'])) $brand_ctx[] = "Brand voice: " . $settings['brand_voice'];
        $brand_ctx_str = implode("\n", $brand_ctx);

        $target_lang = ($settings['default_lang'] ?? 'id') === 'en' ? 'English' : 'Bahasa Indonesia';

        $funnel_instruction = "Distribusikan ide topik ke dalam piramida funneling pemasaran:
- TOFU (Top of Funnel): Masalah awal, edukasi, definisi, cara, tips umum (Informational).
- MOFU (Middle of Funnel): Perbandingan, review, studi kasus, alternatif, rekomendasi solusi terbaik (Commercial).
- BOFU (Bottom of Funnel): Keputusan pembelian, harga, layanan, panduan implementasi langsung (Transactional).";

        if (!empty($funnel_filter)) {
            $funnel_instruction = "Hanya fokus buat ide topik pada tahap: " . strtoupper($funnel_filter);
        }

        $system_prompt = "Kamu adalah Master SEO Content Strategist & Media Editor. Kamu menyusun ide judul artikel blog yang ringkas, berbobot, manusiawi, optimal untuk Search Console, dan terstruktur dalam Content Funnel (TOFU/MOFU/BOFU) untuk kategori: {$cat_name}. Tulis output dalam {$target_lang}.";

        $user_prompt = "Rancang $count ide artikel blog BARU untuk kategori '{$cat_name}'.

{$brand_ctx_str}

PEDOMAN STRATEGI FUNNELING:
{$funnel_instruction}

Artikel yang sudah ada di kategori ini (JANGAN ulangi):
{$existing_text}

ATURAN FORMAT JUDUL:
1. BATAS PANJANG: Wajib 45 - 65 karakter (maksimal 70 karakter).
2. Dilarang kata-kata klise AI ('Menyelami', 'Mengupas Tuntas', 'Rombak UI', 'Mesin Konversi', titik dua panjang).
3. Primary keyword natural.
4. Tentukan nilai 'funnel_stage' secara presisi: 'tofu', 'mofu', atau 'bofu'.

Balas HANYA dengan JSON array valid tanpa teks pengantar:
[{\"title\": \"string\", \"primary_keyword\": \"string\", \"intent\": \"string\", \"funnel_stage\": \"tofu|mofu|bofu\", \"angle\": \"string\"}]";

        // Fallback jika model planner kosong atau belum terset
        $model = !empty($settings['model_planner']) ? $settings['model_planner'] : (!empty($settings['model_writer']) ? $settings['model_writer'] : 'openai/gpt-4o-mini');
        
        $raw_response = APCAI_AI_Client::chat($system_prompt, $user_prompt, $model, 0.7);

        if (is_wp_error($raw_response)) {
            return $raw_response;
        }

        $parsed = APCAI_AI_Client::extract_ideas($raw_response);
        if (empty($parsed)) {
            return new WP_Error('parse_failed', 'Gagal membaca format JSON topik dari AI. Respon AI: ' . substr($raw_response, 0, 200));
        }

        $created = 0;
        $total_parsed = count($parsed);
        $duplicate_count = 0;

        foreach ($parsed as $item) {
            if (empty($item['title'])) continue;
            $title = sanitize_text_field($item['title']);
            $keyword = sanitize_text_field($item['primary_keyword'] ?? '');
            $intent = sanitize_text_field($item['intent'] ?? 'informational');
            $funnel = strtolower(sanitize_text_field($item['funnel_stage'] ?? 'tofu'));
            if (!in_array($funnel, ['tofu', 'mofu', 'bofu'])) $funnel = 'tofu';
            $angle = sanitize_textarea_field($item['angle'] ?? '');

            // Duplikasi check
            $check = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_topics WHERE title = %s", $title));
            if (!$check) {
                $wpdb->insert($table_topics, [
                    'category_id'     => $category_id,
                    'funnel_stage'    => $funnel,
                    'title'           => $title,
                    'primary_keyword' => $keyword,
                    'intent'          => $intent,
                    'angle'           => $angle,
                    'status'          => 'idea',
                    'source'          => 'ai_brainstorm',
                    'created_at'      => current_time('mysql'),
                    'updated_at'      => current_time('mysql'),
                ]);
                $created++;
            } else {
                $duplicate_count++;
            }
        }

        return [
            'created'         => $created,
            'category_id'     => $category_id,
            'category_name'   => $cat_name,
            'total_items'     => $total_parsed,
            'duplicate_count' => $duplicate_count,
        ];
    }

    /**
     * Dapatkan Konteks Internal Link & Target Landing Page (Money Page) Berdasarkan Kategori & Silo
     */
    public static function get_internal_link_context($category_id, $current_funnel = 'tofu') {
        global $wpdb;
        $context_instruction = "";
        $target_landing_url = "";
        $chosen_anchor = "";

        // 1. Ambil Target Landing Page (Money Page) untuk kategori ini
        $t_landing = APCAI_DB::table_landing_pages();
        $landing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $t_landing WHERE category_id = %d AND status = 'active' ORDER BY RAND() LIMIT 1",
            $category_id
        ));

        // Fallback jika tidak ada landing page khusus kategori tsb, cari yang general
        if (!$landing) {
            $landing = $wpdb->get_row("SELECT * FROM $t_landing WHERE status = 'active' ORDER BY RAND() LIMIT 1");
        }

        if ($landing) {
            $anchors = array_filter(array_map('trim', explode(',', $landing->anchor_keywords)));
            if (!empty($anchors)) {
                $chosen_anchor = $anchors[array_rand($anchors)];
            } else {
                $chosen_anchor = $landing->page_title ?: 'Lihat Penawaran Selengkapnya';
            }
            $target_landing_url = esc_url_raw($landing->page_url);

            $context_instruction .= "PENTING - TARGET LANDING PAGE (MONEY PAGE) INTERNAL LINK:\n" .
                "- Anda WAJIB menyisipkan 1 link rekomendasi utama yang mengarah ke Landing Page produk/jasa resmi kami.\n" .
                "- URL Tujuan: {$target_landing_url}\n" .
                "- Teks Jangkar (Anchor Text): Wajib menggunakan variasi kata kunci berikut secara natural di dalam kalimat: [{$chosen_anchor}]({$target_landing_url})\n" .
                "- Sisipkan link ini di bagian solusi, rekomendasi layanan, atau sebelum kesimpulan.\n\n";
        }

        // 2. Ambil 1-2 Artikel Terkait dalam Silo Kategori yang Sama (Lateral / Down-Funneling Link)
        $t_rel = APCAI_DB::table_posts_meta();
        $silo_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT r.post_id, r.funnel_stage, p.post_title, p.guid 
             FROM $t_rel r 
             INNER JOIN {$wpdb->posts} p ON r.post_id = p.ID 
             WHERE r.category_id = %d AND r.language = 'id' AND p.post_status = 'publish' 
             ORDER BY r.id DESC LIMIT 5",
            $category_id
        ));

        if (!empty($silo_posts)) {
            $links_str = "";
            $count = 0;
            foreach ($silo_posts as $sp) {
                $permalink = get_permalink($sp->post_id);
                if ($permalink) {
                    $links_str .= "- Judul: \"{$sp->post_title}\" (Tahap: {$sp->funnel_stage}) -> Link: {$permalink}\n";
                    $count++;
                    if ($count >= 2) break;
                }
            }

            if (!empty($links_str)) {
                $context_instruction .= "STRUKTUR SILO INTERNAL LINKING (KATEGORI SEJENIS):\n" .
                    "- Anda WAJIB menyisipkan minimal 1 link kontekstual ke artikel bacaan terkait di bawah ini di dalam paragraf isi secara mengalir:\n" .
                    $links_str .
                    "- Gunakan anchor text kata kunci yang relevan dan natural (dilarang menggunakan kata 'klik disini').\n\n";
            }
        }

        return [
            'instruction' => $context_instruction,
            'landing_url' => $target_landing_url,
            'landing_anchor' => $chosen_anchor,
        ];
    }

    /**
     * Generate Konten Utama (Bilingual Ready + Funneling + Auto Internal Linking)
     */
    public static function generate_primary_content($input_topic, $category_id = 1, $funnel_stage = 'tofu', $custom_options = []) {
        $settings = get_option('apcai_settings', []);

        $keyword_prompt  = !empty($custom_options['keyword_prompt']) ? $custom_options['keyword_prompt'] : ($settings['keyword_prompt'] ?? '');
        $content_prompt  = !empty($custom_options['content_prompt']) ? $custom_options['content_prompt'] : ($settings['content_prompt'] ?? '');
        $cta_type        = !empty($custom_options['cta_type']) ? $custom_options['cta_type'] : ($settings['cta_type'] ?? 'none');
        $cta_text        = !empty($custom_options['cta_text']) ? $custom_options['cta_text'] : ($settings['cta_text'] ?? 'Hubungi Kami');
        $cta_target      = !empty($custom_options['cta_target']) ? $custom_options['cta_target'] : ($settings['cta_target'] ?? '');
        $cta_instruction = !empty($custom_options['cta_instruction']) ? $custom_options['cta_instruction'] : ($settings['cta_instruction'] ?? '');

        // Susun Call To Action guideline
        $cta_guideline = '';
        if ($cta_type === 'whatsapp' && !empty($cta_target)) {
            $clean_wa = preg_replace('/\D/', '', $cta_target);
            $wa_url = "https://wa.me/{$clean_wa}?text=" . rawurlencode("Halo Admin, saya membaca artikel '{$input_topic}' dan ingin info pemesanan/konsultasi.");
            $cta_guideline = "PENTING - INTEGRASI CALL TO ACTION (CTA) WHATSAPP:\n" .
                "- Tujuan: Mengarahkan pembaca berkonsultasi/order via WhatsApp ({$clean_wa}).\n" .
                "- Format link tombol CTA di Markdown wajib: [{$cta_text}]({$wa_url})\n" .
                "- Sisipkan 1 penawaran persuasif di tengah dan 1 penutup di akhir artikel.";
        } elseif ($cta_type === 'link' && !empty($cta_target)) {
            $cta_guideline = "PENTING - CALL TO ACTION (CTA) LINK TUJUAN:\n" .
                "- Arahkan pembaca ke URL: {$cta_target} dengan teks: [{$cta_text}]({$cta_target})";
        } elseif ($cta_type === 'custom' && !empty($cta_instruction)) {
            $cta_guideline = "PENTING - CALL TO ACTION (CTA) KHUSUS:\n{$cta_instruction}";
        }

        // Ambil Instruksi Internal Linking Silo & Target Landing Page
        $linking = self::get_internal_link_context($category_id, $funnel_stage);
        $link_instruction = $linking['instruction'];

        $default_lang = $settings['default_lang'] ?? 'id';
        $lang_name = ($default_lang === 'en') ? 'English' : 'Bahasa Indonesia';
        $cat_obj = get_category($category_id);
        $cat_name = $cat_obj ? $cat_obj->name : 'General';

        $system_prompt = "Anda adalah seorang Master SEO Content Strategist & Senior Conversion Copywriter.
Kembangkan topik dari pengguna menjadi artikel blog SEO mendalam dan komprehensif.
Kategori: {$cat_name} | Tahap Funnel: " . strtoupper($funnel_stage) . " | Bahasa: {$lang_name}.
ATURAN OUTPUT:
Keluarkan HASIL AKHIR HANYA dalam format JSON valid tanpa teks pengantar atau markdown block dengan struktur:
{
  \"title\": \"Judul artikel yang menarik klik (Click-worthy & SEO optimized)\",
  \"seo_title\": \"Title tag untuk Google (maks 60 karakter)\",
  \"seo_description\": \"Meta deskripsi persuasif (maks 155 karakter)\",
  \"excerpt\": \"Ringkasan artikel 2-3 kalimat\",
  \"keywords\": [\"keyword 1\", \"keyword 2\", \"keyword 3\", \"keyword 4\", \"keyword 5\"],
  \"content_md\": \"Isi lengkap artikel dalam format Markdown (mulai langsung dari ## Sub-judul atau paragraf intro, tanpa judul # H1 terpisah), bullet points, link internal natural, CTA link yang rapi...\"
}";

        $user_prompt = "INPUT AWAL DARI PENGGUNA (TOPIK / PARAGRAF):
\"\"\"
" . substr($input_topic, 0, 10000) . "
\"\"\"

PANDUAN RISET ENTITY & KEYWORD:
\"\"\"
" . substr($keyword_prompt, 0, 3000) . "
\"\"\"

PANDUAN PENGEMBANGAN KONTEN:
\"\"\"
" . substr($content_prompt, 0, 5000) . "
\"\"\"

" . (!empty($link_instruction) ? "PANDUAN ARSITEKTUR SILO & TARGET LANDING PAGE:\n\"\"\"\n{$link_instruction}\"\"\"\n\n" : "") . "
" . (!empty($cta_guideline) ? "INSTRUKSI CALL TO ACTION (CTA):\n\"\"\"\n{$cta_guideline}\"\"\"\n" : "");

        $model = $settings['model_writer'] ?? 'openai/gpt-4o';
        $raw = APCAI_AI_Client::chat($system_prompt, $user_prompt, $model, 0.7);

        if (is_wp_error($raw)) {
            return $raw;
        }

        $parsed = APCAI_AI_Client::extract_json($raw);
        if (is_wp_error($parsed)) {
            return $parsed;
        }

        return $parsed;
    }

    /**
     * Generate Konten Bahasa Inggris (Adaptasi SEO EN - Persis CMS SEO generate-en)
     */
    public static function generate_english_adaptation($id_title, $id_content, $id_excerpt = '', $id_keywords = []) {
        $settings = get_option('apcai_settings', []);

        $system_prompt = "You are an elite bilingual SEO Content Strategist and Native English Tech/Business Copywriter.
Translate and adapt the user's article into native, engaging, high-ranking ENGLISH content for a global audience.
Strictly adhere to system guidelines:
1. Native English Adaptation: Do not perform a literal word-for-word translation. Adapt the tone to be fluent, authoritative, and natural American/International English.
2. Heading Structure: Do NOT output a leading # Title in content_md (the template renders canonical H1). Start directly with intro or ## First Heading. Keep all sub-headings (##, ###), bullet lists, tables, and internal markdown links intact.
3. Call-To-Action (CTA): Adapt any Call-To-Action links or buttons into natural English.
4. Concise Keyword-Focused Slug: Provide a short URL slug (strictly 3 to 5 core English words, maximum 45 characters, no stop words, URL safe).
5. Output Format: Return ONLY a valid JSON object matching this schema without preamble:
{
  \"title\": \"Compelling English Click-Worthy & SEO Title\",
  \"slug\": \"short-core-keyword-slug\",
  \"seo_title\": \"Google Title Tag (max 60 chars)\",
  \"seo_description\": \"Compelling meta description (max 155 chars)\",
  \"excerpt\": \"English summary in 2-3 sentences\",
  \"keywords\": [\"keyword 1\", \"keyword 2\", \"keyword 3\", \"keyword 4\"],
  \"content_md\": \"Full translated English article in Markdown...\"
}";

        $kw_str = is_array($id_keywords) ? implode(", ", $id_keywords) : (string)$id_keywords;
        $user_prompt = "ORIGINAL ARTICLE:
TITLE: \"\"\"" . substr($id_title, 0, 300) . "\"\"\"
EXCERPT: \"\"\"" . substr($id_excerpt, 0, 500) . "\"\"\"
KEYWORDS: \"\"\"" . substr($kw_str, 0, 300) . "\"\"\"
CONTENT (MARKDOWN):
\"\"\"
" . substr($id_content, 0, 45000) . "
\"\"\"";

        $model = $settings['model_writer'] ?? 'openai/gpt-4o';
        $raw = APCAI_AI_Client::chat($system_prompt, $user_prompt, $model, 0.6);

        if (is_wp_error($raw)) {
            return $raw;
        }

        $parsed = APCAI_AI_Client::extract_json($raw);
        if (is_wp_error($parsed)) {
            return $parsed;
        }

        // Strip leading # H1 if any
        if (isset($parsed['content_md'])) {
            $parsed['content_md'] = preg_replace('/^#\s+[^\n]+(?:\r?\n|$)/', '', trim($parsed['content_md']));
        }

        return $parsed;
    }

    /**
     * Konversi Markdown ke HTML ramah WordPress Gutenberg / Classic
     */
    public static function markdown_to_html($md) {
        $lines = explode("\n", $md);
        $html = '';
        $in_list = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if (empty($trimmed)) {
                if ($in_list) {
                    $html .= "</ul>\n";
                    $in_list = false;
                }
                continue;
            }

            // Headings
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trimmed, $m)) {
                if ($in_list) {
                    $html .= "</ul>\n";
                    $in_list = false;
                }
                $level = strlen($m[1]);
                $heading_text = self::format_inline_markdown($m[2]);
                $html .= "<h{$level}>{$heading_text}</h{$level}>\n";
                continue;
            }

            // Bullet Lists
            if (preg_match('/^[-*+]\s+(.*)$/', $trimmed, $m)) {
                if (!$in_list) {
                    $html .= "<ul>\n";
                    $in_list = true;
                }
                $item_text = self::format_inline_markdown($m[1]);
                $html .= "<li>{$item_text}</li>\n";
                continue;
            }

            if ($in_list) {
                $html .= "</ul>\n";
                $in_list = false;
            }

            // Paragraph
            $para_text = self::format_inline_markdown($trimmed);
            $html .= "<p>{$para_text}</p>\n";
        }

        if ($in_list) {
            $html .= "</ul>\n";
        }

        return $html;
    }

    private static function format_inline_markdown($text) {
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*([^\*]+)\*/', '<em>$1</em>', $text);
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>', $text);
        return $text;
    }

    /**
     * Simpan Artikel ke wp_posts dan catat relasi tabel terpisah
     */
    public static function save_article($data, $language, $translation_of = 0, $topic_id = 0, $post_status = '', $category_id = 1, $funnel_stage = 'tofu') {
        global $wpdb;
        $settings = get_option('apcai_settings', []);
        $table_rel = APCAI_DB::table_posts_meta();

        if (empty($post_status)) {
            $post_status = $settings['post_status'] ?? 'publish';
        }

        $title = sanitize_text_field($data['title'] ?? 'Untitled Article');
        $slug  = sanitize_title($data['slug'] ?? $title);
        $raw_md = $data['content_md'] ?? '';
        $html_content = self::markdown_to_html($raw_md);
        $excerpt = sanitize_textarea_field($data['excerpt'] ?? '');

        $author_id = absint($settings['post_author'] ?? 1);
        $cat_id    = absint($category_id ?: ($settings['post_category'] ?? 1));

        $postarr = [
            'post_title'    => $title,
            'post_name'     => $slug,
            'post_content'  => $html_content,
            'post_excerpt'  => $excerpt,
            'post_status'   => $post_status,
            'post_author'   => $author_id,
            'post_category' => [$cat_id],
            'post_type'     => 'post',
        ];

        $post_id = wp_insert_post($postarr);
        if (is_wp_error($post_id) || !$post_id) {
            return new WP_Error('insert_failed', 'Gagal membuat post di WordPress.');
        }

        $word_count = str_word_count(strip_tags($html_content));
        $kw_json = is_array($data['keywords'] ?? null) ? wp_json_encode($data['keywords']) : ($data['keywords'] ?? '');

        // Simpan ke tabel relasi terpisah
        $wpdb->insert($table_rel, [
            'post_id'         => $post_id,
            'category_id'     => $cat_id,
            'funnel_stage'    => $funnel_stage,
            'translation_of'  => $translation_of,
            'language'        => $language,
            'topic_id'        => $topic_id,
            'seo_title'       => sanitize_text_field($data['seo_title'] ?? $title),
            'seo_description' => sanitize_text_field($data['seo_description'] ?? $excerpt),
            'keywords'        => $kw_json,
            'word_count'      => $word_count,
            'created_at'      => current_time('mysql'),
        ]);

        // Simpan meta standar Yoast / RankMath
        update_post_meta($post_id, '_apcai_language', $language);
        update_post_meta($post_id, '_apcai_funnel_stage', $funnel_stage);
        update_post_meta($post_id, '_apcai_translation_of', $translation_of);
        if (!empty($data['seo_title'])) {
            update_post_meta($post_id, '_yoast_wpseo_title', $data['seo_title']);
            update_post_meta($post_id, 'rank_math_title', $data['seo_title']);
        }
        if (!empty($data['seo_description'])) {
            update_post_meta($post_id, '_yoast_wpseo_metadesc', $data['seo_description']);
            update_post_meta($post_id, 'rank_math_description', $data['seo_description']);
        }
        if (!empty($data['keywords']) && is_array($data['keywords'])) {
            wp_set_post_tags($post_id, $data['keywords'], true);
        }

        // Auto Download / Set Featured Image (WebP Compressed)
        $auto_thumb = $settings['auto_thumbnail'] ?? '1';
        $thumb_source = $settings['thumbnail_source'] ?? 'bing'; // 'bing' atau 'pool'

        if ($auto_thumb === '1' && class_exists('APCAI_Image_Manager')) {
            $title = sanitize_text_field($data['title'] ?? '');
            $alt_text = !empty($data['keywords'][0]) ? ($data['keywords'][0] . ' - ' . $title) : $title;
            $caption  = !empty($data['excerpt']) ? $data['excerpt'] : $title;

            $attached = false;
            // Jika memilih foto sendiri (Pool Koleksi Gambar Lokal)
            if ($thumb_source === 'pool') {
                $attached = APCAI_Image_Manager::attach_from_local_pool($post_id, $alt_text, $title, $caption);
            }

            // Jika mode Bing atau jika pool lokal masih kosong, ambil dari Bing
            if (!$attached) {
                $search_kw = '';
                if (!empty($data['keywords']) && is_array($data['keywords'])) {
                    $search_kw = reset($data['keywords']);
                }
                if (empty($search_kw)) {
                    $search_kw = $title;
                }

                APCAI_Image_Manager::attach_bing_featured_image(
                    $post_id,
                    $search_kw,
                    $alt_text,
                    $title,
                    $caption
                );
            }
        }

        return $post_id;
    }
}
