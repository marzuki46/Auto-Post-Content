<?php
defined('ABSPATH') || exit;

class APCAI_I18n {

    /**
     * Bahasa aktif (Default ke English untuk seluruh antarmuka)
     */
    public static function get_locale(): string {
        $settings = get_option('apcai_settings', []);
        $ui_lang  = $settings['admin_ui_lang'] ?? 'en';
        if ($ui_lang === 'id') {
            return 'id';
        }
        return 'en';
    }

    public static function is_en(): bool {
        return self::get_locale() === 'en';
    }

    /**
     * Terjemahkan string secara instan (prioritas English)
     */
    public static function t(string $id_text, string $en_text): string {
        return self::is_en() ? $en_text : $id_text;
    }

    public static function e(string $id_text, string $en_text): void {
        echo esc_html(self::t($id_text, $en_text));
    }
}
