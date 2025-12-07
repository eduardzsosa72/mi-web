#!/bin/bash

echo "=== ACTUALIZANDO SISTEMA ==="
sudo apt update -y
sudo apt upgrade -y

echo "=== INSTALANDO APACHE ==="
sudo apt install apache2 -y
sudo systemctl enable apache2
sudo systemctl start apache2

echo "=== INSTALANDO PHP ==="
sudo apt install php libapache2-mod-php php-cli php-common php-mysql php-zip php-gd php-mbstring php-curl php-xml php-bcmath -y

echo "=== ACTIVANDO MODULOS NECESARIOS ==="
sudo a2enmod php*
sudo systemctl restart apache2

echo "=== INSTALANDO GIT ==="
sudo apt install git -y

echo "=== DESCARGANDO TU REPO DE GITHUB ==="
cd /tmp
rm -rf mi-web
git clone https://github.com/eduardzsosa72/mi-web.git

echo "=== MOVER ARCHIVOS AL SERVIDOR APACHE ==="
sudo rm -rf /var/www/html/*
sudo cp -r mi-web/* /var/www/html/

echo "=== AJUSTANDO PERMISOS ==="
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

echo "=== REINICIANDO APACHE ==="
sudo systemctl restart apache2

echo "=== LISTO!! Tu sitio está funcionando en http://localhost ==="
