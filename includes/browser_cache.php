<?php
/**
 * Browser Caching Helper
 * 
 * Provides functions to implement browser-side caching
 */

/**
 * Set browser cache headers for static content
 * 
 * @param int $maxAge Cache lifetime in seconds
 */
function setBrowserCacheHeaders($maxAge = 86400) {
    // Set cache control headers
    header("Cache-Control: public, max-age={$maxAge}");
    
    // Set expires header
    $expiresTime = time() + $maxAge;
    header("Expires: " . gmdate("D, d M Y H:i:s", $expiresTime) . " GMT");
    
    // Set last modified header to current time
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
}

/**
 * Check if browser cache is still valid using ETag
 * 
 * @param string $etagData Data to generate ETag from
 * @return bool True if client cache is valid, false otherwise
 */
function checkETagCache($etagData) {
    // Generate ETag from data
    $etag = '"' . md5($etagData) . '"';
    
    // Set ETag header
    header("ETag: {$etag}");
    
    // Check if client sent If-None-Match header
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $etag) {
        // Client cache is still valid
        header("HTTP/1.1 304 Not Modified");
        exit;
    }
    
    return false;
}

/**
 * Set cache headers for dynamic content with validation
 * 
 * @param string $etagData Data to generate ETag from
 * @param int $maxAge Cache lifetime in seconds
 */
function setDynamicContentCache($etagData, $maxAge = 300) {
    // Set cache control headers for dynamic content
    header("Cache-Control: private, must-revalidate, max-age={$maxAge}");
    
    // Check ETag cache
    checkETagCache($etagData);
}