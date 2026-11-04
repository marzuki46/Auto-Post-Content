jQuery(document).ready(function ($) {
    'use strict';

    const ajaxurl = (typeof apcai_tag_vars !== 'undefined') ? apcai_tag_vars.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php');
    const nonce   = (typeof apcai_tag_vars !== 'undefined') ? apcai_tag_vars.nonce : '';

    // Generate Tag Copywriting Trigger
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
                    $status.html('<span style="color:#10b981;font-weight:600;">✅ Selesai! ' + res.data.word_count + ' kata tersimpan (Single H1 terjamin).</span>');
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
