<?php

namespace Letscms\Controllers;

defined('ABSPATH') || exit;

use Letscms\LetscmsController;

class LetscmsUser extends LetscmsController
{
    public function login($request)
    {
        global $wpdb;

        $inputs = $this->helpers->sanitizedData($request->get_params());

        if (!isset($inputs['username']) || empty($inputs['username'])) {
            $errors['username'] = __('username is required.', 'lwra');
        }

        if (!isset($inputs['password']) || empty($inputs['password'])) {
            $errors['password'] = __('password is required.', 'lwra');
        }

        if (!empty($errors)) {
            $this->response['errors'] = $errors;
            return new \WP_REST_Response($this->response, 200);
        }

        $sql = "SELECT * FROM {$wpdb->prefix}users WHERE user_email = '" . $inputs['username'] . "' OR user_login = '" . $inputs['username'] . "' ";
        $user = $wpdb->get_row($sql);

        if (!$user ||  !wp_check_password($inputs['password'], $user->user_pass)) {
            $this->response['message'] = __('invalid login details.', 'lwra');
            return new \WP_REST_Response($this->response, 200);
        }

        unset($user->user_pass);
        $current_time = time();
        update_user_meta($user->ID, 'last_login', $current_time);
        $user->last_login = $current_time;
        $this->response['letscms_token'] = $this->jwt->generateToken($user);
        unset($user->last_login);
        $user->first_name = get_user_meta($user->ID, 'first_name', true);
        $user->last_name = get_user_meta($user->ID, 'last_name', true);
        $this->response['user'] = $user;
        $this->response['status'] = true;
        return new \WP_REST_Response(apply_filters('lets_woo_api_user_login_response', $this->response));
    }

