#!/bin/bash

# Stop script for IP Whitelist Manager Service

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PID_FILE="$SCRIPT_DIR/data/server.pid"

if [ ! -f "$PID_FILE" ]; then
    echo "Server is not running (PID file not found)"
    exit 1
fi

PID=$(cat "$PID_FILE")

if ps -p "$PID" > /dev/null 2>&1; then
    kill "$PID"
    rm -f "$PID_FILE"
    echo "Server stopped (PID: $PID)"
else
    echo "Server process not found (PID: $PID)"
    rm -f "$PID_FILE"
fi

