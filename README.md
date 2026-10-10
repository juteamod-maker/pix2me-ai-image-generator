# Pix2me AI Image Generator

A WordPress plugin that lets logged-in users generate and edit AI images from
text prompts (and images), using Stable Diffusion or OpenAI models, with
myCred-powered point charging for every generation.

一个 WordPress 插件：让登录用户通过文本提示词（以及图片）生成、编辑 AI 图片，
支持 Stable Diffusion 与 OpenAI 模型，并集成 myCred 对每次生成/编辑扣减积分。

> **Note:** this plugin is designed for English-language sites.
> **说明：** 本插件面向英文站点设计。

## Features · 功能

- Embed the AI image generator in any page via shortcodes.
- Support multiple models: Stable Diffusion, OpenAI DALL·E series, GPT-Image-1.
- Image generation **and** image editing (`/v1/images/edits`, `gpt-image-1`).
- myCred integration: charge points per generation/edit.
- Per-user **Asset Library** to view and manage generated images.
- Generation **history** stored as a custom post type.
- WordPress admin settings page for API keys, endpoints and per-call cost.

- 通过短代码在任意页面嵌入 AI 图像生成器。
- 支持多种模型：Stable Diffusion、OpenAI DALL·E 系列、GPT-Image-1。
- 支持图像生成与图像编辑（`/v1/images/edits`，`gpt-image-1`）。
- 集成 myCred：每次生成/编辑扣减积分。
- 用户专属“资产库”查看和管理生成的图片。
- 生成历史以自定义文章类型存储。
- WordPress 后台设置页配置 API 密钥、API 端点与单次成本。

## Requirements · 环境要求

- WordPress 5.x+ (PHP 7.4+ recommended)
- An AI provider account and API key:
  - Stable Diffusion API, or
  - OpenAI API (DALL·E / GPT-Image-1)
- [myCred](https://wordpress.org/plugins/mycred/) plugin (for point charging)

## Installation · 安装

1. Download or clone this repository into `wp-content/plugins/pix2me-ai-image-generator/`.
2. Activate the plugin from the WordPress admin → Plugins screen.
3. Go to the plugin settings page and configure:
   - API type, API key, model and API endpoint,
   - generation cost (points) and the myCred point type,
   - media library access role.

## Shortcodes · 短代码

| Shortcode | Purpose · 用途 |
|---|---|
| `[pix2me_ai_image_generator]` | Image generation page · 图像生成页面 |
| `[pix2me_ai_image_edit]` | Image editing page · 图像编辑页面 |
| `[pix2me_ai_asset_library]` | User asset library · 用户资产库 |
| `[creative_gallery]` | Creative / public gallery · 创意/公共画廊 |
| `[pix2me_ai_history]` | Generation history · 生成历史 |

## Configuration · 配置

All secrets are stored as WordPress options via the admin settings screen —
**never** hard-coded in the source. The relevant option keys are:

```php
'api_type'                 => 'stable_diffusion',   // or 'openai'
'openai_api_key'           => '',                    // OpenAI API key
'openai_model'             => 'dall-e-3',
'stable_diffusion_api_key' => '',                    // Stable Diffusion API key
'stable_diffusion_model'   => 'stable-diffusion-v1-5',
'generation_cost'          => 1,                     // points per generation
'integration_type'         => 'mycred',
'mycred_point_type'        => 'mycred_default',
'media_library_access'     => 'subscriber',
```

所有密钥都通过后台设置页存为 WordPress 选项——**绝不**硬编码在源码中。

## Documentation · 文档

A detailed technical guide (Chinese) is available at
[docs/TECHNICAL_GUIDE.zh-CN.md](docs/TECHNICAL_GUIDE.zh-CN.md).
详细技术文档（中文）见 [docs/TECHNICAL_GUIDE.zh-CN.md](docs/TECHNICAL_GUIDE.zh-CN.md)。

## License · 许可证

Copyright (C) 2025 Pix2me. Licensed under the GNU General Public License v2.0
or later. See [LICENSE](LICENSE).

版权所有 (C) 2025 Pix2me。基于 GNU General Public License v2.0 或更高版本授权。
见 [LICENSE](LICENSE)。
