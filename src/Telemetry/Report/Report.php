<?php

namespace BitApps\WPTelemetry\Telemetry\Report;

use BitApps\WPTelemetry\Telemetry\Telemetry;
use BitApps\WPTelemetry\Telemetry\TelemetryConfig;

class Report
{
    private $addPluginData = false;

    public function init()
    {
        $this->initCommonHooks();

        add_action(TelemetryConfig::getPrefix() . 'activate', [$this, 'activatePlugin']);

        add_action(TelemetryConfig::getPrefix() . 'deactivate', [$this, 'deactivatePlugin']);
    }

    public function addPluginData()
    {
        $this->addPluginData = true;

        return $this;
    }

    public function initCommonHooks()
    {
        if (TelemetryConfig::getShowAdminNotice() && !$this->isTrackingNoticeDismissed()) {
            add_action('admin_notices', [$this, 'adminNotice']);
        }

        add_action('admin_init', [$this, 'handleTrackingOptInOptOut']);

        add_filter('cron_schedules', [$this, 'addWeeklySchedule']);

        add_action(TelemetryConfig::getPrefix() . 'send_tracking_event', [$this, 'sendTrackingReport']);
    }

    public function adminNotice()
    {
        if (
            !current_user_can('manage_options')
            || !$this->isNoticeScreen()
            || $this->isTrackingNoticeDismissed()
            || $this->isTrackingAllowed()
        ) {
            return;
        }

        Telemetry::view('reportOptIn', [
            'termsUrl'      => TelemetryConfig::getTermsUrl(),
            'policyUrl'     => TelemetryConfig::getPolicyUrl(),
            'optInUrl'      => wp_nonce_url(add_query_arg(TelemetryConfig::getPrefix() . 'tracking_opt_in', 'true'), '_wpnonce'),
            'optOutUrl'     => wp_nonce_url(add_query_arg(TelemetryConfig::getPrefix() . 'tracking_opt_out', 'true'), '_wpnonce'),
            'heading'       => $this->getHeading(),
            'description'   => $this->getDescription(),
            'dataWeCollect' => $this->dataWeCollect(),
            'strings'       => Telemetry::strings(),
        ]);
    }

    public function getHeading()
    {
        $heading = \sprintf('We hope you love %s.', esc_html(TelemetryConfig::getTitle()));

        return apply_filters(TelemetryConfig::getPrefix() . 'telemetry_notice_heading', $heading);
    }

    public function getDescription()
    {
        $description = \sprintf(
            'Hi, %1$s! Would you like to help us improve %2$s? If you allow it, the data listed below is sent to our team once a week. Nothing is sent if you skip, and %2$s works the same either way.',
            esc_html(wp_get_current_user()->display_name),
            esc_html(TelemetryConfig::getTitle())
        );

        return apply_filters(TelemetryConfig::getPrefix() . 'telemetry_notice_description', $description);
    }

    public function handleTrackingOptInOptOut()
    {
        $optInKey = TelemetryConfig::getPrefix() . 'tracking_opt_in';
        $optOutKey = TelemetryConfig::getPrefix() . 'tracking_opt_out';
        $optIn = isset($_GET[$optInKey]) && $_GET[$optInKey] === 'true';
        $optOut = isset($_GET[$optOutKey]) && $_GET[$optOutKey] === 'true';

        // Only this plugin's links: verifying every admin request's nonce would fire
        // wp_verify_nonce_failed for other plugins' and core's links.
        if (!$optIn && !$optOut) {
            return;
        }

        if (
            !isset($_GET['_wpnonce'])
            || !wp_verify_nonce(sanitize_key($_GET['_wpnonce']), '_wpnonce')
            || !current_user_can('manage_options')
        ) {
            return;
        }

        if ($optIn) {
            $this->trackingOptIn('notice');
        } else {
            $this->trackingOptOut();
        }

        wp_safe_redirect(remove_query_arg([$optInKey, $optOutKey]));
        exit;
    }

