#!/bin/bash

# Startup script for IP Whitelist Manager Service
# Runs PHP built-in server on port 9999

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PORT=9999
HOST=0.0.0.0
PID_FILE="$SCRIPT_DIR/data/server.pid"
LOG_FILE="$SCRIPT_DIR/data/server.log"

cd "$SCRIPT_DIR"

# Check if server is already running
if [ -f "$PID_FILE" ]; then
    PID=$(cat "$PID_FILE")
    if ps -p "$PID" > /dev/null 2>&1; then
        echo "Server is already running (PID: $PID)"
        exit 1
    else
        rm -f "$PID_FILE"
    fi
fi

# Start PHP built-in server
echo "Starting IP Whitelist Manager on $HOST:$PORT..."
php -S "$HOST:$PORT" > "$LOG_FILE" 2>&1 &
SERVER_PID=$!

# Save PID
echo $SERVER_PID > "$PID_FILE"

echo "Server started with PID: $SERVER_PID"
echo "Access the service at: http://localhost:$PORT"
echo "Log file: $LOG_FILE"
echo ""
echo "To stop the server, run: kill $SERVER_PID"
echo "Or use: ./stop.sh"

