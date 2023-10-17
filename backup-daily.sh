#!/bin/sh

# Define the paths for mysqldump and tar on Ubuntu/Debian systems
mysqldump_path="/usr/bin/mysqldump"
tar_path="/bin/tar"

# Check if mysqldump and tar exist at their defined paths
if [ ! -x "$mysqldump_path" ]; then
    echo "mysqldump command not found at $mysqldump_path. Please check the path or install mysqldump."
    exit 1
fi
if [ ! -x "$tar_path" ]; then
    echo "tar command not found at $tar_path. Please check the path or install tar."
    exit 1
fi

# Get the directory where the script is located
script_dir="$(dirname "$0")"

# Check if the backup output directory, avatar directory, and workshop directory arguments are provided
if [ $# -ne 3 ]; then
  echo "Usage: $0 <backup_output_dir> <avatar_dir> <workshop_dir>"
  exit 1
fi

# Set variables
backup_output_dir="$1"
avatar_dir="$2"
workshop_dir="$3"

# Source the .env file located in the script's directory to set environment variables temporarily
. "$script_dir/.env"

# backup database - DAILY
"$mysqldump_path" -u "$APP_DB_USER" -p"$APP_DB_PASS" "$APP_DB_DATABASE" > "$backup_output_dir/$(date +"%Y-%m-%d")-keeperfx.sql"

# backup avatar files - DAILY
"$tar_path" -czf "$backup_output_dir/$(date +"%Y-%m-%d")-avatars.tar.gz" "$avatar_dir"

# backup workshop files - EACH 3 DAYS
if [ "$(( $(date +'%d') % 3 ))" -eq 0 ]; then
  "$tar_path" -czf "$backup_output_dir/$(date +"%Y-%m-%d")-workshop.tar.gz" "$workshop_dir"
fi
