# Pix2me AI Image Generator 插件技术文档

## 1. 插件概述

Pix2me AI Image Generator 是一个 WordPress 插件，允许登录用户使用 AI（通过外部 API，例如 Stable Diffusion 或 OpenAI）根据文本提示生成图像。它集成了 myCred 插件来管理用户积分，每次生成图像都会扣除相应的积分。

### 1.1 核心功能

* 通过短代码在前端页面嵌入 AI 图像生成器界面
* 支持多种 AI 模型（Stable Diffusion、OpenAI DALL-E 系列、GPT-Image-1 等）
* 集成 myCred 插件进行积分管理和扣除
* 记录用户的图像生成历史（存储为自定义文章类型）
* 提供用户个人的"资产库"以查看和管理生成的图像
* 在 WordPress 后台提供设置页面，用于配置 API 密钥、API 端点和生成成本

### 1.2 版本信息

* 当前版本：1.1.0
* 插件常量：`PIX2ME_AI_IMAGE_GENERATOR_VERSION`

## 2. 系统要求

* WordPress 5.0 或更高版本
* PHP 7.4 或更高版本
* **myCred 插件**：必须安装并激活，用于积分管理
* 外部 AI 图像生成 API 访问权限（Stable Diffusion API 或 OpenAI API）及相应的 API 密钥

## 3. 安装与配置

### 3.1 安装步骤

1. 下载插件的 `.zip` 文件
2. 在 WordPress 后台，导航到 "插件" > "安装插件"
3. 点击 "上传插件" 按钮，选择下载的 `.zip` 文件，然后点击 "现在安装"
4. 安装完成后，点击 "启用插件"

**重要提示**：如果在启用此插件时 myCred 插件未激活，此插件将自动停用并显示错误消息。

### 3.2 配置步骤

1. 导航到 WordPress 后台的 "设置" > "Pix2me AI Generator"
2. 在设置页面中，配置以下信息：
   * **API 类型**：选择 Stable Diffusion 或 OpenAI
   * **API 密钥**：输入相应 API 的密钥
   * **API 端点**：（Stable Diffusion）输入 API 端点 URL
   * **OpenAI 模型**：（OpenAI）选择 DALL-E-2、DALL-E-3 或 GPT-Image-1
   * **生成成本**：设置每次生成图像需要扣除的积分数量
   * **积分类型**：选择使用的 myCred 积分类型
3. 点击 "保存设置" 保存更改

## 4. 插件架构

### 4.1 文件结构

```
pix2me-ai-image-generator/
├── pix2me-ai-image-generator.php  # 主文件，包含插件初始化代码
├── README.md                      # 文档
├── assets/                        # 前端资源
│   ├── css/                       # 样式文件
│   │   ├── pix2me-asset-library.css
│   │   └── pix2me-generate-page.css
│   └── js/                        # JavaScript 文件
│       ├── pix2me-asset-library.js
│       └── pix2me-generate-page.js
├── includes/                      # PHP 核心功能文件
│   ├── admin-settings.php         # 管理员设置页面
│   ├── ajax-handlers.php          # AJAX 请求处理
│   ├── api-handler.php            # 外部 API 通信
│   ├── cache-helper.php           # 缓存辅助函数
│   ├── cpt-history.php            # 自定义文章类型（历史记录）
│   ├── enqueue-scripts.php        # 脚本和样式加载
│   ├── error-logger.php           # 错误日志
│   ├── helper.php                 # 通用辅助函数
│   ├── shortcodes.php             # 短代码定义
│   └── user-logic.php             # 用户权限和积分处理
└── templates/                     # 前端模板
    ├── asset-library.php          # 资产库页面
    ├── generate-page.php          # 生成图像页面
    └── history-page.php           # 历史记录页面
```

### 4.2 插件初始化流程

1. 定义常量（版本、路径、URL）
2. 加载核心文件
3. 注册激活钩子（`register_activation_hook`）
4. 注册初始化钩子（`plugins_loaded`）
5. 加载文本域（国际化支持）

