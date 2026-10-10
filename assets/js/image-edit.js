/**
 * Pix2me AI Image Editor - JavaScript
 */
(function($) {
    'use strict';

    // 成本计算表 - 与后端保持一致
    const COST_TABLE = {
        'gpt-image-1': {
            'low': {
                '1024x1024': 1,
                '1024x1536': 1,
                '15362x1024': 1
            },
            'medium': {
                '1024x1024': 2,
                '1024x1536': 2,
                '1536x1024': 2
            },
            'high': {
                '1024x1024': 6,
                '1024x1536': 7,
                '1536x1024': 7
            }
        },
        'dall-e-2': {
            'standard': {
                '1024x1024': 2,
                '1024x1536': 2,
                '1536x1024': 2
            }
        }
    };

    // --- Style Data (与生成页面保持一致) ---
    const styleData = [
        { name: 'Default Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745620507-nostyle.webp', isHot: false, value: '' },
        { name: 'Ghibli Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745621301-ghibli-style.webp', isHot: false, value: 'Japanese animated fantasy style, inspired by classic hand-drawn Japanese animation, evoking magical realism and a warm, nostalgic atmosphere with exquisite hand-painted textures, soft natural lighting, and whimsical, dreamlike details.' },
        { name: 'Pixar Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745620870-Pixar-Style.webp', isHot: false, value: 'Pixar animation style, creative and imaginative, vibrant colors, family-friendly, expressive 3D characters, dynamic lighting and smooth rendering.' },
        { name: 'Enamel Decal Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745631900-Enamel-Decal-Style.webp', isHot: false, value: 'Enamel decal style, crisp glossy finish, vibrant saturated colors, smooth transitions, converting images or text into a vintage enamel art look.' },
        { name: 'Toy Box Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745632096-Toy-Box-Style.webp', isHot: false, value: 'Transform a character into a movable doll, detailed 3D toy figure with accessories, packaged in a creative toy box design, collector\'s edition style.' }, 
        { name: 'Fast Food Toy Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745632763-Fast-Food-Toy-Style_compressed.png', isHot: false, value: 'McDonald\'s kids meal toy style, playful and colorful cartoon design, small collectible toy aesthetics with fast-food promotional vibe.' },
        { name: 'Polaroid Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745632954-Polaroid-Style_compressed.png', isHot: false, value: 'Polaroid photo style, vintage instant camera effect, faded retro color tones with a thin white border, spontaneous captured moment.' },
        { name: 'Casual Selfie Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745633269-Casual-Selfie-Style.webp', isHot: false, value: 'Casual selfie style, spontaneous composition with no strict subject or framing, natural lighting, relaxed and everyday vibe.' },
        { name: 'Figurine Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745633427-Figurine-Style.webp', isHot: false, value: 'Exquisite figurine style, highly detailed collectible action figure rendered in 3D, dynamic pose with intricate textures and realistic shading.' },
        { name: 'Cute Animal Object Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745633591-Cute-Animal-Object-Style.webp', isHot: false, value: 'Transform everyday objects into cute anthropomorphic animals, playful expressions, soft pastel colors, charming and imaginative character design.' },
        { name: 'Soft Pillow Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745633703-Soft-Pillow-Style.webp', isHot: false, value: 'Ultra-soft plush pillow style, 3D rendered with fluffy, tactile and velvety textures, surreal yet modern with inviting, rounded details.' },
        { name: 'Hand-Sewn Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745633935-Hand-Sewn-Style.webp', isHot: false, value: 'Handcrafted sewing style, soft fabric textures with visible, delicate stitching, warm and intimate tactile quality, reminiscent of artisanal craft.' },
        { name: 'Animal Crossing Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745636018-Animal-Crossing-Style.webp', isHot: false, value: 'Animal Crossing inspired style, charming cartoonish environment, pastel colors, friendly characters, cozy and playful game aesthetic.' },
        { name: 'Cute Plush Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745636180-Cute-Plush-Style.webp', isHot: false, value: 'Cute plush toy style, ultra-soft and huggable 3D texture, fluffy and rounded, modern digital art with a tactile and charming finish.' },
        { name: 'Miniature Diorama Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745636486-Miniature-Diorama-Style.webp', isHot: false, value: 'Miniature diorama style, chibi 3D game aesthetic with a toy-like miniature scene, highly detailed and vibrant, playful and scaled-down visuals.' },
        { name: 'Vintage Photo Restore Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745636588-Vintage-Photo-Restore-Style.webp', isHot: false, value: 'Restored vintage photograph style, enhanced clarity and colorization, subtle textures with natural grain, giving an aged, nostalgic feel.' },
        { name: 'Voxel Art Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745636784-Voxel-Art-Style.webp', isHot: false, value: '3D pixel art (voxel) style, blocky retro gaming aesthetic, vibrant color palette with cubic forms, digital 3D voxel rendering with a charming low-poly look.' },
        { name: 'Claymation Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745636933-Claymation-Style.webp', isHot: false, value: 'Claymation style, realistic clay texture with handcrafted details, cartoonish and anthropomorphic, playful modeling evident in soft, malleable forms.' },
        { name: 'Marble Statue Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745637374-Marble-Statue-Style.webp', isHot: false, value: 'Hyper-realistic marble sculpture style, elegant classical art with polished marble gleam, fine chiseled details that showcase refined craftsmanship and timeless beauty.' },
        { name: 'Graffiti Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745637501-Graffiti-Style.webp', isHot: false, value: 'Crazy graffiti style with handwritten Chinese annotations, spontaneous doodles and creative scribbles, vibrant street art vibe with mixed media textures.' }, 
        { name: 'Line Art Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745637645-Line-Art-Style.webp', isHot: false, value: 'Colorful line drawing style, dynamic and abstract line art with vivid hues, smooth and flowing strokes, modern illustrative technique with geometric precision.' },
        { name: 'Renaissance Painting Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745637758-Renaissance-Painting-Style.webp', isHot: false, value: 'Renaissance painting style, classical portrait and landscape with meticulous brushwork, chiaroscuro lighting, rich historical tones and detailed realism.' },
        { name: 'Disney Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745637860-Disney-Style.webp', isHot: false, value: 'Disney style, inspired by classic Disney animations, enchanting fairy tale ambiance, vibrant colors, smooth line art, soft shading, magical and charming character design, ideal for storybook visuals.' },
        { name: 'Snoopy Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745638004-Snoopy-Style.webp', isHot: false, value: 'Snoopy style, inspired by Peanuts cartoons, minimalistic hand-drawn illustration, simple line art, warm pastel color palette, nostalgic and playful vibe, exuding gentle humor and timeless charm.' },
        { name: 'Realistic Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745638168-Realistic-Style.webp', isHot: false, value: 'Realistic style, ultra-detailed hyper-realistic rendering, true-to-life textures, dynamic lighting and shadows, precise anatomical proportions, natural realistic ambience, photorealistic quality.' },
        { name: 'Cinematic Film Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745638545-Cinematic-Film-Style.webp', isHot: false, value: 'Cinematic film style, vintage movie look with dramatic color grading, film grain overlay, cinematic composition, moody lighting, and a timeless celluloid aesthetic reminiscent of classic films.' },
        { name: 'Stop Motion Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745639132-Stop-Motion-Style.webp', isHot: false, value: 'Stop-motion animation style, handcrafted and tactile look, featuring visible textures and model details, slightly jerky yet charming movements, reminiscent of clay or puppet stop-motion animations.' },
        { name: 'Sticker Note Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745639409-Sticker-Note-Style.webp', isHot: false, value: 'Sticker note style, resembling adhesive stickers with a crisp white border and glossy finish, playful and vibrant design, realistic paper texture with cut-out edges, ideal for modern and fun decals.' }
    ];

    // 缩放图像以符合最大尺寸要求
    function resizeImage(imageData, maxSize = 1024, callback) {
        const img = new Image();
        img.onload = function() {
            let width = img.width;
            let height = img.height;
            
            // 计算缩放比例
            let scale = 1;
            if (width > height && width > maxSize) {
                scale = maxSize / width;
            } else if (height > width && height > maxSize) {
                scale = maxSize / height;
            } else if (width > maxSize && height > maxSize) {
                scale = Math.min(maxSize / width, maxSize / height);
            }
            
            // 如果不需要缩放，则直接返回原图
            if (scale >= 1) {
                callback(imageData);
                return;
            }
            
            // 计算新尺寸
            const newWidth = Math.floor(width * scale);
            const newHeight = Math.floor(height * scale);
            
            // 创建Canvas进行缩放
            const canvas = document.createElement('canvas');
            canvas.width = newWidth;
            canvas.height = newHeight;
            
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, newWidth, newHeight);
            
            // 将Canvas转换为base64数据
            const resizedImageData = canvas.toDataURL('image/jpeg', 0.92);
            callback(resizedImageData);
        };
        
        img.src = imageData;
    }

    // 更新成本显示
    function updateCostDisplay() {
        const model = $('#model').val();
        const quality = $('#quality').val();
        const resolution = $('#resolution').val();
        
        // 基础上传成本 - 固定为1点
        const uploadCost = 1;
        
        // API模型成本 - 根据模型、质量和分辨率计算
        let apiCost = 0;
        
        // 检查是否在成本表中
        if (model in COST_TABLE) {
            const modelTable = COST_TABLE[model];
            
            // 检查质量选项是否在模型表中
            if (quality in modelTable) {
                const qualityTable = modelTable[quality];
                
                // 检查分辨率是否在质量表中
                if (resolution in qualityTable) {
                    apiCost = qualityTable[resolution];
                }
            }
        } else {
            // 对于不在表中的模型，使用系统设置的默认编辑成本
            apiCost = window.pix2meData && window.pix2meData.editCost ? window.pix2meData.editCost : 2;
        }
        
        // 总成本 = 上传成本 + API编辑成本
        const totalCost = uploadCost + apiCost;
        
        // 更新显示
        $('#total-cost-display').text(totalCost);
        $('#total_cost').val(totalCost);
        
        // 检查用户余额是否足够
        const userBalance = parseInt($('#user-balance-display').data('balance') || '0', 10);
        const $editButton = $('#edit-button');
        // 积分不足时禁用按钮并显示提示
        if (userBalance < totalCost) {
            $editButton.addClass('disabled').attr('disabled', 'disabled');
            $('#insufficient-balance-warning').remove();
            var $msg = $('<div id="insufficient-balance-warning" class="error-message" style="margin-top:20px;width:100%;display:block;text-align:left;">Insufficient balance (👉 <a href="https://pix2me.com/account/" style="color:#ff7043;text-decoration:underline;">buy points</a>)</div>');
            $editButton.closest('.action-buttons-container').after($msg);
        } else {
            $editButton.removeClass('disabled').removeAttr('disabled');
            $('#insufficient-balance-warning').remove();
        }
    }

    // 初始化文件上传功能
    function initFileUpload() {
        const $uploadArea = $('#image-preview-area');
        const $fileInput = $('#image-upload');
        const $imagePreview = $('#image-preview');
        const $placeholder = $('#upload-placeholder');
        const $selectButton = $('#select-image-btn');

        // 点击上传区域触发文件选择
        $uploadArea.on('click', function() {
            $fileInput.click();
        });

        // 点击选择图片按钮触发文件选择
        $selectButton.on('click', function(e) {
            e.preventDefault();
            $fileInput.click();
        });

        // 拖放功能
        $uploadArea.on('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('dragover');
        });

        $uploadArea.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');
        });

        $uploadArea.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('dragover');

            const files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileSelect(files[0]);
            }
        });

        // 文件选择处理
        $fileInput.on('change', function() {
            if (this.files.length > 0) {
                handleFileSelect(this.files[0]);
            }
        });

        // 处理选择的文件
        function handleFileSelect(file) {
            // 验证文件类型
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            if (!validTypes.includes(file.type)) {
                alert(pix2meImageEdit.i18n.error_file_type);
                return;
            }

            // 验证文件大小 (10MB 限制)
            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                alert(pix2meImageEdit.i18n.error_file_size);
                return;
            }

            // 读取文件并显示预览
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageData = e.target.result;
                
                // 缩放图像以适应最大尺寸要求
                resizeImage(imageData, 1024, function(resizedImageData) {
                    $imagePreview.attr('src', resizedImageData).show();
                    $placeholder.removeClass('active');
                    
                    // 储存调整大小后的图像数据到隐藏输入
                    $('#image_data').val(resizedImageData);
                });
            };
            
            reader.readAsDataURL(file);
        }

        // 清除按钮功能
        $('#clear-button').on('click', function() {
            $imagePreview.attr('src', '').hide();
            $placeholder.addClass('active');
            $fileInput.val('');
            $('#image_data').val('');
            $('#prompt').val('');
        });
    }

    // 初始化参数菜单
    function initParameterMenus() {
        $('.parameter-button').on('click', function(e) {
            e.preventDefault();
            const $button = $(this);
            const controlId = $button.data('controls');

            if (controlId) {
                const $menu = $('#' + controlId + '-menu');
                // 关闭所有其他菜单
                $('.parameter-menu').not($menu).hide();
                $menu.toggle();
            }
        });

        // 点击菜单项选择值
        $('.parameter-menu-item').on('click', function() {
            const $item = $(this);
            const $menu = $item.parent();
            const value = $item.data('value');
            const controlId = $menu.attr('id').replace('-menu', '');
            const displayValue = $item.text();

            // 更新按钮显示值
            $('button[data-controls="' + controlId + '"] .param-value').text(displayValue);

            // 更新隐藏字段
            $('#' + controlId).val(value);

            // 更新选中状态
            $menu.find('.parameter-menu-item').removeClass('selected');
            $item.addClass('selected');

            // 隐藏菜单
            $menu.hide();

            // 更新成本显示
            updateCostDisplay();
        });

        // 点击外部关闭菜单
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.parameter-button, .parameter-menu').length) {
                $('.parameter-menu').hide();
            }
        });
    }

    // 优化生成等待弹窗，复用生成页面的实现
    function showLoadingModal(message) {
        var $overlay = $('.loading-modal-overlay');
        var $modal = $overlay.find('.loading-modal');
        var $msg = $modal.find('.loading-modal-message');
        if ($msg.length === 0) {
            $msg = $('<div class="loading-modal-message"></div>');
            $modal.append($msg);
        }
        $msg.text(message || 'Processing, please wait...');
        window.lastScrollPosition = window.scrollY || document.documentElement.scrollTop;
        $overlay.css('display', 'flex');
        $('body').addClass('modal-open');
    }
    function hideLoadingModal() {
        var $overlay = $('.loading-modal-overlay');
        $overlay.css('display', 'none');
        $('body').removeClass('modal-open');
        if (window.lastScrollPosition !== undefined) {
            setTimeout(function() {
                window.scrollTo(0, window.lastScrollPosition);
            }, 10);
        }
    }

    // 修改表单提交，使用showLoadingModal/hideLoadingModal
    function initFormSubmit() {
        $('#ai-image-edit-form').on('submit', function(e) {
            e.preventDefault();

            // 验证输入
            const imageData = $('#image_data').val();
            let prompt = $('#prompt').val().trim();
            const stylePrompt = $('#style_prompt').val();
            if (stylePrompt) {
                prompt += ', ' + stylePrompt;
            }

            if (!imageData) {
                alert(pix2meImageEdit.i18n.please_select_image);
                return;
            }

            if (!prompt) {
                alert(pix2meImageEdit.i18n.please_enter_prompt);
                return;
            }

            // 显示优化后的加载指示器
            showLoadingModal('Generating, please wait...');
            $('#ai-error-message').hide().empty();
            $('#ai-image-results').hide();

            // 准备表单数据
            const formData = new FormData(this);
            formData.set('prompt', prompt); // 用拼接后的prompt覆盖原有prompt
            formData.append('action', 'pix2me_edit_image');
            formData.append('nonce', pix2meImageEdit.nonce);

            // 发送AJAX请求
            $.ajax({
                url: pix2meImageEdit.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // 隐藏加载指示器
                    hideLoadingModal();

                    if (response.success) {
                        // 显示结果
                        displayResults(response.data);
                    } else {
                        // 显示错误
                        $('#ai-error-message').html('<p>' + response.data.message + '</p>').show();
                    }
                },
                error: function() {
                    // 隐藏加载指示器
                    hideLoadingModal();
                    // 显示通用错误
                    $('#ai-error-message').html('<p>' + pix2meImageEdit.i18n.error + '</p>').show();
                }
            });
        });
    }

    // 显示结果
    function displayResults(data) {
        // 清空结果网格
        const $resultsGrid = $('#ai-image-results .images-grid');
        $resultsGrid.empty();

        // 显示模型信息
        $('#result-model-info').text('模型: ' + data.model_used);

        // 显示图像
        if (data.image_urls && data.image_urls.length > 0) {
            $.each(data.image_urls, function(index, url) {
                const imageId = data.image_ids && data.image_ids[index] ? data.image_ids[index] : '';
                
                const $imageContainer = $('<div class="image-result"></div>');
                
                // 添加加载指示器
                const $loadingIndicator = $('<div class="loading-indicator"><span>加载中...</span></div>');
                $imageContainer.append($loadingIndicator);
                
                // 创建图像元素并处理加载事件
                const $image = $('<img alt="生成的图像" />');
                
                // 添加错误处理
                $image.on('error', function() {
                    
                    // 如果是OpenAI的URL，尝试加载本地媒体库版本
                    if (url.indexOf('openai.com') > -1 || url.indexOf('staticflickr.com') > -1) {
                        // 图像已成功保存到媒体库，但URL可能失效，使用媒体ID获取本地URL
                        if (imageId) {
                            // 向后端请求获取媒体库URL
                            $.ajax({
                                url: pix2meImageEdit.ajaxurl,
                                type: 'POST',
                                data: {
                                    action: 'pix2me_get_image_details',
                                    nonce: pix2meImageEdit.details_nonce,
                                    image_id: imageId
                                },
                                success: function(response) {
                                    if (response.success && response.data.url) {
                                        // 使用媒体库URL替换
                                        $image.attr('src', response.data.url + '?t=' + new Date().getTime());
                                    } else {
                                        $loadingIndicator.html('<p class="error">图像已生成但无法显示，请到媒体库查看</p>');
                                    }
                                },
                                error: function() {
                                    $loadingIndicator.html('<p class="error">图像已生成但无法显示，请到媒体库查看</p>');
                                }
                            });
                        } else {
                            $loadingIndicator.html('<p class="error">图像加载失败</p>');
                        }
                    } else {
                        $loadingIndicator.html('<p class="error">图像加载失败</p>');
                    }
                });
                
                // 图像加载成功时
                $image.on('load', function() {
                    $loadingIndicator.remove();
                });
                
                // 设置图像源
                $image.attr('src', url);
                $imageContainer.append($image);
                $resultsGrid.append($imageContainer);
                
                // 将图像ID作为data属性存储在图像容器上
                $imageContainer.data('image-id', imageId);
            });
        } else {
            $resultsGrid.html('<p class="no-results">没有生成任何图像</p>');
        }

        // 显示结果区域
        $('#ai-image-results').show();
        $('.result-actions').show();

        // 显示剩余积分
        if (data.remaining_credits !== undefined) {
            $('#user-balance-display').text('剩余积分: ' + data.remaining_credits).attr('data-balance', data.remaining_credits);
        }

        // 绑定操作按钮事件
        $('.save-to-library-button').off('click').on('click', function() {
            alert(pix2meImageEdit.i18n.success);
        });

        $('.download-button').off('click').on('click', function() {
            const $firstImage = $('#ai-image-results .images-grid img').first();
            if ($firstImage.length) {
                const downloadLink = document.createElement('a');
                downloadLink.href = $firstImage.attr('src');
                downloadLink.download = 'ai-edited-image.webp';
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
        });

        // 滚动到结果区域
        $('html, body').animate({
            scrollTop: $('#ai-image-results').offset().top - 100
        }, 500);
    }

    // 风格选择功能 - 修改为与生成页面保持一致
    function initStyleSelection() {
        const styleExamples = document.getElementById('style-examples');
        const styleOverlay = document.getElementById('style-selection-overlay');
        const styleToggleButton = document.getElementById('style-toggle-button');
        const styleButtonValue = document.getElementById('style-button-value');
        const stylePromptInput = document.getElementById('style_prompt');
        
        if (!styleExamples || !styleOverlay || !styleToggleButton) {
            return;
        }
        
        // 点击风格按钮时显示风格选择覆盖层
        styleToggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // 关闭所有参数菜单
            document.querySelectorAll('.parameter-menu').forEach(menu => {
                menu.style.display = 'none';
            });
            
            // 记录当前滚动位置
            window.lastScrollPosition = window.scrollY || document.documentElement.scrollTop;
            
            // 显示风格覆盖层
            styleOverlay.style.display = 'flex';
            
            // 添加modal-open类到body
            document.body.classList.add('modal-open');
            
            // 设置风格覆盖层位置（居中）
            const styleContent = styleOverlay.querySelector('.style-overlay-content');
            if (styleContent) {
                // 确保垂直居中
                setTimeout(function() {
                    styleContent.scrollTop = 0;
                }, 10);
            }
        });
        
        // 点击覆盖层外部区域关闭
        styleOverlay.addEventListener('click', function(e) {
            if (e.target === styleOverlay) {
                styleOverlay.style.display = 'none';
                document.body.classList.remove('modal-open');
                
                // 恢复滚动位置
                if (window.lastScrollPosition !== undefined) {
                    setTimeout(function() {
                        window.scrollTo(0, window.lastScrollPosition);
                    }, 10);
                }
            }
        });
        
        // 清空现有内容，避免重复
        styleExamples.innerHTML = '';
        
        // 填充风格网格示例
        styleData.forEach(style => {
            const styleCard = document.createElement('div');
            styleCard.className = 'style-card';
            styleCard.setAttribute('data-value', style.value);
            
            const styleName = document.createElement('div');
            styleName.className = 'style-name';
            styleName.textContent = style.name;
            
            const styleImage = document.createElement('div');
            styleImage.className = 'style-image';
            styleImage.style.height = '80px'; // 强制高度
            if (style.imageUrl) {
                const img = document.createElement('img');
                img.src = style.imageUrl;
                img.alt = style.name;
                styleImage.appendChild(img);
            }
            
            styleCard.appendChild(styleImage);
            styleCard.appendChild(styleName);
            styleExamples.appendChild(styleCard);
            
            // 设置默认风格为选中状态
            if (style.value === '') {
                styleCard.classList.add('selected');
            }
            
            // 添加点击事件选择风格
            styleCard.addEventListener('click', function() {
                // 更新隐藏输入框的值
                if (stylePromptInput) {
                    stylePromptInput.value = style.value;
                    stylePromptInput.dispatchEvent(new Event('change'));
                }
                
                // 更新按钮文本
                if (styleButtonValue) {
                    styleButtonValue.textContent = style.name;
                }
                
                // 更新视觉选中状态
                document.querySelectorAll('.style-card').forEach(card => {
                    card.classList.remove('selected');
                });
                styleCard.classList.add('selected');
                
                // 隐藏风格覆盖层
                styleOverlay.style.display = 'none';
                document.body.classList.remove('modal-open');
                
                // 恢复滚动位置
                if (window.lastScrollPosition !== undefined) {
                    setTimeout(function() {
                        window.scrollTo(0, window.lastScrollPosition);
                    }, 10);
                }
                
                // 更新成本显示
                updateCostDisplay();
            });
        });
    }

    // 自动上传外部图片（如带image_url参数）
    function autoUploadImageFromUrl() {
        var params = new URLSearchParams(window.location.search);
        var imageUrl = params.get('image_url');
        if (imageUrl) {
            // 仅支持png/jpg
            if (!/\.(png|jpg|jpeg)$/i.test(imageUrl)) return;
            var $imagePreview = $('#image-preview');
            var $placeholder = $('#upload-placeholder');
            var $fileInput = $('#image-upload');
            // 拉取图片并转为base64
            fetch(imageUrl, {mode: 'cors'})
                .then(function(res) { return res.blob(); })
                .then(function(blob) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var imageData = e.target.result;
                        // 缩放图像以适应最大尺寸要求
                        resizeImage(imageData, 1024, function(resizedImageData) {
                            $imagePreview.attr('src', resizedImageData).show();
                            $placeholder.removeClass('active');
                            $('#image_data').val(resizedImageData);
                        });
                    };
                    reader.readAsDataURL(blob);
                })
                .catch(function(){});
        }
    }

    // 初始化页面
    $(document).ready(function() {
        // 更新成本显示
        updateCostDisplay();
        
        // 初始化文件上传
        initFileUpload();
        
        // 初始化参数菜单
        initParameterMenus();
        
        // 初始化表单提交
        initFormSubmit();
        
        // 初始化风格选择
        initStyleSelection();
        
        // 自动上传外部图片
        autoUploadImageFromUrl();
    });

})(jQuery); 