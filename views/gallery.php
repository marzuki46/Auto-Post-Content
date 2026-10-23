<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <h1>
            <span class="dashicons dashicons-format-gallery" style="font-size:30px;color:var(--apcai-primary);"></span>
            Galeri Pool Foto Sendiri (Auto Compress & WebP)
        </h1>
        <p style="color:var(--apcai-slate-500);margin:5px 0 0 0;">
            Upload minimal 10 foto atau lebih dari perangkat Anda. Plugin akan otomatis mengompresi dan mengonversi seluruh foto ke format WebP ringan (&plusmn;40-80 KB) dengan resolusi tajam, lalu merotasikannya sebagai Thumbnail postingan.
        </p>
    </div>

    <!-- Upload Card -->
    <div class="apcai-card">
        <h2>Unggah Foto Baru ke Pool Koleksi</h2>
        <div style="background:#f8fafc;border:2px dashed #cbd5e1;padding:24px;border-radius:8px;text-align:center;">
            <input type="file" id="pool-file-input" multiple accept="image/jpeg,image/png,image/webp" style="display:none;">
            <div style="font-size:36px;margin-bottom:8px;">📁</div>
            <h3 style="margin:0 0 8px 0;color:#1e293b;">Pilih Minimal 10 Foto (JPG, PNG, atau WebP)</h3>
            <p style="color:#64748b;font-size:13px;margin:0 0 16px 0;">
                Sistem akan me-resize maks lebar 1200px dan mengonversi ke WebP kualitas tinggi 82 secara otomatis saat proses upload.
            </p>
            <button type="button" class="apcai-btn apcai-btn-primary" onclick="document.getElementById('pool-file-input').click();">
                Pilih File Gambar dari Komputer
            </button>
            <div id="pool-upload-status" style="margin-top:12px;font-weight:600;font-size:13px;"></div>
        </div>
    </div>

    <!-- Gallery List Card -->
    <div class="apcai-card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h2 style="margin:0;">
                Daftar Foto Pool Tersedia (<span id="pool-count"><?php echo count($pool_images); ?></span> Foto)
            </h2>
            <div style="font-size:13px;color:#64748b;">
                <?php echo count($pool_images) >= 10 ? '✅ Kuota minimal 10 foto terpenuhi.' : '⚠️ Disarankan upload minimal 10 foto agar variasi thumbnail kaya.'; ?>
            </div>
        </div>

        <div id="pool-gallery-grid" style="display:grid;grid-template-columns:repeat(auto-fill, minmax(180px, 1fr));gap:16px;">
            <?php if (empty($pool_images)): ?>
                <div id="pool-empty-notice" style="grid-column:1/-1;text-align:center;padding:40px;color:#94a3b8;">
                    Belum ada foto yang diunggah. Silakan upload minimal 10 foto di atas.
                </div>
            <?php else: ?>
                <?php foreach ($pool_images as $img): ?>
                    <div class="pool-item-card" data-id="<?php echo esc_attr($img['id']); ?>" style="position:relative;background:#fff;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <img src="<?php echo esc_url($img['url']); ?>" style="width:100%;height:130px;object-fit:cover;display:block;">
                        <div style="padding:8px 10px;font-size:11px;color:#64748b;display:flex;justify-content:space-between;align-items:center;">
                            <span><?php echo esc_html($img['size']); ?></span>
                            <button type="button" class="btn-delete-pool-img" data-id="<?php echo esc_attr($img['id']); ?>" style="background:none;border:none;color:#ef4444;cursor:pointer;font-weight:700;font-size:12px;">Hapus</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
