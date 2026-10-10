<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * AJAX handler for generating images.
 */
function pix2me_ai_image_generator_ajax_generate_image() {
    check_ajax_referer( 'pix2me_ai_image_generator_nonce', 'nonce' );
    
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => __( '您需要登录后才能生成图像', 'pix2me-ai-image-generator' ) ) );
        return;
    }
    
    $prompt = isset( $_POST['prompt'] ) ? sanitize_textarea_field( $_POST['prompt'] ) : '';
    $negative_prompt = isset( $_POST['negative_prompt'] ) ? sanitize_textarea_field( $_POST['negative_prompt'] ) : '';
    $num_outputs = isset( $_POST['num_outputs'] ) ? intval( $_POST['num_outputs'] ) : 1;
    if ($num_outputs < 1) $num_outputs = 1; // Ensure at least 1
    
    $resolution = isset($_POST['resolution']) ? sanitize_text_field($_POST['resolution']) : '1024x1024';
    $api_type = isset($_POST['api_type']) ? sanitize_text_field($_POST['api_type']) : 'stable_diffusion';
    $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
    $quality = isset($_POST['quality']) ? sanitize_text_field($_POST['quality']) : 'standard';
    $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '';
    $lighting = isset($_POST['lighting']) ? sanitize_text_field($_POST['lighting']) : '';
    $composition = isset($_POST['composition']) ? sanitize_text_field($_POST['composition']) : '';
    $seed = isset($_POST['seed']) ? sanitize_text_field($_POST['seed']) : '';
    
    // 获取前端传递的总成本
    $frontend_total_cost = isset($_POST['total_cost']) ? intval($_POST['total_cost']) : 0;
    
    // 初始化结果数据数组
    $result_data = array(
        'image_urls' => array(),
        'image_ids' => array(),
        'model_used' => $model,
        'remaining_credits' => 0
    );
    
    // 验证必要参数
    if (empty($prompt)) {
        wp_send_json_error(array('message' => __('请提供提示词', 'pix2me-ai-image-generator')));
        return;
    }

    // 计算生成成本（与前端计算方式保持一致）
    $base_cost = 0;
    
    // 根据不同的API和模型计算基础成本
    if ($api_type === 'openai') {
        if ($model === 'dall-e-3') {
            // DALL-E 3成本:
            // 标准质量: 方形=2, 非方形=3
            // 高质量HD: 方形=3, 非方形=4
            $is_hd = ($quality === 'high');
            $is_square = ($resolution === '1024x1024');
            
            if ($is_hd) {
                $base_cost = $is_square ? 3 : 4;
            } else {
                $base_cost = $is_square ? 2 : 3;
            }
        } else if ($model === 'gpt-image-1') {
            // GPT-Image-1成本基于质量和分辨率
            $is_square = ($resolution === '1024x1024');
            
            if ($quality === 'low') {
                $base_cost = $is_square ? 1 : 1; // 低质量固定成本
            } else if ($quality === 'standard') {
                $base_cost = $is_square ? 2 : 2; // 标准质量
            } else if ($quality === 'high') {
                $base_cost = $is_square ? 6 : 7; // 高质量
            }
        } else { // DALL-E 2
            $base_cost = 1; // DALL-E 2固定成本
        }
    } else {
        // Stable Diffusion/其他API
        $base_cost = get_option('pix2me_ai_image_generator_base_cost', 1);
    }
    
    // 总成本 = 基础成本 × 图像数量
    $total_cost = $base_cost * $num_outputs;
    
    // 向上取整
    $total_cost = ceil($total_cost);
    
    // 检查前端传递的成本与后端计算是否一致（可选安全检查）
    if ($frontend_total_cost !== $total_cost) {
        // 使用后端计算的成本（更安全）
    }
    
    // 获取用户当前积分 - 优先使用myCRED插件
    $user_id = get_current_user_id();
    $user_balance = 0;
    
    // 尝试从myCRED获取余额
    if (function_exists('mycred_get_users_balance')) {
        $user_balance = mycred_get_users_balance($user_id);
        // 确保值是数字
        if ($user_balance === false || !is_numeric($user_balance)) {
            $user_balance = 0;
        }
    } else {
        // 如果myCRED不可用，使用我们自己的积分系统
        $user_balance = get_user_meta($user_id, 'ai_image_generator_credits', true);
        if (empty($user_balance) || !is_numeric($user_balance)) {
            $user_balance = 0;
        }
    }
    
    // 检查用户积分是否足够
    if ($user_balance < $total_cost) {
        wp_send_json_error(array('message' => sprintf(
            __('您的积分不足，需要 %d 积分进行此操作，但您只有 %d 积分', 'pix2me-ai-image-generator'),
            $total_cost,
            $user_balance
        )));
        return;
    }
    
    // 准备API参数
    $options = get_option('pix2me_ai_image_generator_options', array());
    
    // Handle single-output models
    $is_openai_single_output_model = ($api_type === 'openai' && ($model === 'dall-e-3' || $model === 'gpt-image-1'));
    if ($is_openai_single_output_model) {
        $num_outputs = 1;
    }

    // Seed can be optional
    if (empty($seed)) {
        $seed = mt_rand(0, 2147483647); // Generate random seed if not provided
    } else {
        $seed = intval($seed);
    }
    
    $params_for_api = array(
        'api_type' => $api_type,
        'model' => $model,
        'resolution' => $resolution,
        'seed' => $seed,
        'num_outputs' => $num_outputs,
        'negative_prompt' => $negative_prompt,
    );
    
    // 根据不同模型设置质量参数
    if ($api_type === 'openai') {
        if ($model === 'dall-e-3') {
            $params_for_api['quality'] = ($quality === 'high') ? 'hd' : 'standard';
        } else if ($model === 'gpt-image-1') {
            if ($quality === 'standard') {
                $params_for_api['quality'] = 'medium';
            } else {
                $params_for_api['quality'] = $quality;
            }
        }
    } else {
        $params_for_api['quality'] = $quality;
    }

    // Validate main prompt
    if (!function_exists('ai_validate_input') || !ai_validate_input($prompt, 'prompt')) {
        wp_send_json_error(array('message' => __('Invalid prompt provided.', 'pix2me-ai-image-generator')));
        return;
    }
    
    // --- Call the API Handler ---
    if (function_exists('ai_generate_image_api')) {
        // 先调用API生成图像
        $api_result = ai_generate_image_api($prompt, $params_for_api);

        if (is_wp_error($api_result)) {
            wp_send_json_error(array('message' => $api_result->get_error_message()));
            return;
        } else {
            // --- API调用成功，现在执行积分扣除 ---
            $user_id = get_current_user_id();
            $image_urls = isset($api_result['image_urls']) ? $api_result['image_urls'] : [];
            $api_raw_response = isset($api_result['raw_response']) ? $api_result['raw_response'] : $api_result;
            // Use seed returned from API result if possible, otherwise the one generated/sent
            $seed = isset($api_result['seed']) ? $api_result['seed'] : $seed; 
            $model_used = isset($api_result['model_used']) ? $api_result['model_used'] : $model;
            
            // 检查是否有临时文件需要处理（用于GPT-4 Vision b64_json响应）
            $temp_files = isset($api_result['temp_files']) ? $api_result['temp_files'] : [];

            // 确保有图像URL返回
            if (empty($image_urls)) {
                // 清理所有临时文件
                foreach ($temp_files as $temp_file) {
                    if (file_exists($temp_file)) {
                        @unlink($temp_file);
                    }
                }
                
                wp_send_json_error(array('message' => __('API返回成功但没有图像URL', 'pix2me-ai-image-generator')));
                return;
            }

            // 1. 扣除积分
            $deduction_result = false;
            if (function_exists('ai_deduct_credits')) {
                $deduction_result = ai_deduct_credits(
                    $total_cost,
                    sprintf(__('生成 %d 张AI图像 (%s, %s, %s)', 'pix2me-ai-image-generator'),
                        count($image_urls),
                        $model_used,
                        $resolution,
                        $quality
                    ),
                    $user_id,
                    array(
                        'prompt' => $prompt,
                        'api_type' => $api_type,
                        'model' => $model_used,
                        'seed' => $seed
                    )
                );

                if ($deduction_result === false) {
                    // 清理所有未处理的临时文件
                    if (!empty($temp_files)) {
                        foreach ($temp_files as $temp_file) {
                            if (!empty($temp_file) && file_exists($temp_file)) {
                                @unlink($temp_file);
                            }
                        }
                    }
                    
                    wp_send_json_error(array(
                        'message' => __('积分扣除失败，但API已生成图像', 'pix2me-ai-image-generator'),
                        'image_urls' => $image_urls, // 返回图像URL以便显示
                        'seed' => $seed
                    ));
                    return;
                }
            }

            // 2. 保存到媒体库
            $saved_image_ids = [];
            if (!empty($image_urls)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $params_to_save_meta = $params_for_api;

                foreach ($image_urls as $index => $image_url) {
                    // 检查URL是否为本地临时文件
                    $is_temp_file = strpos($image_url, '/pix2me-temp/') !== false;
                    $temp_file_path = null;
                    
                    if ($is_temp_file && !empty($temp_files) && isset($temp_files[$index])) {
                        $temp_file_path = $temp_files[$index];
                    }
                    
                    if ($is_temp_file && file_exists($temp_file_path)) {
                        // 使用临时文件
                        $file_name = basename($temp_file_path);
                        $file_array = array(
                            'name' => $file_name,
                            'tmp_name' => $temp_file_path
                        );
                    } else {
                        // 下载图像到服务器
                        $downloaded_image = download_url($image_url);
                        if (is_wp_error($downloaded_image)) {
                            continue; // 跳过这个图像
                        }

                        // 准备文件用于媒体库
                        $file_name = basename(parse_url($image_url, PHP_URL_PATH));
                        $safe_file_name = sanitize_file_name($file_name);
                        $file_array = array(
                            'name' => $safe_file_name,
                            'tmp_name' => $downloaded_image
                        );
                    }

                    // 上传到媒体库
                    $attachment_id = media_handle_sideload($file_array, 0, substr($prompt, 0, 100));

                    // 检查上传错误
                    if (is_wp_error($attachment_id)) {
                        @unlink($file_array['tmp_name']); // 删除临时文件
                        continue; // 跳过这个图像
                    } else {
                        $saved_image_ids[] = $attachment_id;
                        // 保存元数据
                        update_post_meta($attachment_id, '_pix2me_generation_params', json_encode($params_to_save_meta));
                        update_post_meta($attachment_id, '_pix2me_api_type', $api_type);
                        update_post_meta($attachment_id, '_pix2me_model', $model_used);
                        update_post_meta($attachment_id, '_pix2me_seed', $seed);
                        $cost_per_image = ($num_outputs > 0) ? floor($total_cost / $num_outputs) : $total_cost;
                        update_post_meta($attachment_id, '_pix2me_cost', $cost_per_image);
                        update_post_meta($attachment_id, '_pix2me_resolution', $resolution);
                        update_post_meta($attachment_id, '_pix2me_quality', $quality);
                        
                        // 将临时文件标记为已处理，这样不会在最后被删除（因为WordPress已经移动了文件）
                        $current_url = $image_url;
                        if (strpos($current_url, '/pix2me-temp/') !== false) {
                            foreach ($temp_files as $index => $temp_file) {
                                if (strpos($current_url, basename($temp_file)) !== false) {
                                    // 标记为已处理（将其从数组中移除或替换为空字符串）
                                    $temp_files[$index] = '';
                                    break;
                                }
                            }
                        }
                        
                        // 添加必要的标记以便资产库能够识别为AI生成的图片
                        update_post_meta($attachment_id, '_ai_generated_image', '1');
                        update_post_meta($attachment_id, '_ai_generation_prompt', $prompt);
                        update_post_meta($attachment_id, '_ai_parameters', json_encode(array(
                            'api_type' => $api_type,
                            'model' => $model_used,
                            'seed' => $seed,
                            'resolution' => $resolution,
                            'quality' => $quality,
                            'negative_prompt' => $negative_prompt
                        )));
                        update_post_meta($attachment_id, '_pix2me_private', 1); // 默认Private
                    }
                }
            }

            // 3. 创建历史记录
            if (post_type_exists('ai_image_history') && !empty($saved_image_ids)) {
                $history_content = "Generated " . count($saved_image_ids) . " image(s). Model: {$model_used}, Seed: {$seed}";
                $history_post_id = wp_insert_post(array(
                    'post_title' => 'Generation: ' . substr($prompt, 0, 50) . '...',
                    'post_content' => $history_content,
                    'post_status' => 'publish',
                    'post_author' => $user_id,
                    'post_type' => 'ai_image_history',
                ));

                if (!is_wp_error($history_post_id)) {
                    // 添加元数据到历史记录
                    add_post_meta($history_post_id, '_pix2me_prompt', $prompt);
                    add_post_meta($history_post_id, '_pix2me_negative_prompt', isset($params_to_save_meta['negative_prompt']) ? $params_to_save_meta['negative_prompt'] : '');
                    add_post_meta($history_post_id, '_pix2me_parameters', json_encode($params_to_save_meta));
                    add_post_meta($history_post_id, '_pix2me_cost', $total_cost);
                    add_post_meta($history_post_id, '_pix2me_user_id', $user_id);
                    add_post_meta($history_post_id, '_pix2me_api_type', $api_type);
                    add_post_meta($history_post_id, '_pix2me_model', $model_used);
                    add_post_meta($history_post_id, '_pix2me_seed', $seed);
                    add_post_meta($history_post_id, '_pix2me_attachment_ids', $saved_image_ids);
                    
                    // 将第一张生成的图像设为特色图像
                    if (isset($saved_image_ids[0])) {
                        set_post_thumbnail($history_post_id, $saved_image_ids[0]);
                    }
                }
            }

            // 4. 返回成功响应
            $image_urls_to_return = !empty($saved_image_ids) ? 
                array_map('wp_get_attachment_url', $saved_image_ids) : 
                $image_urls;
                
            // 获取扣除积分后的更新余额
            $updated_balance = 0;
            if (function_exists('ai_get_mycred_balance')) {
                $updated_balance = ai_get_mycred_balance();
            }
            
            // 清理临时文件（如果存在）
            if (!empty($temp_files)) {
                $cleaned_count = 0;
                foreach ($temp_files as $temp_file) {
                    // 只删除非空值和存在的文件（空值表示已被处理）
                    if (!empty($temp_file) && file_exists($temp_file)) {
                        @unlink($temp_file);
                        $cleaned_count++;
                    }
                }
            }
            
            $result_data['image_urls'] = $image_urls_to_return;
            $result_data['image_ids'] = $saved_image_ids;
            $result_data['remaining_credits'] = $updated_balance;
            
            wp_send_json_success(array(
                'message' => sprintf(__('成功生成了 %d 张图像', 'pix2me-ai-image-generator'), count($image_urls_to_return)),
                'image_urls' => $image_urls_to_return,
                'saved_image_ids' => $saved_image_ids,
                'seed' => $seed,
                'model_used' => $model_used,
                'remaining_balance' => $updated_balance
            ));
            return;
        }
    } else {
        wp_send_json_error(array('message' => __('Image generation API function not available.', 'pix2me-ai-image-generator')));
    }

    wp_die(); // this is required to terminate immediately and return a proper response
}

