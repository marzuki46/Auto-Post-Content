<?php
defined('ABSPATH') || exit;

class APCAI_Tag_Manager {

    public static function init() {
        // Frontend Hooks: Tampilkan konten copywriting tag di bawah daftar post
        add_action('loop_end', [__CLASS__, 'render_tag_content_below_posts']);
        add_shortcode('apcai_tag_content', [__CLASS__, 'shortcode_tag_content']);

        // Admin Hooks: Tambahkan form generator di edit tag native WordPress (Posts -> Tags)
        if (is_admin()) {
            add_action('post_tag_edit_form_fields', [__CLASS__, 'render_tag_edit_fields'], 10, 2);
            add_action('edited_post_tag', [__CLASS__, 'save_tag_edit_fields'], 10, 2);
            add_filter('manage_edit-post_tag_columns', [__CLASS__, 'add_tag_columns']);
            add_filter('manage_post_tag_custom_column', [__CLASS__, 'render_tag_column'], 10, 3);
            add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_tag_scripts']);

            // AJAX Generator & Save
            add_action('wp_ajax_apcai_generate_tag_copywriting', [__CLASS__, 'ajax_generate_tag_copywriting']);
            add_action('wp_ajax_apcai_save_tag_copywriting', [__CLASS__, 'ajax_save_tag_copywriting']);
        }
    }

