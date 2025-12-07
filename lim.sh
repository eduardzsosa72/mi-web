cat > clean-everything.sh << 'EOF'
#!/bin/bash

# ============================================
# SCRIPT PARA LIMPIAR TODO - APACHE Y CONFIG
# ============================================

echo "⚠️  ⚠️  ⚠️  ADVERTENCIA: ESTO ELIMINARÁ TODO ⚠️  ⚠️  ⚠️"
echo "Se eliminarán:"
echo "1. Todas las configuraciones de Apache"
echo "2. Todos los sitios web configurados"
echo "3. Logs y archivos temporales"
echo "4. Configuraciones de dominio"
echo ""
read -p "¿Estás SEGURO que quieres continuar? (s/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "❌ Operación cancelada"
    exit 1
fi

echo "🧹 Comenzando limpieza total..."
echo "=========================================="

# 1. DETENER SERVICIOS
echo "[1/7] Deteniendo servicios..."
sudo systemctl stop apache2 2>/dev/null
sudo systemctl stop mysql 2>/dev/null
sudo systemctl stop nginx 2>/dev/null

# 2. DESINSTALAR PAQUETES (opcional)
echo "[2/7] ¿Quieres desinstalar Apache y MySQL completamente? (s/n): " 
read -p "" -n 1 -r
echo
if [[ $REPLY =~ ^[Ss]$ ]]; then
    echo "Desinstalando paquetes..."
    sudo apt remove --purge -y apache2 apache2-utils apache2-bin apache2-data \
        libapache2-mod-php* php* mysql-server mysql-client \
        certbot python3-certbot-apache 2>/dev/null
    sudo apt autoremove -y 2>/dev/null
    sudo apt clean 2>/dev/null
fi

