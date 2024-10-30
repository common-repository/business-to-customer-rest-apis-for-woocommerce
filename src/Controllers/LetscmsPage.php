<?php

namespace Letscms\Controllers;

defined('ABSPATH') || exit;

use Letscms\LetscmsController;

class LetscmsPage extends LetscmsController
{
    public function getContent($request)
    {
        global $wpdb;

        $inputs = $this->helpers->sanitizedData($request->get_params());
        $app_pages = [
            'tnc' => 'tnc_page',
            'about' => 'about_page',
            'privacy_policy' => 'privacy_policy_page',
            'return_policy' => 'return_policy_page'
        ];

        if (!in_array($inputs['name'], array_keys($app_pages))) {
            return new \WP_REST_Response([
                "code" => "rest_no_route",
                "message" => __("No route was found matching the URL and request method"),
                "data" => [
                    "status" => 404
                ]
            ], 404);
        }

        $page_name = $app_pages[$inputs['name']];
        $settings = get_option('lwra_general_settings');
        $page_id = $settings[$page_name];

        $page = get_page($page_id);
        if (!empty($page)) {
            $data = [
                'title' => $page->post_title,
                'description' => do_shortcode($page->post_content),
            ];
        } else {
            $data = [
                'title' => __('Page Not Found.', 'lwra'),
                'description' => '',
            ];
        }

        $this->response['status'] = true;
        $this->response['data'] = $data;

        return new \WP_REST_Response($this->response, 200);
    }
}
