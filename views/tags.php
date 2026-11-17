<?php
defined('ABSPATH') || exit;

$stats = APCAI_Tag_Manager::get_tags_stats();
$settings = get_option('apcai_settings', []);
$tag_schedule_enabled = !empty($settings['tag_schedule_enabled']);
?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <div>
            <h1>
                <span class="dashicons dashicons-tag" style="font-size:30px;color:var(--apcai-primary);"></span>
                <?php echo esc_html(APCAI_I18n::t('Tag Copywriting Hub (Auto-Grab & Bulk Scheduler)', 'Tag Copywriting Hub (Auto-Grab & Bulk Scheduler)')); ?>
            </h1>
            <p style="color:var(--apcai-slate-500);margin:4px 0 0 0;font-size:13px;">
                <?php echo esc_html(APCAI_I18n::t(
                    'Kelola dan buat copywriting otomatis untuk seluruh tag tanpa perlu buka satu per satu. Konten tampil di bawah daftar post arsip tag dengan struktur 1 H1 (H2-H4) dan funnel MOFU.',
                    'Manage and auto-generate copywriting for all tags at once without opening them one by one. Content renders below tag posts list with strict single-H1 (H2-H4) and MOFU funnel.'
                )); ?>
            </p>
        </div>
        <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=post_tag')); ?>" class="apcai-btn apcai-btn-secondary" target="_blank">
            🏷️ <?php echo esc_html(APCAI_I18n::t('Buka Tag Asli WP', 'Open WP Native Tags')); ?>
        </a>
    </div>

    <!-- 1. Stats Counter Cards -->
    <div style="display:grid;grid-template-columns: repeat(4, 1fr);gap:18px;margin-bottom:24px;">
        <div class="apcai-card" style="padding:18px 20px;display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;font-size:24px;">
                🏷️
            </div>
            <div>
                <div style="font-size:24px;font-weight:700;color:#1e293b;" id="stat-total-tags"><?php echo esc_html($stats['total']); ?></div>
                <div style="font-size:12px;color:#64748b;"><?php echo esc_html(APCAI_I18n::t('Total Tag di Website', 'Total Tags in Site')); ?></div>
            </div>
        </div>

        <div class="apcai-card" style="padding:18px 20px;display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;font-size:24px;">
                ✅
            </div>
            <div>
                <div style="font-size:24px;font-weight:700;color:#166534;" id="stat-filled-tags"><?php echo esc_html($stats['filled']); ?></div>
                <div style="font-size:12px;color:#64748b;"><?php echo esc_html(APCAI_I18n::t('Tag Sudah Ada Copywriting', 'Tags with Copywriting')); ?></div>
            </div>
        </div>

        <div class="apcai-card" style="padding:18px 20px;display:flex;align-items:center;gap:16px;">
            <div style="width:48px;height:48px;border-radius:10px;background:#fffbeb;display:flex;align-items:center;justify-content:center;font-size:24px;">
                ⚠️
            </div>
            <div>
                <div style="font-size:24px;font-weight:700;color:#b45309;" id="stat-empty-tags"><?php echo esc_html($stats['empty']); ?></div>
                <div style="font-size:12px;color:#64748b;"><?php echo esc_html(APCAI_I18n::t('Tag Kosong (Perlu Dibuat)', 'Empty Tags (Pending AI)')); ?></div>
            </div>
        </div>

        <div class="apcai-card" style="padding:18px 20px;display:flex;align-items:center;gap:16px;border-top:3px solid #ef4444;">
            <div style="width:48px;height:48px;border-radius:10px;background:#fef2f2;display:flex;align-items:center;justify-content:center;font-size:24px;">
                🛡️
            </div>
            <div>
                <div style="font-size:24px;font-weight:700;color:#ef4444;" id="stat-deleted-redirects"><?php echo esc_html(count(get_option('apcai_deleted_tag_redirects', []))); ?></div>
                <div style="font-size:12px;color:#64748b;"><?php echo esc_html(APCAI_I18n::t('Tag Terhapus (Auto 301 ke Home)', 'Deleted Tags (Auto 301 to Home)')); ?></div>
            </div>
        </div>
    </div>

    <!-- 2. AI Tag Health Auditor & Cleaner Card -->
    <div class="apcai-card" style="margin-bottom:24px;border-top:4px solid #ef4444;">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:12px;">
            <div>
                <h2 style="margin:0;color:#0f172a;display:flex;align-items:center;gap:8px;">
                    🧹 <?php echo esc_html(APCAI_I18n::t('AI Tag Health Auditor & Cleaner (Max 400 Tag Policy)', 'AI Tag Health Auditor & Cleaner (Max 400 Tag Policy)')); ?>
                </h2>
                <p style="color:var(--apcai-slate-500);font-size:13px;margin:4px 0 0 0;">
                    <?php echo esc_html(APCAI_I18n::t(
                        'Pindai seluruh tag di web Anda. AI mengevaluasi kesesuaian profil bisnis/niche Anda, mendeteksi tag 0 post (zombie), tag long-tail terlalu panjang (>3 kata), serta tag sampah/rusak. Tag yang dihapus otomatis dialihkan (301 Redirect) ke Homepage agar reputasi SEO tetap aman.',
                        'Scan all tags across your site. AI analyzes relevance with your website profile, catches zero-post zombie tags, long-tail sentence spam (>3 words), and broken tags. Deleted tags are permanently 301-redirected to Homepage.'
                    )); ?>
                </p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <button type="button" id="btn-start-tag-audit" class="apcai-btn apcai-btn-primary" style="padding:10px 20px;font-size:13px;background:#ef4444;">
                    🔍 <?php echo esc_html(APCAI_I18n::t('Mulai Analisa AI & Audit Tag', 'Run AI Tag Health Audit')); ?>
                </button>
            </div>
        </div>

        <!-- Audit Progress Status -->
        <div id="tag-audit-progress-box" style="display:none;margin-top:14px;background:#f8fafc;border:1px solid #e2e8f0;padding:14px 18px;border-radius:8px;">
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;color:#0f172a;margin-bottom:8px;">
                <span id="tag-audit-status-text">Memindai tag dengan AI...</span>
                <span id="tag-audit-percent-text" style="font-weight:700;color:#ef4444;">0%</span>
            </div>
            <div style="background:#e2e8f0;border-radius:20px;height:10px;overflow:hidden;">
                <div id="tag-audit-progress-bar" style="background:linear-gradient(90deg, #f87171, #ef4444);height:100%;width:0%;transition:width 0.3s ease;"></div>
            </div>
        </div>

        <!-- Candidates Container & Action Bar -->
        <div id="tag-candidates-container" style="display:none;margin-top:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;background:#fff1f2;border:1px solid #fecdd3;padding:12px 18px;border-radius:8px;margin-bottom:14px;">
                <div>
                    <strong style="color:#9f1239;font-size:14px;">
                        ⚠️ <span id="candidate-count-text">0</span> Tag Masuk Kandidat Hapus
                    </strong>
                    <div style="font-size:12px;color:#be123c;margin-top:2px;">
                        Tag di bawah dinilai tidak sehat untuk SEO (0 post, format kalimat panjang, atau melenceng dari profil website).
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:10px;">
                    <label style="font-size:12px;color:#475569;display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="checkbox" id="check-all-candidates" checked> Pilih Semua
                    </label>
                    <button type="button" id="btn-delete-selected-candidates" class="apcai-btn apcai-btn-danger" style="padding:8px 18px;font-size:13px;">
                        🗑️ <?php echo esc_html(APCAI_I18n::t('Hapus Tag Terpilih & Buat 301 ke Home', 'Delete Selected & 301 to Home')); ?>
                    </button>
                </div>
            </div>

            <!-- Table Candidates -->
            <div style="max-height:420px;overflow-y:auto;border:1px solid #e2e8f0;border-radius:8px;background:#fff;">
                <table class="apcai-table" style="width:100%;margin:0;">
                    <thead>
                        <tr style="position:sticky;top:0;background:#f8fafc;z-index:2;">
                            <th style="width:40px;text-align:center;"><input type="checkbox" id="master-candidate-check" checked></th>
                            <th>Nama Tag</th>
                            <th>Panjang Kata</th>
                            <th>Jumlah Artikel</th>
                            <th>Alasan Rekomendasi Hapus</th>
                            <th style="text-align:right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tag-candidates-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. Bulk Generator & Scheduler Settings Card -->
    <div class="apcai-card" style="margin-bottom:24px;">
        <h2><?php echo esc_html(APCAI_I18n::t('Auto-Grab & Bulk Tag Generator', 'Auto-Grab & Bulk Tag Generator')); ?></h2>
        <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:16px;">
            <?php echo esc_html(APCAI_I18n::t(
                'Sistem otomatis mendeteksi seluruh tag kosong dan menyusun copywriting menggunakan data perusahaan dan prompt brand yang sudah Anda simpan.',
                'The system automatically grabs all empty tags and generates copywriting using your saved company knowledge and brand profile.'
            )); ?>
        </p>

        <div style="display:grid;grid-template-columns: 1fr 1fr 1fr;gap:20px;margin-bottom:18px;">
            <div class="apcai-form-group">
                <label><?php echo esc_html(APCAI_I18n::t('Bahasa Copywriting Tag', 'Tag Language')); ?></label>
                <select id="bulk_tag_lang" class="apcai-select">
                    <option value="id" <?php selected($settings['tag_default_lang'] ?? 'id', 'id'); ?>>🇮🇩 Bahasa Indonesia (ID)</option>
                    <option value="ms" <?php selected($settings['tag_default_lang'] ?? 'id', 'ms'); ?>>🇲🇾 Bahasa Melayu (MS)</option>
                    <option value="en" <?php selected($settings['tag_default_lang'] ?? 'id', 'en'); ?>>🇬🇧 English (EN)</option>
                </select>
            </div>

            <div class="apcai-form-group">
                <label><?php echo esc_html(APCAI_I18n::t('Target Panjang Konten', 'Target Word Length')); ?></label>
                <select id="bulk_tag_length" class="apcai-select">
                    <option value="short" <?php selected($settings['tag_default_length'] ?? 'medium', 'short'); ?>>Ringkas (300 - 500 kata)</option>
                    <option value="medium" <?php selected($settings['tag_default_length'] ?? 'medium', 'medium'); ?>>Standar MOFU (600 - 900 kata)</option>
                    <option value="long" <?php selected($settings['tag_default_length'] ?? 'medium', 'long'); ?>>Komprehensif (1000 - 1500 kata)</option>
                </select>
            </div>

            <div class="apcai-form-group">
                <label><?php echo esc_html(APCAI_I18n::t('Tahap Funneling Tag', 'Funnel Stage')); ?></label>
                <select id="bulk_tag_funnel" class="apcai-select">
                    <option value="mofu" <?php selected($settings['tag_default_funnel'] ?? 'mofu', 'mofu'); ?>>MOFU (Pertimbangan / Solusi) [Rekomendasi]</option>
                    <option value="tofu" <?php selected($settings['tag_default_funnel'] ?? 'mofu', 'tofu'); ?>>TOFU (Edukasi Menyeluruh)</option>
                    <option value="bofu" <?php selected($settings['tag_default_funnel'] ?? 'mofu', 'bofu'); ?>>BOFU (Closing & Penawaran Langsung)</option>
                </select>
            </div>
        </div>

        <!-- Background Auto-Schedule Toggle Switch & Daily Quota -->
        <div style="background:#f8fafc;padding:16px 20px;border-radius:8px;border:1px solid #e2e8f0;margin-bottom:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <div>
                    <label for="toggle_tag_schedule" style="font-weight:700;color:#0f172a;cursor:pointer;font-size:14px;display:flex;align-items:center;gap:6px;">
                        ⏱️ <?php echo esc_html(APCAI_I18n::t('Jadwalkan Penulisan Tag Otomatis di Background (Cron)', 'Auto-Schedule Tag Generation in Background (Cron)')); ?>
                    </label>
                    <div class="apcai-help" style="margin:2px 0 0 0;">
                        <?php echo esc_html(APCAI_I18n::t(
                            'Jika ON, sistem otomatis membuatkan copywriting tag kosong secara bertahap saat jadwal posting cron berjalan.',
                            'When ON, the scheduler automatically generates copywriting for empty tags incrementally during routine cron runs.'
                        )); ?>
                    </div>
                </div>
                <label class="apcai-switch">
                    <input type="checkbox" id="toggle_tag_schedule" value="1" <?php checked($tag_schedule_enabled); ?>>
                    <span class="apcai-slider"></span>
                </label>
            </div>

            <!-- Kuota Tag Per Hari & Mode Eksekusi -->
            <div id="tag_schedule_quota_box" style="display:<?php echo $tag_schedule_enabled ? 'grid' : 'none'; ?>;grid-template-columns: 1fr 1fr;gap:16px;padding-top:12px;border-top:1px dashed #cbd5e1;">
                <div>
                    <label style="font-weight:600;font-size:12px;color:#334155;display:block;margin-bottom:4px;">
                        🎯 <?php echo esc_html(APCAI_I18n::t('Target Kuota Tag Per Hari (Daily Quota):', 'Daily Tag Generation Quota:')); ?>
                    </label>
                    <select id="tag_schedule_daily_count" class="apcai-select" style="width:100%;">
                        <option value="1" <?php selected($settings['tag_schedule_daily_count'] ?? 3, 1); ?>>1 tag per hari</option>
                        <option value="2" <?php selected($settings['tag_schedule_daily_count'] ?? 3, 2); ?>>2 tag per hari</option>
                        <option value="3" <?php selected($settings['tag_schedule_daily_count'] ?? 3, 3); ?>>3 tag per hari (Direkomendasikan)</option>
                        <option value="5" <?php selected($settings['tag_schedule_daily_count'] ?? 3, 5); ?>>5 tag per hari</option>
                        <option value="10" <?php selected($settings['tag_schedule_daily_count'] ?? 3, 10); ?>>10 tag per hari</option>
                    </select>
                    <div class="apcai-help" style="margin-top:4px;">Membatasi pembuatan tag agar organik dan tidak membebani limit token AI.</div>
                </div>
                <div>
                    <label style="font-weight:600;font-size:12px;color:#334155;display:block;margin-bottom:4px;">
                        🎲 <?php echo esc_html(APCAI_I18n::t('Ritme Eksekusi Background:', 'Execution Rhythm:')); ?>
                    </label>
                    <select id="tag_schedule_execution_mode" class="apcai-select" style="width:100%;">
                        <option value="with_post" <?php selected($settings['tag_schedule_execution_mode'] ?? 'with_post', 'with_post'); ?>>Tiap kali artikel baru terbit (Alami)</option>
                        <option value="hourly_jitter" <?php selected($settings['tag_schedule_execution_mode'] ?? 'with_post', 'hourly_jitter'); ?>>Terjadwal acak harian terpisah</option>
                    </select>
                    <div class="apcai-help" style="margin-top:4px;">Mengacak waktu pembaruan tag agar tampak alami seperti diedit manusia.</div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <button type="button" id="btn-start-batch-tags" class="apcai-btn apcai-btn-primary" style="padding:10px 22px;font-size:13px;">
                ⚡ <?php echo esc_html(APCAI_I18n::t('Mulai Generate Semua Tag Kosong (Batch Queue)', 'Start Generating All Empty Tags (Batch Queue)')); ?>
            </button>
            <button type="button" id="btn-pause-batch-tags" class="apcai-btn apcai-btn-secondary" style="display:none;padding:10px 18px;font-size:13px;">
                ⏸️ <?php echo esc_html(APCAI_I18n::t('Jeda / Berhenti', 'Pause / Stop')); ?>
            </button>
            <span id="batch-tag-status-text" style="font-size:13px;color:#64748b;"></span>
        </div>

        <!-- Batch Progress Container -->
        <div id="batch-progress-wrapper" style="display:none;margin-top:16px;">
            <div style="background:#e2e8f0;border-radius:20px;height:14px;overflow:hidden;position:relative;">
                <div id="batch-progress-bar" style="background:linear-gradient(90deg, #2563eb, #38bdf8);height:100%;width:0%;transition:width 0.3s ease;"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:12px;color:#64748b;margin-top:6px;">
                <span id="batch-current-tag-text">Menyiapkan antrean tag...</span>
                <span id="batch-percent-text">0%</span>
            </div>
        </div>
    </div>

    <!-- 4. Daftar Tag Table (With Real-Time Search & Pagination) -->
    <div class="apcai-card">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:14px;">
            <div>
                <h2 style="margin:0;"><?php echo esc_html(APCAI_I18n::t('Daftar Tag & Status Copywriting', 'Tags List & Copywriting Status')); ?></h2>
                <div style="font-size:12px;color:#64748b;margin-top:2px;">
                    Mendukung navigasi 1000+ tag dengan cepat & tanpa membuat server berat.
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <input type="text" id="tag-search-input" class="apcai-input" placeholder="Cari nama tag..." style="padding:6px 12px;font-size:12px;width:180px;">
                <div style="display:flex;gap:6px;">
                    <button type="button" class="apcai-btn apcai-btn-secondary btn-filter-tags active" data-filter="all" style="padding:4px 10px;font-size:12px;">Semua</button>
                    <button type="button" class="apcai-btn apcai-btn-secondary btn-filter-tags" data-filter="empty" style="padding:4px 10px;font-size:12px;">Kosong Saja</button>
                    <button type="button" class="apcai-btn apcai-btn-secondary btn-filter-tags" data-filter="filled" style="padding:4px 10px;font-size:12px;">Sudah Ada</button>
                </div>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="apcai-table" id="table-apcai-tags" style="width:100%;">
                <thead>
                    <tr>
                        <th>Tag (Title Case)</th>
                        <th>Jumlah Post</th>
                        <th>Status Copywriting</th>
                        <th>Frasa Kunci Yoast</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="tags-table-body">
                    <tr>
                        <td colspan="5" style="text-align:center;padding:30px 20px;color:#64748b;">
                            <span class="apcai-spinner"></span> Memuat data tag...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:16px;padding-top:12px;border-top:1px solid #e2e8f0;">
            <div style="font-size:12px;color:#64748b;" id="tag-pagination-info">
                Menampilkan 0 tag
            </div>
            <div style="display:flex;align-items:center;gap:6px;" id="tag-pagination-buttons">
                <button type="button" id="btn-prev-tag-page" class="apcai-btn apcai-btn-secondary" style="padding:4px 10px;font-size:12px;" disabled>&larr; Sebelumnya</button>
                <span id="tag-current-page-text" style="font-size:12px;font-weight:600;padding:0 6px;">Hal 1 / 1</span>
                <button type="button" id="btn-next-tag-page" class="apcai-btn apcai-btn-secondary" style="padding:4px 10px;font-size:12px;" disabled>Berikutnya &rarr;</button>
            </div>
        </div>
    </div>
</div>
