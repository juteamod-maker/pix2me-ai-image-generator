<?php
/*
Plugin Name: Pix2me AI Image Generator
Description: Generate images using AI technology, supporting multiple models and customization options. This plugin is exclusively intended for use on English-language sites.
Version: 1.3.1
Author: Pix2me
Text Domain: pix2me-ai-image-generator
Domain Path: /languages
*/

// 防止直接访问
defined('ABSPATH') or die('No script kiddies please!');

// 定义常量
define('PIX2ME_AI_IMAGE_GENERATOR_VERSION', '1.2.1');
define('PIX2ME_AI_IMAGE_GENERATOR_PATH', plugin_dir_path(__FILE__));
define('PIX2ME_AI_IMAGE_GENERATOR_URL', plugin_dir_url(__FILE__));
define('PIX2ME_AI_IMAGE_GENERATOR_FILE', __FILE__);

// 加载所需文件
require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/admin-settings.php';
require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/ajax-handlers.php';
require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/api-handler.php';
require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/shortcodes.php';
require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/enqueue-scripts.php';
require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/user-logic.php';
require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/cache-helper.php';
require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/helpers.php';
require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/helper.php';

// 插件激活钩子
register_activation_hook(__FILE__, 'pix2me_ai_image_generator_activate');

// 激活函数
function pix2me_ai_image_generator_activate() {
    // 设置默认选项
    $default_options = array(
        'api_type' => 'stable_diffusion',
        'openai_api_key' => '',
        'openai_model' => 'dall-e-3',
        'stable_diffusion_api_key' => '',
        'stable_diffusion_model' => 'stable-diffusion-v1-5',
        'generation_cost' => 1,
        'integration_type' => 'mycred',
        'mycred_point_type' => 'mycred_default',
        'media_library_access' => 'subscriber'
    );
    
    // 仅在选项不存在时添加
    if (!get_option('pix2me_ai_image_generator_options')) {
        add_option('pix2me_ai_image_generator_options', $default_options);
    }
    
    // 创建上传目录
    $upload_dir = wp_upload_dir();
    $ai_images_dir = $upload_dir['basedir'] . '/ai-images';
    if (!file_exists($ai_images_dir)) {
        wp_mkdir_p($ai_images_dir);
    }
}

// 插件初始化
function pix2me_ai_image_generator_init() {
    load_plugin_textdomain('pix2me-ai-image-generator', false, basename(dirname(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'pix2me_ai_image_generator_init');

// 如果存在myCRED插件，加载myCRED钩子
function pix2me_load_mycred_hooks() {
    if (function_exists('mycred_get_users_balance') && defined('MYCRED_DEFAULT_TYPE_KEY')) {
        require_once PIX2ME_AI_IMAGE_GENERATOR_PATH . 'includes/mycred-hooks.php';
    }
}
add_action('plugins_loaded', 'pix2me_load_mycred_hooks', 20); 