/**
 * AJAX处理函数：获取图像详情
 */
function pix2me_get_image_details() {
    // 安全检查
    if (!check_ajax_referer('pix2me_details_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => '安全验证失败'));
        return;
    }
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '请先登录'));
        return;
    }
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    if (!$image_id) {
        wp_send_json_error(array('message' => '无效的图像ID'));
        return;
    }
    $attachment = get_post($image_id);
    if (!$attachment) {
        wp_send_json_error(array('message' => '图像不存在'));
        return;
    }
    if (!current_user_can('administrator') && $attachment->post_author != get_current_user_id()) {
        wp_send_json_error(array('message' => '无权查看此图像'));
        return;
    }
    // 获取媒体库URL
    $url = wp_get_attachment_url($image_id);
    $download_url = '';
    if ($url) {
        if (substr($url, -5) === '.webp') {
            $download_url = str_replace('.webp', '.bak.png', $url);
        } else if (substr($url, -4) === '.png') {
            $download_url = str_replace('.png', '.bak.png', $url);
        } else if (substr($url, -4) === '.jpg') {
            $download_url = str_replace('.jpg', '.bak.jpg', $url);
        } else if (substr($url, -5) === '.jpeg') {
            $download_url = str_replace('.jpeg', '.bak.jpeg', $url);
        } else {
            $download_url = $url;
        }
    }
    $file_path = get_attached_file($image_id);
    $file_exists = file_exists($file_path);
    $title = get_the_title($image_id);
    $prompt = get_post_meta($image_id, '_ai_generation_prompt', true);
    if (empty($prompt)) {
        $prompt = get_post_meta($image_id, '_pix2me_prompt', true);
    }
    $timestamp = get_the_date('Y-m-d H:i:s', $image_id);
    $parameters = get_post_meta($image_id, '_ai_parameters', true);
    if (is_string($parameters)) {
        $parameters = json_decode($parameters, true);
    }
    if (empty($parameters) || !is_array($parameters)) {
        $parameters = array();
        $model = get_post_meta($image_id, '_pix2me_model', true);
        $api_type = get_post_meta($image_id, '_pix2me_api_type', true);
        $resolution = get_post_meta($image_id, '_pix2me_resolution', true);
        $quality = get_post_meta($image_id, '_pix2me_quality', true);
        if (!empty($model)) $parameters['model'] = $model;
        if (!empty($api_type)) $parameters['api_type'] = $api_type;
        if (!empty($resolution)) $parameters['resolution'] = $resolution;
        if (!empty($quality)) $parameters['quality'] = $quality;
    }
    $pix2me_private = get_post_meta($image_id, '_pix2me_private', true);
    wp_send_json_success(array(
        'id' => $image_id,
        'url' => $url,
        'download_url' => $download_url,
        'title' => $title,
        'prompt' => $prompt,
        'timestamp' => $timestamp,
        'parameters' => $parameters,
        'file_exists' => $file_exists,
        'pix2me_private' => $pix2me_private
    ));
    wp_die();
}

