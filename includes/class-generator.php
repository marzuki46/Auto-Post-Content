<?php
defined('ABSPATH') || exit;

class APCAI_Generator {

    /**
     * Normalisasi Judul Bahasa Indonesia: Ganti kata 'Panduan' menjadi 'Cara'
     */
    public static function sanitize_indonesian_title(string $text): string {
        $text = trim($text);
        // Ganti "Panduan Lengkap Cara", "Panduan Cara" -> "Cara"
        $text = preg_replace('/\bPanduan\s+(?:Lengkap\s+|Praktis\s+|Mudah\s+)?Cara\b/iu', 'Cara', $text);
        // Ganti "Panduan Lengkap" -> "Cara Lengkap" atau "Cara"
        $text = preg_replace('/\bPanduan\s+Lengkap\b/iu', 'Cara Lengkap', $text);
        $text = preg_replace('/\bPanduan\s+Praktis\b/iu', 'Cara Praktis', $text);
        $text = preg_replace('/\bPanduan\s+Mudah\b/iu', 'Cara Mudah', $text);
        // Ganti kata "Panduan" mandiri menjadi "Cara"
        $text = preg_replace('/\bPanduan\b/iu', 'Cara', $text);
        // Hapus duplikasi kata "Cara Cara" jika ada
        $text = preg_replace('/\bCara\s+Cara\b/iu', 'Cara', $text);
        return trim(preg_replace('/\s+/', ' ', $text));
    }

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
- BOFU (Bottom of Funnel): Keputusan pembelian, harga, layanan, cara implementasi langsung (Transactional).";

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
3. ATURAN KATA 'CARA' (KHUSUS BAHASA INDONESIA): Dilarang menggunakan kata 'Panduan' (seperti 'Panduan Lengkap', 'Panduan Memulai', 'Panduan Praktis'). WAJIB gunakan kata 'Cara' (contoh: 'Cara...', 'Cara Mudah...', 'Cara Efektif...', 'Cara Memilih...').
4. Primary keyword natural.
5. Tentukan nilai 'funnel_stage' secara presisi: 'tofu', 'mofu', atau 'bofu'.

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
            if ($target_lang === 'Bahasa Indonesia') {
                $title = self::sanitize_indonesian_title($title);
            }
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

        $site_url = home_url();
        $site_domain = wp_parse_url($site_url, PHP_URL_HOST);