    /**
     * @param string $source Where the user opted in, e.g. 'notice' for the admin notice or
     *                       'settings' for a plugin settings toggle. Sent as opt_in_source.
     */
    public function trackingOptIn($source = 'custom')
    {
        // A repeat call (e.g. on every settings save) isn't a new opt-in: keep the first source and
        // only restore the weekly event if it went missing.
        if ($this->isTrackingAllowed()) {
            $this->scheduleEvent();

            return;
        }

        // As a hook callback this receives '' or an object; that is still a custom opt-in place.
        $source = \is_string($source) ? sanitize_key($source) : '';
        $source = $source !== '' ? $source : 'custom';

        update_option(TelemetryConfig::getPrefix() . 'tracking_opt_in_source', $source, false);
        update_option(TelemetryConfig::getPrefix() . 'allow_tracking', true);
        update_option(TelemetryConfig::getPrefix() . 'tracking_notice_dismissed', true);

        $this->clearScheduleEvent();
        $this->scheduleEvent();
        $this->sendTrackingReport();

        do_action(TelemetryConfig::getPrefix() . 'tracking_opt_in', $source);
    }

    public function trackingOptOut()
    {
        update_option(TelemetryConfig::getPrefix() . 'allow_tracking', false);
        update_option(TelemetryConfig::getPrefix() . 'tracking_notice_dismissed', true);
        delete_option(TelemetryConfig::getPrefix() . 'tracking_opt_in_source');

        // Stays local: contacting the server after the user says no breaks WordPress.org guideline 7.
        update_option(TelemetryConfig::getPrefix() . 'tracking_skipped', true, false);

        $this->clearScheduleEvent();

        do_action(TelemetryConfig::getPrefix() . 'tracking_opt_out');
    }

    public function addWeeklySchedule($schedules)
    {
        // Core has 'weekly' since WordPress 5.4 and merges its own over this; kept for hosts that
        // still support older versions, where scheduling 'weekly' would otherwise fail.
        $schedules['weekly'] = [
            'interval' => WEEK_IN_SECONDS,
            'display'  => 'Once Weekly'
        ];

        return $schedules;
    }

    public function activatePlugin()
    {
        if (!$this->isTrackingAllowed()) {
            return;
        }

        $this->scheduleEvent();

        $this->sendTrackingReport();
    }

    public function deactivatePlugin()
    {
        $this->clearScheduleEvent();

        delete_option(TelemetryConfig::getPrefix() . 'tracking_notice_dismissed');
    }

    /**
     * Deletes the options and the weekly event the library stores for a plugin. Call it from the
     * plugin's uninstall routine with the plugin's prefix, since telemetry may not be set up there.
     *
     * @param string $prefix The prefix given to TelemetryConfig::setPrefix()
     */
    public function deleteData($prefix)
    {
        foreach (['allow_tracking', 'tracking_notice_dismissed', 'tracking_skipped', 'tracking_last_sended_at', 'tracking_opt_in_source'] as $option) {
            delete_option($prefix . $option);
        }

        wp_clear_scheduled_hook($prefix . 'send_tracking_event');
    }

    public function isTrackingAllowed()
    {
        return get_option(TelemetryConfig::getPrefix() . 'allow_tracking');
    }

    public function isTrackingNoticeDismissed()
    {
        return get_option(TelemetryConfig::getPrefix() . 'tracking_notice_dismissed');
    }

    public function sendTrackingReport()
    {
        if (!$this->isTrackingAllowed() || $this->isSendedWithinWeek()) {
            return;
        }

        $trackingData = $this->getTrackingData();

        Telemetry::sendReport('plugin-track-create', $trackingData);

        $this->updateLastSendedAt();
    }

