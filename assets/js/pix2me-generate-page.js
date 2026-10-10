jQuery(document).ready(function($) {
    // --- Global Variables & Data --- 
    const generatorData = typeof pix2meGeneratorData !== 'undefined' ? pix2meGeneratorData : {};
    const ajaxurl = typeof wpApiSettings !== 'undefined' ? wpApiSettings.ajax_url : (window.ajaxurl || '/wp-admin/admin-ajax.php');
    const nonce = $('#pix2me_nonce_field').val();
    const apiType = generatorData.apiType || 'stable_diffusion';
    const model = generatorData.model || 'stable-diffusion-v1-5'; // Get model from backend
    
    // 获取初始用户余额
    let userBalance = 0;
    const userBalanceElem = document.getElementById('user-balance-display');
    if (userBalanceElem) {
        userBalance = parseFloat(userBalanceElem.getAttribute('data-balance') || 0);
    }

    // --- Style Data (Processed from TISHICI.TXT) ---
    const styleData = [
        { name: 'Default Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745620507-nostyle.webp', isHot: false, value: '' },
        { name: 'Ghibli Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745621301-ghibli-style.webp', isHot: false, value: 'Japanese animated fantasy style, inspired by classic hand-drawn Japanese animation, evoking magical realism and a warm, nostalgic atmosphere with exquisite hand-painted textures, soft natural lighting, and whimsical, dreamlike details.' },
        { name: 'Pixar Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745620870-Pixar-Style.webp', isHot: false, value: 'Pixar animation style, creative and imaginative, vibrant colors, family-friendly, expressive 3D characters, dynamic lighting and smooth rendering.' },
        { name: 'Enamel Decal Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745631900-Enamel-Decal-Style.webp', isHot: false, value: 'Enamel decal style, crisp glossy finish, vibrant saturated colors, smooth transitions, converting images or text into a vintage enamel art look.' },
        { name: 'Toy Box Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745632096-Toy-Box-Style.webp', isHot: false, value: 'Transform a character into a movable doll, detailed 3D toy figure with accessories, packaged in a creative toy box design, collector\'s edition style.' }, // Escaped apostrophe
        { name: 'Fast Food Toy Style', imageUrl: 'https://pix2me.com/wp-content/uploads/2025/04/1745632763-Fast-Food-Toy-Style_compressed.png', isHot: false, value: 'McDonald\'s kids meal toy style, playful and colorful cartoon design, small collectible toy aesthetics with fast-food promotional vibe.' }, // Escaped apostrophe
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
    
    // English prompts for Color and Composition (add more if needed)
    const colorPrompts = {
        '': '', // Standard Color
        'vibrant colors': 'vibrant colors',
        'pastel colors': 'pastel colors',
        'black and white': 'black and white',
        'vintage colors': 'vintage colors',
        'sketch line art': 'sketch line art'
    };
    
    const compositionPrompts = {
        '': '', // Default Composition
        'centered composition': 'centered composition',
        'rule of thirds': 'rule of thirds',
        'symmetrical composition': 'symmetrical composition',
        'close-up shot': 'close-up shot',
        'wide shot': 'wide shot',
        'depth of field': 'depth of field',
        'aerial view': 'aerial view'
    };

    // --- Main Initialization Function --- 
    function initializeApp() {
        // Initialize parameter interactions (dropdowns, etc)
        initializeParameterInteractions();
        
        // Initialize style examples grid
        initializeStyleExamples();
        
        // Initialize image count slider
        initializeImageCountSlider();
        
        // Initialize cost display and calculations
        initializeCostDisplay();
        
        // Initialize form submission 
        initializeFormSubmit();
        
        // Initialize sliders for various parameters
        initializeSliders();
        
        // Initialize tooltips for better UX
        initializeTooltips();
        
        // Initialize clear button functionality
        initializeClearButton();
    }

    /**
     * Initializes the clear button functionality to reset form fields
     */
    function initializeClearButton() {
        $('#clear-button').on('click', function() {
            $('#prompt').val('');
            
            // Reset all parameters to defaults
            resetParameterToDefault('resolution', '1024x1024', 'Square 1024x1024');
            resetParameterToDefault('quality', 'standard', 'Standard Quality');
            resetParameterToDefault('color_prompt', '', 'Standard Color');
            resetParameterToDefault('composition_prompt', '', 'Default Composition');
            
            // Reset style selection safely
            const stylePromptInput = document.getElementById('style_prompt');
            if (stylePromptInput) {
                stylePromptInput.value = '';
            }
            
            const styleButtonValue = document.getElementById('style-button-value');
            if (styleButtonValue) {
                styleButtonValue.textContent = 'Default Style';
            }
            
            // Reset style cards selection
            const styleCards = document.querySelectorAll('.style-card');
            styleCards.forEach(card => {
                card.classList.remove('selected');
            });
            
            // Reset image count slider if it exists
            const slider = document.getElementById('num_outputs');
            const countDisplay = document.getElementById('image-count-display');
            if (slider && countDisplay) {
                slider.value = 1;
                countDisplay.textContent = '1';
            } else if (slider) {
                // If slider exists but display doesn't
                slider.value = 1;
            }
            
            // Update cost after resetting parameters
            updateCostDisplay();
            
            // Hide any previous results or errors
            $('#ai-image-results').hide();
            $('#ai-error-message').hide();
        });
    }

    /**
     * Initializes tooltips for elements with data-tooltip attributes
     * Shows helpful information on hover
     */
    function initializeTooltips() {
        $('.tooltip-trigger').on({
            mouseenter: function() {
                const $this = $(this);
                const tooltip = $this.data('tooltip');
                
                if (!tooltip) return;
                
                // Create tooltip element if not exists
                let $tooltip = $('#custom-tooltip');
                if (!$tooltip.length) {
                    $tooltip = $('<div id="custom-tooltip"></div>');
                    $('body').append($tooltip);
                }
                
                // Position and show tooltip
                const position = $this.offset();
                const width = $this.outerWidth();
                const height = $this.outerHeight();
                
                $tooltip
                    .html(tooltip)
                    .css({
                        top: position.top + height + 10,
                        left: position.left + (width / 2) - 100
                    })
                    .addClass('visible');
            },
            mouseleave: function() {
                $('#custom-tooltip').removeClass('visible');
            }
        });
    }

    /**
     * Initializes sliders like the image count slider
     * Updates displays and triggers cost calculations
     */
    function initializeSliders() {
        const slider = document.getElementById('num_outputs');
        const display = document.getElementById('image-count-display');

        // Only set up event handlers if both elements exist
        if (slider && display) {
            // Set initial display value
            display.textContent = slider.value || '1';
            
            // Update display when slider changes
            slider.addEventListener('input', function() {
                display.textContent = this.value;
                updateCostDisplay(); // Update cost when image count changes
            });
        }
    }

    // --- Event Handlers & UI Logic --- 

    /**
     * Initializes interactions for parameter buttons and dropdowns.
     */
    function initializeParameterInteractions() {
        const $parameterItems = $('.parameter-item');

        // Parameter button click to toggle menu (excluding disabled style button)
        $parameterItems.find('.parameter-button:not([disabled])').on('click', function(e) {
            e.stopPropagation();
            const $button = $(this);
            // Use data-controls attribute to find the menu reliably
            const controlsId = $button.data('controls'); 
            if (!controlsId) return; // Do nothing if button doesn't control anything
            const $menu = $('#' + controlsId + '-menu');
            
            if (!$menu.length) return; // Menu not found
            
            const isActive = $menu.hasClass('show');

            // Close all other menus first
            $('.parameter-menu.show').removeClass('show');
            $('.parameter-button.active').removeClass('active');

            // Toggle current menu
            if (!isActive) {
                $menu.addClass('show');
                $button.addClass('active');
            }
        });

        // Menu item click to select option (using event delegation on the container)
        $('.parameters-section').on('click', '.parameter-menu-item', function() {
            const $item = $(this);
            const $menu = $item.closest('.parameter-menu');
            if (!$menu.length) return;
            
            const menuId = $menu.attr('id');
            const controlsId = menuId.replace('-menu', '');
            const $button = $('.parameter-button[data-controls="' + controlsId + '"]');
            const $input = $('#' + controlsId);
            const value = $item.data('value');
            let text = $item.text().trim(); // Use trimmed text

            // Only proceed if the value actually changed
            if ($input.length && $input.val() !== value) {
                // Update button text
                if($button.length) {
                     $button.find('.param-value').text(text);
                }

                // Update selected state in menu
                $menu.find('.parameter-menu-item').removeClass('selected');
                $item.addClass('selected');

                // Update hidden input value
                $input.val(value).trigger('change'); // Explicitly trigger change event
                
                 // IMPORTANT: Call cost update AFTER input value is set
                 updateCostDisplay(); 
            } else if (!$input.length) {
                 console.warn('Hidden input not found for ID:', controlsId);
            }
            
            // Close the menu regardless
            $menu.removeClass('show');
            if($button.length) {
                 $button.removeClass('active');
            }
        });

        // Close menus when clicking outside
        $(document).on('click', function(e) {
            // Check if the click is outside any parameter item
            if (!$(e.target).closest('.parameter-item').length) {
                $('.parameter-menu.show').removeClass('show');
                $('.parameter-button.active').removeClass('active');
            }
        });
    }
    
    /**
     * Initialize style examples grid with data
     * Add overlay toggle functionality and selection logic
     */
    function initializeStyleExamples() {
        const styleExamples = document.getElementById('style-examples');
        const styleOverlay = document.getElementById('style-selection-overlay');
        const styleToggleButton = document.getElementById('style-toggle-button');
        const styleButtonValue = document.getElementById('style-button-value');
        const stylePromptInput = document.getElementById('style_prompt');
        
        if (!styleExamples || !styleOverlay || !styleToggleButton) {
            return;
        }
        
        // Toggle style overlay visibility when style button is clicked
        styleToggleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close all parameter menus first
            document.querySelectorAll('.parameter-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
            document.querySelectorAll('.parameter-button.active').forEach(button => {
                button.classList.remove('active');
            });
            
            // Record current scroll position
            window.lastScrollPosition = window.scrollY || document.documentElement.scrollTop;
            
            // Show style overlay
            styleOverlay.style.display = 'flex';
            
            // Add modal-open class to body
            document.body.classList.add('modal-open');
            
            // Set style overlay position (centered)
            const styleContent = styleOverlay.querySelector('.style-overlay-content');
            if (styleContent) {
                // Ensure vertical centering
                setTimeout(function() {
                    styleContent.scrollTop = 0;
                }, 10);
            }
        });
        
        // Close overlay when clicking outside the content
        styleOverlay.addEventListener('click', function(e) {
            if (e.target === styleOverlay) {
                styleOverlay.style.display = 'none';
                document.body.classList.remove('modal-open');
                
                // Restore scroll position
                if (window.lastScrollPosition !== undefined) {
                    setTimeout(function() {
                        window.scrollTo(0, window.lastScrollPosition);
                    }, 10);
                }
            }
        });
        
        // Clear existing content to avoid duplicates
        styleExamples.innerHTML = '';
        
        // Populate style grid with examples
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
            
            // Set the default style card as selected
            if (style.value === '') {
                styleCard.classList.add('selected');
            }
            
            // Add click event to select style
            styleCard.addEventListener('click', function() {
                console.log('Selected style:', style.name);
                
                // Update hidden input with selected style value
                if (stylePromptInput) {
                    stylePromptInput.value = style.value;
                    stylePromptInput.dispatchEvent(new Event('change'));
                }
                
                // Update button text
                if (styleButtonValue) {
                    styleButtonValue.textContent = style.name;
                }
                
                // Update visual selection
                document.querySelectorAll('.style-card').forEach(card => {
                    card.classList.remove('selected');
                });
                styleCard.classList.add('selected');
                
                // Hide style overlay
                styleOverlay.style.display = 'none';
                document.body.classList.remove('modal-open');
                
                // Restore scroll position
                if (window.lastScrollPosition !== undefined) {
                    setTimeout(function() {
                        window.scrollTo(0, window.lastScrollPosition);
                    }, 10);
                }
                
                // Update cost display
                updateCostDisplay();
            });
        });
    }

    /**
     * Initializes the image count slider interaction
     * Safely checks element existence before adding event listeners
     */
    function initializeImageCountSlider() {
        const slider = document.getElementById('num_outputs');
        const display = document.getElementById('image-count-display');

        // Only set up event handlers if both elements exist
        if (slider && display) {
            // Set initial display value
            display.textContent = slider.value || '1';
            
            // Add input event listener
            slider.addEventListener('input', function() {
                display.textContent = this.value;
                updateCostDisplay();
            });
        }
    }

    /**
     * Get the base cost for the selected model and API type
     * @param {string} model - The model identifier
     * @param {string} apiType - The API type (openai, stable_diffusion, etc)
     * @returns {number} The base cost
     */
    function getModelBaseCost(model, apiType) {
        let baseCost = 1; // Default base cost
        
        if (apiType === 'openai') {
            if (model === 'dall-e-3') {
                baseCost = 2; // Base cost for DALL-E 3 (will be multiplied by resolution/quality)
            } else if (model === 'gpt-image-1') {
                baseCost = 1; // Base cost for GPT-Image-1 (will be multiplied)
            } else { // DALL-E 2
                baseCost = 1; // Fixed base cost
            }
        } else {
            // Stable Diffusion/Other API
            baseCost = window.pix2meData?.baseCost || 1;
        }
        
        return baseCost;
    }

    /**
     * Get the resolution multiplier for cost calculation
     * @param {string} resolution - The resolution value
     * @param {string} model - The model identifier
     * @returns {number} The resolution multiplier
     */
    function getResolutionMultiplier(resolution, model) {
        if (model === 'dall-e-3') {
            return resolution === '1024x1024' ? 1 : 1.5; // 非方形成本为1.5倍基础成本
        } else {
            // 其他模型不再根据分辨率调整价格
            return 1;
        }
    }

    /**
     * Get the quality multiplier for cost calculation
     * @param {string} quality - The quality value (low, standard, high)
     * @param {string} model - The model identifier
     * @returns {number} The quality multiplier
     */
    function getQualityMultiplier(quality, model) {
        if (model === 'dall-e-3') {
            return quality === 'high' ? 1.5 : 1; // 高质量成本为1.5倍标准质量
        } else if (model === 'gpt-image-1') {
            if (quality === 'low') return 1;
            if (quality === 'standard') return 2;
            
            // 高质量GPT-Image-1的处理
            if (quality === 'high') {
                // 获取当前选择的分辨率
                const resolution = document.getElementById('resolution')?.value || '1024x1024';
                const isSquare = resolution === '1024x1024';
                
                // 根据是否为方形返回不同成本
                return isSquare ? 6 : 7; // 方形=6，非方形=7积分
            }
        }
        
        return 1; // Default multiplier for other models/qualities
    }

    /**
     * Calculate and update the total cost based on selected parameters
     * Also checks user balance and enables/disables generate button accordingly
     * @returns {number} The calculated total cost
     */
    function updateCostDisplay() {
        // Get all parameter values from form
        const apiTypeInput = document.getElementById('api_type');
        const modelInput = document.getElementById('model');
        const qualityInput = document.getElementById('quality');
        const resolutionInput = document.getElementById('resolution');
        const numOutputsInput = document.getElementById('num_outputs');
        const costDisplay = document.getElementById('total-cost-display');
        
        // Ensure all required inputs exist
        if (!apiTypeInput || !modelInput || !qualityInput || !resolutionInput) {
            if (costDisplay) costDisplay.textContent = '1';
            return 1;
        }
        
        // Get current values
        const apiType = apiTypeInput.value;
        const model = modelInput.value;
        const quality = qualityInput.value;
        const resolution = resolutionInput.value;
        const numOutputs = parseInt(numOutputsInput ? numOutputsInput.value : 1);
        
        // Get base costs and multipliers
        const baseCost = getModelBaseCost(model, apiType);
        const resolutionMultiplier = getResolutionMultiplier(resolution, model);
        const qualityMultiplier = getQualityMultiplier(quality, model);
        
        // 特殊处理GPT-Image-1模型的高质量非方形情况
        let totalCost = 0;
        if (model === 'gpt-image-1' && quality === 'high') {
            // 直接使用qualityMultiplier，因为它已经考虑了分辨率因素
            totalCost = Math.ceil(baseCost * qualityMultiplier * numOutputs);
        } else {
            // 常规计算
            totalCost = Math.ceil(baseCost * resolutionMultiplier * qualityMultiplier * numOutputs);
        }
        
        // Update UI
        if (costDisplay) {
            costDisplay.textContent = totalCost;
        }
        
        // Update hidden input for form submission
        const totalCostInput = document.querySelector('input[name="total_cost"]');
        if (totalCostInput) {
            totalCostInput.value = totalCost;
        }
        
        // Get user balance and check if sufficient
        const userBalanceElem = document.getElementById('user-balance-display');
        const generateButton = document.getElementById('generate-button');
        
        if (userBalanceElem && generateButton) {
            const userBalance = parseFloat(userBalanceElem.getAttribute('data-balance') || 0);
            const enoughCredits = userBalance >= totalCost;
            
            // Change generate button state based on balance
            generateButton.classList.toggle('disabled', !enoughCredits);
            
            if (!enoughCredits) {
                generateButton.disabled = true;
                generateButton.title = 'Insufficient balance (👉 buy points)';
                
                // Add clear insufficient balance error
                const errorMsg = document.getElementById('credit-error-message') || document.createElement('div');
                errorMsg.id = 'credit-error-message';
                errorMsg.className = 'error-message';
                errorMsg.innerHTML = `Insufficient balance (👉 <a href="https://pix2me.com/account/" style="color:#ff7043;text-decoration:underline;">buy points</a>)`;
                
                if (!document.getElementById('credit-error-message')) {
                    const form = document.getElementById('ai-generate-form');
                    if (form) {
                        form.parentNode.insertBefore(errorMsg, document.getElementById('ai-error-message'));
                    }
                }
                
                // Also update balance display to highlight
                userBalanceElem.parentElement.classList.add('insufficient');
            } else {
                generateButton.disabled = false;
                generateButton.title = '';
                
                // Remove any previous error message
                userBalanceElem.parentElement.classList.remove('insufficient');
                const errorMsg = document.getElementById('credit-error-message');
                if (errorMsg) {
                    errorMsg.remove();
                }
            }
        }
        
        return totalCost;
    }

    /**
     * Initialize cost display functionality
     * Adds event listeners to parameter inputs and ensures initial cost is calculated
     */
    function initializeCostDisplay() {
        // Add event listeners to update costs when parameters change
        const parameterInputs = document.querySelectorAll('input[name="resolution"], input[name="quality"], input[name="api_type"], input[name="model"], input[name="num_outputs"]');
        
        parameterInputs.forEach(input => {
            input.addEventListener('change', updateCostDisplay);
        });
        
        // Listen for changes to image count input
        const numOutputsInput = document.querySelector('input[name="num_outputs"]');
        if (numOutputsInput) {
            numOutputsInput.addEventListener('input', updateCostDisplay);
        }
        
        // Listen for parameter button clicks
        const parameterButtons = document.querySelectorAll('.parameter-item');
        parameterButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Delay update to ensure hidden fields are updated
                setTimeout(updateCostDisplay, 50);
            });
        });
        
        // Ensure initial cost is calculated on page load
        // Call with a small delay to ensure DOM is ready
        setTimeout(updateCostDisplay, 100);
        
        // Double-check after a longer delay (safety measure)
        setTimeout(function() {
            const costDisplay = document.getElementById('total-cost-display');
            if (costDisplay && (costDisplay.textContent === '--' || costDisplay.textContent === '')) {
                updateCostDisplay();
            }
        }, 500);
    }

    /**
     * Initializes the form submission logic via AJAX.
     * Uses a centered modal loading indicator for better UX on all devices
     */
    function initializeFormSubmit() {
        $('#ai-generate-form').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const $submitButton = $('#generate-button');
            const $errorMessage = $('#ai-error-message');
            const $resultsContainer = $('#ai-image-results');
            const $imagesGrid = $resultsContainer.find('.images-grid');
            const $resultInfo = $resultsContainer.find('.result-info');
            const $resultModelInfo = $('#result-model-info');
            const $resultSeedInfo = $('#result-seed-info');
            const $resultActions = $resultsContainer.find('.result-actions');
            const $seedInput = $('#result_seed');

            // Calculate current cost
            updateCostDisplay();
            const totalCostInput = document.querySelector('input[name="total_cost"]');
            const currentCost = parseInt(totalCostInput.value);
            const userBalanceElem = document.getElementById('user-balance-display');
            const userBalance = parseFloat(userBalanceElem.getAttribute('data-balance') || 0);

            // Final validation
            if (userBalance < currentCost) {
                $errorMessage.html('Insufficient balance (requires ' + currentCost + ' points)').show();
                return;
            }
            if (isNaN(currentCost)) {
                 $errorMessage.html('Unable to calculate cost. Please check your options.').show();
                 return;
            }
             const prompt = $('#prompt').val().trim();
             if (!prompt) {
                 $errorMessage.html('Please enter a prompt.').show();
                return;
            }

            // UI feedback: Loading state
            $submitButton.prop('disabled', true);
            showLoadingModal('Generating, please wait...');
            $errorMessage.hide().empty();
            $resultsContainer.hide();
            $imagesGrid.empty();
            $resultInfo.hide();
            $resultActions.hide();
            $seedInput.val('');

            // Prepare data for submission
            const formData = new FormData(this);
            formData.append('action', 'pix2me_ai_image_generator_ajax_generate_image');
            formData.append('nonce', nonce);
            formData.append('total_cost', currentCost);
            
            // Construct the final prompt
            let finalPrompt = $('#prompt').val().trim();
            const stylePrompt = $('#style_prompt').val();
            const colorPrompt = $('#color_prompt').val();
            const compositionPrompt = $('#composition_prompt').val();
            
            if (stylePrompt) finalPrompt += ', ' + stylePrompt;
            if (colorPrompt) finalPrompt += ', ' + colorPrompt;
            if (compositionPrompt) finalPrompt += ', ' + compositionPrompt;
            
            formData.set('prompt', finalPrompt); // Update the prompt in form data
            
            // Ensure correct number of outputs is sent (value from slider or hidden input)
            const numOutputs = $('#num_outputs').val() || 1;
            formData.set('num_outputs', numOutputs);
            
            // Ensure Quality sent matches what the backend expects if needed
            // (Backend should handle this primarily based on model)
            const qualityValue = $('#quality').val() || 'standard';
            formData.set('quality', qualityValue); 
            
            // Ensure resolution is sent
             const resolutionValue = $('#resolution').val() || '1024x1024';
             formData.set('resolution', resolutionValue); 

            // AJAX Call
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success && response.data) {
                        const images = response.data.image_urls;
                        const seed = response.data.seed;
                        const remainingBalance = response.data.remaining_balance;
                        // Assume backend also returns model used if needed, or use frontend `model` var
                        const modelUsed = response.data.model_used || model; 

                        if (images && Array.isArray(images) && images.length > 0) {
                            images.forEach(function(imageUrl) {
                                const $imgContainer = $('<div>').addClass('generated-image-container');
                                const $img = $('<img>').attr('src', imageUrl).on('error', function() {
                                    $(this).replaceWith('<p>Error loading image</p>');
                                });
                                $imgContainer.append($img);
                                $imagesGrid.append($imgContainer);
                            });
                            
                             // Display result info
                            $resultModelInfo.text('Model: ' + modelUsed);
                            if (seed) {
                                $seedInput.val(seed);
                                $resultSeedInfo.text('Seed: ' + seed);
                                $resultActions.show(); 
                            } else {
                                $resultSeedInfo.text('');
                            }
                            $resultInfo.show();
                            $resultsContainer.show();

                            // Update balance
                            if (typeof remainingBalance !== 'undefined') {
                                const userBalanceElem = document.getElementById('user-balance-display');
                                if (userBalanceElem) {
                                    userBalanceElem.textContent = Math.floor(remainingBalance);
                                    userBalanceElem.setAttribute('data-balance', Math.floor(remainingBalance));
                                    // Update form state
                                    updateCostDisplay();
                                }
                            }
                        } else {
                            $errorMessage.html(response.data.message || 'Generation failed: No images returned.').show();
                        }
                    } else {
                        const message = response.data && response.data.message ? response.data.message : 'An unknown error occurred during generation.';
                        $errorMessage.html(message).show();
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    $errorMessage.html('Request failed. Please check your connection or contact support.').show();
                },
                complete: function() {
                    hideLoadingModal();
                    $submitButton.prop('disabled', false);
                    updateCostDisplay();
                }
            });
        });
        
        // Copy Seed functionality
        $('.copy-seed-button').on('click', function() {
            const seed = $('#result_seed').val();
            if (seed) {
                navigator.clipboard.writeText(seed).then(function() {
                    alert('Seed value copied to clipboard');
                }, function(err) {
                    alert('Failed to copy seed: ' + err);
                });
            }
        });
    }

    /**
     * Resets a parameter to its default value
     * Safely checks for element existence before setting values
     */
    function resetParameterToDefault(inputId, defaultValue, defaultText) {
        const input = document.getElementById(inputId);
        if (input) {
            input.value = defaultValue;
        }
        
        const button = document.querySelector(`.parameter-button[data-controls="${inputId}"]`);
        if (button) {
            const valueSpan = button.querySelector('.param-value');
            if (valueSpan) {
                valueSpan.textContent = defaultText;
            }
        }
        
        const menu = document.getElementById(inputId + '-menu');
        if (menu) {
            const items = menu.querySelectorAll('.parameter-menu-item');
            items.forEach(item => {
                item.classList.remove('selected');
                if (item.getAttribute('data-value') === defaultValue) {
                    item.classList.add('selected');
                }
            });
        }
    }

    // Show loading modal with message
    function showLoadingModal(message) {
        const loadingModalOverlay = document.querySelector('.loading-modal-overlay');
        const loadingModalMessage = document.querySelector('.loading-modal-message');
        
        // Record current scroll position
        window.lastScrollPosition = window.scrollY || document.documentElement.scrollTop;
        
        if (loadingModalMessage) {
            loadingModalMessage.textContent = message || 'Processing your request...';
        }
        
        if (loadingModalOverlay) {
            loadingModalOverlay.style.display = 'flex';
            document.body.classList.add('modal-open');
        }
    }
    
    // Hide loading modal
    function hideLoadingModal() {
        const loadingModalOverlay = document.querySelector('.loading-modal-overlay');
        
        if (loadingModalOverlay) {
            loadingModalOverlay.style.display = 'none';
            document.body.classList.remove('modal-open');
            
            // Restore scroll position
            if (window.lastScrollPosition !== undefined) {
                setTimeout(function() {
                    window.scrollTo(0, window.lastScrollPosition);
                }, 10);
            }
        }
    }

    // ---------- Now call the initialization function ----------
    // Initialize the application after all functions and data are defined
    initializeApp();
}); 