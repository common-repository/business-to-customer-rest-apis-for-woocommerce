<?php

/**
 * Plugin Name: Business to Customer REST APIs For WooCommerce - B2C REST API
 * Plugin URI: https://www.mlmtrees.com/product/woocommerce-b2c-rest-api/
 * Description: This plugin contains custom Woocommerce REST APIs with JWT (RS256) for frontend like product listing, add to cart, order placing, order lising etc.  
 * Version: 2.0
 * Author: LetsCMS Pvt. Ltd
 * Author URI: https://letscms.com
 * Text Domain: lwra
 * Domain Path: /i18n/languages/
 * Requires at least: 6.2
 * Requires PHP: 8.0
 *
 * @package LWRA
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define LWRA_PLUGIN_FILE.
if (!defined('LWRA_PLUGIN_FILE')) {
    define('LWRA_PLUGIN_FILE', __FILE__);
}
if (!defined('LWRA_ABSPATH')) {
    define('LWRA_ABSPATH', dirname(__FILE__));
}

include_once dirname(__FILE__) . '/vendor/autoload.php';

use Letscms\LetscmsApi;

function LWRA()
{

    return LetscmsApi::instance();
}

// Global for backwards compatibility.
$GLOBALS['LWRA'] = LWRA();
