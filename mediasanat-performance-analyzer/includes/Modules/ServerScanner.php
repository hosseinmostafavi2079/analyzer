<?php
namespace Mediasanat\PA\Modules;

if ( ! defined( 'ABSPATH' ) ) exit;

class ServerScanner {
    
    public function get_server_health() {
        return [
            'php_version'      => PHP_VERSION,
            'memory_limit'     => ini_get('memory_limit'),
            'max_exec_time'    => ini_get('max_execution_time'),
            'max_input_vars'   => ini_get('max_input_vars'),
            'redis_active'     => class_exists('Redis'),
            'memcached_active' => class_exists('Memcached'),
        ];
    }
}