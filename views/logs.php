<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <h1><span class="dashicons dashicons-list-view" style="font-size:28px;color:#2563eb;"></span> Riwayat Eksekusi & Log AI</h1>
    </div>

    <div class="apcai-card">
        <h2>Riwayat Aktivitas Penjadwalan & Posting</h2>
        <?php if (!empty($logs)): ?>
        <table class="apcai-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th>Waktu</th>
                    <th>Tipe Aksi</th>
                    <th>Status</th>
                    <th>Pesan / Keterangan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $l): ?>
                <tr>
                    <td><strong>#<?php echo esc_html($l->id); ?></strong></td>
                    <td style="color:#64748b;"><?php echo esc_html(date_i18n('d M Y H:i:s', strtotime($l->created_at))); ?></td>
                    <td><span class="apcai-badge apcai-badge-blue"><?php echo esc_html($l->action_type); ?></span></td>
                    <td>
                        <?php if ($l->status === 'success'): ?>
                            <span class="apcai-badge apcai-badge-green">Sukses</span>
                        <?php else: ?>
                            <span class="apcai-badge apcai-badge-red"><?php echo esc_html($l->status); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo esc_html($l->message); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <p style="text-align:center;padding:40px;color:#64748b;">Belum ada log eksekusi tercatat.</p>
        <?php endif; ?>
    </div>
</div>
