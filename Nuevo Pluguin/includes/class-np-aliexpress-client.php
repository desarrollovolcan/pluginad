<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('NP_AliExpress_Client')) {
    class NP_AliExpress_Client
    {
        private $app_key;
        private $secret_key;
        private $tracking_id;

        public function __construct($app_key, $secret_key, $tracking_id)
        {
            $this->app_key = $app_key;
            $this->secret_key = $secret_key;
            $this->tracking_id = $tracking_id;
        }

        public function build_authorize_url($redirect_uri)
        {
            $base_url = 'https://oauth.aliexpress.com/authorize';
            $query = array(
                'response_type' => 'code',
                'client_id' => $this->app_key,
                'redirect_uri' => $redirect_uri,
            );

            return $base_url . '?' . http_build_query($query);
        }

        public function request($endpoint, $params = array())
        {
            $params['tracking_id'] = $this->tracking_id;

            $url = add_query_arg($params, $endpoint);
            $response = wp_remote_get($url, array('timeout' => 20));

            if (is_wp_error($response)) {
                return $response;
            }

            return wp_remote_retrieve_body($response);
        }
    }
}
