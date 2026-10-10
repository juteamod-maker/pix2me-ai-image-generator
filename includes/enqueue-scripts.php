<?php
/**
 * 添加版本控制字符串，用于强制刷新缓存
 */
function pix2me_get_version_string() {
    // 使用常规版本号加时间戳
    $timestamp = get_option('pix2me_resource_timestamp');
    if (!$timestamp) {
        $timestamp = time();
        update_option('pix2me_resource_timestamp', $timestamp);
    }
    
    return PIX2ME_AI_IMAGE_GENERATOR_VERSION . '.' . $timestamp;
}

// 强制更新资源时间戳以刷新缓存
function pix2me_update_resource_timestamp() {
    update_option('pix2me_resource_timestamp', time());
}
// 在修复后强制刷新资源缓存
pix2me_update_resource_timestamp();

/**
 * 注册并排队所有前端脚本和样式
 */
function pix2me_enqueue_frontend_scripts() {
    // 注册样式
    wp_register_style(
        'pix2me-generate-page-css',
        PIX2ME_AI_IMAGE_GENERATOR_URL . 'assets/css/pix2me-generate-page.css',
        array(),
        PIX2ME_AI_IMAGE_GENERATOR_VERSION
    );
    
    // 注册脚本
    wp_register_script(
        'pix2me-generate-page-js',
        PIX2ME_AI_IMAGE_GENERATOR_URL . 'assets/js/pix2me-generate-page.js',
        array('jquery'),
        PIX2ME_AI_IMAGE_GENERATOR_VERSION,
        true
    );
    
    // 准备脚本数据
    $options = get_option('pix2me_ai_image_generator_options', array());
    $api_type = isset($options['api_type']) ? $options['api_type'] : 'stable_diffusion';
    $openai_model = isset($options['openai_model']) ? $options['openai_model'] : 'dall-e-3';
    $generation_cost = isset($options['generation_cost']) ? floatval($options['generation_cost']) : 1.0;
    
    // 获取用户积分
    $user_balance = 0;
    if (function_exists('ai_get_mycred_balance')) {
        $user_balance = intval(ai_get_mycred_balance());
    }
    
    // 检查模型类型
    $is_dalle3_active = ($api_type === 'openai' && $openai_model === 'dall-e-3');
    $is_gpt_image1_active = ($api_type === 'openai' && $openai_model === 'gpt-image-1');
    
    // 配置脚本数据
    $script_data = array(
        'baseCost'      => $generation_cost,
        'userBalance'   => $user_balance,
        'ajaxurl'       => admin_url('admin-ajax.php'),
        'nonce'         => wp_create_nonce('ai_image_generator_nonce'),
        'isDalle3'      => $is_dalle3_active,
        'isGptImage1'   => $is_gpt_image1_active,
        'apiType'       => $api_type,
        'model'         => $openai_model,
        'text'          => array(
            'insufficient_credits' => __('Insufficient credits. Please reduce the number of images or choose other options.', 'pix2me-ai-image-generator'),
            'no_images_generated'  => __('No images were generated. Please try again.', 'pix2me-ai-image-generator'),
            'error_generating'     => __('An error occurred while generating the image. Please try again.', 'pix2me-ai-image-generator'),
            'total_cost_label'     => '总成本:',
            'balance_label'        => '您的余额:',
            'credits_label'        => '积分',
            'error_calculating'    => __('Error calculating cost. Please check options or contact support.', 'pix2me-ai-image-generator'),
        )
    );
    
    // 加载本地化数据
    wp_localize_script('pix2me-generate-page-js', 'pix2meGeneratorData', $script_data);
}
add_action('wp_enqueue_scripts', 'pix2me_enqueue_frontend_scripts');

// 注册后台脚本
function pix2me_enqueue_admin_scripts($hook) {
    // 仅在插件页面加载
    if ('toplevel_page_pix2me-ai-image-generator' !== $hook) {
        return;
    }
    
    // 加载样式
    wp_enqueue_style(
        'pix2me-admin-css',
        PIX2ME_AI_IMAGE_GENERATOR_URL . 'assets/css/pix2me-admin.css',
        array(),
        PIX2ME_AI_IMAGE_GENERATOR_VERSION
    );
    
    // 加载脚本
    wp_enqueue_script(
        'pix2me-admin-js',
        PIX2ME_AI_IMAGE_GENERATOR_URL . 'assets/js/pix2me-admin.js',
        array('jquery'),
        PIX2ME_AI_IMAGE_GENERATOR_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'pix2me_enqueue_admin_scripts');

// 添加刷新缓存按钮到管理员工具栏
function pix2me_add_cache_clear_button($admin_bar) {
    if (current_user_can('manage_options')) {
        $admin_bar->add_menu(array(
            'id'    => 'pix2me-cache-clear',
            'title' => '刷新Pix2me缓存',
            'href'  => wp_nonce_url(admin_url('options-general.php?page=pix2me-ai-image-generator-settings&action=clear_cache'), 'pix2me_clear_cache'),
            'meta'  => array(
                'title' => '刷新所有Pix2me插件缓存和资源',
            ),
        ));
    }
}
add_action('admin_bar_menu', 'pix2me_add_cache_clear_button', 100);

// 处理缓存清除请求
function pix2me_handle_cache_clear() {
    // 首先检查用户是否有管理权限
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // 然后检查是否是清除缓存的请求
    if (isset($_GET['page']) && $_GET['page'] === 'pix2me-ai-image-generator-settings' &&
        isset($_GET['action']) && $_GET['action'] === 'clear_cache' && 
        isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'pix2me_clear_cache')) {
        
        // 更新资源时间戳，强制刷新所有资源
        update_option('pix2me_resource_timestamp', time());
        
        // 添加通知
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Pix2me插件缓存已成功刷新！</p></div>';
        });
        
        // 重定向回设置页面，避免刷新时重复执行
        wp_safe_redirect(admin_url('options-general.php?page=pix2me-ai-image-generator-settings&cache_cleared=1'));
        exit;
    }
    
    // 显示缓存已清除的消息（如果有cache_cleared参数）
    if (isset($_GET['page']) && $_GET['page'] === 'pix2me-ai-image-generator-settings' &&
        isset($_GET['cache_cleared']) && $_GET['cache_cleared'] == 1) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Pix2me插件缓存已成功刷新！</p></div>';
        });
    }
}
add_action('admin_init', 'pix2me_handle_cache_clear');

