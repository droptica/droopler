# GitLab CI Implementation Plan for Droopler

## Current Status
- GitLab CI pipeline is configured but fails with "413 Request Entity Too Large" error
- Need to implement JS_capable and Acceptance tests
- **Step 1 implemented**: Basic CI configuration with artifact size optimization

## Implementation Steps

### Step 1: Basic CI Configuration
- [x] Create a basic CI configuration that builds the project
- [x] Optimize artifact size to avoid "413 Request Entity Too Large" error
- [x] Set up caching for dependencies

### Step 2: Add JS_capable Tests
- [  ] Configure JS_capable test environment with Selenium Chrome
- [  ] Set up MariaDB service
- [  ] Run Codeception JS_capable tests
- [  ] Collect test results

### Step 3: Add Acceptance Tests
- [  ] Configure Acceptance test environment
- [  ] Set up MariaDB service
- [  ] Run Codeception Acceptance tests
- [  ] Collect test results

### Step 4: Optimize Pipeline
- [  ] Further optimize artifact handling
- [  ] Implement caching strategies
- [  ] Add test result reporting

## Requirements
- PHP 8.3
- MariaDB 10.11
- Selenium Chrome for JS_capable tests
- Codeception testing framework

## Notes
- Droopler is a Drupal distribution/profile with recipes
- Tests are located in the `tests` directory
- JS_capable tests require Selenium Chrome
- Acceptance tests use PhpBrowser
