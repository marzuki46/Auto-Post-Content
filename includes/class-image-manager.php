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
     * Upload dan proses file gambar lokal (user upload), convert ke WebP, dan simpan ke pool galeri plugin
     */
    public static function process_local_upload(array $file): array|WP_Error {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return new WP_Error('no_file', 'File upload tidak ditemukan.');
        }

        $file_info = @getimagesize($file['tmp_name']);
        if (!$file_info) {
            return new WP_Error('invalid_image', 'File bukan gambar valid.');
        }

        $orig_mime = $file_info['mime'];
        $orig_w    = $file_info[0];
        $orig_h    = $file_info[1];

        $source_gd = null;
        switch ($orig_mime) {
            case 'image/jpeg':
                $source_gd = @imagecreatefromjpeg($file['tmp_name']);
                break;
            case 'image/png':
                $source_gd = @imagecreatefrompng($file['tmp_name']);
                break;
            case 'image/webp':
                if (function_exists('imagecreatefromwebp')) {
                    $source_gd = @imagecreatefromwebp($file['tmp_name']);
                }
                break;
        }

        if (!$source_gd) {
            return new WP_Error('gd_failed', 'Gagal memproses gambar GD.');
        }

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
        if ($orig_mime === 'image/png') {
            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
            imagefilledrectangle($canvas, 0, 0, $target_w, $target_h, $transparent);
        }

        imagecopyresampled($canvas, $source_gd, 0, 0, 0, 0, $target_w, $target_h, $orig_w, $orig_h);

        $wp_upload_dir = wp_upload_dir();
        $base_name = sanitize_title(pathinfo($file['name'], PATHINFO_FILENAME));
        $file_name = 'pool-' . $base_name . '-' . wp_generate_password(6, false, false) . '.webp';
        $target_path = $wp_upload_dir['path'] . '/' . $file_name;

        $saved = false;
        if (function_exists('imagewebp')) {
            $saved = imagewebp($canvas, $target_path, 82);
        }
        if (!$saved) {
            $file_name = 'pool-' . $base_name . '-' . wp_generate_password(6, false, false) . '.jpg';
            $target_path = $wp_upload_dir['path'] . '/' . $file_name;
            $saved = imagejpeg($canvas, $target_path, 82);
        }

        imagedestroy($canvas);
        imagedestroy($source_gd);

        if (!$saved || !file_exists($target_path)) {
            return new WP_Error('save_failed', 'Gagal menyimpan hasil kompresi WebP.');
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $file_type = wp_check_filetype($file_name, null);
        $attachment = [
            'post_mime_type' => $file_type['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', $file['name']),
            'post_content'   => '',
            'post_status'    => 'inherit',
        ];

        $attach_id = wp_insert_attachment($attachment, $target_path);
        if (is_wp_error($attach_id) || !$attach_id) {
            @unlink($target_path);
            return new WP_Error('attach_failed', 'Gagal mendaftarkan attachment di Media Library.');
        }

        $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        update_post_meta($attach_id, '_apcai_pool_image', '1');

        // Simpan ID ke daftar pool gambar plugin
        $pool = get_option('apcai_custom_images_pool', []);
        if (!in_array($attach_id, $pool)) {
            $pool[] = $attach_id;
            update_option('apcai_custom_images_pool', $pool);
        }

        return [
            'id'   => $attach_id,
            'url'  => wp_get_attachment_image_url($attach_id, 'medium'),
            'size' => size_format(filesize($target_path)),
        ];
    }

    /**
     * Ambil 1 gambar dari pool lokal secara rotasi atau acak, pasang sebagai Featured Image, dan update Alt/Title/Caption
     */
    public static function attach_from_local_pool(int $post_id, string $alt_text, string $title, string $caption = ''): bool {
        $pool = get_option('apcai_custom_images_pool', []);
        if (empty($pool)) return false;

        // Ambil attachment ID secara berputar (rotasi berurutan agar merata)
        $last_idx = (int) get_option('apcai_custom_images_index', 0);
        if ($last_idx >= count($pool)) {
            $last_idx = 0;
        }

        $attach_id = $pool[$last_idx] ?? null;
        update_option('apcai_custom_images_index', $last_idx + 1);

        if (!$attach_id || !wp_get_attachment_url($attach_id)) {
            // Bersihkan jika ada attachment yang terhapus dari media library
            $pool = array_values(array_filter($pool, fn($id) => (bool) wp_get_attachment_url($id)));
            update_option('apcai_custom_images_pool', $pool);
            if (empty($pool)) return false;
            $attach_id = $pool[0];
        }

        // Update metadata SEO attachment sesuai postingan ini
        wp_update_post([
            'ID'           => $attach_id,
            'post_title'   => sanitize_text_field($title),
            'post_content' => sanitize_textarea_field($caption),
            'post_excerpt' => sanitize_textarea_field($caption),
        ]);
        if (!empty($alt_text)) {
            update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
        }

        set_post_thumbnail($post_id, $attach_id);
        return true;
    }

    /**
     * Ambil 1 gambar dari WordPress Media Library yang sudah pernah diupload sebelumnya
     * (Mendukung pencarian relevan berdasarkan kata kunci/judul, dengan fallback rotasi acak)
     */
    public static function attach_from_existing_media(int $post_id, string $alt_text, string $title, string $caption = '', string $keyword = ''): bool {
        global $wpdb;

        $attach_id = null;

        // 1. Coba cari media gambar yang judul, caption, atau alt text-nya relevan dengan keyword
        if (!empty($keyword)) {
            $like = '%' . $wpdb->esc_like($keyword) . '%';
            $sql = $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} 
                 WHERE post_type = 'attachment' 
                   AND post_mime_type LIKE 'image/%'
                   AND ID != %d
                   AND (post_title LIKE %s OR post_content LIKE %s OR post_excerpt LIKE %s)
                 ORDER BY RAND() LIMIT 1",
                $post_id,
                $like,
                $like,
                $like
            );
            $attach_id = (int) $wpdb->get_var($sql);
        }

        // 2. Jika tidak ada yang cocok dengan kata kunci, ambil gambar apapun dari Media Library secara acak
        if (empty($attach_id)) {
            $sql = $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} 
                 WHERE post_type = 'attachment' 
                   AND post_mime_type LIKE 'image/%'
                   AND ID != %d
                 ORDER BY RAND() LIMIT 1",
                $post_id
            );
            $attach_id = (int) $wpdb->get_var($sql);
        }

        if (empty($attach_id) || !wp_get_attachment_url($attach_id)) {
            return false;
        }

        // Update metadata SEO attachment sesuai konteks postingan ini jika alt text kosong
        $existing_alt = get_post_meta($attach_id, '_wp_attachment_image_alt', true);
        if (empty($existing_alt) && !empty($alt_text)) {
            update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
        }

        set_post_thumbnail($post_id, $attach_id);
        return true;
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
