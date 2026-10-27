jQuery(document).ready(function ($) {
    const ajaxurl = apcai_vars.ajax_url;
    const nonce = apcai_vars.nonce;

    function showNotice(msg, type = 'success') {
        const bg = type === 'success' ? '#10b981' : '#ef4444';
        const $toast = $('<div style="position:fixed;top:40px;right:30px;background:' + bg + ';color:#fff;padding:12px 20px;border-radius:6px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);z-index:999999;font-weight:600;font-size:13px;">' + msg + '</div>');
        $('body').append($toast);
        setTimeout(function () {
            $toast.fadeOut(300, function () { $(this).remove(); });
        }, 3500);
    }

    // Save Settings
    $('#apcai-settings-form').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const originalText = $btn.html();
        $btn.html('<span class="apcai-spinner"></span> Menyimpan...').prop('disabled', true);

        const formData = $(this).serializeArray();
        const settings = {};
        $.each(formData, function (_, field) {
            settings[field.name] = field.value;
        });

        settings['schedule_enabled'] = $('#schedule_enabled').is(':checked') ? 1 : 0;
        settings['enable_internal_links'] = $('#enable_internal_links').is(':checked') ? 1 : 0;
        settings['enable_instant_indexing'] = $('input[name="enable_instant_indexing"]').is(':checked') ? 1 : 0;

        $.post(ajaxurl, {
            action: 'apcai_save_settings',
            nonce: nonce,
            settings: settings
        }, function (res) {
            $btn.html(originalText).prop('disabled', false);
            if (res.success) {
                showNotice(res.data, 'success');
            } else {
                showNotice(res.data || 'Gagal menyimpan', 'error');
            }
        }).fail(function () {
            $btn.html(originalText).prop('disabled', false);
            showNotice('Terjadi kesalahan koneksi.', 'error');
        });
    });

    // Test API Connection
    $('#btn-test-api').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        $btn.html('<span class="apcai-spinner"></span> Memeriksa...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'apcai_test_api',
            nonce: nonce
        }, function (res) {
            $btn.html('🔌 Test Koneksi 9Router').prop('disabled', false);
            if (res.success && res.data) {
                const count = res.data.count || 0;
                const reply = res.data.reply ? ' | Test Chat: "' + res.data.reply + '"' : '';
                showNotice('✅ Terhubung! ' + count + ' Model AI terdeteksi' + reply, 'success');
            } else {
                showNotice('❌ ' + (res.data || 'Gagal tersambung.'), 'error');
            }
        }).fail(function () {
            $btn.html('🔌 Test Koneksi 9Router').prop('disabled', false);
            showNotice('Gagal menghubungkan server.', 'error');
        });
    });

    // Add Target Landing Page (Money Page)
    $('#form-add-landing-page').on('submit', function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true);

        const payload = {
            action: 'apcai_save_landing_page',
            nonce: nonce,
            category_id: $('#lp_category_id').val(),
            page_title: $('#lp_title').val(),
            page_url: $('#lp_url').val(),
            anchor_keywords: $('#lp_anchors').val(),
            cta_button_text: $('#lp_btn_text').val(),
        };

        $.post(ajaxurl, payload, function (res) {
            $btn.prop('disabled', false);
            if (res.success) {
                showNotice(res.data, 'success');
                setTimeout(function () { location.reload(); }, 1000);
            } else {
                showNotice(res.data || 'Gagal menambahkan Landing Page.', 'error');
            }
        }).fail(function () {
            $btn.prop('disabled', false);
            showNotice('Error koneksi server.', 'error');
        });
    });

    // Delete Landing Page
    $('.btn-delete-lp').on('click', function (e) {
        e.preventDefault();
        if (!confirm('Hapus Target Landing Page ini dari sistem link booster?')) return;
        const $btn = $(this);
        const id = $btn.data('id');
        const $row = $btn.closest('tr');

        $.post(ajaxurl, {
            action: 'apcai_delete_landing_page',
            nonce: nonce,
            lp_id: id
        }, function (res) {
            if (res.success) {
                $row.fadeOut(300, function () { $(this).remove(); });
                showNotice('Landing page berhasil dihapus.', 'success');
            } else {
                showNotice('Gagal menghapus.', 'error');
            }
        });
    });

    // Brainstorm Topics (Category & Funnel Aware)
    $('#btn-brainstorm-topics').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const count = $('#topics-count').val() || 5;
        const category_id = $('#topics-category').val() || 0;
        const funnel = $('#topics-funnel-filter').val() || '';

        $btn.html('<span class="apcai-spinner"></span> Menjalankan Riset AI...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'apcai_brainstorm_topics',
            nonce: nonce,
            count: count,
            category_id: category_id,
            funnel_filter: funnel
        }, function (res) {
            $btn.html('⚡ Riset Topik Funneling').prop('disabled', false);
            if (res.success) {
                if (res.data.created > 0) {
                    showNotice('Berhasil meriset ' + res.data.created + ' topik baru untuk kategori: ' + res.data.category_name, 'success');
                    setTimeout(function () { location.reload(); }, 1200);
                } else if (res.data.duplicate_count > 0) {
                    showNotice('AI menghasilkan ' + res.data.total_items + ' ide, namun ' + res.data.duplicate_count + ' sudah pernah ada di database. Silakan coba lagi atau naikkan jumlah ide.', 'error');
                } else {
                    showNotice('AI tidak menghasilkan topik baru yang valid. Coba ulangi dengan kategori lain.', 'error');
                }
            } else {
                showNotice('Gagal: ' + (res.data || 'Respon AI tidak valid.'), 'error');
            }
        }).fail(function () {
            $btn.html('⚡ Riset Topik Funneling').prop('disabled', false);
            showNotice('Error server saat riset AI.', 'error');
        });
    });

    // Delete Topic
    $('.btn-delete-topic').on('click', function (e) {
        e.preventDefault();
        if (!confirm('Hapus topik ini?')) return;
        const $btn = $(this);
        const id = $btn.data('id');
        const $row = $btn.closest('tr');

        $.post(ajaxurl, {
            action: 'apcai_delete_topic',
            nonce: nonce,
            topic_id: id
        }, function (res) {
            if (res.success) {
                $row.fadeOut(300, function () { $(this).remove(); });
            } else {
                showNotice('Gagal menghapus.', 'error');
            }
        });
    });

    // Trigger Manual Cron Execution
    $('#btn-trigger-cron').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        $btn.html('<span class="apcai-spinner"></span> Memproses Posting Silo...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'apcai_trigger_cron_now',
            nonce: nonce
        }, function (res) {
            $btn.html('🚀 Jalankan Posting Sekarang (Manual Trigger)').prop('disabled', false);
            if (res.success) {
                showNotice(res.data, 'success');
            } else {
                showNotice(res.data || 'Gagal eksekusi posting.', 'error');
            }
        }).fail(function () {
            $btn.html('🚀 Jalankan Posting Sekarang (Manual Trigger)').prop('disabled', false);
            showNotice('Terjadi kesalahan koneksi.', 'error');
        });
    });

    // Studio: Generate State
    let generatedIdData = null;
    let generatedEnData = null;

    $('#btn-studio-generate-id').on('click', function (e) {
        e.preventDefault();
        const topic = $('#studio-topic').val().trim();
        if (!topic) {
            showNotice('Masukkan topik atau paragraf awal terlebih dahulu.', 'error');
            return;
        }

        const $btn = $(this);
        $btn.html('<span class="apcai-spinner"></span> Menulis Artikel Silo & Injeksi Link...').prop('disabled', true);
        $('#studio-loading').show();
        $('#studio-results-area').hide();

        const payload = {
            action: 'apcai_generate_now',
            nonce: nonce,
            topic: topic,
            category_id: $('#studio-category').val(),
            funnel_stage: $('#studio-funnel').val(),
            keyword_prompt: $('#studio-kw-prompt').val(),
            content_prompt: $('#studio-content-prompt').val(),
            cta_type: $('#studio-cta-type').val(),
            cta_text: $('#studio-cta-text').val(),
            cta_target: $('#studio-cta-target').val(),
            cta_instruction: $('#studio-cta-instruction').val(),
        };

        $.post(ajaxurl, payload, function (res) {
            $btn.html('🚀 Generate Artikel Sekarang').prop('disabled', false);
            $('#studio-loading').hide();

            if (res.success && res.data.result) {
                generatedIdData = res.data.result;
                generatedEnData = null;
                renderStudioView('id');
                $('#studio-results-area').fadeIn();
                showNotice('Artikel Silo berhasil dibuat dengan internal link otomatis!', 'success');
            } else {
                showNotice(res.data || 'Gagal membuat artikel.', 'error');
            }
        }).fail(function () {
            $btn.html('🚀 Generate Artikel Sekarang').prop('disabled', false);
            $('#studio-loading').hide();
            showNotice('Koneksi timeout atau gagal respon.', 'error');
        });
    });

    // Studio: Generate EN Translation
    $('#btn-studio-translate-en').on('click', function (e) {
        e.preventDefault();
        if (!generatedIdData) {
            showNotice('Generate artikel ID terlebih dahulu.', 'error');
            return;
        }

        const $btn = $(this);
        $btn.html('<span class="apcai-spinner"></span> Menerjemahkan & Adaptasi EN...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'apcai_translate_en_now',
            nonce: nonce,
            title: generatedIdData.title,
            content_md: generatedIdData.content_md,
            excerpt: generatedIdData.excerpt,
            keywords: generatedIdData.keywords
        }, function (res) {
            $btn.html('🌐 Terjemahkan & Adaptasi ke English (EN)').prop('disabled', false);
            if (res.success && res.data.result) {
                generatedEnData = res.data.result;
                $('.apcai-tab[data-lang="en"]').show();
                renderStudioView('en');
                showNotice('Versi Bahasa Inggris berhasil diadaptasi!', 'success');
            } else {
                showNotice(res.data || 'Gagal adaptasi ke EN.', 'error');
            }
        }).fail(function () {
            $btn.html('🌐 Terjemahkan & Adaptasi ke English (EN)').prop('disabled', false);
            showNotice('Koneksi gagal.', 'error');
        });
    });

    // Tab Switcher Studio
    $('.apcai-tab').on('click', function () {
        $('.apcai-tab').removeClass('active');
        $(this).addClass('active');
        const lang = $(this).data('lang');
        renderStudioView(lang);
    });

    function renderStudioView(lang) {
        const data = (lang === 'en') ? generatedEnData : generatedIdData;
        if (!data) return;

        $('#view-title').text(data.title || '-');
        $('#view-seo-title').text(data.seo_title || '-');
        $('#view-seo-desc').text(data.seo_description || '-');
        $('#view-excerpt').text(data.excerpt || '-');
        
        let kwHtml = '';
        if (data.keywords && data.keywords.length) {
            $.each(data.keywords, function (_, k) {
                kwHtml += '<span class="apcai-badge apcai-badge-blue" style="margin-right:6px;">' + k + '</span>';
            });
        }
        $('#view-keywords').html(kwHtml || '-');
        $('#view-content').text(data.content_md || '');
    }

    // Publish Handlers
    $('#btn-publish-both').on('click', function () {
        publishArticle('both');
    });

    $('#btn-publish-primary').on('click', function () {
        publishArticle('id_only');
    });

    $('#btn-save-draft').on('click', function () {
        publishArticle('draft');
    });

    function publishArticle(mode) {
        if (!generatedIdData) return;

        const $btn = $('#btn-publish-both');
        $btn.prop('disabled', true);

        $.post(ajaxurl, {
            action: 'apcai_publish_article',
            nonce: nonce,
            id_data: generatedIdData,
            en_data: generatedEnData,
            publish_mode: mode,
            category_id: $('#studio-category').val(),
            funnel_stage: $('#studio-funnel').val(),
        }, function (res) {
            $btn.prop('disabled', false);
            if (res.success) {
                showNotice('🎉 Artikel berhasil dipublikasikan!', 'success');
                window.open(res.data.view_url, '_blank');
            } else {
                showNotice(res.data || 'Gagal mempublikasikan post.', 'error');
            }
        }).fail(function () {
            $btn.prop('disabled', false);
            showNotice('Error koneksi WordPress.', 'error');
        });
    }

    // Check GitHub Update Manual Trigger
    $('#btn-check-github-update').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const $status = $('#update-check-status');
        $btn.html('<span class="apcai-spinner"></span> Menghubungkan GitHub...').prop('disabled', true);
        $status.html('Sedang memeriksa versi rilis terbaru di GitHub...');

        $.post(ajaxurl, {
            action: 'apcai_check_update_now',
            nonce: nonce
        }, function (res) {
            $btn.html('🔄 Cek Pembaruan GitHub Sekarang').prop('disabled', false);
            if (res.success && res.data) {
                if (res.data.has_update) {
                    $status.html('<span style="color:#10b981;font-weight:700;">🚀 Tersedia Versi v' + res.data.new_version + '!</span> <a href="' + res.data.update_url + '" class="button button-primary" style="margin-left:8px;">Klik untuk Update Otomatis</a>');
                    showNotice('Versi baru ditemukan: v' + res.data.new_version, 'success');
                } else {
                    $status.html('<span style="color:#10b981;">✅ Plugin sudah memakai versi terbaru (v' + res.data.current + ').</span>');
                    showNotice('Plugin Anda sudah versi paling mutakhir.', 'success');
                }
            } else {
                $status.html('<span style="color:#ef4444;">Gagal memeriksa update: ' + (res.data || 'Error') + '</span>');
                showNotice('Gagal menghubungkan GitHub release.', 'error');
            }
        }).fail(function () {
            $btn.html('🔄 Cek Pembaruan GitHub Sekarang').prop('disabled', false);
            $status.html('<span style="color:#ef4444;">Koneksi ke server gagal.</span>');
            showNotice('Error jaringan saat cek update.', 'error');
        });
    });

    // Gallery Pool Upload Handling
    $('#pool-file-input').on('change', function () {
        const files = this.files;
        if (!files || files.length === 0) return;

        const formData = new FormData();
        formData.append('action', 'apcai_upload_pool_images');
        formData.append('nonce', nonce);
        for (let i = 0; i < files.length; i++) {
            formData.append('files[]', files[i]);
        }

        const $status = $('#pool-upload-status');
        $status.html('<span class="apcai-spinner"></span> Sedang mengompres ' + files.length + ' foto ke WebP... Harap tunggu...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                if (res.success && res.data) {
                    $status.html('✅ Berhasil memproses ' + (res.data.uploaded ? res.data.uploaded.length : 0) + ' foto ke WebP.');
                    showNotice('Foto berhasil diupload dan dikompres!', 'success');
                    setTimeout(function () {
                        location.reload();
                    }, 1200);
                } else {
                    $status.html('❌ ' + (res.data || 'Gagal memproses upload.'));
                    showNotice(res.data || 'Upload gagal.', 'error');
                }
            },
            error: function () {
                $status.html('❌ Terjadi kesalahan jaringan saat upload.');
                showNotice('Koneksi gagal.', 'error');
            }
        });
    });

    // Gallery Pool Delete Handling
    $(document).on('click', '.btn-delete-pool-img', function (e) {
        e.preventDefault();
        if (!confirm('Yakin ingin menghapus foto ini dari galeri pool?')) return;

        const $btn = $(this);
        const attachId = $btn.data('id');
        const $card = $btn.closest('.pool-item-card');

        $btn.text('Menghapus...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'apcai_delete_pool_image',
            nonce: nonce,
            attach_id: attachId
        }, function (res) {
            if (res.success) {
                $card.fadeOut(300, function () { $(this).remove(); });
                $('#pool-count').text(res.data.total_count);
                showNotice('Foto dihapus dari pool.', 'success');
            } else {
                alert(res.data || 'Gagal menghapus gambar.');
                $btn.text('Hapus').prop('disabled', false);
            }
        });
    });
});
