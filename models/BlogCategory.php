<?php
/**
 * Blog Category model
 * 
 * Handles blog category-related database operations
 */
class BlogCategory {
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
        $query = "SELECT * FROM blog_categories WHERE category_id = ? LIMIT 1";
        
        return $this->db->fetchRow($query, [$categoryId]);
    }
    
    /**
     * Get category by slug
     * 
     * @param string $slug Category slug
     * @return array|null Category data or null if not found
     */
    public function getBySlug($slug) {
        $query = "SELECT * FROM blog_categories WHERE slug = ? LIMIT 1";
        
        return $this->db->fetchRow($query, [$slug]);
    }
    
    /**
     * Get all categories
     * 
     * @return array Categories
     */
    public function getAll() {
        $query = "SELECT c.*, COUNT(p.post_id) as post_count 
                 FROM blog_categories c 
                 LEFT JOIN blog_posts p ON c.category_id = p.category_id AND p.status = 'published' 
                 GROUP BY c.category_id 
                 ORDER BY c.name ASC";
        
        return $this->db->fetchAll($query);
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
        $query = "SELECT category_id FROM blog_categories WHERE slug = ? LIMIT 1";
        $existingCategory = $this->db->fetchRow($query, [$slug]);
        
        if ($existingCategory) {
            // Append a random string to make the slug unique
            $slug .= '-' . substr(md5(uniqid()), 0, 6);
        }
        
        // Prepare category data
        $categoryData = [
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null
        ];
        
        // Insert category
        return $this->db->insert('blog_categories', $categoryData);
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
        $query = "SELECT * FROM blog_categories WHERE category_id = ? LIMIT 1";
        $currentCategory = $this->db->fetchRow($query, [$categoryId]);
        
        if (!$currentCategory) {
            return false;
        }
        
        // Generate slug if name changed
        if (isset($data['name']) && $data['name'] !== $currentCategory['name']) {
            $slug = createSlug($data['name']);
            
            // Check if slug exists
            $query = "SELECT category_id FROM blog_categories WHERE slug = ? AND category_id != ? LIMIT 1";
            $existingCategory = $this->db->fetchRow($query, [$slug, $categoryId]);
            
            if ($existingCategory) {
                // Append a random string to make the slug unique
                $slug .= '-' . substr(md5(uniqid()), 0, 6);
            }
            
            $data['slug'] = $slug;
        }
        
        // Update category
        return $this->db->update('blog_categories', $data, 'category_id = ?', [$categoryId]);
    }
    
    /**
     * Delete a category
     * 
     * @param int $categoryId Category ID
     * @return bool Success or failure
     */
    public function delete($categoryId) {
        // Check if category has posts
        $query = "SELECT COUNT(*) as count FROM blog_posts WHERE category_id = ?";
        $result = $this->db->fetchRow($query, [$categoryId]);
        
        if ($result['count'] > 0) {
            return false; // Category has posts, cannot delete
        }
        
        return $this->db->delete('blog_categories', 'category_id = ?', [$categoryId]);
    }
}