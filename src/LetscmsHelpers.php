<?php

namespace Letscms;

defined('ABSPATH') || exit;

/**
 * Helper functions
 */
class LetscmsHelpers
{
    public function sanitizedData($array)
    {
        $senitized_data = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $senitized_data[$key] = $this->sanitizedData($value);
            } else {
                if ($key == 'email' || $key == 'user_email') {
                    $senitized_data[$key] = sanitize_email($value);
                } else {
                    $senitized_data[$key] = sanitize_text_field($value);
                }
            }
        }
        return $senitized_data;
    }

    public function cartDataHash($product)
    {
        return md5(
            wp_json_encode(
                apply_filters(
                    'woocommerce_cart_item_data_to_validate',
                    array(
                        'type'       => $product->get_type(),
                        'attributes' => 'variation' === $product->get_type() ? $product->get_variation_attributes() : '',
                    ),
                    $product
                )
            )
        );
    }

    public function generateCartId($product_id, $variation_id = 0, $variation = array(), $cart_item_data = array())
    {
        $id_parts = array($product_id);

        if ($variation_id && 0 !== $variation_id) {
            $id_parts[] = $variation_id;
        }

        if (is_array($variation) && !empty($variation)) {
            $variation_key = '';
            foreach ($variation as $key => $value) {
                $variation_key .= trim($key) . trim($value);
            }
            $id_parts[] = $variation_key;
        }

        if (is_array($cart_item_data) && !empty($cart_item_data)) {
            $cart_item_data_key = '';
            foreach ($cart_item_data as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = http_build_query($value);
                }
                $cart_item_data_key .= trim($key) . trim($value);
            }
            $id_parts[] = $cart_item_data_key;
        }
        return apply_filters('woocommerce_cart_id', md5(implode('_', $id_parts)), $product_id, $variation_id, $variation, $cart_item_data);
    }

    public function getCustomerAddresses($user_id)
    {
        $user_meta = get_user_meta($user_id);
        $customer = [
            "id" => $user_id,
            // "date_modified" => "",
            "postcode" => isset($user_meta['billing_postcode']) ? $user_meta['billing_postcode'][0] : "",
            "city" => isset($user_meta['billing_city']) ? $user_meta['billing_city'][0] : "",
            "address_1" => isset($user_meta['billing_address_1']) ? $user_meta['billing_address_1'][0] : "",
            "address" => isset($user_meta['billing_address_1']) ? $user_meta['billing_address_1'][0] : "",
            "address_2" => isset($user_meta['billing_address_2']) ? $user_meta['billing_address_2'][0] : "",
            "state" => isset($user_meta['billing_state']) ? $user_meta['billing_state'][0] : "",
            "country" => isset($user_meta['billing_country']) ? $user_meta['billing_country'][0] : "",
            "shipping_postcode" => isset($user_meta['shipping_postcode']) ? $user_meta['shipping_postcode'][0] : "",
            "shipping_city" => isset($user_meta['shipping_city']) ? $user_meta['shipping_city'][0] : "",
            "shipping_address_1" => isset($user_meta['shipping_address_1']) ? $user_meta['shipping_address_1'][0] : "",
            "shipping_address" => isset($user_meta['shipping_address']) ? $user_meta['shipping_address'][0] : "",
            "shipping_address_2" => isset($user_meta['shipping_address_2']) ? $user_meta['shipping_address_2'][0] : "",
            "shipping_state" => isset($user_meta['shipping_state']) ? $user_meta['shipping_state'][0] : "",
            "shipping_country" => isset($user_meta['shipping_country']) ? $user_meta['shipping_country'][0] :  "",
            // "is_vat_exempt" => "",
            // "calculated_shipping" => "0",
            "first_name" => isset($user_meta['billing_first_name']) ? $user_meta['billing_first_name'][0] : "",
            "last_name" => isset($user_meta['billing_last_name']) ? $user_meta['billing_last_name'][0] : "",
            "company" => isset($user_meta['billing_company']) ? $user_meta['billing_company'][0] : "",
            "phone" => isset($user_meta['billing_phone']) ? $user_meta['billing_phone'][0] : "",
            "email" => isset($user_meta['billing_phone']) ? $user_meta['billing_email'][0] : "",
            "shipping_first_name" => isset($user_meta['shipping_first_name']) ? $user_meta['shipping_first_name'][0] : "",
            "shipping_last_name" => isset($user_meta['shipping_last_name']) ? $user_meta['shipping_last_name'][0] : "",
            "shipping_company" => isset($user_meta['shipping_company']) ? $user_meta['shipping_company'][0] : ""
        ];
        return $customer;
    }
}
