# AWS Quick Start Commands

## Prerequisites
```bash
# Install AWS CLI
# macOS:
brew install awscli

# Windows (via PowerShell as Administrator):
msiexec.exe /i https://awscli.amazonaws.com/AWSCLIV2.msi

# Linux:
sudo apt install awscli

# Configure AWS credentials
aws configure
# Enter: Access Key ID, Secret Access Key, Default Region (us-east-1)
```

---

## Step 1: Create RDS Database

```bash
# Create RDS MySQL instance
aws rds create-db-instance \
  --db-instance-identifier studentmgmt-db \
  --db-instance-class db.t3.micro \
  --engine mysql \
  --engine-version 8.0.35 \
  --master-username admin \
  --master-user-password 'YourSecurePassword123!' \
  --allocated-storage 20 \
  --publicly-accessible \
  --db-name dbstudentms \
  --region us-east-1

# Get the endpoint
aws rds describe-db-instances \
  --db-instance-identifier studentmgmt-db \
  --region us-east-1 \
  --query 'DBInstances[0].Endpoint.Address' \
  --output text
```

---

## Step 2: Create EC2 Security Group

```bash
# Create security group
aws ec2 create-security-group \
  --group-name laravel-sg \
  --description "Security group for Laravel app" \
  --region us-east-1

# Get the security group ID (output from above)
SG_ID="sg-xxxxxxxx"

# Allow SSH
aws ec2 authorize-security-group-ingress \
  --group-id $SG_ID \
  --protocol tcp \
  --port 22 \
  --cidr 0.0.0.0/0 \
  --region us-east-1

# Allow HTTP
aws ec2 authorize-security-group-ingress \
  --group-id $SG_ID \
  --protocol tcp \
  --port 80 \
  --cidr 0.0.0.0/0 \
  --region us-east-1

# Allow HTTPS
aws ec2 authorize-security-group-ingress \
  --group-id $SG_ID \
  --protocol tcp \
  --port 443 \
  --cidr 0.0.0.0/0 \
  --region us-east-1
```

---

## Step 3: Create EC2 Key Pair

```bash
# Create key pair
aws ec2 create-key-pair \
  --key-name laravel-key \
  --region us-east-1 \
  --output text > laravel-key.pem

# Set permissions
chmod 400 laravel-key.pem
```

---

## Step 4: Launch EC2 Instance

```bash
# Get Ubuntu 22.04 LTS AMI ID
AMI_ID="ami-0c02fb55956c7d316"  # Update for your region

# Launch instance
aws ec2 run-instances \
  --image-id $AMI_ID \
  --instance-type t3.micro \
  --key-name laravel-key \
  --security-group-ids $SG_ID \
  --block-device-mappings "DeviceName=/dev/sda1,Ebs={VolumeSize=30,VolumeType=gp3}" \
  --region us-east-1

# Get instance ID (from output above)
INSTANCE_ID="i-xxxxxxxx"

# Wait for instance to be running
aws ec2 wait instance-running --instance-ids $INSTANCE_ID --region us-east-1

# Get public IP
aws ec2 describe-instances \
  --instance-ids $INSTANCE_ID \
  --region us-east-1 \
  --query 'Reservations[0].Instances[0].PublicIpAddress' \
  --output text
```

---

## Step 5: Connect to EC2 Instance

```bash
# SSH into instance
ssh -i laravel-key.pem ubuntu@YOUR_EC2_PUBLIC_IP

# Or using EC2 Instance Connect (if configured)
aws ec2-instance-connect send-ssh-public-key \
  --instance-id $INSTANCE_ID \
  --instance-os-user ubuntu \
  --ssh-public-key file://~/.ssh/id_rsa.pub \
  --region us-east-1
```

---

## Step 6: Run Deployment Script

```bash
# Once connected to EC2
cd /tmp

# Download deployment script from GitHub
wget https://raw.githubusercontent.com/mcyu-web/SIA22/master/deploy.sh

# Make executable and run
chmod +x deploy.sh
sudo bash deploy.sh

# Wait for completion...
```

---

## Step 7: Configure RDS Security Group

```bash
# Allow traffic from EC2 to RDS
aws ec2 authorize-security-group-ingress \
  --group-id sg-rds-xxxxxxxx \
  --protocol tcp \
  --port 3306 \
  --source-security-group-id $SG_ID \
  --region us-east-1
```

---

## Step 8: Verify Deployment

