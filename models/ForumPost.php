<?php
/**
 * Forum Post model
 * 
 * Handles forum post-related database operations
 */
class ForumPost {
    private $db;
    private $cache;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
        $this->cache = new SystemCache();
    }
    
    /**
     * Get post by ID
     * 
     * @param int $postId Post ID
     * @return array|null Post data or null if not found
     */
    public function getById($postId) {
        $query = "SELECT p.*, u.username 
                 FROM forum_posts p 
                 JOIN users u ON p.user_id = u.user_id 
                 WHERE p.post_id = ? 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$postId]);
    }
    
    /**
     * Get posts by thread ID
     * 
     * @param int $threadId Thread ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Posts
     */
    public function getByThreadId($threadId, $limit = 10, $offset = 0) {
        // Create a cache key based on parameters
        $cacheKey = "thread_posts_{$threadId}_{$limit}_{$offset}";
        
        // Try to get from cache first
        $cachedResult = $this->cache->get($cacheKey, 'forum_posts');
        if ($cachedResult !== null) {
            return $cachedResult;
        }
        
        // If not in cache, query the database
        $query = "SELECT p.*, u.username, COALESCE(u.avatar, 'assets/default-avatar.svg') as avatar 
                 FROM forum_posts p 
                 JOIN users u ON p.user_id = u.user_id 
                 WHERE p.thread_id = ? 
                 ORDER BY p.created_at ASC 
                 LIMIT ?, ?";
        
        $result = $this->db->fetchAll($query, [$threadId, $offset, $limit]);
        
        // Store in cache for 5 minutes (300 seconds)
        $this->cache->set($cacheKey, $result, 'forum_posts', 300);
        
        return $result;
    }
    
    /**
     * Get first post by thread ID
     * 
     * @param int $threadId Thread ID
     * @return array|null First post data or null if not found
     */
    public function getFirstPostByThreadId($threadId) {
        $query = "SELECT p.*, u.username 
                 FROM forum_posts p 
                 JOIN users u ON p.user_id = u.user_id 
                 WHERE p.thread_id = ? 
                 ORDER BY p.created_at ASC 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$threadId]);
    }
    
    /**
     * Count posts by thread ID
     * 
     * @param int $threadId Thread ID
     * @return int Post count
     */
    public function countByThreadId($threadId) {
        // Create a cache key
        $cacheKey = "thread_post_count_{$threadId}";
        
        // Try to get from cache first
        $cachedCount = $this->cache->get($cacheKey, 'forum_counts');
        if ($cachedCount !== null) {
            return $cachedCount;
        }
        
        // If not in cache, query the database
        $query = "SELECT COUNT(*) as count 
                 FROM forum_posts 
                 WHERE thread_id = ?";
        
        $result = $this->db->fetchRow($query, [$threadId]);
        $count = $result['count'] ?? 0;
        
        // Store in cache for 5 minutes (300 seconds)
        $this->cache->set($cacheKey, $count, 'forum_counts', 300);
        
        return $count;
    }
    
    /**
     * Get post position in thread
     * 
     * @param int $threadId Thread ID
     * @param int $postId Post ID
     * @return int Position (1-based) or 0 if not found
     */
    public function getPostPosition($threadId, $postId) {
        $query = "SELECT COUNT(*) as position 
                 FROM forum_posts 
                 WHERE thread_id = ? 
                 AND created_at <= (
                     SELECT created_at 
                     FROM forum_posts 
                     WHERE post_id = ? AND thread_id = ?
                 )";
        
        $result = $this->db->fetchRow($query, [$threadId, $postId, $threadId]);
        return $result['position'] ?? 0;
    }
    
    /**
     * Search forum posts
     * 
     * @param string $query Search query
     * @param int $limit Result limit
     * @return array Search results
     */
    public function search($query, $limit = 10) {
        $searchTerm = '%' . $query . '%';
        
        $sql = "SELECT p.*, u.username, t.title as thread_title, t.slug as thread_slug
                FROM forum_posts p
                JOIN users u ON p.user_id = u.user_id
                JOIN forum_threads t ON p.thread_id = t.thread_id
                WHERE p.content LIKE ?
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$searchTerm, $limit]);
    }
    
    /**
     * Get total post count for a thread
     * 
     * @param int $threadId Thread ID
     * @return int Post count
     */
    public function getCountByThreadId($threadId) {
        $query = "SELECT COUNT(*) as count 
                 FROM forum_posts 
                 WHERE thread_id = ?";
        
        $result = $this->db->fetchRow($query, [$threadId]);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get posts by user ID
     * 
     * @param int $userId User ID
     * @param int $limit Number of posts to return
     * @return array Posts with thread information
     */
    public function getPostsByUser($userId, $limit = 5) {
        $query = "SELECT p.*, t.title as thread_title, t.slug as thread_slug 
                 FROM forum_posts p 
                 JOIN forum_threads t ON p.thread_id = t.thread_id 
                 WHERE p.user_id = ? 
                 ORDER BY p.created_at DESC 
                 LIMIT ?";
        
        return $this->db->fetchAll($query, [$userId, $limit]);
    }
    
    /**
     * Get all forum posts with pagination
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Posts
     */
    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT p.*, t.title as thread_title, t.slug as thread_slug, u.username 
                 FROM forum_posts p 
                 JOIN forum_threads t ON p.thread_id = t.thread_id 
                 JOIN users u ON p.user_id = u.user_id 
                 ORDER BY p.created_at DESC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$offset, $limit]);
    }
    
    /**
     * Get total count of forum posts
     * 
     * @return int Count
     */
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM forum_posts";
        $result = $this->db->fetchRow($query);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Create a new post
     * 
     * @param array $data Post data
     * @return int|bool Post ID or false on failure
     */
    public function create($data) {
        $query = "INSERT INTO forum_posts (thread_id, user_id, content, created_at) 
                 VALUES (?, ?, ?, NOW())";
        
        $stmt = $this->db->query($query, [
            $data['thread_id'],
            $data['user_id'],
            $data['content']
        ]);
        
        if ($stmt) {
            $postId = $this->db->lastInsertId();
            
            // Update thread's last post timestamp
            $threadQuery = "UPDATE forum_threads SET 
                           last_post_at = NOW(), 
                           post_count = post_count + 1 
                           WHERE thread_id = ?";
            
            $this->db->query($threadQuery, [$data['thread_id']]);
            
            // Send notifications to subscribers
            $this->notifySubscribers($data['thread_id'], $postId, $data['user_id']);
            
            // Clear cache for this thread
            $this->cache->clearByType('forum_posts');
            $this->cache->clearByType('forum_counts');
            
            return $postId;
        }
        
        return false;
    }
    
    /**
     * Notify subscribers of new post
     * 
     * @param int $threadId Thread ID
     * @param int $postId Post ID
     * @param int $postUserId User ID who created the post
     * @return void
     */
    private function notifySubscribers($threadId, $postId, $postUserId) {
        // Get thread information
        $threadQuery = "SELECT t.title, t.slug FROM forum_threads t WHERE t.thread_id = ?";
        $thread = $this->db->fetchRow($threadQuery, [$threadId]);
        
        if (!$thread) {
            return;
        }
        
        // Get all subscribers except the post author
        $subscribersQuery = "SELECT s.user_id, u.email, u.username 
                           FROM forum_subscriptions s 
                           JOIN users u ON s.user_id = u.user_id 
                           WHERE s.thread_id = ? AND s.user_id != ?";
        
        $subscribers = $this->db->fetchAll($subscribersQuery, [$threadId, $postUserId]);
        
        if (empty($subscribers)) {
            return;
        }
        
        // Create activity records for all subscribers
        $activityValues = [];
        $activityParams = [];
        
        foreach ($subscribers as $subscriber) {
            $activityValues[] = "(?, ?, ?, ?, NOW(), 0)";
            $activityParams[] = $subscriber['user_id'];
            $activityParams[] = 'reply';
            $activityParams[] = $postId;
            $activityParams[] = $threadId;
        }
        
        // Insert all activities in one query
        if (!empty($activityValues)) {
            $activityQuery = "INSERT INTO user_activities (user_id, activity_type, content_id, thread_id, created_at, is_read) VALUES " . 
                            implode(", ", $activityValues);
            
            $this->db->query($activityQuery, $activityParams);
        }
    }
    
    /**
     * Update a post
     * 
     * @param int $postId Post ID
     * @param array $data Post data
     * @return bool Success or failure
     */
    public function update($postId, $data) {
        return $this->db->update('forum_posts', $data, 'post_id = ?', [$postId]);
    }
    
    /**
     * Delete a post
     * 
     * @param int $postId Post ID
     * @return bool Success or failure
     */
    public function delete($postId) {
        // Get thread ID before deleting
        $query = "SELECT thread_id FROM forum_posts WHERE post_id = ? LIMIT 1";
        $post = $this->db->fetchRow($query, [$postId]);
        
        if (!$post) {
            return false;
        }
        
        $threadId = $post['thread_id'];
        
        // Delete post
        $result = $this->db->delete('forum_posts', 'post_id = ?', [$postId]);
        
        if ($result) {
            // Update thread's last_post_at timestamp to the most recent post
            $query = "SELECT MAX(created_at) as last_post_at FROM forum_posts WHERE thread_id = ?";
            $lastPost = $this->db->fetchRow($query, [$threadId]);
            
            if ($lastPost && $lastPost['last_post_at']) {
                $this->db->query("UPDATE forum_threads SET last_post_at = ? WHERE thread_id = ?", [$lastPost['last_post_at'], $threadId]);
            }
        }
        
        return $result;
    }
    
    /**
     * Get recent posts
     * 
     * @param int $limit Limit
     * @return array Posts
     */
    public function getRecent($limit = 5) {
        $query = "SELECT p.*, u.username, t.title as thread_title, t.slug as thread_slug 
                 FROM forum_posts p 
                 JOIN users u ON p.user_id = u.user_id 
                 JOIN forum_threads t ON p.thread_id = t.thread_id 
                 ORDER BY p.created_at DESC 
                 LIMIT ?";
        
        return $this->db->fetchAll($query, [$limit]);
    }
    
    /**
     * Get posts by user ID
     * 
     * @param int $userId User ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Posts
     */
    public function getByUserId($userId, $limit = 10, $offset = 0) {
        $query = "SELECT p.*, t.title as thread_title, t.slug as thread_slug 
                 FROM forum_posts p 
                 JOIN forum_threads t ON p.thread_id = t.thread_id 
                 WHERE p.user_id = ? 
                 ORDER BY p.created_at DESC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$userId, $offset, $limit]);
    }
    
    /**
     * Count posts by user ID
     * 
     * @param int $userId User ID
     * @return int Count
     */
    public function countByUserId($userId) {
        $query = "SELECT COUNT(*) as count FROM forum_posts WHERE user_id = ?";
        $result = $this->db->fetchRow($query, [$userId]);
        
        return $result['count'];
    }
    
    /**
     * Count all posts
     * 
     * @return int Count
     */
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM forum_posts";
        $result = $this->db->fetchRow($query);
        
        return $result['count'];
    }
}