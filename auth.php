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
    case 'get_credit_keys': // ✅ NUEVA FUNCIÓN
        handleGetCreditKeys();
        break;
    case 'get_user_stats': // ✅ NUEVA FUNCIÓN
        handleGetUserStats();
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
                    'redirect' => 'dashboard_page.php'  // ← LÍNEA AÑADIDA PARA REDIRECCIÓN
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

// FUNCIÓN CREAR CLAVE (ADMIN)
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

// ✅ NUEVA FUNCIÓN: OBTENER CLAVES (ADMIN)
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

// ✅ NUEVA FUNCIÓN: OBTENER ESTADÍSTICAS DE USUARIO
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

        // Obtener claves canjeadas recientemente
        $query = "SELECT credits_amount, used_at 
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
            'recent_keys' => $recent_keys
        ]);
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