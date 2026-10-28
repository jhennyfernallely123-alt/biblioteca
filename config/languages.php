<?php

$api = [
    'en' => 'en-GB',
    'es' => 'es-ES',
    'pt' => 'pt-PT',
    'fr' => 'fr-FR',
    'de' => 'de-DE',
    'it' => 'it-IT',
    'ca' => 'ca-ES',
    'gl' => 'gl-ES',
    'zh' => 'zh-CN',
    'zh-TW' => 'zh-TW',
    'sr' => 'sr-Latn-RS',
    'ku' => 'kmr-TR',
    'ckb' => 'ckb-IQ',
    'he' => 'he-IL',
    'yi' => 'yi-YD',
    'dv' => 'dv-MV',
    'ug' => 'ug-CN',
    'ps' => 'ps-PK',
    'sd' => 'sd-PK',
    'no' => 'nb-NO',
    'tl' => 'tl-PH',
    'hmn' => 'hmn-CN',
    'haw' => 'haw-US',
    'ceb' => 'ceb-PH',
    'jw' => 'jv-ID',
    'or' => 'or-IN',
    'gd' => 'gd-GB',
    'st' => 'st-LS',
    'sn' => 'sn-ZW',
    'si' => 'si-LK',
    'su' => 'su-ID',
    'tg' => 'tg-TJ',
    'ta' => 'ta-IN',
    'te' => 'te-IN',
    'th' => 'th-TH',
    'tr' => 'tr-TR',
    'uk' => 'uk-UA',
    'ur' => 'ur-PK',
    'uz' => 'uz-UZ',
    'vi' => 'vi-VN',
    'cy' => 'cy-GB',
    'xh' => 'xh-ZA',
    'yo' => 'yo-NG',
    'zu' => 'zu-ZA',
];

$codes = [
    'af', 'sq', 'am', 'ar', 'hy', 'az', 'eu', 'be', 'bn', 'bs', 'bg', 'ca',
    'ceb', 'zh', 'zh-TW', 'co', 'hr', 'cs', 'da', 'nl', 'en', 'eo', 'et',
    'tl', 'fi', 'fr', 'gl', 'ka', 'de', 'el', 'gu', 'ht', 'ha', 'haw', 'he',
    'hi', 'hmn', 'hu', 'is', 'ig', 'id', 'ga', 'it', 'ja', 'jw', 'kn', 'kk',
    'km', 'ko', 'ku', 'ckb', 'ky', 'lo', 'la', 'lv', 'lt', 'lb', 'mk', 'mg',
    'ms', 'ml', 'mt', 'mi', 'mr', 'mn', 'my', 'ne', 'no', 'or', 'ps', 'fa',
    'pl', 'pt', 'pa', 'ro', 'ru', 'sm', 'gd', 'sr', 'st', 'sn', 'sd', 'si',
    'sk', 'sl', 'so', 'es', 'su', 'sw', 'sv', 'tg', 'ta', 'te', 'th', 'tr',
    'uk', 'ur', 'uz', 'vi', 'cy', 'xh', 'yi', 'yo', 'zu',
];

$supported = [];

foreach ($codes as $code) {
    $supported[$code] = $api[$code] ?? $code;
}

return [
    'curated' => ['es', 'en', 'pt', 'fr', 'de', 'it', 'ca', 'gl'],
    'rtl' => ['ar', 'he', 'fa', 'ur', 'ps', 'sd', 'ug', 'yi', 'dv', 'ku', 'ckb'],
    'supported' => $supported,
];
