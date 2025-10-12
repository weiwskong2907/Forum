<?php
/**
 * System Cache Model
 * 
 * Handles caching of frequently accessed data
 */
class SystemCache {
    private $db;
    private $cacheDir;
    private $enabled = true;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->cacheDir = __DIR__ . '/../cache';
        $this->createTableIfNotExists();
        $this->ensureCacheDirectory();
    }
    
    /**
     * Create the system_cache table if it doesn't exist
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS system_cache (
            cache_id VARCHAR(255) PRIMARY KEY,
            cache_data LONGTEXT,
            cache_type VARCHAR(50) NOT NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $this->db->query($sql);
    }
    
    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory() {
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Enable or disable caching
     * 
     * @param bool $enabled
     */
    public function setEnabled($enabled) {
        $this->enabled = (bool)$enabled;
    }
    
    /**
     * Get cache data from database
     * 
     * @param string $cacheId
     * @param string $cacheType
     * @return mixed|null
     */
    public function get($cacheId, $cacheType = 'general') {
        if (!$this->enabled) {
            return null;
        }
        
        // Try file cache first (faster)
        $fileCache = $this->getFileCache($cacheId, $cacheType);
        if ($fileCache !== null) {
            return $fileCache;
        }
        
        // Fall back to database cache
        $sql = "SELECT cache_data, expires_at FROM system_cache 
                WHERE cache_id = ? AND cache_type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $cacheId, $cacheType);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Check if cache has expired
            if ($row['expires_at'] !== null && strtotime($row['expires_at']) < time()) {
                $this->delete($cacheId, $cacheType);
                return null;
            }
            
            return json_decode($row['cache_data'], true);
        }
        
        return null;
    }
    
    /**
     * Set cache data in database
     * 
     * @param string $cacheId
     * @param mixed $data
     * @param string $cacheType
     * @param int|null $ttl Time to live in seconds, null for no expiration
     * @return bool
     */
    public function set($cacheId, $data, $cacheType = 'general', $ttl = null) {
        if (!$this->enabled) {
            return false;
        }
        
        $expiresAt = null;
        if ($ttl !== null) {
            $expiresAt = date('Y-m-d H:i:s', time() + $ttl);
        }
        
        $jsonData = json_encode($data);
        
        // Set in file cache for faster access
        $this->setFileCache($cacheId, $data, $cacheType, $ttl);
        
        // Also store in database for persistence
        $sql = "INSERT INTO system_cache (cache_id, cache_data, cache_type, expires_at) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                cache_data = VALUES(cache_data), 
                cache_type = VALUES(cache_type), 
                expires_at = VALUES(expires_at)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssss', $cacheId, $jsonData, $cacheType, $expiresAt);
        
        return $stmt->execute();
    }
    
    /**
     * Delete cache data from database
     * 
     * @param string $cacheId
     * @param string $cacheType
     * @return bool
     */
    public function delete($cacheId, $cacheType = 'general') {
        // Delete from file cache
        $this->deleteFileCache($cacheId, $cacheType);
        
        // Delete from database
        $sql = "DELETE FROM system_cache 
                WHERE cache_id = ? AND cache_type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $cacheId, $cacheType);
        
        return $stmt->execute();
    }
    
    /**
     * Clear all cache data of a specific type
     * 
     * @param string $cacheType
     * @return bool
     */
    public function clearByType($cacheType) {
        // Clear file cache of this type
        $this->clearFileCacheByType($cacheType);
        
        // Clear database cache
        $sql = "DELETE FROM system_cache WHERE cache_type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $cacheType);
        
        return $stmt->execute();
    }
    
    /**
     * Clear all cache data
     * 
     * @return bool
     */
    public function clearAll() {
        // Clear all file cache
        $this->clearAllFileCache();
        
        // Clear all database cache
        $sql = "TRUNCATE TABLE system_cache";
        
        return $this->db->query($sql);
    }
    
    /**
     * Get cache data from file
     * 
     * @param string $cacheId
     * @param string $cacheType
     * @return mixed|null
     */
    private function getFileCache($cacheId, $cacheType) {
        $filePath = $this->getCacheFilePath($cacheId, $cacheType);
        
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $cacheData = json_decode($content, true);
            
            // Check if cache has expired
            if (isset($cacheData['expires_at']) && $cacheData['expires_at'] < time()) {
                $this->deleteFileCache($cacheId, $cacheType);
                return null;
            }
            
            return $cacheData['data'];
        }
        
        return null;
    }
    
    /**
     * Set cache data in file
     * 
     * @param string $cacheId
     * @param mixed $data
     * @param string $cacheType
     * @param int|null $ttl
     * @return bool
     */
    private function setFileCache($cacheId, $data, $cacheType, $ttl) {
        $filePath = $this->getCacheFilePath($cacheId, $cacheType);
        
        $cacheData = [
            'data' => $data,
            'expires_at' => $ttl !== null ? time() + $ttl : null
        ];
        
        return file_put_contents($filePath, json_encode($cacheData)) !== false;
    }
    
    /**
     * Delete cache data from file
     * 
     * @param string $cacheId
     * @param string $cacheType
     * @return bool
     */
    private function deleteFileCache($cacheId, $cacheType) {
        $filePath = $this->getCacheFilePath($cacheId, $cacheType);
        
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        
        return true;
    }
    
    /**
     * Clear all file cache of a specific type
     * 
     * @param string $cacheType
     */
    private function clearFileCacheByType($cacheType) {
        $typeDir = $this->cacheDir . '/' . $cacheType;
        
        if (file_exists($typeDir)) {
            $files = glob($typeDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
    }
    
    /**
     * Clear all file cache
     */
    private function clearAllFileCache() {
        $files = glob($this->cacheDir . '/*/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get cache file path
     * 
     * @param string $cacheId
     * @param string $cacheType
     * @return string
     */
    private function getCacheFilePath($cacheId, $cacheType) {
        $typeDir = $this->cacheDir . '/' . $cacheType;
        
        if (!file_exists($typeDir)) {
            mkdir($typeDir, 0755, true);
        }
        
        return $typeDir . '/' . md5($cacheId) . '.cache';
    }
}