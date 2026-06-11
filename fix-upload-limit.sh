#!/bin/bash
# Help fix 413 "Request Entity Too Large" for file uploads in Laravel Herd.
# Run from project root: chmod +x fix-upload-limit.sh && ./fix-upload-limit.sh

set -e

SITE_NAME="pilot.test"

cat <<EOF
Fixing 413 upload limits for ${SITE_NAME} requires this Nginx directive:

    client_max_body_size 64M;

This script avoids editing local machine paths directly. It will run Herd's
site isolation command so a site-specific Nginx config is available, then you
can add the directive through Herd's site config workflow.

EOF

if command -v herd >/dev/null 2>&1; then
    echo "Running: herd isolate"
    herd isolate
    echo ""
    echo "Next steps:"
    echo "1. Open the Herd site Nginx config for ${SITE_NAME}."
    echo "2. Add 'client_max_body_size 64M;' inside the server block."
    echo "3. Run: herd restart"
else
    echo "The 'herd' command is not available in this shell."
    echo ""
    echo "Next steps:"
    echo "1. Open Herd and create or open the site-specific Nginx config for ${SITE_NAME}."
    echo "2. Add 'client_max_body_size 64M;' inside the server block."
    echo "3. Restart Herd."
fi
