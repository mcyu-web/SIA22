# AWS Infrastructure Setup Guide
## VPC + EC2 Ubuntu + RDS MySQL

---

## Step 1: Create VPC

### Via AWS Console:
1. Go to **VPC** → **Create VPC**
2. Select **VPC and more**
3. Configuration:
   - **Name tag**: `studentmgmt-vpc`
   - **IPv4 CIDR block**: `10.0.0.0/16`
   - **Number of Availability Zones**: `1`
   - **Public subnets**: `1` (CIDR: `10.0.1.0/24`)
   - **Private subnets**: `1` (CIDR: `10.0.2.0/24`)
   - **NAT gateways**: `None` (we'll use public subnet)
   - **VPC endpoints**: `None`
4. Click **Create VPC**

**Note the VPC ID** (e.g., `vpc-12345678`)

---

## Step 2: Create Security Groups

### Web Server Security Group:
1. Go to **EC2** → **Security Groups** → **Create security group**
2. Configuration:
   - **Security group name**: `web-sg`
   - **Description**: `Security group for web server`
   - **VPC**: Select your `studentmgmt-vpc`
3. **Inbound rules**:
   - Type: `SSH`, Source: `My IP` (find your IP)
   - Type: `HTTP`, Source: `0.0.0.0/0`
   - Type: `HTTPS`, Source: `0.0.0.0/0`
4. Click **Create security group**

### Database Security Group:
1. **Create security group** again
2. Configuration:
   - **Security group name**: `db-sg`
   - **Description**: `Security group for database`
   - **VPC**: Select your `studentmgmt-vpc`
3. **Inbound rules**:
   - Type: `MySQL/Aurora`, Source: `web-sg` (select the security group you just created)
4. Click **Create security group**

---

## Step 3: Launch EC2 Ubuntu Instance

### Via AWS Console:
1. Go to **EC2** → **Launch Instance**
2. **Name and tags**: `studentmgmt-web`
3. **Application and OS Images (Amazon Machine Image)**:
   - Search: `Ubuntu`
   - Select: `Ubuntu Server 22.04 LTS (HVM), SSD Volume Type`
4. **Instance type**: `t3.micro` (Free Tier eligible)
5. **Key pair (login)**:
   - **Key pair name**: `studentmgmt-key`
   - Click **Create new key pair**
   - **Key pair type**: `RSA`
   - **Private key file format**: `.pem`
   - Click **Create key pair** (downloads automatically)
6. **Network settings**:
   - **VPC**: Select your `studentmgmt-vpc`
   - **Subnet**: Select the **public** subnet (10.0.1.0/24)
   - **Auto-assign public IP**: `Enable`
   - **Firewall (security groups)**: Select existing `web-sg`
7. **Configure storage**: Default (8 GB) is fine
8. Click **Launch instance**

### Get Instance Details:
- Go to **EC2** → **Instances**
- Select your instance
- Note the **Public IPv4 address** (e.g., `54.123.45.67`)

### Connect to Instance:
```bash
# Set permissions on key file
chmod 400 studentmgmt-key.pem

# Connect
ssh -i studentmgmt-key.pem ubuntu@YOUR_PUBLIC_IP
```

---

## Step 4: Create RDS MySQL Database

### Via AWS Console:
1. Go to **RDS** → **Create database**
2. **Engine options**:
   - **Engine type**: `MySQL`
   - **Version**: `MySQL 8.0.35` (latest)
3. **Templates**: `Free tier`
4. **Settings**:
   - **DB instance identifier**: `studentmgmt-db`
   - **Master username**: `admin`
   - **Master password**: Create a strong password (save it!)
   - **Confirm password**: Same password
5. **Instance configuration**:
   - **DB instance class**: `db.t3.micro` (Free Tier)
   - **Storage**: `20 GB` (gp2)
6. **Connectivity**:
   - **VPC**: Select your `studentmgmt-vpc`
   - **Subnet group**: Create new → `studentmgmt-db-subnet`
     - Select your **private** subnet (10.0.2.0/24)
   - **Publicly accessible**: `No`
   - **VPC security group**: Select existing `db-sg`
   - **Availability Zone**: `No preference`
7. **Database authentication**: `Password authentication`
8. **Additional configuration**:
   - **Initial database name**: `dbstudentms`
   - **Backup retention period**: `7 days`
   - **Backup window**: Default
   - **Maintenance window**: Default
9. Click **Create database**

### Get Database Endpoint:
- Go to **RDS** → **Databases**
- Select `studentmgmt-db`
- Note the **Endpoint** (e.g., `studentmgmt-db.c9akciq32.us-east-1.rds.amazonaws.com`)

---

## Step 5: Test Connections

### Test SSH to EC2:
```bash
ssh -i studentmgmt-key.pem ubuntu@YOUR_EC2_PUBLIC_IP
```

### Test Database Connection from EC2:
```bash
# Install MySQL client
sudo apt update
sudo apt install mysql-client -y

# Connect to database
mysql -h YOUR_RDS_ENDPOINT -u admin -p
# Enter password when prompted
# Type: SHOW DATABASES;
# You should see: dbstudentms
# Type: exit;
```

---

## Step 6: Deploy Your Application

### On your EC2 instance:
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install dependencies
sudo apt install -y php8.2-cli php8.2-fpm php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-curl php8.2-bcmath php8.2-zip nginx git curl wget

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Clone your app
cd /var/www
sudo git clone https://github.com/mcyu-web/SIA22.git html
cd html

# Set permissions
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Install dependencies
sudo -u www-data composer install --no-dev --optimize-autoloader

# Copy and configure .env
sudo cp .env.example .env
sudo nano .env  # Edit with your RDS endpoint and password

# Generate app key
sudo -u www-data php artisan key:generate

# Run migrations
sudo -u www-data php artisan migrate --force

# Seed database (optional)
sudo -u www-data php artisan db:seed

# Create storage link
sudo -u www-data php artisan storage:link

# Configure Nginx
sudo nano /etc/nginx/sites-available/laravel
# Paste the Nginx config from AWS_DEPLOYMENT.md

# Enable site
sudo ln -s /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

---

## Summary

### Resources Created:
- **VPC**: `studentmgmt-vpc` (10.0.0.0/16)
- **Subnets**: 1 public (10.0.1.0/24), 1 private (10.0.2.0/24)
- **Security Groups**: `web-sg`, `db-sg`
- **EC2 Instance**: Ubuntu 22.04, t3.micro, public IP
- **RDS Database**: MySQL 8.0, db.t3.micro, private subnet

### Access Points:
- **Web App**: `http://YOUR_EC2_PUBLIC_IP`
- **SSH**: `ssh -i studentmgmt-key.pem ubuntu@YOUR_EC2_PUBLIC_IP`
- **Database**: `YOUR_RDS_ENDPOINT` (from EC2 only)

### Cost Estimate (Free Tier):
- EC2 t3.micro: Free (750 hours/month for 12 months)
- RDS db.t3.micro: Free (750 hours/month for 12 months)
- **Total: $0/month**

### Next Steps:
1. Test your application at the public IP
2. Set up a domain name (Route 53)
3. Add SSL certificate (Let's Encrypt)
4. Configure backups and monitoring

---

## Troubleshooting

### Can't SSH to EC2?
- Check security group allows SSH from your IP
- Verify key file permissions: `chmod 400 studentmgmt-key.pem`
- Ensure instance is running and has public IP

### Database connection fails?
- Verify RDS endpoint and password
- Check security groups (web-sg should access db-sg)
- Ensure RDS is in "Available" status

### Application not loading?
- Check Nginx status: `sudo systemctl status nginx`
- Check PHP-FPM: `sudo systemctl status php8.2-fpm`
- View logs: `tail -f /var/www/html/storage/logs/laravel.log`

---

## Cleanup (When Done Testing)

⚠️ **This will delete everything!**

```bash
# Delete RDS
aws rds delete-db-instance --db-instance-identifier studentmgmt-db --skip-final-snapshot

# Terminate EC2
aws ec2 terminate-instances --instance-ids YOUR_INSTANCE_ID

# Delete VPC (this removes subnets, IGW, route tables)
aws ec2 delete-vpc --vpc-id YOUR_VPC_ID

# Delete security groups
aws ec2 delete-security-group --group-id YOUR_WEB_SG_ID
aws ec2 delete-security-group --group-id YOUR_DB_SG_ID

# Delete key pair
aws ec2 delete-key-pair --key-name studentmgmt-key
```