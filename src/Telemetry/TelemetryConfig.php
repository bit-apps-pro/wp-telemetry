<?php

namespace BitApps\WPTelemetry\Telemetry;

class TelemetryConfig
{
    private static $title;

    private static $slug;

    private static $prefix;

    private static $version;

    private static $termsUrl = '';

    private static $policyUrl = '';

    private static $serverBaseUrl = '';

    private static $showAdminNotice = true;

    private static $showMarketingOptIn = false;

    public static function setTitle($title)
    {
        self::$title = $title;
    }

    public static function getTitle()
    {
        return self::$title;
    }

    public static function setSlug($slug)
    {
        self::$slug = $slug;
    }

    public static function getSlug()
    {
        return self::$slug;
    }

    public static function setPrefix($prefix)
    {
        self::$prefix = $prefix;
    }

    public static function getPrefix()
    {
        return self::$prefix;
    }

    public static function setVersion($version)
    {
        self::$version = $version;
    }

    public static function getVersion()
    {
        return self::$version;
    }

    public static function setTermsUrl($url)
    {
        self::$termsUrl = $url;
    }

    public static function getTermsUrl()
    {
        return self::$termsUrl;
    }

    public static function setPolicyUrl($url)
    {
        self::$policyUrl = $url;
    }

    public static function getPolicyUrl()
    {
        return self::$policyUrl;
    }

    public static function setServerBaseUrl($url)
    {
        self::$serverBaseUrl = $url;
    }

    public static function getServerBaseUrl()
    {
        return self::$serverBaseUrl;
    }

    public static function setShowAdminNotice($show)
    {
        self::$showAdminNotice = (bool) $show;
    }

    public static function getShowAdminNotice()
    {
        return self::$showAdminNotice;
    }

    /**
     * Ask for marketing email consent: an unticked checkbox in the opt-in notice and a
     * marketing_consent field in reports.
     *
     * @param bool $show
     */
    public static function setShowMarketingOptIn($show)
    {
        self::$showMarketingOptIn = (bool) $show;
    }

    public static function getShowMarketingOptIn()
    {
        return self::$showMarketingOptIn;
    }
}
