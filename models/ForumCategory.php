<?php
/**
 * Forum Category model
 * 
 * Handles forum category-related database operations
 */
class ForumCategory {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get category by ID
     * 
     * @param int $categoryId Category ID
     * @return array|null Category data or null if not found
     */
    public function getById($categoryId) {
        $query = "SELECT * FROM forum_categories WHERE category_id = ? LIMIT 1";
        
        return $this->db->fetchRow($query, [$categoryId]);
    }
    
    /**
     * Get category by slug
     * 
     * @param string $slug Category slug
     * @return array|null Category data or null if not found
     */
    public function getBySlug($slug) {
        $query = "SELECT * FROM forum_categories WHERE slug = ? LIMIT 1";
        
        return $this->db->fetchRow($query, [$slug]);
    }
    
    /**
     * Get all categories with subforums
     * 
     * @return array Categories with subforums
     */
    public function getAllWithSubforums() {
        $query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM forum_subforums s WHERE s.category_id = c.category_id) as subforum_count 
                 FROM forum_categories c 
                 ORDER BY c.display_order ASC, c.name ASC";
        
        $categories = $this->db->fetchAll($query);
        
        // Get subforums for each category
        foreach ($categories as &$category) {
            $subforumQuery = "SELECT s.*, 
                            (SELECT COUNT(*) FROM forum_threads t WHERE t.subforum_id = s.subforum_id) as thread_count,
                            (SELECT COUNT(*) FROM forum_threads t JOIN forum_posts p ON t.thread_id = p.thread_id WHERE t.subforum_id = s.subforum_id) as post_count
                            FROM forum_subforums s 
                            WHERE s.category_id = ? 
                            ORDER BY s.display_order ASC, s.name ASC";
            
            $category['subforums'] = $this->db->fetchAll($subforumQuery, [$category['category_id']]);
        }
        
        return $categories;
    }
    
    /**
     * Create a new category
     * 
     * @param array $data Category data
     * @return int|bool Category ID or false on failure
     */
    public function create($data) {
        // Generate slug
        $slug = createSlug($data['name']);
        
        // Check if slug exists
        $query = "SELECT category_id FROM forum_categories WHERE slug = ? LIMIT 1";
        $existingCategory = $this->db->fetchRow($query, [$slug]);
        
        if ($existingCategory) {
            // Append a random string to make the slug unique
            $slug .= '-' . substr(md5(uniqid()), 0, 6);
        }
        
        // Prepare category data
        $categoryData = [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'display_order' => $data['display_order'] ?? 0
        ];
        
        // Insert category
        return $this->db->insert('forum_categories', $categoryData);
    }
    
    /**
     * Update a category
     * 
     * @param int $categoryId Category ID
     * @param array $data Category data
     * @return bool Success or failure
     */
    public function update($categoryId, $data) {
        // Get current category
        $query = "SELECT * FROM forum_categories WHERE category_id = ? LIMIT 1";
        $currentCategory = $this->db->fetchRow($query, [$categoryId]);
        
        if (!$currentCategory) {
            return false;
        }
        
        // Generate slug if name changed
        if (isset($data['name']) && $data['name'] !== $currentCategory['name']) {
            $slug = createSlug($data['name']);
            
            // Check if slug exists
            $query = "SELECT category_id FROM forum_categories WHERE slug = ? AND category_id != ? LIMIT 1";
            $existingCategory = $this->db->fetchRow($query, [$slug, $categoryId]);
            
            if ($existingCategory) {
                // Append a random string to make the slug unique
                $slug .= '-' . substr(md5(uniqid()), 0, 6);
            }
            
            $data['slug'] = $slug;
        }
        
        // Update category
        return $this->db->update('forum_categories', $data, 'category_id = ?', [$categoryId]);
    }
    
    /**
     * Delete a category
     * 
     * @param int $categoryId Category ID
     * @return bool Success or failure
     */
    public function delete($categoryId) {
        // Check if category has subforums
        $query = "SELECT COUNT(*) as count FROM forum_subforums WHERE category_id = ?";
        $result = $this->db->fetchRow($query, [$categoryId]);
        
        if ($result['count'] > 0) {
            return false; // Category has subforums, cannot delete
        }
        
        return $this->db->delete('forum_categories', 'category_id = ?', [$categoryId]);
    }
}