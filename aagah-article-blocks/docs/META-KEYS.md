# Aagah Article Blocks — Post Meta Keys & Developer API

This document provides technical documentation for developers and external plugins needing programmatic access to the article metadata managed by **Aagah Article Blocks** (v1.7.0+).

---

## 1. Meta Key Reference

All keys are stored as standard WordPress post meta on posts of type `post`.

| Feature | PHP Constant | Post Meta Key | Format / Type |
| :--- | :--- | :--- | :--- |
| **Image Galleries** | `AAB_Meta_Keys::GALLERIES` | `_aab_article_galleries` | Serialized Array / Associative Map |
| **Internal Link Cards** | `AAB_Meta_Keys::INTERNAL_LINKS` | `_aab_article_internal_links` | Serialized Array / Associative Map |
| **Video Playlist** | `AAB_Meta_Keys::VIDEO_PLAYLIST` | `_aab_video_playlist` | Serialized Array / Indexed List |
| **FAQ Accordion** | `AAB_Meta_Keys::FAQ` | `_aab_faq_items` | Serialized Array / Indexed List |

> **Note:** These keys are prefixed with an underscore (`_`), meaning WordPress marks them as private and hides them from the default "Custom Fields" box. They are registered with `register_post_meta` using strict sanitization callbacks.

---

## 2. Data Structures & Schema

### 2.1. Image Galleries (`_aab_article_galleries`)
Stored as an associative map where each key is an alphanumeric unique gallery identifier (e.g., `aab-8f92a1...`). Up to 20 galleries per post, and up to 30 images per gallery.

```php
[
    'aab-gallery-id-1' => [
        'title'       => string, // (e.g. "نمونه‌کارهای طراحی")
        'description' => string, // Optional short description
        'items'       => [
            'aab-img-id-1' => [
                'image_id' => int,    // WordPress attachment ID (image mime only)
                'alt'      => string, // Custom alt text (falls back to media alt or gallery title)
                'caption'  => string, // Optional caption displayed below the image and in lightbox
            ],
            // ...
        ]
    ],
    // ...
]
```

### 2.2. Internal Link Cards (`_aab_article_internal_links`)
Stored as an associative map where each key is an alphanumeric unique link card identifier. Up to 50 cards per post.

```php
[
    'aab-link-id-1' => [
        'target_id' => int,    // ID of the target post, page, or product (must be published)
        'button'    => string, // Button call-to-action label (default: "مشاهده مطلب")
    ],
    // ...
]
```
*Note: Target post title, thumbnail, and permalink are dynamically resolved from `target_id` at render time so links never become stale when destination titles or thumbnails change.*

### 2.3. Video Playlist (`_aab_video_playlist`)
Stored as an indexed, ordered array of video objects. Up to 50 videos per post.

```php
[
    [
        'title'         => string, // Video title (used for playlist navigation & schema)
        'description'   => string, // Video description for VideoObject schema
        'thumbnail_url' => string, // Cover image URL (falls back to featured image / video poster)
        'upload_date'   => string, // YYYY-MM-DD
        'duration'      => string, // Normalized ISO 8601 (PT#M#S) or time format (e.g. "12:30")
        'embed'         => string, // Sanitized HTML embed code (iframe, video tag, Aparat script)
    ],
    // ...
]
```

### 2.4. FAQ Accordion (`_aab_faq_items`)
Stored as an indexed, ordered array of FAQ questions and answers. Up to 50 items per post.

```php
[
    [
        'question' => string, // Question heading
        'answer'   => string, // Formatted answer (HTML supported, sanitized via wp_kses_post)
    ],
    // ...
]
```

---

## 3. Public PHP Helper API

When interacting from themes, child-themes, or other plugins, use the safe helper functions located in `includes/api.php`. Always guard with `function_exists()`:

```php
// 1. Get all blocks in a single associative array
if (function_exists('aab_get_article_blocks')) {
    $blocks = aab_get_article_blocks($post_id);
    // Returns:
    // [
    //     'galleries'      => [...],
    //     'internal_links' => [...],
    //     'video_playlist' => [...],
    //     'faq'            => [...],
    // ]
}

// 2. Read specific block data
if (function_exists('aab_get_galleries')) {
    $galleries = aab_get_galleries($post_id);
}

if (function_exists('aab_get_internal_links')) {
    $links = aab_get_internal_links($post_id);
}

if (function_exists('aab_get_videos')) {
    $videos = aab_get_videos($post_id);
}

if (function_exists('aab_get_faq')) {
    $faq = aab_get_faq($post_id);
}

// 3. Resolve meta key string by name
if (function_exists('aab_get_meta_key')) {
    $key = aab_get_meta_key('galleries'); // returns '_aab_article_galleries'
}
```

