<?php

namespace BitApps\WPTelemetry\Telemetry\Feedback;

use BitApps\WPTelemetry\Telemetry\Telemetry;
use BitApps\WPTelemetry\Telemetry\TelemetryConfig;

class Feedback
{
    public function init()
    {
        add_action('wp_ajax_' . TelemetryConfig::getPrefix() . 'deactivate_feedback', [$this, 'handleDeactivateFeedback']);

        add_action('current_screen', [$this, 'loadAllScripts']);
    }

    public function loadAllScripts()
    {
        if (!$this->is_plugins_screen()) {
            return;
        }

        add_action('admin_enqueue_scripts', [$this, 'enqueueFeedbackDialogScripts']);
    }

    /**
     * Enqueue feedback dialog scripts.
     *
     * Registers the feedback dialog scripts and enqueues them.
     *
     * @since 0.0.1
     */
    public function enqueueFeedbackDialogScripts()
    {
        // Free and pro editions can both run this for one slug. Every bundled copy shares WordPress's
        // action counts, so the first copy claims the slug and the others print nothing.
        $claim = 'bitapps_telemetry_deactivate_modal_' . TelemetryConfig::getSlug();

        if (did_action($claim)) {
            return;
        }

        do_action($claim);

        add_action('admin_footer', [$this, 'printDeactivateFeedbackDialog']);

        $handle = TelemetryConfig::getPrefix() . 'deactivate_modal';
        $resourcesUrl = $this->getAssetPath() . 'resources/';

        wp_enqueue_style($handle, $resourcesUrl . 'css/deactivateModalStyle.css', [], TelemetryConfig::getVersion());
        wp_enqueue_script($handle, $resourcesUrl . 'js/deactivateModal.js', ['jquery'], TelemetryConfig::getVersion(), true);

        // Printed right after this copy's script, so each plugin's modal runs the code shipped with its
        // own markup, even when other plugins on the site bundle a different version of this library.
        wp_add_inline_script($handle, 'bitappsTelemetryDeactivateModal(' . wp_json_encode(TelemetryConfig::getSlug()) . ');');
    }

    public static function getAssetPath()
    {
        return plugin_dir_url(\dirname(__DIR__));
    }

    /**
     * Print deactivate feedback dialog.
     *
     * Display a dialog box to ask the user why he deactivated this plugin.
     *
     * @since 0.0.1
     */
    public function printDeactivateFeedbackDialog()
    {
        Telemetry::view('deactivateModal', [
            'slug'      => TelemetryConfig::getSlug(),
            'prefix'    => TelemetryConfig::getPrefix(),
            'policyUrl' => TelemetryConfig::getPolicyUrl(),
            'reasons'   => $this->getDeactivateReasons(),
            'strings'   => Telemetry::strings(),
        ]);
    }

    /**
     * Plain strings: a bundled library can't use the host plugin's text domain as a
     * literal, so plugins translate these through the deactivate_reasons filter.
     *
     * @return array
     */
    public function getDeactivateReasons()
    {
        $title = TelemetryConfig::getTitle();

        $reasons = [
            'found_a_better_plugin' => [
                'title'       => 'Found a better plugin',
                'placeholder' => 'Which plugin?',
            ],
            'missing_specific_feature' => [
                'title'       => 'Missing a specific feature',
                'placeholder' => 'Could you tell us more about that feature?',
            ],
            'not_working' => [
                'title'       => 'Not working',
                'placeholder' => 'Could you tell us what is not working?',
            ],
            'not_working_as_expected' => [
                'title'       => 'Not working as expected',
                'placeholder' => 'Could you tell us what do you expect?',
            ],
            'temporary_deactivation' => [
                'title'       => 'It\'s a temporary deactivation',
                'placeholder' => '',
            ],
            TelemetryConfig::getPrefix() . 'pro' => [
                'title'       => \sprintf('I have %s Pro', $title),
                'placeholder' => '',
                'alert'       => \sprintf('Wait! Don\'t deactivate %1$s. You have to activate both %1$s and %1$s Pro in order to work the plugin.', $title),
            ],
            'other' => [
                'title'       => 'Other',
                'placeholder' => 'Please share the reason',
            ],
        ];

        return apply_filters(TelemetryConfig::getPrefix() . 'deactivate_reasons', $reasons);
    }

    /**
     * Ajax plugin deactivate feedback.
     *
     * Send the user feedback when plugin is deactivated.
     *
     * @since 0.0.1
     */
    public function handleDeactivateFeedback()
    {
        if (!isset($_POST['_ajax_nonce'])) {
            return;
        }

        if (!wp_verify_nonce(sanitize_key(wp_unslash($_POST['_ajax_nonce'])), TelemetryConfig::getPrefix() . 'nonce')) {
            wp_send_json_error('Nonce verification failed');
        }

        if (!current_user_can('activate_plugins')) {
            wp_send_json_error('Permission denied');
        }

        $reasonKey = isset($_POST['reason_key']) ? sanitize_text_field(wp_unslash($_POST['reason_key'])) : '';

        // Answers arrive as reason_text[<key>]: PHP keeps keys inside brackets as sent, but turns
        // spaces and dots in top-level field names into underscores.
        $reasonTexts = isset($_POST['reason_text']) && \is_array($_POST['reason_text'])
            ? map_deep(wp_unslash($_POST['reason_text']), 'sanitize_text_field')
            : [];
        $feedback = isset($reasonTexts[$reasonKey]) && \is_string($reasonTexts[$reasonKey]) ? $reasonTexts[$reasonKey] : '';

        // Strict checks: '0' is a valid key (reasons appended with []) and a valid answer.
        $report = [];
        $report['url'] = esc_url(home_url());
        $report['site_lang'] = get_bloginfo('language');
        $report['feedback_key'] = $reasonKey !== '' ? $reasonKey : null;
        $report['feedback'] = $feedback !== '' ? $feedback : null;
        $report['plugin_slug'] = TelemetryConfig::getPrefix();
        $report['plugin_version'] = TelemetryConfig::getversion();

        Telemetry::sendReport('deactivate-reason', $report);

        wp_send_json_success();
    }

    /**
     * @since 0.0.1
     */
    private function is_plugins_screen()
    {
        return \in_array(get_current_screen()->id, ['plugins', 'plugins-network']);
    }
}