    public function getTrackingData()
    {
        $reportInfo = new ReportInfo();

        $allPlugins = $reportInfo->getAllPlugins();

        $user_name = $reportInfo->getUserName();

        $data = [
            'url'              => esc_url(home_url()),
            'site'             => $reportInfo->getSiteName(),
            'admin_email'      => get_option('admin_email'),
            'first_name'       => $user_name['firstName'],
            'last_name'        => $user_name['lastName'],
            'server'           => $reportInfo->getServerInfo(),
            'wp'               => $reportInfo->getWpInfo(),
            'users'            => $reportInfo->getUserCounts(),
            'active_plugins'   => \count($allPlugins['activePlugins']),
            'inactive_plugins' => \count($allPlugins['inactivePlugins']),
            'ip_address'       => '', // IP lookup removed; key kept for servers that expect it.
            'plugin_slug'      => TelemetryConfig::getPrefix(),
            'plugin_version'   => TelemetryConfig::getversion(),
            'is_local'         => $reportInfo->isLocalServer(),
            'skipped'          => false,
            'opt_in_source'    => get_option(TelemetryConfig::getPrefix() . 'tracking_opt_in_source', ''),
        ];

        if ($this->addPluginData) {
            $data['plugins'] = $reportInfo->getPluginInfo($allPlugins['activePlugins'], TelemetryConfig::getSlug());
        }

        if (get_option(TelemetryConfig::getPrefix() . 'tracking_skipped')) {
            delete_option(TelemetryConfig::getPrefix() . 'tracking_skipped');
            $data['previously_skipped'] = true;
        }

        $data['additional_data'] = apply_filters(TelemetryConfig::getPrefix() . 'telemetry_additional_data', []);

        return apply_filters(TelemetryConfig::getPrefix() . 'telemetry_data', $data);
    }

    /**
     * What getTrackingData() sends, in words, so consent covers all of it. The opt-in notice
     * shows it, and plugins show it next to their own opt-in settings. Plugins that add data
     * through the telemetry_data or telemetry_additional_data filters must add it here through
     * the telemetry_data_we_collect filter.
     *
     * @return array
     */
    public function dataWeCollect()
    {
        $collectList = [
            'Site name and URL',
            'Site admin email address and the first administrator\'s name',
            'Server environment (server software, PHP and MySQL versions, PHP settings, whether the site is local)',
            'WordPress version, language and settings (memory limit, debug mode, multisite)',
            'Active theme (name, version, author, website)',
            'Number of users, in total and per role',
            'Number of active and inactive plugins',
            TelemetryConfig::getTitle() . ' version and telemetry library version',
            'Which page you opted in from, and whether you skipped this before',
        ];

        if ($this->addPluginData) {
            $collectList[] = 'Names and versions of active plugins';
        }

        $collectList = apply_filters(TelemetryConfig::getPrefix() . 'telemetry_data_we_collect', $collectList);

        // A filter may return a string or nested values; only plain items can be printed.
        return array_values(array_filter((array) $collectList, 'is_scalar'));
    }

    /**
     * Keep the opt-in notice to the dashboard, the plugins page and the screens under the
     * plugin's own admin menu (WordPress.org guideline 11).
     *
     * @return bool
     */
    private function isNoticeScreen()
    {
        // Not defined outside wp-admin, where some plugins still fire admin_notices.
        $screen = \function_exists('get_current_screen') ? get_current_screen() : null;
        $slug = TelemetryConfig::getSlug();

        // parent_base is the admin menu the screen sits under, so the plugin's post type and
        // settings screens count, but another plugin whose slug merely contains ours doesn't.
        $show = $screen && (
            \in_array($screen->id, ['dashboard', 'plugins'], true)
            || ($slug && $screen->parent_base === $slug)
        );

        return (bool) apply_filters(TelemetryConfig::getPrefix() . 'telemetry_show_notice', $show, $screen);
    }

    private function scheduleEvent()
    {
        $hook_name = TelemetryConfig::getPrefix() . 'send_tracking_event';

        if (!wp_next_scheduled($hook_name)) {
            wp_schedule_event(time(), 'weekly', $hook_name);
        }
    }

    private function clearScheduleEvent()
    {
        return wp_clear_scheduled_hook(TelemetryConfig::getPrefix() . 'send_tracking_event');
    }

    private function isSendedWithinWeek()
    {
        $lastSendedAt = $this->lastSendedAt();

        return $lastSendedAt && $lastSendedAt > strtotime('-1 week');
    }

    private function lastSendedAt()
    {
        return get_option(TelemetryConfig::getPrefix() . 'tracking_last_sended_at');
    }

    private function updateLastSendedAt()
    {
        return update_option(TelemetryConfig::getPrefix() . 'tracking_last_sended_at', time());
    }
}
