<?php
defined('ABSPATH') || exit;

class APCAI_AI_Client {

    public static function chat($system_prompt, $user_prompt, $model = '', $temperature = 0.7) {
        $settings = get_option('apcai_settings', []);
        $base_url = rtrim($settings['ninerouter_url'] ?? '', '/');
        $api_key  = $settings['ninerouter_key'] ?? '';
        
        if (empty($base_url)) {
            return new WP_Error('missing_url', __('URL Endpoint 9Router belum dikonfigurasi.', 'auto-post-content'));
        }

        if (empty($model)) {
            $model = $settings['model_writer'] ?? 'openai/gpt-4o';
        }

        $endpoint = $base_url . '/v1/chat/completions';

        $messages = [];
        if (!empty($system_prompt)) {
            $messages[] = ['role' => 'system', 'content' => $system_prompt];
        }
        $messages[] = ['role' => 'user', 'content' => $user_prompt];

        $body = [
            'model'       => $model,
            'messages'    => $messages,
            'temperature' => (float)$temperature,
        ];

        $headers = [
            'Content-Type' => 'application/json',
        ];
        if (!empty($api_key)) {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }

        $response = wp_remote_post($endpoint, [
            'headers'     => $headers,
            'body'        => wp_json_encode($body),
            'timeout'     => 180,
            'data_format' => 'body',
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw_body = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            $err_data = json_decode($raw_body, true);
            $detail = '';
            if (isset($err_data['error']['message'])) {
                $detail = $err_data['error']['message'];
            } elseif (isset($err_data['message'])) {
                $detail = $err_data['message'];
            } else {
                $detail = substr($raw_body, 0, 300);
            }
            return new WP_Error('api_http_error', "API Error (HTTP $code) dari 9Router: $detail");
        }

        $json = json_decode($raw_body, true);
        if (!$json) {
            return new WP_Error('invalid_json', 'Format response 9Router tidak valid (bukan JSON): ' . substr($raw_body, 0, 200));
        }

        if (isset($json['error'])) {
            $err_msg = is_array($json['error']) ? ($json['error']['message'] ?? wp_json_encode($json['error'])) : $json['error'];
            return new WP_Error('api_error', "9Router Error: $err_msg");
        }

        if (!isset($json['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_json', 'Respon AI tidak memiliki atribut choices/content. Respon: ' . substr($raw_body, 0, 200));
        }

        return $json['choices'][0]['message']['content'];
    }

    public static function extract_json($raw) {
        $s = trim($raw);
        // Hapus markdown triple backticks (```json ... ``` atau ``` ... ```)
        $s = preg_replace('/^```(?:json)?\s*/i', '', $s);
        $s = preg_replace('/\s*```$/', '', $s);
        $s = trim($s);

        $decoded = json_decode($s, true);
        if ($decoded !== null) {
            return $decoded;
        }

        // Cari kurung siku terluar [ ... ] untuk list/array
        $start_arr = strpos($s, '[');
        $end_arr   = strrpos($s, ']');
        if ($start_arr !== false && $end_arr !== false && $end_arr > $start_arr) {
            $slice = substr($s, $start_arr, $end_arr - $start_arr + 1);
            $decoded = json_decode($slice, true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Cari kurung kurawal terluar { ... } untuk object tunggal
        $start_obj = strpos($s, '{');
        $end_obj   = strrpos($s, '}');
        if ($start_obj !== false && $end_obj !== false && $end_obj > $start_obj) {
            $slice = substr($s, $start_obj, $end_obj - $start_obj + 1);
            $decoded = json_decode($slice, true);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        // Jika JSON memiliki trailing koma atau format tidak sempurna, coba sanitasi
        $cleaned = preg_replace('/,\s*([\]}])/m', '$1', $s);
        $decoded = json_decode($cleaned, true);
        if ($decoded !== null) {
            return $decoded;
        }

        $snippet = substr($raw, 0, 200);
        return new WP_Error('json_parse_failed', "Gagal membaca format JSON dari respon AI. Cuplikan teks: \"$snippet\"");
    }

    public static function test_connection() {
        $settings = get_option('apcai_settings', []);
        $base_url = rtrim($settings['ninerouter_url'] ?? '', '/');
        $api_key  = $settings['ninerouter_key'] ?? '';

        if (empty($base_url)) {
            return new WP_Error('missing_url', __('URL Endpoint 9Router belum diisi.', 'auto-post-content'));
        }

        $endpoint = $base_url . '/v1/models';
        $headers = [];
        if (!empty($api_key)) {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }

        $response = wp_remote_get($endpoint, [
            'headers' => $headers,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code < 200 || $code >= 300) {
            $err_data = json_decode($body, true);
            $msg = $err_data['error']['message'] ?? $err_data['message'] ?? substr($body, 0, 200);
            return new WP_Error('http_error', "Koneksi gagal (HTTP $code): $msg");
        }

        $data = json_decode($body, true);
        $models = [];
        if (isset($data['data']) && is_array($data['data'])) {
            foreach ($data['data'] as $m) {
                if (isset($m['id'])) {
                    $models[] = $m['id'];
                }
            }
        }

        // Test mini completion untuk memastikan API key dan model benar-benar bisa generate teks
        $writer_model = $settings['model_writer'] ?? 'openai/gpt-4o';
        $test_chat = self::chat('', 'Balas satu kata saja: OK', $writer_model, 0.1);
        $chat_reply = is_wp_error($test_chat) ? ('Peringatan Model Chat: ' . $test_chat->get_error_message()) : trim($test_chat);

        return [
            'models' => $models,
            'reply'  => $chat_reply,
            'count'  => count($models),
        ];
    }
}
