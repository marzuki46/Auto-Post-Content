<?php
defined('ABSPATH') || exit;

class APCAI_Image_Manager {

    /**
     * Cari gambar dari Bing Image Search berdasarkan kata kunci
     */
    public static function search_bing_images(string $keyword, int $limit = 5): array {
        $keyword = trim($keyword);
        if (empty($keyword)) return [];

        // Gunakan parameter filter gambar ukuran besar (imagesize-large) dan wide (aspect-wide)
        $query = urlencode($keyword);
        $url = "https://www.bing.com/images/search?q={$query}&qft=+filterui:imagesize-large+filterui:aspect-wide&form=IRFLTR";

        $response = wp_remote_get($url, [
            'timeout'    => 15,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'headers'    => [
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,id;q=0.8',
            ],
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            // Fallback tanpa filter ukuran ketat jika tidak ditemukan
            $url_fallback = "https://www.bing.com/images/search?q={$query}&form=HDRSC2";
            $response = wp_remote_get($url_fallback, [
                'timeout'    => 15,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            ]);
            if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
                return [];
            }
        }

        $html = wp_remote_retrieve_body($response);
        $images = [];

        // Bing menyematkan URL gambar resolusi tinggi pada atribut `m="{...&quot;murl&quot;:&quot;URL&quot;...}"`
        if (preg_match_all('/murl&quot;:&quot;(https?:\/\/[^&]+)&quot;/i', $html, $matches)) {
            $images = array_unique($matches[1]);
        } elseif (preg_match_all('/mediaurl=(https?[^&]+)/i', $html, $matches)) {
            foreach ($matches[1] as $raw_url) {
                $images[] = urldecode($raw_url);
            }
            $images = array_unique($images);
        }

        // Filter ekstensi gambar valid yang umum
        $valid_images = [];
        foreach ($images as $img_url) {
            $clean_url = strtok($img_url, '?');
            $ext = strtolower(pathinfo($clean_url, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $valid_images[] = $img_url;
            }
            if (count($valid_images) >= $limit) break;
        }

        return $valid_images;
    }

    /**
     * Download gambar, kompres, convert ke WebP, dan attach ke WordPress Media Library
     *
     * @param string $image_url  URL gambar sumber
     * @param int    $post_id    ID post artikel
     * @param string $alt_text   Teks Alt untuk SEO
     * @param string $title      Judul gambar
     * @param string $caption    Caption / Deskripsi gambar
     * @return int|WP_Error      Attachment ID atau WP_Error
     */
    public static function process_and_attach_image(string $image_url, int $post_id, string $alt_text = '', string $title = '', string $caption = '') {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // 1. Download file sementara
        $tmp_file = download_url($image_url, 30);
        if (is_wp_error($tmp_file)) {
            return $tmp_file;
        }

        // Cek ukuran & tipe file
        $file_info = @getimagesize($tmp_file);
        if (!$file_info) {
            @unlink($tmp_file);
            return new WP_Error('invalid_image', 'File yang didownload bukan gambar valid.');
        }

        $orig_mime = $file_info['mime'];
        $orig_w    = $file_info[0];
        $orig_h    = $file_info[1];

        // 2. Load resource GD berdasarkan tipe mime asli
        $source_gd = null;
        switch ($orig_mime) {
            case 'image/jpeg':
                $source_gd = @imagecreatefromjpeg($tmp_file);
                break;
            case 'image/png':
                $source_gd = @imagecreatefrompng($tmp_file);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $source_gd = @imagecreatefromwebp($tmp_file);
                }
                break;
        }

        if (!$source_gd) {
            @unlink($tmp_file);
            return new WP_Error('gd_load_failed', 'Gagal memproses gambar dengan library GD.');
        }

        // 3. Resize cerdas (Maksimal 1200px lebar untuk featured image standar, pertahankan aspek rasio)
        $max_width = 1200;
        $max_height = 800;

        $target_w = $orig_w;
        $target_h = $orig_h;

        if ($orig_w > $max_width || $orig_h > $max_height) {
            $ratio = min($max_width / $orig_w, $max_height / $orig_h);
            $target_w = (int) round($orig_w * $ratio);
            $target_h = (int) round($orig_h * $ratio);
        }

        $canvas = imagecreatetruecolor($target_w, $target_h);

        // Pertahankan transparansi bila sumber PNG
        if ($orig_mime === 'image/png') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $target_w, $target_h, $transparent);
        }

        imagecopyresampled($canvas, $source_gd, 0, 0, 0, 0, $target_w, $target_h, $orig_w, $orig_h);

        // 4. Siapkan direktori upload WordPress dan file tujuan .webp
        $wp_upload_dir = wp_upload_dir();
        $base_name = sanitize_title($title ?: 'article-thumbnail-' . $post_id);
        $file_name = $base_name . '-' . wp_generate_password(6, false, false) . '.webp';
        $target_path = $wp_upload_dir['path'] . '/' . $file_name;

        // 5. Simpan sebagai WebP (Kualitas 82 = optimal tajam & ukuran file minimal ~40-80 KB)
        $saved = false;
        if (function_exists('imagewebp')) {
            $saved = imagewebp($canvas, $target_path, 82);
        }

        // Fallback jika imagewebp tidak disupport (simpan jpeg terkompresi)
        if (!$saved) {
            $file_name = $base_name . '-' . wp_generate_password(6, false, false) . '.jpg';
            $target_path = $wp_upload_dir['path'] . '/' . $file_name;
            $saved = imagejpeg($canvas, $target_path, 82);
        }

        // Bersihkan memori GD & file temporary
        imagedestroy($canvas);
        imagedestroy($source_gd);
        @unlink($tmp_file);

        if (!$saved || !file_exists($target_path)) {
            return new WP_Error('save_failed', 'Gagal menyimpan file gambar hasil kompresi WebP.');
        }

        // 6. Masukkan gambar ke Media Library WordPress
        $file_type = wp_check_filetype($file_name, null);
        $attachment = [
            'post_mime_type' => $file_type['type'],
            'post_title'     => sanitize_text_field($title),
            'post_content'   => sanitize_textarea_field($caption),
            'post_excerpt'   => sanitize_textarea_field($caption),
            'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $target_path, $post_id);
        if (is_wp_error($attach_id) || !$attach_id) {
            @unlink($target_path);
            return new WP_Error('attach_failed', 'Gagal mendaftarkan attachment di WordPress.');
        }

        // Generate metadata attachment WordPress
        $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // 7. Simpan Alt Text Image untuk SEO Google
        if (!empty($alt_text)) {
            update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
        }

        // 8. Jadikan Featured Image resmi postingan
        set_post_thumbnail($post_id, $attach_id);

        return $attach_id;
    }

    /**
     * Cari gambar dari Bing dan langsung attach ke artikel post
     */
    public static function attach_bing_featured_image(int $post_id, string $search_query, string $alt_text, string $title, string $caption = '') {
        $urls = self::search_bing_images($search_query, 3);
        if (empty($urls)) {
            return new WP_Error('no_images_found', 'Tidak ditemukan gambar di Bing untuk query: ' . $search_query);
        }

        // Coba URL yang ditemukan satu per satu jika ada yang gagal didownload
        foreach ($urls as $img_url) {
            $result = self::process_and_attach_image($img_url, $post_id, $alt_text, $title, $caption);
            if (!is_wp_error($result)) {
                return $result;
            }
        }

        return new WP_Error('all_images_failed', 'Semua kandidat gambar Bing gagal didownload atau diproses.');
    }
}
