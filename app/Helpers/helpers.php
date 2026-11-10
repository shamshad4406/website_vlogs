<?php

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        $setting = \App\Models\SiteSetting::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
}