---

## 4. Reading Metadata Directly via WordPress Core

If your plugin runs in an environment where Aagah Article Blocks might not be loaded yet, you can read the meta keys directly using `get_post_meta()`:

```php
// Read FAQ items
$faq = get_post_meta($post_id, '_aab_faq_items', true);
if (is_array($faq)) {
    foreach ($faq as $item) {
        echo esc_html($item['question']) . ': ' . esc_html($item['answer']);
    }
}

// Read Galleries
$galleries = get_post_meta($post_id, '_aab_article_galleries', true);
if (is_array($galleries)) {
    foreach ($galleries as $gallery_id => $gallery) {
        $images = $gallery['items'] ?? [];
        // Process images...
    }
}
```

---

## 5. Writing / Updating Metadata Programmatically

Because each meta key is registered with a WordPress `sanitize_callback`, any call to `update_post_meta()` will automatically undergo validation and sanitization:

```php
// Example: Programmatically attaching FAQ items to a post
$new_faq = [
    [
        'question' => 'How can I participate?',
        'answer'   => 'You can register online through our portal.',
    ],
    [
        'question' => 'Is there a prerequisite?',
        'answer'   => 'No prerequisites are required for beginners.',
    ]
];

update_post_meta($post_id, AAB_Meta_Keys::FAQ, $new_faq);
```

---

## 6. Action & Filter Hooks

### 6.1. Post Save Action Hook
Fires immediately after all article blocks have been saved and sanitized from the post editor:

```php
add_action('aab_article_blocks_saved', function ($post_id) {
    // Perform custom cache busting, external sync, or search re-indexing
}, 10, 1);
```

### 6.2. Filter Registered Meta Keys
Add or modify meta keys recognized by `AAB_Meta_Keys::all()`:

```php
add_filter('aab_meta_keys', function ($keys) {
    // $keys['custom_block'] = '_my_custom_block_meta';
    return $keys;
});
```

### 6.3. Add Custom Tabs to the Unified Meta Box
Extend the unified «بلوک‌های مقاله» editor box with custom tabs:

```php
add_filter('aab_meta_box_tabs', function ($tabs) {
    $tabs['my_tab'] = [
        'label'  => 'بلوک اختصاصی',
        'icon'   => 'star-filled', // Dashicons name without prefix
        'render' => function ($post) {
            echo '<div class="aab-mb-panel-head"><h3>تنظیمات اختصاصی</h3></div>';
            echo '<p>محتوای سفارشی افزونه دیگر...</p>';
        },
        'count'  => function ($post_id) {
            return 1; // Number shown in the badge
        },
    ];
    return $tabs;
});
```

---

## 7. Shortcode Reference

| Shortcode | Parameters | Output |
| :--- | :--- | :--- |
| `[aagah_article_gallery]` | `id` *(required)* | Fixed-height responsive image slider with contain-fit images, arrow-only thumbnail scrolling, and integrated lightbox. |
| `[aagah_internal_link]` | `id` *(required)*, `button` *(optional)* | Compact link card with independently linked image, title, and action button. |
| `[aagah_video_playlist]` | `title` *(optional)* | Video player with tabbed playlist switcher and Schema.org VideoObject graph. |
| `[aagah_faq]` | `title` *(optional)*, `open_first` *(yes/no)* | Accordion FAQ container and Schema.org FAQPage LD+JSON. |
| `[aagah_article_toc]` | `title` *(optional)* | Standalone table of contents card with reading progress bar. |
| `[aagah_voice]` | `post_id` *(optional)*, `id` *(optional: zero-based numeric index or voice title slug)* | Multi-file article audio player with keyboard, progress, remaining-time, and Media Session controls. |
| `[aagah_article_content]` | `show_meta`, `show_tags`, `show_breadcrumb`, `show_toc` | Full article body wrapper with auto-extracted TOC. In `[aagah_article_layout]`, the TOC moves to the desktop sidebar and stays before the body on mobile; latest articles follow related articles. |


## Audio voices

The _aab_voice_items meta key is an ordered array of records:

    [
        [
            'url' => 'https://example.test/audio.mp3',
            'title' => 'خوانش مقاله',
            'mime' => 'audio/mpeg',
            'sources' => [
                ['url' => 'https://example.test/audio.mp3', 'mime' => 'audio/mpeg'],
                ['url' => 'https://example.test/audio.ogg', 'mime' => 'audio/ogg'],
            ],
        ],
    ]

The sources list provides alternate encodings to one player. The older _aab_voice_url remains a supported input and is exposed as the first voice when no new list exists. aab_get_voices($post_id) returns the sanitized ordered records.