        $system_prompt = "Anda adalah seorang Jurnalis Senior, Praktisi Industri, dan Master SEO Copywriter.
Tugas Anda: Mengembangkan topik dan data spesifik yang diinputkan pengguna menjadi artikel blog yang mendalam, berbobot, akurat, dan 100% TERASA ALAMI SEPERTI DITULIS MANUSIA ASLI (BEBAS DARI POLA & KLISE ROBOT AI).
Kategori: {$cat_name} | Tahap Funnel: " . strtoupper($funnel_stage) . " | Bahasa: {$lang_name}.
Domain Website Resmi: {$site_domain} ({$site_url}).

ATURAN WAJIB PENULISAN ALAMI & KONTEN (ANTI-AI PATTERN):
1. PATUHI & SERAP DATA INPUT PENGGUNA: Jika pengguna menginputkan data spesifik (tabel komparasi, nama tipe motor, rute lokal seperti Solo/Palang Joglo/Boyolali, pro/kontra, spesifikasi), Anda WAJIB membahas dan menyajikan data tersebut secara utuh dan mendalam ke dalam artikel. Jangan mengabaikan poin-poin yang sudah ditulis pengguna!
2. FORMAT TABEL MARKDOWN: Jika ada data komparasi atau perbandingan, sajikan dalam tabel Markdown standar (| Kolom 1 | Kolom 2 |) yang valid agar otomatis ter-render rapi di website.
3. DILARANG LINK FIKTIF / 'EXAMPLE.COM': JANGAN PERNAH membuat link fiktif seperti 'example.com', 'domainanda.com', atau link karangan sendiri. HANYA gunakan link resmi dari domain situs ini ({$site_url}) atau link WhatsApp resmi yang disediakan di instruksi. Jika tidak ada instruksi link, jangan buat link fiktif.
4. DILARANG KERAS PEMBUKA KLISE: Jangan pernah memulai artikel dengan frasa klise robot seperti: 'Di era digital yang serba cepat...', 'Dalam dunia yang terus berkembang...', 'Tidak dapat dipungkiri bahwa...', 'Apakah Anda sedang mencari...', atau 'Dalam artikel ini kita akan membahas...'. Mulai langsung dengan fakta nyata, studi kasus, keresahan riil di lapangan, atau sudut pandang tajam tanpa basa-basi.
5. DILARANG SUB-JUDUL 'KESIMPULAN': Jangan pernah membuat heading berjudul 'Kesimpulan' atau 'Penutup', dan hindari frasa klise 'Sebagai kesimpulan...', 'Dapat disimpulkan bahwa...', 'Demikianlah pembahasan...'. Gantilah dengan sub-judul penutup yang kontekstual dan berisi rekomendasi langkah aksi nyata (Actionable Next Steps).
6. HINDARI GARIS MENDATAR: JANGAN gunakan garis pemisah horizontal seperti '---', '***', atau '___' di antara bagian artikel (kecuali di dalam sintaks baris pemisah tabel Markdown). Transisi antar bagian harus mengalir alami lewat teks dan sub-judul.
7. RITME KALIMAT ALAMI: Variasikan panjang kalimat (kombinasi kalimat pendek, sedang, dan lugas). Hindari kalimat majemuk berbelit-belit yang membosankan. Tulis dengan nada bicara praktisi berpengalaman yang ramah dan solutif.
8. JANGAN SPAM BOLDING: Hindari menebalkan (**bold**) kata kunci secara berlebihan di setiap paragraf. Bolding hanya dipakai untuk 1-2 poin yang benar-benar krusial.
9. GUNAKAN KATA 'CARA' (BUKAN 'PANDUAN'): Dalam pembuatan konten bahasa Indonesia, JANGAN gunakan kata 'Panduan' (seperti 'Panduan Lengkap...', 'Panduan Memulai...', 'Panduan Praktis...'). WAJIB gunakan kata 'Cara' (contoh: 'Cara...', 'Cara Mudah...', 'Cara Efektif...'). Audiens Indonesia mencari di Google dengan kata 'Cara'.

ATURAN STRUKTUR JSON:
Keluarkan HASIL AKHIR HANYA dalam format JSON valid tanpa teks pengantar atau markdown wrapper dengan struktur:
{
  \"title\": \"Judul artikel menarik, bernas, dan SEO-optimized\",
  \"seo_title\": \"Title tag Google (maks 60 karakter)\",
  \"seo_description\": \"Meta deskripsi persuasif (maks 155 karakter)\",
  \"excerpt\": \"Ringkasan artikel 2-3 kalimat tajam\",
  \"keywords\": [\"keyword 1\", \"keyword 2\", \"keyword 3\", \"keyword 4\", \"keyword 5\"],
  \"content_md\": \"Isi lengkap artikel dalam format Markdown murni (mulai langsung dari paragraf intro atau ## Sub-judul pertama, tanpa H1 # judul, menyerap data input pengguna, tanpa garis horizontal ---, mengalir alami dengan tabel/poin praktis, link internal resmi tanpa domain fiktif, dan tombol CTA)...\"
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

        // Normalisasi kata 'Panduan' menjadi 'Cara' untuk konten Bahasa Indonesia
        if ($lang_name === 'Bahasa Indonesia' && is_array($parsed)) {
            if (!empty($parsed['title'])) {
                $parsed['title'] = self::sanitize_indonesian_title($parsed['title']);
            }
            if (!empty($parsed['seo_title'])) {
                $parsed['seo_title'] = self::sanitize_indonesian_title($parsed['seo_title']);
            }
            if (!empty($parsed['content_md'])) {
                $parsed['content_md'] = preg_replace('/^(#{1,4})\s*Panduan\s+(?:Lengkap\s+|Praktis\s+|Mudah\s+)?Cara\b/imu', '$1 Cara', $parsed['content_md']);
                $parsed['content_md'] = preg_replace('/^(#{1,4})\s*Panduan\s+(?:Lengkap|Praktis|Mudah|Pemula|Terbaru)?\s*/imu', '$1 Cara ', $parsed['content_md']);
                $parsed['content_md'] = preg_replace('/^(#{1,4})\s*Panduan\b/imu', '$1 Cara', $parsed['content_md']);
            }
        }

        // Hapus link fiktif example.com jika model tidak sengaja memunculkannya
        if (is_array($parsed) && !empty($parsed['content_md'])) {
            $parsed['content_md'] = preg_replace('/\[(.*?)\]\(https?:\/\/(?:www\.)?example\.com[^\)]*\)/i', '$1', $parsed['content_md']);
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
STRICT HUMAN-LIKE & ANTI-AI WRITING GUIDELINES:
1. No AI Clichés: Never start with 'In today's fast-paced digital world...', 'In the modern landscape...', 'It is no secret that...', or 'In this article, we delve into...'. Start directly with compelling industry context, relatable real-world friction, or actionable insights.
2. No 'Conclusion' heading: Do not use generic headings like 'Conclusion' or phrases like 'In conclusion...', 'To summarize...'. Use a contextual takeaway or action-oriented heading.
3. No Horizontal Divider Lines: Do NOT insert horizontal lines like '---', '***', or '___' between sections.
4. Natural Sentence Rhythm: Vary sentence length and structure. Write with the tone of an experienced professional practitioner—crisp, authoritative, clear, and engaging. Avoid keyword-bolding spam.
5. Heading Structure: Do NOT output a leading # Title in content_md (the template renders canonical H1). Start directly with intro or ## First Heading. Keep all sub-headings (##, ###), bullet lists, tables, and internal markdown links intact.
6. Call-To-Action (CTA): Adapt any Call-To-Action links or buttons into natural English.
7. Concise Keyword-Focused Slug: Provide a short URL slug (strictly 3 to 5 core English words, maximum 45 characters, no stop words, URL safe).
8. Output Format: Return ONLY a valid JSON object matching this schema without preamble:
{
  \"title\": \"Compelling English Click-Worthy & SEO Title\",
  \"slug\": \"short-core-keyword-slug\",
  \"seo_title\": \"Google Title Tag (max 60 chars)\",
  \"seo_description\": \"Compelling meta description (max 155 chars)\",
  \"excerpt\": \"English summary in 2-3 sentences\",
  \"keywords\": [\"keyword 1\", \"keyword 2\", \"keyword 3\", \"keyword 4\"],
  \"content_md\": \"Full translated English article in Markdown without horizontal lines...\"
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
            // Hapus link fiktif example.com
            $parsed['content_md'] = preg_replace('/\[(.*?)\]\(https?:\/\/(?:www\.)?example\.com[^\)]*\)/i', '$1', $parsed['content_md']);
        }

        return $parsed;
    }

    /**
     * Konversi Markdown ke HTML ramah WordPress Gutenberg / Classic (Termasuk Render Tabel & Bebas Garis Mendatar)
     */
    public static function markdown_to_html($md) {
        // 1. Ganti heading "Kesimpulan" atau "Penutup" klise menjadi lebih berorientasi aksi nyata
        $md = preg_replace('/^(#{2,4})\s*(?:Kesimpulan|Penutup)\b/im', '$1 Langkah Praktis Selanjutnya', $md);

        // 2. Normalisasi kata "Panduan" pada heading menjadi "Cara"
        $md = preg_replace('/^(#{1,4})\s*Panduan\s+(?:Lengkap\s+|Praktis\s+|Mudah\s+)?Cara\b/imu', '$1 Cara', $md);
        $md = preg_replace('/^(#{1,4})\s*Panduan\s+(?:Lengkap|Praktis|Mudah|Pemula|Terbaru)?\s*/imu', '$1 Cara ', $md);
        $md = preg_replace('/^(#{1,4})\s*Panduan\b/imu', '$1 Cara', $md);

        $lines = explode("\n", $md);
        $html = '';
        $in_list = false;
        $table_buffer = [];

        $flush_table = function() use (&$html, &$table_buffer) {
            if (!empty($table_buffer)) {
                $html .= self::render_markdown_table($table_buffer);
                $table_buffer = [];
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Deteksi baris Markdown Table (| cell | cell |)
            if (!empty($trimmed) && str_starts_with($trimmed, '|') && str_ends_with($trimmed, '|')) {
                if ($in_list) {
                    $html .= "</ul>\n";
                    $in_list = false;
                }
                $table_buffer[] = $trimmed;
                continue;
            }

            // Jika baris bukan bagian dari tabel, flush buffer tabel yang ada
            $flush_table();

            if (empty($trimmed)) {
                if ($in_list) {
                    $html .= "</ul>\n";
                    $in_list = false;
                }
                continue;
            }

            // Bersihkan garis mendatar (---, ***, ___, <hr>)
            if (preg_match('/^[-*_]{3,}$/', $trimmed) || preg_match('/^<hr\s*\/?>$/i', $trimmed)) {
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
            if ($para_text === '---' || $para_text === '***' || $para_text === '___') {
                continue;
            }
            $html .= "<p>{$para_text}</p>\n";
        }

        $flush_table();

        if ($in_list) {
            $html .= "</ul>\n";
        }

        return $html;
    }

    /**
     * Render Buffer Baris Markdown Table Menjadi HTML <table> yang Rapi dan Responsif
     */
    private static function render_markdown_table(array $lines): string {
        if (count($lines) < 2) {
            // Bukan tabel utuh, cetak per paragraf biasa
            $res = '';
            foreach ($lines as $l) {
                $res .= "<p>" . self::format_inline_markdown($l) . "</p>\n";
            }
            return $res;
        }

        $headers = [];
        $alignments = [];
        $rows = [];
        $parsed_header = false;

        foreach ($lines as $idx => $line) {
            $line_content = trim($line, "|\t ");
            $cols = array_map('trim', explode('|', $line_content));

            // Cek apakah baris pemisah (misal: | :--- | :--- |)
            $is_separator = true;
            $current_aligns = [];
            foreach ($cols as $col) {
                if (preg_match('/^:?-+:?$/', $col)) {
                    $align = 'left';
                    if (str_starts_with($col, ':') && str_ends_with($col, ':')) {
                        $align = 'center';
                    } elseif (str_ends_with($col, ':')) {
                        $align = 'right';
                    }
                    $current_aligns[] = $align;
                } else {
                    $is_separator = false;
                    break;
                }
            }

            if ($is_separator) {
                $alignments = $current_aligns;
                continue;
            }

            if (!$parsed_header) {
                $headers = $cols;
                $parsed_header = true;
            } else {
                $rows[] = $cols;
            }
        }

        if (empty($headers)) {
            return '';
        }

        $html = "<div class=\"apcai-table-responsive\" style=\"overflow-x:auto;margin:24px 0;\">\n";
        $html .= "<table style=\"width:100%;border-collapse:collapse;margin:0;font-size:15px;line-height:1.6;border:1px solid #e2e8f0;\">\n";

        // Render thead
        $html .= "  <thead>\n    <tr style=\"background-color:#f8fafc;\">\n";
        foreach ($headers as $i => $h) {
            $align = $alignments[$i] ?? 'left';
            $h_fmt = self::format_inline_markdown($h);
            $html .= "      <th style=\"border:1px solid #cbd5e1;padding:12px 14px;text-align:{$align};font-weight:600;color:#1e293b;\">{$h_fmt}</th>\n";
        }
        $html .= "    </tr>\n  </thead>\n";

        // Render tbody
        $html .= "  <tbody>\n";
        foreach ($rows as $r_idx => $row) {
            $bg = ($r_idx % 2 === 1) ? 'background-color:#fdfdfd;' : 'background-color:#ffffff;';
            $html .= "    <tr style=\"{$bg}\">\n";
            foreach ($headers as $i => $h) {
                $cell = $row[$i] ?? '';
                $align = $alignments[$i] ?? 'left';
                $cell_fmt = self::format_inline_markdown($cell);
                $html .= "      <td style=\"border:1px solid #e2e8f0;padding:10px 14px;text-align:{$align};color:#334155;\">{$cell_fmt}</td>\n";
            }
            $html .= "    </tr>\n";
        }
        $html .= "  </tbody>\n";
        $html .= "</table>\n</div>\n";

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
        if ($language === 'id') {
            $title = self::sanitize_indonesian_title($title);
        }
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

        $seo_title = sanitize_text_field($data['seo_title'] ?? $title);
        if ($language === 'id') {
            $seo_title = self::sanitize_indonesian_title($seo_title);
        }

        // Simpan ke tabel relasi terpisah
        $wpdb->insert($table_rel, [
            'post_id'         => $post_id,
            'category_id'     => $cat_id,
            'funnel_stage'    => $funnel_stage,
            'translation_of'  => $translation_of,
            'language'        => $language,
            'topic_id'        => $topic_id,
            'seo_title'       => $seo_title,
            'seo_description' => sanitize_text_field($data['seo_description'] ?? $excerpt),
            'keywords'        => $kw_json,
            'word_count'      => $word_count,
            'created_at'      => current_time('mysql'),
        ]);

        // Simpan meta standar Yoast / RankMath
        update_post_meta($post_id, '_apcai_language', $language);
        update_post_meta($post_id, '_apcai_funnel_stage', $funnel_stage);
        update_post_meta($post_id, '_apcai_translation_of', $translation_of);
        if (!empty($seo_title)) {
            update_post_meta($post_id, '_yoast_wpseo_title', $seo_title);
            update_post_meta($post_id, 'rank_math_title', $seo_title);
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