/**
 * 注册和加载脚本和样式
 */
function pix2me_ai_image_generator_enqueue_scripts() {
    global $post;
    $plugin_url = plugin_dir_url(PIX2ME_AI_IMAGE_GENERATOR_FILE);
    $version = PIX2ME_AI_IMAGE_GENERATOR_VERSION;
    
    // 初始化页面类型变量
    $is_edit_page = false;
    $is_generate_page = false;
    $is_asset_library = false;
    
    // 仅当$post对象有效时才检查页面类型
    if (is_object($post)) {
        $current_page_template = get_page_template_slug($post->ID);
        
        // 检查是否为编辑页面 - 通过页面模板或自定义字段判断
        $is_edit_page = (has_shortcode($post->post_content, 'pix2me_image_edit') || 
                        strpos($current_page_template, 'image-edit') !== false);
        
        // 检查是否为生成页面 - 通过页面模板或自定义字段判断
        $is_generate_page = (has_shortcode($post->post_content, 'pix2me_image_generator') || 
                            strpos($current_page_template, 'generate') !== false);
                            
        // 检查是否为资产库页面
        $is_asset_library = (has_shortcode($post->post_content, 'pix2me_asset_library') || 
                            strpos($current_page_template, 'asset-library') !== false);
    }

    // 注册主生成页面脚本
    wp_register_script(
        'pix2me-generate-page-js',
        plugins_url('assets/js/pix2me-generate-page.js', dirname(__FILE__)),
        array('jquery'),
        $version,
        true
    );

    // 注册资产库页面脚本
    wp_register_script(
        'pix2me-asset-library-js',
        plugins_url('assets/js/pix2me-asset-library.js', dirname(__FILE__)),
        array('jquery'),
        $version,
        true
    );

    // 注册生成页面样式
    wp_register_style(
        'pix2me-generate-page-css',
        plugins_url('assets/css/pix2me-generate-page.css', dirname(__FILE__)),
        array(),
        $version
    );

    // 注册资产库页面样式
    wp_register_style(
        'pix2me-asset-library-css',
        plugins_url('assets/css/pix2me-asset-library.css', dirname(__FILE__)),
        array(),
        $version
    );

    // 添加到WordPress的全局JS中的数据
    wp_localize_script('pix2me-generate-page-js', 'pix2meGeneratorData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_image_generator_nonce')
    ));

    // 为资产库页面添加全局JS数据
    wp_localize_script('pix2me-asset-library-js', 'pix2meAssetLibraryData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pix2me_details_nonce'),
        'generate_page_url' => home_url('/image-edit')
    ));

    // 图像编辑页面的JavaScript
    wp_register_script(
        'pix2me-image-edit-js',
        $plugin_url . 'assets/js/image-edit.js',
        array('jquery'),
        $version,
        true
    );
    
    // 定位AJAX URL和随机数
    wp_localize_script('pix2me-image-edit-js', 'pix2meImageEdit', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pix2me_ai_image_generator_nonce'),
        'details_nonce' => wp_create_nonce('pix2me_details_nonce'),
        'max_upload_size' => wp_max_upload_size(),
        'i18n' => array(
            'error_file_size' => __('文件太大，请上传更小的图片', 'pix2me-ai-image-generator'),
            'error_file_type' => __('仅支持 JPG 和 PNG 格式', 'pix2me-ai-image-generator'),
            'uploading' => __('上传中...', 'pix2me-ai-image-generator'),
            'processing' => __('处理中...', 'pix2me-ai-image-generator'),
            'success' => __('成功', 'pix2me-ai-image-generator'),
            'error' => __('错误', 'pix2me-ai-image-generator'),
            'please_select_image' => __('请选择图片', 'pix2me-ai-image-generator'),
            'please_enter_prompt' => __('请输入编辑指令', 'pix2me-ai-image-generator'),
        )
    ));
    
    // 按条件加载脚本
    if ($is_edit_page) {
        // 仅加载编辑页面需要的脚本和样式
        wp_enqueue_script('pix2me-image-edit-js');
        wp_enqueue_style('pix2me-generate-page-css'); // 共用样式
    } 
    else if ($is_generate_page) {
        // 仅加载生成页面需要的脚本和样式
        wp_enqueue_script('pix2me-generate-page-js');
        wp_enqueue_style('pix2me-generate-page-css');
    }
    else if ($is_asset_library) {
        // 仅加载资产库页面需要的脚本和样式
        wp_enqueue_script('pix2me-asset-library-js');
        wp_enqueue_style('pix2me-asset-library-css');
    }
}
add_action('wp_enqueue_scripts', 'pix2me_ai_image_generator_enqueue_scripts'); 