<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// 获取设置和用户数据
$options = get_option('pix2me_ai_image_generator_options', array());
$api_type = isset($options['api_type']) ? $options['api_type'] : 'openai';
$openai_model = 'gpt-image-1'; // 根据需求使用 gpt-image-1 模型
$user_balance = function_exists('ai_get_mycred_balance') ? ai_get_mycred_balance() : 0;
if (is_wp_error($user_balance)) {
    $user_balance = 0;
}

// 检查依赖
if (function_exists('ai_is_mycred_active') && !ai_is_mycred_active()) {
    echo '<div class="error"><p>' . esc_html__('AI Image Generator requires myCred plugin to be installed and activated.', 'pix2me-ai-image-generator') . '</p></div>';
    return;
}

// 确保用户已登录
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('请登录后使用图片编辑功能。', 'pix2me-ai-image-generator') . '</p>';
    return;
}

?>

<div id="ai-image-editor-app" class="pix2me-generator-container">
    <!-- 标签导航 -->
    <div class="tab-navigation">
        <a href="/generate" class="tab">Text to Image</a>
        <a href="/image-edit" class="tab active">Image Edit</a>
    </div>

    <form id="ai-image-edit-form">
        <?php wp_nonce_field('pix2me_ai_image_generator_nonce', 'pix2me_nonce_field'); ?>
        
        <!-- 隐藏输入，用于后端确定的值 -->
        <input type="hidden" id="api_type" name="api_type" value="openai">
        <input type="hidden" id="model" name="model" value="gpt-image-1">
        <input type="hidden" id="num_outputs" name="num_outputs" value="1">
        
        <!-- 图片上传和提示词区域（水平排列） -->
        <div class="editor-container">
            <div class="image-upload-container">
                <div class="image-preview-area" id="image-preview-area">
                    <div class="upload-placeholder active" id="upload-placeholder">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click or drop image here</p>
                        <small>Supports JPG, PNG formats</small>
                    </div>
                    <img id="image-preview" style="display:none; max-width:100%; max-height:100%;" />
                </div>
                <input type="file" id="image-upload" name="image-upload" accept="image/jpeg,image/png" style="display:none;" />
                <!-- 隐藏的输入框，用于存储base64格式的图片数据 -->
                <input type="hidden" id="image_data" name="image_data" value="">
            </div>
            
            <div class="prompt-container">
                <textarea id="prompt" name="prompt" placeholder="Enter your edit instructions..." required>A stylized depiction of:</textarea>
            </div>
        </div>

        <!-- 参数部分 -->
        <div class="parameters-section">
            <div class="parameter-row">
                <!-- 风格 -->
                <div class="parameter-item">
                    <button class="epcl-shortcode epcl-button regular flat light-blue parameter-button" type="button" id="style-toggle-button">
                        <span class="param-icon"><i class="fas fa-paint-brush"></i></span>
                        <span class="param-value" id="style-button-value">Default Style</span>
                        <span class="param-arrow"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <input type="hidden" id="style_prompt" name="style_prompt" value="">
                </div>

                <!-- 尺寸 -->
                <div class="parameter-item">
                    <button class="epcl-shortcode epcl-button regular flat light-blue parameter-button" type="button" data-controls="resolution">
                        <span class="param-icon"><i class="fas fa-crop-alt"></i></span>
                        <span class="param-value">Square 1024x1024</span>
                        <span class="param-arrow"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="parameter-menu" id="resolution-menu">
                        <div class="parameter-menu-item selected" data-value="1024x1024">Square 1024x1024</div>
                        <div class="parameter-menu-item" data-value="1024x1536">Portrait 1024x1536</div>
                        <div class="parameter-menu-item" data-value="1536x1024">Landscape 1536x1024</div>
                    </div>
                    <input type="hidden" id="resolution" name="resolution" value="1024x1024">
                </div>

                <!-- 质量 -->
                <div class="parameter-item">
                    <button class="epcl-shortcode epcl-button regular flat light-blue parameter-button" type="button" data-controls="quality">
                        <span class="param-icon"><i class="fas fa-sliders-h"></i></span>
                        <span class="param-value">Medium Quality</span>
                        <span class="param-arrow"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="parameter-menu" id="quality-menu">
                        <div class="parameter-menu-item" data-value="low">Low Quality</div>
                        <div class="parameter-menu-item selected" data-value="medium">Medium Quality</div>
                        <div class="parameter-menu-item" data-value="high">High Quality</div>
                    </div>
                    <input type="hidden" id="quality" name="quality" value="medium">
                </div>

                <!-- 费用显示 -->
                <div class="parameter-item cost-display">
                    <span class="param-icon"><i class="fas fa-coins"></i></span>
                    <span>Cost: <span id="total-cost-display">3</span> Pts</span>
                    <input type="hidden" name="total_cost" id="total_cost" value="3">
                </div>

                <!-- 余额显示 -->
                <div class="parameter-item balance-display">
                    <span class="param-icon"><i class="fas fa-wallet"></i></span>
                    <span>Balance: <span id="user-balance-display" data-balance="<?php echo esc_attr(floor($user_balance)); ?>"><?php echo esc_html(floor($user_balance)); ?></span> Pts</span>
                </div>
            </div>
            <!-- 参数行结束 -->
        </div>
        <!-- 参数部分结束 -->

        <!-- 风格选择覆盖 -->
        <div id="style-selection-overlay" class="style-overlay" style="display: none;">
            <div class="style-overlay-content">
                <div id="style-examples" class="style-examples-grid">
                    <!-- 风格卡片将由JavaScript填充 -->
                </div>
            </div>
        </div>

        <!-- 动作按钮容器 -->
        <div class="action-buttons-container">
            <button type="button" id="clear-button" class="epcl-shortcode epcl-button regular outline gray">Clear</button>
            <button type="submit" id="edit-button" class="epcl-shortcode epcl-button regular primary-style primary-color">
                <span class="edit-text">Edit Image</span>
                <span class="edit-arrow">→</span>
            </button>
        </div>
        <!-- 动作按钮容器结束 -->

    </form>

    <!-- 加载指示器 - 模态样式 -->
    <div id="ai-loading-indicator" style="display: none;" class="loading-modal-overlay">
        <div class="loading-modal">
            <span aria-busy="true">Processing, please wait...</span>
        </div>
    </div>

    <!-- 错误消息 -->
    <div id="ai-error-message" class="notice notice-error is-dismissible" style="display: none;"></div>

    <!-- 结果区域 -->
    <div id="ai-image-results" style="margin-top: 20px; display: none;">
        <h3 class="results-title"><?php esc_html_e('Results', 'pix2me-ai-image-generator'); ?></h3>
        <div class="result-info" style="margin-bottom: 15px; font-size: 0.9em; color: #555;">
            <span id="result-model-info"></span> <!-- JS将填充此内容 -->
        </div>
        <div class="images-grid">
            <!-- 生成的图像将显示在这里 -->
        </div>
        <div class="result-actions" style="margin-top: 15px; display: none;">
            <button class="epcl-shortcode epcl-button regular outline gray save-to-library-button" type="button">Save to Library</button>
            <button class="epcl-shortcode epcl-button regular outline gray download-button" type="button">Download Image</button>
        </div>
    </div>
