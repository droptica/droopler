import pytest
from playwright.sync_api import sync_playwright
import os
import shutil
from typing import Generator
import yaml
from datetime import datetime

def get_ddev_url() -> str:
    """Get base URL from DDEV configuration or fallback to default"""
    try:
        # Try to read .ddev/config.yaml for the URL
        ddev_config_path = os.path.join(os.path.dirname(os.path.dirname(__file__)), '.ddev', 'config.yaml')

        if os.path.exists(ddev_config_path):
            with open(ddev_config_path, 'r') as f:
                config = yaml.safe_load(f)
                project_name = config.get('name')
                if project_name:
                    return f"https://{project_name}.ddev.site"
    except Exception:
        pass

    # Fallback to default URL
    return "http://127.0.0.1:8080"

def archive_existing_screenshots() -> None:
    """Move existing screenshots to a backup folder"""
    screenshots_dir = os.path.join(os.path.dirname(__file__), "screenshots")

    # Skip if screenshots directory doesn't exist
    if not os.path.exists(screenshots_dir):
        return

    # Skip if directory is empty
    if not os.listdir(screenshots_dir):
        return

    # Create backup directory name with timestamp
    timestamp = datetime.now().strftime("%Y-%m-%d-%H-%M-%S")
    backup_dir = os.path.join(screenshots_dir, f"bak-{timestamp}")

    # Create backup directory
    os.makedirs(backup_dir)

    # Move all files (except backup directories) to backup directory
    for filename in os.listdir(screenshots_dir):
        file_path = os.path.join(screenshots_dir, filename)
        if os.path.isfile(file_path):  # Only move files, not directories
            shutil.move(file_path, os.path.join(backup_dir, filename))

def save_screenshot(page, name: str) -> None:
    """Save a screenshot with a numbered filename"""
    # Create screenshots directory if it doesn't exist
    screenshots_dir = os.path.join(os.path.dirname(__file__), "screenshots")
    os.makedirs(screenshots_dir, exist_ok=True)

    # Get list of existing screenshots to determine next number
    existing_files = os.listdir(screenshots_dir)
    numbers = [int(f[:4]) for f in existing_files if f[:4].isdigit()]
    next_num = 1 if not numbers else max(numbers) + 1

    # Create filename with number and timestamp
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    filename = f"{next_num:04d}_{name}_{timestamp}.png"

    # Save screenshot
    page.screenshot(path=os.path.join(screenshots_dir, filename))

def test_login_page_loads() -> None:
    """Test if the login page loads and login works"""
    # Archive existing screenshots before starting test
    archive_existing_screenshots()

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        base_url = get_ddev_url()

        try:
            # Go to login page
            response = page.goto(f"{base_url}/user/login", wait_until="networkidle")

            # Verify the page loaded successfully
            assert response is not None, "Failed to load login page"
            assert response.ok, f"Login page returned status {response.status}"

            # Verify we're on the login page
            assert "user/login" in page.url, "Not on the login page"

            # Take screenshot of login page
            save_screenshot(page, "login_page")

            # Verify login form is present
            assert page.locator("form#user-login-form").count() > 0, "Login form not found"

            # Fill in login form
            page.fill("input[name='name']", "admin")
            page.fill("input[name='pass']", "admin")

            # Click submit and wait for navigation
            with page.expect_navigation():
                page.click("input[type='submit']")

            # Take screenshot after login attempt
            save_screenshot(page, "after_login_attempt")

            # Multiple ways to verify successful login
            success_indicators = [
                # Check if we're no longer on login page
                lambda: "user/login" not in page.url,
                # Check for presence of toolbar (even if hidden)
                lambda: page.locator("#toolbar-administration").count() > 0,
                # Check for admin menu items
                lambda: page.locator(".toolbar-menu-administration").count() > 0,
                # Check for logged-in user class
                lambda: page.locator("body.user-logged-in").count() > 0
            ]

            # Check all success indicators
            login_successful = all(indicator() for indicator in success_indicators)
            assert login_successful, "Login verification failed"

            # Take screenshot of logged-in state
            save_screenshot(page, "login_successful")

            # Additional verification - try to access admin area
            response = page.goto(f"{base_url}/admin/content", wait_until="networkidle")
            assert response.ok, "Could not access admin area"
            save_screenshot(page, "admin_area_access")

        except Exception as e:
            save_screenshot(page, "error_login_test")
            raise e
        finally:
            browser.close()
