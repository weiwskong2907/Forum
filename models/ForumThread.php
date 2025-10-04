<?php
/**
 * Forum Thread model
 * 
 * Handles forum thread-related database operations
 */
class ForumThread {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get thread by ID
     * 
     * @param int $threadId Thread ID
     * @return array|null Thread data or null if not found
     */
    public function getById($threadId) {
        $query = "SELECT t.*, s.name as subforum_name, s.category_id, u.username 
                 FROM forum_threads t 
                 JOIN forum_subforums s ON t.subforum_id = s.subforum_id 
                 JOIN users u ON t.user_id = u.user_id 
                 WHERE t.thread_id = ? 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$threadId]);
    }
    
    /**
     * Get thread by slug
     * 
     * @param string $slug Thread slug
     * @return array|null Thread data or null if not found
     */
    public function getBySlug($slug) {
        $query = "SELECT t.*, s.name as subforum_name, s.category_id, u.username 
                 FROM forum_threads t 
                 JOIN forum_subforums s ON t.subforum_id = s.subforum_id 
                 JOIN users u ON t.user_id = u.user_id 
                 WHERE t.slug = ? 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$slug]);
    }
    
    /**
     * Get threads by subforum ID
     * 
     * @param int $subforumId Subforum ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Threads
     */
    public function getBySubforumId($subforumId, $limit = 20, $offset = 0) {
        $query = "SELECT t.*, u.username, 
                 (SELECT COUNT(*) FROM forum_posts WHERE thread_id = t.thread_id) as post_count,
                 (SELECT u2.username FROM forum_posts p2 JOIN users u2 ON p2.user_id = u2.user_id 
                  WHERE p2.thread_id = t.thread_id ORDER BY p2.created_at DESC LIMIT 1) as last_post_username
                 FROM forum_threads t 
                 JOIN users u ON t.user_id = u.user_id 
                 WHERE t.subforum_id = ? 
                 ORDER BY t.is_sticky DESC, t.last_post_at DESC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$subforumId, $offset, $limit]);
    }
    
    /**
     * Count threads by subforum ID
     * 
     * @param int $subforumId Subforum ID
     * @return int Count
     */
    public function countBySubforumId($subforumId) {
        $query = "SELECT COUNT(*) as count FROM forum_threads WHERE subforum_id = ?";
        $result = $this->db->fetchRow($query, [$subforumId]);
        
        return $result['count'];
    }
    
    /**
     * Get latest threads
     * 
     * @param int $limit Limit
     * @return array Threads
     */
    public function getLatest($limit = 5) {
        $query = "SELECT t.*, s.name as subforum_name, u.username 
                 FROM forum_threads t 
                 JOIN forum_subforums s ON t.subforum_id = s.subforum_id 
                 JOIN users u ON t.user_id = u.user_id 
                 ORDER BY t.last_post_at DESC 
                 LIMIT ?";
        
        return $this->db->fetchAll($query, [$limit]);
    }
    
    /**
     * Get threads by user ID
     * 
     * @param int $userId User ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Threads
     */
    public function getByUser($userId, $limit = 10, $offset = 0) {
        $query = "SELECT t.*, s.name as subforum_name 
                 FROM forum_threads t 
                 JOIN forum_subforums s ON t.subforum_id = s.subforum_id 
                 WHERE t.user_id = ? 
                 ORDER BY t.created_at DESC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$userId, $offset, $limit]);
    }
    
    /**
     * Count all threads
     * 
     * @return int Count
     */
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM forum_threads";
        $result = $this->db->fetchRow($query);
        
        return $result['count'];
    }
    
    /**
     * Create a new thread
     * 
     * @param array $data Thread data
     * @return int|bool Thread ID or false on failure
     */
    public function create($data) {
        // Generate slug
        $slug = createSlug($data['title']);
        
        // Check if slug exists
        $query = "SELECT thread_id FROM forum_threads WHERE slug = ? LIMIT 1";
        $existingThread = $this->db->fetchRow($query, [$slug]);
        
        if ($existingThread) {
            // Append a random string to make the slug unique
            $slug .= '-' . substr(md5(uniqid()), 0, 6);
        }
        
        // Prepare thread data
        $threadData = [
            'subforum_id' => $data['subforum_id'],
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'slug' => $slug,
            'is_sticky' => $data['is_sticky'] ?? 0,
            'is_locked' => $data['is_locked'] ?? 0
        ];
        
        // Insert thread
        $threadId = $this->db->insert('forum_threads', $threadData);
        
        if ($threadId && isset($data['content'])) {
            // Create first post
            $postData = [
                'thread_id' => $threadId,
                'user_id' => $data['user_id'],
                'content' => $data['content']
            ];
            
            $postModel = new ForumPost();
            $postModel->create($postData);
        }
        
        return $threadId;
    }
    
    /**
     * Update a thread
     * 
     * @param int $threadId Thread ID
     * @param array $data Thread data
     * @return bool Success or failure
     */
    public function update($threadId, $data) {
        // Get current thread
        $query = "SELECT * FROM forum_threads WHERE thread_id = ? LIMIT 1";
        $currentThread = $this->db->fetchRow($query, [$threadId]);
        
        if (!$currentThread) {
            return false;
        }
        
        // Generate slug if title changed
        if (isset($data['title']) && $data['title'] !== $currentThread['title']) {
            $slug = createSlug($data['title']);
            
            // Check if slug exists
            $query = "SELECT thread_id FROM forum_threads WHERE slug = ? AND thread_id != ? LIMIT 1";
            $existingThread = $this->db->fetchRow($query, [$slug, $threadId]);
            
            if ($existingThread) {
                // Append a random string to make the slug unique
                $slug .= '-' . substr(md5(uniqid()), 0, 6);
            }
            
            $data['slug'] = $slug;
        }
        
        // Update thread
        return $this->db->update('forum_threads', $data, 'thread_id = ?', [$threadId]);
    }
    
    /**
     * Delete a thread
     * 
     * @param int $threadId Thread ID
     * @return bool Success or failure
     */
    public function delete($threadId) {
        // Delete all posts in the thread first
        $this->db->delete('forum_posts', 'thread_id = ?', [$threadId]);
        
        // Delete thread
        return $this->db->delete('forum_threads', 'thread_id = ?', [$threadId]);
    }
    
    /**
     * Toggle sticky status
     * 
     * @param int $threadId Thread ID
     * @return bool Success
     */
    public function toggleSticky($threadId) {
        $query = "UPDATE forum_threads SET is_sticky = NOT is_sticky WHERE thread_id = ?";
        $stmt = $this->db->query($query, [$threadId]);
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Toggle locked status
     * 
     * @param int $threadId Thread ID
     * @return bool Success or failure
     */
    public function toggleLocked($threadId) {
        $query = "UPDATE forum_threads SET is_locked = NOT is_locked WHERE thread_id = ?";
        $stmt = $this->db->query($query, [$threadId]);
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Move thread to another subforum
     * 
     * @param int $threadId Thread ID
     * @param int $newSubforumId New subforum ID
     * @return bool Success or failure
     */
    public function moveThread($threadId, $newSubforumId) {
        $query = "UPDATE forum_threads SET subforum_id = ? WHERE thread_id = ?";
        $stmt = $this->db->query($query, [$newSubforumId, $threadId]);
        
        return $stmt->affected_rows > 0;
    }
    
    /**
     * Increment view count
     * 
     * @param int $threadId Thread ID
     * @return bool Success or failure
     */
    public function incrementViewCount($threadId) {
        try {
            // First, try to add the column if it doesn't exist
            $this->ensureViewCountColumnExists();
            
            // Then increment the view count
            $query = "UPDATE forum_threads SET view_count = view_count + 1 WHERE thread_id = ?";
            $stmt = $this->db->query($query, [$threadId]);
            
            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            // Log error and continue
            error_log("Error incrementing view count: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure view_count column exists in forum_threads table
     */
    private function ensureViewCountColumnExists() {
        try {
            $query = "ALTER TABLE forum_threads ADD COLUMN IF NOT EXISTS view_count INT UNSIGNED NOT NULL DEFAULT 0";
            $this->db->query($query);
        } catch (Exception $e) {
            // If the database doesn't support IF NOT EXISTS, try a different approach
            try {
                // Check if column exists
                $query = "SHOW COLUMNS FROM forum_threads LIKE 'view_count'";
                $result = $this->db->fetchAll($query);
                
                if (empty($result)) {
                    // Column doesn't exist, add it
                    $query = "ALTER TABLE forum_threads ADD COLUMN view_count INT UNSIGNED NOT NULL DEFAULT 0";
                    $this->db->query($query);
                }
            } catch (Exception $e2) {
                // Log error
                error_log("Error ensuring view_count column exists: " . $e2->getMessage());
                throw $e2;
            }
        }
    }
    
    /**
     * Get recent threads
     * 
     * @param int $limit Limit
     * @return array Threads
     */
    public function getRecent($limit = 5) {
        $query = "SELECT t.*, s.name as subforum_name, u.username 
                 FROM forum_threads t 
                 JOIN forum_subforums s ON t.subforum_id = s.subforum_id 
                 JOIN users u ON t.user_id = u.user_id 
                 ORDER BY t.created_at DESC 
                 LIMIT ?";
        
        return $this->db->fetchAll($query, [$limit]);
    }
    
    /**
     * Get total count of threads
     * 
     * @return int Count
     */
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM forum_threads";
        $result = $this->db->fetchRow($query);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get all threads with pagination
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Threads
     */
    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT t.*, s.name as subforum_name, u.username, 
                 (SELECT COUNT(*) FROM forum_posts WHERE thread_id = t.thread_id) as post_count
                 FROM forum_threads t 
                 JOIN forum_subforums s ON t.subforum_id = s.subforum_id 
                 JOIN users u ON t.user_id = u.user_id 
                 ORDER BY t.created_at DESC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$offset, $limit]);
    }
    
    /**
     * Search threads
     * 
     * @param string $query Search query
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Threads
     */
    public function search($query, $limit = 10, $offset = 0) {
        $searchQuery = "SELECT t.*, s.name as subforum_name, u.username 
                       FROM forum_threads t 
                       JOIN forum_subforums s ON t.subforum_id = s.subforum_id 
                       JOIN users u ON t.user_id = u.user_id 
                       WHERE t.title LIKE ? 
                       ORDER BY t.last_post_at DESC 
                       LIMIT ?, ?";
        
        return $this->db->fetchAll($searchQuery, ['%' . $query . '%', $offset, $limit]);
    }
    
    /**
     * Count search results
     * 
     * @param string $query Search query
     * @return int Count
     */
    public function countSearch($query) {
        $searchQuery = "SELECT COUNT(*) as count 
                       FROM forum_threads 
                       WHERE title LIKE ?";
        
        $result = $this->db->fetchRow($searchQuery, ['%' . $query . '%']);
        
        return $result['count'];
    }
    
    /**
     * Subscribe a user to a thread
     * 
     * @param int $userId User ID
     * @param int $threadId Thread ID
     * @return bool Success status
     */
    public function subscribeUser($userId, $threadId) {
        try {
            // Check if table exists
            $this->createSubscriptionsTableIfNotExists();
            
            // Check if already subscribed
            $checkQuery = "SELECT 1 FROM forum_subscriptions 
                          WHERE user_id = ? AND thread_id = ? 
                          LIMIT 1";
            
            $exists = $this->db->fetchRow($checkQuery, [$userId, $threadId]);
            
            if ($exists) {
                return true; // Already subscribed
            }
            
            // Insert new subscription
            $query = "INSERT INTO forum_subscriptions (user_id, thread_id, created_at) 
                     VALUES (?, ?, NOW())";
            
            $stmt = $this->db->query($query, [$userId, $threadId]);
            
            if ($stmt) {
                $stmt->close();
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            // Log error
            error_log("Error in subscribeUser: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create forum_subscriptions table if it doesn't exist
     */
    private function createSubscriptionsTableIfNotExists() {
        $sql = "
        CREATE TABLE IF NOT EXISTS `forum_subscriptions` (
          `subscription_id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` INT UNSIGNED NOT NULL,
          `thread_id` INT UNSIGNED NOT NULL,
          `created_at` datetime NOT NULL,
          PRIMARY KEY (`subscription_id`),
          UNIQUE KEY `user_thread_unique` (`user_id`, `thread_id`),
          KEY `user_id` (`user_id`),
          KEY `thread_id` (`thread_id`),
          CONSTRAINT `forum_subscriptions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
          CONSTRAINT `forum_subscriptions_ibfk_2` FOREIGN KEY (`thread_id`) REFERENCES `forum_threads` (`thread_id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $this->db->query($sql);
    }
    
    /**
     * Unsubscribe a user from a thread
     * 
     * @param int $userId User ID
     * @param int $threadId Thread ID
     * @return bool Success status
     */
    public function unsubscribeUser($userId, $threadId) {
        try {
            // Check if table exists
            $this->createSubscriptionsTableIfNotExists();
            
            $query = "DELETE FROM forum_subscriptions 
                     WHERE user_id = ? AND thread_id = ?";
            
            $stmt = $this->db->query($query, [$userId, $threadId]);
            
            if ($stmt) {
                $stmt->close();
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            // Log error
            error_log("Error in unsubscribeUser: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if a user is subscribed to a thread
     * 
     * @param int $userId User ID
     * @param int $threadId Thread ID
     * @return bool Is subscribed
     */
    public function isUserSubscribed($userId, $threadId) {
        try {
            // Check if table exists
            $this->createSubscriptionsTableIfNotExists();
            
            $query = "SELECT 1 FROM forum_subscriptions 
                     WHERE user_id = ? AND thread_id = ? 
                     LIMIT 1";
            
            $result = $this->db->fetchRow($query, [$userId, $threadId]);
            
            return !empty($result);
        } catch (Exception $e) {
            // Log error
            error_log("Error in isUserSubscribed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all subscriptions for a user
     * 
     * @param int $userId User ID
     * @return array Subscriptions
     */
    public function getUserSubscriptions($userId) {
        try {
            // Check if table exists
            $this->createSubscriptionsTableIfNotExists();
            
            $query = "SELECT s.*, t.title as thread_title, t.slug as thread_slug, 
                            t.last_post_at as last_activity, 
                            sf.name as subforum_name, sf.slug as subforum_slug
                     FROM forum_subscriptions s
                     JOIN forum_threads t ON s.thread_id = t.thread_id
                     JOIN forum_subforums sf ON t.subforum_id = sf.subforum_id
                     WHERE s.user_id = ?
                     ORDER BY t.last_post_at DESC";
            
            return $this->db->fetchAll($query, [$userId]);
        } catch (Exception $e) {
            // Log error
            error_log("Error in getUserSubscriptions: " . $e->getMessage());
            return [];
        }
    }
}