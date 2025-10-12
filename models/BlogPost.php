<?php
/**
 * Blog Post model
 * 
 * Handles blog post-related database operations
 */
class BlogPost {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get blog post by ID
     * 
     * @param int $postId Post ID
     * @return array|null Post data or null if not found
     */
    public function getById($postId) {
        $query = "SELECT p.*, c.name as category_name, u.username 
                 FROM blog_posts p 
                 JOIN blog_categories c ON p.category_id = c.category_id 
                 JOIN users u ON p.user_id = u.user_id 
                 WHERE p.post_id = ? 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$postId]);
    }
    
    /**
     * Get blog post by slug
     * 
     * @param string $slug Post slug
     * @return array|null Post data or null if not found
     */
    public function getBySlug($slug) {
        $query = "SELECT p.*, c.name as category_name, u.username 
                 FROM blog_posts p 
                 JOIN blog_categories c ON p.category_id = c.category_id 
                 JOIN users u ON p.user_id = u.user_id 
                 WHERE (p.slug = ? OR p.custom_slug = ?) AND p.status = 'published' 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$slug, $slug]);
    }
    
    /**
     * Get all blog posts
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     * @param string $orderBy Order by field
     * @param string $order Order direction
     * @return array Posts
     */
    public function getAll($limit = 10, $offset = 0, $orderBy = 'published_at', $order = 'DESC', $includeAll = false) {
        $whereClause = $includeAll ? "" : "WHERE p.status = 'published'";
        
        $query = "SELECT p.*, c.name as category_name, u.username 
                 FROM blog_posts p 
                 JOIN blog_categories c ON p.category_id = c.category_id 
                 JOIN users u ON p.user_id = u.user_id 
                 {$whereClause} 
                 ORDER BY p.{$orderBy} {$order} 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$offset, $limit]);
    }
    
    /**
     * Get latest blog posts
     * 
     * @param int $limit Limit
     * @return array Posts
     */
    public function getLatest($limit = 5) {
        return $this->getAll($limit);
    }
    
    /**
     * Get recent blog posts
     * 
     * @param int $limit Limit
     * @return array Posts
     */
    public function getRecent($limit = 5) {
        return $this->getAll($limit);
    }
    
    /**
     * Get total count of blog posts
     * 
     * @return int Count
     */
    public function getTotalCount() {
        $query = "SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'";
        $result = $this->db->fetchRow($query);
        return $result ? (int)$result['count'] : 0;
    }
        
    /**
     * Update featured image for a blog post
     * 
     * @param int $postId Post ID
     * @param string $featuredImage Featured image path
     * @return bool Success or failure
     */
    public function updateFeaturedImage($postId, $featuredImage) {
        $query = "UPDATE blog_posts SET featured_image = ? WHERE post_id = ?";
        $stmt = $this->db->query($query, [$featuredImage, $postId]);
        $result = $stmt->affected_rows > 0;
        $stmt->close();
        return $result;
    }
    
    /**
     * Check if a slug already exists
     * 
     * @param string $slug Slug to check
     * @return bool True if exists, false otherwise
     */
    public function slugExists($slug) {
        $query = "SELECT COUNT(*) as count FROM blog_posts WHERE slug = ?";
        $result = $this->db->fetchRow($query, [$slug]);
        return $result && (int)$result['count'] > 0;
    }
    
    /**
     * Check if a slug already exists (excluding a specific post)
     * 
     * @param string $slug Slug to check
     * @param int $postId Post ID to exclude
     * @return bool True if exists, false otherwise
     */
    public function slugExistsExcept($slug, $postId) {
        $query = "SELECT COUNT(*) as count FROM blog_posts WHERE slug = ? AND post_id != ?";
        $result = $this->db->fetchRow($query, [$slug, $postId]);
        return $result && (int)$result['count'] > 0;
    }
    

    
    /**
     * Get blog posts by category
     * 
     * @param int $categoryId Category ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Posts
     */
    public function getByCategory($categoryId, $limit = 10, $offset = 0) {
        $query = "SELECT p.*, c.name as category_name, u.username 
                 FROM blog_posts p 
                 JOIN blog_categories c ON p.category_id = c.category_id 
                 JOIN users u ON p.user_id = u.user_id 
                 WHERE p.category_id = ? AND p.status = 'published' 
                 ORDER BY p.published_at DESC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$categoryId, $offset, $limit]);
    }
    
    /**
     * Get blog posts by user
     * 
     * @param int $userId User ID
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Posts
     */
    public function getByUser($userId, $limit = 10, $offset = 0) {
        $query = "SELECT p.*, c.name as category_name, u.username 
                 FROM blog_posts p 
                 JOIN blog_categories c ON p.category_id = c.category_id 
                 JOIN users u ON p.user_id = u.user_id 
                 WHERE p.user_id = ? AND p.status = 'published' 
                 ORDER BY p.published_at DESC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$userId, $offset, $limit]);
    }
    
    /**
     * Count all blog posts
     * 
     * @return int Count
     */
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM blog_posts WHERE status = 'published'";
        $result = $this->db->fetchRow($query);
        
        return $result['count'];
    }
    
    /**
     * Count blog posts by category
     * 
     * @param int $categoryId Category ID
     * @return int Count
     */
    public function countByCategory($categoryId) {
        $query = "SELECT COUNT(*) as count FROM blog_posts WHERE category_id = ? AND status = 'published'";
        $result = $this->db->fetchRow($query, [$categoryId]);
        
        return $result['count'];
    }
    
    /**
     * Count blog posts by user
     * 
     * @param int $userId User ID
     * @return int Count
     */
    public function countByUser($userId) {
        $query = "SELECT COUNT(*) as count FROM blog_posts WHERE user_id = ? AND status = 'published'";
        $result = $this->db->fetchRow($query, [$userId]);
        
        return $result['count'];
    }
    
    /**
     * Create a new blog post
     * 
     * @param array $data Post data
     * @return int|bool Post ID or false on failure
     */
    public function create($data) {
        // Generate slug
        $slug = createSlug($data['title']);
        
        // Check if slug exists
        $query = "SELECT post_id FROM blog_posts WHERE slug = ? LIMIT 1";
        $existingPost = $this->db->fetchRow($query, [$slug]);
        
        if ($existingPost) {
            // Append a random string to make the slug unique
            $slug .= '-' . substr(md5(uniqid()), 0, 6);
        }
        
        // Generate excerpt if not provided
        if (!isset($data['excerpt']) || empty($data['excerpt'])) {
            $data['excerpt'] = truncateText(strip_tags($data['content']), 150);
        }
        
        // Set published_at if status is published
        if (isset($data['status']) && $data['status'] === 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        
        // Prepare post data
        $postData = [
            'user_id' => $data['user_id'],
            'category_id' => $data['category_id'],
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'],
            'excerpt' => $data['excerpt'],
            'status' => $data['status'] ?? 'draft'
        ];
        
        if (isset($data['published_at'])) {
            $postData['published_at'] = $data['published_at'];
        }
        
        // Insert post
        return $this->db->insert('blog_posts', $postData);
    }
    
    /**
     * Update a blog post
     * 
     * @param int $postId Post ID
     * @param array $data Post data
     * @return bool Success or failure
     */
    public function update($postId, $data) {
        // Get current post
        $query = "SELECT * FROM blog_posts WHERE post_id = ? LIMIT 1";
        $currentPost = $this->db->fetchRow($query, [$postId]);
        
        if (!$currentPost) {
            return false;
        }
        
        // Generate slug if title changed
        if (isset($data['title']) && $data['title'] !== $currentPost['title']) {
            $slug = createSlug($data['title']);
            
            // Check if slug exists
            $query = "SELECT post_id FROM blog_posts WHERE slug = ? AND post_id != ? LIMIT 1";
            $existingPost = $this->db->fetchRow($query, [$slug, $postId]);
            
            if ($existingPost) {
                // Append a random string to make the slug unique
                $slug .= '-' . substr(md5(uniqid()), 0, 6);
            }
            
            $data['slug'] = $slug;
        }
        
        // Generate excerpt if content changed and excerpt not provided
        if (isset($data['content']) && (!isset($data['excerpt']) || empty($data['excerpt']))) {
            $data['excerpt'] = truncateText(strip_tags($data['content']), 150);
        }
        
        // Set published_at if status changed to published
        if (isset($data['status']) && $data['status'] === 'published' && $currentPost['status'] !== 'published') {
            $data['published_at'] = date('Y-m-d H:i:s');
        }
        
        // Update post
        return $this->db->update('blog_posts', $data, 'post_id = ?', [$postId]);
    }
    
    /**
     * Delete a blog post
     * 
     * @param int $postId Post ID
     * @return bool Success or failure
     */
    public function delete($postId) {
        return $this->db->delete('blog_posts', 'post_id = ?', [$postId]);
    }
    
    /**
     * Search blog posts
     * 
     * @param string $query Search query
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Posts
     */
    public function search($query, $limit = 10, $offset = 0) {
        $searchQuery = "SELECT p.*, c.name as category_name, u.username 
                       FROM blog_posts p 
                       JOIN blog_categories c ON p.category_id = c.category_id 
                       JOIN users u ON p.user_id = u.user_id 
                       WHERE p.status = 'published' AND p.title LIKE ? 
                       ORDER BY p.published_at DESC 
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
                       FROM blog_posts 
                       WHERE status = 'published' AND title LIKE ?";
        
        $result = $this->db->fetchRow($searchQuery, ['%' . $query . '%']);
        
        return $result['count'];
    }
}