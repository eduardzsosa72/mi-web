#!/bin/bash

# Configuración específica para goku-cheker.vpskraker.shop
DOMAIN="goku-cheker.vpskraker.shop"
REPO="https://github.com/eduardzsosa72/mi-web.git"
IP_SERVER="13.59.193.174"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${GREEN}=== CONFIGURACIÓN PARA: $DOMAIN ===${NC}"
echo -e "${YELLOW}IP del servidor: $IP_SERVER${NC}"
echo -e "${YELLOW}Repositorio: $REPO${NC}"

# 1. Verificar DNS
echo -e "\n${GREEN}[1/7] Verificando DNS...${NC}"
echo "Tu dominio debe apuntar a: $IP_SERVER"
echo "Verificando..."
nslookup $DOMAIN 2>/dev/null || dig $DOMAIN +short

read -p "¿Los DNS están configurados? (s/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}Configura estos registros DNS:${NC}"
    echo "A     goku-cheker.vpskraker.shop    $IP_SERVER"
    echo "A     www.goku-cheker.vpskraker.shop $IP_SERVER"
    echo -e "${YELLOW}Continúa cuando estén configurados${NC}"
fi

# 2. Actualizar e instalar
echo -e "\n${GREEN}[2/7] Instalando dependencias...${NC}"
sudo apt update
sudo apt install -y apache2 certbot python3-certbot-apache \
    curl git software-properties-common \
    php php-cli php-mysql php-curl php-xml php-zip php-gd php-mbstring

# 3. Configurar Apache
echo -e "\n${GREEN}[3/7] Configurando Apache...${NC}"

# Crear directorio
sudo mkdir -p /var/www/$DOMAIN/public_html

# Configuración del sitio
sudo cat > /tmp/$DOMAIN.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot /var/www/$DOMAIN/public_html
    
    ErrorLog \${APACHE_LOG_DIR}/$DOMAIN-error.log
    CustomLog \${APACHE_LOG_DIR}/$DOMAIN-access.log combined
    
    <Directory /var/www/$DOMAIN/public_html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Cabeceras de seguridad
        Header always set X-Content-Type-Options "nosniff"
        Header always set X-Frame-Options "SAMEORIGIN"
        Header always set X-XSS-Protection "1; mode=block"
        Header always set Referrer-Policy "strict-origin-when-cross-origin"
    </Directory>
    
    # Redirección www a no-www
    RewriteEngine On
    RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
    RewriteRule ^(.*)$ http://%1/\$1 [R=301,L]
</VirtualHost>
EOF

sudo mv /tmp/$DOMAIN.conf /etc/apache2/sites-available/
sudo a2dissite 000-default.conf 2>/dev/null
sudo a2ensite $DOMAIN.conf

