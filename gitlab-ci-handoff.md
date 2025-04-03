# GitLab CI Implementation Plan for Droopler

## Current Status
- GitLab CI pipeline is configured but fails with "413 Request Entity Too Large" error
- Need to implement JS_capable and Acceptance tests
- **Step 1 implemented**: Basic CI configuration with artifact size optimization
- **Step 2 implemented**: Added JS_capable tests with Selenium Chrome and MariaDB
- **Step 3 implemented**: Added Acceptance tests with PhpBrowser and MariaDB
- **Step 4 implemented**: Optimized Docker images and fixed Apache configuration

## Implementation Steps

### Step 1: Basic CI Configuration
- [x] Create a basic CI configuration that builds the project
- [x] Optimize artifact size to avoid "413 Request Entity Too Large" error
- [x] Set up caching for dependencies

### Step 2: Add JS_capable Tests
- [x] Configure JS_capable test environment with Selenium Chrome
- [x] Set up MariaDB service
- [x] Run Codeception JS_capable tests
- [x] Collect test results

### Step 3: Add Acceptance Tests
- [x] Configure Acceptance test environment
- [x] Set up MariaDB service
- [x] Run Codeception Acceptance tests
- [x] Collect test results

### Step 4: Optimize Pipeline
- [x] Further optimize artifact handling
- [x] Use specialized Docker images (drupalci/php-8.3-apache, drupalci/mariadb-10.6)
- [x] Fix Apache configuration issues
- [x] Add debugging commands for troubleshooting

## Requirements
- PHP 8.3
- MariaDB 10.6
- Selenium Chrome for JS_capable tests
- Codeception testing framework

## Notes
- Droopler is a Drupal distribution/profile with recipes
- Tests are located in the `tests` directory
- JS_capable tests require Selenium Chrome
- Acceptance tests use PhpBrowser