/**
 * AJAX处理函数：删除图片
 */
function pix2me_delete_image() {
    // 安全检查
    if (!check_ajax_referer('pix2me_details_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => '安全验证失败'));
        return;
    }
    
    // 确保用户已登录
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '请先登录'));
        return;
    }
    
    // 获取图像ID
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    if (!$image_id) {
        wp_send_json_error(array('message' => '无效的图像ID'));
        return;
    }
    
    // 验证当前用户是否有权限删除此图像
    $attachment = get_post($image_id);
    if (!$attachment || $attachment->post_author != get_current_user_id()) {
        wp_send_json_error(array('message' => '无权删除此图像'));
        return;
    }
    
    // 查找相关的历史记录
    $history_posts = get_posts(array(
        'post_type' => 'ai_image_history',
        'meta_query' => array(
            array(
                'key' => '_ai_image_attachment_id',
                'value' => $image_id,
                'compare' => '='
            )
        ),
        'posts_per_page' => -1
    ));
    
    // 删除相关的历史记录
    foreach ($history_posts as $history_post) {
        wp_delete_post($history_post->ID, true);
    }
    
    // 删除附件
    $result = wp_delete_attachment($image_id, true);
    
    if ($result) {
        wp_send_json_success(array('message' => '图片已成功删除'));
    } else {
        wp_send_json_error(array('message' => '删除图片失败'));
    }
}

