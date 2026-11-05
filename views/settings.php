<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <h1><span class="dashicons dashicons-admin-settings" style="font-size:28px;color:#2563eb;"></span> <?php echo esc_html(APCAI_I18n::t('Pengaturan AI, Bahasa, & Prompt SEO', 'AI, Language, & SEO Prompt Settings')); ?></h1>
    </div>

    <form id="apcai-settings-form">
        <!-- 1. AI Connection -->
        <div class="apcai-card">
            <h2><?php echo esc_html(APCAI_I18n::t('1. Konfigurasi Provider AI Engine & Model', '1. AI Engine Provider & Model Configuration')); ?></h2>
            <div class="apcai-form-group" style="margin-bottom:18px;">
                <label>Pilihan AI Engine Provider</label>
                <div style="display:flex;gap:20px;align-items:center;margin-top:6px;">
                    <label style="cursor:pointer;display:flex;align-items:center;gap:6px;font-weight:600;">
                        <input type="radio" name="ai_provider" value="gemini" <?php checked($settings['ai_provider'] ?? 'gemini', 'gemini'); ?>>
                        <span>✨ Google Gemini Direct (Google AI Studio)</span>
                    </label>
                    <label style="cursor:pointer;display:flex;align-items:center;gap:6px;font-weight:600;">
                        <input type="radio" name="ai_provider" value="ninerouter" <?php checked($settings['ai_provider'] ?? 'gemini', 'ninerouter'); ?>>
                        <span>🌐 9Router / OpenAI-Compatible Gateway</span>
                    </label>
                </div>
            </div>

            <!-- Fields Google Gemini -->
            <div id="provider-gemini-fields" style="<?php echo (($settings['ai_provider'] ?? 'gemini') === 'gemini') ? '' : 'display:none;'; ?>background:#f8fafc;padding:16px;border-radius:6px;border:1px solid #e2e8f0;margin-bottom:18px;">
                <div class="apcai-form-group" style="margin-bottom:0;">
                    <label>Google Gemini API Key <span style="color:#ef4444;">*</span></label>
                    <input type="password" name="gemini_api_key" class="apcai-input" value="<?php echo esc_attr($settings['gemini_api_key'] ?? ''); ?>" placeholder="AIzaSy...">
                    <div class="apcai-help">Dapatkan API Key gratis atau berbayar dari <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio</a>. Terhubung langsung via official OpenAI-compatible endpoint Google.</div>
                </div>
            </div>

            <!-- Fields 9Router -->
            <div id="provider-ninerouter-fields" style="<?php echo (($settings['ai_provider'] ?? 'gemini') === 'ninerouter') ? '' : 'display:none;'; ?>background:#f8fafc;padding:16px;border-radius:6px;border:1px solid #e2e8f0;margin-bottom:18px;">
                <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;">
                    <div class="apcai-form-group" style="margin:0;">
                        <label>URL Endpoint 9Router <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="ninerouter_url" class="apcai-input" value="<?php echo esc_attr($settings['ninerouter_url'] ?? 'https://api.9router.com'); ?>">
                        <div class="apcai-help">Endpoint gateway OpenAI-compatible.</div>
                    </div>

                    <div class="apcai-form-group" style="margin:0;">
                        <label>API Key 9Router</label>
                        <input type="password" name="ninerouter_key" class="apcai-input" value="<?php echo esc_attr($settings['ninerouter_key'] ?? ''); ?>" placeholder="nr-xxxx...">
                        <div class="apcai-help">Dapat dikosongkan jika 9Router berjalan lokal tanpa API Key.</div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;">
                <div class="apcai-form-group">
                    <label>Model Penulis Artikel (Writer Model)</label>
                    <input type="text" name="model_writer" id="setting_model_writer" class="apcai-input" value="<?php echo esc_attr($settings['model_writer'] ?? ((($settings['ai_provider'] ?? 'gemini') === 'gemini') ? 'gemini-2.0-flash' : 'openai/gpt-4o')); ?>">
                    <div class="apcai-help" id="help-model-writer">
                        <?php if (($settings['ai_provider'] ?? 'gemini') === 'gemini'): ?>
                            Pilihan Gemini: <code>gemini-2.5-pro</code>, <code>gemini-2.0-flash</code>, <code>gemini-1.5-pro</code>
                        <?php else: ?>
                            Pilihan: <code>openai/gpt-4o</code>, <code>claude-3-5-sonnet</code>, <code>google/gemini-2.5-pro</code>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="apcai-form-group">
                    <label>Model Perencana & Riset (Planner Model)</label>
                    <input type="text" name="model_planner" id="setting_model_planner" class="apcai-input" value="<?php echo esc_attr($settings['model_planner'] ?? ((($settings['ai_provider'] ?? 'gemini') === 'gemini') ? 'gemini-2.5-flash' : 'openai/gpt-4o-mini')); ?>">
                    <div class="apcai-help" id="help-model-planner">
                        <?php if (($settings['ai_provider'] ?? 'gemini') === 'gemini'): ?>
                            Pilihan Gemini: <code>gemini-2.5-flash</code>, <code>gemini-2.0-flash</code>, <code>gemini-1.5-flash</code>
                        <?php else: ?>
                            Pilihan: <code>openai/gpt-4o-mini</code>, <code>claude-3-5-haiku</code>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-top:12px;">
                <button type="button" id="btn-test-api" class="apcai-btn apcai-btn-secondary">
                    🔌 Uji Koneksi API Sekarang
                </button>
                <span id="api-test-result" style="margin-left:12px;font-size:13px;"></span>
            </div>
        </div>

        <!-- 2. Pengaturan Bahasa & Multi-Language -->
        <div class="apcai-card">
            <h2><?php echo esc_html(APCAI_I18n::t('2. Bahasa & Mode Multi-Language', '2. Language & Multi-Language Mode')); ?></h2>
            <div style="display:grid;grid-template-columns: 1fr 1fr 1fr;gap:20px;">
                <div class="apcai-form-group">
                    <label>Mode Bahasa</label>
                    <select name="lang_mode" id="setting_lang_mode" class="apcai-select">
                        <option value="single" <?php selected($settings['lang_mode'] ?? 'dual', 'single'); ?>>1 Bahasa (Hanya Bahasa Utama)</option>
                        <option value="dual" <?php selected($settings['lang_mode'] ?? 'dual', 'dual'); ?>>2 Bahasa (Bilingual: Utama + 1 Bahasa Adaptasi)</option>
                        <option value="tri" <?php selected($settings['lang_mode'] ?? 'dual', 'tri'); ?>>3 Bahasa Sekaligus (Trilingual: ID + MS + EN)</option>
                    </select>
                    <div class="apcai-help">Mode 3 Bahasa otomatis menerbitkan versi Indonesia, Melayu, dan Inggris secara simultan.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Bahasa Utama (Primary)</label>
                    <select name="default_lang" class="apcai-select">
                        <option value="id" <?php selected($settings['default_lang'] ?? 'id', 'id'); ?>>🇮🇩 Bahasa Indonesia (ID) - Default</option>
                        <option value="ms" <?php selected($settings['default_lang'] ?? 'id', 'ms'); ?>>🇲🇾 Bahasa Melayu (MS - Malaysia/Brunei/SG)</option>
                        <option value="en" <?php selected($settings['default_lang'] ?? 'id', 'en'); ?>>🇬🇧 English (EN)</option>
                    </select>
                    <div class="apcai-help">Bahasa pertama yang ditulis oleh AI sebagai acuan konten.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Bahasa Kedua Target (Jika Mode 2 Bahasa)</label>
                    <select name="secondary_lang" class="apcai-select">
                        <option value="en" <?php selected($settings['secondary_lang'] ?? 'en', 'en'); ?>>🇬🇧 English (EN) - Global Market</option>
                        <option value="ms" <?php selected($settings['secondary_lang'] ?? 'en', 'ms'); ?>>🇲🇾 Bahasa Melayu (MS) - Regional Market</option>
                        <option value="id" <?php selected($settings['secondary_lang'] ?? 'en', 'id'); ?>>🇮🇩 Bahasa Indonesia (ID)</option>
                    </select>
                    <div class="apcai-help">Target adaptasi bahasa kedua jika memilih Mode 2 Bahasa.</div>
                </div>
            </div>

            <!-- Target Range Jumlah Kata -->
            <div style="background:#f8fafc;padding:16px;border-radius:6px;border:1px solid #cbd5e1;margin-top:10px;">
                <label style="font-weight:600;font-size:13px;color:#1e293b;display:block;margin-bottom:6px;">
                    📏 Target Rentang Jumlah Kata per Artikel (Word Count Range)
                </label>
                <div class="apcai-help" style="margin-bottom:12px;color:#475569;">
                    Tentukan batas minimum dan maksimum kata yang wajib dihasilkan oleh AI agar artikel padat, mendalam, dan memenuhi standar algoritma Google E-E-A-T.
                </div>
                <div style="display:grid;grid-template-columns: 1fr 1fr 2fr;gap:16px;align-items:center;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Min Kata:</label>
                        <input type="number" name="word_count_min" id="word_count_min" class="apcai-input" value="<?php echo esc_attr($settings['word_count_min'] ?? 800); ?>" min="300" max="5000" step="50">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Maks Kata:</label>
                        <input type="number" name="word_count_max" id="word_count_max" class="apcai-input" value="<?php echo esc_attr($settings['word_count_max'] ?? 1300); ?>" min="400" max="8000" step="50">
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#64748b;">Preset Cepat:</label>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:2px;">
                            <button type="button" class="apcai-btn apcai-btn-secondary btn-preset-words" data-min="600" data-max="900" style="padding:6px 10px;font-size:11px;">Ringkas (600-900)</button>
                            <button type="button" class="apcai-btn apcai-btn-secondary btn-preset-words" data-min="900" data-max="1400" style="padding:6px 10px;font-size:11px;">Standar SEO (900-1400)</button>
                            <button type="button" class="apcai-btn apcai-btn-secondary btn-preset-words" data-min="1400" data-max="2000" style="padding:6px 10px;font-size:11px;">Mendalam (1400-2000)</button>
                            <button type="button" class="apcai-btn apcai-btn-secondary btn-preset-words" data-min="2000" data-max="3000" style="padding:6px 10px;font-size:11px;">Pilar (2000-3000)</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Niche & Konteks Brand -->
        <div class="apcai-card">
            <h2><?php echo esc_html(APCAI_I18n::t('3. Konteks Niche & Brand Identity', '3. Niche Context & Brand Identity')); ?></h2>
            <div style="display:grid;grid-template-columns: 1fr 1fr 1fr;gap:20px;">
                <div class="apcai-form-group">
                    <label>Nama Situs / Brand</label>
                    <input type="text" name="site_name" class="apcai-input" value="<?php echo esc_attr($settings['site_name'] ?? get_bloginfo('name')); ?>">
                </div>

                <div class="apcai-form-group">
                    <label>Niche / Topik Utama Web</label>
                    <input type="text" name="niche" class="apcai-input" value="<?php echo esc_attr($settings['niche'] ?? ''); ?>" placeholder="Contoh: Teknologi, Bisnis, Otomotif, dll">
                </div>

                <div class="apcai-form-group">
                    <label>Target Audiens</label>
                    <input type="text" name="audience" class="apcai-input" value="<?php echo esc_attr($settings['audience'] ?? ''); ?>" placeholder="Contoh: Profesional, Pebisnis, Pemula IT">
                </div>
            </div>

            <div class="apcai-form-group">
                <label>Brand Voice / Nada Bicara Penulisan</label>
                <input type="text" name="brand_voice" class="apcai-input" value="<?php echo esc_attr($settings['brand_voice'] ?? ''); ?>" placeholder="Contoh: Informatif, ramah, otoritatif, persuasif">
            </div>

            <!-- Form Bahan Pembuatan Konten / Basis Data Perusahaan -->
            <div style="background:#f8fafc;padding:16px;border-radius:6px;border:1px solid #cbd5e1;margin-top:15px;">
                <h3 style="margin-top:0;font-size:14px;color:#0f172a;display:flex;align-items:center;gap:6px;">
                    📊 Bahan Pembuatan Konten & Basis Data Terkini (Harga, Promo, Berita, & Info Perusahaan)
                </h3>
                <div class="apcai-help" style="margin-bottom:12px;color:#334155;">
                    Masukkan data faktual terbaru perusahaan (daftar harga unit, diskon bulan ini, promo DP/angsuran, jam operasional, rute layanan, dsb). <strong>AI akan memprioritaskan data ini saat meriset topik dan menyusun konten baru</strong>. Jika form ini kosong, AI akan meriset topik umum sesuai niche seperti biasa.
                </div>

                <div style="display:grid;grid-template-columns: 2fr 1fr;gap:16px;margin-bottom:12px;">
                    <div class="apcai-form-group" style="margin:0;">
                        <label style="font-weight:600;color:#1e293b;">Data / Update Informasi Terbaru</label>
                        <textarea name="knowledge_data" class="apcai-textarea" rows="6" placeholder="Contoh:&#10;- Daftar Harga Honda Vario 160 OTR Solo per Oktober 2026: Tipe CBS Rp 27.500.000, Tipe ABS Rp 30.200.000&#10;- Promo Honda Beat DP mulai Rp 800rb khusus KTP Karanganyar & Sukoharjo&#10;- Layanan Servis Kunjung & Booking Online Dealer Cahaya Sakti Motor Solo WA: 0812xxxx"><?php echo esc_textarea($settings['knowledge_data'] ?? ''); ?></textarea>
                    </div>

                    <div class="apcai-form-group" style="margin:0;">
                        <label style="font-weight:600;color:#1e293b;">Tanggal Update Data (Penting)</label>
                        <input type="text" name="knowledge_updated_date" class="apcai-input" value="<?php echo esc_attr($settings['knowledge_updated_date'] ?? date_i18n('d F Y')); ?>" placeholder="Contoh: 24 Oktober 2026 / Oktober 2026">
                        <div class="apcai-help" style="margin-top:6px;">Tanggal ini akan dicantumkan secara presisi oleh AI agar pembaca dan mesin pencari mengetahui data harga/info tersebut adalah yang paling mutakhir.</div>
                    </div>
                </div>
            </div>

            <!-- Smart Cognitive Reasoning & Anti-Halusinasi Engine -->
            <div style="background:#f0fdfa;padding:16px 20px;border-radius:6px;border:1px solid #99f6e4;margin-top:15px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <div>
                        <div style="font-weight:700;color:#0f766e;font-size:14px;">🧠 Smart Cognitive Reasoning & Anti-Halusinasi Engine</div>
                        <div style="font-size:12px;color:#134e4a;margin-top:2px;">Kecerdasan penalaran kontekstual untuk memastikan artikel tajam, aktual, dan bebas pola duplikasi.</div>
                    </div>
                    <label class="apcai-switch">
                        <input type="checkbox" name="enable_cognitive_engine" id="enable_cognitive_engine" value="1" <?php checked($settings['enable_cognitive_engine'] ?? '1', '1'); ?>>
                        <span class="apcai-slider"></span>
                    </label>
                </div>
                <div style="font-size:13px;color:#134e4a;line-height:1.6;margin-top:6px;">
                    <ul style="margin:4px 0 0 18px;list-style:disc;">
                        <li><strong>Sinkronisasi Waktu Aktual</strong>: Mengunci tahun aktif saat ini (Tahun <?php echo esc_html(date_i18n('Y')); ?>) agar AI tidak halusinasi merujuk tahun lampau.</li>
                        <li><strong>Memori Topik Jangka Panjang</strong>: Menganalisis riwayat artikel yang sudah terbit di database agar konten baru menyajikan sudut pandang segar tanpa duplikasi.</li>
                        <li><strong>Perencanaan Outline 2 Tahap (Sequential Reasoning)</strong>: AI merancang arsitektur outline, pain points audiens, dan target tabel sebelum mengeksekusi penulisan penuh.</li>
                    </ul>
                </div>
            </div>

            <div style="background:#f0fdf4;padding:16px;border-radius:6px;border:1px solid #bbf7d0;margin-top:15px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:18px;">🖼️</span>
                        <div>
                            <div style="font-weight:700;color:#166534;font-size:14px;">Auto Thumbnail Kompresi WebP & Lengkapi SEO (Alt, Title, Desc)</div>
                            <div style="font-size:12px;color:#15803d;margin-top:2px;">Otomatis memasang thumbnail WebP ultra-ringan (±40-80 KB) dengan garansi anti-kosong (multi-tier fallback).</div>
                        </div>
                    </div>
                    <label class="apcai-switch">
                        <input type="checkbox" name="auto_thumbnail" id="auto_thumbnail" value="1" <?php checked($settings['auto_thumbnail'] ?? '1', '1'); ?>>
                        <span class="apcai-slider"></span>
                    </label>
                </div>

                <div style="margin-top:14px;padding-top:12px;border-top:1px dashed #86efac;">
                    <label style="font-weight:600;color:#166534;display:block;margin-bottom:8px;">Pilihan Sumber Gambar Thumbnail:</label>
                    <div style="display:flex;gap:24px;flex-wrap:wrap;">
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;color:#14532d;font-weight:500;">
                            <input type="radio" name="thumbnail_source" value="bing" <?php checked($settings['thumbnail_source'] ?? 'bing', 'bing'); ?>>
                            1. 🌐 Otomatis dari Bing Image Search (WebP & Relevan Kata Kunci)
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;color:#14532d;font-weight:500;">
                            <input type="radio" name="thumbnail_source" value="pool" <?php checked($settings['thumbnail_source'] ?? 'bing', 'pool'); ?>>
                            2. 📁 Koleksi Upload Sendiri (Pool Gambar WebP Lokal)
                        </label>
                        <label style="display:flex;align-items:center;gap:6px;cursor:pointer;color:#14532d;font-weight:500;">
                            <input type="radio" name="thumbnail_source" value="media" <?php checked($settings['thumbnail_source'] ?? 'bing', 'media'); ?>>
                            3. 🖼️ Media yang Pernah Diupload Sebelumnya (Media Library)
                        </label>
                    </div>
                    <div class="apcai-help" style="margin-top:8px;color:#166534;line-height:1.6;">
                        - <strong>Bing Image</strong>: Mencari foto resolusi tinggi di web sesuai keyword, lalu mengompresnya ke WebP ringan (&plusmn;40-80 KB).<br>
                        - <strong>Koleksi Upload Sendiri</strong>: Mengambil dari galeri khusus di menu <em>Pool Foto Sendiri</em> secara rotasi adil.<br>
                        - <strong>Media Library Sebelumnya</strong>: Menggunakan kembali foto yang sudah pernah ada di WordPress Anda (dicocokkan dengan kata kunci topik / dirotasi acak). Jika belum ada media, otomatis fallback ke Bing.
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Default Prompt & Call To Action (Persis CMS SEO) -->
        <div class="apcai-card">
            <h2><?php echo esc_html(APCAI_I18n::t('4. Template Prompt & Target Call To Action (CTA)', '4. Prompt Templates & Call To Action (CTA)')); ?></h2>
            <div class="apcai-form-group">
                <label>Template Prompt Riset Kata Kunci Entitas (Entity SEO)</label>
                <textarea name="keyword_prompt" class="apcai-textarea" rows="4"><?php echo esc_textarea($settings['keyword_prompt'] ?? ''); ?></textarea>
            </div>

            <div class="apcai-form-group">
                <label>Template Prompt Pengembangan Konten Utama</label>
                <textarea name="content_prompt" class="apcai-textarea" rows="5"><?php echo esc_textarea($settings['content_prompt'] ?? ''); ?></textarea>
                <div class="apcai-help">Sistem telah dilengkapi filter bawaan untuk menghapus garis mendatar (---) dan melarang klise robotik ("Di era digital", "Kesimpulan", dll) agar tulisan 100% seperti buatan manusia.</div>
            </div>

            <div style="background:#f8fafc;padding:16px;border-radius:6px;border:1px solid #e2e8f0;">
                <h3 style="margin-top:0;font-size:14px;color:#1e293b;">Default Call To Action (CTA) Generator</h3>
                
                <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;">
                    <div class="apcai-form-group">
                        <label>Tipe CTA</label>
                        <select name="cta_type" class="apcai-select">
                            <option value="whatsapp" <?php selected($settings['cta_type'] ?? '', 'whatsapp'); ?>>WhatsApp Direct</option>
                            <option value="link" <?php selected($settings['cta_type'] ?? '', 'link'); ?>>Custom Link / URL</option>
                            <option value="custom" <?php selected($settings['cta_type'] ?? '', 'custom'); ?>>Custom Instruction Prompt</option>
                            <option value="none" <?php selected($settings['cta_type'] ?? '', 'none'); ?>>Tanpa CTA</option>
                        </select>
                    </div>

                    <div class="apcai-form-group">
                        <label>Target CTA (Nomor WhatsApp atau URL)</label>
                        <input type="text" name="cta_target" class="apcai-input" value="<?php echo esc_attr($settings['cta_target'] ?? ''); ?>" placeholder="08123456789 atau https://domain.com/order">
                    </div>
                </div>

                <div class="apcai-form-group">
                    <label>Teks Tombol CTA</label>
                    <input type="text" name="cta_text" class="apcai-input" value="<?php echo esc_attr($settings['cta_text'] ?? 'Hubungi Kami via WhatsApp'); ?>">
                </div>

                <div class="apcai-form-group">
                    <label>Instruksi Khusus CTA</label>
                    <textarea name="cta_instruction" class="apcai-textarea" rows="3"><?php echo esc_textarea($settings['cta_instruction'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- 5. Arsitektur Silo & Internal Linking Otomatis -->
        <div class="apcai-card">
            <h2><?php echo esc_html(APCAI_I18n::t('5. Arsitektur Silo & Internal Linking Otomatis (Anti-Bocor Funnel)', '5. Silo Architecture & Automated Internal Linking (Leak-Proof Funnel)')); ?></h2>
            <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:16px;">
                Mengatur distribusi link internal kontekstual antar artikel dan ke halaman utama secara terstruktur untuk melipatgandakan otoritas domain (Link Juice) dan menjaga alur psikologi pembaca dari edukasi hingga pembelian.
            </p>

            <div style="background:#eff6ff;padding:16px;border-radius:6px;border:1px solid #bfdbfe;margin-bottom:16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:18px;">🔗</span>
                        <div>
                            <div style="font-weight:700;color:#1e40af;font-size:14px;">Otomatisasi Arsitektur Silo & Internal Link Berjenjang</div>
                            <div style="font-size:12px;color:#1e3a8a;margin-top:2px;">Mendistribusikan link equity antar funnel secara ketat tanpa kebocoran link.</div>
                        </div>
                    </div>
                    <label class="apcai-switch">
                        <input type="checkbox" name="enable_internal_links" id="enable_internal_links" value="1" <?php checked($settings['enable_internal_links'] ?? '1', '1'); ?>>
                        <span class="apcai-slider"></span>
                    </label>
                </div>
                <div class="apcai-help" style="margin-top:8px;color:#1e3a8a;line-height:1.6;">
                    Sistem secara otomatis menerapkan 4 hukum ketat Silo SEO di setiap artikel yang diposting:
                    <ul style="margin:6px 0 0 18px;list-style:disc;">
                        <li><strong>Wajib Link Home Brand (Semua Funnel)</strong>: Setiap artikel (TOFU, MOFU, BOFU) wajib menyisipkan link ke Halaman Utama dengan anchor text nama Brand (<code><?php echo esc_html(!empty($settings['site_name']) ? $settings['site_name'] : get_bloginfo('name')); ?></code>) di paragraf pembuka.</li>
                        <li><strong>TOFU (Top of Funnel - Edukasi)</strong>: Di-link ke artikel sesama <strong>TOFU</strong> dan <strong>MOFU</strong>. <span style="color:#b91c1c;font-weight:600;">Dilarang keras link ke BOFU / Halaman Jualan</span> (karena audiens belum siap beli).</li>
                        <li><strong>MOFU (Middle of Funnel - Pertimbangan)</strong>: Di-link ke artikel <strong>MOFU</strong> dan <strong>BOFU</strong> (Target Landing Page). <span style="color:#b91c1c;font-weight:600;">Dilarang mundur link ke TOFU</span>.</li>
                        <li><strong>BOFU (Bottom of Funnel - Keputusan Beli)</strong>: <span style="color:#15803d;font-weight:600;">HANYA di-link ke sesama artikel BOFU atau Landing Page resmi</span>. <span style="color:#b91c1c;font-weight:600;">Dilarang link ke TOFU & MOFU</span> (agar fokus closing 100%).</li>
                    </ul>
                </div>
            </div>

            <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;">
                <div style="background:#f8fafc;padding:14px;border-radius:6px;border:1px solid #e2e8f0;">
                    <label style="font-weight:600;color:#1e293b;display:block;margin-bottom:6px;">Target Link Halaman Utama (Homepage)</label>
                    <input type="text" class="apcai-input" value="<?php echo esc_attr(home_url('/')); ?>" readonly style="background:#f1f5f9;color:#64748b;">
                    <div class="apcai-help">Anchor text otomatis menggunakan nama brand resmi: <strong><?php echo esc_html(!empty($settings['site_name']) ? $settings['site_name'] : get_bloginfo('name')); ?></strong>.</div>
                </div>

                <div style="background:#f8fafc;padding:14px;border-radius:6px;border:1px solid #e2e8f0;">
                    <label style="font-weight:600;color:#1e293b;display:block;margin-bottom:6px;">Status Target Landing Page (Money Pages)</label>
                    <div style="font-size:13px;color:#475569;margin-top:6px;line-height:1.5;">
                        Dikelola di menu <strong>Target Landing Page</strong> di bilah navigasi samping. Hanya artikel MOFU dan BOFU yang dialirkan ke Money Page.
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Fast Instant Indexing Bot Push -->
        <div class="apcai-card">
            <h2><?php echo esc_html(APCAI_I18n::t('6. Fast Instant Indexing Bot Push (Multi-Search Engine)', '6. Fast Instant Indexing Bot Push (Multi-Search Engine)')); ?></h2>
            <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:16px;">
                Mengirimkan sinyal prioritas ke jaringan bot mesin pencari global detik itu juga saat artikel terbit, memastikan URL baru langsung diprioritaskan untuk crawling dan perayapan cepat.
            </p>

            <div style="background:#eff6ff;padding:16px;border-radius:6px;border:1px solid #bfdbfe;margin-bottom:16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <span style="font-size:18px;">⚡</span>
                        <div>
                            <div style="font-weight:700;color:#1e40af;font-size:14px;">Aktifkan Fast Instant Indexing Bot Push</div>
                            <div style="font-size:12px;color:#1e3a8a;margin-top:2px;">Otomatis memicu bot perayap mesin pencari utama secara simultan begitu artikel berstatus publish.</div>
                        </div>
                    </div>
                    <label class="apcai-switch">
                        <input type="checkbox" name="enable_instant_indexing" id="enable_instant_indexing" value="1" <?php checked($settings['enable_instant_indexing'] ?? '1', '1'); ?>>
                        <span class="apcai-slider"></span>
                    </label>
                </div>
            </div>

            <div style="margin-bottom:16px;">
                <div class="apcai-form-group" style="margin-bottom:0;">
                    <label>Auto-Verification Security Key (Tervalidasi Otomatis)</label>
                    <input type="text" class="apcai-input" value="<?php echo esc_attr(APCAI_Indexer::get_indexnow_key()); ?>" readonly style="background:#f1f5f9;color:#64748b;">
                    <div class="apcai-help">Kunci autentikasi resmi sistem untuk verifikasi bot perayap mesin pencari mitra.</div>
                </div>
            </div>

            <!-- Dojo & Dedicated Indexing Integration (Opsional) -->
            <div style="background:#fdf4ff;padding:16px;border-radius:6px;border:1px solid #f0abfc;">
                <h3 style="margin-top:0;font-size:14px;color:#86198f;display:flex;align-items:center;gap:6px;">
                    🎯 Integrasi Dedicated Indexing Queue (Opsional)
                </h3>
                <div class="apcai-help" style="margin-bottom:12px;color:#701a75;">
                    Terhubung otomatis jika Anda menggunakan plugin indexing tambahan di WordPress Anda (misal <code>nerd-for-wp</code>). Email dan API Key akan tersinkronisasi otomatis.
                </div>

                <div style="display:grid;grid-template-columns: 1fr 1fr;gap:16px;">
                    <div class="apcai-form-group" style="margin:0;">
                        <label style="font-weight:600;color:#581c87;">Account Email</label>
                        <input type="email" name="nerd_email" class="apcai-input" value="<?php echo esc_attr($settings['nerd_email'] ?? get_option('nerd_email', '')); ?>" placeholder="email@domain.com">
                    </div>

                    <div class="apcai-form-group" style="margin:0;">
                        <label style="font-weight:600;color:#581c87;">API Access Key</label>
                        <input type="text" name="nerd_api_key" class="apcai-input" value="<?php echo esc_attr($settings['nerd_api_key'] ?? get_option('nerd_api_key', '')); ?>" placeholder="Masukkan API Key jika ada">
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Focus Keyphrase & SEO Backfill Utility (Bulk Injector) -->
        <div class="apcai-card">
            <h2><?php echo esc_html(APCAI_I18n::t('7. SEO Focus Keyphrase Auto-Injector (Yoast & Rank Math)', '7. SEO Focus Keyphrase Auto-Injector (Yoast & Rank Math)')); ?></h2>
            <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:16px;">
                <?php echo esc_html(APCAI_I18n::t(
                    'Pindai seluruh postingan dan tag lama yang target keyword-nya masih kosong. Fitur ini otomatis menyuntikkan Frasa Kunci Utama (Focus Keyphrase) ke Yoast SEO dan Rank Math, lalu memperbarui status post.',
                    'Scan existing published posts and tags that are missing SEO target keywords. Automatically injects Primary Focus Keyphrases into Yoast SEO & Rank Math and re-syncs post metadata.'
                )); ?>
            </p>

            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:16px 20px;margin-bottom:16px;">
                <div style="margin-bottom:14px;">
                    <div style="font-weight:700;color:#0f172a;font-size:14px;display:flex;align-items:center;gap:6px;">
                        🎯 <?php echo esc_html(APCAI_I18n::t('Pindai & Suntikkan Keyword Target Kosong', 'Scan & Auto-Fill Missing Focus Keyphrases')); ?>
                    </div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">
                        <?php echo esc_html(APCAI_I18n::t(
                            'Mencocokkan keyword dari tag artikel, bank topik AI, atau mengekstrak entitas utama dari judul.',
                            'Matches keywords from existing post tags, AI topic database, or extracts core entities from titles.'
                        )); ?>
                    </div>
                </div>

                <!-- 3 Opsi Strategi Tanggal (Timestamp Freshness Mode) -->
                <div style="background:#ffffff;border:1px solid #cbd5e1;border-radius:6px;padding:14px 16px;margin-bottom:14px;">
                    <label style="font-weight:600;font-size:13px;color:#1e293b;display:block;margin-bottom:8px;">
                        📅 <?php echo esc_html(APCAI_I18n::t('Strategi Tanggal Pembaruan (Modified Date Strategy):', 'Modified Date SEO Strategy:')); ?>
                    </label>
                    <div style="display:grid;grid-template-columns: 1fr;gap:8px;">
                        <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;color:#334155;cursor:pointer;">
                            <input type="radio" name="backfill_date_mode" value="smart_organic" checked style="margin-top:2px;">
                            <span>
                                <strong><?php echo esc_html(APCAI_I18n::t('Smart Organic Freshness (Direkomendasikan Google)', 'Smart Organic Freshness (Google Recommended)')); ?></strong><br>
                                <span style="font-size:11px;color:#64748b;">
                                    <?php echo esc_html(APCAI_I18n::t(
                                        'Artikel baru hari ini: jeda 15-45 menit setelah tanggal terbit. Artikel lama: jeda acak 2-12 jam setelah terbit asli (natural & anti-spam).',
                                        'Fresh posts get 15-45m jitter after published date. Older posts get random 2-12h incremental lag (natural & anti-spam penalty).'
                                    )); ?>
                                </span>
                            </span>
                        </label>
                        <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;color:#334155;cursor:pointer;">
                            <input type="radio" name="backfill_date_mode" value="keep_original" style="margin-top:2px;">
                            <span>
                                <strong><?php echo esc_html(APCAI_I18n::t('Kunci Tanggal Asli (Strict Original)', 'Keep Strict Original Date')); ?></strong><br>
                                <span style="font-size:11px;color:#64748b;">
                                    <?php echo esc_html(APCAI_I18n::t(
                                        'Menjaga tanggal post_modified persis sama dengan post_date asli tanpa merubah tanggal sama sekali.',
                                        'Keeps modified date identical to original published date without any touch.'
                                    )); ?>
                                </span>
                            </span>
                        </label>
                        <label style="display:flex;align-items:flex-start;gap:8px;font-size:13px;color:#334155;cursor:pointer;">
                            <input type="radio" name="backfill_date_mode" value="gradual_recent" style="margin-top:2px;">
                            <span>
                                <strong><?php echo esc_html(APCAI_I18n::t('Pembaruan Bertahap Terkini (Gradual Recent Updates)', 'Gradual Recent Updates')); ?></strong><br>
                                <span style="font-size:11px;color:#64748b;">
                                    <?php echo esc_html(APCAI_I18n::t(
                                        'Menetapkan tanggal modified secara bertahap acak dalam 1-3 hari terakhir agar terdeteksi segar di mesin pencari.',
                                        'Spreads modified timestamp randomly across last 1-3 days to signal fresh content updates.'
                                    )); ?>
                                </span>
                            </span>
                        </label>
                    </div>
                </div>

                <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;">
                    <button type="button" id="btn-scan-missing-keywords" class="apcai-btn apcai-btn-secondary">
                        🔍 <?php echo esc_html(APCAI_I18n::t('Pindai Konten Kosong', 'Scan Missing Content')); ?>
                    </button>
                    <button type="button" id="btn-start-fix-keywords" class="apcai-btn apcai-btn-primary" style="display:none;">
                        ⚡ <?php echo esc_html(APCAI_I18n::t('Mulai Isi Semua Keyword', 'Start Auto-Fill & Update')); ?>
                    </button>
                </div>

                <!-- Progress Bar & Status Log -->
                <div id="missing-kw-progress-wrapper" style="display:none;margin-top:16px;">
                    <div style="background:#e2e8f0;border-radius:20px;height:12px;overflow:hidden;position:relative;">
                        <div id="missing-kw-progress-bar" style="background:linear-gradient(90deg, #10b981, #059669);height:100%;width:0%;transition:width 0.3s ease;"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;color:#64748b;margin-top:6px;">
                        <span id="missing-kw-current-text">Menyiapkan antrean...</span>
                        <span id="missing-kw-percent-text">0%</span>
                    </div>
                </div>

                <div id="missing-kw-scan-summary" style="display:none;margin-top:12px;font-size:13px;padding:10px 14px;border-radius:6px;background:#ffffff;border:1px solid #cbd5e1;"></div>
            </div>
        </div>

        <!-- 8. Status Pembaruan Sistem (Cloud OTA Status) -->
        <div class="apcai-card">
            <h2><?php echo esc_html(APCAI_I18n::t('8. Status Sistem & Pembaruan Cloud Otomatis', '8. System Status & Pro Cloud Updates')); ?></h2>
            <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:16px;">
                <?php echo esc_html(APCAI_I18n::t(
                    'Sistem terhubung langsung ke cloud release channel resmi. Peningkatan model AI, algoritma indexing, dan patch keamanan diterapkan otomatis.',
                    'The system connects directly to the official cloud release channel. AI engine improvements, indexing algorithms, and security patches apply automatically.'
                )); ?>
            </p>

            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:15px;">
                <div style="display:flex;align-items:center;gap:12px;">
                    <span style="font-size:24px;">🛡️</span>
                    <div>
                        <div style="font-weight:700;color:#166534;font-size:14px;">
                            <?php echo esc_html(APCAI_I18n::t('Anda Menggunakan Versi Resmi Terbaru', 'You Are Using The Latest Official Version')); ?>
                        </div>
                        <div style="font-size:12px;color:#15803d;margin-top:2px;">
                            <?php echo esc_html(APCAI_I18n::t('Lisensi Resmi Pro Aktif • Terhubung ke Server Rilis Cloud', 'Official Pro License Active • Connected to Cloud Release Server')); ?>
                        </div>
                    </div>
                </div>
                <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;font-weight:700;padding:6px 14px;border-radius:20px;font-size:13px;">
                    v<?php echo esc_html(APCAI_VERSION); ?> Pro
                </div>
            </div>

            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <button type="button" id="btn-check-github-update" class="apcai-btn apcai-btn-secondary">
                    <?php echo esc_html(APCAI_I18n::t('🔄 Periksa Pembaruan Sistem Sekarang', '🔄 Check for Updates Now')); ?>
                </button>
                <span id="update-check-status" style="font-size:13px;color:var(--apcai-slate-600);">
                    <?php echo esc_html(APCAI_I18n::t('Status: Semua sistem beroperasi normal.', 'Status: All systems operating normally.')); ?>
                </span>
            </div>
        </div>

        <div>
            <button type="submit" class="apcai-btn apcai-btn-primary" style="font-size:14px;padding:12px 28px;">
                💾 <?php echo esc_html(APCAI_I18n::t('Simpan Semua Pengaturan', 'Save All Settings')); ?>
            </button>
        </div>
    </form>
</div>