# 3. ELIMINAR CONFIGURACIONES APACHE
echo "[3/7] Eliminando configuraciones Apache..."
# Sitios
sudo rm -rf /etc/apache2/sites-available/*
sudo rm -rf /etc/apache2/sites-enabled/*
sudo rm -rf /etc/apache2/conf-available/*
sudo rm -rf /etc/apache2/conf-enabled/*

# Módulos
sudo rm -rf /etc/apache2/mods-available/*
sudo rm -rf /etc/apache2/mods-enabled/*

# Configuración principal
sudo cp /etc/apache2/apache2.conf /etc/apache2/apache2.conf.backup.$(date +%Y%m%d)
sudo cat > /etc/apache2/apache2.conf << 'CONFIG'
# Configuración limpia de Apache
DefaultRuntimeDir ${APACHE_RUN_DIR}
PidFile ${APACHE_PID_FILE}
Timeout 300
KeepAlive On
MaxKeepAliveRequests 100
KeepAliveTimeout 5
User ${APACHE_RUN_USER}
Group ${APACHE_RUN_GROUP}
HostnameLookups Off
ErrorLog ${APACHE_LOG_DIR}/error.log
LogLevel warn
IncludeOptional mods-enabled/*.load
IncludeOptional mods-enabled/*.conf
Include ports.conf
IncludeOptional conf-enabled/*.conf
IncludeOptional sites-enabled/*.conf
CONFIG

# 4. ELIMINAR ARCHIVOS WEB
echo "[4/7] Eliminando archivos web..."
# Preguntar qué eliminar
echo "¿Qué directorios web quieres eliminar?"
echo "1. Solo /var/www/html"
echo "2. Todo /var/www"
echo "3. Solo dominios específicos"
read -p "Opción (1/2/3): " web_option

case $web_option in
    1)
        echo "Eliminando /var/www/html..."
        sudo rm -rf /var/www/html/*
        sudo mkdir -p /var/www/html
        sudo chown www-data:www-data /var/www/html
        ;;
    2)
        echo "Eliminando TODO /var/www..."
        sudo rm -rf /var/www/*
        sudo mkdir -p /var/www/html
        sudo chown www-data:www-data /var/www/html
        ;;
    3)
        echo "Dominios a eliminar (separados por espacio):"
        read -p "" domains
        for domain in $domains; do
            echo "Eliminando $domain..."
            sudo rm -rf "/var/www/$domain" 2>/dev/null
        done
        ;;
    *)
        echo "No se eliminarán archivos web"
        ;;
esac

# 5. ELIMINAR LOGS
echo "[5/7] Limpiando logs..."
sudo rm -f /var/log/apache2/*.log
sudo rm -f /var/log/apache2/*.gz
sudo touch /var/log/apache2/error.log
sudo touch /var/log/apache2/access.log
sudo chown root:adm /var/log/apache2/*.log

# 6. ELIMINAR CERTIFICADOS SSL
echo "[6/7] Eliminando certificados SSL..."
sudo rm -rf /etc/letsencrypt/live/*
sudo rm -rf /etc/letsencrypt/archive/*
sudo rm -rf /etc/letsencrypt/renewal/*
sudo rm -f /etc/ssl/certs/*.pem
sudo rm -f /etc/ssl/private/*.key

# 7. ELIMINAR CONFIGURACIONES PHP
echo "[7/7] Limpiando PHP..."
sudo rm -f /etc/php/*/apache2/conf.d/*.ini 2>/dev/null
sudo rm -f /etc/php/*/cli/conf.d/*.ini 2>/dev/null

# 8. CREAR CONFIGURACIÓN BÁSICA
echo "🔧 Creando configuración básica..."

# Puerto básico
sudo cat > /etc/apache2/ports.conf << 'PORTS'
Listen 80
<IfModule ssl_module>
    Listen 443
</IfModule>
<IfModule mod_gnutls.c>
    Listen 443
</IfModule>
PORTS

# Sitio por defecto MUY simple
sudo cat > /etc/apache2/sites-available/000-default.conf << 'SITE'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
    
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
SITE

# Crear página de prueba
sudo cat > /var/www/html/index.html << 'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Servidor Reiniciado</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 50px; 
            text-align: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: rgba(255,255,255,0.1);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        h1 { font-size: 3em; margin-bottom: 20px; }
        .success { color: #4CAF50; font-size: 5em; margin-bottom: 20px; }
        .info { margin: 20px 0; line-height: 1.6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✅</div>
        <h1>SERVIDOR REINICIADO</h1>
        <div class="info">
            <p>Apache ha sido limpiado completamente.</p>
            <p>IP: <?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></p>
            <p>Hora: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        <p>Todas las configuraciones previas han sido eliminadas.</p>
    </div>
</body>
</html>
HTML

# 9. PERMISOS
echo "🔐 Aplicando permisos..."
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo chmod 644 /var/www/html/index.html

# 10. REINICIAR SERVICIOS
echo "🔄 Reiniciando servicios..."
sudo a2dissite '*' 2>/dev/null
sudo a2ensite 000-default.conf
sudo a2enmod rewrite
sudo systemctl start apache2

# 11. VERIFICACIÓN FINAL
echo "=========================================="
echo "✅ LIMPIEZA COMPLETADA"
echo "=========================================="
echo "Servicios:"
sudo systemctl status apache2 --no-pager | head -5

echo -e "\nArchivos web en /var/www/html:"
ls -la /var/www/html/

echo -e "\nSitios configurados:"
sudo apache2ctl -S 2>/dev/null | grep "namevhost" || echo "Solo sitio por defecto"

echo -e "\nTest de conexión:"
curl -s -o /dev/null -w "HTTP Status: %{http_code}\n" http://localhost

echo -e "\n🌐 Acceso por:"
echo "   http://$(curl -s ifconfig.me)"
echo "   http://localhost"
echo "   http://127.0.0.1"

echo -e "\n⚠️  Backup de configuración original en:"
echo "   /etc/apache2/apache2.conf.backup.$(date +%Y%m%d)"
echo ""
echo "✅ Todo ha sido limpiado y reiniciado."
EOF

# Hacer ejecutable
chmod +x clean-everything.sh

echo "📄 Script creado: clean-everything.sh"
echo "📋 Para ejecutar: sudo ./clean-everything.sh"