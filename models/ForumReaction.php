<?php
/**
 * Forum Reaction Model
 * 
 * Handles reactions to forum posts (like, heart, etc.)
 */
class ForumReaction {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->createTableIfNotExists();
    }
    
    /**
     * Create the forum_reactions table if it doesn't exist
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS forum_reactions (
            reaction_id INT AUTO_INCREMENT PRIMARY KEY,
            post_id INT NOT NULL,
            user_id INT NOT NULL,
            reaction_type VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_reaction (post_id, user_id, reaction_type),
            FOREIGN KEY (post_id) REFERENCES forum_posts(post_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )";
        
        $this->db->query($sql);
    }
    
    /**
     * Add or toggle a reaction to a post
     * 
     * @param int $postId
     * @param int $userId
     * @param string $reactionType
     * @return bool
     */
    public function toggleReaction($postId, $userId, $reactionType) {
        // Check if reaction already exists
        $existingReaction = $this->getUserReaction($postId, $userId, $reactionType);
        
        if ($existingReaction) {
            // Remove the reaction if it already exists
            return $this->removeReaction($postId, $userId, $reactionType);
        } else {
            // Add the reaction
            return $this->addReaction($postId, $userId, $reactionType);
        }
    }
    
    /**
     * Add a reaction to a post
     * 
     * @param int $postId
     * @param int $userId
     * @param string $reactionType
     * @return bool
     */
    private function addReaction($postId, $userId, $reactionType) {
        $sql = "INSERT INTO forum_reactions (post_id, user_id, reaction_type) 
                VALUES (?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iis', $postId, $userId, $reactionType);
        
        return $stmt->execute();
    }
    
    /**
     * Remove a reaction from a post
     * 
     * @param int $postId
     * @param int $userId
     * @param string $reactionType
     * @return bool
     */
    private function removeReaction($postId, $userId, $reactionType) {
        $sql = "DELETE FROM forum_reactions 
                WHERE post_id = ? AND user_id = ? AND reaction_type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iis', $postId, $userId, $reactionType);
        
        return $stmt->execute();
    }
    
    /**
     * Get a user's reaction to a post
     * 
     * @param int $postId
     * @param int $userId
     * @param string $reactionType
     * @return array|null
     */
    public function getUserReaction($postId, $userId, $reactionType) {
        $sql = "SELECT * FROM forum_reactions 
                WHERE post_id = ? AND user_id = ? AND reaction_type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iis', $postId, $userId, $reactionType);
        $stmt->execute();
        
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get all reactions for a post
     * 
     * @param int $postId
     * @return array
     */
    public function getReactionsByPostId($postId) {
        $sql = "SELECT reaction_type, COUNT(*) as count 
                FROM forum_reactions 
                WHERE post_id = ? 
                GROUP BY reaction_type";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $reactions = [];
        
        while ($row = $result->fetch_assoc()) {
            $reactions[$row['reaction_type']] = $row['count'];
        }
        
        return $reactions;
    }
    
    /**
     * Get users who reacted to a post
     * 
     * @param int $postId
     * @param string $reactionType
     * @return array
     */
    public function getUsersByReaction($postId, $reactionType) {
        $sql = "SELECT u.user_id, u.username, u.avatar 
                FROM forum_reactions r
                JOIN users u ON r.user_id = u.user_id
                WHERE r.post_id = ? AND r.reaction_type = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('is', $postId, $reactionType);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    /**
     * Check if a user has reacted to a post
     * 
     * @param int $postId
     * @param int $userId
     * @return array
     */
    public function getUserReactions($postId, $userId) {
        $sql = "SELECT reaction_type 
                FROM forum_reactions 
                WHERE post_id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $postId, $userId);
        $stmt->execute();
        
        $result = $stmt->get_result();
        $reactions = [];
        
        while ($row = $result->fetch_assoc()) {
            $reactions[] = $row['reaction_type'];
        }
        
        return $reactions;
    }
}