## 5. 核心数据结构

### 5.1 插件选项

插件使用 WordPress 选项 API 存储设置，主要选项名为 `pix2me_ai_image_generator_options`，包含以下键值：

```php
$default_options = array(
    'api_type' => 'stable_diffusion',           // API 类型：stable_diffusion 或 openai
    'openai_api_key' => '',                     // OpenAI API 密钥
    'openai_model' => 'dall-e-3',               // OpenAI 模型：dall-e-2, dall-e-3, gpt-image-1
    'stable_diffusion_api_key' => '',           // Stable Diffusion API 密钥
    'stable_diffusion_model' => 'stable-diffusion-v1-5', // SD 模型
    'generation_cost' => 1,                     // 基础生成成本
    'integration_type' => 'mycred',             // 积分系统类型
    'mycred_point_type' => 'mycred_default',    // myCred 积分类型
    'media_library_access' => 'subscriber'      // 媒体库访问权限
);
```

### 5.2 自定义文章类型

插件定义了一个自定义文章类型 `pix2me_ai_history` 用于存储图像生成历史：

```php
register_post_type('pix2me_ai_history', array(
    'public' => false,
    'publicly_queryable' => false,
    'show_ui' => true,
    'show_in_menu' => true,
    'query_var' => false,
    'rewrite' => false,
    'capability_type' => 'post',
    'has_archive' => false,
    'hierarchical' => false,
    'menu_position' => null,
    'menu_icon' => 'dashicons-images-alt2',
    'supports' => array('title', 'author', 'custom-fields', 'thumbnail'),
    'show_in_rest' => true,
));
```

### 5.3 自定义元数据

#### 5.3.1 历史记录元数据

每个历史记录文章包含以下元数据：

* `_ai_prompt` - 用户输入的提示文本
* `_ai_parameters` - 生成参数（JSON 格式）
* `_ai_api_response` - API 响应（JSON 格式）
* `_ai_media_id` - 关联的媒体库 ID
* `_ai_credit_deducted` - 是否成功扣除积分
* `_ai_generation_cost` - 生成成本

#### 5.3.2 媒体库元数据

生成的图像在媒体库中包含以下元数据：

* `_ai_generated_image` - 标记为 AI 生成的图像
* `_ai_generation_prompt` - 生成图像的提示文本
* `_ai_generation_params` - 生成参数（JSON 格式）
* `_ai_generation_date` - 生成日期
* `_ai_generation_user_id` - 生成用户 ID

## 6. 关键函数和钩子

### 6.1 初始化和激活函数

* `pix2me_ai_image_generator_activate()` - 插件激活时执行，设置默认选项
* `pix2me_ai_image_generator_init()` - 插件初始化，加载文本域

### 6.2 用户和积分管理函数

* `ai_is_mycred_active()` - 检查 myCred 插件是否激活
* `ai_check_user_permission($required_credits)` - 检查用户权限和积分
* `ai_deduct_credits($amount, $log_entry, $ref_id, $data)` - 扣除用户积分
* `ai_get_mycred_balance()` - 获取用户积分余额

### 6.3 API 处理函数

* `ai_generate_image_sd_api($prompt, $options)` - 调用 Stable Diffusion API
* `ai_generate_image_openai_api($prompt, $options)` - 调用 OpenAI API
* `ai_process_api_response($response, $api_type)` - 处理 API 响应

### 6.4 AJAX 处理函数

* `pix2me_ai_image_generator_ajax_generate_image()` - 处理图像生成 AJAX 请求
* `pix2me_ai_image_generator_ajax_save_to_library()` - 处理保存到媒体库的 AJAX 请求

### 6.5 短代码函数

* `pix2me_ai_image_generator_shortcode($atts)` - 渲染生成页面短代码
* `pix2me_ai_asset_library_shortcode($atts)` - 渲染资产库页面短代码
* `pix2me_ai_history_shortcode($atts)` - 渲染历史页面短代码

