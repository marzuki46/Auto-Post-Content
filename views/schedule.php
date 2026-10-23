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
