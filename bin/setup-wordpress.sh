#!/bin/sh
set -e

# ---------------------------------------------------------------------------
# Provision a fresh WordPress install for integration testing.
# Runs as a one-shot WP-CLI container defined in docker-compose.test.yml.
# ---------------------------------------------------------------------------

WP_URL="${WP_URL:-http://wordpress}"
WP_TITLE="${WP_TITLE:-BDPWR Test Site}"
WP_ADMIN_USER="${WP_ADMIN_USER:-admin}"
WP_ADMIN_PASSWORD="${WP_ADMIN_PASSWORD:-AdminPass123!}"
WP_ADMIN_EMAIL="${WP_ADMIN_EMAIL:-admin@bdpwr.test}"
TEST_SUBSCRIBER_EMAIL="${TEST_SUBSCRIBER_EMAIL:-subscriber@bdpwr.test}"
TEST_SUBSCRIBER_PASSWORD="${TEST_SUBSCRIBER_PASSWORD:-SubscriberPass123!}"

WP="wp --allow-root --path=/var/www/html"

# ---------------------------------------------------------------------------
# Wait for WordPress / database to be ready
# ---------------------------------------------------------------------------
echo "[setup] Waiting for WordPress to become available..."
until $WP db check 2>/dev/null; do
    echo "[setup] Database not ready yet, retrying in 3s..."
    sleep 3
done
echo "[setup] Database is ready."

# ---------------------------------------------------------------------------
# Install WordPress (idempotent — skips if already installed)
# ---------------------------------------------------------------------------
if ! $WP core is-installed 2>/dev/null; then
    echo "[setup] Installing WordPress..."
    $WP core install \
        --url="$WP_URL" \
        --title="$WP_TITLE" \
        --admin_user="$WP_ADMIN_USER" \
        --admin_password="$WP_ADMIN_PASSWORD" \
        --admin_email="$WP_ADMIN_EMAIL" \
        --skip-email
    echo "[setup] WordPress installed."
else
    echo "[setup] WordPress already installed, skipping."
fi

# ---------------------------------------------------------------------------
# Activate the plugin
# ---------------------------------------------------------------------------
echo "[setup] Activating plugin bdvs-password-reset..."
$WP plugin activate bdvs-password-reset
echo "[setup] Plugin activated."

# ---------------------------------------------------------------------------
# Create test users (idempotent)
# ---------------------------------------------------------------------------

# Subscriber (non-admin) — used to test happy paths
if ! $WP user get "$TEST_SUBSCRIBER_EMAIL" --field=ID 2>/dev/null; then
    echo "[setup] Creating subscriber test user: $TEST_SUBSCRIBER_EMAIL"
    $WP user create subscriber_test "$TEST_SUBSCRIBER_EMAIL" \
        --role=subscriber \
        --user_pass="$TEST_SUBSCRIBER_PASSWORD"
else
    echo "[setup] Subscriber test user already exists."
fi

# ---------------------------------------------------------------------------
# Summary
# ---------------------------------------------------------------------------
echo ""
echo "[setup] =============================="
echo "[setup]  Setup complete!"
echo "[setup]  WP URL      : $WP_URL"
echo "[setup]  Admin       : $WP_ADMIN_EMAIL"
echo "[setup]  Subscriber  : $TEST_SUBSCRIBER_EMAIL"
echo "[setup] =============================="
