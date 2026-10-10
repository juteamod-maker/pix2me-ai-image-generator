<?php
// 创意库模板
if (!defined('ABSPATH')) exit;
wp_enqueue_style('pix2me-asset-library-css');
wp_enqueue_style('dashicons');
wp_enqueue_script('jquery');
wp_enqueue_script('pix2me-creative-gallery-js');
// 输出全局JS数据
$ajaxurl = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('pix2me_details_nonce');
?>
<div class="creative-gallery-container">
    <div class="pix2me-header"><h2>创意库</h2></div>
    <div class="creative-gallery-list"></div>
    <div class="creative-gallery-pagination" style="text-align:center;margin:20px 0;">
        <button id="creative-load-more" class="pix2me-button">Load More</button>
    </div>
</div>
<script type="text/javascript">
window.pix2meCreativeGalleryData = {
    ajaxurl: '<?php echo esc_js($ajaxurl); ?>',
    nonce: '<?php echo esc_js($nonce); ?>'
};
</script> 