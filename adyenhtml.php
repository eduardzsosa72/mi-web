<?php
error_reporting(0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>ADYEN | GOKU CHK</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap">
    <style>
        :root {
            --primary-dark: #0a0a0f;
            --secondary-dark: #121218;
            --card-bg: #16161e;
            --accent-color: #2a5a8c;
            --accent-light: #3a6fa3;
            --text-light: #c5d1e0;
            --text-secondary: #8a9db3;
            --success: #2c6e49;
            --danger: #9e2a2b;
            --warning: #b08d57;
            --info: #3a6ea5;
            --secondary: #4a4a55;
            --border-radius: 8px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--primary-dark);
            color: var(--text-light);
            font-family: 'JetBrains Mono', monospace;
            padding: 1.5rem;
            line-height: 1.6;
            position: relative;
            overflow-x: hidden;
        }

        /* Efecto de líneas de conexión */
        .network-lines {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.07;
            pointer-events: none;
        }

        /* Partículas flotantes */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: var(--accent-color);
            border-radius: 50%;
            opacity: 0.3;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .card {
            background: var(--card-bg);
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
            position: relative;
            border: 1px solid rgba(42, 90, 140, 0.15);
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--accent-color);
        }

        .card-header {
            background: rgba(42, 90, 140, 0.1);
            border-bottom: 1px solid rgba(42, 90, 140, 0.15);
            padding: 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        h3 {
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--accent-light);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        h3 i {
            color: var(--accent-light);
        }

        p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        strong {
            color: var(--text-light);
            font-weight: 500;
        }

        .nav-tabs {
            background: rgba(42, 90, 140, 0.1);
            border: none;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 0.5rem 0.5rem 0;
            display: flex;
            gap: 0.5rem;
        }

        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: none;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .nav-tabs .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--accent-color);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: -1;
        }

        .nav-tabs .nav-link:hover::before {
            opacity: 0.1;
        }

        .nav-tabs .nav-link.active {
            background: var(--card-bg);
            color: var(--accent-light);
            border-bottom: none;
        }

        .nav-tabs .nav-link.active::before {
            opacity: 0;
        }

        .nav-tabs .nav-link i {
            margin-right: 0.5rem;
        }

        .tab-content {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
        }

        textarea, .form-control {
            background: var(--secondary-dark);
            color: var(--text-light);
            border: 1px solid rgba(42, 90, 140, 0.2);
            border-radius: var(--border-radius);
            padding: 1rem;
            resize: none;
            transition: all 0.3s ease;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
        }

        textarea:focus, .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(42, 90, 140, 0.15);
            outline: none;
            background: rgba(18, 18, 24, 0.8);
        }

        .btn {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 500;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            border: none;
            background: var(--secondary-dark);
            color: var(--text-light);
            box-shadow: none;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.05);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: -1;
        }

        .btn:hover::before {
            transform: translateX(0);
        }

        .btn::after {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: var(--border-radius);
            background: transparent;
            border: 1px solid transparent;
            opacity: 0;
            z-index: -2;
            transition: all 0.3s ease;
        }

        .btn:hover::after {
            opacity: 1;
        }

        .btn-primary {
            background: var(--secondary-dark);
            border: 1px solid var(--accent-color);
        }

        .btn-primary::after {
            border-color: var(--accent-color);
            box-shadow: 0 0 10px rgba(42, 90, 140, 0.4);
        }

        .btn-success {
            background: var(--secondary-dark);
            border: 1px solid var(--success);
        }

        .btn-success::after {
            border-color: var(--success);
            box-shadow: 0 0 10px rgba(44, 110, 73, 0.4);
        }

        .btn-danger {
            background: var(--secondary-dark);
            border: 1px solid var(--danger);
        }

        .btn-danger::after {
            border-color: var(--danger);
            box-shadow: 0 0 10px rgba(158, 42, 43, 0.4);
        }

        .btn-warning {
            background: var(--secondary-dark);
            border: 1px solid var(--warning);
        }

        .btn-warning::after {
            border-color: var(--warning);
            box-shadow: 0 0 10px rgba(176, 141, 87, 0.4);
        }

        .btn-info {
            background: var(--secondary-dark);
            border: 1px solid var(--info);
        }

        .btn-info::after {
            border-color: var(--info);
            box-shadow: 0 0 10px rgba(58, 110, 165, 0.4);
        }

        .btn-secondary {
            background: var(--secondary-dark);
            border: 1px solid var(--secondary);
        }

        .btn-secondary::after {
            border-color: var(--secondary);
            box-shadow: 0 0 10px rgba(74, 74, 85, 0.4);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn:disabled::before {
            display: none;
        }

        .buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin-top: 1.5rem;
        }

        .status-bar {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            margin: 1rem 0 1.5rem;
            background: rgba(42, 90, 140, 0.05);
            padding: 1rem;
            border-radius: var(--border-radius);
            border: 1px solid rgba(42, 90, 140, 0.1);
        }

        .status-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-item i {
            font-size: 1.2rem;
        }

        .status-item.lives i {
            color: var(--success);
        }

        .status-item.dies i {
            color: var(--danger);
        }

        .status-item.errors i {
            color: var(--warning);
        }

        .status-item.tested i {
            color: var(--info);
        }

        .status-item.total i {
            color: var(--accent-light);
        }

        .result-box {
            max-height: 300px;
            overflow-y: auto;
            padding: 1rem;
            background: var(--secondary-dark);
            border-radius: var(--border-radius);
            border: 1px solid rgba(42, 90, 140, 0.1);
            font-size: 0.9rem;
            line-height: 1.8;
        }

        .result-box::-webkit-scrollbar {
            width: 8px;
        }

        .result-box::-webkit-scrollbar-track {
            background: var(--secondary-dark);
            border-radius: 4px;
        }

        .result-box::-webkit-scrollbar-thumb {
            background: var(--accent-color);
            border-radius: 4px;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.85rem;
        }

        .badge-success {
            background: var(--success);
            color: var(--text-light);
        }

        .badge-danger {
            background: var(--danger);
            color: var(--text-light);
        }

        .badge-warning {
            background: var(--warning);
            color: var(--text-light);
        }

        .badge-info {
            background: var(--info);
            color: var(--text-light);
        }

        .badge-secondary {
            background: var(--secondary);
            color: var(--text-light);
        }

        .user-info {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .user-info-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .user-info-item i {
            color: var(--accent-light);
        }

        /* Personalización de Toastr */
        #toast-container > .toast {
            background-image: none !important;
            padding: 15px 15px 15px 50px;
            width: 350px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            opacity: 1;
            font-family: 'JetBrains Mono', monospace;
        }

        #toast-container > .toast:before {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 24px;
        }

        #toast-container > .toast-success {
            background: var(--success);
        }
        #toast-container > .toast-success:before {
            content: '\f00c';
        }

        #toast-container > .toast-error {
            background: var(--danger);
        }
        #toast-container > .toast-error:before {
            content: '\f00d';
        }

        #toast-container > .toast-info {
            background: var(--info);
        }
        #toast-container > .toast-info:before {
            content: '\f129';
        }

        #toast-container > .toast-warning {
            background: var(--warning);
        }
        #toast-container > .toast-warning:before {
            content: '\f071';
        }

        /* Corrigiendo el texto del toast */
        .toast-message {
            color: white !important;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        /* Selección de API */
        .api-selection {
            background: rgba(42, 90, 140, 0.05);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(42, 90, 140, 0.1);
        }

        .api-selection h5 {
            margin-bottom: 1rem;
            color: var(--accent-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .api-options {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .api-option {
            position: relative;
            padding-left: 2rem;
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
        }

        .api-option input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkmark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background-color: var(--secondary-dark);
            border: 1px solid rgba(42, 90, 140, 0.2);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .api-option:hover input ~ .checkmark {
            background-color: rgba(42, 90, 140, 0.1);
        }

        .api-option input:checked ~ .checkmark {
            background-color: var(--accent-color);
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .api-option input:checked ~ .checkmark:after {
            display: block;
        }

        .api-option .checkmark:after {
            left: 7px;
            top: 3px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        /* Selección de hilos */
        .thread-selection {
            margin-top: 1rem;
        }

        .thread-selection label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--accent-light);
        }

        .thread-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: var(--secondary-dark);
            outline: none;
            margin-bottom: 1rem;
        }

        .thread-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--accent-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .thread-slider::-webkit-slider-thumb:hover {
            background: var(--accent-light);
            box-shadow: 0 0 10px rgba(42, 90, 140, 0.3);
        }

        .thread-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--accent-color);
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .thread-slider::-moz-range-thumb:hover {
            background: var(--accent-light);
            box-shadow: 0 0 10px rgba(42, 90, 140, 0.3);
        }

        .thread-value {
            text-align: center;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--accent-light);
            margin-bottom: 1rem;
        }

        /* Pie de página */
        .footer {
            text-align: center;
            margin-top: 3rem;
            padding: 1.5rem;
            background: rgba(42, 90, 140, 0.05);
            border-radius: var(--border-radius);
            border: 1px solid rgba(42, 90, 140, 0.1);
        }

        .footer p {
            margin-bottom: 0.5rem;
        }

        .footer a {
            color: var(--accent-light);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .footer a:hover {
            color: var(--text-light);
        }

        .footer .social-icon {
            font-size: 1.5rem;
            margin: 0 0.5rem;
        }

        /* Efecto de pulso suave */
        @keyframes softPulse {
            0% { opacity: 0.7; }
            50% { opacity: 1; }
            100% { opacity: 0.7; }
        }

        .soft-pulse {
            animation: softPulse 3s infinite ease-in-out;
        }

        /* Efecto de conexión de nodos */
        .node {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--accent-color);
            border-radius: 50%;
            opacity: 0.3;
        }

        /* Efecto de serpiente */
        .snake {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--accent-color), transparent);
            opacity: 0.1;
            animation: snakeMove 15s infinite linear;
        }

        @keyframes snakeMove {
            0% { transform: translateY(0) scaleX(0.8); }
            50% { transform: translateY(30px) scaleX(1.2); }
            100% { transform: translateY(0) scaleX(0.8); }
        }

        /* Efecto de caja pulsante */
        .pulse-box {
            position: absolute;
            width: 40px;
            height: 40px;
            border: 1px solid var(--accent-color);
            opacity: 0.1;
            animation: pulseBox 10s infinite ease-in-out;
        }

        @keyframes pulseBox {
            0% { transform: scale(1) rotate(0deg); opacity: 0.05; }
            50% { transform: scale(1.5) rotate(45deg); opacity: 0.1; }
            100% { transform: scale(1) rotate(0deg); opacity: 0.05; }
        }

        /* Animación en vivo */
        @keyframes liveFlash {
            0% { background-color: rgba(44, 110, 73, 0); }
            50% { background-color: rgba(44, 110, 73, 0.2); }
            100% { background-color: rgba(44, 110, 73, 0); }
        }

        .live-flash {
            animation: liveFlash 1s ease-out;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }

            .buttons {
                flex-direction: column;
            }

            .nav-tabs {
                flex-wrap: nowrap;
                overflow-x: auto;
                padding: 0.5rem;
            }

            .nav-tabs .nav-link {
                padding: 0.5rem 1rem;
                white-space: nowrap;
            }

            .status-bar {
                flex-direction: column;
                gap: 0.75rem;
            }

            .user-info {
                flex-direction: column;
                gap: 0.75rem;
            }
        }

        .back-btn {
            font-size: 0.85rem;
            padding: 0.5rem 1rem;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .back-btn:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="network-lines"></div>
    <div class="particles"></div>
    <input type="hidden" value="<?php echo $base64Value ?? ''; ?>" name="token_api" id="token_api">
    
    <div class="container">
        <a href="../../" class="btn btn-secondary mb-4 back-btn">
            <i class="fas fa-arrow-left"></i> Volver al Dashboard
        </a>

        <div class="card">
            <div class="card-header">
                <h3>
                    <i class="fas fa-shield-alt"></i> CHECKER ALLBINS PRE-AUTH [GATE AMAZON]
                </h3>
                
                <div class="user-info">
                    <div class="user-info-item">
                        <i class="fas fa-user"></i>
                        <span>Usuario: <strong><?php echo $username ?? 'N/A'; ?></strong></span>
                    </div>
                    <div class="user-info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Expira en: <strong><?php echo $expira ?? 'N/A'; ?></strong></span>
                    </div>
                    <div class="user-info-item">
                        <i class="fas fa-clock"></i>
                        <span>Último Login: <strong><?php echo $lastLogin ?? 'N/A'; ?></strong></span>
                    </div>
                </div>

                <div class="api-selection">
                    <h5><i class="fas fa-server"></i> Selecciona la API</h5>
                    <div class="api-options">
                        <label class="api-option">
                            <input type="radio" name="api" value="adyen.php" data-name="ALLBINS (ADYEN)">
                            <span class="checkmark"></span>
                            (ADYEN)
                        </label>
                    </div>
                </div>

                <div class="thread-selection">
                    <label for="thread-slider"><i class="fas fa-bolt"></i> Hilos Simultáneos</label>
                    <input type="range" min="1" max="4" value="1" class="thread-slider" id="thread-slider">
                    <div class="thread-value" id="thread-value">1x</div>
                </div>

                <div class="buttons">
                    <button class="btn btn-success" id="chk-start" disabled>
                        <i class="fas fa-play"></i> Iniciar
                    </button>
                    <button class="btn btn-warning" id="chk-pause" disabled>
                        <i class="fas fa-pause"></i> Pausar
                    </button>
                    <button class="btn btn-danger" id="chk-stop" disabled>
                        <i class="fas fa-stop"></i> Parar
                    </button>
                    <button class="btn btn-secondary" id="chk-clean">
                        <i class="fas fa-trash-alt"></i> Limpiar
                    </button>
                </div>

                <div class="mt-4">
                    <span class="badge badge-warning" id="estatus">
                        <i class="fas fa-hourglass-start"></i> Esperando inicio...
                    </span>
                </div>
            </div>
        </div>

        <div class="card">
            <ul class="nav nav-tabs" id="myTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#chk-home">
                        <i class="far fa-credit-card"></i> Tarjetas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#chk-lives">
                        <i class="fas fa-check-circle"></i> Aprobadas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#chk-dies">
                        <i class="fas fa-times-circle"></i> Rechazadas
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#chk-errors">
                        <i class="fas fa-exclamation-triangle"></i> Errores
                    </a>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="chk-home">
                    <div class="status-bar">
                        <div class="status-item lives">
                            <i class="fas fa-check-circle"></i>
                            <span>Aprobadas: <strong class="val-lives">0</strong></span>
                        </div>
                        <div class="status-item dies">
                            <i class="fas fa-times-circle"></i>
                            <span>Rechazadas: <strong class="val-dies">0</strong></span>
                        </div>
                        <div class="status-item errors">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>Errores: <strong class="val-errors">0</strong></span>
                        </div>
                        <div class="status-item tested">
                            <i class="fas fa-vial"></i>
                            <span>Testeadas: <strong class="val-tested">0</strong></span>
                        </div>
                        <div class="status-item total">
                            <i class="fas fa-list"></i>
                            <span>Total: <strong class="val-total">0</strong></span>
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label for="lista_cartoes">
                            <i class="fas fa-list-ul"></i> Lista de Tarjetas
                        </label>
                        <textarea id="lista_cartoes" class="form-control" rows="10" placeholder="Inserta tu lista en formato: NÚMERO|MES|AÑO|CVV"></textarea>
                    </div>
                </div>

                <div class="tab-pane fade" id="chk-lives">
                    <h5 class="mb-3">
                        <i class="fas fa-check-circle text-success"></i> Aprobadas
                    </h5>
                    <p>Total: <span class="val-lives">0</span></p>
                    
                    <div class="buttons mb-3">
                        <button class="btn btn-primary" id="copyButton">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                        <button class="btn btn-danger" onclick="apagarValoresLives()">
                            <i class="fas fa-trash-alt"></i> Limpiar
                        </button>
                    </div>
                    
                    <div id="lives" class="result-box"></div>
                </div>

                <div class="tab-pane fade" id="chk-dies">
                    <h5 class="mb-3">
                        <i class="fas fa-times-circle text-danger"></i> Rechazadas
                    </h5>
                    <p>Total: <span class="val-dies">0</span></p>
                    
                    <div class="buttons mb-3">
                        <button class="btn btn-danger" onclick="apagarValoresDies()">
                            <i class="fas fa-trash-alt"></i> Limpiar
                        </button>
                    </div>
                    
                    <div id="dies" class="result-box"></div>
                </div>

                <div class="tab-pane fade" id="chk-errors">
                    <h5 class="mb-3">
                        <i class="fas fa-exclamation-triangle text-warning"></i> Errores
                    </h5>
                    <p>Total: <span class="val-errors">0</span></p>
                    
                    <div class="buttons mb-3">
                        <button class="btn btn-warning" onclick="apagarValoresErrors()">
                            <i class="fas fa-trash-alt"></i> Limpiar
                        </button>
                    </div>
                    
                    <div id="errors" class="result-box"></div>
                </div>
            </div>
        </div>

        <footer class="footer">
            <p>© 2024 GOKUCHK - Todos los derechos reservados</p>
            <p>Desarrollado por <a href="" target="_blank"></a></p>
            <div class="social-links">
                <a href="https://t.me/pladixoficial" target="_blank" class="social-icon"><i class="fab fa-telegram"></i></a>
            </div>
        </footer>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        var custo = "0";
        var descricao_chk = "CheckerADYEN";
        var audio = new Audio('live.mp3');
        var selectedApi = "";
        var threadCount = 1;
        var activeThreads = 0;
        var maxThreads = 1;

        // Configuración personalizada de Toastr
        toastr.options = {
            closeButton: true,
            debug: false,
            newestOnTop: true,
            progressBar: true,
            positionClass: "toast-top-right",
            preventDuplicates: false,
            onclick: null,
            showDuration: "300",
            hideDuration: "1000",
            timeOut: "5000",
            extendedTimeOut: "1000",
            showEasing: "swing",
            hideEasing: "linear",
            showMethod: "fadeIn",
            hideMethod: "fadeOut"
        };

        // Crear efectos visuales de conexión
        function createNetworkEffects() {
            const networkLines = document.createElement('div');
            networkLines.className = 'network-lines';
            document.body.appendChild(networkLines);

            // Crear nodos
            for (let i = 0; i < 20; i++) {
                const node = document.createElement('div');
                node.className = 'node';
                node.style.left = Math.random() * 100 + '%';
                node.style.top = Math.random() * 100 + '%';
                networkLines.appendChild(node);
            }

            // Crear serpientes
            for (let i = 0; i < 5; i++) {
                const snake = document.createElement('div');
                snake.className = 'snake';
                snake.style.left = Math.random() * 100 + '%';
                snake.style.top = Math.random() * 100 + '%';
                snake.style.width = (Math.random() * 200 + 100) + 'px';
                snake.style.animationDelay = (Math.random() * 5) + 's';
                networkLines.appendChild(snake);
            }

            // Crear cajas pulsantes
            for (let i = 0; i < 8; i++) {
                const pulseBox = document.createElement('div');
                pulseBox.className = 'pulse-box';
                pulseBox.style.left = Math.random() * 100 + '%';
                pulseBox.style.top = Math.random() * 100 + '%';
                pulseBox.style.animationDelay = (Math.random() * 5) + 's';
                networkLines.appendChild(pulseBox);
            }

            // Crear partículas
            const particles = document.createElement('div');
            particles.className = 'particles';
            document.body.appendChild(particles);

            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle soft-pulse';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                particle.style.animationDelay = (Math.random() * 3) + 's';
                particles.appendChild(particle);
            }
        }

        // Inicializar efectos visuales
        document.addEventListener('DOMContentLoaded', createNetworkEffects);

        function apagarValoresLives() { 
            document.getElementById("lives").innerHTML = ""; 
            toastr.info("Lista de aprobadas limpiada exitosamente!");
        }
        
        function apagarValoresDies() { 
            document.getElementById("dies").innerHTML = ""; 
            toastr.info("Lista de rechazadas limpiada exitosamente!");
        }
        
        function apagarValoresErrors() { 
            document.getElementById("errors").innerHTML = ""; 
            toastr.info("Lista de errores limpiada exitosamente!");
        }

        $(document).ready(function() {
            var testadas = [], total = 0, tested = 0, lives = 0, dies = 0, errors = 0;
            var stopped = true, paused = true;
            var token_api = $("#token_api").val();
            var processingQueue = [];

            // Control deslizante de hilos
            var slider = document.getElementById("thread-slider");
            var output = document.getElementById("thread-value");
            
            slider.oninput = function() {
                threadCount = parseInt(this.value);
                output.innerHTML = threadCount + "x";
                maxThreads = threadCount;
            }

            // Selección de API
            $('input[name="api"]').change(function() {
                selectedApi = $(this).val();
                var apiName = $(this).data('name');
                $("#chk-start").prop("disabled", false);
                toastr.info("API seleccionada: " + apiName);
            });

            function removelinha() {
                var lines = $("#lista_cartoes").val().split('\n');
                lines.shift();
                $("#lista_cartoes").val(lines.join('\n'));
            }

            function updateStats() {
                $(".val-total").text(total);
                $(".val-lives").text(lives);
                $(".val-dies").text(dies);
                $(".val-errors").text(errors);
                $(".val-tested").text(tested);
            }

            function processNextInQueue() {
                if (processingQueue.length > 0 && activeThreads < maxThreads && !stopped && !paused) {
                    var item = processingQueue.shift();
                    testarCartao(item);
                }
            }

            function testarCartao(conteudo) {
                if (stopped || paused) return;

                activeThreads++;

                $.ajax({
                    url: selectedApi,
                    type: 'GET',
                    data: { lista: conteudo, token_api: token_api }
                }).done(function(response) {
                    if (stopped || paused) {
                        activeThreads--;
                        return;
                    }

                    tested++;
                    if (response.indexOf("Aprovada") >= 0) {
                        lives++;
                        $("#estatus").attr("class", "badge badge-success").html(`<i class="fas fa-check"></i> ${conteudo} -> LIVE`);
                        toastr.success(`¡Aprobada! ${conteudo}`);
                        $("#lives").append(response + "<br>");
                        $("#lives").addClass("live-flash");
                        setTimeout(function() {
                            $("#lives").removeClass("live-flash");
                        }, 1000);
                        audio.play();
                    } else if (response.indexOf("Reprovada") >= 0) {
                        dies++;
                        $("#estatus").attr("class", "badge badge-danger").html(`<i class="fas fa-times"></i> ${conteudo} -> DIE`);
                        toastr.error(`¡Rechazada! ${conteudo}`);
                        $("#dies").append(response + "<br>");
                    } else {
                        errors++;
                        $("#estatus").attr("class", "badge badge-warning").html(`<i class="fas fa-exclamation-triangle"></i> ${conteudo} -> ERROR`);
                        toastr.warning(`¡Ocurrió un error! ${conteudo}`);
                        $("#errors").append(response + "<br>");
                    }

                    updateStats();
                    activeThreads--;
                    
                    // Verificar si todas las pruebas fueron concluidas
                    if (tested >= total && activeThreads === 0) {
                        $("#estatus").attr("class", "badge badge-success").html('<i class="fas fa-check"></i> Prueba finalizada');
                        toastr.success(`¡Prueba de ${total} items finalizada con éxito!`);
                        $("#chk-start").removeAttr('disabled');
                        $("#chk-clean").removeAttr('disabled');
                        $("#chk-stop, #chk-pause").attr("disabled", "true");
                        stopped = true;
                    } else {
                        processNextInQueue();
                    }
                }).fail(function() {
                    errors++;
                    updateStats();
                    toastr.error("¡Error en la solicitud al servidor!");
                    activeThreads--;
                    processNextInQueue();
                });
            }

            function testar(tested, total, lista) {
                if (stopped || paused || tested >= total) {
                    if (tested >= total) {
                        $("#estatus").attr("class", "badge badge-success").html('<i class="fas fa-check"></i> Prueba finalizada');
                        toastr.success(`¡Prueba de ${total} items finalizada con éxito!`);
                        $("#chk-start").removeAttr('disabled');
                        $("#chk-clean").removeAttr('disabled');
                        $("#chk-stop, #chk-pause").attr("disabled", "true");
                    }
                    return false;
                }

                // Limpiar la cola de procesamiento
                processingQueue = [];
                
                // Agregar todas las tarjetas a la cola
                for (var i = 0; i < lista.length; i++) {
                    processingQueue.push(lista[i]);
                }
                
                // Iniciar hilos de acuerdo con el número seleccionado
                for (var i = 0; i < maxThreads; i++) {
                    processNextInQueue();
                }
            }

            $("#chk-start").click(function() {
                if (!selectedApi) {
                    toastr.warning("¡Por favor, selecciona una API primero!");
                    return;
                }

                var lista = $("#lista_cartoes").val().trim().split('\n');
                if (!lista[0]) {
                    toastr.warning("¡Por favor, inserta una lista de tarjetas!");
                    return $("#lista_cartoes").focus();
                }

                total = lista.length;
                stopped = paused = false;
                tested = lives = dies = errors = 0;
                activeThreads = 0;
                updateStats();
                toastr.success(`¡Checker iniciado con ${maxThreads} hilos! Buena suerte`);
                $("#estatus").attr("class", "badge badge-success").html('<i class="fas fa-cog fa-spin"></i> Checker iniciado, espera...');
                $("#chk-stop, #chk-pause").removeAttr('disabled');
                $("#chk-start, #chk-clean").attr("disabled", "true");
                testar(0, total, lista);
            });

            $("#chk-pause").click(function() {
                paused = true;
                $("#chk-start").removeAttr('disabled');
                $("#chk-pause").attr("disabled", "true");
                toastr.info("¡Checker pausado! Haz clic en Iniciar para continuar.");
                $("#estatus").attr("class", "badge badge-info").html('<i class="fas fa-pause"></i> Checker pausado...');
            });

            $("#chk-stop").click(function() {
                stopped = true;
                $("#chk-start").removeAttr('disabled');
                $("#chk-clean").removeAttr('disabled');
                $("#chk-stop, #chk-pause").attr("disabled", "true");
                toastr.info("¡Checker detenido! Todos los procesos fueron interrumpidos.");
                $("#estatus").attr("class", "badge badge-secondary").html('<i class="fas fa-stop"></i> Checker detenido...');
            });

            $("#chk-clean").click(function() {
                testadas = []; total = tested = lives = dies = errors = 0;
                stopped = true;
                updateStats();
                $("#lista_cartoes").val("");
                $("#lives, #dies, #errors").empty();
                toastr.info("¡Checker limpiado! Todos los datos fueron reiniciados.");
                $("#estatus").attr("class", "badge badge-warning").html('<i class="fas fa-hourglass-start"></i> Esperando inicio...');
            });

            $("#copyButton").click(function() {
                var range = document.createRange();
                range.selectNode(document.getElementById("lives"));
                window.getSelection().removeAllRanges();
                window.getSelection().addRange(range);
                try {
                    document.execCommand('copy');
                    toastr.success("¡Aprobadas copiadas al portapapeles!");
                } catch (err) {
                    toastr.error("¡Error al copiar!");
                    console.error('Error al copiar: ', err);
                }
                window.getSelection().removeAllRanges();
            });
        });
    </script>
</body>
</html>