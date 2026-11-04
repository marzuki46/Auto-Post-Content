<?php
defined('ABSPATH') || exit;

$stats = APCAI_Tag_Manager::get_tags_stats();
$settings = get_option('apcai_settings', []);
$tag_schedule_enabled = !empty($settings['tag_schedule_enabled']);
$all_tags = get_terms([
    'taxonomy'   => 'post_tag',
    'hide_empty' => false,
    'number'     => 100,
]);
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
    <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap:18px;margin-bottom:24px;">
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
    </div>

    <!-- 2. Bulk Generator & Scheduler Settings Card -->
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

        <!-- Background Auto-Schedule Toggle Switch -->
        <div style="background:#f8fafc;padding:16px 20px;border-radius:8px;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
            <div>
                <label for="toggle_tag_schedule" style="font-weight:700;color:#0f172a;cursor:pointer;font-size:14px;display:flex;align-items:center;gap:6px;">
                    ⏱️ <?php echo esc_html(APCAI_I18n::t('Jadwalkan Penulisan Tag Otomatis di Background (Cron)', 'Auto-Schedule Tag Generation in Background (Cron)')); ?>
                </label>
                <div class="apcai-help" style="margin:2px 0 0 0;">
                    <?php echo esc_html(APCAI_I18n::t(
                        'Jika ON, sistem otomatis mengisi 1-2 tag kosong setiap kali jadwal auto-post berjalan tanpa perlu membuka halaman ini.',
                        'When ON, the scheduler automatically generates copywriting for empty tags in background during routine cron runs.'
                    )); ?>
                </div>
            </div>
            <label class="apcai-switch">
                <input type="checkbox" id="toggle_tag_schedule" value="1" <?php checked($tag_schedule_enabled); ?>>
                <span class="apcai-slider"></span>
            </label>
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

    <!-- 3. Daftar Tag Table -->
    <div class="apcai-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h2 style="margin:0;"><?php echo esc_html(APCAI_I18n::t('Daftar Tag & Status Copywriting', 'Tags List & Copywriting Status')); ?></h2>
            <div style="display:flex;gap:8px;">
                <button type="button" class="apcai-btn apcai-btn-secondary btn-filter-tags active" data-filter="all" style="padding:4px 10px;font-size:12px;">Semua (<?php echo esc_html($stats['total']); ?>)</button>
                <button type="button" class="apcai-btn apcai-btn-secondary btn-filter-tags" data-filter="empty" style="padding:4px 10px;font-size:12px;">Kosong Saja (<?php echo esc_html($stats['empty']); ?>)</button>
                <button type="button" class="apcai-btn apcai-btn-secondary btn-filter-tags" data-filter="filled" style="padding:4px 10px;font-size:12px;">Sudah Ada (<?php echo esc_html($stats['filled']); ?>)</button>
            </div>
        </div>

        <table class="apcai-table" id="table-apcai-tags">
            <thead>
                <tr>
                    <th>Tag (Title Case)</th>
                    <th>Jumlah Post</th>
                    <th>Status Copywriting</th>
                    <th>Frasa Kunci Yoast</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_tags) || is_wp_error($all_tags)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:40px 20px;color:#94a3b8;">
                            <?php echo esc_html(APCAI_I18n::t('Belum ada tag yang terdaftar di WordPress Anda.', 'No tags found in your WordPress site.')); ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($all_tags as $t):
                        $content = get_term_meta($t->term_id, '_apcai_tag_copywriting', true);
                        $has_content = !empty(trim($content));
                        $words = $has_content ? str_word_count(strip_tags($content)) : 0;
                        $t_lang = strtoupper(get_term_meta($t->term_id, '_apcai_tag_lang', true) ?: 'ID');
                        $t_funnel = strtoupper(get_term_meta($t->term_id, '_apcai_tag_funnel', true) ?: 'MOFU');
                        $yoast_kw = get_term_meta($t->term_id, '_yoast_wpseo_focuskw', true) ?: $t->name;
                        $tag_link = get_term_link($t);
                    ?>
                        <tr class="tag-row" data-status="<?php echo $has_content ? 'filled' : 'empty'; ?>" id="tag-row-<?php echo esc_attr($t->term_id); ?>">
                            <td>
                                <strong style="color:#0f172a;font-size:14px;"><?php echo esc_html($t->name); ?></strong>
                                <div style="font-size:11px;color:#64748b;">slug: <code><?php echo esc_html($t->slug); ?></code></div>
                            </td>
                            <td>
                                <span class="apcai-badge apcai-badge-blue"><?php echo esc_html($t->count); ?> Artikel</span>
                            </td>
                            <td class="tag-status-cell">
                                <?php if ($has_content): ?>
                                    <span class="apcai-badge apcai-badge-green">
                                        ✅ Aktif (<?php echo esc_html($words); ?> kata • <?php echo esc_html($t_lang); ?> • <?php echo esc_html($t_funnel); ?>)
                                    </span>
                                <?php else: ?>
                                    <span class="apcai-badge apcai-badge-amber">
                                        ⚠️ Belum Ada Konten
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px;"><?php echo esc_html($yoast_kw); ?></code>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:6px;">
                                    <button type="button" class="apcai-btn apcai-btn-secondary btn-quick-gen-tag" data-id="<?php echo esc_attr($t->term_id); ?>" data-name="<?php echo esc_attr($t->name); ?>" style="padding:4px 10px;font-size:11px;">
                                        ⚡ <?php echo $has_content ? 'Tulis Ulang' : 'Tulis AI'; ?>
                                    </button>
                                    <a href="<?php echo esc_url(get_edit_term_link($t->term_id, 'post_tag')); ?>" target="_blank" class="apcai-btn apcai-btn-secondary" style="padding:4px 8px;font-size:11px;" title="Edit di WordPress">
                                        ✏️
                                    </a>
                                    <?php if (!is_wp_error($tag_link)): ?>
                                        <a href="<?php echo esc_url($tag_link); ?>" target="_blank" class="apcai-btn apcai-btn-secondary" style="padding:4px 8px;font-size:11px;" title="Lihat di Web">
                                            👁️
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
