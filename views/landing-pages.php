<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <h1>
            <span class="dashicons dashicons-external" style="font-size:30px;color:var(--apcai-primary);"></span>
            Target Landing Pages (Money Pages / BOFU)
        </h1>
    </div>

    <div style="display:grid;grid-template-columns: 380px 1fr;gap:24px;">
        <!-- Left: Form Input Landing Page -->
        <div class="apcai-card">
            <h2>➕ Tambah Target Landing Page</h2>
            <form id="form-add-landing-page">
                <div class="apcai-form-group">
                    <label>Kategori WordPress Terkait <span style="color:#ef4444;">*</span></label>
                    <select id="lp_category_id" class="apcai-select" required>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat->term_id); ?>">
                                <?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->count); ?> post)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="apcai-help">Artikel dalam kategori ini akan otomatis mengalirkan backlink internal ke LP ini.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Judul / Nama Produk / Layanan</label>
                    <input type="text" id="lp_title" class="apcai-input" placeholder="Contoh: Jasa Pembuatan Website & SEO">
                </div>

                <div class="apcai-form-group">
                    <label>URL Landing Page Tujuan <span style="color:#ef4444;">*</span></label>
                    <input type="url" id="lp_url" class="apcai-input" placeholder="https://domain.com/landing-page" required>
                    <div class="apcai-help">Bisa link page di website ini atau subdomain / external link.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Variasi Target Kata Kunci (Anchor Text) <span style="color:#ef4444;">*</span></label>
                    <textarea id="lp_anchors" class="apcai-textarea" rows="4" placeholder="jasa seo profesional, ahli seo terpercaya, paket seo bulanan, konsultasi optimasi website" required></textarea>
                    <div class="apcai-help">Pisahkan dengan koma. AI akan merotasi kata kunci ini agar anchor text terlihat alami di mata Google.</div>
                </div>

                <div class="apcai-form-group">
                    <label>Teks Tombol CTA Default</label>
                    <input type="text" id="lp_btn_text" class="apcai-input" value="Beli Sekarang / Konsultasi">
                </div>

                <button type="submit" class="apcai-btn apcai-btn-primary" style="width:100%;justify-content:center;">
                    💾 Simpan Target Landing Page
                </button>
            </form>
        </div>

        <!-- Right: List Landing Pages -->
        <div class="apcai-card">
            <h2>Daftar Target Landing Page Terpasang</h2>
            <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:14px;">
                Landing Page di bawah ini menerima suntikan internal link otomatis dari setiap artikel baru di kategorinya:
            </p>

            <?php if (!empty($landing_pages)): ?>
            <table class="apcai-table">
                <thead>
                    <tr>
                        <th>Kategori</th>
                        <th>Target Landing Page</th>
                        <th>Variasi Anchor Keywords</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($landing_pages as $lp): ?>
                    <?php $cat = get_category($lp->category_id); ?>
                    <tr>
                        <td>
                            <span class="apcai-badge apcai-badge-blue">
                                <?php echo esc_html($cat ? $cat->name : 'General'); ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo esc_html($lp->page_title ?: 'Landing Page'); ?></strong><br>
                            <a href="<?php echo esc_url($lp->page_url); ?>" target="_blank" rel="noopener noreferrer" style="font-size:12px;color:var(--apcai-primary);">
                                <?php echo esc_html($lp->page_url); ?> ↗
                            </a>
                        </td>
                        <td>
                            <?php 
                            $kw_list = array_filter(array_map('trim', explode(',', $lp->anchor_keywords)));
                            foreach (array_slice($kw_list, 0, 4) as $k): 
                            ?>
                                <span class="apcai-badge apcai-badge-purple" style="margin:2px 2px 2px 0;">
                                    <?php echo esc_html($k); ?>
                                </span>
                            <?php endforeach; ?>
                            <?php if (count($kw_list) > 4): ?>
                                <span style="font-size:11px;color:#64748b;">+<?php echo (count($kw_list) - 4); ?> lainnya</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <button class="apcai-btn apcai-btn-danger btn-delete-lp" data-id="<?php echo esc_attr($lp->id); ?>" style="padding:4px 8px;font-size:12px;">Hapus</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
                <div style="text-align:center;padding:50px 20px;color:var(--apcai-slate-500);">
                    <p style="font-size:14px;">Belum ada Target Landing Page yang ditambahkan.</p>
                    <p style="font-size:12px;">Tambahkan URL penawaran/produk utama Anda di formulir sebelah kiri untuk mulai menaikkan rankingnya.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
