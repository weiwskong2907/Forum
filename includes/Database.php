<?php
/**
 * Database connection class
 * 
 * Handles database connections and provides methods for common database operations
 */
class Database {
    private $connection;
    private static $instance = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get database instance (Singleton pattern)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Connect to the database
     * 
     * @return void
     */
    private function connect() {
        try {
            $this->connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->connection->connect_error) {
                throw new Exception("Database connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset to UTF-8
            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    /**
     * Get the database connection
     * 
     * @return mysqli
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return mysqli_stmt|bool
     */
    public function query($query, $params = []) {
        $stmt = $this->connection->prepare($query);
        
        if (!$stmt) {
            die("Query preparation failed: " . $this->connection->error);
        }
        
        if (!empty($params)) {
            $types = '';
            $bindParams = [];
            
            // Build the types string and parameters array
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b';
                }
                $bindParams[] = $param;
            }
            
            // Add types as the first element
            array_unshift($bindParams, $types);
            
            // Bind parameters dynamically
            call_user_func_array([$stmt, 'bind_param'], $this->refValues($bindParams));
        }
        
        $stmt->execute();
        
        return $stmt;
    }
    
    /**
     * Helper function to convert parameters to references for bind_param
     * 
     * @param array $arr
     * @return array
     */
    private function refValues($arr) {
        $refs = [];
        foreach ($arr as $key => $value) {
            $refs[$key] = &$arr[$key];
        }
        return $refs;
    }
    
    /**
     * Fetch a single row
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return array|null
     */
    public function fetchRow($query, $params = []) {
        $stmt = $this->query($query, $params);
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    }
    
    /**
     * Fetch multiple rows
     * 
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return array
     */
    public function fetchAll($query, $params = []) {
        $stmt = $this->query($query, $params);
        $result = $stmt->get_result();
        $rows = [];
        
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        $stmt->close();
        
        return $rows;
    }
    
    /**
     * Insert data into a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int|bool Last insert ID or false on failure
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->query($query, array_values($data));
        
        if ($stmt) {
            $insertId = $this->connection->insert_id;
            $stmt->close();
            return $insertId;
        }
        
        return false;
    }
    
    /**
     * Update data in a table
     * 
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @param string $where Where clause
     * @param array $whereParams Parameters for where clause
     * @return bool Success or failure
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
        }
        
        $setClause = implode(', ', $set);
        $query = "UPDATE {$table} SET {$setClause} WHERE {$where}";
        
        // Combine data values and where parameters
        $params = array_merge(array_values($data), $whereParams);
        
        $stmt = $this->query($query, $params);
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $affected > 0;
    }
    
    /**
     * Delete data from a table
     * 
     * @param string $table Table name
     * @param string $where Where clause
     * @param array $params Parameters for where clause
     * @return bool Success or failure
     */
    public function delete($table, $where, $params = []) {
        $query = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($query, $params);
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $affected > 0;
    }
    
    /**
     * Close the database connection
     * 
     * @return void
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    /**
     * Destructor to close the connection
     */
    public function __destruct() {
        $this->close();
    }
}