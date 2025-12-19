<?php
declare(strict_types=1);

// API Entry Point

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Api;
use App\Config;

// Load configuration
Config::getInstance();

// Handle the API request
$api = new Api();
$api->handle();
