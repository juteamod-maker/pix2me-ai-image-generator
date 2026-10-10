<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * 记录调试信息到错误日志
 *
 * @param string $message 要记录的消息
 * @param mixed $data 要记录的数据（可选）
 * @return void
 */
function ai_debug_log($message, $data = null) {
    // 检查是否启用了调试模式
    $plugin_options = get_option('pix2me_ai_image_generator_options', array());
    $debug_enabled = isset($plugin_options['enable_debug_logging']) && $plugin_options['enable_debug_logging'] === '1';
    
    if (!$debug_enabled) {
        return;
    }
    
    // 记录消息
    error_log('[Pix2me Debug] ' . $message);
    
    // 如果提供了数据，也记录数据
    if ($data !== null) {
        if (is_array($data) || is_object($data)) {
            error_log('[Pix2me Debug Data] ' . print_r($data, true));
        } else {
            error_log('[Pix2me Debug Data] ' . $data);
        }
    }
}

/**
 * Handles the communication with the Stable Diffusion API.
 *
 * @param string $prompt The text prompt for image generation.
 * @param array $options Additional options (e.g., resolution, quantity, negative_prompt, model, etc.).
 * @return array|WP_Error An array of image URLs on success, WP_Error on failure.
 */
function ai_generate_image_api( $prompt, $options = array() ) {
    // --- Configuration - Get API details from plugin options ---
    $plugin_options = get_option( 'pix2me_ai_image_generator_options', array() );
    $api_type = isset($plugin_options['api_type']) ? $plugin_options['api_type'] : 'stable_diffusion';

    // Based on the API type, call appropriate handler
    if ($api_type === 'openai') {
        return ai_generate_image_openai_api($prompt, $options);
    } else {
        // Default to Stable Diffusion API
        return ai_generate_image_sd_api($prompt, $options);
    }
}

/**
 * Handles the communication with the Stable Diffusion API.
 *
 * @param string $prompt The text prompt for image generation.
 * @param array $options Additional options (e.g., resolution, quantity, negative_prompt, model, etc.).
 * @return array|WP_Error An array of image URLs on success, WP_Error on failure.
 */
function ai_generate_image_sd_api( $prompt, $options = array() ) {
    // --- Configuration - Get API details from plugin options ---
    $plugin_options = get_option( 'pix2me_ai_image_generator_options', array() );
    $api_key = isset($plugin_options['api_key']) ? $plugin_options['api_key'] : '';
    $api_endpoint = isset($plugin_options['api_endpoint']) ? $plugin_options['api_endpoint'] : '';

    if ( empty( $api_key ) || empty( $api_endpoint ) ) {
        return new WP_Error( 'api_config_missing', __( 'Stable Diffusion API Key or Endpoint is not configured.', 'pix2me-ai-image-generator' ) );
    }

    // --- Prepare API Request Data ---
    $default_options = array(
        'width' => 512,
        'height' => 512,
        'samples' => 1, // Number of images to generate
        // Add other default parameters your API supports
        // 'negative_prompt' => 'low quality, blurry',
        // 'model_id' => 'stable-diffusion-v1-5',
        // 'guidance_scale' => 7.5,
        // 'num_inference_steps' => 30,
    );

    $request_data = array_merge( $default_options, $options );
    $request_data['prompt'] = $prompt;
    // Ensure required fields are present based on your specific API

    // --- Make the API Call ---
    $args = array(
        'method'    => 'POST',
        'headers'   => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key, // Or appropriate auth header
            // Add any other required headers
        ),
        'body'      => json_encode( $request_data ),
        'timeout'   => 60, // Increase timeout for potentially long generation times
        'sslverify' => true,
    );

    $response = wp_remote_post( $api_endpoint, $args );

    // --- Handle API Response ---
    if ( is_wp_error( $response ) ) {
        // Network error or WP HTTP API error
        return new WP_Error( 'api_connection_error', __( 'Could not connect to the image generation service.', 'pix2me-ai-image-generator' ), $response->get_error_message() );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $decoded_body = json_decode( $response_body, true );

    if ( $response_code >= 400 ) {
        // API returned an error status code
        $error_message = isset( $decoded_body['message'] ) ? $decoded_body['message'] : __( 'Unknown API error occurred.', 'pix2me-ai-image-generator' );
        return new WP_Error( 'api_error', $error_message, array( 'status_code' => $response_code, 'response' => $decoded_body ) );
    }

    if ( ! $decoded_body || ! isset( $decoded_body['output'] ) || ! is_array( $decoded_body['output'] ) ) {
        return new WP_Error( 'api_response_format_error', __( 'Received an unexpected response from the image generation service.', 'pix2me-ai-image-generator' ), $response_body );
    }

    // --- Process Successful Response --- 
    // Assuming the API returns an array of image URLs in the 'output' key
    $image_urls = $decoded_body['output'];

    // You might need further processing here:
    // - Download images to local server? (Recommended for privacy and control)
    // - Create attachments in WordPress Media Library?
    // - Store generation details (prompt, parameters, user ID, image URL/ID) in a custom table or post type.

    // Return both the URLs and the raw decoded response body
    return array(
        'image_urls' => $image_urls,
        'raw_response' => $decoded_body
    );
}

