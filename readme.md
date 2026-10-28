# WP Telemetry

This package provides a simple way to add telemetry tracking to your WordPress plugin. It sends weekly reports to your server with information about the plugin's usage.

## Usage

### 1. Installation

Install the package using composer:

```bash
composer require bitapps/wp-telemetry
```

### 2. Create a Telemetry Client

Initialize the telemetry client in your plugin's bootstrap file.

```php
function initialize_telemetry_client()
{
  TelemetryConfig::setTitle($title);
  TelemetryConfig::setSlug($plugin_slug);
  TelemetryConfig::setPrefix($plugin_prefix);
  TelemetryConfig::setVersion($plugin_version);

  TelemetryConfig::setServerBaseUrl( 'https://api.example.com/' );
  TelemetryConfig::setTermsUrl( 'https://example.com/terms/' ); // (optional)
  TelemetryConfig::setPolicyUrl( 'https://example.com/privacy/' ); // (optional)
  TelemetryConfig::setShowAdminNotice( false ); // (optional) hide the opt-in admin notice, default true

  Telemetry::report()->init(); // initialize telemetry tracking
  Telemetry::feedback()->init(); // initialize deactivation feedback survey
}

initialize_telemetry_client();
```

Then fire these from your plugin's activation and deactivation hooks, after `Telemetry::report()->init()` has run, so the library resumes the weekly report when the plugin is reactivated and stops it when it is deactivated:

```php
do_action($plugin_prefix . 'activate');
do_action($plugin_prefix . 'deactivate');
```

**You are good to go! 👍️**

No usage report is sent until an admin clicks **Allow & Continue** in the opt-in notice, or turns on a setting that calls `trackingOptIn()`. After that, the telemetry client sends data `weekly` to your configured server url. The deactivation survey only sends what a user chooses to submit.