/**
 * AJAX处理函数：加载资产库图片
 */
function pix2me_load_gallery_images() {
    // 检查安全nonce
    check_ajax_referer('pix2me_details_nonce', 'nonce');
    
    // 检查用户是否登录
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '请先登录'));
        return;
    }
    
    $user_id = get_current_user_id();
    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
    $per_page = 9; // 每页显示9张图片
    
    // 查询用户的AI生成图片
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'author' => $user_id,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => '_ai_generated_image',
                'value' => '1',
                'compare' => '='
            ),
            array(
                'key' => '_pix2me_model',
                'compare' => 'EXISTS'
            )
        ),
        'orderby' => 'date',
        'order' => 'DESC'
    );
    
    $query = new WP_Query($args);
    $images = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $attachment_id = get_the_ID();
            
            // 获取图片URL
            $full_url = wp_get_attachment_url($attachment_id);
            $thumbnail_url = wp_get_attachment_image_url($attachment_id, 'thumbnail');
            
            // 获取元数据
            $title = get_the_title();
            $prompt = get_post_meta($attachment_id, '_ai_generation_prompt', true);
            $timestamp = get_the_date('Y-m-d H:i:s');
            
            // 获取参数数据
            $parameters = get_post_meta($attachment_id, '_ai_parameters', true);
            if (is_string($parameters)) {
                $parameters = json_decode($parameters, true);
            }
            
            // 如果没有从_ai_parameters获取到数据，尝试从_pix2me_前缀的元数据获取
            $model = '';
            $seed = '';
            
            if (!empty($parameters) && is_array($parameters)) {
                $model = isset($parameters['model']) ? $parameters['model'] : '';
                $seed = isset($parameters['seed']) ? $parameters['seed'] : '';
            }
            
            // 如果上面没有获取到，尝试直接获取单独的元数据
            if (empty($model)) {
                $model = get_post_meta($attachment_id, '_pix2me_model', true);
            }
            
            if (empty($seed)) {
                $seed = get_post_meta($attachment_id, '_pix2me_seed', true);
            }
            
            // 构建展示信息
            $display_info = '';
            if (!empty($model)) {
                $display_info .= 'Model: ' . $model . ' ';
            }
            if (!empty($seed)) {
                $display_info .= 'Seed: ' . $seed;
            }
            
            // 如果有额外信息，将其添加到提示词后面
            $display_prompt = $prompt;
            if (!empty($display_info)) {
                $display_prompt .= ' [' . trim($display_info) . ']';
            }
            
            $pix2me_private = get_post_meta($attachment_id, '_pix2me_private', true);
            $images[] = array(
                'id' => $attachment_id,
                'url' => $full_url,
                'thumbnail_url' => $thumbnail_url,
                'title' => $title,
                'prompt' => $display_prompt,
                'raw_prompt' => $prompt,
                'model' => $model,
                'seed' => $seed,
                'timestamp' => $timestamp,
                'pix2me_private' => $pix2me_private
            );
        }
        wp_reset_postdata();
    }
    
    // 检查是否有更多图片
    $has_more = ($query->max_num_pages > $page);
    
    // 返回数据
    wp_send_json_success(array(
        'images' => $images,
        'has_more' => $has_more,
        'current_page' => $page,
        'total_pages' => $query->max_num_pages
    ));
}

/**
 * AJAX处理函数：删除图库图片
 */
function pix2me_delete_gallery_image() {
    // 检查安全nonce
    check_ajax_referer('pix2me_details_nonce', 'nonce');
    
    // 检查用户是否登录
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '请先登录'));
        return;
    }
    
    $user_id = get_current_user_id();
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    
    // 验证图片存在且属于当前用户
    $attachment = get_post($image_id);
    
    if (!$attachment || $attachment->post_author != $user_id) {
        wp_send_json_error(array('message' => '没有权限删除此图片'));
        return;
    }
    
    // 删除图片
    $result = wp_delete_attachment($image_id, true);
    
    if ($result) {
        wp_send_json_success(array('message' => '图片已成功删除'));
    } else {
        wp_send_json_error(array('message' => '删除图片失败'));
    }
}

