<?php
session_start();
require_once 'auth.php';

header('Content-Type: application/json');

class RedeemSystem {
    private $pdo;
    
    public function __construct() {
        $this->pdo = (new Auth())->getPDO();
    }
    
    public function redeemKey($key, $user_id) {
        try {
            // Verificar si la key existe y está activa
            $stmt = $this->pdo->prepare("
                SELECT * FROM redemption_keys 
                WHERE key_value = ? AND status = 'active' AND expires_at > NOW()
            ");
            $stmt->execute([$key]);
            $keyData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$keyData) {
                return [
                    'success' => false,
                    'message' => 'Key inválida, expirada o ya utilizada'
                ];
            }
            
            // Iniciar transacción
            $this->pdo->beginTransaction();
            
            // Marcar key como usada
            $stmt = $this->pdo->prepare("
                UPDATE redemption_keys 
                SET status = 'used', used_by = ?, used_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$user_id, $keyData['id']]);
            
            // Agregar créditos al usuario
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET credits = credits + ? 
                WHERE id = ?
            ");
            $stmt->execute([$keyData['credits'], $user_id]);
            
            // Registrar en el historial
            $stmt = $this->pdo->prepare("
                INSERT INTO redemption_history (user_id, key_id, key_value, credits, redeemed_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$user_id, $keyData['id'], $key, $keyData['credits']]);
            
            // Obtener nuevo balance
            $stmt = $this->pdo->prepare("SELECT credits FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $newBalance = $stmt->fetchColumn();
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'credits_added' => $keyData['credits'],
                'new_balance' => $newBalance,
                'message' => 'Key canjeada exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error en redeemKey: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del servidor'
            ];
        }
    }
    
    public function getRedemptionHistory($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    rh.key_value,
                    rh.credits,
                    rh.redeemed_at,
                    rk.status
                FROM redemption_history rh
                LEFT JOIN redemption_keys rk ON rh.key_id = rk.id
                WHERE rh.user_id = ?
                ORDER BY rh.redeemed_at DESC
                LIMIT 50
            ");
            $stmt->execute([$user_id]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'history' => $history
            ];
            
        } catch (Exception $e) {
            error_log("Error en getRedemptionHistory: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error cargando historial'
            ];
        }
    }
}

// Procesar solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'redeem') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $key = trim($_POST['key'] ?? '');
    if (empty($key)) {
        echo json_encode(['success' => false, 'message' => 'Key no proporcionada']);
        exit;
    }
    
    $redeemSystem = new RedeemSystem();
    $result = $redeemSystem->redeemKey($key, $_SESSION['user_id']);
    echo json_encode($result);
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_history') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'No autorizado']);
        exit;
    }
    
    $redeemSystem = new RedeemSystem();
    $result = $redeemSystem->getRedemptionHistory($_SESSION['user_id']);
    echo json_encode($result);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>