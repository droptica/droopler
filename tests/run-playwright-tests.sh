#!/usr/bin/env bash

set -euo pipefail

# This script checks if Python 3 is installed.
# Then it checks if DDEV is available (optional).
# It creates or uses a Python virtual environment in /tests/.venv,
# installs Playwright and Pytest, then runs a simple test from test_playwright.py.

# Get the directory of this script to handle relative paths
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

# Check Python version (we need 3.7+)
if ! command -v python3 > /dev/null 2>&1; then
    log_error "Python 3 not found. Please install Python 3 and try again."
    exit 1
fi

# Simpler version check
PYTHON_VERSION=$(python3 -c 'import sys; v=sys.version_info; print(v.major*100 + v.minor)')
if [ "$PYTHON_VERSION" -lt 307 ]; then
    log_error "Python 3.7 or higher is required. Found version: $(python3 --version)"
    exit 1
fi

# Check and start DDEV
if command -v ddev > /dev/null 2>&1; then
    if [ -d "${PROJECT_ROOT}/.ddev" ]; then
        # Skip DDEV startup check since we assume project is already running

        # Improved Drupal installation check
        if ! ddev exec drush status --fields=bootstrap | grep -q "Successful"; then
            if ! ddev exec drush status --fields=bootstrap | grep -q "Successful"; then
                log_error "Cannot connect to Drupal. Please ensure DDEV is running and Drupal is installed."
                exit 1
            fi
        fi

        # Set admin password
        log_info "Setting up admin user credentials..."
        ddev drush upwd admin admin

        log_info "DDEV is running"
    else
        log_warn "DDEV is installed but no .ddev directory found in project root"
    fi
else
    log_warn "DDEV not found. Tests will use default localhost URL"
fi

# Setup virtual environment
VENV_DIR="${SCRIPT_DIR}/.venv"
if [ ! -d "$VENV_DIR" ]; then
    log_info "Creating new virtual environment in ${VENV_DIR}..."
    python3 -m venv "$VENV_DIR"
fi

# Activate virtual environment
# shellcheck disable=SC1091
source "$VENV_DIR/bin/activate"

# Install dependencies
log_info "Installing/upgrading dependencies..."
pip install --upgrade pip
pip install --upgrade pytest playwright pyyaml

# Install Playwright browsers
log_info "Installing Playwright browsers..."
playwright install chromium

# Run the tests
log_info "Running Playwright tests..."
TEST_FILE="${SCRIPT_DIR}/test_playwright.py"
pytest "$TEST_FILE" -v

# Cleanup
deactivate

log_info "Tests completed!"