/**
 * Handles the communication with the OpenAI API for image generation.
 *
 * @param string $prompt The text prompt for image generation.
 * @param array $options Additional options (e.g., resolution, quantity, model, etc.).
 * @return array|WP_Error An array of image URLs on success, WP_Error on failure.
 */
function ai_generate_image_openai_api( $prompt, $options = array() ) {
    // --- Configuration - Get API details from plugin options ---
    $plugin_options = get_option( 'pix2me_ai_image_generator_options', array() );
    $api_key = isset($plugin_options['openai_api_key']) ? $plugin_options['openai_api_key'] : '';
    
    // OpenAI image generation API endpoint - 不同模型使用不同端点
    $model = isset($options['model']) ? $options['model'] : (isset($plugin_options['openai_model']) ? $plugin_options['openai_model'] : 'dall-e-3');
    
    // 根据模型选择正确的API端点
    $api_endpoint = 'https://api.openai.com/v1/images/generations';

    if ( empty( $api_key ) ) {
        return new WP_Error( 'api_config_missing', __( 'OpenAI API Key is not configured.', 'pix2me-ai-image-generator' ) );
    }

    // --- Prepare OpenAI API Request Data ---
    // Parse resolution into size for OpenAI
    $size = '1024x1024'; // Default size
    if (isset($options['resolution'])) {
        // Convert resolution format if needed
        $size = $options['resolution'];
    }
    
    // 确定是否使用DALL-E 3模型
    $use_dalle3 = isset($plugin_options['openai_model']) && $plugin_options['openai_model'] === 'dall-e-3';
    $use_gpt_image_1 = isset($plugin_options['openai_model']) && $plugin_options['openai_model'] === 'gpt-image-1';
    $model = $use_dalle3 ? 'dall-e-3' : ($use_gpt_image_1 ? 'gpt-image-1' : 'dall-e-2');

    // 设置num_outputs参数，如果未提供则默认为1
    $n = 1;
    if ($model === 'dall-e-2') {
        // 从传入的选项获取num_outputs参数
        if (isset($options['num_outputs'])) {
            $n = intval($options['num_outputs']);
            if ($n < 1) $n = 1;
            if ($n > 10) $n = 10; // OpenAI最大值
        }
    }

    // 根据不同模型处理质量参数
    if ($model === 'dall-e-3') {
        // DALL-E 3 支持 standard 或 hd 质量
        $quality = isset($options['quality']) && $options['quality'] === 'hd' ? 'hd' : 'standard';
    } else if ($model === 'gpt-image-1') {
        // GPT-Image-1 支持 low、medium 或 high 质量
        if (isset($options['quality'])) {
            if (in_array($options['quality'], ['low', 'medium', 'high'])) {
                $quality = $options['quality'];
            } else {
                $quality = 'low'; // 默认使用低质量
            }
        } else {
            $quality = 'low'; // 默认使用低质量
        }
    } else {
        // DALL-E 2 不支持质量参数
        $quality = null;
    }

    // 准备请求数据
    $data = [
        'prompt' => $prompt,
        'n' => $n,
        'size' => $size,
        'model' => $model
    ];

    // 根据模型添加质量参数
    if ($model === 'dall-e-3' && $quality !== null) {
        $data['quality'] = $quality;
    } else if ($model === 'gpt-image-1' && $quality !== null) {
        $data['quality'] = $quality;
    }

    // GPT-4 Vision不支持style参数，仅在DALL-E 3模型上添加style参数
    if ($model === 'dall-e-3') {
        $style = isset($options['style']) ? $options['style'] : 'natural'; // 默认使用natural风格
        $data['style'] = $style;
    }

    // --- Make the API Call ---
    $args = array(
        'method'    => 'POST',
        'headers'   => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
        ),
        'body'      => json_encode( $data ),
        'timeout'   => 120, // 增加超时时间，GPT-4 Vision可能需要更长处理时间
        'sslverify' => true,
    );

    // 记录请求详情
    $response = wp_remote_post( $api_endpoint, $args );

    // --- Handle API Response ---
    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'api_connection_error', __( 'Could not connect to the OpenAI image generation service.', 'pix2me-ai-image-generator' ), $response->get_error_message() );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $response_headers = wp_remote_retrieve_headers( $response );
    
    // 记录响应信息
    ai_debug_log('收到API响应', [
        '响应代码' => $response_code,
        '响应头' => $response_headers
    ]);
    
    // 尝试解码响应体
    $decoded_body = json_decode($response_body, true);
    
    // 检查是否能成功解码JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        ai_debug_log('JSON解码错误', json_last_error_msg());
        ai_debug_log('原始响应体', substr($response_body, 0, 1000) . (strlen($response_body) > 1000 ? '...[truncated]' : ''));
        
        // 尝试检测响应是否为图像数据而不是JSON
        if (strpos($response_headers['content-type'], 'image/') === 0) {
            ai_debug_log('检测到响应是图像数据而不是JSON');
            
            // 保存响应数据为图像文件
            $temp_dir = get_temp_dir();
            $temp_filename = md5(time() . rand(1000, 9999)) . '.png';
            $file_path = $temp_dir . $temp_filename;
            
            if (file_put_contents($file_path, $response_body)) {
                $image_url = site_url('/wp-content/uploads/temp/' . $temp_filename);
                
                // 返回单个图像URL
                return [
                    'image_urls' => [$image_url],
                    'model_used' => 'gpt-image-1'
                ];
            }
        }
        
        return new WP_Error('api_response_format_error', __('API响应不是有效的JSON格式', 'pix2me-ai-image-generator'), 
            ['response_code' => $response_code, 'content_type' => $response_headers['content-type']]);
    }

    // 对不同模型响应进行特定处理
    if ($model === 'gpt-image-1') {
        // gpt-image-1模型可能返回不同结构
        if (!$decoded_body) {
            return new WP_Error('api_response_format_error', __('Received an empty response from OpenAI GPT-4 Vision.', 'pix2me-ai-image-generator'), $response_body);
        }

        // 尝试获取图像数据
        if (isset($decoded_body['data']) && is_array($decoded_body['data'])) {
            $image_urls = array();
            $image_temp_files = array();
            
            foreach ($decoded_body['data'] as $image) {
                if (isset($image['url'])) {
                    $image_urls[] = $image['url'];
                } elseif (isset($image['b64_json'])) {
                    // 处理base64编码的图像数据
                    $b64_data = $image['b64_json'];
                    
                    // 创建临时文件存储base64解码后的图像
                    $upload_dir = wp_upload_dir();
                    $temp_dir = $upload_dir['basedir'] . '/pix2me-temp';
                    
                    // 确保临时目录存在
                    if (!file_exists($temp_dir)) {
                        wp_mkdir_p($temp_dir);
                    }
                    
                    // 生成唯一文件名
                    $file_name = 'gpt-img-' . uniqid() . '.png';
                    $file_path = $temp_dir . '/' . $file_name;
                    
                    // 解码base64数据并写入文件
                    $decoded_image = base64_decode($b64_data);
                    if ($decoded_image !== false) {
                        $result = file_put_contents($file_path, $decoded_image);
                        if ($result !== false) {
                            // 使用临时URL来处理这个图像
                            $image_urls[] = $upload_dir['baseurl'] . '/pix2me-temp/' . $file_name;
                            $image_temp_files[] = $file_path; // 记录临时文件以便最后处理
                        }
                    }
                }
            }

            if (!empty($image_urls)) {
                return array(
                    'image_urls' => $image_urls,
                    'raw_response' => $decoded_body,
                    'model_used' => $model,
                    'temp_files' => $image_temp_files // 添加临时文件路径以便清理
                );
            }
        } elseif (isset($decoded_body['url']) && !empty($decoded_body['url'])) {
            // 直接返回单个URL的情况
            return array(
                'image_urls' => array($decoded_body['url']),
                'raw_response' => $decoded_body,
                'model_used' => $model
            );
        }

        // 如果执行到这里，说明无法找到有效图像URL
        return new WP_Error('api_no_images', __('No images were returned by OpenAI GPT-4 Vision.', 'pix2me-ai-image-generator'), $decoded_body);
    }

    // 标准OpenAI响应处理
    if ( ! $decoded_body || ! isset( $decoded_body['data'] ) || ! is_array( $decoded_body['data'] ) ) {
        return new WP_Error( 'api_response_format_error', __( 'Received an unexpected response from OpenAI.', 'pix2me-ai-image-generator' ), $response_body );
    }

    // --- Process Successful Response ---
    // Extract image URLs from the OpenAI response format
    $image_urls = array();
    foreach ( $decoded_body['data'] as $image ) {
        if ( isset( $image['url'] ) ) {
            $image_urls[] = $image['url'];
        }
    }

    if ( empty( $image_urls ) ) {
        return new WP_Error( 'api_no_images', __( 'No images were returned by OpenAI.', 'pix2me-ai-image-generator' ), $decoded_body );
    }

    // Return both the URLs and the raw decoded response body
    return array(
        'image_urls' => $image_urls,
        'raw_response' => $decoded_body,
        'model_used' => $model
    );
}

