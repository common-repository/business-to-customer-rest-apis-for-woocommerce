<?php

namespace Letscms;

defined('ABSPATH') || exit;

use Letscms\LetscmsHelpers;
use Letscms\LetscmsJwtAuth;

/**
 * Main Controller Class
 */
class LetscmsController extends \WP_REST_Controller
{
    public $helpers;
    public $jwt;

    public $response = [
        'status' => false,
        'errors' => [],
        'message' => ""
    ];

    function __construct()
    {
        $this->helpers = new LetscmsHelpers();
        $this->jwt = new LetscmsJwtAuth();
    }
}
