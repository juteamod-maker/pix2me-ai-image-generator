<?php
/**
 * 短代码相关功能
 */

// 防止直接访问
defined('ABSPATH') or die('No script kiddies please!');

/**
 * 渲染AI图像生成页面
 */
function pix2me_ai_image_generator_shortcode($atts) {
    // 加载脚本和样式
    wp_enqueue_script('pix2me-generate-page-js');
    wp_enqueue_style('pix2me-generate-page-css');
    
    // 处理属性
    $atts = shortcode_atts(array(
        'model' => '', // 默认使用插件设置
    ), $atts, 'pix2me_ai_image_generator');
    
    // 输出模板
    ob_start();
    include PIX2ME_AI_IMAGE_GENERATOR_PATH . 'templates/generate-page.php';
    return ob_get_clean();
}
add_shortcode('pix2me_ai_image_generator', 'pix2me_ai_image_generator_shortcode'); 

/**
 * 渲染用户作品库页面
 */
function pix2me_ai_asset_library_shortcode($atts) {
    // 加载样式和脚本
    wp_enqueue_style('pix2me-generate-page-css');
    wp_enqueue_style('pix2me-asset-library-css');
    wp_enqueue_style('dashicons');
    wp_enqueue_script('jquery');
    wp_enqueue_script('pix2me-asset-library-js');
    
    // 处理属性
    $atts = shortcode_atts(array(), $atts, 'pix2me_ai_asset_library');
    
    // 输出模板
    ob_start();
    include PIX2ME_AI_IMAGE_GENERATOR_PATH . 'templates/asset-library.php';
    return ob_get_clean();
}
add_shortcode('pix2me_ai_asset_library', 'pix2me_ai_asset_library_shortcode');

/**
 * 渲染用户生成历史页面
 */
function pix2me_ai_history_shortcode($atts) {
    // 加载样式
    wp_enqueue_style('pix2me-generate-page-css');
    
    // 处理属性
    $atts = shortcode_atts(array(), $atts, 'pix2me_ai_history');
    
    // 输出模板
    ob_start();
    include PIX2ME_AI_IMAGE_GENERATOR_PATH . 'templates/history-page.php';
    return ob_get_clean();
}
add_shortcode('pix2me_ai_history', 'pix2me_ai_history_shortcode'); 

/**
 * 渲染AI图像编辑页面
 */
function pix2me_ai_image_edit_shortcode($atts) {
    // 加载脚本和样式
    wp_enqueue_script('pix2me-image-edit-js');
    wp_enqueue_style('pix2me-generate-page-css');
    
    // 处理属性
    $atts = shortcode_atts(array(), $atts, 'pix2me_ai_image_edit');
    
    // 输出模板
    ob_start();
    include PIX2ME_AI_IMAGE_GENERATOR_PATH . 'templates/image-edit-page.php';
    return ob_get_clean();
}
add_shortcode('pix2me_ai_image_edit', 'pix2me_ai_image_edit_shortcode'); 

/**
 * 渲染创意库页面
 */
function pix2me_creative_gallery_shortcode($atts) {
    wp_enqueue_style('pix2me-asset-library-css');
    wp_enqueue_style('dashicons');
    wp_enqueue_script('jquery');
    wp_enqueue_script('pix2me-creative-gallery-js');
    ob_start();
    include PIX2ME_AI_IMAGE_GENERATOR_PATH . 'templates/creative-gallery.php';
    return ob_get_clean();
}
add_shortcode('creative_gallery', 'pix2me_creative_gallery_shortcode'); 