#!/bin/sh

# Environment variables injection script for Vue.js SPA
# This script replaces placeholders in the built files with actual runtime environment variables

# Get the directory of the built application
APP_DIR="/usr/share/nginx/html"

# List of environment variables to inject
VARS="VITE_API_URL VITE_APP_TITLE VITE_APP_VERSION"

# Find and replace environment variables in JS files
for file in $(find $APP_DIR -name "*.js" -type f); do
    echo "Processing $file"
    
    # Create a temporary file
    tmp_file=$(mktemp)
    
    # Copy original file to temp
    cp "$file" "$tmp_file"
    
    # Replace each environment variable
    for var in $VARS; do
        # Get the environment variable value
        value=$(eval echo \$$var)
        
        # If the environment variable is set, replace the placeholder
        if [ ! -z "$value" ]; then
            # Replace __${var}__ with the actual value
            sed -i "s|__${var}__|${value}|g" "$tmp_file"
        fi
    done
    
    # Move the processed file back
    mv "$tmp_file" "$file"
done

echo "Environment variables injection completed" 