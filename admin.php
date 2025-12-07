<?php
session_start();
require_once 'auth.php';

header('Content-Type: application/json');

class AdminSystem {
    private $pdo;
    
    public function __construct() {
        $this->pdo = (new Auth())->getPDO();
    }
    
    public function checkPermissions($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user && $user['is_admin'] == 1;
        } catch (Exception $e) {
            error_log("Error checking permissions: " . $e->getMessage());
            return false;
        }
    }
    
    public function getStats() {
        try {
            // Total usuarios
            $stmt = $this->pdo->query("SELECT COUNT(*) as total_users FROM users");
            $totalUsers = $stmt->fetchColumn();
            
            // Usuarios este mes
            $stmt = $this->pdo->query("SELECT COUNT(*) as users_this_month FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
            $usersThisMonth = $stmt->fetchColumn();
            
            // Keys activas
            $stmt = $this->pdo->query("SELECT COUNT(*) as active_keys FROM redemption_keys WHERE status = 'active' AND expires_at > NOW()");
            $activeKeys = $stmt->fetchColumn();
            
            // Keys generadas hoy
            $stmt = $this->pdo->query("SELECT COUNT(*) as keys_generated_today FROM redemption_keys WHERE DATE(created_at) = CURDATE()");
            $keysGeneratedToday = $stmt->fetchColumn();
            
            // Créditos totales
            $stmt = $this->pdo->query("SELECT SUM(credits) as total_credits FROM users");
            $totalCredits = $stmt->fetchColumn() ?: 0;
            
            // Créditos distribuidos hoy
            $stmt = $this->pdo->query("SELECT SUM(credits) as credits_distributed_today FROM redemption_history WHERE DATE(redeemed_at) = CURDATE()");
            $creditsDistributedToday = $stmt->fetchColumn() ?: 0;
            
            // Ingresos (simulado - ajustar según tu modelo de negocio)
            $totalIncome = $totalCredits * 0.01; // Ejemplo: $0.01 por crédito
            $incomeThisMonth = $creditsDistributedToday * 0.01;
            
            return [
                'success' => true,
                'stats' => [
                    'total_users' => (int)$totalUsers,
                    'users_this_month' => (int)$usersThisMonth,
                    'active_keys' => (int)$activeKeys,
                    'keys_generated_today' => (int)$keysGeneratedToday,
                    'total_credits' => (int)$totalCredits,
                    'credits_distributed_today' => (int)$creditsDistributedToday,
                    'total_income' => round($totalIncome, 2),
                    'income_this_month' => round($incomeThisMonth, 2)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error getting stats: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error obteniendo estadísticas'];
        }
    }
    
    public function generateKey($credits, $expiry_days, $prefix = '', $quantity = 1) {
        try {
            $keys = [];
            
            for ($i = 0; $i < $quantity; $i++) {
                $key = $this->generateUniqueKey($prefix);
                $expiry_date = date('Y-m-d H:i:s', strtotime("+$expiry_days days"));
                
                $stmt = $this->pdo->prepare("
                    INSERT INTO redemption_keys (key_value, credits, expires_at, created_by) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$key, $credits, $expiry_date, $_SESSION['user_id']]);
                
                $keys[] = $key;
            }
            
            return [
                'success' => true,
                'keys' => $keys,
                'message' => "$quantity key(s) generada(s) exitosamente"
            ];
            
        } catch (Exception $e) {
            error_log("Error generating key: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error generando key'];
        }
    }
    
    private function generateUniqueKey($prefix = '') {
        do {
            $random = strtoupper(bin2hex(random_bytes(8)));
            $key = $prefix ? $prefix . '-' . $random : $random;
            
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM redemption_keys WHERE key_value = ?");
            $stmt->execute([$key]);
        } while ($stmt->fetchColumn() > 0);
        
        return $key;
    }
    
    public function getRecentKeys($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT key_value, credits, status, expires_at, created_at 
                FROM redemption_keys 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'keys' => $keys];
            
        } catch (Exception $e) {
            error_log("Error getting recent keys: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error obteniendo keys'];
        }
    }
    
    public function getRecentUsers($limit = 10) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT username, email, credits, created_at, is_active 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'users' => $users];
            
        } catch (Exception $e) {
            error_log("Error getting recent users: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error obteniendo usuarios'];
        }
    }
    
    public function deleteKey($key) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM redemption_keys WHERE key_value = ?");
            $stmt->execute([$key]);
            
            return ['success' => true, 'message' => 'Key eliminada exitosamente'];
            
        } catch (Exception $e) {
            error_log("Error deleting key: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error eliminando key'];
        }
    }
    
    public function exportKeys() {
        try {
            $stmt = $this->pdo->query("
                SELECT key_value, credits, status, expires_at, created_at 
                FROM redemption_keys 
                ORDER BY created_at DESC
            ");
            $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Generar CSV
            $csv = "Key,Creditos,Estado,Expiracion,Creado\n";
            foreach ($keys as $key) {
                $csv .= "\"{$key['key_value']}\",{$key['credits']},\"{$key['status']}\",\"{$key['expires_at']}\",\"{$key['created_at']}\"\n";
            }
            
            return ['success' => true, 'csv' => $csv];
            
        } catch (Exception $e) {
            error_log("Error exporting keys: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error exportando keys'];
        }
    }
}

// Procesar solicitud
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$adminSystem = new AdminSystem();

// Verificar permisos de administrador
if (!$adminSystem->checkPermissions($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Se requieren permisos de administrador.']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_permissions':
        echo json_encode(['success' => true, 'is_admin' => true]);
        break;
        
    case 'get_stats':
        $result = $adminSystem->getStats();
        echo json_encode($result);
        break;
        
    case 'generate_key':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $credits = intval($_POST['credits'] ?? 0);
            $expiry_days = intval($_POST['expiry_days'] ?? 30);
            $prefix = $_POST['prefix'] ?? '';
            $quantity = intval($_POST['quantity'] ?? 1);
            
            $result = $adminSystem->generateKey($credits, $expiry_days, $prefix, $quantity);
            echo json_encode($result);
        }
        break;
        
    case 'get_recent_keys':
        $result = $adminSystem->getRecentKeys();
        echo json_encode($result);
        break;
        
    case 'get_recent_users':
        $result = $adminSystem->getRecentUsers();
        echo json_encode($result);
        break;
        
    case 'delete_key':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $key = $_POST['key'] ?? '';
            $result = $adminSystem->deleteKey($key);
            echo json_encode($result);
        }
        break;
        
    case 'export_keys':
        $result = $adminSystem->exportKeys();
        echo json_encode($result);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>