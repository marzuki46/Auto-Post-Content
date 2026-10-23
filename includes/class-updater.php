<?php
defined('ABSPATH') || exit;

class APCAI_Updater {

    private string $file;
    private string $plugin_slug;
    private string $version;
    private string $repo; // format: "owner/repo"

    public function __construct(string $file, string $repo) {
        $this->file = $file;
        $this->plugin_slug = plugin_basename($file);
        $this->version = APCAI_VERSION;
        $this->repo = $repo;

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_update']);
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('upgrader_post_install', [$this, 'post_install'], 10, 3);
    }

    public static function init(): void {
        $settings = get_option('apcai_settings', []);
        $repo = !empty($settings['github_repo']) ? trim($settings['github_repo']) : 'marzuki46/Auto-Post-Content';
        new self(APCAI_PLUGIN_FILE, $repo);
    }

    private function get_github_release(): ?array {
        $settings = get_option('apcai_settings', []);
        $token = !empty($settings['github_token']) ? trim($settings['github_token']) : '';

        $headers = [
            'Accept'     => 'application/vnd.github.v3+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
        ];
        if (!empty($token)) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        // 1. Coba ambil dari GitHub Releases (/releases/latest)
        $url_release = "https://api.github.com/repos/{$this->repo}/releases/latest";
        $response = wp_remote_get($url_release, [
            'headers' => $headers,
            'timeout' => 15,
        ]);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (is_array($body) && !empty($body['tag_name'])) {
                return $body;
            }
        }

        // 2. Fallback: Jika belum ada formal Release (hanya Git Tag biasa), ambil dari /tags
        $url_tags = "https://api.github.com/repos/{$this->repo}/tags";
        $response_tags = wp_remote_get($url_tags, [
            'headers' => $headers,
            'timeout' => 15,
        ]);

        if (!is_wp_error($response_tags) && wp_remote_retrieve_response_code($response_tags) === 200) {
            $tags = json_decode(wp_remote_retrieve_body($response_tags), true);
            if (is_array($tags) && !empty($tags[0]['name'])) {
                $latest_tag = $tags[0];
                return [
                    'tag_name'    => $latest_tag['name'],
                    'zipball_url' => $latest_tag['zipball_url'] ?? "https://github.com/{$this->repo}/archive/refs/tags/{$latest_tag['name']}.zip",
                    'html_url'    => "https://github.com/{$this->repo}/releases/tag/{$latest_tag['name']}",
                    'body'        => 'Pembaruan versi dari git tag ' . $latest_tag['name'],
                ];
            }
        }

        return null;
    }

    public function check_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_github_release();
        if (!$release || empty($release['tag_name'])) {
            return $transient;
        }

        $new_version = ltrim($release['tag_name'], 'vV');

        if (version_compare($this->version, $new_version, '<')) {
            $package_url = $release['zipball_url'] ?? '';

            // Cek jika ada asset ZIP terlampir di release
            if (!empty($release['assets']) && is_array($release['assets'])) {
                foreach ($release['assets'] as $asset) {
                    if (isset($asset['name']) && str_ends_with(strtolower($asset['name']), '.zip')) {
                        $package_url = $asset['browser_download_url'] ?? $package_url;
                        break;
                    }
                }
            }

            $obj = new stdClass();
            $obj->slug = dirname($this->plugin_slug);
            $obj->plugin = $this->plugin_slug;
            $obj->new_version = $new_version;
            $obj->url = $release['html_url'] ?? "https://github.com/{$this->repo}";
            $obj->package = $package_url;
            $obj->tested = '6.7';
            $obj->requires_php = '8.3';

            $transient->response[$this->plugin_slug] = $obj;
        }

        return $transient;
    }

    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }

        $slug = dirname($this->plugin_slug);
        if (!isset($args->slug) || ($args->slug !== $slug && $args->slug !== $this->plugin_slug)) {
            return $result;
        }

        $release = $this->get_github_release();
        if (!$release) {
            return $result;
        }

        $new_version = ltrim($release['tag_name'] ?? '1.0.0', 'vV');
        $package_url = $release['zipball_url'] ?? '';

        if (!empty($release['assets']) && is_array($release['assets'])) {
            foreach ($release['assets'] as $asset) {
                if (isset($asset['name']) && str_ends_with(strtolower($asset['name']), '.zip')) {
                    $package_url = $asset['browser_download_url'] ?? $package_url;
                    break;
                }
            }
        }

        $info = new stdClass();
        $info->name = 'Auto Post Content AI & Topic Generator';
        $info->slug = $slug;
        $info->version = $new_version;
        $info->author = '<a href="https://github.com/' . esc_attr($this->repo) . '">Marzuki Team</a>';
        $info->homepage = "https://github.com/{$this->repo}";
        $info->requires = '6.0';
        $info->tested = '6.7';
        $info->requires_php = '8.3';
        $info->download_link = $package_url;
        $info->sections = [
            'description' => 'Plugin WordPress canggih untuk riset topik AI, auto-generate artikel SEO mendalam (1 atau 2 bahasa ID & EN), funneling TOFU/MOFU/BOFU, internal link landing page booster, dan penjadwalan otomatis.',
            'changelog'   => wp_kses_post($release['body'] ?? 'Lihat catatan rilis di GitHub.'),
        ];

        return $info;
    }

    public function post_install($true, $hook_extra, $result) {
        global $wp_filesystem;

        // Pastikan nama folder hasil extract tetap sesuai plugin slug
        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->plugin_slug);
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;

        activate_plugin($this->plugin_slug);
        return $result;
    }

    public static function force_check_update(): array {
        delete_site_transient('update_plugins');

        $settings = get_option('apcai_settings', []);
        $repo = !empty($settings['github_repo']) ? trim($settings['github_repo']) : 'marzuki46/Auto-Post-Content';
        $updater = new self(APCAI_PLUGIN_FILE, $repo);
        $release = $updater->get_github_release();

        if (!$release || empty($release['tag_name'])) {
            return [
                'has_update'  => false,
                'current'     => APCAI_VERSION,
                'new_version' => APCAI_VERSION,
                'message'     => 'Tidak dapat menemukan rilis atau tag di repository GitHub (' . esc_html($repo) . ').',
            ];
        }

        $new_version = ltrim($release['tag_name'], 'vV');
        $slug = plugin_basename(APCAI_PLUGIN_FILE);

        if (version_compare(APCAI_VERSION, $new_version, '<')) {
            $transient = get_site_transient('update_plugins');
            if (!is_object($transient)) {
                $transient = new stdClass();
            }

            $obj = new stdClass();
            $obj->slug = dirname($slug);
            $obj->plugin = $slug;
            $obj->new_version = $new_version;
            $obj->url = $release['html_url'] ?? "https://github.com/{$repo}";
            $obj->package = $release['zipball_url'] ?? '';
            $obj->tested = '6.7';
            $obj->requires_php = '8.3';

            $transient->response[$slug] = $obj;
            set_site_transient('update_plugins', $transient);

            return [
                'has_update'  => true,
                'new_version' => $new_version,
                'current'     => APCAI_VERSION,
                'package'     => $obj->package,
                'update_url'  => wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . urlencode($slug)), 'upgrade-plugin_' . $slug),
            ];
        }

        return [
            'has_update'  => false,
            'current'     => APCAI_VERSION,
            'new_version' => $new_version,
            'message'     => 'Plugin sudah memakai versi terbaru (v' . APCAI_VERSION . ').',
        ];
    }
}
