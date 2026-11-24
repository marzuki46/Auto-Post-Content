jQuery(document).ready(function ($) {
    'use strict';

    const ajaxurl = (typeof apcai_tag_vars !== 'undefined') ? apcai_tag_vars.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php');
    const nonce   = (typeof apcai_tag_vars !== 'undefined') ? apcai_tag_vars.nonce : '';

    let batchQueue = [];
    let isBatchRunning = false;
    let isBatchPaused = false;
    let completedCount = 0;
    let totalBatchCount = 0;

    let currentTagPage = 1;
    let totalTagPages = 1;
    let currentTagFilter = 'all';
    let currentTagSearch = '';
    let searchDebounceTimer = null;

    // Load Tags with Pagination & Search
    function loadTagsList(page) {
        currentTagPage = page || 1;
        const $tbody = $('#tags-table-body');
        $tbody.html('<tr><td colspan="5" style="text-align:center;padding:30px 20px;color:#64748b;"><span class="apcai-spinner"></span> Memuat data tag...</td></tr>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apcai_load_tags_paged',
                nonce: nonce,
                page: currentTagPage,
                per_page: 50,
                search: currentTagSearch,
                filter: currentTagFilter,
            },
            success: function (res) {
                if (!res.success || !res.data) {
                    $tbody.html('<tr><td colspan="5" style="text-align:center;padding:30px;color:#ef4444;">Gagal memuat tag.</td></tr>');
                    return;
                }

                const data = res.data;
                totalTagPages = data.total_pages || 1;
                $('#tag-pagination-info').text('Menampilkan ' + data.items.length + ' dari total ' + data.total + ' tag');
                $('#tag-current-page-text').text('Hal ' + data.page + ' / ' + totalTagPages);
                $('#btn-prev-tag-page').prop('disabled', data.page <= 1);
                $('#btn-next-tag-page').prop('disabled', data.page >= totalTagPages);

                if (data.items.length === 0) {
                    $tbody.html('<tr><td colspan="5" style="text-align:center;padding:30px;color:#94a3b8;">Tidak ada tag yang sesuai kriteria.</td></tr>');
                    return;
                }

                $tbody.empty();
                $.each(data.items, function (_, t) {
                    const statusBadge = t.has_content
                        ? '<span class="apcai-badge apcai-badge-green">✅ Aktif (' + t.words + ' kata • ' + t.lang + ' • ' + t.funnel + ')</span>'
                        : '<span class="apcai-badge apcai-badge-amber">⚠️ Belum Ada Konten</span>';

                    const viewBtn = t.view_url
                        ? '<a href="' + t.view_url + '" target="_blank" class="apcai-btn apcai-btn-secondary" style="padding:4px 8px;font-size:11px;" title="Lihat di Web">👁️</a>'
                        : '';

                    const rowHtml =
                        '<tr class="tag-row" data-status="' + (t.has_content ? 'filled' : 'empty') + '" id="tag-row-' + t.id + '">' +
                            '<td>' +
                                '<strong style="color:#0f172a;font-size:14px;">' + t.name + '</strong>' +
                                '<div style="font-size:11px;color:#64748b;">slug: <code>' + t.slug + '</code></div>' +
                            '</td>' +
                            '<td><span class="apcai-badge apcai-badge-blue">' + t.count + ' Artikel</span></td>' +
                            '<td class="tag-status-cell">' + statusBadge + '</td>' +
                            '<td><code style="font-size:12px;background:#f1f5f9;padding:2px 6px;border-radius:4px;">' + (t.yoast_kw || t.name) + '</code></td>' +
                            '<td style="text-align:right;">' +
                                '<div style="display:inline-flex;gap:6px;">' +
                                    '<button type="button" class="apcai-btn apcai-btn-secondary btn-quick-gen-tag" data-id="' + t.id + '" data-name="' + t.name + '" style="padding:4px 10px;font-size:11px;">⚡ ' + (t.has_content ? 'Tulis Ulang' : 'Tulis AI') + '</button>' +
                                    '<a href="' + t.edit_url + '" target="_blank" class="apcai-btn apcai-btn-secondary" style="padding:4px 8px;font-size:11px;" title="Edit di WP">✏️</a>' +
                                    viewBtn +
                                '</div>' +
                            '</td>' +
                        '</tr>';
                    $tbody.append(rowHtml);
                });
            },
            error: function () {
                $tbody.html('<tr><td colspan="5" style="text-align:center;padding:30px;color:#ef4444;">Terjadi kesalahan jaringan.</td></tr>');
            }
        });
    }

    // Init list on page load
    if ($('#tags-table-body').length) {
        loadTagsList(1);
    }

    // Filter Buttons (Semua / Kosong Saja / Sudah Ada)
    $('.btn-filter-tags').on('click', function () {
        $('.btn-filter-tags').removeClass('active');
        $(this).addClass('active');
        currentTagFilter = $(this).data('filter');
        loadTagsList(1);
    });

    // Search Box Debounce
    $('#tag-search-input').on('keyup input', function () {
        clearTimeout(searchDebounceTimer);
        currentTagSearch = $.trim($(this).val());
        searchDebounceTimer = setTimeout(function () {
            loadTagsList(1);
        }, 400);
    });

    // Pagination Click
    $('#btn-prev-tag-page').on('click', function () {
        if (currentTagPage > 1) {
            loadTagsList(currentTagPage - 1);
        }
    });
    $('#btn-next-tag-page').on('click', function () {
        if (currentTagPage < totalTagPages) {
            loadTagsList(currentTagPage + 1);
        }
    });

    // -------------------------------------------------------------
    // AI Tag Health Auditor & Cleaner Engine
    // -------------------------------------------------------------
    let allCandidates = [];
    let isAuditing = false;
    let candidateActiveTab = 'all';

    $('#btn-start-tag-audit').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const originalText = $btn.html();
        isAuditing = true;
        $btn.prop('disabled', true).html('<span class="apcai-spinner"></span> Sedang Menganalisis Tag...');

        const critZombie   = $('#audit_crit_zombie').is(':checked') ? 1 : 0;
        const critLongtail = $('#audit_crit_longtail').is(':checked') ? 1 : 0;
        const critNiche    = $('#audit_crit_niche').is(':checked') ? 1 : 0;
        const critGarbage  = $('#audit_crit_garbage').is(':checked') ? 1 : 0;
        const protectCopy  = $('#audit_protect_copy').is(':checked') ? 1 : 0;
        const maxWords     = $('#audit_max_words').val() || 3;

        if (!critZombie && !critLongtail && !critNiche && !critGarbage) {
            alert('Pilih minimal satu kriteria audit.');
            $btn.prop('disabled', false).html(originalText);
            return;
        }

        allCandidates = [];
        $('#tag-candidates-body').empty();
        $('#tag-candidates-container').hide();
        $('#tag-audit-progress-box').slideDown(200);
        $('#tag-audit-progress-bar').css('width', '5%');
        $('#tag-audit-percent-text').text('0%');
        $('#tag-audit-status-text').text('Menghubungkan ke AI Engine & membaca profil website...');

        function runAuditBatch(offset) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'apcai_analyze_tags_with_ai',
                    nonce: nonce,
                    offset: offset,
                    crit_zombie: critZombie,
                    crit_longtail: critLongtail,
                    crit_niche: critNiche,
                    crit_garbage: critGarbage,
                    protect_copy: protectCopy,
                    max_words: maxWords,
                },
                success: function (res) {
                    if (!res.success || !res.data) {
                        $btn.prop('disabled', false).html(originalText);
                        $('#tag-audit-status-text').html('<span style="color:#ef4444;">Gagal menganalisis tag.</span>');
                        return;
                    }

                    const data = res.data;
                    if (data.candidates && data.candidates.length > 0) {
                        allCandidates = allCandidates.concat(data.candidates);
                    }

                    const total = data.total_all || 1;
                    const currentOffset = data.offset;
                    const percent = Math.min(100, Math.round((currentOffset / total) * 100));

                    $('#tag-audit-progress-bar').css('width', percent + '%');
                    $('#tag-audit-percent-text').text(percent + '%');
                    $('#tag-audit-status-text').text('Telah memeriksa ' + Math.min(currentOffset, total) + ' dari ' + total + ' tag... (Ditemukan ' + allCandidates.length + ' kandidat tidak sehat)');

                    if (data.is_done) {
                        // Selesai seluruh audit
                        $btn.prop('disabled', false).html(originalText);
                        $('#tag-audit-progress-bar').css('width', '100%');
                        $('#tag-audit-percent-text').text('100%');
                        $('#tag-audit-status-text').html('<span style="color:#10b981;font-weight:700;">✅ Analisa Selesai! Ditemukan ' + allCandidates.length + ' tag yang berisiko merusak SEO.</span>');

                        renderCandidatesTable();
                    } else {
                        // Jeda sejenak untuk rate limit AI
                        setTimeout(function () {
                            runAuditBatch(data.offset);
                        }, 400);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false).html(originalText);
                    $('#tag-audit-status-text').html('<span style="color:#ef4444;">Terjadi kesalahan jaringan atau server timeout.</span>');
                }
            });
        }

        runAuditBatch(0);
    });

    function renderCandidatesTable() {
        const $body = $('#tag-candidates-body');
        $body.empty();
        $('#candidate-count-text').text(allCandidates.length);

        // Update Counter Sub-Tabs
        let countZombie = 0, countLong = 0, countNiche = 0, countGarb = 0;
        $.each(allCandidates, function (_, c) {
            if (c.reason_type === 'zombie') countZombie++;
            else if (c.reason_type === 'longtail') countLong++;
            else if (c.reason_type === 'out_of_niche') countNiche++;
            else if (c.reason_type === 'garbage') countGarb++;
        });

        $('#count-tab-all').text(allCandidates.length);
        $('#count-tab-zombie').text(countZombie);
        $('#count-tab-longtail').text(countLong);
        $('#count-tab-niche').text(countNiche);
        $('#count-tab-garbage').text(countGarb);

        if (allCandidates.length === 0) {
            $('#tag-candidates-container').slideDown(200);
            $body.html('<tr><td colspan="7" style="text-align:center;padding:30px;color:#10b981;font-weight:600;">🎉 Hebat! Seluruh tag di web Anda sehat dan sesuai dengan filter yang dipilih.</td></tr>');
            $('#btn-delete-selected-candidates').hide();
            return;
        }

        $('#btn-delete-selected-candidates').show();
        $.each(allCandidates, function (idx, c) {
            const badgeColor = (c.reason_type === 'zombie') ? '#94a3b8' : ((c.reason_type === 'longtail') ? '#f59e0b' : '#ef4444');
            const copyBadge = c.has_copy 
                ? '<span class="apcai-badge apcai-badge-green" style="font-size:10px;">✅ Ada Copywriting</span>' 
                : '<span class="apcai-badge" style="background:#f1f5f9;color:#94a3b8;font-size:10px;">Belum Ada</span>';

            const rowHtml =
                '<tr class="candidate-row" data-type="' + c.reason_type + '" id="candidate-row-' + c.id + '">' +
                    '<td style="text-align:center;"><input type="checkbox" class="candidate-check" value="' + c.id + '" checked></td>' +
                    '<td><strong style="color:#0f172a;">' + c.name + '</strong><div style="font-size:11px;color:#64748b;">slug: <code>' + c.slug + '</code></div></td>' +
                    '<td><span class="apcai-badge" style="background:#f1f5f9;color:#334155;">' + c.word_count + ' kata</span></td>' +
                    '<td><span class="apcai-badge apcai-badge-blue">' + c.count + ' Post</span></td>' +
                    '<td>' + copyBadge + '</td>' +
                    '<td><span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:11px;background:' + badgeColor + '15;color:' + badgeColor + ';font-weight:600;border:1px solid ' + badgeColor + '40;">' + c.reason_text + '</span></td>' +
                    '<td style="text-align:right;">' +
                        '<button type="button" class="btn-delete-single-candidate apcai-btn apcai-btn-danger" data-id="' + c.id + '" data-name="' + c.name + '" style="padding:2px 8px;font-size:11px;">🗑️ Hapus</button>' +
                    '</td>' +
                '</tr>';
            $body.append(rowHtml);
        });

        applyCandidateTabFilter();
        $('#tag-candidates-container').slideDown(200);
    }

    // Filter Candidate Sub-Tabs (Zombie, Long-Tail, Niche, dll)
    $('.btn-filter-candidate').on('click', function () {
        $('.btn-filter-candidate').removeClass('active');
        $(this).addClass('active');
        candidateActiveTab = $(this).data('type') || 'all';
        applyCandidateTabFilter();
    });

    function applyCandidateTabFilter() {
        if (candidateActiveTab === 'all') {
            $('.candidate-row').show();
        } else {
            $('.candidate-row').hide();
            $('.candidate-row[data-type="' + candidateActiveTab + '"]').show();
        }
    }

    // Master Checkbox Toggle
    $('#master-candidate-check, #check-all-candidates').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('#master-candidate-check, #check-all-candidates').prop('checked', isChecked);
        $('.candidate-check').prop('checked', isChecked);
    });

    // Delete Single Candidate
    $(document).on('click', '.btn-delete-single-candidate', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const tagId = $btn.data('id');
        const tagName = $btn.data('name');

        if (!confirm('Yakin ingin menghapus tag "' + tagName + '"? URL tag ini otomatis dialihkan 301 ke Homepage demi SEO.')) {
            return;
        }

        $btn.prop('disabled', true).text('Menghapus...');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apcai_bulk_delete_tags',
                nonce: nonce,
                tag_ids: [tagId],
            },
            success: function (res) {
                if (res.success) {
                    $('#candidate-row-' + tagId).fadeOut(200, function () { $(this).remove(); });
                    $('#tag-row-' + tagId).fadeOut(200, function () { $(this).remove(); });
                    const delCount = parseInt($('#stat-deleted-redirects').text()) || 0;
                    $('#stat-deleted-redirects').text(delCount + 1);
                    const totalCount = parseInt($('#stat-total-tags').text()) || 0;
                    $('#stat-total-tags').text(Math.max(0, totalCount - 1));
                } else {
                    alert(res.data || 'Gagal menghapus tag.');
                    $btn.prop('disabled', false).text('🗑️ Hapus');
                }
            },
            error: function () {
                alert('Terjadi kesalahan jaringan.');
                $btn.prop('disabled', false).text('🗑️ Hapus');
            }
        });
    });

    // Bulk Delete Selected Candidates
    $('#btn-delete-selected-candidates').on('click', function (e) {
        e.preventDefault();
        const selectedIds = [];
        $('.candidate-check:checked').each(function () {
            selectedIds.push($(this).val());
        });

        if (selectedIds.length === 0) {
            alert('Pilih minimal 1 tag untuk dihapus.');
            return;
        }

        if (!confirm('Peringatan: Anda akan menghapus ' + selectedIds.length + ' tag terpilih.\nSemua tag ini otomatis di-redirect 301 ke Homepage agar tidak ada link error 404 di Google. Lanjutkan?')) {
            return;
        }

        const $btn = $(this);
        const originalText = $btn.html();
        $btn.prop('disabled', true).html('<span class="apcai-spinner"></span> Menghapus & Menyetel 301 Redirect...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apcai_bulk_delete_tags',
                nonce: nonce,
                tag_ids: selectedIds,
            },
            success: function (res) {
                $btn.prop('disabled', false).html(originalText);
                if (res.success && res.data) {
                    alert(res.data.message);
                    $.each(selectedIds, function (_, id) {
                        $('#candidate-row-' + id).remove();
                        $('#tag-row-' + id).remove();
                    });
                    const delCount = parseInt($('#stat-deleted-redirects').text()) || 0;
                    $('#stat-deleted-redirects').text(delCount + res.data.deleted_count);
                    const totalCount = parseInt($('#stat-total-tags').text()) || 0;
                    $('#stat-total-tags').text(Math.max(0, totalCount - res.data.deleted_count));
                    loadTagsList(currentTagPage);
                } else {
                    alert(res.data || 'Gagal menghapus tag.');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html(originalText);
                alert('Terjadi kesalahan jaringan saat menghapus tag.');
            }
        });
    });

    // Toggle Tag Schedule (Background Cron)
    $('#toggle_tag_schedule, #tag_schedule_daily_count, #tag_schedule_execution_mode, #bulk_tag_storage').on('change', function () {
        const isChecked = $('#toggle_tag_schedule').is(':checked') ? 1 : 0;
        const lang       = $('#bulk_tag_lang').val() || 'id';
        const length     = $('#bulk_tag_length').val() || 'medium';
        const funnel     = $('#bulk_tag_funnel').val() || 'mofu';
        const storage    = $('#bulk_tag_storage').val() || 'both';
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
            storage_target: storage,
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
        const lang    = $('#bulk_tag_lang').val() || 'id';
        const length  = $('#bulk_tag_length').val() || 'medium';
        const funnel  = $('#bulk_tag_funnel').val() || 'mofu';
        const storage = $('#bulk_tag_storage').val() || 'both';

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
                storage_target: storage,
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
        const lang    = $('#bulk_tag_lang').val() || 'id';
        const length  = $('#bulk_tag_length').val() || 'medium';
        const funnel  = $('#bulk_tag_funnel').val() || 'mofu';
        const storage = $('#bulk_tag_storage').val() || 'both';

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
                storage_target: storage,
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

        const lang    = $('#apcai_tag_lang').val() || 'id';
        const length  = $('#apcai_tag_length').val() || 'medium';
        const funnel  = $('#apcai_tag_funnel').val() || 'mofu';
        const storage = $('#apcai_tag_storage_target').val() || 'both';

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
                storage_target: storage,
            },
            success: function (res) {
                $btn.html(originalText).prop('disabled', false);
                if (res.success && res.data) {
                    const st = res.data.storage_target || storage;

                    // Update textarea AI Hub
                    if (st === 'hub' || st === 'both') {
                        $('#apcai_tag_copywriting').val(res.data.content);
                    } else {
                        $('#apcai_tag_copywriting').val('');
                    }
                    
                    // 1. Update ke field textarea asli WordPress 'description'
                    let cleanText = res.data.content;
                    const $desc = $('#description');
                    if (st === 'default' || st === 'both') {
                        if ($desc.length) {
                            $desc.val(cleanText).trigger('input').trigger('change');
                        }
                        if (typeof tinyMCE !== 'undefined' && tinyMCE.get('description')) {
                            tinyMCE.get('description').setContent(cleanText);
                            tinyMCE.get('description').fire('change');
                            tinyMCE.get('description').fire('input');
                        }
                    }

                    // 2. Set Focus Keyphrase Yoast SEO di form jika kosong
                    const $yoastInput = $('input[name*="focuskw"], #yoast_wpseo_focuskw, input#focus-keyword-input-metabox');
                    if ($yoastInput.length) {
                        $yoastInput.val(tagName).trigger('input').trigger('change');
                    }

                    // 3. Integrasi Yoast SEO Plugin Mod Plugin JS Bridge (Yoast.App / YoastSEO)
                    if (typeof YoastSEO !== 'undefined' && YoastSEO.app && YoastSEO.app.pluginReloaded) {
                        try {
                            YoastSEO.app.pluginReloaded();
                        } catch (err) {}
                    }
                    if (window.YoastSEO && window.YoastSEO.app && typeof window.YoastSEO.app.refresh === 'function') {
                        try {
                            window.YoastSEO.app.refresh();
                        } catch (err) {}
                    }

                    const targetLabel = (st === 'default') ? 'Deskripsi WordPress Saja' : ((st === 'hub') ? 'AI Hub (Bawah Loop) Saja' : 'Dual Sinkron (Deskripsi + AI Hub)');
                    $status.html('<span style="color:#10b981;font-weight:600;">✅ Selesai! ' + res.data.word_count + ' kata tersimpan ke ' + targetLabel + ' & Yoast SEO terdeteksi.</span>');
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
