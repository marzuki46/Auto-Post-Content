<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <h1><span class="dashicons dashicons-clock" style="font-size:28px;color:#2563eb;"></span> Auto Post Schedule & Penjadwalan</h1>
    </div>

    <form id="apcai-settings-form">
        <div class="apcai-card">
            <h2>Konfigurasi Penjadwalan Otomatis</h2>

            <div class="apcai-form-group" style="display:flex;align-items:center;gap:12px;background:#f8fafc;padding:15px;border-radius:6px;border:1px solid #e2e8f0;">
                <input type="checkbox" id="schedule_enabled" name="schedule_enabled" value="1" <?php checked(!empty($settings['schedule_enabled'])); ?> style="width:20px;height:20px;">
                <div>
                    <label for="schedule_enabled" style="font-size:15px;font-weight:700;color:#0f172a;margin:0;cursor:pointer;">
                        Aktifkan Auto Post Schedule
                    </label>
                    <div class="apcai-help" style="margin:2px 0 0 0;">Jika dicentang, WordPress cron akan otomatis memposting artikel secara mandiri.</div>
                </div>
            </div>

            <!-- Auto Topic Research & Autopilot Setting -->
            <div style="background:#eff6ff;padding:16px;border-radius:6px;border:1px solid #bfdbfe;margin-bottom:20px;">
                <label style="display:flex;align-items:center;gap:10px;font-weight:700;color:#1e40af;cursor:pointer;">
                    <input type="checkbox" name="auto_research_topics" value="1" <?php checked($settings['auto_research_topics'] ?? '1', '1'); ?> style="width:18px;height:18px;">
                    🤖 Auto Search / Riset Topik Mandiri (Full Autopilot)
                </label>
                <div class="apcai-help" style="margin-top:4px;color:#1d4ed8;">
                    Jika dicentang, AI akan otomatis meriset topik baru dari kategori secara mandiri setiap kali antrian topik menipis (&lt; 5 topik). Anda tidak perlu meriset topik manual lagi — plugin akan terus mencari topik dan mempostingnya tanpa henti.
                </div>

                <div style="display:grid;grid-template-columns: 1fr 1fr;gap:16px;margin-top:12px;">
                    <div class="apcai-form-group" style="margin:0;">
                        <label style="color:#1e3a8a;font-weight:600;">Jumlah Topik Sekali Riset Otomatis</label>
                        <input type="number" name="auto_research_count" class="apcai-input" min="3" max="20" value="<?php echo esc_attr($settings['auto_research_count'] ?? 5); ?>">
                        <div class="apcai-help">Berapa topik yang otomatis di-generate AI saat stok topik habis (Default: 5).</div>
                    </div>

                    <div class="apcai-form-group" style="margin:0;">
                        <label style="color:#1e3a8a;font-weight:600;">Rotasi Kategori Auto Riset</label>
                        <select name="auto_research_category_mode" class="apcai-select">
                            <option value="rotate" <?php selected($settings['auto_research_category_mode'] ?? 'rotate', 'rotate'); ?>>🔄 Rotasi Otomatis Semua Kategori (Merata)</option>
                            <option value="specific" <?php selected($settings['auto_research_category_mode'] ?? '', 'specific'); ?>>🎯 Kategori Tertentu Saja</option>
                        </select>
                        <div class="apcai-help">Memastikan artikel terdistribusi adil di seluruh kategori silo blog Anda.</div>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns: 1fr 1fr;gap:20px;">
                <div class="apcai-form-group">
                    <label>Jumlah Posting Per Hari (Frekuensi)</label>
                    <input type="number" name="posts_per_day" class="apcai-input" min="1" max="24" value="<?php echo esc_attr($settings['posts_per_day'] ?? 2); ?>">
                    <div class="apcai-help">Bisa diset misalnya 2x sehari, 5x sehari, dsb.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Metode Penentuan Jam Posting</label>
                    <select name="schedule_type" class="apcai-select">
                        <option value="random" <?php selected($settings['schedule_type'] ?? '', 'random'); ?>>🎲 Acak / Random Jam Alami (Direkomendasikan)</option>
                        <option value="fixed" <?php selected($settings['schedule_type'] ?? '', 'fixed'); ?>>⏰ Jam Terjadwal Tetap (Fixed Hours)</option>
                    </select>
                    <div class="apcai-help">Random akan membagikan jam secara organik antara jam 07:00 s/d 22:00 agar natural.</div>
                </div>
            </div>

            <div class="apcai-form-group">
                <label>Jam Tetap (Hanya jika memilih Jam Terjadwal Tetap)</label>
                <input type="text" name="fixed_hours" class="apcai-input" value="<?php echo esc_attr($settings['fixed_hours'] ?? '08:00, 13:00, 19:00'); ?>">
                <div class="apcai-help">Pisahkan jam dengan koma (Format 24 Jam, contoh: 09:00, 14:30, 20:00).</div>
            </div>

            <div style="display:grid;grid-template-columns: 1fr 1fr 1fr;gap:20px;">
                <div class="apcai-form-group">
                    <label>Status Post Hasil Auto Generate</label>
                    <select name="post_status" class="apcai-select">
                        <option value="publish" <?php selected($settings['post_status'] ?? '', 'publish'); ?>>Langsung Publish (Terbit Publik)</option>
                        <option value="draft" <?php selected($settings['post_status'] ?? '', 'draft'); ?>>Draft (Tinjau Manual Dulu)</option>
                    </select>
                </div>

                <div class="apcai-form-group">
                    <label>Penulis Default (Author)</label>
                    <?php
                    wp_dropdown_users([
                        'name'     => 'post_author',
                        'selected' => $settings['post_author'] ?? 1,
                        'class'    => 'apcai-select',
                    ]);
                    ?>
                </div>

                <div class="apcai-form-group">
                    <label>Kategori Default</label>
                    <?php
                    wp_dropdown_categories([
                        'name'       => 'post_category',
                        'selected'   => $settings['post_category'] ?? 1,
                        'class'      => 'apcai-select',
                        'hide_empty' => 0,
                    ]);
                    ?>
                </div>
            </div>

            <div style="margin-top:20px;">
                <button type="submit" class="apcai-btn apcai-btn-primary" style="font-size:14px;padding:10px 24px;">
                    Simpan Pengaturan Jadwal
                </button>
            </div>
        </div>
    </form>

    <?php if (!empty($slots)): ?>
    <div class="apcai-card">
        <h2>Slot Jam Posting Terjadwal Hari Ini</h2>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <?php foreach ($slots as $slot): ?>
                <div style="background:#e0f2fe;color:#0369a1;padding:8px 14px;border-radius:6px;font-weight:700;font-size:13px;">
                    ⏰ <?php echo esc_html(date_i18n('H:i:s', $slot)); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
