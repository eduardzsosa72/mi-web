<?php
session_start();
require_once 'config.php';

// Verificar sesión
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.html');
    exit;
}

// Obtener datos del usuario para mostrar en el dashboard
$user_id = $_SESSION['user_id'];
$database = new Database();
$db = $database->getConnection();

$userData = [];
$isAdmin = false;
$usedCredits = 0;
$totalChecks = 0;

if ($db) {
    try {
        // Obtener datos del usuario
        $stmt = $db->prepare("SELECT username, email, credits, is_admin FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        $isAdmin = $userData['is_admin'] ?? false;
        
        // Obtener créditos usados y verificaciones del usuario
        $stmt = $db->prepare("
            SELECT 
                COALESCE(SUM(ck.credits_amount), 0) as used_credits,
                COUNT(ck.id) as total_checks
            FROM credit_keys ck 
            WHERE ck.used_by = ?
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $usedCredits = $stats['used_credits'] ?? 0;
        $totalChecks = $stats['total_checks'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Error getting user data: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PANEL MENU | 888-CHECKER</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Rubik:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <style>
        /* Estilos actualizados con tema verde */
        :root {
            --primary: #10b981;
            --primary-dark: #059669;
            --primary-light: #34d399;
            --primary-lighter: #d1fae5;
            
            --secondary: #34d399;
            --amazon: #10b981;
            --amazon-dark: #059669;
            --amazon-light: #a7f3d0;
            --paypal: #10b981;
            --paypal-dark: #059669;
            --paypal-light: #a7f3d0;
            
            --success: #10b981;
            --success-dark: #059669;
            --success-light: #a7f3d0;
            
            --warning: #f59e0b;
            --warning-dark: #d97706;
            --warning-light: #fde68a;
            
            --error: #ef4444;
            --error-dark: #b91c1c;
            --error-light: #fecaca;
            
            --info: #10b981;
            --info-dark: #059669;
            --info-light: #a7f3d0;
            
            --dark: #020617;
            --darker: #000814;
            --light: #e5e7eb;
            --gray: #9ca3af;
            --gray-light: #1f2937;
            
            --card-bg: rgba(2, 6, 23, 0.95);
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
            --safe-left: env(safe-area-inset-left, 0px);
            --safe-right: env(safe-area-inset-right, 0px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes glow {
            0%, 100% { box-shadow: 0 0 10px rgba(16, 185, 129, 0.4); }
            50% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.6); }
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            -webkit-tap-highlight-color: transparent;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body {
            background: radial-gradient(circle at center, #020617, #000814);
            color: var(--light);
            min-height: 100vh;
            min-height: -webkit-fill-available;
            line-height: 1.5;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding-top: var(--safe-top);
            padding-bottom: var(--safe-bottom);
            padding-left: var(--safe-left);
            padding-right: var(--safe-right);
            overflow-x: hidden;
        }

        html {
            height: -webkit-fill-available;
        }

        .app-container {
            max-width: 100%;
            margin: 0 auto;
            padding: 1rem;
            padding-top: calc(1rem + var(--safe-top));
            padding-bottom: calc(1rem + var(--safe-bottom));
            animation: fadeIn 0.5s ease-out;
        }

        /* Header Styles - Tema verde */
        .app-header {
            background: rgba(2, 6, 23, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(16, 185, 129, 0.3);
            animation: glow 3s infinite alternate;
            position: relative;
            overflow: hidden;
        }

        .app-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light), var(--primary));
            animation: gradient 3s ease infinite;
            background-size: 200% 100%;
        }

        .app-logo {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .app-logo-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            width: 100%;
        }

        .app-logo-img {
            width: 70px;
            height: 70px;
            min-width: 70px;
            min-height: 70px;
            border-radius: 16px;
            object-fit: cover;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.6);
            border: 2px solid var(--primary);
        }

        .app-title {
            flex: 1;
            min-width: 0;
        }

        .app-title h1 {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.25rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-shadow: 0 0 15px rgba(16, 185, 129, 0.3);
        }

        .app-title p {
            color: var(--primary-light);
            font-size: 0.875rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            width: 100%;
        }

        .user-stats-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .user-credits {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--dark);
            padding: 0.6rem 1rem;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            transition: var(--transition);
            font-size: 0.875rem;
            flex: 1;
            min-width: 0;
        }

        .user-credits:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .user-badge {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--dark);
            padding: 0.5rem 0.875rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .admin-badge {
            background: linear-gradient(135deg, var(--warning), var(--warning-dark));
            color: white;
            padding: 0.5rem 0.875rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.8rem;
            border: 1px solid rgba(255, 255, 255, 0.15);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .nav-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            width: 100%;
        }

        /* Stats Grid - Tema verde */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.25rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(16, 185, 129, 0.25);
            animation: fadeIn 0.5s ease-out;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            animation: gradient 3s ease infinite;
            background-size: 200% 100%;
        }

        .stat-card:active {
            transform: scale(0.98);
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            min-width: 45px;
            min-height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .stat-card.credits .stat-icon {
            background: rgba(16, 185, 129, 0.2);
            color: var(--primary);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .stat-card.used .stat-icon {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .stat-card.available .stat-icon {
            background: rgba(16, 185, 129, 0.2);
            color: var(--primary-light);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .stat-card.history .stat-icon {
            background: rgba(16, 185, 129, 0.2);
            color: var(--info);
            border-color: rgba(16, 185, 129, 0.3);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            color: var(--primary);
            line-height: 1.2;
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }

        .stat-card.credits .stat-value {
            color: var(--primary);
        }

        .stat-card.used .stat-value {
            color: var(--success);
        }

        .stat-card.available .stat-value {
            color: var(--primary-light);
        }

        .stat-card.history .stat-value {
            color: var(--info);
        }

        .stat-label {
            color: rgba(255, 255, 255, 0.7);
            font-weight: 500;
            font-size: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .stat-change {
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .positive {
            color: var(--success-light);
        }

        .negative {
            color: var(--error-light);
        }

        .warning {
            color: var(--warning-light);
        }

        /* Main Content */
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Card Styles */
        .card {
            background: var(--card-bg);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(16, 185, 129, 0.25);
            animation: fadeIn 0.5s ease-out;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            animation: gradient 3s ease infinite;
            background-size: 200% 100%;
        }

        .card:active {
            transform: scale(0.99);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .card-icon {
            width: 50px;
            height: 50px;
            min-width: 50px;
            min-height: 50px;
            background: rgba(16, 185, 129, 0.15);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.5rem;
            transition: var(--transition);
            border: 1px solid rgba(16, 185, 129, 0.25);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-light);
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .card-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-light);
            font-size: 0.875rem;
        }

        input, select, textarea {
            width: 100%;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(16, 185, 129, 0.3);
            border-radius: 10px;
            color: var(--light);
            padding: 0.875rem 1rem;
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            -webkit-appearance: none;
            appearance: none;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.2);
            background: rgba(0, 0, 0, 0.35);
        }

        input::placeholder, select::placeholder, textarea::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Buttons - Tema verde */
        .btn-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            width: 100%;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.875rem 1rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            white-space: nowrap;
            -webkit-tap-highlight-color: transparent;
            user-select: none;
        }

        .btn:active {
            transform: scale(0.95);
        }

        .btn i {
            font-size: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: var(--dark);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-amazon {
            background: linear-gradient(135deg, var(--amazon), var(--amazon-dark));
            color: var(--dark);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-paypal {
            background: linear-gradient(135deg, var(--paypal), var(--paypal-dark));
            color: var(--dark);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success), var(--success-dark));
            color: var(--dark);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--light);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--error), var(--error-dark));
            color: white;
            box-shadow: 0 4px 12px rgba(229, 62, 62, 0.3);
        }

        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }

        .btn-xs {
            padding: 0.375rem 0.5rem;
            font-size: 0.7rem;
            gap: 0.25rem;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Admin Section */
        .admin-section {
            margin-bottom: 1.5rem;
            display: none;
        }

        .admin-visible .admin-section {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            border-radius: 12px;
            border: 1px solid rgba(16, 185, 129, 0.2);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            margin-top: 1rem;
            background: rgba(0, 0, 0, 0.25);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: transparent;
            min-width: 600px;
        }

        .data-table th {
            text-align: left;
            padding: 0.75rem;
            background: rgba(16, 185, 129, 0.15);
            color: var(--primary-light);
            font-weight: 700;
            font-size: 0.75rem;
            border-bottom: 2px solid var(--primary);
            white-space: nowrap;
        }

        .data-table td {
            padding: 0.75rem;
            border-bottom: 1px solid rgba(16, 185, 129, 0.15);
            color: var(--light);
            font-size: 0.75rem;
            white-space: nowrap;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:active td {
            background: rgba(16, 185, 129, 0.08);
        }

        .table-actions {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: inline-block;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success-light);
            border-color: var(--success);
        }

        .status-inactive {
            background: rgba(245, 158, 11, 0.2);
            color: var(--warning-light);
            border-color: var(--warning);
        }

        .status-admin {
            background: rgba(239, 68, 68, 0.2);
            color: var(--error-light);
            border-color: var(--error);
        }

        .empty-message {
            text-align: center;
            padding: 2rem 1rem;
            color: rgba(255, 255, 255, 0.5);
            font-style: italic;
            font-size: 0.875rem;
        }

        /* Key styles */
        .key-display {
            font-family: 'Courier New', monospace;
            background: rgba(0, 0, 0, 0.4);
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            border: 1px solid var(--primary);
            font-weight: 600;
            color: var(--primary-light);
            font-size: 0.7rem;
            word-break: break-all;
            max-width: 150px;
        }

        .copy-btn {
            background: var(--primary);
            color: var(--dark);
            border: none;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.7rem;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 28px;
            min-height: 28px;
        }

        .copy-btn:active {
            transform: scale(0.9);
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.85);
            display: none;
            align-items: flex-start;
            justify-content: center;
            z-index: 1000;
            padding: 1rem;
            padding-top: calc(2rem + var(--safe-top));
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .modal {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 1.5rem;
            width: 100%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            border: 1px solid var(--primary);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-out;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-light);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--gray);
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .modal-close:active {
            background: rgba(16, 185, 129, 0.1);
        }

        /* Modal para múltiples keys */
        .modal-body .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .modal-body .form-full {
            grid-column: 1 / -1;
        }

        /* Footer */
        .app-footer {
            text-align: center;
            padding-top: 1.5rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
            animation: fadeIn 0.5s ease-out;
        }

        .heart-icon {
            color: var(--error);
            display: inline-block;
            animation: pulse 1.5s infinite;
            margin: 0 0.25rem;
        }

        .app-footer a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
        }

        .app-footer a:hover {
            color: var(--primary-light);
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }

        .app-footer a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .app-footer a:hover::after {
            width: 100%;
        }

        /* Responsive adjustments */
        @media (min-width: 640px) {
            .app-container {
                padding: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
            
            .main-content {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
            
            .nav-buttons {
                grid-template-columns: repeat(3, auto);
                gap: 0.75rem;
            }
            
            .btn {
                padding: 0.875rem 1.25rem;
            }
        }

        @media (min-width: 768px) {
            .app-container {
                max-width: 1200px;
                padding: 2rem;
            }
            
            .app-logo {
                flex-direction: row;
                justify-content: space-between;
            }
            
            .user-info {
                flex-direction: row;
                align-items: center;
            }
            
            .nav-buttons {
                width: auto;
            }
        }

        @media (max-width: 480px) {
            .app-logo-left {
                align-items: flex-start;
            }
            
            .app-logo-img {
                width: 60px;
                height: 60px;
                min-width: 60px;
                min-height: 60px;
            }
            
            .app-title h1 {
                font-size: 1.2rem;
            }
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--primary), var(--primary-dark));
            border-radius: 3px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--primary-dark), var(--info-dark));
        }

        /* Selection */
        ::selection {
            background: rgba(16, 185, 129, 0.3);
            color: white;
        }

        /* Prevent text size adjustment on orientation change */
        html {
            -webkit-text-size-adjust: 100%;
        }

        /* Better touch targets */
        button, 
        input[type="button"], 
        input[type="submit"], 
        input[type="reset"],
        a.btn {
            min-height: 44px;
            min-width: 44px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <header class="app-header">
            <div class="app-logo">
                <div class="app-logo-left">
                    <!-- Reemplaza "logo.jpg" con el nombre de tu archivo de logo -->
                    <img src="logo.jpg" alt="888-CHECKER Logo" class="app-logo-img">
                    <div class="app-title">
                        <h1>Dashboard</h1>
                        <p id="userRoleText">Panel de control</p>
                    </div>
                </div>
                <div class="user-info">
                    <div class="user-stats-row">
                        <div class="user-credits">
                            <i class="fas fa-coins"></i>
                            <span id="creditsCount"><?php echo $userData['credits'] ?? 0; ?></span>
                        </div>
                        <div id="userBadge" class="<?php echo $isAdmin ? 'admin-badge' : 'user-badge'; ?>">
                            <i class="fas fa-<?php echo $isAdmin ? 'crown' : 'user'; ?>"></i>
                            <span id="badgeText"><?php echo $isAdmin ? 'Admin' : 'Usuario'; ?></span>
                        </div>
                    </div>
                    <div class="nav-buttons">
                        <a href="index.html" class="btn btn-amazon">
                            <i class="fas fa-shield-alt"></i>
                            <span>VERIFICAR</span>
                        </a>
                        <button id="logoutBtn" class="btn btn-danger">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>SALIR</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- SECCIÓN PARA TODOS LOS USUARIOS -->
        <div class="stats-grid">
            <div class="stat-card credits">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="currentCredits"><?php echo $userData['credits'] ?? 0; ?></div>
                        <div class="stat-label">Créditos</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
                <div class="stat-change" id="creditsStatus">Disponibles</div>
            </div>

            <div class="stat-card used">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="usedCredits"><?php echo $usedCredits; ?></div>
                        <div class="stat-label">Usados</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-change positive" id="usedStatus">Total</div>
            </div>

            <div class="stat-card available">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="availableChecks"><?php echo $userData['credits'] ?? 0; ?></div>
                        <div class="stat-label">Verificaciones</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                </div>
                <div class="stat-change positive" id="availableStatus">Disponibles</div>
            </div>

            <div class="stat-card history">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" id="totalChecks"><?php echo $totalChecks; ?></div>
                        <div class="stat-label">Historial</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-history"></i>
                    </div>
                </div>
                <div class="stat-change positive" id="historyStatus">Realizadas</div>
            </div>
        </div>

        <div class="main-content">
            <!-- TARJETA DE RECARGA -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-key"></i>
                    </div>
                    <h3 class="card-title">Recargar Créditos</h3>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="creditKey" class="form-label">Key de Recarga</label>
                        <input type="text" id="creditKey" placeholder="CK-ABC123DEF456" maxlength="50">
                        <div style="font-size: 0.75rem; color: var(--primary-light); margin-top: 0.5rem;">
                            Obtén keys del administrador
                        </div>
                    </div>
                    <div class="btn-group">
                        <button id="redeemBtn" class="btn btn-success">
                            <i class="fas fa-gift"></i>
                            <span>Canjear</span>
                        </button>
                        <button id="clearKeyBtn" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            <span>Limpiar</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- TARJETA DE ACTIVIDAD -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="card-title">Actividad Reciente</h3>
                    <div class="card-actions">
                        <button id="refreshBtn" class="btn btn-secondary btn-sm">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Key</th>
                                    <th>Créditos</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody id="activityTable">
                                <tr>
                                    <td colspan="4" class="empty-message">Cargando...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECCIÓN SOLO PARA ADMINISTRADORES -->
        <?php if ($isAdmin): ?>
        <div class="admin-section">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h3 class="card-title">Panel Admin</h3>
                    <div class="card-actions">
                        <button id="adminRefreshBtn" class="btn btn-primary btn-sm">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="color: var(--primary-light); margin-bottom: 0.75rem; font-size: 1rem;">Generar Keys</h4>
                        <div class="form-group">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem;">
                                <div>
                                    <label style="display: block; margin-bottom: 0.25rem; font-size: 0.75rem; color: var(--primary-light);">Créditos</label>
                                    <input type="number" id="keyCredits" placeholder="1000" min="100" max="100000" value="1000">
                                </div>
                                <div>
                                    <label style="display: block; margin-bottom: 0.25rem; font-size: 0.75rem; color: var(--primary-light);">Días</label>
                                    <input type="number" id="keyExpiry" placeholder="30" min="1" max="365" value="30">
                                </div>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button id="generateKeyBtn" class="btn btn-success">
                                <i class="fas fa-plus"></i>
                                <span>Generar</span>
                            </button>
                            <button id="generateMultipleBtn" class="btn btn-primary">
                                <i class="fas fa-layer-group"></i>
                                <span>Múltiples</span>
                            </button>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <h4 style="color: var(--primary-light); margin-bottom: 0.75rem; font-size: 1rem;">Keys Generadas</h4>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Key</th>
                                        <th>Créditos</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="keysTable">
                                    <tr>
                                        <td colspan="4" class="empty-message">Cargando keys...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div>
                        <h4 style="color: var(--primary-light); margin-bottom: 0.75rem; font-size: 1rem;">Usuarios</h4>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Usuario</th>
                                        <th>Email</th>
                                        <th>Créditos</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="usersTable">
                                    <tr>
                                        <td colspan="4" class="empty-message">Cargando usuarios...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <footer class="app-footer">
            <p>
                Desarrollado con <i class="fas fa-heart heart-icon"></i> por 
                <a href="#" target="_blank">888-CHECKER</a>
            </p>
        </footer>
    </div>

    <!-- Modal para editar usuario -->
    <div id="editUserModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Editar Usuario</h3>
                <button class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="editUsername" class="form-label">Usuario</label>
                    <input type="text" id="editUsername" placeholder="Nombre de usuario">
                </div>
                <div class="form-group">
                    <label for="editEmail" class="form-label">Email</label>
                    <input type="email" id="editEmail" placeholder="Correo electrónico">
                </div>
                <div class="form-group">
                    <label for="editCredits" class="form-label">Créditos</label>
                    <input type="number" id="editCredits" placeholder="Créditos" min="0" max="1000000">
                </div>
                <div class="form-group">
                    <label for="editIsAdmin" class="form-label">Rol</label>
                    <select id="editIsAdmin">
                        <option value="0">Usuario</option>
                        <option value="1">Administrador</option>
                    </select>
                </div>
                <input type="hidden" id="editUserId">
                <div class="btn-group" style="margin-top: 1.5rem;">
                    <button id="saveUserBtn" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        <span>Guardar</span>
                    </button>
                    <button id="cancelEditBtn" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para múltiples keys -->
    <div id="multipleKeysModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Generar Keys Múltiples</h3>
                <button class="modal-close" onclick="closeMultipleModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="form-row">
                        <div class="form-full">
                            <label for="multipleCount" class="form-label">Cantidad de Keys</label>
                            <input type="number" id="multipleCount" placeholder="5" min="1" max="50" value="5">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label for="multipleCredits" class="form-label">Créditos por Key</label>
                            <input type="number" id="multipleCredits" placeholder="1000" min="100" max="100000" value="1000">
                        </div>
                        <div>
                            <label for="multipleExpiry" class="form-label">Días de Validez</label>
                            <input type="number" id="multipleExpiry" placeholder="30" min="1" max="365" value="30">
                        </div>
                    </div>
                </div>
                <div class="btn-group" style="margin-top: 1.5rem;">
                    <button id="generateMultipleConfirmBtn" class="btn btn-success">
                        <i class="fas fa-plus"></i>
                        <span>Generar Keys</span>
                    </button>
                    <button onclick="closeMultipleModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        <span>Cancelar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <script>
        // DATOS INICIALES DESDE PHP
        const initialUserData = {
            username: '<?php echo addslashes($userData['username'] ?? ''); ?>',
            email: '<?php echo addslashes($userData['email'] ?? ''); ?>', 
            credits: <?php echo $userData['credits'] ?? 0; ?>,
            is_admin: <?php echo $isAdmin ? 'true' : 'false'; ?>,
            used_credits: <?php echo $usedCredits; ?>,
            total_checks: <?php echo $totalChecks; ?>
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Elementos de la interfaz
            const creditsCount = document.getElementById('creditsCount');
            const currentCredits = document.getElementById('currentCredits');
            const usedCredits = document.getElementById('usedCredits');
            const availableChecks = document.getElementById('availableChecks');
            const totalChecks = document.getElementById('totalChecks');
            const creditKey = document.getElementById('creditKey');
            const redeemBtn = document.getElementById('redeemBtn');
            const clearKeyBtn = document.getElementById('clearKeyBtn');
            const refreshBtn = document.getElementById('refreshBtn');
            const logoutBtn = document.getElementById('logoutBtn');
            const activityTable = document.getElementById('activityTable');
            
            // Elementos de admin
            const keyCredits = document.getElementById('keyCredits');
            const keyExpiry = document.getElementById('keyExpiry');
            const generateKeyBtn = document.getElementById('generateKeyBtn');
            const generateMultipleBtn = document.getElementById('generateMultipleBtn');
            const adminRefreshBtn = document.getElementById('adminRefreshBtn');
            const keysTable = document.getElementById('keysTable');
            const usersTable = document.getElementById('usersTable');
            
            // Elementos del modal de usuario
            const editUserModal = document.getElementById('editUserModal');
            const modalClose = editUserModal.querySelector('.modal-close');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const saveUserBtn = document.getElementById('saveUserBtn');
            const editUserId = document.getElementById('editUserId');
            const editUsername = document.getElementById('editUsername');
            const editEmail = document.getElementById('editEmail');
            const editCredits = document.getElementById('editCredits');
            const editIsAdmin = document.getElementById('editIsAdmin');
            
            // Elementos del modal de múltiples keys
            const multipleKeysModal = document.getElementById('multipleKeysModal');
            const generateMultipleConfirmBtn = document.getElementById('generateMultipleConfirmBtn');

            // Mostrar sección de admin si corresponde
            if (initialUserData.is_admin) {
                document.body.classList.add('admin-visible');
            }

            // Actualizar estado de créditos
            updateCreditsStatus(initialUserData.credits);

            // Cargar datos iniciales
            loadUserActivity();

            // Si es admin, cargar datos administrativos
            if (initialUserData.is_admin) {
                loadAdminData();
            }

            // Event Listeners
            redeemBtn.addEventListener('click', redeemCreditKey);
            clearKeyBtn.addEventListener('click', () => creditKey.value = '');
            refreshBtn.addEventListener('click', refreshUserData);
            logoutBtn.addEventListener('click', logoutUser);

            if (initialUserData.is_admin) {
                generateKeyBtn.addEventListener('click', generateCreditKey);
                generateMultipleBtn.addEventListener('click', openMultipleKeysModal);
                adminRefreshBtn.addEventListener('click', refreshAdminData);
                generateMultipleConfirmBtn.addEventListener('click', generateMultipleCreditKeys);
            }

            // Modal events
            modalClose.addEventListener('click', closeModal);
            cancelEditBtn.addEventListener('click', closeModal);
            saveUserBtn.addEventListener('click', saveUserChanges);

            // Cerrar modal al hacer clic fuera
            editUserModal.addEventListener('click', (e) => {
                if (e.target === editUserModal) {
                    closeModal();
                }
            });

            // Permitir canjear con Enter
            creditKey.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    redeemCreditKey();
                }
            });

            // Prevenir zoom en inputs
            document.addEventListener('touchstart', function(event) {
                if (event.target.tagName === 'INPUT' || event.target.tagName === 'SELECT' || event.target.tagName === 'TEXTAREA') {
                    event.target.style.fontSize = '16px';
                }
            });

            // Funciones principales
            function updateCreditsStatus(credits) {
                const creditsStatus = document.getElementById('creditsStatus');
                
                if (credits <= 0) {
                    creditsStatus.textContent = 'Sin créditos';
                    creditsStatus.className = 'stat-change negative';
                } else if (credits < 10) {
                    creditsStatus.textContent = 'Bajos';
                    creditsStatus.className = 'stat-change warning';
                } else {
                    creditsStatus.textContent = 'Disponibles';
                    creditsStatus.className = 'stat-change positive';
                }
            }

            function loadUserActivity() {
                fetch('auth.php?action=get_user_stats')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (data.recent_keys) {
                                updateActivityTable(data.recent_keys);
                            }
                            
                            // Actualizar estadísticas si vienen en la respuesta
                            if (data.used_credits !== undefined) {
                                document.getElementById('usedCredits').textContent = data.used_credits;
                            }
                            if (data.total_checks !== undefined) {
                                document.getElementById('totalChecks').textContent = data.total_checks;
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading user activity:', error);
                    });
            }

            function updateActivityTable(activities) {
                if (!activities || activities.length === 0) {
                    activityTable.innerHTML = '<tr><td colspan="4" class="empty-message">No hay actividad</td></tr>';
                    return;
                }

                let html = '';
                activities.forEach(activity => {
                    // Para actividad de usuario, mostrar key censurada
                    const maskedKey = activity.credit_key ? 
                        activity.credit_key.substring(0, 6) + '***' + activity.credit_key.substring(activity.credit_key.length - 4) : 
                        'N/A';
                    
                    const date = activity.used_at ? formatDateMobile(activity.used_at) : 'N/A';
                    
                    html += `
                        <tr>
                            <td style="font-size: 0.7rem;">${maskedKey}</td>
                            <td style="color: var(--success-light); font-weight: 600; font-size: 0.7rem;">+${activity.credits_amount || 0}</td>
                            <td style="font-size: 0.7rem;">${date}</td>
                            <td><span class="status-badge status-active">✓</span></td>
                        </tr>
                    `;
                });

                activityTable.innerHTML = html;
            }

            function redeemCreditKey() {
                const key = creditKey.value.trim();
                
                if (!key) {
                    showToast('error', 'Ingresa una key', '#ef4444');
                    return;
                }

                redeemBtn.disabled = true;
                redeemBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>...</span>';

                const formData = new FormData();
                formData.append('credit_key', key);

                fetch('auth.php?action=redeem_key', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message, '#10b981');
                        creditKey.value = '';
                        
                        // Actualizar créditos en la interfaz
                        const newBalance = data.new_balance;
                        creditsCount.textContent = newBalance;
                        currentCredits.textContent = newBalance;
                        availableChecks.textContent = newBalance;
                        initialUserData.credits = newBalance;
                        
                        updateCreditsStatus(newBalance);
                        loadUserActivity();
                        
                        // Actualizar créditos usados si vienen en la respuesta
                        if (data.used_credits !== undefined) {
                            document.getElementById('usedCredits').textContent = data.used_credits;
                        }
                    } else {
                        showToast('error', data.message, '#ef4444');
                    }
                })
                .catch(error => {
                    console.error('Error redeeming key:', error);
                    showToast('error', 'Error al procesar', '#ef4444');
                })
                .finally(() => {
                    redeemBtn.disabled = false;
                    redeemBtn.innerHTML = '<i class="fas fa-gift"></i><span>Canjear</span>';
                });
            }

            function refreshUserData() {
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                Promise.all([
                    fetch('auth.php?action=check_session').then(r => r.json()),
                    fetch('auth.php?action=get_user_stats').then(r => r.json())
                ])
                .then(([sessionData, statsData]) => {
                    if (sessionData.success) {
                        const userCredits = sessionData.user.credits;
                        creditsCount.textContent = userCredits;
                        currentCredits.textContent = userCredits;
                        availableChecks.textContent = userCredits;
                        initialUserData.credits = userCredits;
                        updateCreditsStatus(userCredits);
                    }
                    
                    if (statsData.success) {
                        if (statsData.used_credits !== undefined) {
                            document.getElementById('usedCredits').textContent = statsData.used_credits;
                        }
                        if (statsData.total_checks !== undefined) {
                            document.getElementById('totalChecks').textContent = statsData.total_checks;
                        }
                        
                        if (statsData.recent_keys) {
                            updateActivityTable(statsData.recent_keys);
                        }
                    }
                    
                    showToast('success', 'Actualizado', '#10b981');
                })
                .catch(error => {
                    console.error('Error refreshing data:', error);
                    showToast('error', 'Error al actualizar', '#ef4444');
                })
                .finally(() => {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i>';
                });
            }

            function logoutUser() {
                Swal.fire({
                    title: 'Cerrar Sesión',
                    text: '¿Estás seguro?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#9ca3af',
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'No',
                    background: '#1a202c',
                    color: '#ffffff',
                    customClass: {
                        container: 'mobile-swal'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch('auth.php?action=logout')
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.location.href = 'login.html';
                                }
                            })
                            .catch(error => {
                                console.error('Error logging out:', error);
                                window.location.href = 'login.html';
                            });
                    }
                });
            }

            // Funciones de administrador
            function loadAdminData() {
                loadRecentKeys();
                loadUsers();
            }

            function loadRecentKeys() {
                fetch('auth.php?action=get_credit_keys')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateKeysTable(data.keys);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading keys:', error);
                        showToast('error', 'Error cargando keys', '#ef4444');
                        keysTable.innerHTML = '<tr><td colspan="4" class="empty-message">Error</td></tr>';
                    });
            }

            function loadUsers() {
                fetch('auth.php?action=get_users')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateUsersTable(data.users);
                        }
                    })
                    .catch(error => {
                        console.error('Error loading users:', error);
                        showToast('error', 'Error cargando usuarios', '#ef4444');
                        usersTable.innerHTML = '<tr><td colspan="4" class="empty-message">Error</td></tr>';
                    });
            }

            function updateKeysTable(keys) {
                if (!keys || keys.length === 0) {
                    keysTable.innerHTML = '<tr><td colspan="4" class="empty-message">No hay keys</td></tr>';
                    return;
                }

                let html = '';
                keys.forEach(key => {
                    const statusClass = key.is_used ? 'status-inactive' : 'status-active';
                    const statusText = key.is_used ? 'Usada' : 'Activa';
                    const usedBy = key.used_by_username ? `por ${key.used_by_username}` : '';
                    // Escapar caracteres especiales para JavaScript
                    const safeKey = (key.credit_key || 'N/A').replace(/'/g, "\\'");
                    
                    html += `
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.25rem; flex-wrap: wrap;">
                                    <span class="key-display">${key.credit_key || 'N/A'}</td>
                                    <button class="copy-btn" onclick="copyToClipboard('${safeKey}')" title="Copiar">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                                ${usedBy ? `<div style="font-size: 0.6rem; color: var(--gray); margin-top: 0.125rem;">${usedBy}</div>` : ''}
                            </td>
                            <td style="color: var(--primary-light); font-weight: 600; font-size: 0.7rem;">${key.credits_amount || 0}</td>
                            <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                            <td>
                                <div class="table-actions">
                                    <button onclick="deleteKey('${safeKey}')" class="btn btn-danger btn-xs">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                keysTable.innerHTML = html;
            }

            function updateUsersTable(users) {
                if (!users || users.length === 0) {
                    usersTable.innerHTML = '<tr><td colspan="4" class="empty-message">No hay usuarios</td></tr>';
                    return;
                }

                let html = '';
                users.forEach(user => {
                    const role = user.is_admin ? 'Admin' : 'User';
                    const roleClass = user.is_admin ? 'status-admin' : 'status-active';
                    const creditColor = user.credits === 0 ? 'var(--error-light)' : 
                                      user.credits < 10 ? 'var(--warning-light)' : 'var(--success-light)';
                    
                    // Mostrar email abreviado para móviles
                    const shortEmail = user.email.length > 15 ? user.email.substring(0, 12) + '...' : user.email;
                    // Escapar comillas simples para JavaScript
                    const safeUsername = (user.username || '').replace(/'/g, "\\'");
                    const safeEmail = (user.email || '').replace(/'/g, "\\'");
                    
                    html += `
                        <tr>
                            <td style="font-weight: 600; font-size: 0.7rem;">${user.username}</td>
                            <td style="font-size: 0.7rem;" title="${user.email}">${shortEmail}</td>
                            <td style="color: ${creditColor}; font-weight: 600; font-size: 0.7rem;">${user.credits}</td>
                            <td>
                                <div class="table-actions">
                                    <button onclick="editUser(${user.id}, '${safeUsername}', '${safeEmail}', ${user.credits}, ${user.is_admin})" class="btn btn-secondary btn-xs">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="resetPassword(${user.id})" class="btn btn-primary btn-xs">
                                        <i class="fas fa-key"></i>
                                    </button>
                                    <button onclick="deleteUser(${user.id})" class="btn btn-danger btn-xs">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });

                usersTable.innerHTML = html;
            }

            window.editUser = function(id, username, email, credits, isAdmin) {
                editUserId.value = id;
                editUsername.value = username;
                editEmail.value = email;
                editCredits.value = credits;
                editIsAdmin.value = isAdmin ? '1' : '0';
                
                editUserModal.style.display = 'flex';
            }

            function saveUserChanges() {
                const userId = editUserId.value;
                const username = editUsername.value.trim();
                const email = editEmail.value.trim();
                const credits = parseInt(editCredits.value);
                const isAdmin = editIsAdmin.value === '1';

                if (!username || !email) {
                    showToast('error', 'Completa todos los campos', '#ef4444');
                    return;
                }

                saveUserBtn.disabled = true;
                saveUserBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>...</span>';

                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('username', username);
                formData.append('email', email);
                formData.append('credits', credits);
                formData.append('is_admin', isAdmin ? '1' : '0');

                fetch('auth.php?action=update_user', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message, '#10b981');
                        closeModal();
                        loadUsers();
                        
                        // Si es el usuario actual, actualizar datos
                        if (parseInt(userId) === parseInt(<?php echo $user_id; ?>)) {
                            refreshUserData();
                        }
                    } else {
                        showToast('error', data.message, '#ef4444');
                    }
                })
                .catch(error => {
                    console.error('Error updating user:', error);
                    showToast('error', 'Error al guardar', '#ef4444');
                })
                .finally(() => {
                    saveUserBtn.disabled = false;
                    saveUserBtn.innerHTML = '<i class="fas fa-save"></i><span>Guardar</span>';
                });
            }

            window.resetPassword = function(userId) {
                Swal.fire({
                    title: 'Resetear Contraseña',
                    text: '¿Estás seguro?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#9ca3af',
                    confirmButtonText: 'Sí',
                    cancelButtonText: 'No',
                    background: '#1a202c',
                    color: '#ffffff',
                    customClass: {
                        container: 'mobile-swal'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('user_id', userId);

                        fetch('auth.php?action=reset_password', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('success', data.message, '#10b981');
                            } else {
                                showToast('error', data.message, '#ef4444');
                            }
                        })
                        .catch(error => {
                            console.error('Error resetting password:', error);
                            showToast('error', 'Error al resetear', '#ef4444');
                        });
                    }
                });
            }

            window.deleteUser = function(userId) {
                Swal.fire({
                    title: 'Eliminar Usuario',
                    text: '¿Estás seguro?',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#EF4444',
                    cancelButtonColor: '#9ca3af',
                    confirmButtonText: 'Eliminar',
                    cancelButtonText: 'Cancelar',
                    background: '#1a202c',
                    color: '#ffffff',
                    customClass: {
                        container: 'mobile-swal'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const formData = new FormData();
                        formData.append('user_id', userId);

                        fetch('auth.php?action=delete_user', {
                            method: 'POST',
                            body: formData
                        })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', data.message, '#10b981');
                            loadUsers();
                        } else {
                            showToast('error', data.message, '#ef4444');
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting user:', error);
                        showToast('error', 'Error al eliminar', '#ef4444');
                    });
                }
            });
        }

        function closeModal() {
            editUserModal.style.display = 'none';
            editUserId.value = '';
            editUsername.value = '';
            editEmail.value = '';
            editCredits.value = '';
            editIsAdmin.value = '0';
        }

        function generateCreditKey() {
            const credits = parseInt(keyCredits.value);
            const expiryDays = parseInt(keyExpiry.value);

            if (!credits || credits < 100) {
                showToast('error', 'Mínimo 100 créditos', '#ef4444');
                return;
            }

            generateKeyBtn.disabled = true;
            generateKeyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>...</span>';

            const formData = new FormData();
            formData.append('credits_amount', credits);
            formData.append('expiry_days', expiryDays);

            fetch('auth.php?action=create_credit_key', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Key generada', '#10b981');
                    keyCredits.value = '';
                    loadRecentKeys();
                    
                    // Mostrar la key completa
                    Swal.fire({
                        title: 'Key Generada',
                        html: `
                            <div style="text-align: center;">
                                <div style="background: rgba(0,0,0,0.5); padding: 1rem; border-radius: 8px; margin: 1rem 0; border: 1px solid var(--primary); word-break: break-all;">
                                    <code style="font-size: 0.9rem; color: var(--primary-light);">${data.credit_key}</code>
                                </div>
                                <p style="font-size: 0.8rem; color: var(--gray);">Copia esta key</p>
                            </div>
                        `,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonText: 'Copiar',
                        cancelButtonText: 'Cerrar',
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: '#9ca3af',
                        background: '#1a202c',
                        color: '#ffffff',
                        customClass: {
                            container: 'mobile-swal'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            copyToClipboard(data.credit_key);
                            showToast('success', 'Copiada', '#10b981');
                        }
                    });
                } else {
                    showToast('error', data.message, '#ef4444');
                }
            })
            .catch(error => {
                console.error('Error generating key:', error);
                showToast('error', 'Error al generar', '#ef4444');
            })
            .finally(() => {
                generateKeyBtn.disabled = false;
                generateKeyBtn.innerHTML = '<i class="fas fa-plus"></i><span>Generar</span>';
            });
        }

        function openMultipleKeysModal() {
            multipleKeysModal.style.display = 'flex';
        }

        window.closeMultipleModal = function() {
            multipleKeysModal.style.display = 'none';
            document.getElementById('multipleCount').value = '5';
            document.getElementById('multipleCredits').value = '1000';
            document.getElementById('multipleExpiry').value = '30';
        }

        function generateMultipleCreditKeys() {
            const count = parseInt(document.getElementById('multipleCount').value);
            const credits = parseInt(document.getElementById('multipleCredits').value);
            const expiryDays = parseInt(document.getElementById('multipleExpiry').value);

            if (!count || count < 1 || count > 50) {
                showToast('error', 'Cantidad inválida (1-50)', '#ef4444');
                return;
            }

            if (!credits || credits < 100) {
                showToast('error', 'Mínimo 100 créditos', '#ef4444');
                return;
            }

            generateMultipleConfirmBtn.disabled = true;
            generateMultipleConfirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Generando...</span>';

            const formData = new FormData();
            formData.append('count', count);
            formData.append('credits_amount', credits);
            formData.append('expiry_days', expiryDays);

            fetch('auth.php?action=create_multiple_keys', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', `${count} keys generadas`, '#10b981');
                    closeMultipleModal();
                    loadRecentKeys();
                    
                    // Mostrar todas las keys generadas
                    const keysList = data.keys.map(key => `<div style="background: rgba(0,0,0,0.3); padding: 0.5rem; margin: 0.25rem 0; border-radius: 6px; border: 1px solid var(--primary);">
                        <code style="font-size: 0.8rem; color: var(--primary-light);">${key}</code>
                    </div>`).join('');
                    
                    Swal.fire({
                        title: 'Keys Generadas',
                        html: `
                            <div style="max-height: 300px; overflow-y: auto;">
                                <p style="margin-bottom: 1rem; color: var(--gray); font-size: 0.9rem;">${count} keys de ${credits} créditos</p>
                                ${keysList}
                                <p style="margin-top: 1rem; color: var(--gray); font-size: 0.8rem;">Puedes copiar cada key individualmente</p>
                            </div>
                        `,
                        width: '90%',
                        icon: 'success',
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#10b981',
                        background: '#1a202c',
                        color: '#ffffff',
                        customClass: {
                            container: 'mobile-swal'
                        }
                    });
                } else {
                    showToast('error', data.message, '#ef4444');
                }
            })
            .catch(error => {
                console.error('Error generating multiple keys:', error);
                showToast('error', 'Error al generar keys', '#ef4444');
            })
            .finally(() => {
                generateMultipleConfirmBtn.disabled = false;
                generateMultipleConfirmBtn.innerHTML = '<i class="fas fa-plus"></i><span>Generar Keys</span>';
            });
        }

        function refreshAdminData() {
            loadRecentKeys();
            loadUsers();
            showToast('success', 'Actualizado', '#10b981');
        }

        // Utilidades
        function formatDateMobile(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString('es-ES', { 
                day: '2-digit', 
                month: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function showToast(icon, message, color = '#10b981') {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                background: '#1a202c',
                color: '#ffffff',
                customClass: {
                    container: 'mobile-toast'
                }
            });
            
            Toast.fire({
                icon: icon,
                title: message,
                iconColor: color
            });
        }

        // Función para copiar al portapapeles
        window.copyToClipboard = function(text) {
            // Crear un elemento temporal para copiar
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            
            // Seleccionar y copiar
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                
                if (successful) {
                    showToast('success', 'Copiado', '#10b981');
                } else {
                    showToast('error', 'Error al copiar', '#ef4444');
                }
            } catch (err) {
                console.error('Error al copiar:', err);
                document.body.removeChild(textArea);
                
                // Fallback usando Clipboard API
                navigator.clipboard.writeText(text).then(() => {
                    showToast('success', 'Copiado', '#10b981');
                }).catch(clipboardErr => {
                    console.error('Error usando Clipboard API:', clipboardErr);
                    showToast('error', 'Error al copiar', '#ef4444');
                });
            }
        };

        window.deleteKey = function(key) {
            Swal.fire({
                title: '¿Eliminar Key?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EF4444',
                cancelButtonColor: '#9ca3af',
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
                background: '#1a202c',
                color: '#ffffff',
                customClass: {
                    container: 'mobile-swal'
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    try {
                        const formData = new FormData();
                        formData.append('key', key);

                        const response = await fetch('auth.php?action=delete_key', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            showToast('success', 'Key eliminada', '#10b981');
                            loadRecentKeys();
                        } else {
                            showToast('error', data.message || 'Error', '#ef4444');
                        }
                    } catch (error) {
                        console.error('Error eliminando key:', error);
                        showToast('error', 'Error de conexión', '#ef4444');
                    }
                }
            });
        };

        // Cerrar modal de múltiples keys al hacer clic fuera
        multipleKeysModal.addEventListener('click', (e) => {
            if (e.target === multipleKeysModal) {
                closeMultipleModal();
            }
        });
    });
</script>
</body>
</html>