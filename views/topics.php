<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <h1>
            <span class="dashicons dashicons-search" style="font-size:30px;color:var(--apcai-primary);"></span>
            Riset & Bank Topik Berdasarkan Kategori & Funnel
        </h1>
        <div style="display:flex;gap:10px;align-items:center;">
            <select id="topics-category" class="apcai-select" style="width:160px;">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->term_id); ?>"><?php echo esc_html($cat->name); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="topics-funnel-filter" class="apcai-select" style="width:140px;">
                <option value="">Semua Funnel</option>
                <option value="tofu">TOFU (Edu)</option>
                <option value="mofu">MOFU (Review)</option>
                <option value="bofu">BOFU (Money)</option>
            </select>
            <input type="number" id="topics-count" value="5" min="1" max="20" style="width:60px;padding:8px;border-radius:6px;border:1px solid var(--apcai-slate-200);text-align:center;">
            <button id="btn-brainstorm-topics" class="apcai-btn apcai-btn-primary">
                ⚡ Riset Topik Funneling
            </button>
        </div>
    </div>

    <div class="apcai-card">
        <h2>Daftar Topik AI Tersedia</h2>
        <p style="color:var(--apcai-slate-500);font-size:13px;margin-top:-6px;margin-bottom:14px;">
            Topik-topik ini siap diposting otomatis sesuai jam posting harian dan kategori yang ditentukan:
        </p>

        <?php if (!empty($topics)): ?>
        <table class="apcai-table">
            <thead>
                <tr>
                    <th style="width:50px;">ID</th>
                    <th>Judul Topik / Ide Konten</th>
                    <th>Kategori Target</th>
                    <th>Funnel Stage</th>
                    <th>Keyword Utama</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($topics as $t): ?>
                <?php $cat = get_category($t->category_id); ?>
                <tr>
                    <td><strong>#<?php echo esc_html($t->id); ?></strong></td>
                    <td style="font-weight:600;color:var(--apcai-slate-900);">
                        <?php echo esc_html($t->title); ?>
                    </td>
                    <td>
                        <span class="apcai-badge apcai-badge-blue">
                            <?php echo esc_html($cat ? $cat->name : 'General'); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($t->funnel_stage === 'tofu'): ?>
                            <span class="apcai-badge apcai-badge-cyan">TOFU (Edu)</span>
                        <?php elseif ($t->funnel_stage === 'mofu'): ?>
                            <span class="apcai-badge apcai-badge-purple">MOFU (Review)</span>
                        <?php elseif ($t->funnel_stage === 'bofu'): ?>
                            <span class="apcai-badge apcai-badge-green">BOFU (Money)</span>
                        <?php else: ?>
                            <span class="apcai-badge"><?php echo esc_html($t->funnel_stage ?: 'tofu'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-size:12px;color:var(--apcai-slate-600);"><?php echo esc_html($t->primary_keyword ?: '-'); ?></span>
                    </td>
                    <td>
                        <?php if ($t->status === 'idea'): ?>
                            <span class="apcai-badge apcai-badge-green">Siap Pakai</span>
                        <?php elseif ($t->status === 'used'): ?>
                            <span class="apcai-badge apcai-badge-amber">Sudah Diposting</span>
                        <?php else: ?>
                            <span class="apcai-badge"><?php echo esc_html($t->status); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <button class="apcai-btn apcai-btn-danger btn-delete-topic" data-id="<?php echo esc_attr($t->id); ?>" style="padding:4px 8px;font-size:12px;">Hapus</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div style="text-align:center;padding:50px 20px;color:var(--apcai-slate-500);">
                <p>Belum ada topik tersimpan. Pilih kategori di kanan atas lalu klik <strong>"⚡ Riset Topik Funneling"</strong>.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
