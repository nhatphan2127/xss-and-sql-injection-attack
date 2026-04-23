#!/bin/bash

# Helper script to toggle between VULNERABLE and SECURE mode

if [ "$1" == "secure" ]; then
    echo "🔒 Switching to SECURE mode..."
    sed -i '' "s/SECURE_MODE: \"false\"/SECURE_MODE: \"true\"/" docker-compose.yml
    echo "✅ docker-compose.yml updated"
    echo ""
    echo "Starting containers in SECURE mode..."
    docker-compose up --build
    
elif [ "$1" == "vulnerable" ]; then
    echo "⚠️  Switching to VULNERABLE mode..."
    sed -i '' "s/SECURE_MODE: \"true\"/SECURE_MODE: \"false\"/" docker-compose.yml
    echo "✅ docker-compose.yml updated"
    echo ""
    echo "Starting containers in VULNERABLE mode..."
    docker-compose up --build
    
elif [ "$1" == "status" ]; then
    echo "📊 Current SECURE_MODE setting:"
    grep -A 2 "environment:" docker-compose.yml | grep "SECURE_MODE"
    
else
    echo "Usage: ./toggle-mode.sh [secure|vulnerable|status]"
    echo ""
    echo "Examples:"
    echo "  ./toggle-mode.sh secure     - Run in SECURE mode"
    echo "  ./toggle-mode.sh vulnerable - Run in VULNERABLE mode"
    echo "  ./toggle-mode.sh status     - Check current mode"
fi
