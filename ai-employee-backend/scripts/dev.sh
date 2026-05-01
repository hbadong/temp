#!/usr/bin/env bash
# Development startup script for AI Employee Backend

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== AI Employee Backend - Development Server ===${NC}"

# Check if .env exists
if [ ! -f .env ]; then
    echo -e "${YELLOW}No .env file found. Creating from .env.example...${NC}"
    cp .env.example .env
    echo -e "${YELLOW}Please update .env with your configuration${NC}"
fi

# Check Python version
PYTHON_VERSION=$(python3 --version 2>&1 | awk '{print $2}' | cut -d. -f1,2)
REQUIRED_VERSION="3.11"

if [ "$(printf '%s\n' "$REQUIRED_VERSION" "$PYTHON_VERSION" | sort -V | head -n1)" != "$REQUIRED_VERSION" ]; then
    echo -e "${RED}Error: Python $REQUIRED_VERSION or higher is required${NC}"
    exit 1
fi

# Install dependencies
echo -e "${GREEN}Installing dependencies...${NC}"
pip install -e ".[dev]" -q

# Run database migrations
echo -e "${GREEN}Running database migrations...${NC}"
if command -v alembic &> /dev/null; then
    alembic upgrade head 2>/dev/null || echo -e "${YELLOW}Warning: Migration failed. Make sure DATABASE_URL is configured correctly.${NC}"
fi

# Start development server
echo -e "${GREEN}Starting development server...${NC}"
echo -e "${GREEN}API Docs: http://localhost:8000/docs${NC}"
echo -e "${GREEN}Health: http://localhost:8000/health${NC}"
echo ""

uvicorn src.ai_employee.main:app --reload --host 0.0.0.0 --port 8000
