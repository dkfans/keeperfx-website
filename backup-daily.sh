#!/bin/bash

# Check if the backup output directory, avatar directory, and workshop directory arguments are provided
if [ $# -ne 3 ]; then
  echo "Usage: $0 <backup_output_dir> <avatar_dir> <workshop_dir>"
  exit 1
fi

# Set variables
backup_output_dir="$1"
avatar_dir="$2"
workshop_dir="$3"

# Source the .env file to set environment variables temporarily
source .env

# backup database - DAILY
mysqldump -u "$APP_DB_USER" -p"$APP_DB_PASS" "$APP_DB_DATABASE" > "$backup_output_dir/$(date +"%Y-%m-%d")-keeperfx.sql"

# backup avatar files - DAILY
tar -czf "$backup_output_dir/$(date +"%Y-%m-%d")-avatars.tar.gz" "$avatar_dir"

# backup workshop files - EACH 3 DAYS
if [ "$(( $(date +'%d') % 3 ))" -eq 0 ]; then
  tar -czf "$backup_output_dir/$(date +"%Y-%m-%d")-workshop.tar.gz" "$workshop_dir"
fi