/**
 * Handles image editing using AI APIs.
 *
 * @param string $prompt The text prompt for image editing.
 * @param array $params Additional parameters including the image data.
 * @return array|WP_Error An array of edited image URLs on success, WP_Error on failure.
 */
function ai_edit_image_api($prompt, $params = array()) {
    // 获取API配置
    $plugin_options = get_option('pix2me_ai_image_generator_options', array());
    $api_type = isset($params['api_type']) ? $params['api_type'] : (isset($plugin_options['api_type']) ? $plugin_options['api_type'] : 'stable_diffusion');
    
    // 根据API类型调用相应的处理函数
    if ($api_type === 'openai') {
        // 检查是否使用gpt-image-1模型
        $model = isset($params['model']) ? $params['model'] : (isset($plugin_options['openai_model']) ? $plugin_options['openai_model'] : 'dall-e-2');
        
        if ($model === 'gpt-image-1') {
            return ai_edit_image_gpt_image_1($prompt, $params);
        } else {
            return ai_edit_image_openai_api($prompt, $params);
        }
    } else {
        // 默认使用Stable Diffusion API
        return ai_edit_image_sd_api($prompt, $params);
    }
}

/**
 * Handles image editing using OpenAI's API.
 *
 * @param string $prompt The text prompt for image editing.
 * @param array $params Additional parameters including the image data.
 * @return array|WP_Error An array of edited image URLs on success, WP_Error on failure.
 */
