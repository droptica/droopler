#!/usr/bin/env bash

###
# Launches Drupal CMS using DDEV.
#
# This requires that DDEV be installed and available in the PATH, and only works in
# Unix-like environments (Linux, macOS, or the Windows Subsystem for Linux). This will
# initialize DDEV configuration, start the containers, install dependencies, and open
# Drupal CMS in the browser.
###

# Abort this entire script if any one command fails.
set -e

if ! command -v ddev >/dev/null; then
  echo "DDEV needs to be installed. Visit https://ddev.com/get-started for instructions."
  exit 1
fi

NAME=$(basename $PWD)
# If there are any other DDEV projects in this system with this name, add a numeric suffix.
declare -i n=$(ddev list | grep --count "$NAME")
if [ $n > 0 ]; then
  NAME=$NAME-$(expr $n + 1)
fi

# Configure DDEV if not already done.
test -d .ddev || ddev config --project-type=drupal11 --docroot=web --php-version=8.3 --ddev-version-constraint=">=1.24.0" --project-name="$NAME"
# Install the Selenium add-on.
ddev add-on get ddev/ddev-selenium-standalone-chrome
# Start your engines.
ddev start
# Install dependencies if not already done.
test -f composer.lock || ddev composer install

# Copy the DDEV commands to the project.
cp -r ddev_commands/* .ddev/commands/

# Copy the starter theme to the project.
cp -r starter-theme/ web/themes/custom/

ask_yes_no() {
    while true; do
        read -p "$1 [y/n]: " yn
        case $yn in
            [Yy]* ) return 0;;
            [Nn]* ) return 1;;
            * ) echo "Please answer yes (y) or no (n).";;
        esac
    done
}

# Ask about removing installation files
if ask_yes_no "Would you like to remove installation files and directories (.git, ddev_commands, starter-theme)?"; then
    echo "Removing installation files..."
    rm -rf .git
    rm -rf ddev_commands
    rm -rf starter-theme

    # Only ask about git init if files were removed
    if ask_yes_no "Would you like to initialize a new git repository?"; then
        echo "Initializing new git repository..."
        git init
    fi
else
    echo "Keeping installation files."
fi

#show the welcome message
echo -e "\nCongratulations, you’ve installed Droopler CMS!
         Next steps:
         \u2022 Run “ddev launch” to install Droopler in a browser
         \u2022 Run “ddev drush site-install droopler” to install Droopler in a terminal
         \u2022 Get support: http://drupal.org/project/droopler/  -> “Issues for Droopler”\n"
