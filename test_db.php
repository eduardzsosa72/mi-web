<?php
require_once 'config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🔍 Test de Conexión a Base de Datos</h2>";

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<p style='color: green; font-weight: bold;'>✅ CONEXIÓN EXITOSA A LA BASE DE DATOS</p>";
    
    // Probar consulta
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $result = $stmt->fetch();
        echo "<p>✅ Consulta ejecutada correctamente</p>";
        echo "<p>Total de usuarios: " . $result['total'] . "</p>";
        
        // Mostrar usuarios
        $stmt = $db->query("SELECT id, username, email, credits, is_admin FROM users");
        $users = $stmt->fetchAll();
        
        if (count($users) > 0) {
            echo "<h3>Usuarios en la base de datos:</h3>";
            echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Usuario</th><th>Email</th><th>Créditos</th><th>Admin</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . $user['id'] . "</td>";
                echo "<td>" . $user['username'] . "</td>";
                echo "<td>" . $user['email'] . "</td>";
                echo "<td>" . $user['credits'] . "</td>";
                echo "<td>" . ($user['is_admin'] ? 'Sí' : 'No') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>⚠️ No hay usuarios en la base de datos</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error en consulta: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ ERROR DE CONEXIÓN A LA BASE DE DATOS</p>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>El servidor de BD esté activo</li>";
    echo "<li>Las credenciales sean correctas</li>";
    echo "<li>No haya bloqueos del hosting</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='login.html' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Login</a></p>";
?>