/**
 * AJAX处理函数：更新图片公开状态
 */
function pix2me_update_image_privacy() {
    // 检查安全nonce
    check_ajax_referer('pix2me_details_nonce', 'nonce');
    // 检查用户是否登录
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => '请先登录'));
        return;
    }
    $user_id = get_current_user_id();
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
    $is_private = 1; // 默认Private
    if (isset($_POST['is_private'])) {
        $is_private = intval($_POST['is_private']);
    } elseif (isset($_POST['is_public'])) {
        $is_private = intval($_POST['is_public']) ? 0 : 1;
    }
    // 验证图片存在且属于当前用户
    $attachment = get_post($image_id);
    if (!$attachment || $attachment->post_author != $user_id) {
        wp_send_json_error(array('message' => '没有权限更新此图片'));
        return;
    }
    // 更新_pix2me_private字段
    update_post_meta($image_id, '_pix2me_private', $is_private);
    wp_send_json_success(array(
        'message' => '图片状态已更新',
        'pix2me_private' => $is_private
    ));
}

/**
 * AJAX处理程序：处理图片编辑请求
 */
function pix2me_ai_image_editor_ajax_edit_image() {
    check_ajax_referer('pix2me_ai_image_generator_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('您需要登录后才能编辑图像', 'pix2me-ai-image-generator')));
        return;
    }
    
    // 获取参数
    $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
    $image_data = isset($_POST['image_data']) ? $_POST['image_data'] : '';
    $resolution = isset($_POST['resolution']) ? sanitize_text_field($_POST['resolution']) : '1024x1024';
    $api_type = isset($_POST['api_type']) ? sanitize_text_field($_POST['api_type']) : 'openai';
    $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : 'gpt-image-1';
    $quality = isset($_POST['quality']) ? sanitize_text_field($_POST['quality']) : 'standard';
    $style_prompt = isset($_POST['style_prompt']) ? sanitize_text_field($_POST['style_prompt']) : '';
    
    // 获取前端传递的总成本
    $frontend_total_cost = isset($_POST['total_cost']) ? intval($_POST['total_cost']) : 0;
    
    // 初始化结果数据数组
    $result_data = array(
        'image_urls' => array(),
        'image_ids' => array(),
        'model_used' => $model,
        'remaining_credits' => 0
    );
    
    // 验证必要参数
    if (empty($prompt)) {
        wp_send_json_error(array('message' => __('请提供编辑指令', 'pix2me-ai-image-generator')));
        return;
    }
    
    if (empty($image_data)) {
        wp_send_json_error(array('message' => __('请上传要编辑的图片', 'pix2me-ai-image-generator')));
        return;
    }
    
    // 验证并清理图片数据
    if (strpos($image_data, 'data:image/') === 0) {
        // 从base64数据中提取实际的图像数据，移除前缀
        $image_parts = explode(';base64,', $image_data);
        if (count($image_parts) !== 2) {
            wp_send_json_error(array('message' => __('图像数据格式无效', 'pix2me-ai-image-generator')));
            return;
        }
        // 保留完整图像数据以供API调用使用
    }
    
    // 计算编辑成本
    $base_cost = 0;
    
    // 定义分辨率是否为方形
    $is_square = ($resolution === '1024x1024');
    
    // 根据不同的API和模型计算编辑成本
    if ($api_type === 'openai') {
        if ($model === 'gpt-image-1') {
            // GPT-Image-1 基础成本
            if ($quality === 'low') {
                $base_cost = $is_square ? 1 : 1; // 低质量固定成本
            } else if ($quality === 'medium') {
                $base_cost = $is_square ? 2 : 2; // 中等质量固定成本
            } else if ($quality === 'high') {
                $base_cost = $is_square ? 6 : 7; // 高质量（方形/非方形）
            }
        } else if ($model === 'dall-e-3') {
            // DALL-E 3 不支持编辑
            wp_send_json_error(array('message' => __('DALL-E 3 不支持图像编辑功能', 'pix2me-ai-image-generator')));
            return;
        } else {
            // DALL-E 2 固定成本
            $base_cost = 2;
        }
    } else {
        // Stable Diffusion 基础成本
        $base_cost = get_option('pix2me_ai_image_generator_edit_cost', 2);
    }
    
    // 上传图片的基础成本
    $upload_cost = 1;
    
    // 总成本 = 基础上传成本 + 编辑成本
    $total_cost = $upload_cost + $base_cost;
    
    // 获取用户当前积分 - 优先使用myCRED插件
    $user_id = get_current_user_id();
    $user_balance = 0;
    
    // 尝试从myCRED获取余额
    if (function_exists('mycred_get_users_balance')) {
        $user_balance = mycred_get_users_balance($user_id);
        // 确保值是数字
        if ($user_balance === false || !is_numeric($user_balance)) {
            $user_balance = 0;
        }
    } else {
        // 如果myCRED不可用，使用我们自己的积分系统
        $user_balance = get_user_meta($user_id, 'ai_image_generator_credits', true);
        if (empty($user_balance) || !is_numeric($user_balance)) {
            $user_balance = 0;
        }
    }
    
    // 检查用户积分是否足够
    if ($user_balance < $total_cost) {
        wp_send_json_error(array('message' => sprintf(
            __('您的积分不足，需要 %d 积分进行此操作，但您只有 %d 积分', 'pix2me-ai-image-generator'),
            $total_cost,
            $user_balance
        )));
        return;
    }
    
    // 准备API参数
    $api_params = array(
        'image' => $image_data,
        'resolution' => $resolution,
        'api_type' => $api_type,
        'model' => $model,
        'quality' => $quality,
        'num_outputs' => 1
    );
    
    // 添加样式提示词（如果有）
    if (!empty($style_prompt)) {
        $api_params['style_prompt'] = $style_prompt;
    }
    
    // 记录API调用日志
    ai_debug_log('开始图像编辑API调用', [
        '提示词' => $prompt,
        'API类型' => $api_type,
        '模型' => $model,
        '分辨率' => $resolution,
        '质量' => $quality
    ]);
    
    // 调用API进行图像编辑
    $ai_result = ai_edit_image_api($prompt, $api_params);
    
    // 检查API结果
    if (is_wp_error($ai_result)) {
        $error_message = $ai_result->get_error_message();
        $error_data = $ai_result->get_error_data();
        
        // 格式化错误消息以便用户理解
        $user_message = $error_message;
        
        // 记录错误日志
        ai_debug_log('图像编辑API调用失败', [
            '错误消息' => $error_message,
            '错误数据' => $error_data
        ]);
        
        // 特殊处理某些常见错误
        if (strpos($error_message, 'Unknown parameter') !== false) {
            $user_message = __('API参数错误: ', 'pix2me-ai-image-generator') . $error_message . 
                            __('。这可能是由于API更新或配置问题导致的，请联系管理员。', 'pix2me-ai-image-generator');
        }
        
        // API调用失败，不扣除积分，直接返回错误
        wp_send_json_error(array(
            'message' => $user_message
        ));
        return;
    }
    
    // 检查是否有图像URL
    if (empty($ai_result['image_urls'])) {
        // 记录错误日志
        ai_debug_log('图像编辑API调用返回空结果', $ai_result);
        
        // API成功但没有返回图像
        wp_send_json_error(array(
            'message' => __('API成功处理但没有返回任何图像', 'pix2me-ai-image-generator')
        ));
        return;
    }
    
    // 检查图像URL是否可访问
    $all_images_valid = true;
    foreach ($ai_result['image_urls'] as $image_url) {
        // 使用WP HTTP API检查URL是否可访问
        $response = wp_remote_head($image_url, ['timeout' => 5]);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $all_images_valid = false;
            ai_debug_log('图像URL无法访问', [
                'URL' => $image_url,
                '响应代码' => is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_response_code($response)
            ]);
            break;
        }
    }
    
    if (!$all_images_valid) {
        wp_send_json_error(array(
            'message' => __('无法访问生成的图像，请重试', 'pix2me-ai-image-generator')
        ));
        return;
    }
    
    // 只有当API调用成功并且图像URL可访问时，才扣除用户积分
    $deduction_success = false;
    
    // 如果使用myCRED，通过myCRED扣除积分
    if (function_exists('mycred_add')) {
        // 使用负值扣除积分，设置日志条目类型为"image_edit"
        $deduction_result = mycred_add(
            'image_edit',
            $user_id,
            -$total_cost,
            sprintf(__('使用 %s 编辑图像', 'pix2me-ai-image-generator'), $model)
        );
        
        $deduction_success = ($deduction_result !== false);
    } 
    else {
        // 使用自定义积分系统扣除积分
        $current_balance = get_user_meta($user_id, 'ai_image_generator_credits', true);
        if (!is_numeric($current_balance)) $current_balance = 0;
        
        $new_balance = max(0, $current_balance - $total_cost);
        $deduction_success = update_user_meta($user_id, 'ai_image_generator_credits', $new_balance);
        
        // 更新后的余额用于返回给前端
        $user_balance = $new_balance;
    }
    
    if (!$deduction_success) {
        ai_debug_log('扣除积分失败', [
            '用户ID' => $user_id,
            '积分成本' => $total_cost,
            '当前余额' => $user_balance
        ]);
        
        wp_send_json_error(array('message' => __('扣除积分失败，无法保存生成结果', 'pix2me-ai-image-generator')));
        return;
    }
    
    // 只用ai_result返回的image_ids和image_urls，不再重复注册
    $image_ids = isset($ai_result['image_ids']) ? $ai_result['image_ids'] : [];
    $image_urls = isset($ai_result['image_urls']) ? $ai_result['image_urls'] : [];
    // 如果没有image_ids，才调用ai_save_image_to_media_library（兼容老接口）
    if (empty($image_ids)) {
        foreach ($image_urls as $image_url) {
            $save_result = ai_save_image_to_media_library(array(
                'image_url' => $image_url,
                'title' => __('AI编辑图像', 'pix2me-ai-image-generator') . ' - ' . date('Y-m-d H:i:s'),
                'alt_text' => __('AI编辑图像', 'pix2me-ai-image-generator') . ' - ' . mb_substr($prompt, 0, 100),
                'description' => $prompt,
                'caption' => sprintf(__('使用 %s 编辑的图像', 'pix2me-ai-image-generator'), $model),
                'user_id' => $user_id
            ));
            if (!is_wp_error($save_result) && isset($save_result['id'])) {
                $image_ids[] = $save_result['id'];
                // 添加生成记录
                ai_record_generation(array(
                    'user_id' => $user_id,
                    'prompt' => $prompt,
                    'type' => 'edit',
                    'model' => $model,
                    'image_id' => $save_result['id'],
                    'resolution' => $resolution,
                    'quality' => $quality,
                    'cost' => $total_cost
                ));
            } else {
                $image_ids[] = 0;
                ai_debug_log('保存图像到媒体库失败', [
                    '图像URL' => $image_url,
                    '错误' => is_wp_error($save_result) ? $save_result->get_error_message() : '未知错误'
                ]);
            }
        }
    } else {
        // 有image_ids时，直接写入生成记录
        foreach ($image_ids as $img_id) {
            ai_record_generation(array(
                'user_id' => $user_id,
                'prompt' => $prompt,
                'type' => 'edit',
                'model' => $model,
                'image_id' => $img_id,
                'resolution' => $resolution,
                'quality' => $quality,
                'cost' => $total_cost
            ));
        }
    }
    $result_data['image_urls'] = $image_urls;
    $result_data['image_ids'] = $image_ids;
    $result_data['remaining_credits'] = $user_balance;
    wp_send_json_success($result_data);
}

