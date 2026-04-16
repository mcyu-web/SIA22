# AWS Deployment Guide for Student Management System

## Overview
This guide walks through deploying the Laravel Student Management System to AWS EC2 with RDS MySQL.

## Prerequisites
- AWS Account with credentials configured locally
- EC2 key pair created in AWS
- Git installed on local machine

## Architecture
```
Route 53 (DNS)
    ↓
EC2 Instance (Ubuntu 22.04)
    ↓ (connects to)
RDS MySQL Database
    ↓ (stores files in)
S3 Bucket (for receipts)
```

---

## Step 1: Create RDS MySQL Database

### Via AWS Console:
1. Go to **RDS** → **Create Database**
2. Select **MySQL** (version 8.0 or higher)
3. Configuration:
   - DB instance class: `db.t3.micro` (Free Tier eligible)
   - Storage: `20 GB`
   - DB name: `dbstudentms`
   - Master username: `admin`
   - Master password: (create strong password)
4. Network:
   - VPC: Default VPC
   - Publicly accessible: Yes
   - Security group: Allow inbound on port 3306 from your EC2 security group
5. Click **Create Database**
6. Once created, note the **Endpoint** (e.g., `studentmgmt-db.c9akciq32.us-east-1.rds.amazonaws.com`)

---

## Step 2: Create EC2 Instance

### Via AWS Console:
1. Go to **EC2** → **Launch Instance**
2. AMI: **Ubuntu Server 22.04 LTS**
3. Instance type: `t3.micro` (Free Tier eligible)
4. Storage: 30 GB
5. Security Group:
   - Inbound SSH (port 22) from your IP
   - Inbound HTTP (port 80) from anywhere
   - Inbound HTTPS (port 443) from anywhere
6. Key Pair: Select or create a new `.pem` file
7. Click **Launch**
8. Once running, note the **Public IPv4 address** or **DNS name**

---

## Step 3: Connect to EC2 Instance

```bash
# SSH into the instance
ssh -i your-key.pem ubuntu@YOUR_EC2_PUBLIC_IP

# Update system
sudo apt update && sudo apt upgrade -y
```

---

## Step 4: Install Dependencies

```bash
# Install PHP and extensions
sudo apt install -y php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-bcmath php8.2-zip

# Install Nginx
sudo apt install -y nginx

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Git
sudo apt install -y git

# Install MySQL client
sudo apt install -y mysql-client
```

---

## Step 5: Clone and Setup Application

```bash
# Navigate to web root
cd /var/www

# Clone the repository
sudo git clone https://github.com/mcyu-web/SIA22.git html
cd html

# Fix permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Install PHP dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Create .env file
sudo cp .env.example .env
sudo nano .env
```

---

## Step 6: Configure .env for Production

Edit `/var/www/html/.env` with RDS credentials:

```env
APP_NAME=StudentManagement
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_EXISTING_KEY

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=YOUR_RDS_ENDPOINT
DB_PORT=3306
DB_DATABASE=dbstudentms
DB_USERNAME=admin
DB_PASSWORD=YOUR_RDS_PASSWORD

FILESYSTEM_DISK=public
```

---

## Step 7: Run Laravel Setup

```bash
cd /var/www/html

# Generate app key (if needed)
sudo -u www-data php artisan key:generate

# Run migrations
sudo -u www-data php artisan migrate --force

# Seed database
sudo -u www-data php artisan db:seed

# Create storage link
sudo -u www-data php artisan storage:link

# Clear caches
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear
```

---

## Step 8: Configure Nginx

Create `/etc/nginx/sites-available/laravel`:

```nginx
server {
    listen 80;
    server_name YOUR_EC2_PUBLIC_IP;

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
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
```

---

## Step 9: Verify Deployment

1. Open your browser and navigate to: `http://YOUR_EC2_PUBLIC_IP`
2. You should see the Student Management login page
3. Test login with seeded credentials:
   - Email: `admin@example.com`
   - Password: `password`

---

## Step 10: (Optional) Set Up Domain & SSL

### Using Route 53:
1. Go to **Route 53** → **Hosted Zones**
2. Create a new hosted zone for your domain
3. Add an `A` record pointing to your EC2 public IP

### Using Let's Encrypt (Free SSL):
```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
sudo systemctl restart nginx
```

---

## Step 11: (Recommended) Configure S3 for Receipts

Instead of storing receipts on the instance, use S3:

1. Go to **S3** → **Create Bucket**
2. Update `.env`:
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=YOUR_KEY
AWS_SECRET_ACCESS_KEY=YOUR_SECRET
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

3. Update `config/filesystems.php` to add S3 disk configuration

---

## Maintenance & Monitoring

### View Application Logs:
```bash
tail -f /var/www/html/storage/logs/laravel.log
```

### Restart PHP-FPM:
```bash
sudo systemctl restart php8.2-fpm
```

### Restart Nginx:
```bash
sudo systemctl restart nginx
```

### Check disk space:
```bash
df -h
```

---

## Cost Estimate (Free Tier)
- EC2 t3.micro: Free (first 12 months, 750 hours/month)
- RDS db.t3.micro: Free (first 12 months, 750 hours/month)
- Data transfer: Minimal (within free tier)
- **Total: ~$0/month** (if within free tier limits)

---

## Troubleshooting

**Issue: Can't connect to database**
- Verify RDS security group allows EC2 security group
- Check credentials in `.env`
- Test: `mysql -h RDS_ENDPOINT -u admin -p`

**Issue: Permission denied on storage**
- Run: `sudo chown -R www-data:www-data /var/www/html/storage`

**Issue: Nginx returns 404**
- Check that Laravel is in `/var/www/html/public`
- Restart Nginx: `sudo systemctl restart nginx`

---

## Next Steps
1. Set up daily backups for RDS
2. Enable CloudWatch monitoring
3. Set up auto-scaling if traffic increases
4. Configure SNS for alerts