# 4. Clonar tu repositorio
echo -e "\n${GREEN}[4/7] Clonando tu web...${NC}"
cd /var/www/$DOMAIN/public_html
sudo rm -rf ./* ./.git 2>/dev/null

# Clonar repositorio
sudo git clone $REPO /tmp/mi-web-temp
sudo cp -r /tmp/mi-web-temp/* ./
sudo cp -r /tmp/mi-web-temp/.* . 2>/dev/null || true
sudo rm -rf /tmp/mi-web-temp

# Verificar estructura
echo "Contenido del repositorio:"
ls -la

# 5. Configurar .htaccess para tu caso específico
echo -e "\n${GREEN}[5/7] Configurando .htaccess...${NC}"

sudo cat > /var/www/$DOMAIN/public_html/.htaccess << 'EOF'
# Configuración para Goku Checker
RewriteEngine On

# IMPORTANTE: Temporalmente SIN forzar HTTPS (lo haremos después del SSL)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]

# Redirigir www a no-www
RewriteCond %{HTTP_HOST} ^www\.(.+)$ [NC]
RewriteRule ^(.*)$ http://%1/$1 [R=301,L]

# Front controller - SI tu app usa index.php como router
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]

# Protección
Options -Indexes

# Bloquear acceso a archivos sensibles
<FilesMatch "\.(env|log|ini|config|sql|bak|save|sh)$">
    Deny from all
</FilesMatch>

<Files ".ht*">
    Deny from all
</Files>

# Permitir acceso a auth.php, admin.php, redeem.php explícitamente
<FilesMatch "^(auth|admin|redeem|index|api)\.php$">
    Allow from all
</FilesMatch>

# Cache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType application/pdf "access plus 1 month"
</IfModule>

# Compresión
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
EOF

# 6. Obtener certificado SSL
echo -e "\n${GREEN}[6/7] Obteniendo certificado SSL...${NC}"

# Reiniciar Apache primero
sudo systemctl restart apache2

# Intentar obtener certificado
echo -e "${YELLOW}Intentando obtener certificado SSL de Let's Encrypt...${NC}"
echo "Si falla, puedes intentar manualmente después."

if sudo certbot --apache -d $DOMAIN -d www.$DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN 2>/dev/null; then
    echo -e "${GREEN}✓ Certificado SSL instalado${NC}"
    
    # Habilitar HTTPS en .htaccess
    sudo sed -i 's/# RewriteCond %{HTTPS} off/RewriteCond %{HTTPS} off/' /var/www/$DOMAIN/public_html/.htaccess
    sudo sed -i 's/# RewriteRule ^(.*)$ https/RewriteRule ^(.*)$ https/' /var/www/$DOMAIN/public_html/.htaccess
    
    # Configurar redirección en Apache también
    sudo cat > /tmp/ssl-redirect.conf << 'EOF'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
</IfModule>
EOF
    sudo mv /tmp/ssl-redirect.conf /etc/apache2/conf-available/ssl-redirect.conf
    sudo a2enconf ssl-redirect
else
    echo -e "${YELLOW}⚠️  No se pudo obtener certificado automáticamente${NC}"
    echo "Puedes obtenerlo manualmente después con:"
    echo "sudo certbot --apache -d $DOMAIN"
fi

# 7. Aplicar permisos y finalizar
echo -e "\n${GREEN}[7/7] Aplicando permisos...${NC}"

sudo chown -R www-data:www-data /var/www/$DOMAIN
sudo chmod -R 755 /var/www/$DOMAIN
sudo find /var/www/$DOMAIN -type f -exec chmod 644 {} \;

# Permisos especiales para archivos de escritura si existen
[ -f "/var/www/$DOMAIN/public_html/data.json" ] && sudo chmod 666 /var/www/$DOMAIN/public_html/data.json 2>/dev/null
[ -f "/var/www/$DOMAIN/public_html/logs.txt" ] && sudo chmod 666 /var/www/$DOMAIN/public_html/logs.txt 2>/dev/null

# Reiniciar servicios
sudo systemctl restart apache2

# 8. Verificación final
echo -e "\n${GREEN}=== VERIFICACIÓN ===${NC}"

echo -e "\n${YELLOW}1. Estado de Apache:${NC}"
sudo systemctl status apache2 --no-pager -l

echo -e "\n${YELLOW}2. Test de conexión:${NC}"
echo "Probando HTTP..."
curl -I http://$DOMAIN 2>/dev/null | head -5 || echo "Error en HTTP"

if [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    echo "Probando HTTPS..."
    curl -Ik https://$DOMAIN 2>/dev/null | head -5 || echo "Error en HTTPS"
fi

echo -e "\n${YELLOW}3. Estructura de archivos:${NC}"
ls -la /var/www/$DOMAIN/public_html/

echo -e "\n${YELLOW}4. Configuración de sitio:${NC}"
sudo apache2ctl -S 2>/dev/null | grep $DOMAIN

echo -e "\n${GREEN}=== RESUMEN COMPLETO ===${NC}"
echo -e "✅ Dominio: $DOMAIN"
echo -e "✅ Directorio: /var/www/$DOMAIN/public_html"
echo -e "✅ Repositorio: $REPO"
echo -e "✅ Apache configurado y corriendo"

if [ -f "/etc/letsencrypt/live/$DOMAIN/fullchain.pem" ]; then
    echo -e "✅ SSL Let's Encrypt INSTALADO"
    echo -e "\n🔗 URLs SEGURAS:"
    echo -e "   🔐 https://$DOMAIN"
    echo -e "   🔐 https://www.$DOMAIN"
else
    echo -e "⚠️  SSL PENDIENTE (ejecuta cuando DNS esté propagado):"
    echo -e "   sudo certbot --apache -d $DOMAIN -d www.$DOMAIN"
    echo -e "\n🔗 URL temporal:"
    echo -e "   http://$DOMAIN"
fi

echo -e "\n${YELLOW}Comandos útiles:${NC}"
echo "Ver logs:           sudo tail -f /var/log/apache2/$DOMAIN-*.log"
echo "Reiniciar Apache:   sudo systemctl restart apache2"
echo "Renovar SSL:        sudo certbot renew"
echo "Editar .htaccess:   sudo nano /var/www/$DOMAIN/public_html/.htaccess"

echo -e "\n${GREEN}🎉 Configuración completada para Goku Checker!${NC}"
echo -e "Visita: http://$DOMAIN"