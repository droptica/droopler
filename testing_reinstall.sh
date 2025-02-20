#!/usr/bin/env bash

echo "WARNING! This script will remove untracked files (except the .ddev directory) and DROP the database."
echo "Are you sure you want to proceed? [y/n]"
read user_choice

if [ "$user_choice" != "y" ]; then
echo "Aborting."
exit 1
fi

echo "Cleaning untracked files (excluding .ddev)..."
git clean -fdx --exclude=.ddev

echo "Running launch-droopler-cms.sh..."
./launch-droopler-cms.sh

echo "Dropping the database..."
ddev drush sql:drop -y

echo "Opening the project in your browser..."
ddev launch