> Before publishing on WordPress.org, read [WordPress.org Plugin Guidelines](#wordpressorg-plugin-guidelines) below. Some steps have to be done in your plugin.

## Configuration

### # Activate/Deactivate Telemetry Tracking

You can add a setting in your plugin settings page to allow users to opt-in or opt-out of telemetry tracking. You can use the following methods to change the opt-in/opt-out status.

**⚡️ Opt In :**

```php
Telemetry::report()->trackingOptIn('settings');
```

The argument records where the user opted in. It's saved and sent as `opt_in_source` in every report, so you can compare the notice with your own opt-in places:

| `opt_in_source` | Meaning |
| --- | --- |
| `notice` | Clicked **Allow & Continue** in the opt-in notice |
| your value, e.g. `settings` | Your plugin called `trackingOptIn('settings')` |
| `custom` | Your plugin called `trackingOptIn()` without a source, or with an empty or non-text value (as when it's used directly as a hook callback) |
| empty | Opted in before this field existed |

If your plugin has several opt-in places, give each one its own name:

```php
// Onboarding wizard
Telemetry::report()->trackingOptIn('onboarding');

// Settings page toggle
Telemetry::report()->trackingOptIn('settings');

// Support page checkbox
Telemetry::report()->trackingOptIn('support_page');
```

Names are passed through `sanitize_key()`, so use lowercase letters, numbers, `_` and `-`. Calling `trackingOptIn()` again while tracking is already on changes nothing, so saving a settings page doesn't overwrite the name. If a user opts out and later opts back in from another place, the new name replaces the old one.

The source is also passed to the opt-in action:

```php
add_action($plugin_prefix . 'tracking_opt_in', function ($source) {
  // e.g. $source === 'notice'
});
```

**⚡️ Opt Out :**

```php
Telemetry::report()->trackingOptOut();
```

Opting out (including **Skip** and the dismiss button in the notice) only updates local options. Nothing is sent to your server.

**⚡️ Check if tracking is enabled**

```php
Telemetry::report()->isTrackingAllowed();
```

**⚡️ Clean up on uninstall**

Call this from your plugin's uninstall routine. It deletes every option the library stores and clears its weekly event. Pass the prefix, because telemetry may not be set up while uninstalling:

```php
Telemetry::report()->deleteData($plugin_prefix);
```

### # Marketing Email Consent (optional)

Agreeing to telemetry is not agreeing to marketing email. If you want to send product news, ask separately:

```php
TelemetryConfig::setShowMarketingOptIn(true);
```

This adds an **unticked** checkbox to the opt-in notice: "Also email me tips and product news. You can unsubscribe anytime." If the admin ticks it before clicking **Allow & Continue**, reports include `marketing_consent: true` and `marketing_consent_at` (when they ticked it, as a Unix timestamp). It's also added to the "What we collect" list.

To ask in your own settings page instead, or to let users change their answer later:

```php
Telemetry::report()->setMarketingConsent(true);  // the user ticked your checkbox
Telemetry::report()->setMarketingConsent(false); // the user unticked it
Telemetry::report()->hasMarketingConsent();
```

Rules for your server and emails:

- Only send marketing email when the latest report says `marketing_consent: true`.
- Never pre-tick the box. Pre-ticked boxes are not valid consent under GDPR or CASL.
- Put an unsubscribe link in every email and honor it. A change made on the site only reaches your server with the next weekly report, and never if tracking is off, so the unsubscribe link is what users rely on.

### # Tracking Report Modify

**⚡️ Filter Hook to Add Additional Data :**

This filter allows adding additional data to track information used by the plugin. You can modify the `additional_data` array to include any custom data needed.

```php
add_filter($plugin_prefix . 'telemetry_additional_data', 'customize_additional_data', 10, 1);

function customize_additional_data($additional_data)
{
  // Do your stuffs here
  return $additional_data;
}
```

**⚡️ Filter Hook To Modify Telemetry Data :**

This filter allows modifying the telemetry data before sending it to the server. You can modify the `$telemetry_data` array to include any custom data needed.

```php
add_filter($plugin_prefix . 'telemetry_data', 'customize_telemetry_data', 10, 1);

function customize_telemetry_data($telemetry_data)
{
  // Do your stuffs here
  return $telemetry_data;
}
```

**⚡️ Add plugin information in tracking data**

```php
Telemetry::report()
                ->addPluginData()
                ->init();
```

**⚡️ List the data you add in the opt-in notice**

The opt-in notice has a "What we collect" toggle that opens this list, so users know what they agree to. Anything you add with `telemetry_data` or `telemetry_additional_data` must be added to this list too. The filter must return an array of strings.

```php
add_filter($plugin_prefix . 'telemetry_data_we_collect', function ($list) {
  $list[] = 'Number of flows and how often they run';
  $list[] = 'License key and status';

  return $list;
});
```

Show the same list wherever users opt in, such as a settings toggle, an onboarding step or a support page. `dataWeCollect()` returns it, including your additions:

```php
$list = Telemetry::report()->dataWeCollect();
```

### # Customize the Opt-In Admin Notice

**⚡️ Filter Hooks to Alter the Notice Heading and Description :**

Both accept HTML, so links can be included.

| Filter | Value |
| --- | --- |
| `$plugin_prefix . 'telemetry_notice_heading'` | Heading, default `Hi {user name}, help us make {title} better!` |
| `$plugin_prefix . 'telemetry_notice_description'` | Description, default `Share diagnostic data and usage info about your use of {title}.` |

The "What we collect" toggle is shown below the description, so the description doesn't need its own link. If your plugin has a setting to turn tracking off, say where it is:

```php
add_filter($plugin_prefix . 'telemetry_notice_description', function ($description) {
  return $description . ' You can opt out anytime from the Support page.';
});
```

**⚡️ Hide the notice**

```php
TelemetryConfig::setShowAdminNotice(false);
```

You can call it before or after `Telemetry::report()->init()`. With the notice hidden, nothing is sent until your own setting calls `trackingOptIn()`, so show the `dataWeCollect()` list next to that setting.

**⚡️ Choose where the notice is shown**

By default the notice is shown only on the Dashboard, the Plugins page, and the screens under your plugin's admin menu: the menu whose slug matches your plugin slug, including its post type and settings screens (WordPress.org guideline 11). If your menu slug is different, use this filter. `$screen` can be `null`:

```php
add_filter($plugin_prefix . 'telemetry_show_notice', function ($show, $screen) {
  return $show || ($screen && $screen->parent_base === 'my-plugin-menu');
}, 10, 2);
```

**⚡️ Translations**

The notice and survey text is plain English by default. A bundled library can't use your plugin's text domain as a literal, and WordPress.org requires a literal one. So translate the text from your plugin, with these filters and your own `__()` calls:

| Filter | Translates |
| --- | --- |
| `$plugin_prefix . 'telemetry_strings'` | Buttons, links and labels in the notice and the survey |
| `$plugin_prefix . 'telemetry_notice_heading'`, `$plugin_prefix . 'telemetry_notice_description'` | The notice heading and description |
| `$plugin_prefix . 'telemetry_data_we_collect'` | The "What we collect" list |
| `$plugin_prefix . 'deactivate_reasons'` | The survey reasons |

```php
add_filter($plugin_prefix . 'telemetry_strings', function ($strings) {
  $strings['allow'] = __('Allow & Continue', 'my-plugin');
  $strings['skip'] = __('Skip', 'my-plugin');
  /* translators: %s: plugin name */
  $strings['survey_caption'] = sprintf(__('If you have a moment, please let us know how "%s" can improve.', 'my-plugin'), 'My Plugin');

  return $strings;
});
```

The keys are `allow`, `skip`, `dismiss`, `data_shared`, `marketing`, `terms`, `privacy`, `survey_title`, `survey_caption`, `survey_disclosure`, `survey_submit` and `survey_skip`. The filter gets finished text, with your plugin title already in `survey_caption` and `survey_disclosure`, and its values are printed as they are.

### # Deactivation Feedback Survey

**⚡️ Filter Hook to Add Deactivate Reasons :**

This filter allows adding additional deactivate reasons to the feedback survey. You can modify the `deactivate_reasons` array to include any custom reasons needed.

```php

add_filter($plugin_prefix . 'deactivate_reasons', 'add_deactivate_reasons', 10, 1);

function add_deactivate_reasons($deactivate_reasons)
{
  // example of adding a custom deactivate reason
  $deactivate_reasons[] = [
    'title' => 'What could we have done to improve your experience?',
    'placeholder' => 'Please provide your feedback here',
  ];

  return $deactivate_reasons;
}
```

The survey tells users that submitting sends their site URL, site language and their answer. It links to your privacy policy when `setPolicyUrl()` is set.

## WordPress.org Plugin Guidelines

The package follows [guideline 7](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#7-plugins-may-not-track-users-without-their-consent) (no tracking without consent) and [guideline 11](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/#11-plugins-should-not-hijack-the-admin-dashboard) (limited, dismissible notices). To stay compliant, your plugin also has to:

1. **Document the external service in `readme.txt`.** Add an `== External services ==` section that names your telemetry server, lists the data sent (the opt-in notice list) and says when (weekly after opt-in, and on deactivation feedback when submitted). Link your Terms and Privacy Policy.
2. **Set `setTermsUrl()` and `setPolicyUrl()`.** They are optional in code, but in practice WordPress.org expects them.
3. **List extra data, and show the list wherever users opt in.** See "List the data you add in the opt-in notice" above.
4. **Keep consent honest.** The report includes the site admin email and the first administrator's name, so don't call the data "non-sensitive" or "anonymous" in custom notice text. Don't bundle marketing email consent into the telemetry opt-in. Only send marketing email to sites whose reports say `marketing_consent: true` (see "Marketing Email Consent" above).
5. **Only opt in on user action.** Call `trackingOptIn()` only when the user turns tracking on, such as by enabling a setting. Calling it automatically is tracking without consent.
6. **Respect opt-outs on your server.** Opt-outs aren't sent, so treat a site as opted out once its weekly reports stop (for example after three missed weeks).
