#!/bin/sh
# Start a private, socket-only MariaDB instance using the installed XAMPP runtime.
cd -- "$(dirname -- "$0")/.." || exit 1
runtime=$(command -v php)
if [ -z "$runtime" ] && [ -x "$HOME/.config/herd-lite/bin/php" ]; then runtime="$HOME/.config/herd-lite/bin/php"; fi
if [ -z "$runtime" ] && [ -x /Applications/XAMPP/xamppfiles/bin/php ]; then runtime=/Applications/XAMPP/xamppfiles/bin/php; fi
if [ -z "$runtime" ]; then echo 'PHP was not found.'; exit 1; fi
# An explicitly configured database stays under the user's control.
if [ -n "${MEDIFOLLOW_DB_HOST:-}${MEDIFOLLOW_DB_SOCKET:-}${MEDIFOLLOW_DB_NAME:-}" ]; then exit 0; fi
if "$runtime" -r 'require "app/database.php"; try { database(); exit(0); } catch (PDOException $e) { exit(1); }' 2>/dev/null; then exit 0; fi
base=/Applications/XAMPP/xamppfiles
if [ ! -x "$base/sbin/mysqld" ] || [ ! -x "$base/bin/mysql_install_db" ]; then
    echo 'Start MySQL in XAMPP and check config/database.php.'; exit 1
fi
storage="$HOME/Documents/Codex/MediFollow-data"
data="$storage/mysql"
socket="$storage/mysql.sock"
umask 077
mkdir -p "$storage" || exit 1
if [ ! -d "$data/mysql" ]; then
    echo 'Preparing the local MediFollow database for the first time…'
    "$base/bin/mysql_install_db" --no-defaults --basedir="$base" --datadir="$data" --auth-root-authentication-method=normal >"$storage/initialize.log" 2>&1 || {
        echo 'The database could not be prepared. See the local database log.'; exit 1;
    }
fi
if [ -S "$socket" ] && "$base/bin/mysqladmin" --no-defaults --socket="$socket" -uroot ping >/dev/null 2>&1; then exit 0; fi
"$base/sbin/mysqld" --no-defaults --basedir="$base" --datadir="$data" --socket="$socket" --pid-file="$storage/mysql.pid" --skip-networking --log-error="$storage/mysql.log" </dev/null >>"$storage/mysql.log" 2>&1 &
database_pid=$!
attempt=0
while [ "$attempt" -lt 60 ]; do
    if "$base/bin/mysqladmin" --no-defaults --socket="$socket" -uroot ping >/dev/null 2>&1; then
        echo 'Local MediFollow database is ready.'; exit 0
    fi
    if ! kill -0 "$database_pid" 2>/dev/null; then break; fi
    attempt=$((attempt + 1))
    sleep 0.2
done
echo 'The local database could not start. See ~/Documents/Codex/MediFollow-data/mysql.log.'
exit 1