/**
 * 保存图像到媒体库
 * 
 * @param array $args 参数数组，包括图像URL、提示词等
 * @return array|WP_Error 成功时返回附件信息，失败时返回错误
 */
function ai_save_image_to_media_library($args) {
    $image_url = isset($args['image_url']) ? $args['image_url'] : '';
    $prompt = isset($args['prompt']) ? $args['prompt'] : '';
    $model = isset($args['model']) ? $args['model'] : '';
    $format = isset($args['format']) ? $args['format'] : 'webp';
    
    // 新增参数支持
    $title = isset($args['title']) ? $args['title'] : '';
    $alt_text = isset($args['alt_text']) ? $args['alt_text'] : '';
    $description = isset($args['description']) ? $args['description'] : $prompt;
    $caption = isset($args['caption']) ? $args['caption'] : '';
    $user_id = isset($args['user_id']) ? intval($args['user_id']) : get_current_user_id();
    
    if (empty($image_url)) {
        return new WP_Error('missing_url', __('Image URL is required', 'pix2me-ai-image-generator'));
    }
    
    // 记录下载信息
    ai_debug_log('开始下载图像', [
        'URL' => $image_url,
        '格式' => $format
    ]);
    
    // 检查是否是本地临时文件URL
    $upload_dir = wp_upload_dir();
    $is_local_temp_file = (strpos($image_url, $upload_dir['baseurl'] . '/pix2me-temp/') === 0);
    $tmp_file = '';
    
    if ($is_local_temp_file) {
        // 如果是本地临时文件，直接获取文件路径
        $file_path = str_replace(
            $upload_dir['baseurl'],
            $upload_dir['basedir'],
            $image_url
        );
        
        if (file_exists($file_path)) {
            $tmp_file = $file_path;
            ai_debug_log('使用本地临时文件', [
                'URL' => $image_url,
                '文件路径' => $file_path
            ]);
        } else {
            ai_debug_log('本地临时文件不存在', [
                'URL' => $image_url,
                '文件路径' => $file_path
            ]);
            // 继续尝试下载
            $tmp_file = download_url($image_url);
        }
    } else {
        // 下载远程图像
        $tmp_file = download_url($image_url);
    }
    
    if (is_wp_error($tmp_file)) {
        ai_debug_log('下载图像失败', [
            'URL' => $image_url,
            '错误' => $tmp_file->get_error_message()
        ]);
        return $tmp_file;
    }
    
    // 生成文件名
    $filename = 'ai-generated-' . date('YmdHis') . '-' . wp_rand(1000, 9999);
    
    // 根据目标格式处理
    if ($format === 'webp') {
        // 转换为 WEBP
        $new_tmp_file = $tmp_file . '.webp';
        
        // 加载原始图像
        $image_type = exif_imagetype($tmp_file);
        switch ($image_type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($tmp_file);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($tmp_file);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($tmp_file);
                break;
            case IMAGETYPE_WEBP:
                // 已经是 WEBP，直接复制
                copy($tmp_file, $new_tmp_file);
                $image = null;
                break;
            default:
                // 不支持的格式，使用原文件
                $new_tmp_file = $tmp_file;
                $image = null;
                $format = 'jpg'; // 默认使用 JPG
        }
        
        // 如果成功加载图像，则转换为 WEBP
        if ($image) {
            imagewebp($image, $new_tmp_file, 90);
            imagedestroy($image);
            unlink($tmp_file); // 删除原临时文件
            $tmp_file = $new_tmp_file;
            $filename .= '.webp';
        } else if ($image_type === IMAGETYPE_WEBP) {
            // 如果是 WEBP 并且已经复制，更新文件名和路径
            $tmp_file = $new_tmp_file;
            $filename .= '.webp';
        } else {
            // 转换失败，使用原格式
            $format = ($image_type === IMAGETYPE_PNG) ? 'png' : 'jpg';
            $filename .= '.' . $format;
        }
    } else {
        // 使用原始格式
        $filename .= '.' . $format;
    }
    
    // 准备媒体上传
    $file_array = array(
        'name'     => $filename,
        'tmp_name' => $tmp_file
    );
    
    // 设置上传类型
    $mime_type = '';
    switch ($format) {
        case 'webp':
            $mime_type = 'image/webp';
            break;
        case 'png':
            $mime_type = 'image/png';
            break;
        case 'jpg':
        case 'jpeg':
            $mime_type = 'image/jpeg';
            break;
        default:
            $mime_type = 'image/jpeg';
    }
    
    // 禁用 WordPress 缩略图生成
    add_filter('intermediate_image_sizes_advanced', function($sizes) {
        return array(); // 返回空数组以跳过所有缩略图
    });
    
    // 准备上传参数
    $attachment_data = array(
        'post_mime_type' => $mime_type,
        'post_title' => !empty($title) ? $title : __('AI生成图像', 'pix2me-ai-image-generator'),
        'post_content' => $description,
        'post_excerpt' => $caption,
        'post_author' => $user_id
    );
    
    // 执行上传
    $attachment_id = media_handle_sideload($file_array, 0, '', $attachment_data);
    
    // 恢复缩略图生成
    remove_all_filters('intermediate_image_sizes_advanced');
    
    // 检查上传结果
    if (is_wp_error($attachment_id)) {
        // 清理临时文件
        @unlink($tmp_file);
        
        ai_debug_log('保存图像到媒体库失败', [
            '错误' => $attachment_id->get_error_message()
        ]);
        
        return $attachment_id;
    }
    
    // 设置图像alt文本
    if (!empty($alt_text)) {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
    }
    
    // 将提示词和相关信息保存为附件元数据
    update_post_meta($attachment_id, '_ai_generation_prompt', $prompt);
    update_post_meta($attachment_id, '_ai_generated_image', '1');
    update_post_meta($attachment_id, '_pix2me_private', 1); // 默认Private
    if (!empty($model)) {
        update_post_meta($attachment_id, '_pix2me_model', $model);
    }
    
    // 获取上传文件信息
    $upload_dir = wp_upload_dir();
    $file_path = get_attached_file($attachment_id);
    $file_url = wp_get_attachment_url($attachment_id);
    
    ai_debug_log('图像成功保存到媒体库', [
        '图像ID' => $attachment_id,
        '图像URL' => $file_url
    ]);
    
    return array(
        'id' => $attachment_id,
        'path' => $file_path,
        'url' => $file_url
    );
}

