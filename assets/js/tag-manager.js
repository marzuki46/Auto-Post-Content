jQuery(document).ready(function ($) {
    'use strict';

    const ajaxurl = (typeof apcai_tag_vars !== 'undefined') ? apcai_tag_vars.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php');
    const nonce   = (typeof apcai_tag_vars !== 'undefined') ? apcai_tag_vars.nonce : '';

    let batchQueue = [];
    let isBatchRunning = false;
    let isBatchPaused = false;
    let completedCount = 0;
    let totalBatchCount = 0;

    // Filter Buttons (Semua / Kosong Saja / Sudah Ada)
    $('.btn-filter-tags').on('click', function () {
        $('.btn-filter-tags').removeClass('active');
        $(this).addClass('active');
        const filter = $(this).data('filter');

        if (filter === 'all') {
            $('.tag-row').show();
        } else if (filter === 'empty') {
            $('.tag-row').hide();
            $('.tag-row[data-status="empty"]').show();
        } else if (filter === 'filled') {
            $('.tag-row').hide();
            $('.tag-row[data-status="filled"]').show();
        }
    });

    // Toggle Tag Schedule (Background Cron)
    $('#toggle_tag_schedule, #tag_schedule_daily_count, #tag_schedule_execution_mode').on('change', function () {
        const isChecked = $('#toggle_tag_schedule').is(':checked') ? 1 : 0;
        const lang       = $('#bulk_tag_lang').val() || 'id';
        const length     = $('#bulk_tag_length').val() || 'medium';
        const funnel     = $('#bulk_tag_funnel').val() || 'mofu';
        const dailyCount = $('#tag_schedule_daily_count').val() || 3;
        const execMode   = $('#tag_schedule_execution_mode').val() || 'with_post';

        if (isChecked) {
            $('#tag_schedule_quota_box').slideDown(200);
        } else {
            $('#tag_schedule_quota_box').slideUp(200);
        }

        $.post(ajaxurl, {
            action: 'apcai_toggle_tag_schedule',
            nonce: nonce,
            enabled: isChecked,
            lang: lang,
            length: length,
            funnel: funnel,
            daily_count: dailyCount,
            exec_mode: execMode,
        }, function (res) {
            if (res.success) {
                if (typeof showNotice === 'function') {
                    showNotice(res.data.message, 'success');
                } else {
                    alert(res.data.message);
                }
            }
        });
    });

    // Start Batch Generate for All Empty Tags
    $('#btn-start-batch-tags').on('click', function (e) {
        e.preventDefault();

        if (isBatchRunning) return;

        const $btn = $(this);
        const $pauseBtn = $('#btn-pause-batch-tags');
        const $statusText = $('#batch-tag-status-text');
        const $progressWrapper = $('#batch-progress-wrapper');
        const $progressBar = $('#batch-progress-bar');
        const $currentTagText = $('#batch-current-tag-text');
        const $percentText = $('#batch-percent-text');

        $btn.prop('disabled', true);
        $statusText.html('<span class="apcai-spinner"></span> Mengambil daftar tag kosong...');

        $.post(ajaxurl, {
            action: 'apcai_get_empty_tags',
            nonce: nonce,
        }, function (res) {
            if (!res.success || !res.data || res.data.tags.length === 0) {
                $btn.prop('disabled', false);
                $statusText.html('<span style="color:#10b981;font-weight:600;">🎉 Semua tag sudah memiliki konten copywriting!</span>');
                return;
            }

            batchQueue = res.data.tags;
            totalBatchCount = batchQueue.length;
            completedCount = 0;
            isBatchRunning = true;
            isBatchPaused = false;

            $progressWrapper.slideDown(200);
            $pauseBtn.show().prop('disabled', false).text('⏸️ Jeda Antrean');
            $statusText.html('<span style="color:#2563eb;font-weight:600;">Memproses ' + totalBatchCount + ' tag... Harap biarkan halaman ini terbuka.</span>');

            processNextBatchItem();
        }).fail(function () {
            $btn.prop('disabled', false);
            $statusText.html('<span style="color:#ef4444;">Gagal mengambil antrean tag.</span>');
        });
    });

    // Pause / Resume Batch
    $('#btn-pause-batch-tags').on('click', function () {
        if (!isBatchRunning) return;

        if (!isBatchPaused) {
            isBatchPaused = true;
            $(this).text('▶️ Lanjutkan Antrean');
            $('#batch-tag-status-text').html('<span style="color:#f59e0b;font-weight:600;">Antrean dijeda sementara.</span>');
        } else {
            isBatchPaused = false;
            $(this).text('⏸️ Jeda Antrean');
            $('#batch-tag-status-text').html('<span style="color:#2563eb;font-weight:600;">Melanjutkan antrean...</span>');
            processNextBatchItem();
        }
    });

    function processNextBatchItem() {
        if (!isBatchRunning || isBatchPaused) return;

        if (batchQueue.length === 0) {
            // Selesai seluruh antrean
            isBatchRunning = false;
            $('#btn-start-batch-tags').prop('disabled', false);
            $('#btn-pause-batch-tags').hide();
            $('#batch-progress-bar').css('width', '100%');
            $('#batch-percent-text').text('100%');
            $('#batch-current-tag-text').text('Semua tag selesai diproses!');
            $('#batch-tag-status-text').html('<span style="color:#10b981;font-weight:700;">🎉 Selesai! ' + completedCount + ' tag berhasil dibuatkan copywriting & diset ke Frasa Kunci Yoast.</span>');
            updateStatsCounter();
            return;
        }

        const currentItem = batchQueue.shift();
        const lang   = $('#bulk_tag_lang').val() || 'id';
        const length = $('#bulk_tag_length').val() || 'medium';
        const funnel = $('#bulk_tag_funnel').val() || 'mofu';

        const percent = Math.round((completedCount / totalBatchCount) * 100);
        $('#batch-progress-bar').css('width', percent + '%');
        $('#batch-percent-text').text(percent + '%');
        $('#batch-current-tag-text').html('Sedang menyusun: <strong>"' + currentItem.name + '"</strong> (' + (completedCount + 1) + '/' + totalBatchCount + ')...');

        // Tandai baris tabel yang sedang diproses
        const $row = $('#tag-row-' + currentItem.id);
        if ($row.length) {
            $row.find('.tag-status-cell').html('<span class="apcai-spinner"></span> <span style="color:#2563eb;">Menulis AI...</span>');
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apcai_batch_generate_single_tag',
                nonce: nonce,
                tag_id: currentItem.id,
                tag_name: currentItem.name,
                lang: lang,
                length: length,
                funnel: funnel,
            },
            success: function (res) {
                completedCount++;
                if (res.success && res.data) {
                    if ($row.length) {
                        $row.attr('data-status', 'filled');
                        $row.find('.tag-status-cell').html(
                            '<span class="apcai-badge apcai-badge-green">✅ Aktif (' + res.data.word_count + ' kata • ' + res.data.lang + ' • ' + res.data.funnel + ')</span>'
                        );
                    }
                } else {
                    if ($row.length) {
                        $row.find('.tag-status-cell').html(
                            '<span class="apcai-badge apcai-badge-red">❌ Gagal</span>'
                        );
                    }
                }
                updateStatsCounter();
                // Jeda santai alami 3000ms - 5000ms per tag agar tidak terlalu cepat
                setTimeout(processNextBatchItem, Math.floor(Math.random() * 2000) + 3000);
            },
            error: function () {
                completedCount++;
                if ($row.length) {
                    $row.find('.tag-status-cell').html(
                        '<span class="apcai-badge apcai-badge-red">⚠️ Timeout / Skip</span>'
                    );
                }
                setTimeout(processNextBatchItem, 3000);
            }
        });
    }

    function updateStatsCounter() {
        const total = $('.tag-row').length;
        const filled = $('.tag-row[data-status="filled"]').length;
        const empty = Math.max(0, total - filled);
        $('#stat-total-tags').text(total);
        $('#stat-filled-tags').text(filled);
        $('#stat-empty-tags').text(empty);
    }

    // Quick Generate Single Tag from Table Button
    $(document).on('click', '.btn-quick-gen-tag', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const tagId = $btn.data('id');
        const tagName = $btn.data('name');
        const lang   = $('#bulk_tag_lang').val() || 'id';
        const length = $('#bulk_tag_length').val() || 'medium';
        const funnel = $('#bulk_tag_funnel').val() || 'mofu';

        const originalText = $btn.html();
        $btn.html('<span class="apcai-spinner"></span>').prop('disabled', true);

        const $row = $('#tag-row-' + tagId);
        $row.find('.tag-status-cell').html('<span class="apcai-spinner"></span> Menulis AI...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apcai_batch_generate_single_tag',
                nonce: nonce,
                tag_id: tagId,
                tag_name: tagName,
                lang: lang,
                length: length,
                funnel: funnel,
            },
            success: function (res) {
                $btn.html(originalText).prop('disabled', false);
                if (res.success && res.data) {
                    $row.attr('data-status', 'filled');
                    $row.find('.tag-status-cell').html(
                        '<span class="apcai-badge apcai-badge-green">✅ Aktif (' + res.data.word_count + ' kata • ' + res.data.lang + ' • ' + res.data.funnel + ')</span>'
                    );
                    updateStatsCounter();
                } else {
                    $row.find('.tag-status-cell').html('<span class="apcai-badge apcai-badge-red">❌ Gagal</span>');
                }
            },
            error: function () {
                $btn.html(originalText).prop('disabled', false);
                $row.find('.tag-status-cell').html('<span class="apcai-badge apcai-badge-red">⚠️ Error Koneksi</span>');
            }
        });
    });

    // Native Edit Tag Page Generator Trigger
    $('#btn-generate-tag-copywriting').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const $status = $('#tag-gen-status');
        const tagId = $btn.data('tag-id') || 0;
        let tagName = $btn.data('tag-name') || $('#name').val() || '';

        if (!tagName) {
            alert('Nama tag tidak boleh kosong.');
            return;
        }

        const lang   = $('#apcai_tag_lang').val() || 'id';
        const length = $('#apcai_tag_length').val() || 'medium';
        const funnel = $('#apcai_tag_funnel').val() || 'mofu';

        const originalText = $btn.html();
        $btn.html('<span class="apcai-spinner"></span> Sedang Menyusun Copywriting Tag...').prop('disabled', true);
        $status.html('<span style="color:#2563eb;">🤖 AI sedang menganalisis entitas & merancang heading H2-H4...</span>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apcai_generate_tag_copywriting',
                nonce: nonce,
                tag_id: tagId,
                tag_name: tagName,
                lang: lang,
                length: length,
                funnel: funnel,
            },
            success: function (res) {
                $btn.html(originalText).prop('disabled', false);
                if (res.success && res.data) {
                    $('#apcai_tag_copywriting').val(res.data.content);
                    $status.html('<span style="color:#10b981;font-weight:600;">✅ Selesai! ' + res.data.word_count + ' kata tersimpan (Single H1 & Yoast focuskw terjamin).</span>');
                } else {
                    $status.html('<span style="color:#ef4444;">❌ ' + (res.data || 'Gagal generate copywriting.') + '</span>');
                }
            },
            error: function () {
                $btn.html(originalText).prop('disabled', false);
                $status.html('<span style="color:#ef4444;">❌ Terjadi kesalahan jaringan / server timeout.</span>');
            }
        });
    });
});