### 6.6 管理设置函数

* `pix2me_ai_image_generator_add_admin_menu()` - 添加管理菜单
* `pix2me_ai_image_generator_register_settings()` - 注册设置选项
* `pix2me_ai_image_generator_settings_page_html()` - 渲染设置页面

### 6.7 自定义文章类型函数

* `ai_image_generator_register_history_cpt()` - 注册历史记录自定义文章类型
* `ai_image_generator_add_history_meta_boxes()` - 添加元数据框
* `ai_image_generator_set_custom_edit_history_columns()` - 设置自定义列

## 7. 业务逻辑流程

### 7.1 图像生成流程

1. 用户在前端填写表单（提示文本、参数）并提交
2. 前端 JavaScript 通过 AJAX 发送请求到 WordPress
3. `pix2me_ai_image_generator_ajax_generate_image()` 处理请求：
   - 验证用户权限和积分
   - 计算生成成本
   - 根据 API 类型调用相应的 API 函数
   - 处理 API 响应
   - 扣除用户积分
   - 将图像保存到媒体库
   - 创建历史记录
   - 返回结果给前端
4. 前端显示生成的图像

### 7.2 积分计算逻辑

积分计算基于以下因素：

* API 类型（Stable Diffusion 或 OpenAI）
* 模型类型（DALL-E-2、DALL-E-3、GPT-Image-1 等）
* 图像质量（低、标准、高）
* 图像分辨率（方形、横向、纵向）
* 生成数量

计算公式：`总成本 = 基础成本 × 图像数量`

不同模型的基础成本：

* DALL-E-3：
  * 标准质量：方形=2，非方形=4
  * 高质量：方形=4，非方形=6
* GPT-Image-1：
  * 低质量：1
  * 标准质量：方形=2，非方形=3
  * 高质量：方形=8，非方形=12
* DALL-E-2：固定为 1
* Stable Diffusion：使用管理员设置的基础成本

## 8. 前端界面

### 8.1 生成页面

生成页面包含以下主要元素：

* 提示输入框
* 参数选择（分辨率、质量、风格等）
* 生成按钮
* 结果显示区域
* 积分余额显示

### 8.2 资产库页面

资产库页面显示用户生成的所有图像，包括：

* 图像网格布局
* 缩略图和大图查看
* 图像元数据（提示、日期等）

### 8.3 历史页面

历史页面显示用户的生成历史记录，包括：

* 表格布局
* 日期、提示、参数、成本等信息
* 图像预览

## 9. API 集成

### 9.1 Stable Diffusion API

#### 9.1.1 请求参数

```php
$request_data = array(
    'prompt' => $prompt,
    'width' => $width,
    'height' => $height,
    'samples' => $num_outputs,
    'negative_prompt' => $negative_prompt,
    // 其他可选参数
);
```

#### 9.1.2 响应处理

```php
// 成功响应示例
{
    "status": "success",
    "images": [
        "https://example.com/image1.png",
        "https://example.com/image2.png"
    ]
}
```

### 9.2 OpenAI API

#### 9.2.1 请求参数

```php
$request_data = array(
    'model' => $model,
    'prompt' => $prompt,
    'n' => $num_outputs,
    'size' => $resolution,
    'quality' => $quality,
    'style' => $style,
    // 其他可选参数
);
```

#### 9.2.2 响应处理

```php
// 成功响应示例
{
    "created": 1589478378,
    "data": [
        {
            "url": "https://example.com/image1.png"
        },
        {
            "url": "https://example.com/image2.png"
        }
    ]
}
```

## 10. 开发规范

### 10.1 命名约定

* **函数前缀**：所有插件函数应使用 `pix2me_ai_` 或 `ai_` 前缀
* **选项名**：使用 `pix2me_ai_image_generator_` 前缀
* **元数据键**：使用 `_ai_` 前缀
* **短代码**：使用 `pix2me_ai_` 前缀

### 10.2 钩子系统

插件定义了以下自定义钩子：