/**
 * 记录生成历史
 * 
 * @param array $args 历史记录参数
 * @return int|bool 记录ID或失败
 */
function ai_record_generation($args) {
    $user_id = isset($args['user_id']) ? intval($args['user_id']) : get_current_user_id();
    $prompt = isset($args['prompt']) ? sanitize_textarea_field($args['prompt']) : '';
    $model = isset($args['model']) ? sanitize_text_field($args['model']) : '';
    $api_type = isset($args['api_type']) ? sanitize_text_field($args['api_type']) : '';
    $resolution = isset($args['resolution']) ? sanitize_text_field($args['resolution']) : '';
    $quality = isset($args['quality']) ? sanitize_text_field($args['quality']) : '';
    
    // 支持新旧参数命名
    $credit_cost = isset($args['cost']) ? intval($args['cost']) : (isset($args['credit_cost']) ? intval($args['credit_cost']) : 0);
    
    // 支持单个图像ID或图像ID数组
    $image_ids = array();
    if (isset($args['image_id']) && !empty($args['image_id'])) {
        $image_ids[] = intval($args['image_id']);
    } elseif (isset($args['image_ids']) && is_array($args['image_ids'])) {
        $image_ids = array_map('intval', $args['image_ids']);
    }
    
    // 支持新旧操作类型参数
    $operation_type = isset($args['type']) ? sanitize_text_field($args['type']) : (isset($args['operation_type']) ? sanitize_text_field($args['operation_type']) : 'generate');
    
    // 自定义文章类型是否存在
    if (!post_type_exists('ai_image_history')) {
        return false;
    }
    
    // 记录历史记录创建过程
    ai_debug_log('创建AI图像生成历史记录', [
        '用户ID' => $user_id,
        '提示词' => $prompt,
        '模型' => $model,
        '操作类型' => $operation_type,
        '分辨率' => $resolution,
        '质量' => $quality,
        '费用' => $credit_cost,
        '图像IDs' => $image_ids
    ]);
    
    // 创建历史记录
    $history_data = array(
        'post_title'   => $prompt ? substr($prompt, 0, 100) . (strlen($prompt) > 100 ? '...' : '') : __('AI Image Generation', 'pix2me-ai-image-generator'),
        'post_content' => $prompt,
        'post_status'  => 'publish',
        'post_type'    => 'ai_image_history',
        'post_author'  => $user_id,
    );
    
    // 插入历史记录
    $history_id = wp_insert_post($history_data);
    
    if (!$history_id || is_wp_error($history_id)) {
        ai_debug_log('创建历史记录失败', [
            '错误' => is_wp_error($history_id) ? $history_id->get_error_message() : '未知错误'
        ]);
        return false;
    }
    
    // 添加元数据
    update_post_meta($history_id, '_pix2me_prompt', $prompt);
    update_post_meta($history_id, '_pix2me_model', $model);
    update_post_meta($history_id, '_pix2me_api_type', $api_type);
    update_post_meta($history_id, '_pix2me_resolution', $resolution);
    update_post_meta($history_id, '_pix2me_quality', $quality);
    update_post_meta($history_id, '_pix2me_credit_cost', $credit_cost);
    update_post_meta($history_id, '_pix2me_operation_type', $operation_type);
    
    // 保存图像ID关联
    if (!empty($image_ids)) {
        update_post_meta($history_id, '_pix2me_image_ids', $image_ids);
        
        // 如果有特色图像，设置第一个为特色图像
        if (!empty($image_ids[0])) {
            set_post_thumbnail($history_id, $image_ids[0]);
        }
    }
    
    ai_debug_log('历史记录创建成功', [
        '历史记录ID' => $history_id,
        '图像IDs' => $image_ids
    ]);
    
    return $history_id;
}

