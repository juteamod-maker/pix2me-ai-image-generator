<?php
/**
 * 缓存助手函数
 */

// 防止直接访问
defined('ABSPATH') or die('No script kiddies please!');

/**
 * 为资源URL添加版本号，强制浏览器刷新缓存
 */
function pix2me_add_cache_busting_query($src, $handle) {
    if (strpos($handle, 'pix2me') !== false) {
        $src = add_query_arg('ver', PIX2ME_AI_IMAGE_GENERATOR_VERSION, $src);
    }
    return $src;
}

/**
 * 注册缓存控制钩子
 */
function pix2me_setup_cache_control() {
    // 为脚本和样式添加版本号
    add_filter('script_loader_src', 'pix2me_add_cache_busting_query', 10, 2);
    add_filter('style_loader_src', 'pix2me_add_cache_busting_query', 10, 2);
}
add_action('init', 'pix2me_setup_cache_control');
