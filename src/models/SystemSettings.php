<?php
require_once __DIR__ . '/../config/database.php';

class SystemSettings {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getSetting($key, $default = null) {
        $stmt = $this->db->prepare("SELECT value FROM system_settings WHERE key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_COLUMN);
        return $result !== false ? $result : $default;
    }
    
    public function setSetting($key, $value) {
        $stmt = $this->db->prepare("
            INSERT INTO system_settings (key, value, updated_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT (key) DO UPDATE SET 
            value = EXCLUDED.value, 
            updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([$key, $value]);
    }
    
    public function toggleStoreVisibilityMode() {
        $currentMode = $this->getSetting('store_visibility_mode', 'show_all');
        $newMode = $currentMode === 'show_all' ? 'available_only' : 'show_all';
        $this->setSetting('store_visibility_mode', $newMode);
        return $newMode;
    }
    
    public function getStoreVisibilityMode() {
        return $this->getSetting('store_visibility_mode', 'show_all');
    }
}
?>
