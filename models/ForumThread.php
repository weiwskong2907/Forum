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
     * @return bool Success or failure
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
     * Increment view count
     * 
     * @param int $threadId Thread ID
     * @return bool Success or failure
     */
    public function incrementViewCount($threadId) {
        $query = "UPDATE forum_threads SET view_count = view_count + 1 WHERE thread_id = ?";
        $stmt = $this->db->query($query, [$threadId]);
        
        return $stmt->affected_rows > 0;
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
}