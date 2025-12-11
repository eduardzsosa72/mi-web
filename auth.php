<?php
// auth.php - VERSIÓN COMPLETA CON TODAS LAS FUNCIONES
require_once 'config.php';

// Configurar headers primero
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Función de respuesta JSON
function jsonResponse($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// Obtener action de manera segura
$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : '';

if (empty($action)) {
    jsonResponse(false, 'No se especificó acción');
}

// Switch para las acciones
switch ($action) {
    case 'login':
        handleLogin();
        break;
    case 'register':
        handleRegister();
        break;
    case 'check_session':
        handleCheckSession();
        break;
    case 'logout':
        handleLogout();
        break;
    case 'consume_credit':
        handleConsumeCredit();
        break;
    case 'redeem_key':
        handleRedeemKey();
        break;
    case 'create_credit_key':
        handleCreateCreditKey();
        break;
    case 'create_multiple_keys':
        handleCreateMultipleKeys();
        break;
    case 'get_credit_keys':
        handleGetCreditKeys();
        break;
    case 'get_user_stats':
        handleGetUserStats();
        break;
    case 'get_users':
        handleGetUsers();
        break;
    case 'update_user':
        handleUpdateUser();
        break;
    case 'reset_password':
        handleResetPassword();
        break;
    case 'delete_user':
        handleDeleteUser();
        break;
    case 'delete_key':
        handleDeleteKey();
        break;
    default:
        jsonResponse(false, 'Acción no válida: ' . $action);
}

// FUNCIÓN LOGIN - ACTUALIZADA CON REDIRECCIÓN
function handleLogin() {
    $input = getInputData();
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');

    if (empty($username) || empty($password)) {
        jsonResponse(false, 'Usuario y contraseña requeridos');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión a la base de datos');
    }

    try {
        $query = "SELECT id, username, email, password, credits, is_admin FROM users WHERE username = :username OR email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $username);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
            
            if (password_verify($password, $user['password'])) {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['credits'] = $user['credits'];
                $_SESSION['is_admin'] = $user['is_admin'];
                $_SESSION['logged_in'] = true;

                jsonResponse(true, 'Login exitoso', [
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'credits' => $user['credits'],
                        'is_admin' => $user['is_admin']
                    ],
                    'redirect' => 'dashboard_page.php'
                ]);
            } else {
                jsonResponse(false, 'Contraseña incorrecta');
            }
        } else {
            jsonResponse(false, 'Usuario no encontrado');
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN REGISTER
function handleRegister() {
    $input = getInputData();
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');
    $confirm_password = trim($input['confirm_password'] ?? '');

    if (empty($username) || empty($email) || empty($password)) {
        jsonResponse(false, 'Todos los campos son requeridos');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Email inválido');
    }

    if ($password !== $confirm_password) {
        jsonResponse(false, 'Las contraseñas no coinciden');
    }

    if (strlen($password) < 6) {
        jsonResponse(false, 'La contraseña debe tener al menos 6 caracteres');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión a la base de datos');
    }

    try {
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            jsonResponse(false, 'El usuario o email ya existen');
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password, credits, is_admin, created_at) 
                  VALUES (:username, :email, :password, 0, FALSE, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashed_password);

        if ($stmt->execute()) {
            jsonResponse(true, 'Usuario registrado exitosamente');
        } else {
            jsonResponse(false, 'Error al registrar usuario');
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN CHECK SESSION
function handleCheckSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
        jsonResponse(true, 'Sesión activa', [
            'user' => [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'email' => $_SESSION['email'],
                'credits' => $_SESSION['credits'],
                'is_admin' => $_SESSION['is_admin']
            ]
        ]);
    } else {
        jsonResponse(false, 'No hay sesión activa');
    }
}

// FUNCIÓN LOGOUT
function handleLogout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    jsonResponse(true, 'Sesión cerrada exitosamente');
}

// FUNCIÓN CONSUMIR CRÉDITO
function handleConsumeCredit() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'No hay sesión activa');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión a la base de datos');
    }

    try {
        $query = "SELECT credits FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();

        $user = $stmt->fetch();
        
        if ($user['credits'] <= 0) {
            jsonResponse(false, 'Créditos insuficientes');
        }

        $query = "UPDATE users SET credits = credits - 1 WHERE id = :user_id AND credits > 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            $_SESSION['credits'] = $user['credits'] - 1;
            
            jsonResponse(true, 'Crédito consumido', [
                'new_balance' => $_SESSION['credits']
            ]);
        } else {
            jsonResponse(false, 'Error al consumir crédito');
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN CANJEAR CLAVE
function handleRedeemKey() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'No hay sesión activa');
    }

    $input = getInputData();
    $credit_key = trim($input['credit_key'] ?? '');

    if (empty($credit_key)) {
        jsonResponse(false, 'La clave es requerida');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión');
    }

    try {
        $query = "SELECT * FROM credit_keys WHERE credit_key = :credit_key AND is_used = FALSE";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':credit_key', $credit_key);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $key_data = $stmt->fetch();

            // Marcar clave como usada
            $query = "UPDATE credit_keys SET is_used = TRUE, used_by = :used_by, used_at = NOW() WHERE id = :key_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':used_by', $_SESSION['user_id']);
            $stmt->bindParam(':key_id', $key_data['id']);
            $stmt->execute();

            // Agregar créditos al usuario
            $query = "UPDATE users SET credits = credits + :credits WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':credits', $key_data['credits_amount']);
            $stmt->bindParam(':user_id', $_SESSION['user_id']);
            $stmt->execute();

            // Actualizar sesión
            $_SESSION['credits'] += $key_data['credits_amount'];

            jsonResponse(true, "¡Clave canjeada! Se agregaron {$key_data['credits_amount']} créditos a tu cuenta.", [
                'credits_added' => $key_data['credits_amount'],
                'new_balance' => $_SESSION['credits']
            ]);

        } else {
            jsonResponse(false, 'Clave inválida o ya utilizada');
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN CREAR CLAVE (ADMIN) - ACTUALIZADA SIN expiry_days
function handleCreateCreditKey() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        jsonResponse(false, 'No autorizado');
    }

    $input = getInputData();
    $credits_amount = intval($input['credits_amount'] ?? 0);

    if ($credits_amount <= 0) {
        jsonResponse(false, 'La cantidad debe ser mayor a 0');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión');
    }

    try {
        // Generar clave única
        $credit_key = 'CK-' . strtoupper(substr(md5(uniqid()), 0, 10)) . '-' . rand(1000, 9999);
        
        $query = "INSERT INTO credit_keys (credit_key, credits_amount, created_by) VALUES (:credit_key, :credits_amount, :created_by)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':credit_key', $credit_key);
        $stmt->bindParam(':credits_amount', $credits_amount);
        $stmt->bindParam(':created_by', $_SESSION['user_id']);

        if ($stmt->execute()) {
            jsonResponse(true, 'Clave creada exitosamente', [
                'credit_key' => $credit_key,
                'credits_amount' => $credits_amount
            ]);
        } else {
            jsonResponse(false, 'Error al crear clave');
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN CREAR MÚLTIPLES CLAVES (ADMIN) - ACTUALIZADA SIN expiry_days
function handleCreateMultipleKeys() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        jsonResponse(false, 'No autorizado');
    }

    $input = getInputData();
    $count = intval($input['count'] ?? 0);
    $credits_amount = intval($input['credits_amount'] ?? 0);

    if ($count < 1 || $count > 50) {
        jsonResponse(false, 'La cantidad debe estar entre 1 y 50');
    }

    if ($credits_amount <= 0) {
        jsonResponse(false, 'La cantidad de créditos debe ser mayor a 0');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión');
    }

    try {
        $generated_keys = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Generar clave única
            $credit_key = 'CK-' . strtoupper(substr(md5(uniqid() . $i), 0, 10)) . '-' . rand(1000, 9999);
            
            $query = "INSERT INTO credit_keys (credit_key, credits_amount, created_by) 
                      VALUES (:credit_key, :credits_amount, :created_by)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':credit_key', $credit_key);
            $stmt->bindParam(':credits_amount', $credits_amount);
            $stmt->bindParam(':created_by', $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $generated_keys[] = $credit_key;
            }
        }

        jsonResponse(true, "{$count} claves creadas exitosamente", [
            'keys' => $generated_keys,
            'count' => $count,
            'credits_amount' => $credits_amount
        ]);
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN: OBTENER CLAVES (ADMIN)
function handleGetCreditKeys() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        jsonResponse(false, 'No autorizado');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión');
    }

    try {
        $query = "SELECT ck.*, u.username as used_by_username 
                  FROM credit_keys ck 
                  LEFT JOIN users u ON ck.used_by = u.id 
                  ORDER BY ck.created_at DESC 
                  LIMIT 50";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $keys = $stmt->fetchAll();

        jsonResponse(true, 'Claves obtenidas', ['keys' => $keys]);
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN: OBTENER ESTADÍSTICAS DE USUARIO
function handleGetUserStats() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'No hay sesión activa');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión a la base de datos');
    }

    try {
        $user_id = $_SESSION['user_id'];
        
        // Obtener créditos actuales
        $query = "SELECT credits FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch();

        // Obtener créditos usados y total de verificaciones
        $query = "SELECT 
                    COALESCE(SUM(credits_amount), 0) as used_credits,
                    COUNT(id) as total_checks
                  FROM credit_keys 
                  WHERE used_by = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $stats = $stmt->fetch();

        // Obtener claves canjeadas recientemente
        $query = "SELECT credit_key, credits_amount, used_at 
                  FROM credit_keys 
                  WHERE used_by = :user_id 
                  ORDER BY used_at DESC 
                  LIMIT 5";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $recent_keys = $stmt->fetchAll();

        jsonResponse(true, 'Estadísticas obtenidas', [
            'credits' => $user['credits'],
            'used_credits' => $stats['used_credits'],
            'total_checks' => $stats['total_checks'],
            'recent_keys' => $recent_keys
        ]);
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN: OBTENER USUARIOS (ADMIN)
function handleGetUsers() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        jsonResponse(false, 'No autorizado');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión');
    }

    try {
        $query = "SELECT id, username, email, credits, is_admin, created_at 
                  FROM users 
                  ORDER BY created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();

        $users = $stmt->fetchAll();

        jsonResponse(true, 'Usuarios obtenidos', ['users' => $users]);
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN: ACTUALIZAR USUARIO (ADMIN)
function handleUpdateUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        jsonResponse(false, 'No autorizado');
    }

    $input = getInputData();
    $user_id = intval($input['user_id'] ?? 0);
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $credits = intval($input['credits'] ?? 0);
    $is_admin = intval($input['is_admin'] ?? 0);

    if ($user_id <= 0) {
        jsonResponse(false, 'ID de usuario inválido');
    }

    if (empty($username) || empty($email)) {
        jsonResponse(false, 'Usuario y email son requeridos');
    }

    if ($credits < 0) {
        $credits = 0;
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión');
    }

    try {
        // Verificar que el email no esté duplicado
        $query = "SELECT id FROM users WHERE email = :email AND id != :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            jsonResponse(false, 'El email ya está en uso por otro usuario');
        }

        // Actualizar usuario
        $query = "UPDATE users SET 
                  username = :username, 
                  email = :email, 
                  credits = :credits, 
                  is_admin = :is_admin 
                  WHERE id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':credits', $credits);
        $stmt->bindParam(':is_admin', $is_admin);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            // Si es el usuario actual, actualizar sesión
            if ($user_id == $_SESSION['user_id']) {
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['credits'] = $credits;
                $_SESSION['is_admin'] = $is_admin;
            }
            
            jsonResponse(true, 'Usuario actualizado exitosamente');
        } else {
            jsonResponse(false, 'Error al actualizar usuario');
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN: RESETEAR CONTRASEÑA (ADMIN)
function handleResetPassword() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        jsonResponse(false, 'No autorizado');
    }

    $input = getInputData();
    $user_id = intval($input['user_id'] ?? 0);

    if ($user_id <= 0) {
        jsonResponse(false, 'ID de usuario inválido');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión');
    }

    try {
        // Generar nueva contraseña aleatoria
        $new_password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $query = "UPDATE users SET password = :password WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            jsonResponse(true, "Contraseña reseteada. Nueva contraseña: {$new_password}");
        } else {
            jsonResponse(false, 'Error al resetear contraseña');
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN: ELIMINAR USUARIO (ADMIN)
function handleDeleteUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        jsonResponse(false, 'No autorizado');
    }

    $input = getInputData();
    $user_id = intval($input['user_id'] ?? 0);

    if ($user_id <= 0) {
        jsonResponse(false, 'ID de usuario inválido');
    }

    // Prevenir que el admin se elimine a sí mismo
    if ($user_id == $_SESSION['user_id']) {
        jsonResponse(false, 'No puedes eliminarte a ti mismo');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión');
    }

    try {
        // Primero, eliminar las keys asociadas
        $query = "DELETE FROM credit_keys WHERE created_by = :user_id OR used_by = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        // Luego, eliminar el usuario
        $query = "DELETE FROM users WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            jsonResponse(true, 'Usuario eliminado exitosamente');
        } else {
            jsonResponse(false, 'Error al eliminar usuario');
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN: ELIMINAR KEY (ADMIN)
function handleDeleteKey() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
        jsonResponse(false, 'No autorizado');
    }

    $input = getInputData();
    $key = trim($input['key'] ?? '');

    if (empty($key)) {
        jsonResponse(false, 'La key es requerida');
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        jsonResponse(false, 'Error de conexión');
    }

    try {
        $query = "DELETE FROM credit_keys WHERE credit_key = :key";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':key', $key);

        if ($stmt->execute()) {
            jsonResponse(true, 'Key eliminada exitosamente');
        } else {
            jsonResponse(false, 'Error al eliminar key');
        }
    } catch (Exception $e) {
        jsonResponse(false, 'Error del sistema: ' . $e->getMessage());
    }
}

// FUNCIÓN PARA OBTENER DATOS DE INPUT
function getInputData() {
    $input = [];
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!empty($_POST)) {
            $input = $_POST;
        } else {
            $jsonInput = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $jsonInput;
            }
        }
    } else {
        $input = $_GET;
    }
    
    return $input;
}
?>