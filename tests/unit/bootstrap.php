<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Minimum WordPress constants needed to load plugin files
define('ABSPATH', sys_get_temp_dir() . '/');
define('WPINC', 'wp-includes');

// WordPress translation stub — the plugin calls __() throughout; stub it to
// return the first argument so tests get the raw English string.
if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

// WP_User stub — BDPWR_User extends this
if (!class_exists('WP_User')) {
    class WP_User
    {
        public int $ID = 0;
        public array $roles = [];
        public string $user_email = '';

        public function __construct(int $id = 0)
        {
            $this->ID = $id;
        }
    }
}

// Load plugin files (defines functions/classes only; no WP calls at include-time)
require_once dirname(__DIR__, 2) . '/inc/functions.php';
require_once dirname(__DIR__, 2) . '/inc/class/class.user.php';
