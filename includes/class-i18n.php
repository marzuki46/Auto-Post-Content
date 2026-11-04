<?php
defined('ABSPATH') || exit;

class APCAI_I18n {

    /**
     * Deteksi bahasa aktif pada dashboard WordPress user
     */
    public static function get_locale(): string {
        $locale = function_exists('get_user_locale') ? get_user_locale() : get_locale();
        if (str_starts_with(strtolower($locale), 'id')) {
            return 'id';
        }
        return 'en';
    }

    public static function is_en(): bool {
        return self::get_locale() === 'en';
    }

    /**
     * Terjemahkan string secara instan berdasarkan bahasa dashboard
     */
    public static function t(string $id_text, string $en_text): string {
        return self::is_en() ? $en_text : $id_text;
    }

    public static function e(string $id_text, string $en_text): void {
        echo esc_html(self::t($id_text, $en_text));
    }
}
