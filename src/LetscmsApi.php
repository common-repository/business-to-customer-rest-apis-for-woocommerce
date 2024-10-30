<?php

namespace Letscms;

defined('ABSPATH') || exit;

use \Letscms\LetscmsJwtAuth;
use Letscms\LetscmsAdminSettings;

include_once(ABSPATH . 'wp-admin/includes/plugin.php');
// include_once(ABSPATH . 'wp-includes/rest-api.php');
class LetscmsApi
{
    public $version = '1.0.0';
    protected static $_instance = null;
    public $session = null;
    public $query = null;
    public $product_factory = null;
    public $countries = null;
    public $integrations = null;
    public $cart = null;
    public $customer = null;
    public $structured_data = null;
    public $deprecated_hook_handlers = array();
    protected $namespace = 'letscms/v1';
    public $routes;

    function __construct()
    {
        $this->init();
        $this->adminSettings = new LetscmsAdminSettings();
    }

    public static function instance()
    {


        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            add_action('admin_notices', function () {
?>
                <div class="error">
                    <?php _e('<p>Sorry, but <strong>LWRA (LetsCMS Woocommerce REST APIs )</strong> requires the <strong>woocommerce</strong> to be installed and active.</p>', 'LWRA'); ?>
                </div>
<?php
            });
        } else {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }
    }

    public function init()
    {
        $this->defineConstants();
        $this->routes = include LWRA_ABSPATH . '/routes.php';
        add_action('rest_api_init', [$this, 'registerRoutes']);
        add_filter('rest_dispatch_request', [$this, 'protectRoutes'], 10, 4);
        add_filter('plugin_action_links_' . plugin_basename(LWRA_PLUGIN_FILE), [$this, 'pluginActionLinks']);
        add_action('admin_menu', [$this, 'adminMenu'], 9);
        add_action('admin_enqueue_scripts', [$this, 'adminEnqueScript'], 9);
    }


    public function adminEnqueScript()
    {
        if (is_admin()) {
            wp_enqueue_style('lets_bootstrap', $this->plugin_url() . '/assets/css/lets-boot.min.css');
        }
    }

    public function plugin_url()
    {
        return untrailingslashit(plugins_url('/', LWRA_PLUGIN_FILE));
    }

    public function plugin_path()
    {
        return untrailingslashit(plugin_dir_path(LWRA_PLUGIN_FILE));
    }

    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    private function defineConstants()
    {
        // $this->define('LWRA_ABSPATH', dirname(LWRA_PLUGIN_FILE) . '/');
        $this->define('LWRA_PLUGIN_BASENAME', plugin_basename(LWRA_PLUGIN_FILE));
        $this->define('LWRA_VERSION', $this->version);
    }

    public function registerRoutes()
    {
        foreach ($this->routes as $route) {
            $callbackBase = '';
            $callback = explode('@', $route['callback']);
            $callbackClass = $callback[0];
            $callbackFunction = $callback[1];
            register_rest_route($this->namespace, $route['endpoint'], array(
                'methods' => $route['method'],
                'callback' => [new $callbackClass, $callbackFunction],
                'permission_callback' => '__return_true'
            ));
        }
    }

    public function protectRoutes($dispatch_result, $request, $route, $hndlr)
    {
        $target_base = '/' . $this->namespace;
        $pattern1 = untrailingslashit($target_base);
        $pattern2 = trailingslashit($target_base);

        $not_protected_routes = array_filter($this->routes, function ($val) {
            if (!$val['auth']) return $val;
        });

        $non_auth_endpoints = [];
        foreach ($not_protected_routes as $value) {
            $endpoint = $target_base . $value['endpoint'];
            if ($route == $endpoint) {
                return $dispatch_result;
            }
            $non_auth_endpoints[] = $endpoint;
        }

        if ($pattern1 !== $route && $pattern2 !== substr($route, 0, strlen($pattern2)))
            return $dispatch_result;

        // check jwt auth token
        if ($user = $this->verifyLetscmsToken()) {
            $request->current_user = $user;
            return $dispatch_result;
        }

        $response['status'] = false;
        $response['errors'] = [];
        $response['message'] = __('Please provide letscms_token in header', 'lwra');
        return new \WP_REST_Response($response, 401);
    }

    public function verifyLetscmsToken()
    {
        $headers = apache_request_headers();

        if (isset($headers['letscms_token']) && !empty($headers['letscms_token']) && $user = LetscmsJwtAuth::verifyToken($headers['letscms_token'])) {
            if (get_user_meta($user->ID, 'last_login', true) == $user->last_login) {
                return $user;
            }
        }
        return false;
    }

    public function pluginActionLinks($links)
    {
        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=lwra-settings') . '" aria-label="' . esc_attr__('View LWRA settings', 'lwra') . '">' . esc_html__('Settings', 'lwra') . '</a>',
        );
        return array_merge($action_links, $links);
    }

    public function adminMenu()
    {
        global $menu;
        add_menu_page(__('REST API General Settings', 'lwra'), __('REST API Settings', 'lwra'), 'administrator', 'lwra-settings', [$this->adminSettings, 'general'], 'dashicons-rest-api', 59);
        add_submenu_page('lwra-settings', __('REST API General Settings', 'lwra'), __('General Settings', 'lwra'), 'administrator', 'lwra-settings',  [$this->adminSettings, 'general']);
        add_submenu_page('lwra-settings', __('Shop Page Slider', 'lwra'), __('Shop Page Slider', 'lwra'), 'administrator', 'lwra-shop-page-slider',  [$this->adminSettings, 'shopPageSlider']);
    }
}