    /**
     * Enqueue asset pendukung untuk halaman edit tag WordPress
     */
    public static function enqueue_tag_scripts($hook) {
        if ($hook === 'term.php' || $hook === 'edit-tags.php') {
            $screen = get_current_screen();
            if ($screen && $screen->taxonomy === 'post_tag') {
                wp_enqueue_style('apcai-admin-style', APCAI_PLUGIN_URL . 'assets/css/admin.css', [], APCAI_VERSION);
                wp_enqueue_script('apcai-tag-script', APCAI_PLUGIN_URL . 'assets/js/tag-manager.js', ['jquery'], APCAI_VERSION, true);
                wp_localize_script('apcai-tag-script', 'apcai_tag_vars', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce'    => wp_create_nonce('apcai_tag_nonce'),
                ]);
            }
        }
    }

    /**
     * Kolom status copywriting di daftar tag (Posts -> Tags)
     */
    public static function add_tag_columns($columns) {
        $columns['apcai_copywriting'] = APCAI_I18n::t('AI Copywriting Hub', 'AI Copywriting Hub');
        return $columns;
    }

    public static function render_tag_column($content, $column_name, $term_id) {
        if ($column_name === 'apcai_copywriting') {
            $has_content = get_term_meta($term_id, '_apcai_tag_copywriting', true);
            if (!empty($has_content)) {
                $word_count = str_word_count(strip_tags($has_content));
                $lang = strtoupper(get_term_meta($term_id, '_apcai_tag_lang', true) ?: 'ID');
                $funnel = strtoupper(get_term_meta($term_id, '_apcai_tag_funnel', true) ?: 'MOFU');
                return '<span class="apcai-badge apcai-badge-green">Aktif (' . $word_count . ' kata | ' . $lang . ' | ' . $funnel . ')</span>';
            }
            return '<span class="apcai-badge" style="background:#f1f5f9;color:#94a3b8;">Belum Ada</span>';
        }
        return $content;
    }

    /**
     * Render Form Generator di Halaman Edit Tag Native WordPress
     */
    public static function render_tag_edit_fields($term, $taxonomy) {
        $content = get_term_meta($term->term_id, '_apcai_tag_copywriting', true);
        $lang    = get_term_meta($term->term_id, '_apcai_tag_lang', true) ?: 'id';
        $length  = get_term_meta($term->term_id, '_apcai_tag_length', true) ?: 'medium';
        $funnel  = get_term_meta($term->term_id, '_apcai_tag_funnel', true) ?: 'mofu';
        ?>
        <tr class="form-field apcai-tag-section">
            <th scope="row" valign="top">
                <label style="font-weight:700;color:#0f172a;display:flex;align-items:center;gap:6px;">
                    ✨ AI Tag Copywriting Hub (Silo MOFU)
                </label>
            </th>
            <td>
                <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:8px;padding:20px;max-width:850px;">
                    <p style="margin-top:0;font-size:13px;color:#475569;line-height:1.6;">
                        <strong>Strategi Funnel Tag (MOFU):</strong> Halaman tag adalah jembatan pertimbangan (MOFU) antara edukasi (TOFU) dan halaman jualan (BOFU).
                        Konten ini otomatis tampil <strong>di bawah daftar postingan</strong> arsip tag. Sistem mengunci aturan ketat: <strong>Hanya ada 1 H1 di halaman (Judul Tag Tema)</strong>, dan copywriting ini tersusun hierarkis dari <code>H2</code> $\rightarrow$ <code>H3</code> $\rightarrow$ <code>H4</code> lengkap dengan tabel komparasi dan CTA closing.
                    </p>

                    <div style="display:grid;grid-template-columns: 1fr 1fr 1fr;gap:16px;margin-bottom:16px;">
                        <div>
                            <label style="font-weight:600;font-size:12px;color:#334155;display:block;margin-bottom:4px;">Bahasa Konten:</label>
                            <select id="apcai_tag_lang" class="apcai-select" style="width:100%;">
                                <option value="id" <?php selected($lang, 'id'); ?>>🇮🇩 Bahasa Indonesia (ID)</option>
                                <option value="ms" <?php selected($lang, 'ms'); ?>>🇲🇾 Bahasa Melayu (MS)</option>
                                <option value="en" <?php selected($lang, 'en'); ?>>🇬🇧 English (EN)</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-weight:600;font-size:12px;color:#334155;display:block;margin-bottom:4px;">Target Panjang Konten:</label>
                            <select id="apcai_tag_length" class="apcai-select" style="width:100%;">
                                <option value="short" <?php selected($length, 'short'); ?>>Ringkas (300 - 500 kata)</option>
                                <option value="medium" <?php selected($length, 'medium'); ?>>Standar MOFU (600 - 900 kata)</option>
                                <option value="long" <?php selected($length, 'long'); ?>>Komprehensif (1000 - 1500 kata)</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-weight:600;font-size:12px;color:#334155;display:block;margin-bottom:4px;">Tahap Funnel:</label>
                            <select id="apcai_tag_funnel" class="apcai-select" style="width:100%;">
                                <option value="mofu" <?php selected($funnel, 'mofu'); ?>>MOFU (Pertimbangan / Solusi) [Rekomendasi]</option>
                                <option value="tofu" <?php selected($funnel, 'tofu'); ?>>TOFU (Edukasi Menyeluruh)</option>
                                <option value="bofu" <?php selected($funnel, 'bofu'); ?>>BOFU (Closing & Penawaran Langsung)</option>
                            </select>
                        </div>
                    </div>

                    <div style="margin-bottom:14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <button type="button" id="btn-generate-tag-copywriting" class="apcai-btn apcai-btn-primary" data-tag-id="<?php echo esc_attr($term->term_id); ?>" data-tag-name="<?php echo esc_attr($term->name); ?>">
                            🚀 Generate Copywriting Tag dengan AI
                        </button>
                        <span id="tag-gen-status" style="font-size:13px;color:#64748b;"></span>
                    </div>

                    <div class="apcai-form-group" style="margin-bottom:0;">
                        <label style="font-weight:600;font-size:13px;color:#1e293b;display:block;margin-bottom:6px;">
                            Isi Copywriting Tag (HTML / Terformat):
                        </label>
                        <textarea name="_apcai_tag_copywriting" id="apcai_tag_copywriting" rows="12" class="apcai-textarea" style="width:100%;font-family:monospace;font-size:13px;"><?php echo esc_textarea($content); ?></textarea>
                        <div class="apcai-help" style="margin-top:6px;">
                            Bisa diedit manual kapan saja. Tag heading dijamin mulai dari <code>&lt;h2&gt;</code> ke bawah untuk memastikan standar <strong>1 H1 per Halaman</strong>.
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }

    /**
     * Simpan Meta Tag saat tombol Update Tag di klik
     */
    public static function save_tag_edit_fields($term_id) {
        if (isset($_POST['_apcai_tag_copywriting'])) {
            $content = self::enforce_single_h1(wp_unslash($_POST['_apcai_tag_copywriting']));
            update_term_meta($term_id, '_apcai_tag_copywriting', $content);
        }
        if (isset($_POST['apcai_tag_lang'])) {
            update_term_meta($term_id, '_apcai_tag_lang', sanitize_text_field($_POST['apcai_tag_lang']));
        }
        if (isset($_POST['apcai_tag_length'])) {
            update_term_meta($term_id, '_apcai_tag_length', sanitize_text_field($_POST['apcai_tag_length']));
        }
        if (isset($_POST['apcai_tag_funnel'])) {
            update_term_meta($term_id, '_apcai_tag_funnel', sanitize_text_field($_POST['apcai_tag_funnel']));
        }
    }

    /**
     * AI Generator Khusus Copywriting Tag
     */
    public static function generate_tag_copywriting(string $tag_name, string $lang = 'id', string $length = 'medium', string $funnel = 'mofu'): string|WP_Error {
        $settings  = get_option('apcai_settings', []);
        $site_name = !empty($settings['site_name']) ? $settings['site_name'] : get_bloginfo('name');
        $niche     = !empty($settings['niche']) ? $settings['niche'] : 'Umum';
        $brand_voice = !empty($settings['brand_voice']) ? $settings['brand_voice'] : 'Informatif, profesional, terpercaya';
        $site_url  = home_url('/');

        // Rentang kata
        $word_range = match ($length) {
            'short'  => '300 hingga 500 kata',
            'long'   => '1000 hingga 1500 kata',
            default  => '600 hingga 900 kata',
        };

        // Bahasa output
        $lang_desc = match ($lang) {
            'ms'    => 'Bahasa Melayu standard baku (Malaysia/Nusantara)',
            'en'    => 'English (fluent, natural, authoritative)',
            default => 'Bahasa Indonesia yang mengalir, lugas, dan profesional',
        };

        // Arahan Funneling
        $funnel_desc = match ($funnel) {
            'tofu' => 'TOFU (Top of Funnel): Berikan edukasi menyeluruh seputar tag ini, apa pentingnya, dan bagaimana mengatasi kendala umum audiens.',
            'bofu' => 'BOFU (Bottom of Funnel): Fokus langsung ke solusi nyata dari brand kami, perbandingan spesifikasi/harga, dan Call To Action kuat untuk transaksi/konsultasi.',
            default => 'MOFU (Middle of Funnel - Rekomendasi): Audiens sedang membandingkan opsi atau mencari solusi terbaik seputar tag ini. Berikan perbandingan objektif, tabel pertimbangan, kelebihan/kekurangan, dan jembatan ke solusi resmi ' . $site_name . '.',
        };

        $system_prompt = "Anda adalah Senior Content Strategist & Copywriter profesional untuk {$site_name}.
Tugas Anda menyusun COPYWRITING KHUSUS HALAMAN ARSIP TAG (Tag Archive Page) untuk topik: \"{$tag_name}\".
Bahasa Output: {$lang_desc}.
Target Panjang: {$word_range}.

ATURAN STRUKTUR & SEO HEADING PALING KETAT:
1. ATURAN 1 H1 (ABSOLUTE RULE):
   DILARANG KERAS menggunakan tag <h1> atau # Heading 1! Halaman arsip tag WordPress SUDAH memiliki tag <h1> bawaan dari tema untuk nama tag.
   Semua heading wajib dimulai dari:
   - ## (H2) untuk judul bagian utama.
   - ### (H3) untuk sub-bagian penjelasan atau poin pendukung.
   - #### (H4) untuk FAQ (Pertanyaan Umum) atau detail teknis.
2. FUNNEL FOCUS:
   {$funnel_desc}
3. TABEL DATA / KOMPARASI:
   Wajib sertakan minimal 1 tabel data Markdown yang rapi (| Kolom 1 | Kolom 2 |) yang membandingkan opsi, estimasi biaya, atau ringkasan solusi.
4. ZERO ROBOTIC CLICHES:
   - Dilarang membuka dengan kalimat klise: 'Di era digital saat ini...', 'Dalam artikel ini...', 'Tidak dapat dipungkiri bahwa...'. Mulai langsung dengan konteks nyata audiens.
   - Dilarang membuat sub-heading klise 'Kesimpulan' atau 'Penutup'. Gunakan sub-heading aksi nyata (Langkah Praktis / Nasihat Ahli).
   - Dilarang menggunakan garis pemisah mendatar (---, ***, ___).
5. INTERNAL LINK & CALL TO ACTION:
   Sertakan link kontekstual ke Brand Resmi [{$site_name}]({$site_url}) dengan ajakan aksi (Call To Action) yang elegan di akhir tulisan.

Format Output WAJIB Markdown murni tanpa bungkus code block ```.";

        $user_prompt = "TAG / TOPIK: \"{$tag_name}\"\nNICHE: {$niche}\nBRAND: {$site_name}\nBRAND VOICE: {$brand_voice}\n\nTulis konten copywriting tag lengkap sekarang:";

        $model = $settings['model_writer'] ?? 'openai/gpt-4o';
        $raw = APCAI_AI_Client::chat($system_prompt, $user_prompt, $model, 0.6);

        if (is_wp_error($raw)) {
            return $raw;
        }

        // Bersihkan Markdown block wrapper jika ada
        $md = preg_replace('/^```(?:markdown|html)?\s*/i', '', trim($raw));
        $md = preg_replace('/\s*```$/', '', $md);

        // Konversi Markdown ke HTML
        $html = APCAI_Generator::markdown_to_html($md);

        // Jamin 100% tidak ada tag <h1> di output
        $html = self::enforce_single_h1($html);

        return $html;
    }

    /**
     * Pastikan tidak ada tag <h1> ganda di halaman arsip
     */
    public static function enforce_single_h1(string $html): string {
        return preg_replace('/<(\/?)h1\b([^>]*)>/i', '<$1h2$2>', $html);
    }

    /**
     * Frontend Injection: Tampilkan konten tag DI BAWAH DAFTAR POST
     */
    public static function render_tag_content_below_posts($query = null) {
        if (is_admin() || !is_tag()) {
            return;
        }

        // Pastikan hanya berjalan pada main query WordPress loop
        if (is_object($query) && method_exists($query, 'is_main_query') && !$query->is_main_query()) {
            return;
        }

        static $rendered = false;
        if ($rendered) {
            return;
        }

        $term_id = get_queried_object_id();
        if (!$term_id) {
            return;
        }

        $content = get_term_meta($term_id, '_apcai_tag_copywriting', true);
        if (empty($content)) {
            return;
        }

        $rendered = true;
        $content = self::enforce_single_h1($content);

        echo "\n<!-- APCAI Tag Copywriting Hub (MOFU Below Posts) -->\n";
        echo '<section class="apcai-tag-archive-hub" style="margin-top:48px;margin-bottom:32px;padding:32px 28px;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.05);line-height:1.75;color:#1e293b;">';
        echo $content;
        echo "</section>\n";
    }

    /**
     * Shortcode [apcai_tag_content] untuk Page Builder / Tema Modern
     */
    public static function shortcode_tag_content($atts) {
        if (!is_tag()) {
            return '';
        }
        $term_id = get_queried_object_id();
        if (!$term_id) {
            return '';
        }
        $content = get_term_meta($term_id, '_apcai_tag_copywriting', true);
        if (empty($content)) {
            return '';
        }
        return '<div class="apcai-tag-archive-hub" style="margin-top:32px;padding:28px 24px;background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;line-height:1.75;">' . self::enforce_single_h1($content) . '</div>';
    }

    /**
     * AJAX: Generate Copywriting Tag
     */
    public static function ajax_generate_tag_copywriting() {
        check_ajax_referer('apcai_tag_nonce', 'nonce');
        if (!current_user_can('manage_categories')) {
            wp_send_json_error('Unauthorized');
        }

        $tag_name = sanitize_text_field(wp_unslash($_POST['tag_name'] ?? ''));
        $tag_id   = absint($_POST['tag_id'] ?? 0);
        $lang     = sanitize_text_field(wp_unslash($_POST['lang'] ?? 'id'));
        $length   = sanitize_text_field(wp_unslash($_POST['length'] ?? 'medium'));
        $funnel   = sanitize_text_field(wp_unslash($_POST['funnel'] ?? 'mofu'));

        if (empty($tag_name) && $tag_id > 0) {
            $term = get_term($tag_id, 'post_tag');
            if ($term && !is_wp_error($term)) {
                $tag_name = $term->name;
            }
        }

        if (empty($tag_name)) {
            wp_send_json_error('Nama tag tidak boleh kosong.');
        }

        $res = self::generate_tag_copywriting($tag_name, $lang, $length, $funnel);
        if (is_wp_error($res)) {
            wp_send_json_error($res->get_error_message());
        }

        // Auto-save jika tag_id valid
        if ($tag_id > 0) {
            update_term_meta($tag_id, '_apcai_tag_copywriting', $res);
            update_term_meta($tag_id, '_apcai_tag_lang', $lang);
            update_term_meta($tag_id, '_apcai_tag_length', $length);
            update_term_meta($tag_id, '_apcai_tag_funnel', $funnel);
        }

        wp_send_json_success([
            'content'    => $res,
            'word_count' => str_word_count(strip_tags($res)),
            'message'    => 'Copywriting tag berhasil di-generate dan disimpan!',
        ]);
    }

    /**
     * AJAX: Simpan Manual Copywriting Tag
     */
    public static function ajax_save_tag_copywriting() {
        check_ajax_referer('apcai_tag_nonce', 'nonce');
        if (!current_user_can('manage_categories')) {
            wp_send_json_error('Unauthorized');
        }

        $tag_id  = absint($_POST['tag_id'] ?? 0);
        $content = self::enforce_single_h1(wp_unslash($_POST['content'] ?? ''));
        $lang    = sanitize_text_field(wp_unslash($_POST['lang'] ?? 'id'));
        $length  = sanitize_text_field(wp_unslash($_POST['length'] ?? 'medium'));
        $funnel  = sanitize_text_field(wp_unslash($_POST['funnel'] ?? 'mofu'));

        if ($tag_id <= 0) {
            wp_send_json_error('ID tag tidak valid.');
        }

        update_term_meta($tag_id, '_apcai_tag_copywriting', $content);
        update_term_meta($tag_id, '_apcai_tag_lang', $lang);
        update_term_meta($tag_id, '_apcai_tag_length', $length);
        update_term_meta($tag_id, '_apcai_tag_funnel', $funnel);

        wp_send_json_success('Konten tag berhasil diperbarui.');
    }
}
