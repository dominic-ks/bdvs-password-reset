#!/usr/bin/env bash
# Script copied from WordPress core contributor instructions.
if [ $# -lt 3 ]; then
cat <<- EOT
Usage: $0 <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-docker]
e.g. $0 wordpress_test root '' localhost latest

"wp-version" can be "latest" or a specific tags like 6.6.
EOT
exit 1
fi

DB_NAME=$1
DB_USER=$2
DB_PASS=$3
DB_HOST=${4-localhost}
WP_VERSION=${5-latest}
SKIP_DOCKER=${6-false}
WP_TESTS_DIR=${WP_TESTS_DIR-/tmp/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR-/tmp/wordpress/}
TMPDIR=${TMPDIR-/tmp}

set -ex

install_wp() {
if [ -d "$WP_CORE_DIR" ]; then
return;
fi

mkdir -p "$WP_CORE_DIR"

if [ "$WP_VERSION" = 'latest' ]; then
ARCHIVE_URL='https://wordpress.org/latest.tar.gz'
else
ARCHIVE_URL="https://wordpress.org/wordpress-${WP_VERSION}.tar.gz"
fi

wget -nv -O /tmp/wordpress.tar.gz "$ARCHIVE_URL"
tar --strip-components=1 -zxmf /tmp/wordpress.tar.gz -C "$WP_CORE_DIR"
}

install_test_suite() {
# portable in-place argument for both GNU sed and macOS sed
if [[ $(uname -s) == 'Darwin' ]]; then
sed_in_place=(-i '')
else
sed_in_place=(-i)
fi

# set up testing suite if it doesn't yet exist
if [ ! -d "$WP_TESTS_DIR" ]; then
mkdir -p "$WP_TESTS_DIR"
download_wp_tests
fi

cd "$WP_TESTS_DIR"

if [ ! -f wp-tests-config.php ]; then
cp wp-tests-config-sample.php wp-tests-config.php
sed "${sed_in_place[@]}" "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
sed "${sed_in_place[@]}" "s/youremptytestdbnamehere/$DB_NAME/" wp-tests-config.php
sed "${sed_in_place[@]}" "s/yourusernamehere/$DB_USER/" wp-tests-config.php
sed "${sed_in_place[@]}" "s/yourpasswordhere/$DB_PASS/" wp-tests-config.php
sed "${sed_in_place[@]}" "s|localhost|${DB_HOST}|" wp-tests-config.php
fi
}

download_wp_tests() {
cd "$TMPDIR"

if [ "$WP_VERSION" = 'latest' ]; then
ARCHIVE_URL="https://github.com/WordPress/wordpress-develop/archive/master.tar.gz"
else
ARCHIVE_URL="https://github.com/WordPress/wordpress-develop/archive/${WP_VERSION}.tar.gz"
fi

wget -nv -O /tmp/wordpress-tests-lib.tar.gz "$ARCHIVE_URL"
tar --strip-components=1 -zxmf /tmp/wordpress-tests-lib.tar.gz -C "$WP_TESTS_DIR"

cd "$WP_TESTS_DIR"

if [ ! -f wp-tests-config.php ]; then
cp wp-tests-config-sample.php wp-tests-config.php
sed "${sed_in_place[@]}" "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR':" wp-tests-config.php
fi
}

create_db() {
if ${SKIP_DOCKER}; then
return 0
fi

if [ "$WP_VERSION" = 'latest' ]; then
MYSQL_TAG='latest'
else
MYSQL_TAG='8.0'
fi

docker run --name wp-tests-mysql -e MYSQL_ALLOW_EMPTY_PASSWORD=yes -e MYSQL_DATABASE=$DB_NAME -p 3306:3306 -d mysql:$MYSQL_TAG --default-authentication-plugin=mysql_native_password

# wait for mysql to initialize
MAX_TRIES=20
for i in $(seq 1 $MAX_TRIES); do
docker exec wp-tests-mysql mysqladmin ping --silent && break
sleep 3
done
}

install_wp
install_test_suite
create_db