function ai_edit_image_openai_api($prompt, $params = array()) {
    // 获取API配置
    $plugin_options = get_option('pix2me_ai_image_generator_options', array());
    $api_key = isset($plugin_options['openai_api_key']) ? $plugin_options['openai_api_key'] : '';
    
    if (empty($api_key)) {
        return new WP_Error('api_config_missing', __('OpenAI API Key is not configured.', 'pix2me-ai-image-generator'));
    }
    
    // 验证必要参数
    if (empty($params['image'])) {
        return new WP_Error('missing_image', __('No image data provided for editing.', 'pix2me-ai-image-generator'));
    }
    
    try {
        // 准备API请求数据
        $model = isset($params['model']) ? $params['model'] : 'gpt-image-1';
        
        // 对不同模型使用不同的API端点
        if ($model === 'gpt-image-1') {
            // 使用 GPT-Image-1 模型的图像生成API
            return ai_edit_image_gpt_image_1($prompt, $params);
        }
        
        // 默认使用标准 OpenAI 编辑API
        $api_endpoint = 'https://api.openai.com/v1/images/edits';
        
        // 解析分辨率
        $size = isset($params['resolution']) ? $params['resolution'] : '1024x1024';
        
        // 准备multipart表单数据
        $boundary = wp_generate_password(24, false);
        $payload = '';
        
        // 添加图片数据
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Disposition: form-data; name=\"image\"; filename=\"image.png\"\r\n";
        $payload .= "Content-Type: image/png\r\n\r\n";
        $payload .= base64_decode($params['image']) . "\r\n";
        
        // 添加蒙版数据（如果有）
        if (!empty($params['mask'])) {
            $payload .= "--$boundary\r\n";
            $payload .= "Content-Disposition: form-data; name=\"mask\"; filename=\"mask.png\"\r\n";
            $payload .= "Content-Type: image/png\r\n\r\n";
            $payload .= base64_decode($params['mask']) . "\r\n";
        }
        
        // 添加其他参数
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Disposition: form-data; name=\"prompt\"\r\n\r\n";
        $payload .= $prompt . "\r\n";
        
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Disposition: form-data; name=\"n\"\r\n\r\n";
        $payload .= (isset($params['num_outputs']) ? intval($params['num_outputs']) : 1) . "\r\n";
        
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Disposition: form-data; name=\"size\"\r\n\r\n";
        $payload .= $size . "\r\n";
        
        $payload .= "--$boundary\r\n";
        $payload .= "Content-Disposition: form-data; name=\"model\"\r\n\r\n";
        $payload .= $model . "\r\n";
        
        $payload .= "--$boundary--\r\n";
        
        // 发送API请求
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
                'Content-Length' => strlen($payload)
            ),
            'body' => $payload,
            'timeout' => 120
        );
        
        $response = wp_remote_post($api_endpoint, $args);
        
        // 如果创建了临时文件，请删除它
        if (isset($temp_file) && file_exists($temp_file)) {
            @unlink($temp_file);
        }
        
        // 如果创建了临时PNG文件，删除它
        if (isset($temp_png_file) && file_exists($temp_png_file)) {
            @unlink($temp_png_file);
        }

        // 处理API响应
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('WP API请求错误: ' . $error_message);
            return new WP_Error('api_connection_error', __('无法连接到OpenAI图像生成服务', 'pix2me-ai-image-generator'), $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        // 记录响应信息
        ai_debug_log('收到OpenAI编辑API响应', [
            '响应代码' => $response_code,
            '响应头' => $response_headers
        ]);
        
        // 检查是否能成功解码JSON
        $decoded_body = json_decode($response_body, true);
        
        // 检查是否能成功解码JSON
        if (json_last_error() !== JSON_ERROR_NONE) {
            ai_debug_log('JSON解码错误', json_last_error_msg());
            ai_debug_log('原始响应体', substr($response_body, 0, 1000) . (strlen($response_body) > 1000 ? '...[truncated]' : ''));
            
            // 尝试检测响应是否为图像数据而不是JSON
            if (strpos($response_headers['content-type'], 'image/') === 0) {
                ai_debug_log('检测到响应是图像数据而不是JSON');
                
                // 保存响应数据为图像文件
                $temp_dir = get_temp_dir();
                $temp_filename = md5(time() . rand(1000, 9999)) . '.png';
                $file_path = $temp_dir . $temp_filename;
                
                if (file_put_contents($file_path, $response_body)) {
                    $image_url = site_url('/wp-content/smush-webp/temp/' . $temp_filename);
                    
                    // 返回单个图像URL
                    return [
                        'image_urls' => [$image_url],
                        'model_used' => 'gpt-image-1'
                    ];
                }
            }
            
            return new WP_Error('api_response_format_error', __('API响应不是有效的JSON格式', 'pix2me-ai-image-generator'), 
                ['response_code' => $response_code, 'content_type' => $response_headers['content-type']]);
        }
        
        if (!isset($decoded_body['data']) || !is_array($decoded_body['data'])) {
            return new WP_Error('api_response_format_error', __('Received an unexpected response from the image editing service.', 'pix2me-ai-image-generator'));
        }
        
        // 提取图片URL
        $image_urls = array_map(function($item) {
            return $item['url'];
        }, $decoded_body['data']);
        
        return array(
            'image_urls' => $image_urls,
            'model_used' => $model,
            'raw_response' => $decoded_body
        );
        
    } catch (Exception $e) {
        return new WP_Error('api_exception', __('An unexpected error occurred during image editing.', 'pix2me-ai-image-generator'));
    }

    // 处理错误响应
    if ($response_code >= 400) {
        $error_message = isset($decoded_body['error']['message']) ? $decoded_body['error']['message'] : __('发生未知API错误', 'pix2me-ai-image-generator');
        $error_data = [
            'status_code' => $response_code,
            'response' => $decoded_body,
            'request_data' => [
                'api_endpoint' => $api_endpoint,
                'model' => isset($params['model']) ? $params['model'] : 'dall-e-2',
                'prompt' => $prompt,
                'quality' => isset($params['quality']) ? $params['quality'] : 'standard',
                'has_image' => true
            ]
        ];
        
        // 将错误记录到错误日志以便调试
        ai_debug_log('OpenAI API错误', [
            '错误消息' => $error_message,
            '状态码' => $response_code,
            '响应体' => $decoded_body
        ]);
        
        return new WP_Error('api_error', $error_message, $error_data);
    }
}

