<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// Get options and user data
$options = get_option('pix2me_ai_image_generator_options', array());
$api_type = isset($options['api_type']) ? $options['api_type'] : 'stable_diffusion';
$openai_model = isset($options['openai_model']) ? $options['openai_model'] : 'dall-e-3';
$user_balance = function_exists('ai_get_mycred_balance') ? ai_get_mycred_balance() : 0;
if (is_wp_error($user_balance)) {
    $user_balance = 0;
}
$is_openai_single_output_model = ($api_type === 'openai' && ($openai_model === 'dall-e-3' || $openai_model === 'gpt-image-1'));

// Check dependencies (assuming ai_is_mycred_active is available)
if (function_exists('ai_is_mycred_active') && !ai_is_mycred_active()) {
    echo '<div class="error"><p>' . esc_html__('AI Image Generator requires myCred plugin to be installed and activated.', 'pix2me-ai-image-generator') . '</p></div>';
    return;
}

// Ensure user is logged in
if (!is_user_logged_in()) {
    echo '<p>' . esc_html__('Please log in to generate images.', 'pix2me-ai-image-generator') . '</p>';
    return;
}

?>

<div id="ai-image-generator-app" class="pix2me-generator-container">
    <!-- Tab Navigation (keep as is, use English eventually via WPML) -->
    <div class="tab-navigation">
        <a href="/generate" class="tab active">Text to Image</a>
        <a href="/image-edit" class="tab">Image Edit</a>
    </div>

    <form id="ai-generate-form">
        <?php wp_nonce_field('pix2me_ai_image_generator_nonce', 'pix2me_nonce_field'); ?>
        
        <!-- Hidden inputs for backend determined values -->
        <input type="hidden" id="api_type" name="api_type" value="<?php echo esc_attr($api_type); ?>">
        <input type="hidden" id="model" name="model" value="<?php echo esc_attr($openai_model); ?>">

        <!-- Prompt Area -->
        <div class="prompt-container">
            <textarea id="prompt" name="prompt" placeholder="Enter your image description..." required></textarea>
            <!-- Optional Negative Prompt Toggle? -->
        </div>

        <!-- Parameters Section -->
        <div class="parameters-section">
            <div class="parameter-row">
                <!-- Style -->
                <div class="parameter-item">
                    <button class="epcl-shortcode epcl-button regular flat light-blue parameter-button" type="button" id="style-toggle-button">
                        <span class="param-icon"><i class="fas fa-paint-brush"></i></span>
                        <span class="param-value" id="style-button-value">Default Style</span>
                        <span class="param-arrow"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <input type="hidden" id="style_prompt" name="style_prompt" value="">
                </div>
                <!-- Size -->
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
                <!-- Quality -->
                <div class="parameter-item">
                    <button class="epcl-shortcode epcl-button regular flat light-blue parameter-button" type="button" data-controls="quality">
                        <span class="param-icon"><i class="fas fa-sliders-h"></i></span>
                        <span class="param-value">Standard Quality</span>
                        <span class="param-arrow"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="parameter-menu" id="quality-menu">
                        <div class="parameter-menu-item" data-value="low">Low Quality</div>
                        <div class="parameter-menu-item selected" data-value="standard">Standard Quality</div>
                        <div class="parameter-menu-item" data-value="high">High Quality</div>
                    </div>
                    <input type="hidden" id="quality" name="quality" value="standard">
                </div>
                <!-- Color -->
                <div class="parameter-item">
                    <button class="epcl-shortcode epcl-button regular flat light-blue parameter-button" type="button" data-controls="color_prompt">
                        <span class="param-icon"><i class="fas fa-palette"></i></span>
                        <span class="param-value">Standard Color</span>
                        <span class="param-arrow"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="parameter-menu" id="color_prompt-menu">
                        <div class="parameter-menu-item selected" data-value="">Standard Color</div>
                        <div class="parameter-menu-item" data-value="vibrant colors">Vibrant Color</div>
                        <div class="parameter-menu-item" data-value="pastel colors">Pastel Tone</div>
                        <div class="parameter-menu-item" data-value="black and white">Black and White</div>
                        <div class="parameter-menu-item" data-value="vintage colors">Vintage Tone</div>
                        <div class="parameter-menu-item" data-value="sketch line art">Sketch Lineart</div>
                    </div>
                    <input type="hidden" id="color_prompt" name="color_prompt" value="">
                </div>
                <!-- Composition -->
                <div class="parameter-item">
                    <button class="epcl-shortcode epcl-button regular flat light-blue parameter-button" type="button" data-controls="composition_prompt">
                        <span class="param-icon"><i class="fas fa-th-large"></i></span>
                        <span class="param-value">Default Composition</span>
                        <span class="param-arrow"><i class="fas fa-chevron-down"></i></span>
                    </button>
                    <div class="parameter-menu" id="composition_prompt-menu">
                        <div class="parameter-menu-item selected" data-value="">Default Composition</div>
                        <div class="parameter-menu-item" data-value="centered composition">Centered</div>
                        <div class="parameter-menu-item" data-value="rule of thirds">Rule of Thirds</div>
                        <div class="parameter-menu-item" data-value="symmetrical composition">Symmetrical</div>
                        <div class="parameter-menu-item" data-value="close-up shot">Close-up</div>
                        <div class="parameter-menu-item" data-value="wide shot">Wide Shot</div>
                        <div class="parameter-menu-item" data-value="depth of field">Depth of Field</div>
                        <div class="parameter-menu-item" data-value="aerial view">Aerial View</div>
                    </div>
                    <input type="hidden" id="composition_prompt" name="composition_prompt" value="">
                </div>
                <!-- Cost Display -->
                <div class="parameter-item cost-display">
                    <span class="param-icon"><i class="fas fa-coins"></i></span>
                    <span>Cost: <span id="total-cost-display">--</span> Pts</span>
                    <input type="hidden" name="total_cost" value="1">
                </div>
                <!-- Balance Display -->
                <div class="parameter-item balance-display">
                    <span class="param-icon"><i class="fas fa-wallet"></i></span>
                    <span>Balance: <span id="user-balance-display" data-balance="<?php echo esc_attr(floor($user_balance)); ?>"><?php echo esc_html(floor($user_balance)); ?></span> Pts</span>
                </div>
                <!-- Image Count (Conditional, moved to end for better flow) -->
                <?php if (!$is_openai_single_output_model): ?>
                <div class="parameter-item image-count-item">
                     <span class="param-icon"><i class="fas fa-images"></i> Count:</span>
                     <input type="range" id="num_outputs" name="num_outputs" min="1" max="4" value="1" class="image-count-slider">
                     <span id="image-count-display" class="image-count-value">1</span>
                </div>
                <?php else: ?>
                <input type="hidden" id="num_outputs" name="num_outputs" value="1">
                <?php endif; ?>
            </div>
            <!-- End Parameter Row -->
        </div>
        <!-- End Parameters Section -->

        <!-- Style Selection Overlay -->
        <div id="style-selection-overlay" class="style-overlay" style="display: none;">
            <div class="style-overlay-content">
                <div id="style-examples" class="style-examples-grid">
                    <!-- Style cards will be populated by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Action Buttons Container -->
        <div class="action-buttons-container">
            <button type="button" id="clear-button" class="epcl-shortcode epcl-button regular outline gray">Clear</button>
            <button type="submit" id="generate-button" class="epcl-shortcode epcl-button regular primary-style primary-color">
                <span class="generate-text">Generate</span>
                <span class="generate-arrow">→</span>
            </button>
        </div>
        <!-- End Action Buttons Container -->

    </form>

    <!-- Loading Indicator - Modal Style -->
    <div id="ai-loading-indicator" style="display: none;" class="loading-modal-overlay">
        <div class="loading-modal">
            <span aria-busy="true">Generating, please wait...</span>
        </div>
    </div>

    <!-- Error Message -->
    <div id="ai-error-message" class="notice notice-error is-dismissible" style="display: none;"></div>

    <!-- Results Area -->
    <div id="ai-image-results" style="margin-top: 20px; display: none;">
        <h3 class="results-title"><?php esc_html_e('Results', 'pix2me-ai-image-generator'); ?></h3>
        <div class="result-info" style="margin-bottom: 15px; font-size: 0.9em; color: #555;">
            <span id="result-model-info"></span> <!-- JS will populate this -->
            <span id="result-seed-info" style="margin-left: 15px;"></span> <!-- JS will populate this -->
        </div>
        <div class="images-grid">
            <!-- Generated images will appear here -->
        </div>
        <div class="result-actions" style="margin-top: 15px; display: none;">
             <button class="epcl-shortcode epcl-button regular outline gray copy-seed-button" type="button">Copy Seed</button>
             <!-- Maybe add a copy prompt button here -->
        </div>
        <input type="hidden" id="result_seed" value="">
    </div>
    <!-- End Results Area -->

</div>

<script type="text/javascript">
// 将PHP值传递到JavaScript
window.pix2meData = {
    baseCost: <?php echo intval(get_option('pix2me_ai_image_generator_base_cost', 1)); ?>,
    apiType: '<?php echo esc_js($api_type); ?>',
    model: '<?php echo esc_js($openai_model); ?>',
    userBalance: <?php echo floatval($user_balance); ?>,
    ajaxurl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>'
};
</script>

<?php
// Ensure 'pix2me-generate-page.css' and 'pix2me-generate-page.js' are properly enqueued.
?>