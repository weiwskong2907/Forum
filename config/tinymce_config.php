<?php
/**
 * TinyMCE Configuration
 * This file contains the TinyMCE API key and common configuration settings
 */

// TinyMCE API Key
define('TINYMCE_API_KEY', 'xj1pomo1mrpu7fz9gus1zulblwty6ajfd4c76gtbmsx5fhwn');

/**
 * Get TinyMCE CDN URL with API key
 * 
 * @return string The TinyMCE CDN URL with API key
 */
function getTinymceCdnUrl() {
    return 'https://cdn.jsdelivr.net/npm/tinymce@6.8.2/tinymce.min.js';
}

/**
 * Get common TinyMCE initialization options as a JSON string
 * 
 * @return string JSON string of common TinyMCE options
 */
function getTinymceDefaultOptions() {
    $options = [
        'height' => 400,
        'menubar' => true,
        'plugins' => [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons',
            'codesample', 'quickbars', 'autoresize', 'pagebreak'
        ],
        'toolbar' => 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media codesample emoticons | removeformat | help',
        'content_style' => 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }',
        'image_advtab' => true,
        'image_caption' => true,
        'media_live_embeds' => true,
        'media_alt_source' => true,
        'media_poster' => true,
        'media_dimensions' => true,
        'codesample_languages' => [
            ['text' => 'HTML/XML', 'value' => 'markup'],
            ['text' => 'JavaScript', 'value' => 'javascript'],
            ['text' => 'CSS', 'value' => 'css'],
            ['text' => 'PHP', 'value' => 'php'],
            ['text' => 'Python', 'value' => 'python'],
            ['text' => 'Java', 'value' => 'java'],
            ['text' => 'C', 'value' => 'c'],
            ['text' => 'C#', 'value' => 'csharp'],
            ['text' => 'C++', 'value' => 'cpp']
        ],
        'quickbars_selection_toolbar' => 'bold italic | quicklink h2 h3 blockquote',
        'quickbars_insert_toolbar' => 'image media table'
    ];
    
    return json_encode($options);
}
?>