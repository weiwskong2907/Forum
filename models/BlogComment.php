<?php
/**
 * Blog Comment model
 * 
 * Handles blog comment-related database operations
 */
class BlogComment {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get comment by ID
     * 
     * @param int $commentId Comment ID
     * @return array|null Comment data or null if not found
     */
    public function getById($commentId) {
        $query = "SELECT c.*, u.username 
                 FROM blog_comments c 
                 JOIN users u ON c.user_id = u.user_id 
                 WHERE c.comment_id = ? 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$commentId]);
    }
    
    /**
     * Get comments by post ID
     * 
     * @param int $postId Post ID
     * @param bool $approvedOnly Only get approved comments
     * @return array Comments
     */
    public function getByPostId($postId, $approvedOnly = true) {
        $query = "SELECT c.*, u.username, u.avatar 
                 FROM blog_comments c 
                 JOIN users u ON c.user_id = u.user_id 
                 WHERE c.post_id = ?";
        
        if ($approvedOnly) {
            $query .= " AND c.is_approved = 1";
        }
        
        $query .= " ORDER BY c.created_at ASC";
        
        return $this->db->fetchAll($query, [$postId]);
    }
    
    /**
     * Count comments by post ID
     * 
     * @param int $postId Post ID
     * @param bool $approvedOnly Only count approved comments
     * @return int Count
     */
    public function countByPostId($postId, $approvedOnly = true) {
        $query = "SELECT COUNT(*) as count FROM blog_comments WHERE post_id = ?";
        
        if ($approvedOnly) {
            $query .= " AND is_approved = 1";
        }
        
        $result = $this->db->fetchRow($query, [$postId]);
        
        return $result['count'];
    }
    
    /**
     * Get comments by user ID
     * 
     * @param int $userId User ID
     * @param int $limit Number of comments to return
     * @return array Comments with post information
     */
    public function getCommentsByUser($userId, $limit = 5) {
        $query = "SELECT c.*, p.title as post_title, p.slug as post_slug 
                 FROM blog_comments c 
                 JOIN blog_posts p ON c.post_id = p.post_id 
                 WHERE c.user_id = ? AND c.is_approved = 1 
                 ORDER BY c.created_at DESC 
                 LIMIT ?";
        
        return $this->db->fetchAll($query, [$userId, $limit]);
    }
    
    /**
     * Get all comments with pagination
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Comments
     */
    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT c.*, p.title as post_title, p.slug as post_slug, u.username 
                 FROM blog_comments c 
                 JOIN blog_posts p ON c.post_id = p.post_id 
                 JOIN users u ON c.user_id = u.user_id 
                 ORDER BY c.created_at DESC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$offset, $limit]);
    }
    
    /**
     * Get total count of comments
     * 
     * @return int Count
     */
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM blog_comments";
        $result = $this->db->fetchRow($query);
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Create a new comment
     * 
     * @param array $data Comment data
     * @return int|bool Comment ID or false on failure
     */
    public function create($data) {
        // Prepare comment data
        $commentData = [
            'post_id' => $data['post_id'],
            'user_id' => $data['user_id'],
            'content' => $data['content'],
            'is_approved' => $data['is_approved'] ?? 1
        ];
        
        // Insert comment
        $commentId = $this->db->insert('blog_comments', $commentData);
        
        if ($commentId) {
            // Update post's updated_at timestamp
            $this->db->query("UPDATE blog_posts SET updated_at = NOW() WHERE post_id = ?", [$data['post_id']]);
        }
        
        return $commentId;
    }
    
    /**
     * Update a comment
     * 
     * @param int $commentId Comment ID
     * @param array $data Comment data
     * @return bool Success or failure
     */
    public function update($commentId, $data) {
        return $this->db->update('blog_comments', $data, 'comment_id = ?', [$commentId]);
    }
    
    /**
     * Delete a comment
     * 
     * @param int $commentId Comment ID
     * @return bool Success or failure
     */
    public function delete($commentId) {
        // Get post ID before deleting
        $query = "SELECT post_id FROM blog_comments WHERE comment_id = ? LIMIT 1";
        $comment = $this->db->fetchRow($query, [$commentId]);
        
        if (!$comment) {
            return false;
        }
        
        $postId = $comment['post_id'];
        
        // Delete comment
        $result = $this->db->delete('blog_comments', 'comment_id = ?', [$commentId]);
        
        if ($result) {
            // Update post's updated_at timestamp
            $this->db->query("UPDATE blog_posts SET updated_at = NOW() WHERE post_id = ?", [$postId]);
        }
        
        return $result;
    }
    
    /**
     * Approve a comment
     * 
     * @param int $commentId Comment ID
     * @return bool Success or failure
     */
    public function approve($commentId) {
        return $this->update($commentId, ['is_approved' => 1]);
    }
    
    /**
     * Unapprove a comment
     * 
     * @param int $commentId Comment ID
     * @return bool Success or failure
     */
    public function unapprove($commentId) {
        return $this->update($commentId, ['is_approved' => 0]);
    }
    
    /**
     * Get recent comments
     * 
     * @param int $limit Limit
     * @param bool $approvedOnly Only get approved comments
     * @return array Comments
     */
    public function getRecent($limit = 5, $approvedOnly = true) {
        $query = "SELECT c.*, u.username, p.title as post_title, p.slug as post_slug 
                 FROM blog_comments c 
                 JOIN users u ON c.user_id = u.user_id 
                 JOIN blog_posts p ON c.post_id = p.post_id 
                 WHERE p.status = 'published'";
        
        if ($approvedOnly) {
            $query .= " AND c.is_approved = 1";
        }
        
        $query .= " ORDER BY c.created_at DESC LIMIT ?";
        
        return $this->db->fetchAll($query, [$limit]);
    }
}