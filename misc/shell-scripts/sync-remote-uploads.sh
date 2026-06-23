#!/usr/bin/env bash

# Define connection variables
# Replace 'your_remote_host.com' with your server's IP address or domain name
REMOTE_USER="corenominal"
REMOTE_HOST="corenominal.com"

# Define paths
# Note the trailing slash on REMOTE_PATH. This tells rsync to copy the 
# contents of the directory, rather than the directory itself.
REMOTE_PATH="/home/corenominal/www/corenominal.com/codeigniter/public/uploads/"
LOCAL_PATH="$HOME/Projects/corenominal.com/codeigniter/public/uploads/"

# Start the sync process
echo "Starting sync from ${REMOTE_HOST}:${REMOTE_PATH} to ${LOCAL_PATH}..."

# Run the rsync command
# -a : archive mode (preserves permissions, times, symlinks, etc.)
# -v : verbose (shows what is happening)
# -z : compress file data during the transfer
# --delete : removes files on the local machine that no longer exist on the remote machine
# -e ssh : uses SSH for the transfer
rsync -avz --delete -e ssh ${REMOTE_USER}@${REMOTE_HOST}:${REMOTE_PATH} ${LOCAL_PATH}

# Check if the command was successful
if [ $? -eq 0 ]; then
    echo "Sync completed successfully."
else
    echo "An error occurred during the sync process."
fi