/**
 * 使用GPT-Image-1模型编辑图像
 *
 * @param string $prompt 图像生成提示词
 * @param array $params 附加参数，包括图像文件路径或base64图像数据
 * @return array|WP_Error 成功时返回图像URL数组，失败时返回WP_Error
 */
function ai_edit_image_gpt_image_1($prompt, $params = array()) {
    $plugin_options = get_option('pix2me_ai_image_generator_options', array());
    $api_key = isset($plugin_options['openai_api_key']) ? $plugin_options['openai_api_key'] : '';
    if (empty($api_key)) {
        return new WP_Error('api_config_missing', __('OpenAI API密钥未配置', 'pix2me-ai-image-generator'));
    }
    $api_endpoint = 'https://api.openai.com/v1/images/edits';
    ai_debug_log('使用GPT-Image-1编辑图像', [
        'API端点' => $api_endpoint,
        '提示词' => $prompt
    ]);
    $quality = isset($params['quality']) && in_array($params['quality'], ['low', 'medium', 'high']) ? $params['quality'] : 'low';
    $size = isset($params['resolution']) ? $params['resolution'] : '1024x1024';
    $model = 'gpt-image-1';
    $image_file = null;
    $temp_files = [];
    $media_ids = [];
    $media_urls = [];
    try {
        if (isset($params['image']) && !empty($params['image'])) {
            $image_data = $params['image'];
            if (strpos($image_data, 'data:image/') === 0) {
                $image_parts = explode(';base64,', $image_data);
                if (count($image_parts) !== 2) {
                    return new WP_Error('invalid_image_format', __('图像数据格式无效', 'pix2me-ai-image-generator'));
                }
                $image_base64 = $image_parts[1];
                $image_binary = base64_decode($image_base64);
            } else {
                $image_binary = base64_decode($image_data);
            }
            $temp_dir = get_temp_dir();
            $image_file = tempnam($temp_dir, 'img_edit_');
            file_put_contents($image_file, $image_binary);
            $temp_files[] = $image_file;
        } else if (isset($params['image_path']) && !empty($params['image_path'])) {
            if (!file_exists($params['image_path'])) {
                return new WP_Error('image_not_found', __('无法找到上传的图像文件', 'pix2me-ai-image-generator'));
            }
            $image_file = $params['image_path'];
        } else {
            return new WP_Error('missing_image', __('缺少图像数据', 'pix2me-ai-image-generator'));
        }
        // 构建multipart/form-data请求
        $boundary = '----Pix2meAI' . md5(uniqid('', true));
        $eol = "\r\n";
        $payload = '';
        $payload .= "--$boundary$eol";
        $payload .= "Content-Disposition: form-data; name=\"image\"; filename=\"image.png\"$eol";
        $payload .= "Content-Type: image/png$eol$eol";
        $payload .= file_get_contents($image_file) . $eol;
        $payload .= "--$boundary$eol";
        $payload .= "Content-Disposition: form-data; name=\"prompt\"$eol$eol";
        $payload .= $prompt . $eol;
        $payload .= "--$boundary$eol";
        $payload .= "Content-Disposition: form-data; name=\"model\"$eol$eol";
        $payload .= $model . $eol;
        $payload .= "--$boundary$eol";
        $payload .= "Content-Disposition: form-data; name=\"quality\"$eol$eol";
        $payload .= $quality . $eol;
        $payload .= "--$boundary$eol";
        $payload .= "Content-Disposition: form-data; name=\"size\"$eol$eol";
        $payload .= $size . $eol;
        $payload .= "--$boundary$eol";
        $payload .= "Content-Disposition: form-data; name=\"n\"$eol$eol";
        $payload .= "1" . $eol;
        $payload .= "--$boundary--$eol";
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $api_endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: multipart/form-data; boundary=' . $boundary
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload
        ]);
        $response_body = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        ai_debug_log('收到OpenAI编辑响应', [
            '响应代码' => $response_code,
            '错误' => $curl_error ?: '无',
            '响应体' => mb_substr($response_body, 0, 500)
        ]);
        if ($curl_error) {
            return new WP_Error('curl_error', __('请求失败', 'pix2me-ai-image-generator') . ': ' . $curl_error);
        }
        if ($response_code != 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : __('未知错误', 'pix2me-ai-image-generator');
            ai_debug_log('API错误', [
                '状态码' => $response_code,
                '错误信息' => $error_message,
                '响应体' => $response_body
            ]);
            return new WP_Error('api_error', $error_message, [
                'status_code' => $response_code,
                'response' => $error_data
            ]);
        }
        $decoded_body = json_decode($response_body, true);
        if (!isset($decoded_body['data']) || !is_array($decoded_body['data'])) {
            ai_debug_log('API响应格式错误', [
                '响应体' => $response_body
            ]);
            return new WP_Error('invalid_response', __('API响应格式错误', 'pix2me-ai-image-generator'));
        }
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        foreach ($decoded_body['data'] as $item) {
            if (!isset($item['url']) && !isset($item['b64_json'])) {
                continue;
            }
            // 只处理b64_json，且保存为png
            if (isset($item['b64_json'])) {
                $image_binary = base64_decode($item['b64_json']);
                $upload_dir = wp_upload_dir();
                $pix2me_dir = $upload_dir['basedir'] . '/pix2me-temp';
                if (!file_exists($pix2me_dir)) {
                    wp_mkdir_p($pix2me_dir);
                }
                $image_filename = 'gpt-image-' . time() . '-' . mt_rand(1000, 9999) . '.png';
                $image_path = $pix2me_dir . '/' . $image_filename;
                file_put_contents($image_path, $image_binary);
                chmod($image_path, 0644);
                // 注册到媒体库
                $file_array = array(
                    'name' => $image_filename,
                    'tmp_name' => $image_path
                );
                $attachment_id = media_handle_sideload($file_array, 0, $prompt);
                if (!is_wp_error($attachment_id)) {
                    // 写入元数据
                    update_post_meta($attachment_id, '_ai_generation_prompt', $prompt);
                    update_post_meta($attachment_id, '_ai_generated_image', '1');
                    update_post_meta($attachment_id, '_pix2me_model', $model);
                    update_post_meta($attachment_id, '_pix2me_quality', $quality);
                    update_post_meta($attachment_id, '_pix2me_resolution', $size);
                    update_post_meta($attachment_id, '_ai_parameters', json_encode([
                        'model' => $model,
                        'quality' => $quality,
                        'resolution' => $size
                    ]));
                    $media_ids[] = $attachment_id;
                    $media_urls[] = wp_get_attachment_url($attachment_id);
                }
                // 删除临时文件
                @unlink($image_path);
            }
        }
        if (empty($media_urls)) {
            return new WP_Error('no_images', __('未能生成任何图像', 'pix2me-ai-image-generator'));
        }
        return [
            'image_urls' => $media_urls,
            'image_ids' => $media_ids,
            'model_used' => $model
        ];
    } finally {
        foreach ($temp_files as $temp_file) {
            if (file_exists($temp_file)) {
                @unlink($temp_file);
            }
        }
    }
}

