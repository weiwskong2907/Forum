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
        'menubar' => false,
        'plugins' => [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        'toolbar' => 'undo redo | blocks | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        'content_style' => 'body { font-family:Helvetica,Arial,sans-serif; font-size:16px }'
    ];
    
    return json_encode($options);
}
?>