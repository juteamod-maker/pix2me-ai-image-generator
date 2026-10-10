<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Add admin menu page for Pix2me AI Image Generator Settings.
 */
function pix2me_ai_image_generator_add_admin_menu() {
    add_options_page(
        __( 'Pix2me AI Image Generator Settings', 'pix2me-ai-image-generator' ),
        __( 'Pix2me AI Generator', 'pix2me-ai-image-generator' ),
        'manage_options',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_settings_page_html'
    );
}
add_action( 'admin_menu', 'pix2me_ai_image_generator_add_admin_menu' );

/**
 * Register plugin settings using the Settings API.
 */
function pix2me_ai_image_generator_register_settings() {
    // Register a setting group
    register_setting( 
        'pix2me_ai_image_generator_options', 
        'pix2me_ai_image_generator_options', 
        'pix2me_ai_image_generator_options_sanitize' 
    );

    // Add settings section for API selection
    add_settings_section(
        'pix2me_ai_image_generator_api_selection_section', 
        __( 'API Selection', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_api_selection_section_callback',
        'pix2me-ai-image-generator-settings'
    );
    
    // Add API type field
    add_settings_field(
        'api_type',
        __( 'API Type', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_api_type_render',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_api_selection_section'
    );

    // Add settings section for Stable Diffusion API
    add_settings_section(
        'pix2me_ai_image_generator_api_section',
        __( 'Stable Diffusion API Configuration', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_api_section_callback',
        'pix2me-ai-image-generator-settings'
    );

    // Add settings fields
    add_settings_field(
        'api_key',
        __( 'API Key', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_api_key_render',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_api_section'
    );

    add_settings_field(
        'api_endpoint',
        __( 'API Endpoint URL', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_api_endpoint_render',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_api_section'
    );
    
    // Add settings section for OpenAI API
    add_settings_section(
        'pix2me_ai_image_generator_openai_section',
        __( 'OpenAI API Configuration', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_openai_section_callback',
        'pix2me-ai-image-generator-settings'
    );
    
    // Add OpenAI API Key field
    add_settings_field(
        'openai_api_key',
        __( 'OpenAI API Key', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_openai_api_key_render',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_openai_section'
    );
    
    // Add OpenAI Model field
    add_settings_field(
        'openai_model',
        __( 'OpenAI Model', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_openai_model_render',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_openai_section'
    );
    
    // Add OpenAI Quality field
    add_settings_field(
        'openai_quality',
        __( 'Image Quality', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_openai_quality_render',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_openai_section'
    );
    
    // Add OpenAI Style field
    add_settings_field(
        'openai_style',
        __( 'Image Style (DALL-E 3 only)', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_openai_style_render',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_openai_section'
    );

    // Add general settings section
    add_settings_section(
        'pix2me_ai_image_generator_general_section',
        __( 'General Settings', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_general_section_callback',
        'pix2me-ai-image-generator-settings'
    );
    
    // Add generation cost field
    add_settings_field(
        'generation_cost',
        __( 'Generation Cost (myCred Points)', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_generation_cost_render',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_general_section'
    );

    // Add edit cost field
    add_settings_field(
        'edit_cost',
        __( 'Edit Cost (myCred Points)', 'pix2me-ai-image-generator' ),
        'pix2me_ai_image_generator_edit_cost_render',
        'pix2me-ai-image-generator-settings',
        'pix2me_ai_image_generator_general_section'
    );
}
add_action( 'admin_init', 'pix2me_ai_image_generator_register_settings' );

/**
 * Sanitize options data before saving.
 *
 * @param array $input Raw input data.
 * @return array Sanitized data.
 */
function pix2me_ai_image_generator_options_sanitize( $input ) {
    $sanitized_input = array();
    
    // API Type
    if ( isset( $input['api_type'] ) ) {
        $sanitized_input['api_type'] = sanitize_text_field( $input['api_type'] );
    }
    
    // Stable Diffusion Settings
    if ( isset( $input['api_key'] ) ) {
        $sanitized_input['api_key'] = sanitize_text_field( $input['api_key'] );
    }
    if ( isset( $input['api_endpoint'] ) ) {
        $sanitized_input['api_endpoint'] = esc_url_raw( trim( $input['api_endpoint'] ) );
    }
    
    // OpenAI Settings
    if ( isset( $input['openai_api_key'] ) ) {
        $sanitized_input['openai_api_key'] = sanitize_text_field( $input['openai_api_key'] );
    }
    if ( isset( $input['openai_model'] ) ) {
        $sanitized_input['openai_model'] = sanitize_text_field( $input['openai_model'] );
    }
    if ( isset( $input['openai_quality'] ) ) {
        $sanitized_input['openai_quality'] = sanitize_text_field( $input['openai_quality'] );
    }
    if ( isset( $input['openai_style'] ) ) {
        $sanitized_input['openai_style'] = sanitize_text_field( $input['openai_style'] );
    }
    
    // General Settings
    if ( isset( $input['generation_cost'] ) ) {
        $sanitized_input['generation_cost'] = absint( $input['generation_cost'] );
        if ( $sanitized_input['generation_cost'] <= 0 ) {
            $sanitized_input['generation_cost'] = 1;
        }
    }
    
    if ( isset( $input['edit_cost'] ) ) {
        $sanitized_input['edit_cost'] = absint( $input['edit_cost'] );
        if ( $sanitized_input['edit_cost'] <= 0 ) {
            $sanitized_input['edit_cost'] = 2;
        }
    }

    return $sanitized_input;
}

/**
 * Callback for the API selection section.
 */
function pix2me_ai_image_generator_api_selection_section_callback() {
    echo '<p>' . esc_html__( 'Select which AI image generation API to use.', 'pix2me-ai-image-generator' ) . '</p>';
}

/**
 * Render the API Type selection field.
 */
function pix2me_ai_image_generator_api_type_render() {
    $options = get_option( 'pix2me_ai_image_generator_options' );
    $api_type = isset( $options['api_type'] ) ? $options['api_type'] : 'stable_diffusion';
    ?>
    <select name="pix2me_ai_image_generator_options[api_type]" id="api_type">
        <option value="stable_diffusion" <?php selected( $api_type, 'stable_diffusion' ); ?>><?php esc_html_e( 'Stable Diffusion', 'pix2me-ai-image-generator' ); ?></option>
        <option value="openai" <?php selected( $api_type, 'openai' ); ?>><?php esc_html_e( 'OpenAI (DALL-E)', 'pix2me-ai-image-generator' ); ?></option>
    </select>
    <p class="description"><?php esc_html_e( 'Choose the API service you want to use for image generation.', 'pix2me-ai-image-generator' ); ?></p>
    <?php
}

/**
 * Callback for the API settings section.
 */
function pix2me_ai_image_generator_api_section_callback() {
    echo '<div class="pix2me-stable-diffusion-section">';
    echo '<p>' . esc_html__( 'Enter your Stable Diffusion API credentials and settings below.', 'pix2me-ai-image-generator' ) . '</p>';
    echo '</div>';
}

/**
 * Render the API Key input field.
 */
function pix2me_ai_image_generator_api_key_render() {
    $options = get_option( 'pix2me_ai_image_generator_options' );
    $api_key = isset( $options['api_key'] ) ? $options['api_key'] : '';
    echo '<input type="text" name="pix2me_ai_image_generator_options[api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
}

/**
 * Render the API Endpoint URL input field.
 */
function pix2me_ai_image_generator_api_endpoint_render() {
    $options = get_option( 'pix2me_ai_image_generator_options' );
    $api_endpoint = isset( $options['api_endpoint'] ) ? $options['api_endpoint'] : '';
    echo '<input type="url" name="pix2me_ai_image_generator_options[api_endpoint]" value="' . esc_attr( $api_endpoint ) . '" placeholder="https://your-api-provider.com/v1/images/generations" class="regular-text">';
}

/**
 * Callback for the OpenAI API section.
 */
function pix2me_ai_image_generator_openai_section_callback() {
    echo '<div class="pix2me-openai-section">';
    echo '<p>' . esc_html__( 'Enter your OpenAI API credentials and settings below.', 'pix2me-ai-image-generator' ) . '</p>';
    
    // 添加模型兼容性表
    ?>
    <div class="pix2me-model-compatibility">
        <h3><?php esc_html_e('OpenAI模型参数兼容性表', 'pix2me-ai-image-generator'); ?></h3>
        <p><?php esc_html_e('下表显示了不同OpenAI模型支持的参数选项:', 'pix2me-ai-image-generator'); ?></p>
        
        <table>
            <thead>
                <tr>
                    <th><?php esc_html_e('参数/模型', 'pix2me-ai-image-generator'); ?></th>
                    <th>DALL-E 2</th>
                    <th>DALL-E 3</th>
                    <th>GPT-4 Vision</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('质量选项', 'pix2me-ai-image-generator'); ?></td>
                    <td class="not-supported"><?php esc_html_e('不支持', 'pix2me-ai-image-generator'); ?></td>
                    <td class="supported">Standard / HD</td>
                    <td class="supported">Low / Medium / High</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('风格选项', 'pix2me-ai-image-generator'); ?></td>
                    <td class="not-supported"><?php esc_html_e('不支持', 'pix2me-ai-image-generator'); ?></td>
                    <td class="supported">Natural / Vivid</td>
                    <td class="not-supported"><?php esc_html_e('仅Natural', 'pix2me-ai-image-generator'); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('支持分辨率', 'pix2me-ai-image-generator'); ?></td>
                    <td class="supported">256x256<br>512x512<br>1024x1024</td>
                    <td class="supported">1024x1024<br>1024x1536<br>1536x1024</td>
                    <td class="supported">1024x1024<br>1024x1536<br>1536x1024</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('每个提示生成图像数', 'pix2me-ai-image-generator'); ?></td>
                    <td class="supported">1-10</td>
                    <td class="not-supported">1</td>
                    <td class="not-supported">1</td>
                </tr>
                <tr>
                    <td><?php esc_html_e('积分成本', 'pix2me-ai-image-generator'); ?></td>
                    <td>
                        256x256: 1积分<br>
                        512x512: 1积分<br>
                        1024x1024: 1积分
                    </td>
                    <td>
                        标准质量: 方形=2积分，非方形=3积分<br>
                        高清质量: 方形=3积分，非方形=4积分
                    </td>
                    <td>
                        低质量: 方形和非方形=1积分<br>
                        中等质量: 方形和非方形=2积分<br>
                        高质量: 方形=6积分，非方形=7积分
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php
    
    echo '</div>';
}

/**
 * Render the OpenAI API Key input field.
 */
function pix2me_ai_image_generator_openai_api_key_render() {
    $options = get_option( 'pix2me_ai_image_generator_options' );
    $api_key = isset( $options['openai_api_key'] ) ? $options['openai_api_key'] : '';
    echo '<input type="text" name="pix2me_ai_image_generator_options[openai_api_key]" value="' . esc_attr( $api_key ) . '" class="regular-text">';
}

/**
 * Render the OpenAI Model selection field.
 */
function pix2me_ai_image_generator_openai_model_render() {
    $options = get_option( 'pix2me_ai_image_generator_options' );
    $model = isset( $options['openai_model'] ) ? $options['openai_model'] : 'dall-e-3';
    ?>
    <select name="pix2me_ai_image_generator_options[openai_model]" id="openai_model">
        <option value="gpt-image-1" <?php selected( $model, 'gpt-image-1' ); ?>><?php esc_html_e( 'GPT-4 Vision (最新)', 'pix2me-ai-image-generator' ); ?></option>
        <option value="dall-e-3" <?php selected( $model, 'dall-e-3' ); ?>><?php esc_html_e( 'DALL-E 3', 'pix2me-ai-image-generator' ); ?></option>
        <option value="dall-e-2" <?php selected( $model, 'dall-e-2' ); ?>><?php esc_html_e( 'DALL-E 2', 'pix2me-ai-image-generator' ); ?></option>
    </select>
    <p class="description">
        <?php esc_html_e( '选择OpenAI图像生成模型', 'pix2me-ai-image-generator' ); ?>
        <br>
        <strong>GPT-4 Vision (gpt-image-1)</strong>: <?php esc_html_e( '最新的图像生成模型，基于GPT-4的图像理解能力', 'pix2me-ai-image-generator' ); ?>
        <br>
        <strong>DALL-E 3</strong>: <?php esc_html_e( '优秀的图像质量和提示词理解', 'pix2me-ai-image-generator' ); ?>
        <br>
        <strong>DALL-E 2</strong>: <?php esc_html_e( '更经济的选择', 'pix2me-ai-image-generator' ); ?>
    </p>
    <?php
}

/**
 * Render the OpenAI Quality selection field.
 */
function pix2me_ai_image_generator_openai_quality_render() {
    $options = get_option( 'pix2me_ai_image_generator_options' );
    $quality = isset( $options['openai_quality'] ) ? $options['openai_quality'] : 'standard';
    $model = isset( $options['openai_model'] ) ? $options['openai_model'] : 'dall-e-3';
    ?>
    <div id="quality-options-container">
        <?php if ($model === 'gpt-image-1' || $model === 'gpt-4-vision'): ?>
        <!-- GPT-Image-1模型质量选项 -->
        <select name="pix2me_ai_image_generator_options[openai_quality]" id="openai_quality" class="gpt-image-quality">
            <option value="low" <?php selected( $quality, 'low' ); ?>><?php esc_html_e( 'Low - 低质量 (1积分)', 'pix2me-ai-image-generator' ); ?></option>
            <option value="medium" <?php selected( $quality, 'medium' ); ?>><?php esc_html_e( 'Medium - 中等质量 (2积分)', 'pix2me-ai-image-generator' ); ?></option>
            <option value="high" <?php selected( $quality, 'high' ); ?>><?php esc_html_e( 'High - 高质量 (6-7积分)', 'pix2me-ai-image-generator' ); ?></option>
        </select>
        <?php else: ?>
        <!-- DALL-E模型质量选项 -->
        <select name="pix2me_ai_image_generator_options[openai_quality]" id="openai_quality" class="dalle-quality">
            <option value="standard" <?php selected( $quality, 'standard' ); ?>><?php esc_html_e( 'Standard - 标准质量 (2-3积分)', 'pix2me-ai-image-generator' ); ?></option>
            <option value="hd" <?php selected( $quality, 'hd' ); ?>><?php esc_html_e( 'HD - 高清质量 (3-4积分)', 'pix2me-ai-image-generator' ); ?></option>
        </select>
        <?php endif; ?>
    </div>
    <p class="description">
        <?php esc_html_e( '选择图像质量设置:', 'pix2me-ai-image-generator' ); ?><br>
        <?php if ($model === 'gpt-image-1' || $model === 'gpt-4-vision'): ?>
            <strong>Low:</strong> <?php esc_html_e( '低质量，图像细节较少但速度快，积分消耗最少', 'pix2me-ai-image-generator' ); ?><br>
            <strong>Medium:</strong> <?php esc_html_e( '中等质量，图像质量和积分消耗的平衡选择', 'pix2me-ai-image-generator' ); ?><br>
            <strong>High:</strong> <?php esc_html_e( '高质量，生成最精细的图像细节，但积分消耗最多', 'pix2me-ai-image-generator' ); ?><br>
            <span class="note"><?php esc_html_e( '适用于 GPT-4 Vision (gpt-image-1) 模型', 'pix2me-ai-image-generator' ); ?></span>
        <?php else: ?>
            <strong>Standard:</strong> <?php esc_html_e( '标准质量，适用于一般用途，积分消耗较少', 'pix2me-ai-image-generator' ); ?><br>
            <strong>HD:</strong> <?php esc_html_e( '高清质量，图像细节更丰富，但消耗更多积分', 'pix2me-ai-image-generator' ); ?><br>
            <span class="note"><?php esc_html_e( '适用于 DALL-E 3 模型', 'pix2me-ai-image-generator' ); ?></span>
        <?php endif; ?>
    </p>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // 当OpenAI模型选择改变时更新质量选项
        $('#openai_model').on('change', function() {
            var selectedModel = $(this).val();
            var qualityContainer = $('#quality-options-container');
            
            if (selectedModel === 'gpt-image-1') {
                qualityContainer.html(`
                    <select name="pix2me_ai_image_generator_options[openai_quality]" id="openai_quality" class="gpt-image-quality">
                        <option value="low"><?php esc_html_e( 'Low - 低质量 (1积分)', 'pix2me-ai-image-generator' ); ?></option>
                        <option value="medium" selected><?php esc_html_e( 'Medium - 中等质量 (2积分)', 'pix2me-ai-image-generator' ); ?></option>
                        <option value="high"><?php esc_html_e( 'High - 高质量 (6-7积分)', 'pix2me-ai-image-generator' ); ?></option>
                    </select>
                `);
                
                // 更新描述文本
                $(this).closest('.form-group').find('.description').html(`
                    <?php esc_html_e( '选择图像质量设置:', 'pix2me-ai-image-generator' ); ?><br>
                    <strong>Low:</strong> <?php esc_html_e( '低质量，图像细节较少但速度快，积分消耗最少', 'pix2me-ai-image-generator' ); ?><br>
                    <strong>Medium:</strong> <?php esc_html_e( '中等质量，图像质量和积分消耗的平衡选择', 'pix2me-ai-image-generator' ); ?><br>
                    <strong>High:</strong> <?php esc_html_e( '高质量，生成最精细的图像细节，但积分消耗最多', 'pix2me-ai-image-generator' ); ?><br>
                    <span class="note"><?php esc_html_e( '适用于 GPT-4 Vision (gpt-image-1) 模型', 'pix2me-ai-image-generator' ); ?></span>
                `);
            } else {
                qualityContainer.html(`
                    <select name="pix2me_ai_image_generator_options[openai_quality]" id="openai_quality" class="dalle-quality">
                        <option value="standard" selected><?php esc_html_e( 'Standard - 标准质量 (2-3积分)', 'pix2me-ai-image-generator' ); ?></option>
                        <option value="hd"><?php esc_html_e( 'HD - 高清质量 (3-4积分)', 'pix2me-ai-image-generator' ); ?></option>
                    </select>
                `);
                
                // 更新描述文本
                $(this).closest('.form-group').find('.description').html(`
                    <?php esc_html_e( '选择图像质量设置:', 'pix2me-ai-image-generator' ); ?><br>
                    <strong>Standard:</strong> <?php esc_html_e( '标准质量，适用于一般用途，积分消耗较少', 'pix2me-ai-image-generator' ); ?><br>
                    <strong>HD:</strong> <?php esc_html_e( '高清质量，图像细节更丰富，但消耗更多积分', 'pix2me-ai-image-generator' ); ?><br>
                    <span class="note"><?php esc_html_e( '适用于 DALL-E 3 模型', 'pix2me-ai-image-generator' ); ?></span>
                `);
            }
        });
    });
    </script>
    <?php
}

/**
 * Render the OpenAI Style selection field.
 */
function pix2me_ai_image_generator_openai_style_render() {
    $options = get_option( 'pix2me_ai_image_generator_options' );
    $style = isset( $options['openai_style'] ) ? $options['openai_style'] : 'natural';
    ?>
    <select name="pix2me_ai_image_generator_options[openai_style]" id="openai_style">
        <option value="natural" <?php selected( $style, 'natural' ); ?>><?php esc_html_e( 'Natural', 'pix2me-ai-image-generator' ); ?></option>
        <option value="vivid" <?php selected( $style, 'vivid' ); ?>><?php esc_html_e( 'Vivid', 'pix2me-ai-image-generator' ); ?></option>
    </select>
    <p class="description">
        <?php esc_html_e( '选择图像风格设置:', 'pix2me-ai-image-generator' ); ?><br>
        <strong>Natural:</strong> <?php esc_html_e( '自然风格，更接近真实照片效果', 'pix2me-ai-image-generator' ); ?><br>
        <strong>Vivid:</strong> <?php esc_html_e( '生动风格，颜色更鲜艳，对比度更高', 'pix2me-ai-image-generator' ); ?><br>
        <span class="note"><?php esc_html_e( '注意: 此设置仅适用于 DALL-E 3 模型，GPT-4 Vision 只支持 Natural 风格', 'pix2me-ai-image-generator' ); ?></span>
    </p>
    <?php
}

/**
 * Callback for the general settings section.
 */
function pix2me_ai_image_generator_general_section_callback() {
    echo '<p>' . esc_html__( 'Configure general settings for the AI image generator.', 'pix2me-ai-image-generator' ) . '</p>';
}

/**
 * Render the generation cost input field.
 */
function pix2me_ai_image_generator_generation_cost_render() {
    $options = get_option( 'pix2me_ai_image_generator_options' );
    $cost = isset( $options['generation_cost'] ) ? $options['generation_cost'] : 1;
    echo '<input type="number" name="pix2me_ai_image_generator_options[generation_cost]" value="' . esc_attr( $cost ) . '" min="1" class="small-text">';
    echo '<p class="description">' . esc_html__( 'Number of myCred points required for each image generation.', 'pix2me-ai-image-generator' ) . '</p>';
}

/**
 * Render the edit cost input field.
 */
function pix2me_ai_image_generator_edit_cost_render() {
    $options = get_option( 'pix2me_ai_image_generator_options' );
    $cost = isset( $options['edit_cost'] ) ? $options['edit_cost'] : 2;
    echo '<input type="number" name="pix2me_ai_image_generator_options[edit_cost]" value="' . esc_attr( $cost ) . '" min="1" class="small-text">';
    echo '<p class="description">' . esc_html__( 'Number of myCred points required for each image edit (plus 1 point for image upload).', 'pix2me-ai-image-generator' ) . '</p>';
}

/**
 * Render the settings page HTML.
 */
function pix2me_ai_image_generator_settings_page_html() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Get the current tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if (isset($_GET['settings-updated'])): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Settings saved successfully.', 'pix2me-ai-image-generator'); ?></p>
            </div>
        <?php endif; ?>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('pix2me_ai_image_generator_options');
            do_settings_sections('pix2me-ai-image-generator-settings');
            submit_button(__('Save Settings', 'pix2me-ai-image-generator'));
            ?>
        </form>
    </div>
    <?php
}

/**
 * Enqueue admin styles for the settings page.
 */
function pix2me_ai_image_generator_admin_styles($hook) {
    if ($hook !== 'settings_page_pix2me-ai-image-generator-settings') {
        return;
    }
    
    // 为设置页面添加自定义样式
    echo '<style>
    .description .note {
        display: inline-block;
        margin-top: 8px;
        padding: 5px 10px;
        background-color: #f0f6fc;
        border-left: 4px solid #2271b1;
        font-style: italic;
    }
    .pix2me-model-compatibility {
        margin-top: 15px;
        padding: 10px 15px;
        background: #fff;
        border: 1px solid #ccd0d4;
        border-left: 4px solid #2271b1;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    .pix2me-model-compatibility h3 {
        margin-top: 0;
        font-size: 14px;
    }
    .pix2me-model-compatibility table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .pix2me-model-compatibility th, 
    .pix2me-model-compatibility td {
        text-align: left;
        padding: 8px;
        border: 1px solid #ddd;
    }
    .pix2me-model-compatibility th {
        background-color: #f8f9fa;
    }
    .pix2me-model-compatibility td.supported {
        color: #00a32a;
    }
    .pix2me-model-compatibility td.not-supported {
        color: #cc1818;
    }
    </style>';
}
add_action('admin_head', 'pix2me_ai_image_generator_admin_styles');

?>