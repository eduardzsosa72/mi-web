cat > setup-todo.sh << 'EOF'
#!/bin/bash

# Configuración
DOMAIN="goku-cheker.vpskraker.shop"
REPO="https://github.com/eduardzsosa72/mi-web.git"
EMAIL="admin@goku-cheker.vpskraker.shop"

echo "============================================"
echo "  CONFIGURACIÓN COMPLETA PARA: $DOMAIN"
echo "============================================"

# 1. Detener Apache
echo "[1/8] Deteniendo servicios..."
sudo systemctl stop apache2

# 2. Limpiar instalaciones previas
echo "[2/8] Limpiando configuraciones anteriores..."
sudo rm -rf /var/www/$DOMAIN 2>/dev/null
sudo rm -f /etc/apache2/sites-available/$DOMAIN.conf 2>/dev/null
sudo rm -f /etc/apache2/sites-enabled/$DOMAIN.conf 2>/dev/null
sudo rm -f /etc/apache2/sites-enabled/000-default.conf 2>/dev/null
sudo rm -rf /var/www/html/* 2>/dev/null

# 3. Instalar dependencias esenciales
echo "[3/8] Instalando dependencias..."
sudo apt update 2>/dev/null
sudo apt install -y apache2 certbot python3-certbot-apache curl git \
    software-properties-common php php-cli php-mysql php-curl \
    php-xml php-zip php-gd php-mbstring 2>/dev/null

# 4. Configurar módulos Apache
echo "[4/8] Configurando Apache..."
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod ssl

# 5. Crear directorio y clonar repositorio
echo "[5/8] Configurando sitio web..."
sudo mkdir -p /var/www/$DOMAIN/public_html
cd /var/www/$DOMAIN/public_html
sudo git clone $REPO . 2>/dev/null || sudo wget -qO- https://github.com/eduardzsosa72/mi-web/archive/refs/heads/main.tar.gz | sudo tar -xz --strip-components=1

# 6. Configuración Apache SIMPLE (sin errores)
echo "[6/8] Creando configuración Apache..."
sudo cat > /tmp/$DOMAIN.conf << 'APACHE'
<VirtualHost *:80>
    ServerName goku-cheker.vpskraker.shop
    ServerAlias www.goku-cheker.vpskraker.shop
    DocumentRoot /var/www/goku-cheker.vpskraker.shop/public_html
    
    ErrorLog ${APACHE_LOG_DIR}/goku-error.log
    CustomLog ${APACHE_LOG_DIR}/goku-access.log combined
    
    <Directory /var/www/goku-cheker.vpskraker.shop/public_html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>

<VirtualHost *:443>
    ServerName goku-cheker.vpskraker.shop
    ServerAlias www.goku-cheker.vpskraker.shop
    DocumentRoot /var/www/goku-cheker.vpskraker.shop/public_html
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
    SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
    
    ErrorLog ${APACHE_LOG_DIR}/goku-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/goku-ssl-access.log combined
    
    <Directory /var/www/goku-cheker.vpskraker.shop/public_html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
APACHE

sudo mv /tmp/$DOMAIN.conf /etc/apache2/sites-available/
sudo a2ensite $DOMAIN.conf

# 7. Configurar .htaccess básico
echo "[7/8] Configurando .htaccess..."
sudo cat > /var/www/$DOMAIN/public_html/.htaccess << 'HTACCESS'
# Configuración básica
RewriteEngine On

# Permitir acceso a todos los archivos PHP
<Files "*.php">
    Order Allow,Deny
    Allow from all
</Files>

# Front controller
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Protección básica
Options -Indexes

# Cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType text/css "access plus 1 week"
    ExpiresByType application/javascript "access plus 1 week"
</IfModule>
HTACCESS

# 8. Permisos
echo "[8/8] Aplicando permisos..."
sudo chown -R www-data:www-data /var/www/$DOMAIN
sudo chmod -R 755 /var/www/$DOMAIN

# Reiniciar Apache
sudo systemctl restart apache2

# 9. Verificar
echo ""
echo "============================================"
echo "  VERIFICACIÓN"
echo "============================================"
echo "Estado de Apache:"
sudo systemctl status apache2 --no-pager | head -10

echo ""
echo "Test local:"
curl -s -o /dev/null -w "Código HTTP: %{http_code}\n" http://localhost

echo ""
echo "============================================"
echo "  🎯 CONFIGURACIÓN COMPLETADA"
echo "============================================"
echo "Tu sitio está disponible en:"
echo "  → http://$DOMAIN"
echo "  → http://13.59.193.174"
echo ""
echo "Archivos en: /var/www/$DOMAIN/public_html"
echo "Logs en: /var/log/apache2/goku-*.log"
echo ""
echo "Para SSL real ejecuta después:"
echo "  sudo certbot --apache -d $DOMAIN"
echo "============================================"
EOF

# Dar permisos y ejecutar
chmod +x setup-todo.sh
sudo ./setup-todo.sh