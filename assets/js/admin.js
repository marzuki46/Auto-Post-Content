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

    // Save Settings & Schedule (Form-Aware & Anti-Cache)
    $(document).on('submit', '#apcai-settings-form, #apcai-schedule-form', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');
        const originalText = $btn.html();
        $btn.html('<span class="apcai-spinner"></span> Menyimpan & Purge Cache...').prop('disabled', true);

        const formData = $form.serializeArray();
        const settings = {};
        $.each(formData, function (_, field) {
            settings[field.name] = field.value;
        });

        // HANYA proses input checkbox/switch yang ADA di dalam form ini
        $form.find('input[type="checkbox"]').each(function () {
            const name = $(this).attr('name');
            if (name) {
                settings[name] = $(this).is(':checked') ? '1' : '0';
            }
        });

        $.ajax({
            url: ajaxurl + '?_t=' + Date.now(),
            type: 'POST',
            cache: false,
            data: {
                action: 'apcai_save_settings',
                nonce: nonce,
                settings: settings,
                _nocache: Date.now()
            },
            success: function (res) {
                $btn.html(originalText).prop('disabled', false);
                if (res.success) {
                    showNotice(res.data, 'success');
                } else {
                    showNotice(res.data || 'Gagal menyimpan', 'error');
                }
            },
            error: function () {
                $btn.html(originalText).prop('disabled', false);
                showNotice('Terjadi kesalahan koneksi atau server timeout.', 'error');
            }
        });
    });

    // Toggle AI Provider View
    $('input[name="ai_provider"]').on('change', function () {
        const val = $(this).val();
        if (val === 'gemini') {
            $('#provider-gemini-fields').slideDown(200);
            $('#provider-ninerouter-fields').slideUp(200);
            $('#help-model-writer').html('Pilihan Gemini: <code>gemini-2.5-pro</code>, <code>gemini-2.0-flash</code>, <code>gemini-1.5-pro</code>');
            $('#help-model-planner').html('Pilihan Gemini: <code>gemini-2.5-flash</code>, <code>gemini-2.0-flash</code>, <code>gemini-1.5-flash</code>');
            if ($('#setting_model_writer').val().includes('gpt-4o')) {
                $('#setting_model_writer').val('gemini-2.0-flash');
            }
            if ($('#setting_model_planner').val().includes('gpt-4o')) {
                $('#setting_model_planner').val('gemini-2.5-flash');
            }
        } else {
            $('#provider-gemini-fields').slideUp(200);
            $('#provider-ninerouter-fields').slideDown(200);
            $('#help-model-writer').html('Pilihan: <code>openai/gpt-4o</code>, <code>claude-3-5-sonnet</code>, <code>google/gemini-2.5-pro</code>');
            $('#help-model-planner').html('Pilihan: <code>openai/gpt-4o-mini</code>, <code>claude-3-5-haiku</code>');
            if ($('#setting_model_writer').val().startsWith('gemini-')) {
                $('#setting_model_writer').val('openai/gpt-4o');
            }
            if ($('#setting_model_planner').val().startsWith('gemini-')) {
                $('#setting_model_planner').val('openai/gpt-4o-mini');
            }
        }
    });

    // Test API Connection
    $('#btn-test-api').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        $btn.html('<span class="apcai-spinner"></span> Memeriksa Koneksi API...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'apcai_test_api',
            nonce: nonce
        }, function (res) {
            $btn.html('🔌 Uji Koneksi API Sekarang').prop('disabled', false);
            if (res.success && res.data) {
                const count = res.data.count || 0;
                const reply = res.data.reply ? ' | Balasan Model: "' + res.data.reply + '"' : '';
                const provider = res.data.provider || 'AI Engine';
                showNotice('✅ Berhasil Terhubung ke ' + provider + '! ' + count + ' model terdeteksi' + reply, 'success');
            } else {
                showNotice('❌ ' + (res.data || 'Gagal tersambung ke provider AI.'), 'error');
            }
        }).fail(function () {
            $btn.html('🔌 Uji Koneksi API Sekarang').prop('disabled', false);
            showNotice('Gagal menghubungkan server API.', 'error');
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

    // Preset Word Count Buttons
    $(document).on('click', '.btn-preset-words', function (e) {
        e.preventDefault();
        const min = $(this).data('min');
        const max = $(this).data('max');
        $('#word_count_min').val(min);
        $('#word_count_max').val(max);
        showNotice('Rentang kata diset ke ' + min + ' - ' + max + ' kata.', 'success');
    });

    // Studio: Generate State
    let generatedIdData = null;
    let generatedEnData = null;
    let generatedMsData = null;

    // Tahap 1: Buat Outline Terlebih Dahulu
    $('#btn-studio-plan-outline').on('click', function (e) {
        e.preventDefault();
        const topic = $('#studio-topic').val().trim();
        if (!topic) {
            showNotice('Masukkan topik atau paragraf awal terlebih dahulu.', 'error');
            return;
        }

        const $btn = $(this);
        $btn.html('<span class="apcai-spinner"></span> Merancang Blueprint Outline...').prop('disabled', true);

        const payload = {
            action: 'apcai_generate_outline_now',
            nonce: nonce,
            topic: topic,
            category_id: $('#studio-category').val(),
            funnel_stage: $('#studio-funnel').val(),
        };

        $.post(ajaxurl, payload, function (res) {
            $btn.html('1. 📝 Buat Outline Dulu').prop('disabled', false);

            if (res.success && res.data.formatted_text) {
                $('#studio-custom-outline').val(res.data.formatted_text);
                $('#studio-outline-wrapper').slideDown();
                showNotice('Blueprint Outline berhasil dirancang! Silakan tinjau/edit, lalu klik "2. Tulis Konten Penuh".', 'success');
            } else {
                showNotice(res.data || 'Gagal merancang outline.', 'error');
            }
        }).fail(function () {
            $btn.html('1. 📝 Buat Outline Dulu').prop('disabled', false);
            showNotice('Koneksi timeout atau gagal merespons.', 'error');
        });
    });

    // Tahap 2: Eksekusi Penulisan Konten Penuh dari Outline
    $('#btn-studio-generate-id').on('click', function (e) {
        e.preventDefault();
        const topic = $('#studio-topic').val().trim();
        if (!topic) {
            showNotice('Masukkan topik atau paragraf awal terlebih dahulu.', 'error');
            return;
        }

        const customOutline = $('#studio-custom-outline').val().trim();
        const $btn = $(this);
        $btn.html('<span class="apcai-spinner"></span> Mengembangkan Outline & Injeksi Link...').prop('disabled', true);
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
            custom_outline: customOutline,
            cta_type: $('#studio-cta-type').val(),
            cta_text: $('#studio-cta-text').val(),
            cta_target: $('#studio-cta-target').val(),
            cta_instruction: $('#studio-cta-instruction').val(),
        };

        $.post(ajaxurl, payload, function (res) {
            $btn.html('2. 🚀 Tulis Konten Penuh').prop('disabled', false);
            $('#studio-loading').hide();

            if (res.success && res.data.result) {
                generatedIdData = res.data.result;
                generatedEnData = null;
                generatedMsData = null;
                $('.apcai-tab[data-lang="en"], .apcai-tab[data-lang="ms"]').hide();
                $('.apcai-tab[data-lang="id"]').addClass('active');
                renderStudioView('id');
                $('#studio-results-area').fadeIn();
                showNotice('Artikel berhasil dikembangkan dari outline dengan tautan silo!', 'success');
            } else {
                showNotice(res.data || 'Gagal membuat artikel.', 'error');
            }
        }).fail(function () {
            $btn.html('2. 🚀 Tulis Konten Penuh').prop('disabled', false);
            $('#studio-loading').hide();
            showNotice('Koneksi timeout atau gagal respon.', 'error');
        });
    });

    // Studio: Generate MS Translation
    $('#btn-studio-translate-ms').on('click', function (e) {
        e.preventDefault();
        if (!generatedIdData) {
            showNotice('Generate artikel utama terlebih dahulu.', 'error');
            return;
        }

        const $btn = $(this);
        $btn.html('<span class="apcai-spinner"></span> Mengadaptasi Bahasa Melayu...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'apcai_translate_ms_now',
            nonce: nonce,
            title: generatedIdData.title,
            content_md: generatedIdData.content_md,
            excerpt: generatedIdData.excerpt,
            keywords: generatedIdData.keywords
        }, function (res) {
            $btn.html('🇲🇾 Adaptasi ke Bahasa Melayu (MS)').prop('disabled', false);
            if (res.success && res.data.result) {
                generatedMsData = res.data.result;
                $('.apcai-tab[data-lang="ms"]').show().click();
                showNotice('Versi Bahasa Melayu berhasil diadaptasi!', 'success');
            } else {
                showNotice(res.data || 'Gagal adaptasi ke Bahasa Melayu.', 'error');
            }
        }).fail(function () {
            $btn.html('🇲🇾 Adaptasi ke Bahasa Melayu (MS)').prop('disabled', false);
            showNotice('Koneksi gagal.', 'error');
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
                $('.apcai-tab[data-lang="en"]').show().click();
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
        let data = generatedIdData;
        if (lang === 'en') data = generatedEnData;
        if (lang === 'ms') data = generatedMsData;
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
            ms_data: generatedMsData,
            primary_lang: $('#studio-primary-lang').val() || 'id',
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

    // Check Cloud Update Manual Trigger
    $('#btn-check-github-update').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const $status = $('#update-check-status');
        $btn.html('<span class="apcai-spinner"></span> Menghubungkan Server Cloud...').prop('disabled', true);
        $status.html('Sedang memeriksa versi rilis terbaru di Cloud Server...');

        $.post(ajaxurl, {
            action: 'apcai_check_update_now',
            nonce: nonce
        }, function (res) {
            $btn.html('🔄 Periksa Pembaruan Sistem Sekarang').prop('disabled', false);
            if (res.success && res.data) {
                if (res.data.has_update) {
                    $status.html('<span style="color:#10b981;font-weight:700;">🚀 Pembaruan Tersedia: v' + res.data.new_version + '!</span> <a href="' + res.data.update_url + '" class="button button-primary" style="margin-left:8px;">Perbarui Sistem Sekarang</a>');
                    showNotice('Pembaruan baru tersedia: v' + res.data.new_version, 'success');
                } else {
                    $status.html('<span style="color:#10b981;">✅ Sistem Anda sudah menggunakan versi paling mutakhir (v' + res.data.current + ').</span>');
                    showNotice('Sistem Anda sudah dalam kondisi mutakhir.', 'success');
                }
            } else {
                $status.html('<span style="color:#ef4444;">Gagal memeriksa update: ' + (res.data || 'Error') + '</span>');
                showNotice('Gagal menghubungkan server rilis.', 'error');
            }
        }).fail(function () {
            $btn.html('🔄 Periksa Pembaruan Sistem Sekarang').prop('disabled', false);
            $status.html('<span style="color:#ef4444;">Koneksi ke server cloud gagal.</span>');
            showNotice('Error jaringan saat mengecek pembaruan.', 'error');
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

    // ----------------------------------------------------
    // SEO Focus Keyphrase Auto-Injector (Backfill Tool)
    // ----------------------------------------------------
    let missingQueue = [];
    let missingTotal = 0;
    let missingProcessed = 0;
    let isFixingKeywords = false;

    $('#btn-scan-missing-keywords').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const $startBtn = $('#btn-start-fix-keywords');
        const $summary = $('#missing-kw-scan-summary');
        const $progressWrapper = $('#missing-kw-progress-wrapper');

        $btn.prop('disabled', true).html('<span class="apcai-spinner"></span> Memindai Post & Tag...');
        $startBtn.hide();
        $progressWrapper.hide();
        $summary.show().html('Sedang menganalisis basis data WordPress untuk mencari keyword kosong...');

        $.post(ajaxurl, {
            action: 'apcai_scan_missing_keywords',
            nonce: nonce
        }, function (res) {
            $btn.prop('disabled', false).html('🔍 Pindai Konten Kosong');
            if (res.success && res.data) {
                missingQueue = res.data.items || [];
                missingTotal = missingQueue.length;
                missingProcessed = 0;

                if (missingTotal === 0) {
                    $summary.html('<span style="color:#10b981;font-weight:700;">🎉 Sempurna! Semua postingan dan tag sudah memiliki Focus Keyphrase Yoast / Rank Math.</span>');
                } else {
                    $summary.html(
                        'Ditemukan <strong>' + missingTotal + ' item</strong> yang target keyword-nya kosong (' +
                        res.data.posts_count + ' Postingan, ' + res.data.tags_count + ' Tag). ' +
                        'Klik tombol di samping untuk mulai mengisi dan memperbarui metadata otomatis.'
                    );
                    $startBtn.show().text('⚡ Mulai Isi Semua Keyword (' + missingTotal + ')');
                }
            } else {
                $summary.html('<span style="color:#ef4444;">Gagal memindai: ' + (res.data || 'Error') + '</span>');
            }
        }).fail(function () {
            $btn.prop('disabled', false).html('🔍 Pindai Konten Kosong');
            $summary.html('<span style="color:#ef4444;">Terjadi kesalahan jaringan atau server timeout.</span>');
        });
    });

    $('#btn-start-fix-keywords').on('click', function (e) {
        e.preventDefault();
        if (isFixingKeywords || missingQueue.length === 0) return;

        isFixingKeywords = true;
        const $startBtn = $(this);
        const $scanBtn = $('#btn-scan-missing-keywords');
        const $progressWrapper = $('#missing-kw-progress-wrapper');
        const $summary = $('#missing-kw-scan-summary');

        $startBtn.prop('disabled', true);
        $scanBtn.prop('disabled', true);
        $progressWrapper.slideDown(200);
        $summary.html('<span style="color:#2563eb;font-weight:600;">Memproses input keyword & memperbarui postingan... Jangan tutup halaman ini.</span>');

        processNextMissingKeywordItem();
    });

    function processNextMissingKeywordItem() {
        if (!isFixingKeywords) return;

        if (missingQueue.length === 0) {
            isFixingKeywords = false;
            $('#missing-kw-progress-bar').css('width', '100%');
            $('#missing-kw-percent-text').text('100%');
            $('#missing-kw-current-text').text('Selesai!');
            $('#btn-scan-missing-keywords').prop('disabled', false);
            $('#btn-start-fix-keywords').hide().prop('disabled', false);
            $('#missing-kw-scan-summary').html(
                '<span style="color:#10b981;font-weight:700;">🎉 Berhasil! Sebanyak ' + missingProcessed + ' postingan & tag telah diisi Focus Keyphrase Yoast & Rank Math dan diperbarui.</span>'
            );
            if (typeof showNotice === 'function') {
                showNotice('Berhasil memperbarui semua target keyword SEO!', 'success');
            }
            return;
        }

        const item = missingQueue.shift();
        const percent = Math.round((missingProcessed / missingTotal) * 100);
        $('#missing-kw-progress-bar').css('width', percent + '%');
        $('#missing-kw-percent-text').text(percent + '%');
        $('#missing-kw-current-text').html(
            '[' + item.type.toUpperCase() + '] Memproses: <strong>\"' + item.title + '\"</strong> (' + (missingProcessed + 1) + '/' + missingTotal + ')...'
        );

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'apcai_fix_single_keyword',
                nonce: nonce,
                item_type: item.type,
                item_id: item.id
            },
            success: function (res) {
                missingProcessed++;
                processNextMissingKeywordItem();
            },
            error: function () {
                missingProcessed++;
                processNextMissingKeywordItem();
            }
        });
    }
});