// 注册AJAX处理程序
add_action('wp_ajax_pix2me_ai_image_generator_ajax_generate_image', 'pix2me_ai_image_generator_ajax_generate_image');
add_action('wp_ajax_pix2me_get_image_details', 'pix2me_get_image_details');
add_action('wp_ajax_pix2me_delete_image', 'pix2me_delete_image');
add_action('wp_ajax_pix2me_load_gallery_images', 'pix2me_load_gallery_images');
add_action('wp_ajax_pix2me_delete_gallery_image', 'pix2me_delete_gallery_image');
add_action('wp_ajax_pix2me_update_image_privacy', 'pix2me_update_image_privacy');
add_action('wp_ajax_pix2me_edit_image', 'pix2me_ai_image_editor_ajax_edit_image');
add_action('wp_ajax_pix2me_load_public_gallery_images', 'pix2me_load_public_gallery_images');
add_action('wp_ajax_nopriv_pix2me_load_public_gallery_images', 'pix2me_load_public_gallery_images');

function pix2me_load_public_gallery_images() {
    check_ajax_referer('pix2me_details_nonce', 'nonce');
    $paged = max(1, intval($_POST['page'] ?? 1));
    $per_page = 9;
    $args = array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => $per_page,
        'paged' => $paged,
        'meta_query' => array(
            array('key' => '_ai_generated_image', 'value' => '1', 'compare' => '='),
            array('key' => '_pix2me_private', 'value' => '0', 'compare' => '='),
        ),
        'orderby' => 'date',
        'order' => 'DESC',
    );
    $query = new WP_Query($args);
    $images = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $id = get_the_ID();
            $url = wp_get_attachment_url($id);
            $author_id = get_post_field('post_author', $id);
            $author_name = get_the_author_meta('display_name', $author_id);
            $images[] = array(
                'id' => $id,
                'url' => $url,
                'author_name' => $author_name,
            );
        }
        wp_reset_postdata();
    }
    wp_send_json_success(array('images' => $images));
}
?>