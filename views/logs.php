<?php defined('ABSPATH') || exit; ?>
<div class="wrap apcai-wrap">
    <div class="apcai-header">
        <div>
            <h1><span class="dashicons dashicons-list-view" style="font-size:28px;color:#2563eb;"></span> <?php echo esc_html(APCAI_I18n::t('Riwayat Eksekusi & Log AI', 'AI Execution & Activity Logs')); ?></h1>
            <p style="color:var(--apcai-slate-500);margin:4px 0 0 0;font-size:13px;">
                <?php echo esc_html(APCAI_I18n::t('Rekam jejak otomatisasi posting, riset topik, injeksi keyword SEO, dan webhook indexing.', 'Automated logs for scheduling, topic brainstorm, SEO keyword backfill, and indexing.')); ?>
            </p>
        </div>
        <div>
            <button type="button" id="btn-clear-logs" class="apcai-btn apcai-btn-secondary" style="color:#ef4444;border-color:#fca5a5;">
                🗑️ <?php echo esc_html(APCAI_I18n::t('Bersihkan Semua Log', 'Clear All Logs')); ?>
            </button>
        </div>
    </div>

    <div class="apcai-card">
        <h2><?php echo esc_html(APCAI_I18n::t('Riwayat Aktivitas Sistem', 'System Activity Logs')); ?></h2>
        <table class="apcai-table">
            <thead>
                <tr>
                    <th style="width:60px;">ID</th>
                    <th><?php echo esc_html(APCAI_I18n::t('Waktu', 'Timestamp')); ?></th>
                    <th><?php echo esc_html(APCAI_I18n::t('Tipe Aksi', 'Action Type')); ?></th>
                    <th>Status</th>
                    <th><?php echo esc_html(APCAI_I18n::t('Pesan / Keterangan', 'Message / Details')); ?></th>
                </tr>
            </thead>
            <tbody id="table-logs-body">
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td><strong>#<?php echo esc_html($l->id); ?></strong></td>
                        <td style="color:#64748b;font-size:12px;"><?php echo esc_html(date_i18n('d M Y H:i:s', strtotime($l->created_at))); ?></td>
                        <td><span class="apcai-badge apcai-badge-blue"><?php echo esc_html($l->action_type); ?></span></td>
                        <td>
                            <?php if ($l->status === 'success'): ?>
                                <span class="apcai-badge apcai-badge-green">Success</span>
                            <?php else: ?>
                                <span class="apcai-badge apcai-badge-red"><?php echo esc_html(strtoupper($l->status)); ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="line-height:1.6;font-size:13px;"><?php echo wp_kses_post($l->message); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;padding:40px;color:#64748b;">
                            <?php echo esc_html(APCAI_I18n::t('Belum ada log eksekusi tercatat.', 'No activity logs found.')); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
