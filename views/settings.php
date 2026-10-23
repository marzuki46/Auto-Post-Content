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

        <!-- 5. Integrasi Auto-Update GitHub -->
        <div class="apcai-card">
            <h2>5. Auto-Update via GitHub Release</h2>
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
