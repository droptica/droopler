#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

# Function to print error messages to stderr
error_exit() {
  echo "ERROR: $1" >&2
  exit 1
}

# --- Dynamically Install Matching ChromeDriver ---
echo "Determining Chrome version..."
CHROME_FULL_VERSION=$(google-chrome --version | cut -d ' ' -f 3)
if [ -z "$CHROME_FULL_VERSION" ]; then
  error_exit "Could not determine installed Chrome version."
fi
echo "Detected Chrome full version: ${CHROME_FULL_VERSION}"

# Extract the major version (milestone)
CHROME_MAJOR_VERSION=$(echo "$CHROME_FULL_VERSION" | cut -d '.' -f 1)
echo "Detected Chrome major version (milestone): ${CHROME_MAJOR_VERSION}"

# Use the JSON endpoint for latest versions per milestone
# See: https://googlechromelabs.github.io/chrome-for-testing/
echo "Finding matching ChromeDriver version using JSON endpoints..."
API_URL_MILESTONE="https://googlechromelabs.github.io/chrome-for-testing/latest-versions-per-milestone-with-downloads.json"
API_URL_STABLE="https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json"

CHROMEDRIVER_VERSION=$(curl -s "$API_URL_MILESTONE" | jq -r --arg MILESTONE "$CHROME_MAJOR_VERSION" '.milestones[$MILESTONE].version')

# Fallback to latest known good stable if milestone match not found
if [ -z "$CHROMEDRIVER_VERSION" ] || [ "$CHROMEDRIVER_VERSION" == "null" ]; then
  echo "ChromeDriver version for milestone ${CHROME_MAJOR_VERSION} not found. Falling back to latest known good stable ChromeDriver."
  CHROMEDRIVER_VERSION=$(curl -s "$API_URL_STABLE" | jq -r '.channels.Stable.version')
fi

# Final check if we got a version
if [ -z "$CHROMEDRIVER_VERSION" ] || [ "$CHROMEDRIVER_VERSION" == "null" ]; then
  error_exit "Could not determine a suitable ChromeDriver version from JSON endpoints."
fi

echo "Determined ChromeDriver version to install: ${CHROMEDRIVER_VERSION}"
DOWNLOAD_URL="https://storage.googleapis.com/chrome-for-testing-public/${CHROMEDRIVER_VERSION}/linux64/chromedriver-linux64.zip"

echo "Downloading ChromeDriver from ${DOWNLOAD_URL}"
wget -q -O /tmp/chromedriver.zip "${DOWNLOAD_URL}"

echo "Unzipping ChromeDriver..."
unzip /tmp/chromedriver.zip -d /tmp/

echo "Moving ChromeDriver to /usr/local/bin/"
mv /tmp/chromedriver-linux64/chromedriver /usr/local/bin/chromedriver

echo "Setting execute permissions..."
chmod +x /usr/local/bin/chromedriver

echo "Cleaning up temporary files..."
rm -rf /tmp/chromedriver.zip /tmp/chromedriver-linux64

echo "Verifying installed ChromeDriver version:"
chromedriver --version

echo "ChromeDriver installation completed successfully."
# --- End Dynamic ChromeDriver Install ---
