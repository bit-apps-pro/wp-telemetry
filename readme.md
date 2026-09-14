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

**You are good to go! 👍️**

The telemetry client will start sending data `weekly` to your configured server url.

## Configuration

### # Activate/Deactivate Telemetry Tracking

You can add a setting in your plugin settings page to allow users to opt-in or opt-out of telemetry tracking. You can use the following methods to change the opt-in/opt-out status.

**⚡️ Opt In :**

```php
Telemetry::report()->trackingOptIn();
```

**⚡️ Opt Out :**

```php
Telemetry::report()->trackingOptOut();
```

**⚡️ Check if tracking is enabled**

```php
Telemetry::report()->isTrackingAllowed();
```

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

### # Customize the Opt-In Admin Notice

**⚡️ Filter Hooks to Alter the Notice Heading and Description :**

Both accept HTML, so links can be included.

| Filter | Value |
| --- | --- |
| `$plugin_prefix . 'telemetry_notice_heading'` | Heading, default `We hope you love {title}.` |
| `$plugin_prefix . 'telemetry_notice_description'` | Description paragraph |

```php
add_filter($plugin_prefix . 'telemetry_notice_heading', function ($heading) {
  return 'Help us make My Plugin better';
});

add_filter($plugin_prefix . 'telemetry_notice_description', function ($description) {
  return 'Share non-sensitive diagnostic data and usage info - <a href="https://example.com/what-we-collect/" target="_blank">what we collect</a>';
});
```

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
