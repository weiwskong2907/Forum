<?php
/**
 * Forum Post model
 * 
 * Handles forum post-related database operations
 */
class ForumPost {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
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
        $query = "SELECT p.*, u.username, COALESCE(u.avatar, 'assets/default-avatar.svg') as avatar 
                 FROM forum_posts p 
                 JOIN users u ON p.user_id = u.user_id 
                 WHERE p.thread_id = ? 
                 ORDER BY p.created_at ASC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$threadId, $offset, $limit]);
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
        $query = "SELECT COUNT(*) as count 
                 FROM forum_posts 
                 WHERE thread_id = ?";
        
        $result = $this->db->fetchRow($query, [$threadId]);
        return $result['count'] ?? 0;
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
        // Prepare post data
        $postData = [
            'thread_id' => $data['thread_id'],
            'user_id' => $data['user_id'],
            'content' => $data['content']
        ];
        
        // Insert post
        $postId = $this->db->insert('forum_posts', $postData);
        
        if ($postId) {
            // Update thread's last_post_at timestamp
            $this->db->query("UPDATE forum_threads SET last_post_at = NOW() WHERE thread_id = ?", [$data['thread_id']]);
        }
        
        return $postId;
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