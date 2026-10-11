<?php
if (!defined('ABSPATH')) exit;

class Abtal_Public {
    private static $instance = null;

    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Public initialization
    }
}
