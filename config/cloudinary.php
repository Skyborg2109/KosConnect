<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cloudinary\Configuration\Configuration;

// Konfigurasi Cloudinary
Configuration::instance([
    'cloud' => [
        'cloud_name' => 'dxhbbcpvz',
        'api_key'    => '834954584268535',
        'api_secret' => 'E1dUz0fKW1L5dpqzFIb3s29xbVM'],
    'url' => [
        'secure' => true
    ]
]);
