<?php
/**
 * myCRED Hooks for Pix2me AI Image Generator
 * 
 * Implements myCRED hooks for tracking AI image generation and editing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;

/**
 * Register our custom hooks with myCRED
 */
function pix2me_register_mycred_hooks($installed) {
    $installed['pix2me_image_generation'] = array(
        'title'       => __('AI 图像生成', 'pix2me-ai-image-generator'),
        'description' => __('生成AI图像时扣除积分', 'pix2me-ai-image-generator'),
        'callback'    => array('myCRED_Hook_Pix2me_Image_Generation')
    );
    
    return $installed;
}
add_filter('mycred_setup_hooks', 'pix2me_register_mycred_hooks');

/**
 * Pix2me AI Image Generation Hook Class
 */
if (!class_exists('myCRED_Hook_Pix2me_Image_Generation') && class_exists('myCRED_Hook')) {
    
    class myCRED_Hook_Pix2me_Image_Generation extends myCRED_Hook {
        
        /**
         * Constructor
         */
        public function __construct($hook_prefs, $type = MYCRED_DEFAULT_TYPE_KEY) {
            parent::__construct(array(
                'id'       => 'pix2me_image_generation',
                'defaults' => array(
                    'image_generation' => array(
                        'creds' => -1,
                        'log'   => __('生成AI图像', 'pix2me-ai-image-generator'),
                        'limit' => '0/x'
                    ),
                    'image_edit' => array(
                        'creds' => -1,
                        'log'   => __('编辑AI图像', 'pix2me-ai-image-generator'),
                        'limit' => '0/x'
                    )
                )
            ), $hook_prefs, $type);
        }
        
        /**
         * Run
         * This is where we start generating credits for completed tasks
         */
        public function run() {
            // 添加回调到相应的钩子
            add_action('mycred_post_image_generation', array($this, 'generate_image_credit'), 10, 3);
            add_action('mycred_post_image_edit', array($this, 'edit_image_credit'), 10, 3);
            
            // 我们在此只监听myCRED_LOG操作，因为积分扣除已经在Ajax处理中完成
            // 这里主要是为了让myCRED的统计和限制功能正常工作
            add_action('mycred_add', array($this, 'logging'), 10, 8);
        }
        
        /**
         * 图像生成积分
         */
        public function generate_image_credit($user_id, $cost, $log_entry) {
            // 这个方法在实际操作中不会被直接调用
            // 我们在Ajax处理中直接调用mycred_add()
        }
        
        /**
         * 图像编辑积分
         */
        public function edit_image_credit($user_id, $cost, $log_entry) {
            // 这个方法在实际操作中不会被直接调用
            // 我们在Ajax处理中直接调用mycred_add()
        }
        
        /**
         * Log Callback
         * 确保myCRED能够记录我们的操作类型
         */
        public function logging($ref, $user_id, $amount, $entry, $ref_id, $data, $type, $time) {
            // 只处理我们关心的引用类型
            if ($ref != 'image_generation' && $ref != 'image_edit') return;
            
            // 如果为正数但处理积分消费，则跳过
            if ($amount >= 0) return;
            
            // 确定是哪种操作类型
            $pref_key = $ref;
            
            // 检查是否达到了限制
            if ($this->over_hook_limit($pref_key, $ref, $user_id)) return;
            
            // 作为消费，仅记录在myCRED日志中
            // 实际积分消费已经在Ajax处理中完成
        }
        
        /**
         * 偏好设置
         */
        public function preferences() {
            $prefs = $this->prefs;
            ?>
            <div class="hook-instance">
                <h3><?php _e('AI图像生成', 'pix2me-ai-image-generator'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id(array('image_generation', 'creds')); ?>"><?php _e('积分', 'pix2me-ai-image-generator'); ?></label>
                            <input type="text" name="<?php echo $this->field_name(array('image_generation', 'creds')); ?>" id="<?php echo $this->field_id(array('image_generation', 'creds')); ?>" value="<?php echo $this->core->number($prefs['image_generation']['creds']); ?>" class="form-control" />
                            <p class="description"><?php _e('生成图像时消耗的积分', 'pix2me-ai-image-generator'); ?></p>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id(array('image_generation', 'log')); ?>"><?php _e('日志模板', 'pix2me-ai-image-generator'); ?></label>
                            <input type="text" name="<?php echo $this->field_name(array('image_generation', 'log')); ?>" id="<?php echo $this->field_id(array('image_generation', 'log')); ?>" value="<?php echo esc_attr($prefs['image_generation']['log']); ?>" class="form-control" />
                            <p class="description"><?php _e('记录在日志中的文本', 'pix2me-ai-image-generator'); ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id(array('image_generation', 'limit')); ?>"><?php _e('限制', 'pix2me-ai-image-generator'); ?></label>
                            <?php echo $this->hook_limit_setting($this->field_name(array('image_generation', 'limit')), $this->field_id(array('image_generation', 'limit')), $prefs['image_generation']['limit']); ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hook-instance">
                <h3><?php _e('AI图像编辑', 'pix2me-ai-image-generator'); ?></h3>
                <div class="row">
                    <div class="col-lg-2 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id(array('image_edit', 'creds')); ?>"><?php _e('积分', 'pix2me-ai-image-generator'); ?></label>
                            <input type="text" name="<?php echo $this->field_name(array('image_edit', 'creds')); ?>" id="<?php echo $this->field_id(array('image_edit', 'creds')); ?>" value="<?php echo $this->core->number($prefs['image_edit']['creds']); ?>" class="form-control" />
                            <p class="description"><?php _e('编辑图像时消耗的积分', 'pix2me-ai-image-generator'); ?></p>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id(array('image_edit', 'log')); ?>"><?php _e('日志模板', 'pix2me-ai-image-generator'); ?></label>
                            <input type="text" name="<?php echo $this->field_name(array('image_edit', 'log')); ?>" id="<?php echo $this->field_id(array('image_edit', 'log')); ?>" value="<?php echo esc_attr($prefs['image_edit']['log']); ?>" class="form-control" />
                            <p class="description"><?php _e('记录在日志中的文本', 'pix2me-ai-image-generator'); ?></p>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label for="<?php echo $this->field_id(array('image_edit', 'limit')); ?>"><?php _e('限制', 'pix2me-ai-image-generator'); ?></label>
                            <?php echo $this->hook_limit_setting($this->field_name(array('image_edit', 'limit')), $this->field_id(array('image_edit', 'limit')), $prefs['image_edit']['limit']); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }
        
        /**
         * 钩子外部说明
         */
        public function sanitise_preferences($data) {
            
            if (isset($data['image_generation']['limit']) && isset($data['image_generation']['limit_by'])) {
                $limit = sanitize_text_field($data['image_generation']['limit']);
                if ($limit == '') $limit = 0;
                $data['image_generation']['limit'] = $limit . '/' . $data['image_generation']['limit_by'];
                unset($data['image_generation']['limit_by']);
            }
            
            if (isset($data['image_edit']['limit']) && isset($data['image_edit']['limit_by'])) {
                $limit = sanitize_text_field($data['image_edit']['limit']);
                if ($limit == '') $limit = 0;
                $data['image_edit']['limit'] = $limit . '/' . $data['image_edit']['limit_by'];
                unset($data['image_edit']['limit_by']);
            }
            
            return $data;
        }
    }
} 