# Playwright Tests for Droopler/Drupal

This directory contains automated tests using **Playwright** for testing Droopler/Drupal sites.
The tests are designed to work with **DDEV**-based local environments on both macOS and Linux.

## Prerequisites

- **Python 3.7 or higher**
- **DDEV** (recommended, but not required)
- A running **Droopler/Drupal** instance

## Quick Start

The easiest way to run the tests is to use the provided shell script:

```bash
./run-playwright-tests.sh
```

## Directory Structure

```
tests/
├── screenshots/         # Test screenshots (gitignored except .gitkeep)
│   └── .gitkeep
├── run-playwright-tests.sh  # Main test runner script
├── test_playwright.py      # Test cases
├── requirements.txt        # Python dependencies
└── README.md              # This file
```

## Writing New Tests

### Basic Test Structure

Tests are written in Python using the Playwright framework. Here's a basic template:

```python
def test_your_feature():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)
        page = browser.new_page()
        base_url = get_ddev_url()

        try:
            # Your test steps here
            response = page.goto(f"{base_url}/your-path")
            assert response.ok, "Page failed to load"

            # Take screenshots for debugging
            save_screenshot(page, "descriptive_name")

        except Exception as e:
            save_screenshot(page, "error_your_feature")
            raise e
        finally:
            browser.close()
```

### Helper Functions

- `get_ddev_url()`: Returns the DDEV site URL or fallback URL
- `save_screenshot(page, name)`: Takes numbered screenshots with timestamps
- `archive_existing_screenshots()`: Archives old screenshots before new test runs

### Screenshots

Screenshots are automatically:
- Numbered sequentially (0001, 0002, etc.)
- Timestamped
- Saved in `tests/screenshots/`
- Archived in dated backup folders before each test run

## Running Tests

### Using the Script

```bash
./run-playwright-tests.sh
```

The script will:
1. Check Python installation
2. Create/activate virtual environment
3. Install dependencies
4. Run the tests
5. Clean up

### Manual Run

```bash
# Create and activate virtual environment
python3 -m venv .venv
source .venv/bin/activate

# Install dependencies
pip install -r requirements.txt

# Run tests
pytest test_playwright.py -v
```

## Debugging

- Screenshots are automatically taken at key points
- Failed tests create error screenshots
- Old screenshots are archived in `screenshots/bak-YYYY-MM-DD-HH-MM-SS/`
- Check the pytest output for detailed error messages

## Contributing

When adding new tests:
1. Follow the existing test structure
2. Use helper functions for common operations
3. Take screenshots at important steps
4. Add proper assertions and error handling
5. Update this README if adding new features

## Common Issues

- If DDEV is not detected, tests will use localhost:8080
- Screenshots directory is automatically created
- Virtual environment (.venv) is gitignored
