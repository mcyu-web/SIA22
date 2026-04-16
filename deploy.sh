#!/bin/bash
# AWS EC2 Deployment Script for Student Management System
# Run this on your EC2 instance after cloning the repository

set -e

echo "=========================================="
echo "Student Management System - AWS Deployment"
echo "=========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[✓]${NC} $1"
}

print_error() {
    echo -e "${RED}[✗]${NC} $1"
}

print_info() {
    echo -e "${YELLOW}[i]${NC} $1"
}

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    print_error "This script must be run as root (use sudo)"
    exit 1
fi

print_info "Starting deployment..."

# Step 1: Update system
print_info "Updating system packages..."
apt update && apt upgrade -y
print_status "System updated"

# Step 2: Install PHP and extensions
print_info "Installing PHP 8.2 and extensions..."
apt install -y php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-bcmath php8.2-zip php8.2-intl
print_status "PHP 8.2 installed"

# Step 3: Install Nginx
print_info "Installing Nginx..."
apt install -y nginx
print_status "Nginx installed"

# Step 4: Install Composer
print_info "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
print_status "Composer installed"

# Step 5: Install Git and MySQL client
print_info "Installing Git and MySQL client..."
apt install -y git mysql-client curl wget
print_status "Git and MySQL client installed"

# Step 6: Set up Laravel application
print_info "Setting up Laravel application..."
if [ -d "/var/www/html" ]; then
    print_info "Removing existing /var/www/html..."
    rm -rf /var/www/html
fi

print_info "Cloning repository..."
cd /var/www
git clone https://github.com/mcyu-web/SIA22.git html
cd html

print_status "Repository cloned"

# Step 7: Set permissions
print_info "Setting file permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
print_status "Permissions set"

# Step 8: Install PHP dependencies
print_info "Installing PHP dependencies (this may take a few minutes)..."
sudo -u www-data composer install --no-dev --optimize-autoloader
print_status "PHP dependencies installed"

# Step 9: Create .env file
print_info "Creating .env file from example..."
cp .env.example .env
print_info "Edit .env with your RDS credentials before proceeding"
print_info "Run: sudo nano /var/www/html/.env"
print_info "Update DB_HOST, DB_USERNAME, and DB_PASSWORD with RDS details"

# Step 10: Generate APP_KEY
print_info "Generating Laravel APP_KEY..."
sudo -u www-data php artisan key:generate
print_status "APP_KEY generated"

# Step 11: Clear caches
print_info "Clearing Laravel caches..."
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
print_status "Caches cleared"

# Step 12: Configure Nginx
print_info "Configuring Nginx..."
cat > /etc/nginx/sites-available/laravel << 'EOF'
server {
    listen 80;
    server_name _;

    root /var/www/html/public;
    index index.php index.html index.htm;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Enable site
ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel
rm -f /etc/nginx/sites-enabled/default

# Test Nginx config
nginx -t
print_status "Nginx configured"

# Step 13: Restart services
print_info "Restarting services..."
systemctl restart php8.2-fpm
systemctl restart nginx
print_status "Services restarted"

echo ""
echo "=========================================="
echo -e "${GREEN}Deployment Preparation Complete!${NC}"
echo "=========================================="
echo ""
echo -e "${YELLOW}NEXT STEPS:${NC}"
echo "1. Edit your .env file with RDS credentials:"
echo "   sudo nano /var/www/html/.env"
echo ""
echo "2. Test database connection:"
echo "   mysql -h YOUR_RDS_ENDPOINT -u admin -p"
echo ""
echo "3. Run database migrations:"
echo "   cd /var/www/html"
echo "   sudo -u www-data php artisan migrate --force"
echo ""
echo "4. Seed the database (optional):"
echo "   sudo -u www-data php artisan db:seed"
echo ""
echo "5. Create storage link:"
echo "   sudo -u www-data php artisan storage:link"
echo ""
echo "6. Access your application:"
echo "   http://YOUR_EC2_PUBLIC_IP"
echo ""
echo "Default credentials:"
echo "   Email: admin@example.com"
echo "   Password: password"
echo ""
