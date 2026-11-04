<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <h1>
            <span class="dashicons dashicons-edit" style="font-size:30px;color:var(--apcai-primary);"></span>
            <?php echo esc_html(APCAI_I18n::t('AI Content Studio (Silo & Funnel Aware)', 'AI Content Studio (Silo & Funnel Aware)')); ?>
        </h1>
        <a href="<?php echo esc_url(admin_url('admin.php?page=apcai-settings')); ?>" class="apcai-btn apcai-btn-secondary">⚙️ <?php echo esc_html(APCAI_I18n::t('Pengaturan Default', 'Default Settings')); ?></a>
    </div>

    <div style="display:grid;grid-template-columns: 1fr 1fr;gap:24px;">
        <!-- Left: Form Input -->
        <div class="apcai-card">
            <h2>1. Parameter Silo, Kategori, & Konten</h2>
            
            <div style="display:grid;grid-template-columns: 1fr 1fr 1fr;gap:16px;">
                <div class="apcai-form-group">
                    <label>Kategori Silo <span style="color:#ef4444;">*</span></label>
                    <select id="studio-category" class="apcai-select">
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo esc_attr($c->term_id); ?>">
                                <?php echo esc_html($c->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="apcai-help">Internal link disesuaikan otomatis dengan silo ini.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Tahapan Funnel</label>
                    <select id="studio-funnel" class="apcai-select">
                        <option value="tofu">TOFU - Awareness & Edukasi</option>
                        <option value="mofu">MOFU - Review & Perbandingan</option>
                        <option value="bofu">BOFU - Closing & Penawaran</option>
                    </select>
                </div>

                <div class="apcai-form-group">
                    <label>Bahasa Utama Artikel</label>
                    <select id="studio-primary-lang" class="apcai-select">
                        <option value="id" <?php selected($settings['default_lang'] ?? '', 'id'); ?>>🇮🇩 Indonesia (ID)</option>
                        <option value="ms" <?php selected($settings['default_lang'] ?? '', 'ms'); ?>>🇲🇾 Melayu (MS)</option>
                        <option value="en" <?php selected($settings['default_lang'] ?? '', 'en'); ?>>🇬🇧 English (EN)</option>
                    </select>
                </div>
            </div>

            <div class="apcai-form-group">
                <label>Topik Artikel atau Paragraf Awal <span style="color:#ef4444;">*</span></label>
                <textarea id="studio-topic" class="apcai-textarea" rows="3" placeholder="Contoh: 7 Tips Mempercepat Loading Toko Online WooCommerce..."></textarea>
            </div>

            <?php if (!empty($settings['knowledge_data'])): ?>
            <div style="background:#eff6ff;padding:12px;border-radius:6px;border:1px solid #bfdbfe;margin-bottom:16px;">
                <div style="font-size:12px;font-weight:700;color:#1e40af;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                    📌 Basis Data Terkini Aktif (Update: <?php echo esc_html($settings['knowledge_updated_date'] ?? date_i18n('d F Y')); ?>)
                </div>
                <div style="font-size:12px;color:#1e3a8a;max-height:80px;overflow-y:auto;white-space:pre-wrap;background:#fff;padding:8px;border-radius:4px;border:1px solid #dbeafe;"><?php echo esc_html($settings['knowledge_data']); ?></div>
                <div class="apcai-help" style="color:#2563eb;margin-top:4px;">Data ini akan otomatis diintegrasikan dan diprioritaskan AI ke dalam artikel. Edit di menu Pengaturan.</div>
            </div>
            <?php endif; ?>

            <div class="apcai-form-group">
                <label>Instruksi Riset Kata Kunci Entitas (Entity SEO)</label>
                <textarea id="studio-kw-prompt" class="apcai-textarea" rows="3"><?php echo esc_textarea($settings['keyword_prompt'] ?? ''); ?></textarea>
            </div>

            <div class="apcai-form-group">
                <label>Instruksi Pengembangan Konten & Gaya Bahasa</label>
                <textarea id="studio-content-prompt" class="apcai-textarea" rows="3"><?php echo esc_textarea($settings['content_prompt'] ?? ''); ?></textarea>
            </div>

            <!-- CTA Section -->
            <div style="background:var(--apcai-slate-50);padding:16px;border-radius:6px;border:1px solid var(--apcai-slate-200);margin-bottom:18px;">
                <label style="font-weight:700;color:var(--apcai-slate-900);margin-bottom:10px;display:block;">🎯 Target Call To Action (CTA)</label>
                
                <div style="display:grid;grid-template-columns: 1fr 1fr;gap:12px;">
                    <div class="apcai-form-group">
                        <label>Tipe CTA</label>
                        <select id="studio-cta-type" class="apcai-select">
                            <option value="whatsapp" <?php selected($settings['cta_type'] ?? '', 'whatsapp'); ?>>WhatsApp Direct</option>
                            <option value="link" <?php selected($settings['cta_type'] ?? '', 'link'); ?>>Custom Link / URL</option>
                            <option value="custom" <?php selected($settings['cta_type'] ?? '', 'custom'); ?>>Instruksi Khusus Prompt</option>
                            <option value="none" <?php selected($settings['cta_type'] ?? '', 'none'); ?>>Tanpa CTA Tambahan</option>
                        </select>
                    </div>

                    <div class="apcai-form-group">
                        <label>Teks Tombol CTA</label>
                        <input type="text" id="studio-cta-text" class="apcai-input" value="<?php echo esc_attr($settings['cta_text'] ?? 'Hubungi Kami via WhatsApp'); ?>">
                    </div>
                </div>

                <div class="apcai-form-group">
                    <label>Target CTA (Nomor WhatsApp atau URL)</label>
                    <input type="text" id="studio-cta-target" class="apcai-input" value="<?php echo esc_attr($settings['cta_target'] ?? ''); ?>" placeholder="08123456789 atau https://domain.com/promo">
                </div>

            <div class="apcai-form-group" style="margin-bottom:0;">
                    <label>Instruksi Khusus CTA</label>
                    <textarea id="studio-cta-instruction" class="apcai-textarea" rows="2"><?php echo esc_textarea($settings['cta_instruction'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Workflow 2 Tahap: Outline -> Full Content -->
            <div style="background:#f8fafc;padding:16px;border-radius:6px;border:1px solid #cbd5e1;margin-bottom:16px;">
                <h3 style="margin-top:0;font-size:14px;color:#0f172a;display:flex;align-items:center;gap:6px;">
                    🧠 Alur Penulisan 2 Tahap (Sequential Thinking)
                </h3>
                <p style="font-size:12px;color:#64748b;margin:4px 0 12px 0;line-height:1.5;">
                    <strong>Tahap 1:</strong> Buat blueprint outline terlebih dahulu untuk meninjau/mengedit angle dan sub-heading.<br>
                    <strong>Tahap 2:</strong> Kembangkan outline terpilih menjadi artikel lengkap dan terstruktur.
                </p>

                <div style="display:flex;gap:10px;margin-bottom:12px;">
                    <button id="btn-studio-plan-outline" class="apcai-btn apcai-btn-secondary" style="flex:1;justify-content:center;font-weight:600;">
                        1. 📝 Buat Outline Dulu
                    </button>
                    <button id="btn-studio-generate-id" class="apcai-btn apcai-btn-primary" style="flex:1;justify-content:center;font-weight:600;">
                        2. 🚀 Tulis Konten Penuh
                    </button>
                </div>

                <div id="studio-outline-wrapper" style="display:none;margin-top:12px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label style="font-weight:600;font-size:13px;color:#1e293b;">Blueprint Outline (Dapat Diedit):</label>
                        <span style="font-size:11px;color:#059669;font-weight:600;">✓ Outline Siap Dikembangkan</span>
                    </div>
                    <textarea id="studio-custom-outline" class="apcai-textarea" rows="8" placeholder="Outline akan muncul di sini setelah Anda mengklik 'Buat Outline Dulu', atau Anda dapat menuliskan outline sendiri secara langsung..."></textarea>
                    <div class="apcai-help" style="margin-top:6px;font-size:11px;">
                        Setiap sub-heading dan poin di atas akan dikembangkan secara detail dan mendalam pada Tahap 2.
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Preview & Bilingual Studio -->
        <div class="apcai-card">
            <h2>2. Hasil Penulisan & Preview Bilingual</h2>
            
            <div id="studio-loading" style="display:none;text-align:center;padding:60px 20px;">
                <span class="apcai-spinner" style="width:40px;height:40px;border-color:var(--apcai-primary);border-top-color:transparent;"></span>
                <p style="font-weight:600;color:var(--apcai-slate-600);margin-top:16px;">Sedang mengolah artikel, injeksi internal link Landing Page & optimasi SEO...</p>
            </div>

            <div id="studio-results-area" style="display:none;">
                <div class="apcai-tabs">
                    <div class="apcai-tab active" data-lang="id">🇮🇩 Versi Utama (ID)</div>
                    <div class="apcai-tab" data-lang="ms" style="display:none;">🇲🇾 Versi Melayu (MS)</div>
                    <div class="apcai-tab" data-lang="en" style="display:none;">🇬🇧 Versi English (EN)</div>
                </div>

                <div style="margin-bottom:15px;display:flex;gap:10px;flex-wrap:wrap;">
                    <button id="btn-studio-translate-ms" class="apcai-btn apcai-btn-secondary" style="font-size:12px;">
                        🇲🇾 Adaptasi ke Bahasa Melayu (MS)
                    </button>
                    <button id="btn-studio-translate-en" class="apcai-btn apcai-btn-secondary" style="font-size:12px;">
                        🌐 Terjemahkan & Adaptasi ke English (EN)
                    </button>
                </div>

                <div class="apcai-preview-box">
                    <div style="margin-bottom:12px;">
                        <span class="apcai-badge apcai-badge-amber">Judul (H1)</span>
                        <h3 id="view-title" style="margin:5px 0 0 0;font-size:17px;color:var(--apcai-slate-900);"></h3>
                    </div>

                    <div style="margin-bottom:10px;">
                        <span class="apcai-badge apcai-badge-blue">SEO Title Tag</span>
                        <div id="view-seo-title" style="font-weight:600;color:var(--apcai-slate-700);margin-top:3px;"></div>
                    </div>

                    <div style="margin-bottom:10px;">
                        <span class="apcai-badge apcai-badge-blue">Meta Description</span>
                        <div id="view-seo-desc" style="color:var(--apcai-slate-500);font-size:13px;margin-top:3px;"></div>
                    </div>

                    <div style="margin-bottom:10px;">
                        <span class="apcai-badge apcai-badge-purple">Entity Keywords</span>
                        <div id="view-keywords" style="margin-top:4px;"></div>
                    </div>

                    <div style="margin-bottom:10px;">
                        <span class="apcai-badge apcai-badge-green">Isi Artikel (Markdown & Internal Link)</span>
                        <pre id="view-content" style="white-space:pre-wrap;background:#fff;padding:12px;border:1px solid var(--apcai-slate-200);border-radius:4px;font-size:12px;max-height:300px;overflow-y:auto;"></pre>
                    </div>
                </div>

                <!-- Publish Actions -->
                <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap;">
                    <button id="btn-publish-both" class="apcai-btn apcai-btn-success" style="flex:1;">
                        🚀 Terbitkan Semua Bahasa Aktif
                    </button>
                    <button id="btn-publish-primary" class="apcai-btn apcai-btn-primary">
                        Terbitkan Bahasa Utama Saja
                    </button>
                    <button id="btn-save-draft" class="apcai-btn apcai-btn-secondary">
                        Simpan DRAFT
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