    public function register($request)
    {

        global $wpdb;
        $reserved_keywords = ['admin', 'superadmin'];
        $inputs = $this->helpers->sanitizedData($request->get_params());
        if (!isset($inputs['username']) || empty($inputs['username'])) {
            $errors['username'] = __('username is required', 'lwra');
        } else if (username_exists($inputs['username'])) {
            $errors['username'] = __('username is already exists', 'lwra');
        } else if (in_array($inputs['username'], $reserved_keywords)) {
            $errors['username'] = __('this username is reserved for admin only', 'lwra');
        } else if (!ctype_alnum($inputs['username'])) {
            $errors['username'] = __('username can contains only charaters and numbers only', 'lwra');
        }

        if (!isset($inputs['first_name']) || empty($inputs['first_name'])) {
            $errors['first_name'] = __('first name is required', 'lwra');
        }

        if (!isset($inputs['last_name']) || empty($inputs['last_name'])) {
            $errors['last_name'] = __('last name is required', 'lwra');
        }

        if (!isset($inputs['password']) || empty($inputs['password'])) {
            $errors['password'] = __('password is required', 'lwra');
        } else if (strlen($inputs['password']) < 8) {
            $errors['password'] = __('password should be minimum 8 charaters long', 'lwra');
        }

        if (!isset($inputs['email']) || empty($inputs['email'])) {
            $errors['email'] = __('email is required', 'lwra');
        } else if (!filter_var($inputs['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = __('Invalid email format', 'lwra');
        } else if (email_exists($inputs['email'])) {
            $errors['email'] = __('email already exists', 'lwra');
        }

        $errors = apply_filters('lets_woo_api_register_before_send_errors', $errors, $inputs);

        if (!empty($errors)) {
            $this->response['errors'] = $errors;
            return new \WP_REST_Response($this->response, 200);
        }

        do_action('lets_woo_api_before_user_register', $inputs);

        $user = array(
            'user_login' => $inputs['username'],
            'user_pass' => $inputs['password'],
            'first_name' => $inputs['first_name'],
            'last_name' => $inputs['last_name'],
            'user_email' => $inputs['email']
        );

        $user_id = wp_insert_user($user);
        wp_new_user_notification($user_id, $inputs['password']);

        do_action('lets_woo_api_after_user_register', $user_id, $inputs);

        $current_time = time();
        $user_data = get_user_by('ID', $user_id)->data;
        $user_data->last_login = $current_time;
        update_user_meta($user_id, 'last_login', $current_time);
        $this->response['letscms_token'] = $this->jwt->generateToken($user_data);
        unset($user_data->last_login);
        unset($user_data->user_pass);

        $this->response['user'] = $user_data;
        $this->response['message'] = __("User Register Successfully", "lwra");
        $this->response['status'] = true;
        return new \WP_REST_Response(apply_filters('lets_woo_api_user_register_response', $this->response));
    }

    public function getAddresses($request)
    {

        global $wpdb;
        $user = $request->current_user;
        $inputs = $this->helpers->sanitizedData($request->get_params());
        // print_r($inputs);
        // die;
        // if($inputs['type'] != 'billing' && $inputs['type'] != 'shipping') {
        if ($inputs['type'] != 'billing') {
            return new \WP_REST_Response([
                "code" => "rest_no_route",
                "message" => __("No route was found matching the URL and request method", "lwra"),
                "data" => [
                    "status" => 404
                ]
            ], 404);
        }
        $type = $inputs['type'];
        $address_fields = $wpdb->get_results("SELECT meta_key, meta_value FROM {$wpdb->prefix}usermeta WHERE meta_key LIKE '{$type}_%' AND user_id='$user->ID'");
        $address = [];
        foreach ($address_fields as $value) {
            $key = str_replace($type . '_', '', $value->meta_key);
            $address[$key] = $value->meta_value;
        }

        $countries_obj   = new \WC_Countries();
        $countries   = $countries_obj->__get('countries');
        $states = $countries_obj->get_states();

        $data = [
            'address' => $address,
            'countries' => $countries,
            'states' => $states,
        ];

        $this->response['status'] = true;
        $this->response['data'] = $data;
        return new \WP_REST_Response($this->response, 200);
    }

    public function saveAddresses($request)
    {
        global $wpdb;
        $user = $request->current_user;
        $errors = [];
        $inputs = $this->helpers->sanitizedData($request->get_params());
        // if($inputs['type'] != 'billing' && $inputs['type'] != 'shipping') {
        if ($inputs['type'] != 'billing') {
            return new \WP_REST_Response([
                "code" => "rest_no_route",
                "message" => "No route was found matching the URL and request method",
                "data" => [
                    "status" => 404
                ]
            ], 404);
        }
        $type = $inputs['type'];
        unset($inputs['type']);
        if (!isset($inputs['first_name']) || empty($inputs['first_name'])) {
            $errors['first_name'] = __('first name is required field.', 'lwra');
        }
        if (!isset($inputs['last_name']) || empty($inputs['last_name'])) {
            $errors['last_name'] = __('last name is required field.', 'lwra');
        }
        if (!isset($inputs['address_1']) || empty($inputs['address_1'])) {
            $errors['address_1'] = __('Address 1 is required field.', 'lwra');
        }
        if (!isset($inputs['city']) || empty($inputs['city'])) {
            $errors['city'] = __('City is required field.', 'lwra');
        }
        if (!isset($inputs['state']) || empty($inputs['state'])) {
            $errors['state'] = __('State is required field.', 'lwra');
        }
        if (!isset($inputs['postcode']) || empty($inputs['postcode'])) {
            $errors['postcode'] = __('Postcode is required field.', 'lwra');
        }
        if (!isset($inputs['country']) || empty($inputs['country'])) {
            $errors['country'] = __('Country is required field.', 'lwra');
        }
        if (!isset($inputs['email']) || empty($inputs['email'])) {
            $errors['email'] = __('email is required', 'lwra');
        } else if (!filter_var($inputs['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = __('Invalid email format', 'lwra');
        } else if (($user_id = email_exists($inputs['email'])) && ($user_id != $user->ID)) {
            $errors['email'] = __('email already exists', 'lwra');
        }
        if (!isset($inputs['phone']) || empty($inputs['phone'])) {
            $errors['phone'] = __('Phone is required field.', 'lwra');
        }

        if (empty($errors)) {
            foreach ($inputs as $key => $value) {
                update_user_meta($user->ID, $type . '_' . $key, $value);
            }
            $this->response['status'] = true;
            $this->response['message'] = __('Address updated successfully.', 'lwra');
        } else {
            $this->response['status'] = false;
            $this->response['errors'] = $errors;
        }
        return new \WP_REST_Response($this->response, 200);
    }

    public function updateAccountDetails($request)
    {
        global $wpdb;
        $user = $request->current_user;
        $inputs = $this->helpers->sanitizedData($request->get_params());
        $user_data = get_user_by('ID', $user->ID)->data;
        if (!isset($inputs['first_name']) || empty($inputs['first_name'])) {
            $errors['first_name'] = __('first name is required', 'lwra');
        }
        if (!isset($inputs['last_name']) || empty($inputs['last_name'])) {
            $errors['last_name'] = __('last name is required', 'lwra');
        }
        if (!isset($inputs['display_name']) || empty($inputs['display_name'])) {
            $errors['display_name'] = __('display name is required', 'lwra');
        }

        if (!isset($inputs['user_email']) || empty($inputs['user_email'])) {
            $errors['user_email'] = __('email is required', 'lwra');
        } else if (!filter_var($inputs['user_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['user_email'] = __('Invalid email format', 'lwra');
        } else if (($user_id = email_exists($inputs['user_email'])) && ($user_id != $user->ID)) {
            $errors['user_email'] = __('email already exists', 'lwra');
        }

        if (isset($inputs['password']) && !empty($inputs['password'])) {
            if (!isset($inputs['old_password']) || empty($inputs['old_password'])) {
                $errors['old_password'] = __('old password is required to update your password', 'lwra');
            } elseif (!wp_check_password($inputs['old_password'], $user_data->user_pass)) {
                $errors['old_password'] = __('incorrect old password.', 'lwra');
            } elseif (isset($inputs['confirm_password']) && !empty($inputs['confirm_password'])) {
                $errors['confirm_password'] = __('confirm password is required field.', 'lwra');
            } elseif ($inputs['password'] != $inputs['confirm_password']) {
                $errors['confirm_password'] = __('confirm password does not matched.', 'lwra');
            }
        }

        if (!empty($errors)) {
            $this->response['errors'] = $errors;
            return new \WP_REST_Response($this->response, 200);
        }

        $user_update_sql = "UPDATE `{$wpdb->prefix}users` SET ";

        if (isset($inputs['password']) && !empty($inputs['password'])) {
            $user_update_sql .= " `user_pass`=MD5('" . $inputs['password'] . "'), ";
        }

        $user_update_sql .= " `user_email`='" . $inputs['user_email'] . "', `display_name`='" . $inputs['display_name'] . "' WHERE ID='$user->ID'";
        $update = $wpdb->query($user_update_sql);

        update_user_meta($user->ID, 'first_name', $inputs['first_name']);
        update_user_meta($user->ID, 'last_name', $inputs['last_name']);
        $user_data->first_name = $inputs['first_name'];
        $user_data->last_name = $inputs['last_name'];
        unset($user_data->user_pass);
        $this->response['status'] = true;
        $this->response['data'] = $user_data;
        $this->response['message'] = __('Account details updated successfully.', 'lwra');
        return new \WP_REST_Response($this->response, 200);
    }

    public function sendPassWord($request)
    {
        global $wpdb;
        $inputs = $this->helpers->sanitizedData($request->get_params());

        if (!isset($inputs['username']) || empty($inputs['username'])) {
            $errors['username'] = __('username is required.', 'lwra');
        }

        if (!empty($errors)) {
            $this->response['errors'] = $errors;
            return new \WP_REST_Response($this->response, 200);
        }

        $sql = "SELECT * FROM {$wpdb->prefix}users WHERE user_email = '" . $inputs['username'] . "' OR user_login = '" . $inputs['username'] . "' ";
        $user = $wpdb->get_row($sql);

        if (!$user) {
            $this->response['message'] = __('Inavalid Email or username', 'lwra');
            return new \WP_REST_Response($this->response, 200);
        }

        $password = substr(md5(microtime()), 0, 10);

        $user_update_sql = "UPDATE `{$wpdb->prefix}users` SET  `user_pass`=MD5('" . $password . "') WHERE ID='$user->ID'";
        $update = $wpdb->query($user_update_sql);

        if ($update) {
            add_filter('wp_mail_content_type', function () {
                return "text/html";
            });
            $subject = "New password - " . get_bloginfo('name');
            $body = "Your <b>" . get_bloginfo('name') . "</b> account password has been update your new password is : <br> <h3>" . $password . "</h3>";

            wp_mail($user->user_email, $subject, $body);
        }
        $this->response['message'] = __('New password sent your email.', 'lwra');
        $this->response['status'] = true;
        return new \WP_REST_Response($this->response);
    }
}
