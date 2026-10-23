<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <h1>
            <span class="dashicons dashicons-superhero-alt" style="font-size:30px;color:var(--apcai-primary);"></span>
            Auto Post Content AI & Funnel Booster
        </h1>
        <div style="display:flex;gap:10px;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=apcai-studio')); ?>" class="apcai-btn apcai-btn-primary">✍️ Buat Konten Silo</a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=apcai-landing-pages')); ?>" class="apcai-btn apcai-btn-secondary">🎯 Target Landing Pages</a>
        </div>
    </div>

    <!-- Stats Bento Grid -->
    <div class="apcai-stats-grid">
        <div class="apcai-stat-box">
            <div class="apcai-stat-title">Stok Topik Siap Pakai</div>
            <div class="apcai-stat-val"><?php echo esc_html($topics_count); ?></div>
        </div>
        <div class="apcai-stat-box cyan">
            <div class="apcai-stat-title">Landing Pages (Money Pages)</div>
            <div class="apcai-stat-val"><?php echo esc_html($lp_count); ?></div>
        </div>
        <div class="apcai-stat-box green">
            <div class="apcai-stat-title">Artikel ID Terbit</div>
            <div class="apcai-stat-val"><?php echo esc_html($posts_id_count); ?></div>
        </div>
        <div class="apcai-stat-box purple">
            <div class="apcai-stat-title">Artikel EN Terbit</div>
            <div class="apcai-stat-val"><?php echo esc_html($posts_en_count); ?></div>
        </div>
        <div class="apcai-stat-box amber">
            <div class="apcai-stat-title">Status Auto Schedule</div>
            <div class="apcai-stat-val" style="font-size:15px;margin-top:5px;">
                <?php echo !empty($settings['schedule_enabled']) ? '<span style="color:var(--apcai-success);font-weight:700;">● AKTIF ('.esc_html($settings['posts_per_day'] ?? 2).'x/hari)</span>' : '<span style="color:var(--apcai-danger);font-weight:700;">○ NONAKTIF</span>'; ?>
            </div>
        </div>
    </div>

    <!-- Funnel Distribution Card -->
    <div class="apcai-card">
        <h2>📊 Distribusi Content Funnel (Silo Optimization)</h2>
        <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:14px;">
            Rasio konten yang diterbitkan untuk mengalirkan otoritas SEO ke Target Landing Page Anda:
        </p>
        
        <div style="display:grid;grid-template-columns: repeat(3, 1fr);gap:16px;text-align:center;">
            <div style="background:#ecfeff;padding:12px;border-radius:6px;border:1px solid #cffafe;">
                <div style="font-size:12px;font-weight:700;color:#0e7490;">TOFU (Awareness & Edukasi)</div>
                <div style="font-size:22px;font-weight:800;color:#0891b2;margin-top:4px;"><?php echo esc_html($tofu_count); ?> Artikel</div>
                <div style="font-size:11px;color:#155e75;margin-top:2px;">Target: Menarik Traffic Masif</div>
            </div>
            <div style="background:#f5f3ff;padding:12px;border-radius:6px;border:1px solid #ede9fe;">
                <div style="font-size:12px;font-weight:700;color:#6d28d9;">MOFU (Review & Komparasi)</div>
                <div style="font-size:22px;font-weight:800;color:#7c3aed;margin-top:4px;"><?php echo esc_html($mofu_count); ?> Artikel</div>
                <div style="font-size:11px;color:#5b21b6;margin-top:2px;">Target: Filter Minat Pembeli</div>
            </div>
            <div style="background:#ecfdf5;padding:12px;border-radius:6px;border:1px solid #d1fae5;">
                <div style="font-size:12px;font-weight:700;color:#065f46;">BOFU (Penawaran & Transaksi)</div>
                <div style="font-size:22px;font-weight:800;color:#059669;margin-top:4px;"><?php echo esc_html($bofu_count); ?> Artikel</div>
                <div style="font-size:11px;color:#047857;margin-top:2px;">Target: Konversi ke Landing Page</div>
            </div>
        </div>

        <?php 
        $total_funnel = $tofu_count + $mofu_count + $bofu_count;
        $tofu_p = $total_funnel > 0 ? round(($tofu_count / $total_funnel) * 100) : 33;
        $mofu_p = $total_funnel > 0 ? round(($mofu_count / $total_funnel) * 100) : 33;
        $bofu_p = $total_funnel > 0 ? (100 - $tofu_p - $mofu_p) : 34;
        ?>
        <div class="apcai-funnel-bar" style="margin-top:16px;">
            <div class="apcai-bar-tofu" style="width:<?php echo $tofu_p; ?>%;" title="TOFU: <?php echo $tofu_p; ?>%"></div>
            <div class="apcai-bar-mofu" style="width:<?php echo $mofu_p; ?>%;" title="MOFU: <?php echo $mofu_p; ?>%"></div>
            <div class="apcai-bar-bofu" style="width:<?php echo $bofu_p; ?>%;" title="BOFU: <?php echo $bofu_p; ?>%"></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="apcai-card">
        <h2>⚡ Aksi Eksekusi Instan</h2>
        <p style="color:var(--apcai-slate-500);margin-bottom:16px;font-size:13px;">
            Jalankan posting artikel baru segera dengan algoritma rotasi kategori & injeksi internal link:
        </p>
        <button id="btn-trigger-cron" class="apcai-btn apcai-btn-success" style="font-size:14px;padding:12px 22px;">
            🚀 Jalankan Posting Sekarang (Manual Trigger)
        </button>
        <a href="<?php echo esc_url(admin_url('admin.php?page=apcai-topics')); ?>" class="apcai-btn apcai-btn-secondary" style="font-size:14px;padding:12px 22px;margin-left:10px;">
            🔍 Kelola Bank Topik & Funnel
        </a>
    </div>

    <!-- Upcoming Slots Today -->
    <?php if (!empty($scheduled_slots)): ?>
    <div class="apcai-card">
        <h2>📅 Jadwal Antrian Posting Hari Ini</h2>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <?php foreach ($scheduled_slots as $slot): ?>
                <div style="background:#e0f2fe;color:#0369a1;padding:8px 14px;border-radius:6px;font-weight:700;font-size:13px;">
                    ⏰ <?php echo esc_html(date_i18n('H:i:s (d F Y)', $slot)); ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