</div>

<!-- 行内CSS用于图像上传容器的样式 -->
<style>
.editor-container {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}

.image-upload-container, .prompt-container {
    flex: 1;
    min-width: 0;
}

.image-preview-area {
    width: 100%;
    height: 100%;
    border: 2px dashed #ccc;
    border-radius: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    background-color: #f9f9f9;
    position: relative;
    cursor: pointer;
}

.prompt-container textarea, .image-preview-area {
    height: 300px;
}

.upload-placeholder {
    text-align: center;
    color: #888;
    display: none;
}

.upload-placeholder.active {
    display: block;
}

.upload-placeholder i {
    font-size: 48px;
    margin-bottom: 10px;
}

.image-preview-area img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.image-preview-area.dragover {
    border-color: #007bff;
    background-color: rgba(0, 123, 255, 0.05);
}

@media (max-width: 768px) {
    .editor-container {
        flex-direction: column;
    }
}
</style>

<script type="text/javascript">
// 将PHP值传递到JavaScript
window.pix2meData = {
    baseCost: <?php echo intval(get_option('pix2me_ai_image_generator_base_cost', 1)); ?>,
    editCost: <?php echo intval(get_option('pix2me_ai_image_generator_edit_cost', 2)); ?>,
    apiType: 'openai',
    model: 'gpt-image-1',
    userBalance: <?php echo floatval($user_balance); ?>,
    ajaxurl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>'
};
</script> 