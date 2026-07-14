#!/bin/bash

echo "===================================="
echo "  HospiLink MERN Stack Launcher"
echo "===================================="
echo ""

# Check if MongoDB is running
echo "[1/3] Checking MongoDB..."
if pgrep -x "mongod" > /dev/null
then
    echo "✓ MongoDB is running"
else
    echo "✗ MongoDB is NOT running!"
    echo "Please start MongoDB first:"
    echo "  sudo systemctl start mongod"
    echo ""
    exit 1
fi

echo ""
echo "[2/3] Starting Backend Server..."
cd backend
npm run dev &
BACKEND_PID=$!
cd ..

sleep 3

echo ""
echo "[3/3] Starting Frontend Server..."
cd frontend
npm start &
FRONTEND_PID=$!
cd ..

echo ""
echo "===================================="
echo "  HospiLink is running!"
echo "===================================="
echo ""
echo "Backend:  http://localhost:5000"
echo "Frontend: http://localhost:3000"
echo ""
echo "Press Ctrl+C to stop all servers"
echo ""

# Wait for Ctrl+C
trap "kill $BACKEND_PID $FRONTEND_PID; exit" INT
wait
