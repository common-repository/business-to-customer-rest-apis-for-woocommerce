<?php

namespace Letscms;

defined('ABSPATH') || exit;

use Letscms\LetscmsHelpers;

/**
 * Settings Opertaions in admin
 */
class LetscmsAdminSettings
{
    private $view_path = LWRA_ABSPATH . '/view/admin/';

    public $helpers;

    function __construct()
    {
        $this->helpers = new LetscmsHelpers();
        add_action('wp_ajax_save_lwra_general_settings', [$this, 'saveGeneralSettings']);
        add_action('wp_ajax_save_shop_page_slider_settings', [$this, 'saveShopPageSlider']);
        add_action('wp_ajax_remove_shop_page_slider_slide', [$this, 'removeShopPageSlide']);
    }

    public function general()
    {

        include $this->view_path . 'general.php';
    }

    public function saveGeneralSettings()
    {
        $post_data = $this->helpers->sanitizedData($_POST);
        if (!isset($post_data['license_key']) ||  empty($post_data['license_key'])) {
            $errors['license_key'] = __('license key is required.', 'lwra');
        }
        if (!empty($errors)) {
            echo json_encode(['status' => false, 'errors' => $errors]);
            die;
        }
        unset($post_data['action']);
        update_option('lwra_general_settings', $post_data);

        echo json_encode(['status' => true, 'message' => __('Settings saved successfully.', 'lwra')]);
        die;
    }

    public function shopPageSlider()
    {
        include $this->view_path . 'shop_page_slider.php';
    }

    public function saveShopPageSlider()
    {
        $post_data = $this->helpers->sanitizedData($_POST);
        if (!isset($post_data['title']) ||  empty($post_data['title'])) {
            $errors['title'] = __('title is required.', 'lwra');
        }

        if (!isset($post_data['subtitle']) ||  empty($post_data['subtitle'])) {
            $errors['subtitle'] = __('subtitle is required.', 'lwra');
        }

        if (isset($post_data['link_type']) && !empty($post_data['link_type'])) {
            if (!isset($post_data['link']) ||  empty($post_data['link'])) {
                $errors['link'] = __('link is required.', 'lwra');
            }
        }

        if (!isset($_FILES['image']) || empty($_FILES['image']['size'])) {
            $errors['image'] = __('image is required.', 'lwra');
        } elseif ($_FILES["image"]["size"] > 1048576) {
            $errors['image'] = __('image file size too large.', 'lwra');
        } else {
            $upalod_dir = wp_get_upload_dir()['basedir'];
            if (!file_exists($upalod_dir . '/lwra_shop_slider')) {
                mkdir($upalod_dir . '/lwra_shop_slider/', 0777, true);
                // var_dump(file_exists($upalod_dir.'/lwra_shop_slider'));
                // die;
            }
            $filename = sanitize_file_name($_FILES["image"]["name"]);
            $image = $upalod_dir . '/lwra_shop_slider/' . basename($filename);
            $imageFileType = strtolower(pathinfo($image, PATHINFO_EXTENSION));
            if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "pdf") {
                $errors['image'] = __("Sorry, only JPG, JPEG, PNG GIF & PDF files are allowed for image.", 'bmp');
            }
        }

        if (!empty($errors)) {
            echo json_encode(['status' => false, 'errors' => $errors]);
            die;
        }

        $image_path = "/lwra_shop_slider/slide_" . time() . '.' . $imageFileType;
        if (!move_uploaded_file($_FILES["image"]["tmp_name"], $upalod_dir . $image_path)) {
            $errors['image'] = __("Sorry, there was an error uploading your image.", 'bmp');
        }

        if (empty($image_path)) {
            echo json_encode(['status' => false, 'errors' => $errors]);
            die;
        }

        $slider_data = get_option('lwra_slider_settings');
        unset($post_data['action']);
        $post_data['image'] = $image_path;

        if ($post_data['link_type'] == 'product_category') {
            $term = get_term_by('ID', $post_data['link'], 'product_cat');
            $post_data['slug'] = $term->slug;
        }

        $slider_data[time()] = $post_data;

        update_option('lwra_slider_settings', $slider_data);
        echo json_encode(['status' => true, 'message' => __('slider image saved successfully.', 'lwra')]);
        die;
    }

    public function removeShopPageSlide()
    {
        $post_data = $this->helpers->sanitizedData($_POST);
        $slider_data = get_option('lwra_slider_settings');
        $slide_data = $slider_data[$post_data['key']];
        $upalod_dir = wp_get_upload_dir();
        unlink($upalod_dir['basedir'] . $slide_data['image']);
        unset($slider_data[$post_data['key']]);
        update_option('lwra_slider_settings', $slider_data);
        echo json_encode(['status' => true, 'message' => __('slide removed successfully.', 'lwra')]);
        die;
    }
}
