<?php

namespace BitApps\WPTelemetry\Telemetry\Report;

class ReportInfo
{
    /**
     * Check if the current server is localhost
     *
     * @return bool
     */
    public function isLocalServer()
    {
        $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : 'localhost';
        $ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : '127.0.0.1';
        $isLocal = false;

        if (
            \in_array($ip, ['127.0.0.1', '::1'], true)
            || !strpos($host, '.')
            || \in_array(strrchr($host, '.'), ['.test', '.testing', '.local', '.localhost', '.localdomain'], true)
        ) {
            $isLocal = true;
        }

        return $isLocal;
    }

    /**
     * Get the number of post counts
     *
     * @param string $postType
     *
     * @return int
     */
    public function getPostCount($postType)
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT count(ID) FROM {$wpdb->posts} WHERE post_type = %s and post_status = %s",
                [$postType, 'publish']
            )
        );
    }

    /**
     * Get user name.
     *
     * @return array
     */
    public function getUserName()
    {
        $users = get_users(
            [
                'role'    => 'administrator',
                'orderby' => 'ID',
                'order'   => 'ASC',
                'number'  => 1,
                'paged'   => 1,
            ]
        );

        $adminUser = (\is_array($users) && !empty($users)) ? $users[0] : false;
        $firstName = '';
        $lastName = '';

        if ($adminUser) {
            $firstName = $adminUser->first_name ? $adminUser->first_name : $adminUser->display_name;
            $lastName = $adminUser->last_name;
        }

        return [
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ];
    }

    /**
     * Get user totals based on user role.
     *
     * @return array
     */
    public function getUserCounts()
    {
        $userCount = [];
        $userCountData = count_users();
        $userCount['total'] = $userCountData['total_users'];

        // Get user count based on user role
        foreach ($userCountData['avail_roles'] as $role => $count) {
            if (!$count) {
                continue;
            }

            $userCount[$role] = $count;
        }

        return $userCount;
    }

    /**
     * Get server related info.
     *
     * @return array
     */
    public static function getServerInfo()
    {
        global $wpdb;

        $serverData = [];

        if (isset($_SERVER['SERVER_SOFTWARE']) && !empty($_SERVER['SERVER_SOFTWARE'])) {
            $serverData['software'] = sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE']));
        }

        if (\function_exists('phpversion')) {
            $serverData['php_version'] = PHP_VERSION;
        }

        $serverData['mysql_version'] = $wpdb->db_version();

        $serverData['php_max_upload_size'] = size_format(wp_max_upload_size());
        $serverData['php_default_timezone'] = date_default_timezone_get();
        $serverData['php_soap'] = class_exists('SoapClient') ? 'Yes' : 'No';
        $serverData['php_fsockopen'] = \function_exists('fsockopen') ? 'Yes' : 'No';
        $serverData['php_curl'] = \function_exists('curl_init') ? 'Yes' : 'No';

        return $serverData;
    }

    /**
     * Get WordPress related data.
     *
     * @return array
     */
    public function getWpInfo()
    {
        $wpData = [];

        $wpData['memory_limit'] = WP_MEMORY_LIMIT;
        $wpData['debug_mode'] = (\defined('WP_DEBUG') && WP_DEBUG) ? 'Yes' : 'No';
        $wpData['locale'] = get_locale();
        $wpData['version'] = get_bloginfo('version');
        $wpData['multisite'] = is_multisite() ? 'Yes' : 'No';
        $wpData['theme_slug'] = get_stylesheet();

        $theme = wp_get_theme($wpData['theme_slug']);

        $wpData['theme_name'] = $theme->get('Name');
        $wpData['theme_version'] = $theme->get('Version');
        $wpData['theme_uri'] = $theme->get('ThemeURI');
        $wpData['theme_author'] = $theme->get('Author');

        return $wpData;
    }

    /**
     * Get the list of active and inactive plugins
     *
     * @return array
     */
    public function getAllPlugins()
    {
        if (!\function_exists('get_plugins')) {
            include ABSPATH . '/wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();
        $activePluginsKeys = get_option('active_plugins', []);
        $activePlugins = [];

        foreach ($plugins as $k => $v) {
            $formatted = [];
            $formatted['name'] = wp_strip_all_tags($v['Name']);

            if (isset($v['Version'])) {
                $formatted['version'] = wp_strip_all_tags($v['Version']);
            }

            if (isset($v['Author'])) {
                $formatted['author'] = wp_strip_all_tags($v['Author']);
            }

            if (isset($v['Network'])) {
                $formatted['network'] = wp_strip_all_tags($v['Network']);
            }

            if (isset($v['PluginURI'])) {
                $formatted['plugin_uri'] = wp_strip_all_tags($v['PluginURI']);
            }

            if (\in_array($k, $activePluginsKeys, true)) {
                unset($plugins[$k]);
                $activePlugins[$k] = $formatted;
            } else {
                $plugins[$k] = $formatted;
            }
        }

        return [
            'activePlugins'   => $activePlugins,
            'inactivePlugins' => $plugins,
        ];
    }

    /**
     * Get plugin info
     *
     * @param mixed  $activePlugins
     * @param string $slug
     *
     * @return array
     */
    public function getPluginInfo($activePlugins, $slug)
    {
        $pluginInfo = [];

        foreach ($activePlugins as $prefix => $plugin) {
            $prefix = strstr($prefix, '/', true);

            if (!$prefix) {
                continue;
            }

            $pluginInfo[$prefix] = [
                'name'    => isset($plugin['name']) ? $plugin['name'] : '',
                'version' => isset($plugin['version']) ? $plugin['version'] : '',
            ];
        }

        if (\array_key_exists($slug, $pluginInfo)) {
            unset($pluginInfo[$slug]);
        }

        return $pluginInfo;
    }

    /**
     * @deprecated 1.1.0 The lookup called a third-party service (icanhazip.com) that the opt-in
     *                   notice didn't disclose. Read the IP from the request on your server instead.
     *
     * @return string Always empty
     */
    public function getUserIpAddress()
    {
        _deprecated_function(__METHOD__, '1.1.0');

        return '';
    }

    /**
     * Get site name
     */
    public function getSiteName()
    {
        $siteName = get_bloginfo('name');

        if (empty($siteName)) {
            $siteName = get_bloginfo('description');
            $siteName = wp_trim_words($siteName, 3, '');
        }

        if (empty($siteName)) {
            $siteName = esc_url(home_url());
        }

        return $siteName;
    }
}
