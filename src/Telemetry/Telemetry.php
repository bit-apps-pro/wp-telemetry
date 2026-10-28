<?php

namespace BitApps\WPTelemetry\Telemetry;

use BitApps\WPTelemetry\Telemetry\Feedback\Feedback;
use BitApps\WPTelemetry\Telemetry\Report\Report;

class Telemetry
{
    private static $report;

    private static $feedback;

    // Sent as wp_telemetry so the server can tell payload versions apart; bump on every release.
    private static $version = '2.0.0';

    public static function getVersion()
    {
        return self::$version;
    }

    public static function report()
    {
        if (!self::$report) {
            self::$report = new Report();
        }

        return self::$report;
    }

    public static function feedback()
    {
        if (!self::$feedback) {
            self::$feedback = new Feedback();
        }

        return self::$feedback;
    }

    /**
     * Ready-to-print labels for the opt-in notice and the deactivation survey. A bundled library
     * can't use the host plugin's text domain as a literal, so plugins translate these through
     * the telemetry_strings filter with their own __() calls.
     *
     * @return array
     */
    public static function strings()
    {
        $title = TelemetryConfig::getTitle();

        $defaults = [
            'allow'             => 'Allow & Continue',
            'skip'              => 'Skip',
            'dismiss'           => 'Dismiss this notice.',
            'data_shared'       => 'What we collect',
            'marketing'         => 'Also email me tips and product news. You can unsubscribe anytime.',
            'terms'             => 'Terms of service',
            'privacy'           => 'Privacy Policy',
            'survey_title'      => 'Quick Feedback',
            'survey_caption'    => \sprintf('If you have a moment, please let us know how "%s" can improve.', $title),
            'survey_disclosure' => \sprintf('Submitting sends your site URL, site language and your answer to the %s team.', $title),
            'survey_submit'     => 'Submit & Deactivate',
            'survey_skip'       => 'Skip & Deactivate',
        ];

        return wp_parse_args(apply_filters(TelemetryConfig::getPrefix() . 'telemetry_strings', $defaults), $defaults);
    }

    public static function view($fileName, $args)
    {
        load_template(\dirname(\dirname(__DIR__)) . '/src/views/' . $fileName . '.php', false, $args);
    }

    public static function sendReport($route, $data, $blocking = false)
    {
        $apiUrl = trailingslashit(TelemetryConfig::getServerBaseUrl()) . $route;

        $headers = [
            'host-user'    => md5(esc_url(home_url())),
            'Content-Type' => 'application/json',
        ];

        return wp_remote_post(
            $apiUrl,
            [
                'method'      => 'POST',
                'timeout'     => 30,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking'    => $blocking,
                'headers'     => $headers,
                'body'        => wp_json_encode(array_merge($data, ['wp_telemetry' => self::$version])),
                'cookies'     => [],
            ]
        );
    }
}