* **动作钩子**：
  * `pix2me_ai_before_image_generation` - 图像生成前
  * `pix2me_ai_after_image_generation` - 图像生成后
  * `pix2me_ai_before_credit_deduction` - 积分扣除前
  * `pix2me_ai_after_credit_deduction` - 积分扣除后
  * `pix2me_ai_image_saved_to_media` - 图像保存到媒体库后

* **过滤钩子**：
  * `pix2me_ai_generation_parameters` - 修改生成参数
  * `pix2me_ai_credit_cost` - 修改积分成本
  * `pix2me_ai_api_response` - 修改 API 响应

### 10.3 错误处理

* 使用 `ai_log_error()` 函数记录错误
* 使用 `WP_Error` 对象返回错误
* 在 AJAX 处理中使用 `wp_send_json_error()` 返回错误

### 10.4 安全措施

* 使用 `wp_nonce_field()` 和 `check_ajax_referer()` 防止 CSRF 攻击
* 使用 `sanitize_*()` 函数清理用户输入
* 使用 `esc_*()` 函数转义输出
* 检查用户权限和登录状态

## 11. 故障排除

### 11.1 常见问题

* **无法生成图像/API 错误**：
  * 检查 API 密钥和端点是否正确
  * 查看 PHP 错误日志或插件日志
  * 检查服务器是否能访问外部 API

* **积分未扣除/积分不足错误**：
  * 确保 myCred 插件已激活
  * 检查用户积分余额
  * 检查 myCred 日志

* **短代码不显示内容**：
  * 确保用户已登录
  * 确保 myCred 插件已激活
  * 检查短代码拼写是否正确

### 11.2 调试方法

插件提供了错误日志功能，可以通过以下方式启用：

1. 在 `wp-config.php` 中设置 `WP_DEBUG` 为 `true`
2. 使用 `ai_log_error()` 函数记录错误信息

## 12. 未来开发计划

### 12.1 计划功能

* 支持更多 AI 模型和参数选项（优先Gemini，后续Grok）
* 前台页面支持用户选择不同API模型进行生图作业
* 前台图片生成页增加以图生图功能，用户可上传图片与文本提示词（必须填写）配合生图，且用户资产库点击已有生成的图片将利用其种子值进行迭代生成
* 用户资产库内已经生成的图片，将按是否公开，集中显示在一个创意库页面，并且可供浏览者点赞或直接引用提示词进行图片生成
* 将生成增加队列处理功能，用户点击生成后可以关闭页面或刷新生成页面，待生成的图片将自动进入资产库
* 用户资产库内已经生成的图片，默认直接按照其提示词为文章标题生成文章，如用户设置图片隐私不公开，则关联文章也不公开设为私密文章
* 提供更详细的错误日志记录和管理界面
* 改进美化用户的历史页面，参考myCred的用户积分log版式，仅显示扣分时间和扣分关联的图片ID及种子值，无需列出图片

### 12.2 API 扩展

* 添加对 Midjourney API 的支持
* 添加对 Stability AI 的支持
* 添加对 Adobe Firefly 的支持

## 13. 变更日志

### 版本 1.1.1
* 更新积分计算逻辑
* DALL-E 3标准质量非方形图像从4积分降至3积分
* DALL-E 3高质量方形图像从4积分降至3积分
* DALL-E 3高质量非方形图像从6积分降至4积分
* GPT-Image-1标准质量非方形图像从3积分降至2积分
* GPT-Image-1高质量方形图像从8积分降至6积分
* GPT-Image-1高质量非方形图像从12积分降至7积分
* 添加积分定价信息：每积分0.05美元

### 版本 1.1.0
* 添加对 OpenAI DALL-E 3 和 GPT-Image-1 的支持
* 改进积分计算逻辑
* 添加更多图像生成参数
* 优化用户界面

### 版本 1.0.0
* 初始版本
* 支持 Stable Diffusion API
* 基本的图像生成功能
* myCred 积分集成