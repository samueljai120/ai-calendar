#!/bin/bash

# Check if version number is provided
if [ -z "$1" ]; then
    echo "Please provide a version number (e.g. ./release.sh 1.0.7)"
    exit 1
fi

VERSION=$1

# Update version in main plugin file
sed -i '' "s/Version: .*/Version: $VERSION/" ai-calendar.php
sed -i '' "s/define('AI_CALENDAR_VERSION', '.*')/define('AI_CALENDAR_VERSION', '$VERSION')/" ai-calendar.php

# Update version in readme.txt
sed -i '' "s/Stable tag: .*/Stable tag: $VERSION/" readme.txt

# Update version in package.json
sed -i '' "s/\"version\": \".*\"/\"version\": \"$VERSION\"/" package.json

# Add and commit version changes
git add ai-calendar.php readme.txt package.json
git commit -m "Bump version to $VERSION"

# Create and push tag
git tag -a "v$VERSION" -m "Release version $VERSION"
git push origin main
git push origin "v$VERSION"

echo "Version $VERSION has been released!"
echo "The GitHub Action workflow will now:"
echo "1. Create both development and distribution packages"
echo "2. Create a GitHub release with both versions"
echo "3. Upload the packages to the release" 