/**
 * Handles image editing using Stable Diffusion API.
 *
 * @param string $prompt The text prompt for image editing.
 * @param array $params Additional parameters including the image data.
 * @return array|WP_Error An array of edited image URLs on success, WP_Error on failure.
 */
function ai_edit_image_sd_api($prompt, $params = array()) {
    // 获取API配置
    $plugin_options = get_option('pix2me_ai_image_generator_options', array());
    $api_key = isset($plugin_options['stable_diffusion_api_key']) ? $plugin_options['stable_diffusion_api_key'] : '';
    $api_endpoint = isset($plugin_options['stable_diffusion_api_endpoint']) ? $plugin_options['stable_diffusion_api_endpoint'] : '';
    
    if (empty($api_key) || empty($api_endpoint)) {
        return new WP_Error('api_config_missing', __('Stable Diffusion API configuration is missing.', 'pix2me-ai-image-generator'));
    }
    
    // 验证必要参数
    if (empty($params['image'])) {
        return new WP_Error('missing_image', __('No image data provided for editing.', 'pix2me-ai-image-generator'));
    }
    
    // 准备API请求数据
    $request_data = array(
        'prompt' => $prompt,
        'init_image' => $params['image'],
        'mask' => isset($params['mask']) ? $params['mask'] : null,
        'num_outputs' => isset($params['num_outputs']) ? intval($params['num_outputs']) : 1,
        'width' => 1024,
        'height' => 1024
    );
    
    // 根据分辨率设置宽高
    if (isset($params['resolution'])) {
        list($width, $height) = explode('x', $params['resolution']);
        $request_data['width'] = intval($width);
        $request_data['height'] = intval($height);
    }
    
    // 发送API请求
    $args = array(
        'method' => 'POST',
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key
        ),
        'body' => json_encode($request_data),
        'timeout' => 120
    );
    
    $response = wp_remote_post($api_endpoint, $args);
    
    // 处理响应
    if (is_wp_error($response)) {
        return new WP_Error('api_connection_error', __('Could not connect to the Stable Diffusion image editing service.', 'pix2me-ai-image-generator'));
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $response_headers = wp_remote_retrieve_headers($response);
    
    // 记录响应信息
    ai_debug_log('收到Stable Diffusion API响应', [
        '响应代码' => $response_code,
        '响应头' => $response_headers
    ]);
    
    // 尝试解码响应体
    $decoded_body = json_decode($response_body, true);
    
    // 检查是否能成功解码JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        ai_debug_log('JSON解码错误', json_last_error_msg());
        ai_debug_log('原始响应体', substr($response_body, 0, 1000) . (strlen($response_body) > 1000 ? '...[truncated]' : ''));
        
        return new WP_Error('api_response_format_error', __('API响应不是有效的JSON格式', 'pix2me-ai-image-generator'), 
            ['response_code' => $response_code, 'response_body' => substr($response_body, 0, 500)]);
    }
    
    // 处理错误响应
    if ($response_code >= 400) {
        $error_message = isset($decoded_body['error']) ? $decoded_body['error'] : __('发生未知API错误', 'pix2me-ai-image-generator');
        $error_data = [
            'status_code' => $response_code,
            'response' => $decoded_body,
            'request_data' => [
                'api_endpoint' => $api_endpoint,
                'prompt' => $prompt,
                'resolution' => isset($params['resolution']) ? $params['resolution'] : '1024x1024',
                'has_image' => !empty($params['image'])
            ]
        ];
        
        // 将错误记录到错误日志以便调试
        ai_debug_log('Stable Diffusion API错误', [
            '错误消息' => is_string($error_message) ? $error_message : json_encode($error_message),
            '状态码' => $response_code,
            '响应体' => $decoded_body
        ]);
        
        return new WP_Error('api_error', is_string($error_message) ? $error_message : __('发生未知API错误', 'pix2me-ai-image-generator'), $error_data);
    }
    
    if (!isset($decoded_body['output']) || !is_array($decoded_body['output'])) {
        return new WP_Error('api_response_format_error', __('Received an unexpected response from the image editing service.', 'pix2me-ai-image-generator'));
    }
    
    return array(
        'image_urls' => $decoded_body['output'],
        'model_used' => isset($params['model']) ? $params['model'] : 'stable-diffusion-v1-5',
        'raw_response' => $decoded_body
    );
}

?>