```bash
# SSH into EC2
ssh -i laravel-key.pem ubuntu@YOUR_EC2_PUBLIC_IP

# Check logs
tail -f /var/www/html/storage/logs/laravel.log

# Check Nginx status
sudo systemctl status nginx

# Check PHP-FPM status
sudo systemctl status php8.2-fpm
```

---

## Optional: Create S3 Bucket for Receipts

```bash
# Create S3 bucket
aws s3 mb s3://studentmgmt-receipts-$(date +%s) \
  --region us-east-1

# Block public access
aws s3api put-public-access-block \
  --bucket studentmgmt-receipts-xxxxxxxx \
  --public-access-block-configuration \
  "BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true"

# Create IAM user for S3 access
aws iam create-user --user-name laravel-s3-user

# Attach S3 policy
aws iam put-user-policy \
  --user-name laravel-s3-user \
  --policy-name laravel-s3-policy \
  --policy-document '{
    "Version": "2012-10-17",
    "Statement": [{
      "Effect": "Allow",
      "Action": "s3:*",
      "Resource": "arn:aws:s3:::studentmgmt-receipts-xxxxxxxx/*"
    }]
  }'

# Create access key
aws iam create-access-key --user-name laravel-s3-user
```

---

## Useful Commands

### Check instance status
```bash
aws ec2 describe-instances \
  --instance-ids $INSTANCE_ID \
  --region us-east-1 \
  --query 'Reservations[0].Instances[0].[InstanceId,State.Name,PublicIpAddress]' \
  --output table
```

### Stop/Start instance
```bash
# Stop (costs less)
aws ec2 stop-instances --instance-ids $INSTANCE_ID --region us-east-1

# Start
aws ec2 start-instances --instance-ids $INSTANCE_ID --region us-east-1
```

### Monitor RDS
```bash
aws rds describe-db-instances \
  --db-instance-identifier studentmgmt-db \
  --region us-east-1 \
  --query 'DBInstances[0].[DBInstanceIdentifier,DBInstanceStatus,AllocatedStorage,DBInstanceClass]' \
  --output table
```

### View logs
```bash
# RDS error log (last 50 events)
aws rds describe-db-log-files \
  --db-instance-identifier studentmgmt-db \
  --region us-east-1

# EC2 system log
aws ec2 get-console-output \
  --instance-id $INSTANCE_ID \
  --region us-east-1
```

---

## Cost Optimization Tips

1. **Use Free Tier**: t3.micro instances and db.t3.micro databases are free for 12 months
2. **Stop instances**: Stop EC2 when not in use (storage still costs)
3. **Set up CloudWatch alarms**: Monitor costs and get alerts
4. **Use Reserved Instances**: For long-term deployments, save 30-70%
5. **Enable auto-shutdown**: Terminate instance after inactivity

```bash
# Check current month's costs
aws ce get-cost-and-usage \
  --time-period Start=2026-04-01,End=2026-04-30 \
  --granularity MONTHLY \
  --metrics "UnblendedCost" \
  --region us-east-1
```

---

## Cleanup (Delete Resources)

⚠️ **Warning: This will delete everything!**

```bash
# Terminate EC2 instance
aws ec2 terminate-instances --instance-ids $INSTANCE_ID --region us-east-1

# Delete RDS database
aws rds delete-db-instance \
  --db-instance-identifier studentmgmt-db \
  --skip-final-snapshot \
  --region us-east-1

# Delete security groups
aws ec2 delete-security-group --group-id $SG_ID --region us-east-1

# Delete key pair
aws ec2 delete-key-pair --key-name laravel-key --region us-east-1

# Delete S3 bucket (must be empty first)
aws s3 rm s3://studentmgmt-receipts-xxxxxxxx --recursive
aws s3 rb s3://studentmgmt-receipts-xxxxxxxx
```

---

## Troubleshooting

### Can't SSH into instance
```bash
# Check security group allows SSH
aws ec2 describe-security-groups --group-ids $SG_ID --region us-east-1

# Check instance is running
aws ec2 describe-instances --instance-ids $INSTANCE_ID --region us-east-1
```

### Database connection fails
```bash
# Test from EC2
mysql -h RDS_ENDPOINT -u admin -p

# Check RDS security group
aws rds describe-db-security-groups \
  --db-security-group-name default \
  --region us-east-1
```

### Check application logs
```bash
ssh -i laravel-key.pem ubuntu@YOUR_EC2_PUBLIC_IP
tail -50 /var/www/html/storage/logs/laravel.log
```
