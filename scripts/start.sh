#!/bin/sh
cd -- "$(dirname -- "$0")/.." || exit 1

runtime=$(command -v php)
if [ -z "$runtime" ] && [ -x "$HOME/.config/herd-lite/bin/php" ]; then
    runtime="$HOME/.config/herd-lite/bin/php"
fi
if [ -z "$runtime" ] && [ -x /Applications/XAMPP/xamppfiles/bin/php ]; then
    runtime=/Applications/XAMPP/xamppfiles/bin/php
fi
if [ -z "$runtime" ]; then
    echo 'PHP could not be found. Install or start XAMPP, then try again.'
    printf 'Press Return to close. '
    read -r answer
    exit 1
fi

bind_address=127.0.0.1
if [ "${1:-}" = '--wifi' ]; then bind_address=0.0.0.0; fi
/bin/sh scripts/database-server.sh || true
"$runtime" scripts/setup.php || true
port=8080
page=''
while [ "$port" -le 8090 ]; do
    page=$(/usr/bin/curl --silent --fail --max-time 1 "http://127.0.0.1:$port/login.php" 2>/dev/null)
    case "$page" in
        *'MediFollow | Sign in'*)
            url="http://127.0.0.1:$port/login.php"
            /usr/bin/open "$url" >/dev/null 2>&1 || /usr/bin/open -a Safari "$url" >/dev/null 2>&1 || true
            printf 'MediFollow is already running: %s\n' "$url"
            if [ "$bind_address" = '0.0.0.0' ]; then
                echo 'If your phone cannot connect, close the earlier launcher and start the Wi-Fi launcher again.'
            fi
            exit 0
            ;;
    esac
    if ! /usr/sbin/lsof -nP -iTCP:"$port" -sTCP:LISTEN -t >/dev/null 2>&1; then break; fi
    port=$((port + 1))
done
if [ "$port" -gt 8090 ]; then
    echo 'No available port was found. Close an earlier local server and try again.'
    exit 1
fi

url="http://127.0.0.1:$port/login.php"
"$runtime" -S "$bind_address:$port" -t public &
server_pid=$!
cleanup() {
    kill "$server_pid" 2>/dev/null || true
    wait "$server_pid" 2>/dev/null || true
}
trap cleanup EXIT
trap 'exit 0' INT TERM HUP

attempt=0
ready=0
while [ "$attempt" -lt 40 ]; do
    if /usr/bin/curl --silent --fail --max-time 1 "$url" >/dev/null 2>&1; then ready=1; break; fi
    if ! kill -0 "$server_pid" 2>/dev/null; then break; fi
    attempt=$((attempt + 1))
    sleep 0.2
done
if [ "$ready" -ne 1 ]; then
    echo 'MediFollow could not start. Check the message above and try again.'
    exit 1
fi
printf '\nMediFollow is ready: %s\n' "$url"
if [ "$bind_address" = '0.0.0.0' ]; then
    phone_address=''
    for wifi_interface in en0 en1; do
        phone_address=$(/usr/sbin/ipconfig getifaddr "$wifi_interface" 2>/dev/null)
        if [ -n "$phone_address" ]; then break; fi
    done
    if [ -n "$phone_address" ]; then
        printf 'On your phone (same Wi-Fi): http://%s:%s/login.php\n' "$phone_address" "$port"
    else
        echo 'Connect to Wi-Fi, then use this Mac’s Wi-Fi IP address and the port shown above.'
    fi
fi
/usr/bin/open "$url" >/dev/null 2>&1 || /usr/bin/open -a Safari "$url" >/dev/null 2>&1 || echo 'Open the address above in your browser.'
echo 'Keep this window open while using MediFollow. Press Control+C to stop.'
wait "$server_pid"
