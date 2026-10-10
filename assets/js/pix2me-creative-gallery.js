jQuery(document).ready(function($) {
    var page = 1;
    var isLoading = false;
    var hasMore = true;
    var $gallery = $('.creative-gallery-list');
    var $loadMore = $('#creative-load-more');
    var ajaxUrl = window.pix2meCreativeGalleryData.ajaxurl;
    var nonce = window.pix2meCreativeGalleryData.nonce;

    function loadImages() {
        if (isLoading || !hasMore) return;
        isLoading = true;
        $loadMore.text('Loading...').prop('disabled', true);
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'pix2me_load_public_gallery_images',
                nonce: nonce,
                page: page
            },
            success: function(res) {
                if (res.success && res.data && res.data.images.length) {
                    renderImages(res.data.images);
                    if (res.data.images.length < 9) {
                        hasMore = false;
                        $loadMore.hide();
                    } else {
                        $loadMore.show();
                    }
                } else {
                    hasMore = false;
                    $loadMore.hide();
                    if (page === 1) {
                        $gallery.html('<div class="creative-empty">暂无公开创意图片</div>');
                    }
                }
            },
            complete: function() {
                isLoading = false;
                $loadMore.text('Load More').prop('disabled', false);
            }
        });
    }

    function renderImages(images) {
        var html = '';
        images.forEach(function(img) {
            var imageUrl = img.url;
            if (imageUrl && imageUrl.toLowerCase().endsWith('.png')) {
                imageUrl = imageUrl + '.webp';
            }
            html += '<div class="creative-card">';
            html +=   '<div class="creative-image-wrap">';
            html +=     '<img src="' + imageUrl + '" alt="AI创意图片" loading="lazy" />';
            html +=   '</div>';
            html +=   '<div class="creative-author">by ' + (img.author_name || '匿名') + '</div>';
            html += '</div>';
        });
        $gallery.append(html);
    }

    $loadMore.on('click', function() {
        page++;
        loadImages();
    });

    // 首次加载
    loadImages();
}); 