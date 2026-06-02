#!/bin/bash
# Fix 413 "Request Entity Too Large" for file uploads in Laravel Herd
# Run from project root: chmod +x fix-upload-limit.sh && ./fix-upload-limit.sh

set -e

HERD_CONFIG="$HOME/Library/Application Support/Herd/config/valet/Nginx"
HERD_NGINX="$HOME/Library/Application Support/Herd/config/nginx"
SITE_NAME="pilot.test"

echo "Fixing 413 upload limit for $SITE_NAME..."
echo ""

# Option 1: Try site-specific config (exists when site is isolated or secured)
if [ -f "$HERD_CONFIG/$SITE_NAME" ]; then
    echo "Found site config at: $HERD_CONFIG/$SITE_NAME"
    if grep -q "client_max_body_size" "$HERD_CONFIG/$SITE_NAME"; then
        echo "Upload limit already configured."
    else
        echo "Adding client_max_body_size 64M..."
        # Insert after "server {" - macOS compatible
        awk '/server \{/ && !done {print; print "    client_max_body_size 64M;"; done=1; next} 1' \
            "$HERD_CONFIG/$SITE_NAME" > "$HERD_CONFIG/$SITE_NAME.tmp" && mv "$HERD_CONFIG/$SITE_NAME.tmp" "$HERD_CONFIG/$SITE_NAME"
        echo "Restarting Herd..."
        herd restart
        echo "Done! Try uploading again."
    fi
    exit 0
fi

# Option 2: Site config doesn't exist - create it via herd isolate
echo "No site-specific config found. Creating one..."
echo "Running: herd isolate (this pins PHP version for this site)"
herd isolate

if [ -f "$HERD_CONFIG/$SITE_NAME" ]; then
    echo "Adding client_max_body_size 64M..."
    awk '/server \{/ && !done {print; print "    client_max_body_size 64M;"; done=1; next} 1' \
        "$HERD_CONFIG/$SITE_NAME" > "$HERD_CONFIG/$SITE_NAME.tmp" && mv "$HERD_CONFIG/$SITE_NAME.tmp" "$HERD_CONFIG/$SITE_NAME"
    echo "Restarting Herd..."
    herd restart
    echo "Done! Try uploading again."
else
    echo ""
    echo "Manual fix required. Run these commands:"
    echo ""
    echo "  cd $(pwd)"
    echo "  herd isolate"
    echo ""
    echo "Then edit: $HERD_CONFIG/$SITE_NAME"
    echo "Add this line right after 'server {' (inside the block):"
    echo ""
    echo "  client_max_body_size 64M;"
    echo ""
    echo "Then run: herd restart"
    exit 1
fi
