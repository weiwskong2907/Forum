<?php
/**
 * Forum Subforum model
 * 
 * Handles forum subforum-related database operations
 */
class ForumSubforum {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get subforum by ID
     * 
     * @param int $subforumId Subforum ID
     * @return array|null Subforum data or null if not found
     */
    public function getById($subforumId) {
        $query = "SELECT s.*, c.name as category_name 
                 FROM forum_subforums s 
                 JOIN forum_categories c ON s.category_id = c.category_id 
                 WHERE s.subforum_id = ? 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$subforumId]);
    }
    
    /**
     * Get subforum by slug
     * 
     * @param string $slug Subforum slug
     * @return array|null Subforum data or null if not found
     */
    public function getBySlug($slug) {
        $query = "SELECT s.*, c.name as category_name 
                 FROM forum_subforums s 
                 JOIN forum_categories c ON s.category_id = c.category_id 
                 WHERE s.slug = ? 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$slug]);
    }
    
    /**
     * Get all subforums
     * 
     * @return array All subforums
     */

    
    /**
     * Get subforums by category ID
     * 
     * @param int $categoryId Category ID
     * @return array Subforums
     */
    public function getByCategoryId($categoryId) {
        $query = "SELECT s.*, 
                 (SELECT COUNT(*) FROM forum_threads t WHERE t.subforum_id = s.subforum_id) as thread_count,
                 (SELECT COUNT(*) FROM forum_threads t JOIN forum_posts p ON t.thread_id = p.thread_id WHERE t.subforum_id = s.subforum_id) as post_count
                 FROM forum_subforums s 
                 WHERE s.category_id = ? 
                 ORDER BY s.display_order ASC, s.name ASC";
        
        return $this->db->fetchAll($query, [$categoryId]);
    }
    
    /**
     * Get all subforums
     * 
     * @return array Subforums
     */
    public function getAll() {
        $query = "SELECT s.*, c.name as category_name 
                 FROM forum_subforums s 
                 JOIN forum_categories c ON s.category_id = c.category_id 
                 ORDER BY c.display_order ASC, s.display_order ASC, s.name ASC";
        
        return $this->db->fetchAll($query);
    }
    
    /**
     * Create a new subforum
     * 
     * @param array $data Subforum data
     * @return int|bool Subforum ID or false on failure
     */
    public function create($data) {
        // Generate slug
        $slug = createSlug($data['name']);
        
        // Check if slug exists
        $query = "SELECT subforum_id FROM forum_subforums WHERE slug = ? LIMIT 1";
        $existingSubforum = $this->db->fetchRow($query, [$slug]);
        
        if ($existingSubforum) {
            // Append a random string to make the slug unique
            $slug .= '-' . substr(md5(uniqid()), 0, 6);
        }
        
        // Prepare subforum data
        $subforumData = [
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'display_order' => $data['display_order'] ?? 0
        ];
        
        // Insert subforum
        return $this->db->insert('forum_subforums', $subforumData);
    }
    
    /**
     * Update a subforum
     * 
     * @param int $subforumId Subforum ID
     * @param array $data Subforum data
     * @return bool Success or failure
     */
    public function update($subforumId, $data) {
        // Get current subforum
        $query = "SELECT * FROM forum_subforums WHERE subforum_id = ? LIMIT 1";
        $currentSubforum = $this->db->fetchRow($query, [$subforumId]);
        
        if (!$currentSubforum) {
            return false;
        }
        
        // Generate slug if name changed
        if (isset($data['name']) && $data['name'] !== $currentSubforum['name']) {
            $slug = createSlug($data['name']);
            
            // Check if slug exists
            $query = "SELECT subforum_id FROM forum_subforums WHERE slug = ? AND subforum_id != ? LIMIT 1";
            $existingSubforum = $this->db->fetchRow($query, [$slug, $subforumId]);
            
            if ($existingSubforum) {
                // Append a random string to make the slug unique
                $slug .= '-' . substr(md5(uniqid()), 0, 6);
            }
            
            $data['slug'] = $slug;
        }
        
        // Update subforum
        return $this->db->update('forum_subforums', $data, 'subforum_id = ?', [$subforumId]);
    }
    
    /**
     * Delete a subforum
     * 
     * @param int $subforumId Subforum ID
     * @return bool Success or failure
     */
    public function delete($subforumId) {
        // Check if subforum has threads
        $query = "SELECT COUNT(*) as count FROM forum_threads WHERE subforum_id = ?";
        $result = $this->db->fetchRow($query, [$subforumId]);
        
        if ($result['count'] > 0) {
            return false; // Subforum has threads, cannot delete
        }
        
        return $this->db->delete('forum_subforums', 'subforum_id = ?', [$subforumId]);
    }
    
    /**
     * Get subforum stats
     * 
     * @param int $subforumId Subforum ID
     * @return array Stats
     */
    public function getStats($subforumId) {
        $stats = [
            'thread_count' => 0,
            'post_count' => 0,
            'last_post' => null
        ];
        
        // Get thread count
        $query = "SELECT COUNT(*) as count FROM forum_threads WHERE subforum_id = ?";
        $result = $this->db->fetchRow($query, [$subforumId]);
        $stats['thread_count'] = $result['count'];
        
        // Get post count
        $query = "SELECT COUNT(*) as count FROM forum_posts p JOIN forum_threads t ON p.thread_id = t.thread_id WHERE t.subforum_id = ?";
        $result = $this->db->fetchRow($query, [$subforumId]);
        $stats['post_count'] = $result['count'];
        
        // Get last post
        $query = "SELECT p.*, t.title as thread_title, t.slug as thread_slug, u.username 
                 FROM forum_posts p 
                 JOIN forum_threads t ON p.thread_id = t.thread_id 
                 JOIN users u ON p.user_id = u.user_id 
                 WHERE t.subforum_id = ? 
                 ORDER BY p.created_at DESC 
                 LIMIT 1";
        
        $stats['last_post'] = $this->db->fetchRow($query, [$subforumId]);
        
        return $stats;
    }
}