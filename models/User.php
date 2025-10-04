<?php
/**
 * User model
 * 
 * Handles user-related database operations
 */
class User {
    private $db;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId User ID
     * @return array|null User data or null if not found
     */
    public function getById($userId) {
        $query = "SELECT u.user_id, u.username, u.email, u.role, u.created_at, p.avatar, p.bio, p.website 
                 FROM users u 
                 LEFT JOIN user_profiles p ON u.user_id = p.user_id 
                 WHERE u.user_id = ? AND u.is_active = 1 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$userId]);
    }
    
    /**
     * Get user by username
     * 
     * @param string $username Username
     * @return array|null User data or null if not found
     */
    public function getByUsername($username) {
        $query = "SELECT u.user_id, u.username, u.email, u.role, u.created_at, p.avatar, p.bio, p.website 
                 FROM users u 
                 LEFT JOIN user_profiles p ON u.user_id = p.user_id 
                 WHERE u.username = ? AND u.is_active = 1 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$username]);
    }
    
    /**
     * Get user by email
     * 
     * @param string $email Email
     * @return array|null User data or null if not found
     */
    public function getByEmail($email) {
        $query = "SELECT u.user_id, u.username, u.email, u.role, u.created_at, p.avatar, p.bio, p.website 
                 FROM users u 
                 LEFT JOIN user_profiles p ON u.user_id = p.user_id 
                 WHERE u.email = ? AND u.is_active = 1 
                 LIMIT 1";
        
        return $this->db->fetchRow($query, [$email]);
    }
    
    /**
     * Update user profile
     * 
     * @param int $userId User ID
     * @param array $data Profile data
     * @return bool Success or failure
     */
    public function updateProfile($userId, $data) {
        // Update user data
        $userData = [];
        
        if (isset($data['username'])) {
            $userData['username'] = $data['username'];
        }
        
        if (isset($data['email'])) {
            $userData['email'] = $data['email'];
        }
        
        if (!empty($userData)) {
            $this->db->update('users', $userData, 'user_id = ?', [$userId]);
        }
        
        // Update profile data
        $profileData = [];
        
        if (isset($data['avatar'])) {
            $profileData['avatar'] = $data['avatar'];
        }
        
        if (isset($data['bio'])) {
            $profileData['bio'] = $data['bio'];
        }
        
        if (isset($data['website'])) {
            $profileData['website'] = $data['website'];
        }
        
        if (!empty($profileData)) {
            // Check if profile exists
            $query = "SELECT profile_id FROM user_profiles WHERE user_id = ? LIMIT 1";
            $profile = $this->db->fetchRow($query, [$userId]);
            
            if ($profile) {
                return $this->db->update('user_profiles', $profileData, 'user_id = ?', [$userId]);
            } else {
                $profileData['user_id'] = $userId;
                return $this->db->insert('user_profiles', $profileData);
            }
        }
        
        return true;
    }
    
    /**
     * Get all users
     * 
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Users
     */
    public function getAll($limit = 10, $offset = 0) {
        $query = "SELECT u.user_id, u.username, u.email, u.role, u.created_at, u.last_login 
                 FROM users u 
                 WHERE u.is_active = 1 
                 ORDER BY u.created_at DESC 
                 LIMIT ?, ?";
        
        return $this->db->fetchAll($query, [$offset, $limit]);
    }
    
    /**
     * Count all users
     * 
     * @return int Count
     */
    public function countAll() {
        $query = "SELECT COUNT(*) as count FROM users WHERE is_active = 1";
        $result = $this->db->fetchRow($query);
        
        return $result['count'];
    }
    
    /**
     * Deactivate user
     * 
     * @param int $userId User ID
     * @return bool Success or failure
     */
    public function deactivate($userId) {
        return $this->db->update('users', ['is_active' => 0], 'user_id = ?', [$userId]);
    }
    
    /**
     * Activate user
     * 
     * @param int $userId User ID
     * @return bool Success or failure
     */
    public function activate($userId) {
        return $this->db->update('users', ['is_active' => 1], 'user_id = ?', [$userId]);
    }
    
    /**
     * Change user role
     * 
     * @param int $userId User ID
     * @param string $role New role
     * @return bool Success or failure
     */
    public function changeRole($userId, $role) {
        if (!in_array($role, ['user', 'admin', 'super_admin'])) {
            return false;
        }
        
        return $this->db->update('users', ['role' => $role], 'user_id = ?', [$userId]);
    }
    
    /**
     * Get latest registered user
     * 
     * @return array|null User data or null if not found
     */
    public function getLatest() {
        $query = "SELECT u.user_id, u.username, u.email, u.role, u.created_at 
                 FROM users u 
                 WHERE u.is_active = 1 
                 ORDER BY u.created_at DESC 
                 LIMIT 1";
        
        return $this->db->fetchRow($query);
    }
}