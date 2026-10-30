<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <h1><span class="dashicons dashicons-admin-settings" style="font-size:28px;color:#2563eb;"></span> Pengaturan AI, Bahasa, & Prompt SEO</h1>
        <button id="btn-test-api" type="button" class="apcai-btn apcai-btn-secondary">
            🔌 Test Koneksi 9Router
        </button>
    </div>

    <form id="apcai-settings-form">
        <!-- 1. AI Connection -->
        <div class="apcai-card">
            <h2>1. Konfigurasi Endpoint 9Router & Model AI</h2>
            <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;">
                <div class="apcai-form-group">
                    <label>URL Endpoint 9Router <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="ninerouter_url" class="apcai-input" value="<?php echo esc_attr($settings['ninerouter_url'] ?? 'https://api.9router.com'); ?>" required>
                    <div class="apcai-help">Endpoint OpenAI-compatible gateway dari 9Router.</div>
                </div>

                <div class="apcai-form-group">
                    <label>API Key 9Router</label>
                    <input type="password" name="ninerouter_key" class="apcai-input" value="<?php echo esc_attr($settings['ninerouter_key'] ?? ''); ?>" placeholder="nr-xxxx...">
                    <div class="apcai-help">Dapat dikosongkan jika 9Router berjalan lokal tanpa API Key.</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;">
                <div class="apcai-form-group">
                    <label>Model Penulis Artikel (Writer Model)</label>
                    <input type="text" name="model_writer" class="apcai-input" value="<?php echo esc_attr($settings['model_writer'] ?? 'openai/gpt-4o'); ?>">
                    <div class="apcai-help">Contoh: openai/gpt-4o, claude-3-5-sonnet, google/gemini-2.5-pro, dll.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Model Perencana & Riset (Planner Model)</label>
                    <input type="text" name="model_planner" class="apcai-input" value="<?php echo esc_attr($settings['model_planner'] ?? 'openai/gpt-4o-mini'); ?>">
                    <div class="apcai-help">Model cepat & ekonomis untuk riset topik konten.</div>
                </div>
            </div>
        </div>

        <!-- 2. Pengaturan Bahasa & Multi-Language -->
        <div class="apcai-card">
            <h2>2. Bahasa & Mode Multi-Language</h2>
            <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;">
                <div class="apcai-form-group">
                    <label>Mode Bahasa</label>
                    <select name="lang_mode" class="apcai-select">
                        <option value="dual" <?php selected($settings['lang_mode'] ?? '', 'dual'); ?>>2 Bahasa (Bilingual: ID & EN Otomatis Dihubungkan)</option>
                        <option value="single" <?php selected($settings['lang_mode'] ?? '', 'single'); ?>>1 Bahasa Saja</option>
                    </select>
                    <div class="apcai-help">Jika 2 bahasa diaktifkan, AI akan menulis artikel utama lalu mengadaptasinya secara organik ke bahasa kedua.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Bahasa Utama / Default (Primary Language)</label>
                    <select name="default_lang" class="apcai-select">
                        <option value="id" <?php selected($settings['default_lang'] ?? '', 'id'); ?>>🇮🇩 Bahasa Indonesia (ID) - Default</option>
                        <option value="en" <?php selected($settings['default_lang'] ?? '', 'en'); ?>>🇬🇧 English (EN) - Default</option>
                    </select>
                    <div class="apcai-help">Pilih bahasa pertama yang dieksekusi saat scheduler berjalan.</div>
                </div>
            </div>
        </div>

        <!-- 3. Niche & Konteks Brand -->
        <div class="apcai-card">
            <h2>3. Konteks Niche & Brand Identity</h2>
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

            <div style="background:#f0fdf4;padding:16px;border-radius:6px;border:1px solid #bbf7d0;margin-top:15px;">
                <label style="display:flex;align-items:center;gap:10px;font-weight:600;color:#166534;cursor:pointer;">
                    <input type="checkbox" name="auto_thumbnail" id="auto_thumbnail" value="1" <?php checked($settings['auto_thumbnail'] ?? '1', '1'); ?>>
                    🖼️ Aktifkan Auto Thumbnail Kompresi WebP & Otomatis Lengkapi SEO (Alt, Title, Desc)
                </label>
                <div class="apcai-help" style="margin-top:6px;color:#15803d;">
                    Saat artikel dipublish (manual atau schedule otomatis), plugin akan otomatis memasang foto thumbnail WebP ringan (&plusmn;40-80 KB) tanpa mengurangi kualitas, lengkap dengan Alt Image dan Caption SEO.
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
            <h2>4. Template Prompt & Target Call To Action (CTA)</h2>
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
            <h2>5. Arsitektur Silo & Internal Linking Otomatis (Anti-Bocor Funnel)</h2>
            <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:16px;">
                Mengatur distribusi link internal kontekstual antar artikel dan ke halaman utama secara terstruktur untuk melipatgandakan otoritas domain (Link Juice) dan menjaga alur psikologi pembaca dari edukasi hingga pembelian.
            </p>

            <div style="background:#eff6ff;padding:16px;border-radius:6px;border:1px solid #bfdbfe;margin-bottom:16px;">
                <label style="display:flex;align-items:center;gap:10px;font-weight:700;color:#1e40af;cursor:pointer;font-size:14px;">
                    <input type="checkbox" name="enable_internal_links" id="enable_internal_links" value="1" <?php checked($settings['enable_internal_links'] ?? '1', '1'); ?>>
                    🔗 Aktifkan Otomatisasi Arsitektur Silo & Internal Link Berjenjang
                </label>
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

        <!-- 6. Instant Indexing (IndexNow & Google WebSub) -->
        <div class="apcai-card">
            <h2>6. Instant Indexing Otomatis (IndexNow, Google WebSub, & NERD Indexing)</h2>
            <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:16px;">
                Secara otomatis mengirimkan sinyal ping instan ke bot mesin pencari setiap kali ada artikel yang baru terbit, tanpa perlu submit manual satu per satu ke Search Console.
            </p>

            <div style="background:#eff6ff;padding:16px;border-radius:6px;border:1px solid #bfdbfe;margin-bottom:16px;">
                <label style="display:flex;align-items:center;gap:10px;font-weight:700;color:#1e40af;cursor:pointer;font-size:14px;">
                    <input type="checkbox" name="enable_instant_indexing" id="enable_instant_indexing" value="1" <?php checked($settings['enable_instant_indexing'] ?? '1', '1'); ?>>
                    ⚡ Aktifkan Auto-Ping IndexNow (Bing, Yandex, Naver), Google WebSub Hub, & NERD Indexing
                </label>
                <div class="apcai-help" style="margin-top:6px;color:#1e3a8a;">
                    Setiap postingan baru langsung di-push ke:
                    <ul style="margin:6px 0 0 18px;list-style:disc;">
                        <li><strong>IndexNow Protocol</strong>: Langsung menginstruksikan Bing, Yandex, Seznam, dan Naver untuk meng-crawl URL baru dalam hitungan detik.</li>
                        <li><strong>Google WebSub (PubSubHubbub)</strong>: Memberitahu hub Google secara real-time untuk segera menarik feed XML/RSS situs Anda.</li>
                        <li><strong>NERD Indexing (Dojo - nerd.dojo.cx)</strong>: Otomatis submit URL postingan baru ke antrean indexing NERD for WordPress.</li>
                    </ul>
                </div>
            </div>

            <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;margin-bottom:16px;">
                <div class="apcai-form-group">
                    <label>IndexNow API Key (Otomatis)</label>
                    <input type="text" class="apcai-input" value="<?php echo esc_attr(APCAI_Indexer::get_indexnow_key()); ?>" readonly style="background:#f1f5f9;color:#64748b;">
                    <div class="apcai-help">Key ini otomatis diverifikasi oleh sistem melalui <code>/<?php echo esc_html(APCAI_Indexer::get_indexnow_key()); ?>.txt</code>.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Google WebSub Hub URL</label>
                    <input type="text" class="apcai-input" value="https://pubsubhubbub.appspot.com/" readonly style="background:#f1f5f9;color:#64748b;">
                    <div class="apcai-help">Hub resmi Google yang memantau feed <code><?php echo esc_html(get_feed_link('rss2') ?: home_url('/feed/')); ?></code>.</div>
                </div>
            </div>

            <!-- NERD for WordPress / Dojo Integration -->
            <div style="background:#fdf4ff;padding:16px;border-radius:6px;border:1px solid #f0abfc;">
                <h3 style="margin-top:0;font-size:14px;color:#86198f;display:flex;align-items:center;gap:6px;">
                    🎯 Integrasi NERD for WordPress (Dojo Indexing)
                </h3>
                <div class="apcai-help" style="margin-bottom:12px;color:#701a75;">
                    Terhubung langsung dengan plugin <strong>NERD for WordPress</strong> (<code>nerd-for-wp</code>) atau endpoint resmi <code>https://nerd.dojo.cx/api/urls</code>. Jika Anda sudah mengaktifkan plugin NERD for WordPress, email dan API key di bawah akan tersinkronisasi otomatis.
                </div>

                <div style="display:grid;grid-template-columns: 1fr 1fr;gap:16px;">
                    <div class="apcai-form-group" style="margin:0;">
                        <label style="font-weight:600;color:#581c87;">Dojo Email (NERD Indexing)</label>
                        <input type="email" name="nerd_email" class="apcai-input" value="<?php echo esc_attr($settings['nerd_email'] ?? get_option('nerd_email', '')); ?>" placeholder="email@domain.com">
                    </div>

                    <div class="apcai-form-group" style="margin:0;">
                        <label style="font-weight:600;color:#581c87;">API Key (NERD Indexing)</label>
                        <input type="text" name="nerd_api_key" class="apcai-input" value="<?php echo esc_attr($settings['nerd_api_key'] ?? get_option('nerd_api_key', '')); ?>" placeholder="Dapatkan dari https://nerd.dojo.cx/settings">
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Integrasi Auto-Update GitHub -->
        <div class="apcai-card">
            <h2>7. Auto-Update via GitHub Release</h2>
            <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:16px;">
                Aktifkan update otomatis tanpa perlu upload ZIP manual. WordPress akan mendownload release terbaru langsung dari repository GitHub Anda.
            </p>
            <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;">
                <div class="apcai-form-group">
                    <label>GitHub Repository (owner/repo)</label>
                    <input type="text" name="github_repo" class="apcai-input" value="<?php echo esc_attr($settings['github_repo'] ?? 'marzuki46/Auto-Post-Content'); ?>" placeholder="marzuki46/Auto-Post-Content">
                    <div class="apcai-help">Alamat repo GitHub tempat merilis file update.</div>
                </div>

                <div class="apcai-form-group">
                    <label>GitHub Personal Access Token (Opsional)</label>
                    <input type="password" name="github_token" class="apcai-input" value="<?php echo esc_attr($settings['github_token'] ?? ''); ?>" placeholder="ghp_xxxx... (Hanya untuk Private Repo)">
                    <div class="apcai-help">Kosongkan jika repository bersifat Public.</div>
                </div>
            </div>

            <div style="margin-top:10px;display:flex;align-items:center;gap:12px;">
                <button type="button" id="btn-check-github-update" class="apcai-btn apcai-btn-secondary">
                    🔄 Cek Pembaruan GitHub Sekarang
                </button>
                <span id="update-check-status" style="font-size:13px;color:var(--apcai-slate-600);">Versi Aktif: <strong>v<?php echo esc_html(APCAI_VERSION); ?></strong></span>
            </div>
        </div>

        <div>
            <button type="submit" class="apcai-btn apcai-btn-primary" style="font-size:14px;padding:12px 28px;">
                💾 Simpan Semua Pengaturan
            </button>
        </div>
    </form>
</div>
