/**
 * JavaScript functionality for the asset library page
 */
jQuery(document).ready(function($) {
    // Page data
    var page = 1;
    var isLoading = false;
    var ajaxUrl = pix2meAssetLibraryData.ajaxurl;
    var nonce = pix2meAssetLibraryData.nonce;
    var hasMoreImages = true;
    
    // DOM element cache
    var $gallery = $('.pix2me-gallery');
    var $loadMoreButton = $('#load-more-button');
    var $spinnerContainer = $('.spinner-container');
    var $modal = $('#asset-details-modal');
    
    // Debounce function
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }
    
    // Initialize library
    function initLibrary() {
        page = 1;
        hasMoreImages = true;
        isLoading = false;
        
        // 只显示加载动画，不渲染 initial_images
        $gallery.html('<div class="loading-container"><span aria-busy="true">Loading, please wait...</span></div>');
        
        // 直接AJAX加载第一页图片
        loadImages();
        
        // 事件绑定
        $loadMoreButton.on('click', function() {
            page++;
            loadImages();
        });
        
        // Set up modal events
        setupModalEvents();
    }
    
    // Clear gallery
    function clearGallery() {
        $gallery.empty();
        $gallery.html('<div class="loading-container"><span aria-busy="true">Loading, please wait...</span></div>');
    }
    
    // Reset gallery
    function resetGallery() {
        $gallery.empty();
        page = 1;
        hasMoreImages = true;
    }
    
    // Load images
    function loadImages() {
        if (isLoading || !hasMoreImages) return;
        
        isLoading = true;
        $spinnerContainer.show();
        $loadMoreButton.prop('disabled', true);
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'pix2me_load_gallery_images',
                nonce: nonce,
                page: page
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.images && response.data.images.length > 0) {
                        renderImages(response.data.images);
                        
                        // If returned image count is less than requested, no more images
                        if (response.data.images.length < 9) {
                            hasMoreImages = false;
                            $loadMoreButton.hide();
                        } else {
                            $loadMoreButton.show();
                        }
                    } else {
                        hasMoreImages = false;
                        $loadMoreButton.hide();
                        
                        if (page === 1 && $gallery.children().length === 0) {
                            showEmptyState();
                        }
                    }
                } else {
                    showNotification('Failed to load images: ' + response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Failed to load images, please try again later', 'error');
            },
            complete: function() {
                isLoading = false;
                $spinnerContainer.hide();
                $loadMoreButton.prop('disabled', false);
            }
        });
    }
    
    // Render images
    function renderImages(images) {
        if (!images || !Array.isArray(images) || images.length === 0) {
            return;
        }
        
        // 首次加载时，清除"加载中"状态
        if ($gallery.find('.loading-container').length) {
            $gallery.empty();
        }
        
        var html = '';
        
        images.forEach(function(image) {
            if (!image || !image.id || !image.url) {
                return;
            }
            
            // Ensure prompt is safe text, avoid XSS attacks
            var safePrompt = image.prompt ? 
                image.prompt.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;') : 
                '';
            
            // Separate model and seed information from original prompt for formatting display
            var displayPrompt = '';
            var metaInfo = '';
            
            // If there is an original prompt, use it
            if (image.raw_prompt) {
                displayPrompt = image.raw_prompt.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                
                // Build meta information
                if (image.model || image.seed) {
                    metaInfo = '<div class="meta-info">';
                    if (image.model) {
                        metaInfo += '<span class="model-info">Model: ' + image.model + '</span>';
                    }
                    if (image.seed) {
                        metaInfo += '<span class="seed-info">Seed: ' + image.seed + '</span>';
                    }
                    metaInfo += '</div>';
                }
            } else {
                // If there is no original prompt, use full prompt (includes model and seed information)
                displayPrompt = safePrompt;
            }
            
            // 将图片URL的.png后缀替换为.png.webp用于显示
            var imageUrl = image.url;
            if (imageUrl && imageUrl.toLowerCase().endsWith('.png')) {
                imageUrl = imageUrl + '.webp';
            }
            
            // 创建用于下载的高清图片链接(.bak.png)
            var downloadUrl = image.url;
            if (downloadUrl && downloadUrl.toLowerCase().endsWith('.png')) {
                // 将.png替换为.bak.png后缀
                downloadUrl = downloadUrl.replace(/\.png$/i, '.bak.png');
            }
            
            html += '<div class="pix2me-gallery-item">';
            html += '  <div class="image-container">';
            html += '    <img src="' + imageUrl.replace('/wp-content/uploads/', '/wp-content/smush-webp/') + '" alt="' + safePrompt + '" loading="lazy" />';
            html += '    <div class="image-loading-indicator"><span aria-busy="true">Loading, please wait...</span></div>';
            html += '    <div class="image-overlay">';
            html += '      <div class="action-buttons">';
            html += '        <button class="view-details-button" data-image-id="' + image.id + '">';
            html += '          <span class="dashicons dashicons-visibility"></span>';
            html += '          <span class="button-text">View Details</span>';
            html += '        </button>';
            html += '        <button class="copy-prompt-button" data-prompt="' + (image.raw_prompt || safePrompt) + '">';
            html += '          <span class="dashicons dashicons-clipboard"></span>';
            html += '          <span class="button-text">Copy Prompt</span>';
            html += '        </button>';
            html += '        <a href="' + downloadUrl + '" class="download-button" download>';
            html += '          <span class="dashicons dashicons-download"></span>';
            html += '          <span class="button-text">Download</span>';
            html += '        </a>';
            html += '        <button class="delete-image-button" data-image-id="' + image.id + '">';
            html += '          <span class="dashicons dashicons-trash"></span>';
            html += '          <span class="button-text">Delete</span>';
            html += '        </button>';
            html += '      </div>';
            html += '    </div>';
            html += '    <div class="mobile-view-button" data-image-id="' + image.id + '" data-pix2me-private="' + (image.pix2me_private || '') + '">View Details</div>';
            html += '  </div>';
            html += '  <div class="image-info">';
            html += '    <div class="prompt-text">' + displayPrompt + '</div>';
            if (metaInfo) {
                html += metaInfo;
            }
            html += '    <div class="metadata">';
            html += '      <span class="timestamp" data-time="' + (image.timestamp || '') + '">' + formatTimeAgo(image.timestamp) + '</span>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';
        });
        
        $gallery.append(html);
        
        // 图片加载完成后移除加载中状态
        $gallery.find('img').on('load', function() {
            $(this).parent().find('.image-loading-indicator').fadeOut();
        }).on('error', function() {
            $(this).parent().find('.image-loading-indicator').fadeOut();
            // 显示友好提示
            var $container = $(this).parent();
            $(this).remove();
            if ($container.find('.image-error-tip').length === 0) {
                $container.append('<div class="image-error-tip" style="color:#888;font-size:14px;padding:30px 10px;text-align:center;">The image is being optimized. Please wait a moment and refresh.</div>');
            }
        });
    }
    
    // Show empty state
    function showEmptyState() {
        var generatePageUrl = pix2meAssetLibraryData.generate_page_url || '/generate';
        
        var html = '<div class="empty-library">';
        html += 'No images found. Go to <a href="' + generatePageUrl + '">Generate Page</a> to create some!';
        html += '</div>';
        
        $gallery.html(html);
    }
    
    // Load image details
    function loadImageDetails(imageId) {
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'pix2me_get_image_details',
                nonce: nonce,
                image_id: imageId
            },
            success: function(response) {
                if (response.success) {
                    // 兼容卡片传递的pix2me_private
                    var $card = $('.pix2me-gallery-item').find('[data-image-id="' + imageId + '"]');
                    if ($card.length && typeof response.data.pix2me_private === 'undefined') {
                        response.data.pix2me_private = $card.data('pix2me-private');
                    }
                    renderImageDetails(response.data);
                    $modal.fadeIn();
                } else {
                    showNotification('Failed to load image details: ' + response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Failed to load image details, please try again later', 'error');
            }
        });
    }
    
    // Render image details
    function renderImageDetails(data) {
        // 图片显示：.png结尾自动拼接.webp
        var imageUrl = data.url;
        if (imageUrl && imageUrl.toLowerCase().endsWith('.png')) {
            imageUrl = imageUrl + '.webp';
        }
        var downloadUrl = data.download_url;
        // 用_pix2me_private字段判断checked
        var isPrivate = (typeof data.pix2me_private !== 'undefined') ? (data.pix2me_private == '1' || data.pix2me_private === 1) : true;
        var html = '<div class="details-container">';
        html += '  <div class="details-header">';
        html += '    <button class="epcl-shortcode epcl-button regular flat dark close-modal-btn">Close</button>';
        html += '  </div>';
        html += '  <div class="details-content">';
        html += '    <div class="details-image">';
        html += '      <img src="' + imageUrl.replace('/wp-content/uploads/', '/wp-content/smush-webp/') + '" alt="' + (data.title || '') + '" />';
        html += '    </div>';
        html += '    <div class="details-info">';
        html += '      <div class="info-item prompt-item">';
        html += '        <label>Prompt</label>';
        html += '        <p>' + (data.prompt || '') + '</p>';
        html += '      </div>';
        if (data.negative_prompt) {
            html += '      <div class="info-item">';
            html += '        <label>Negative Prompt</label>';
            html += '        <p>' + data.negative_prompt + '</p>';
            html += '      </div>';
        }
        html += '      <div class="info-item parameters-item">';
        html += '        <label>Generation Parameters</label>';
        html += '        <ul>';
        if (data.parameters && typeof data.parameters === 'object') {
            Object.keys(data.parameters).forEach(function(key) {
                html += '<li><strong>' + key + ':</strong> ' + data.parameters[key] + '</li>';
            });
        }
        html += '        </ul>';
        html += '      </div>';
        html += '      <div class="info-item">';
        html += '        <label>Created</label>';
        html += '        <p>' + (data.timestamp || '') + '</p>';
        html += '      </div>';
        html += '      <div class="details-actions">';
        html += '        <button class="edit-image-btn" data-image-id="' + data.id + '">';
        html += '          <span class="dashicons dashicons-edit"></span> Edit Image';
        html += '        </button>';
        html += '        <button class="copy-detail-prompt-btn" data-prompt="' + (data.prompt || '') + '">';
        html += '          <span class="dashicons dashicons-clipboard"></span> Copy Prompt';
        html += '        </button>';
        html += '        <a href="' + downloadUrl + '" class="download-button" download>';
        html += '          <span class="dashicons dashicons-download"></span> Download';
        html += '        </a>';
        html += '        <button class="delete-detail-image-btn" data-image-id="' + data.id + '">';
        html += '          <span class="dashicons dashicons-trash"></span> Delete';
        html += '        </button>';
        html += '      </div>';
        html += '    </div>';
        html += '  </div>';
        html += '</div>';
        $modal.html(html);
        $modal.css('display', 'flex').css('align-items', 'flex-end');
    }
    
    // Delete image
    function deleteImage(imageId) {
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'pix2me_delete_gallery_image',
                nonce: nonce,
                image_id: imageId
            },
            success: function(response) {
                if (response.success) {
                    // Remove image from DOM
                    $gallery.find('.pix2me-gallery-item').filter(function() {
                        return $(this).find('[data-image-id="' + imageId + '"]').length > 0;
                    }).fadeOut(300, function() {
                        $(this).remove();
                        
                        // If gallery is empty, show empty state
                        if ($gallery.children().length === 0) {
                            showEmptyState();
                        }
                    });
                    
                    // If modal is open, close it
                    if ($modal.is(':visible')) {
                        $modal.fadeOut();
                    }
                    
                    showNotification('Image deleted successfully');
                } else {
                    showNotification('Failed to delete image: ' + response.data.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('Failed to delete image, please try again later', 'error');
            }
        });
    }
    
    // Set up modal events
    function setupModalEvents() {
        // Set image details event
        $gallery.on('click', '.view-details-button, .mobile-view-button', function() {
            var imageId = $(this).data('image-id');
            loadImageDetails(imageId);
        });
        
        // Set copy prompt event
        $gallery.on('click', '.copy-prompt-button', function() {
            var prompt = $(this).data('prompt');
            copyToClipboard(prompt);
            showNotification('Prompt copied to clipboard');
        });
        
        // Set delete image event
        $gallery.on('click', '.delete-image-button', function() {
            if (confirm('Are you sure you want to delete this image? This action cannot be undone.')) {
                var imageId = $(this).data('image-id');
                deleteImage(imageId);
            }
        });
        
        // Close details modal when clicking close button
        $(document).on('click', '.close-modal-btn', function() {
            $modal.fadeOut();
        });
        
        // Close modal when clicking on background
        $modal.on('click', function(e) {
            // 只有当点击的是模态框本身（背景）而不是内容区域时才关闭
            if ($(e.target).is($modal)) {
                $modal.fadeOut();
            }
        });
        
        // Prevent modal content click event from bubbling
        $(document).on('click', '.details-container', function(e) {
            e.stopPropagation();
        });
        
        // Modal copy prompt button
        $(document).on('click', '.copy-detail-prompt-btn', function() {
            var prompt = $(this).data('prompt');
            copyToClipboard(prompt);
            showNotification('Prompt copied');
        });
        
        // Modal delete image button
        $(document).on('click', '.delete-detail-image-btn', function() {
            var imageId = $(this).data('image-id');
            if (confirm('Are you sure you want to delete this image?')) {
                deleteImage(imageId);
                $modal.fadeOut();
            }
        });
        
        // Modal edit image button
        $(document).on('click', '.edit-image-btn', function() {
            var imageId = $(this).data('image-id');
            var generatePageUrl = pix2meAssetLibraryData.generate_page_url || '/generate';
            // 获取当前图片的PNG下载地址（假设详情数据已渲染到页面）
            var $modal = $('#asset-details-modal');
            var $img = $modal.find('.details-image img');
            var imageUrl = '';
            if ($img.length) {
                imageUrl = $img.attr('src');
                // 恢复为原始.png后缀和uploads目录
                imageUrl = imageUrl.replace('/wp-content/smush-webp/', '/wp-content/uploads/').replace(/\.png\.webp$/, '.png').replace(/\.webp$/, '.png');
            }
            // 跳转到编辑页面并带上图片URL参数
            if (imageUrl) {
                window.location.href = generatePageUrl + '?edit=' + imageId + '&image_url=' + encodeURIComponent(imageUrl);
            } else {
                window.location.href = generatePageUrl + '?edit=' + imageId;
            }
        });
    }
    
    // Helper to copy text to clipboard
    function copyToClipboard(text) {
        // Create a temporary input element
        var tempInput = $('<input>');
        $('body').append(tempInput);
        tempInput.val(text).select();
        
        // Try to copy to clipboard
        try {
            document.execCommand('copy');
        } catch (err) {
            console.error('Unable to copy text', err);
        }
        
        // Remove temporary element
        tempInput.remove();
    }
    
    // Show notification
    function showNotification(message, type) {
        type = type || 'success';
        var $notification = $('<div class="pix2me-notification ' + type + '">' + message + '</div>');
        
        $('body').append($notification);
        
        // Show notification
        setTimeout(function() {
            $notification.addClass('show');
        }, 10);
        
        // Hide notification after 3 seconds
        setTimeout(function() {
            $notification.removeClass('show');
            
            // Wait for transition to end before removing element
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Format time as "x minutes ago", "x hours ago", etc.
    function formatTimeAgo(timestamp) {
        if (!timestamp) return '';
        
        var now = new Date().getTime() / 1000;
        var seconds = 0;
        
        // Handle different timestamp formats
        if (typeof timestamp === 'string') {
            // Handle ISO format date strings
            if (timestamp.includes('-') && timestamp.includes(':')) {
                var dt = new Date(timestamp.replace(' ', 'T'));
                seconds = Math.floor(now - dt.getTime() / 1000);
            } else if (!isNaN(parseInt(timestamp))) {
                // Handle Unix timestamps (string form)
                seconds = Math.floor(now - parseInt(timestamp));
            }
        } else if (typeof timestamp === 'number') {
            // Handle Unix timestamps (numeric form)
            seconds = Math.floor(now - timestamp);
        }
        
        // If calculated seconds are invalid, return empty string
        if (isNaN(seconds) || seconds < 0) {
            return '';
        }
        
        // Format time difference
        if (seconds < 60) {
            return 'just now';
        }
        
        var minutes = Math.floor(seconds / 60);
        if (minutes < 60) {
            return minutes + ' minute' + (minutes !== 1 ? 's' : '') + ' ago';
        }
        
        var hours = Math.floor(minutes / 60);
        if (hours < 24) {
            return hours + ' hour' + (hours !== 1 ? 's' : '') + ' ago';
        }
        
        var days = Math.floor(hours / 24);
        if (days < 30) {
            return days + ' day' + (days !== 1 ? 's' : '') + ' ago';
        }
        
        var months = Math.floor(days / 30);
        if (months < 12) {
            return months + ' month' + (months !== 1 ? 's' : '') + ' ago';
        }
        
        var years = Math.floor(months / 12);
        return years + ' year' + (years !== 1 ? 's' : '') + ' ago';
    }
    
    // Call initialize function to start processing
    initLibrary();
}); 