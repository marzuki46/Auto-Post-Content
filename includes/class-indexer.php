<?php
defined('ABSPATH') || exit;

class APCAI_Indexer {

    const INDEXNOW_API = 'https://api.indexnow.org/indexnow';
    const GOOGLE_WEBSUB_HUB = 'https://pubsubhubbub.appspot.com/';

    public static function init() {
        // Layani verifikasi IndexNow Key via query param atau rewrite
        add_action('init', [__CLASS__, 'handle_indexnow_key_request']);
        
        // Hook saat artikel di-publish (baik manual maupun otomatis scheduler)
        add_action('publish_post', [__CLASS__, 'on_post_published'], 20, 2);
    }

    /**
     * Dapatkan atau buat IndexNow Key unik untuk domain ini
     */
    public static function get_indexnow_key(): string {
        $key = get_option('apcai_indexnow_key', '');
        if (empty($key)) {
            $key = wp_generate_password(32, false, false);
            update_option('apcai_indexnow_key', $key);
        }
        return $key;
    }

    /**
     * Serve verifikasi {key}.txt untuk validasi IndexNow protocol
     */
    public static function handle_indexnow_key_request() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $key = self::get_indexnow_key();
        
        if (empty($key)) return;

        // Cocokkan URL /{key}.txt
        if (preg_match('#/' . preg_quote($key, '#') . '\.txt$#i', $request_uri)) {
            header('Content-Type: text/plain; charset=utf-8');
            header('X-Robots-Tag: noindex');
            status_header(200);
            echo esc_html($key);
            exit;
        }
    }

    /**
     * Trigger saat post dipublish
     */
    public static function on_post_published($post_id, $post) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($post_id)) return;
        if ($post->post_status !== 'publish') return;

        $settings = get_option('apcai_settings', []);
        $enable_instant_index = $settings['enable_instant_indexing'] ?? '1';
        if ($enable_instant_index !== '1') return;

        // Hindari double ping dalam hitungan detik
        $already_pinged = get_post_meta($post_id, '_apcai_pinged_indexing', true);
        if ($already_pinged && (time() - (int)$already_pinged) < 300) {
            return;
        }

        $permalink = get_permalink($post_id);
        if (!$permalink) return;

        self::ping_all($permalink, $post_id);
    }

    /**
     * Eksekusi ping serentak: IndexNow (Bing, Yandex, Naver) + Google WebSub
     */
    public static function ping_all(string $url, int $post_id = 0): array {
        global $wpdb;
        $table_logs = APCAI_DB::table_logs();

        $results = [
            'indexnow' => self::ping_indexnow($url),
            'websub'   => self::ping_google_websub(),
        ];

        if ($post_id > 0) {
            update_post_meta($post_id, '_apcai_pinged_indexing', time());
        }

        // Catat di log
        $idx_status = $results['indexnow']['success'] ? 'Success' : 'Fail';
        $hub_status = $results['websub']['success'] ? 'Success' : 'Fail';
        $msg = "Instant Indexing Ping: IndexNow ({$idx_status}), Google WebSub ({$hub_status}) untuk URL: {$url}";

        $wpdb->insert($table_logs, [
            'action_type' => 'instant_indexing',
            'status'      => ($results['indexnow']['success'] || $results['websub']['success']) ? 'success' : 'failed',
            'message'     => $msg,
            'data'        => wp_json_encode($results),
            'created_at'  => current_time('mysql'),
        ]);

        return $results;
    }

    /**
     * Ping IndexNow Protocol (Bing, Yandex, Seznam, Naver)
     */
    public static function ping_indexnow(string $url): array {
        $host = wp_parse_url($url, PHP_URL_HOST);
        $key  = self::get_indexnow_key();
        $key_location = home_url("/{$key}.txt");

        $body = [
            'host'        => $host,
            'key'         => $key,
            'keyLocation' => $key_location,
            'urlList'     => [$url],
        ];

        $res = wp_remote_post(self::INDEXNOW_API, [
            'timeout'   => 10,
            'headers'   => ['Content-Type' => 'application/json; charset=utf-8'],
            'body'      => wp_json_encode($body),
            'sslverify' => false,
        ]);

        if (is_wp_error($res)) {
            return [
                'success' => false,
                'code'    => 0,
                'message' => $res->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($res);
        // Code 200 (OK) atau 202 (Accepted) menandakan IndexNow sukses menerima URL
        $success = in_array($code, [200, 202], true);

        return [
            'success' => $success,
            'code'    => $code,
            'message' => wp_remote_retrieve_response_message($res),
        ];
    }

    /**
     * Ping Google WebSub Hub (PubSubHubbub) untuk RSS Feed agar Google langsung crawl
     */
    public static function ping_google_websub(): array {
        $feed_url = get_feed_link('rss2');
        if (empty($feed_url)) {
            $feed_url = home_url('/feed/');
        }

        $body = [
            'hub.mode' => 'publish',
            'hub.url'  => $feed_url,
        ];

        $res = wp_remote_post(self::GOOGLE_WEBSUB_HUB, [
            'timeout'   => 10,
            'body'      => $body,
            'sslverify' => false,
        ]);

        if (is_wp_error($res)) {
            return [
                'success' => false,
                'code'    => 0,
                'message' => $res->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($res);
        // 204 No Content adalah respon sukses standar PubSubHubbub/WebSub
        $success = in_array($code, [200, 204], true);

        return [
            'success' => $success,
            'code'    => $code,
            'message' => wp_remote_retrieve_response_message($res),
        ];
    }
}
