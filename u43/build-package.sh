#!/bin/bash

# U43 Plugin Build and Package Script
# Creates an optimized zip file for WordPress installation

set -e

PLUGIN_DIR="$(cd "$(dirname "$0")" && pwd)"
PLUGIN_NAME="u43"
# Extract version from u43.php (compatible with macOS grep)
VERSION=$(grep "Version:" "$PLUGIN_DIR/u43.php" | sed -E 's/.*Version:[[:space:]]*([0-9.]+).*/\1/' || echo "1.0.0")
BUILD_DIR="$PLUGIN_DIR/build"
ZIP_NAME="${PLUGIN_NAME}-v${VERSION}.zip"

echo "🚀 Building U43 WordPress Plugin Package"
echo "=========================================="
echo "Plugin Directory: $PLUGIN_DIR"
echo "Version: $VERSION"
echo ""

# Step 1: Build frontend assets
echo "📦 Step 1: Building frontend assets..."
cd "$PLUGIN_DIR"
if [ ! -d "node_modules" ]; then
    echo "⚠️  node_modules not found. Running npm install..."
    npm install
fi
npm run build

if [ ! -d "admin/assets/dist" ]; then
    echo "❌ Build failed: admin/assets/dist directory not found"
    exit 1
fi

echo "✅ Frontend assets built successfully"
echo ""

# Step 2: Create build directory
echo "📁 Step 2: Preparing build directory..."
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/$PLUGIN_NAME"

# Step 3: Copy files (excluding dev files)
echo "📋 Step 3: Copying plugin files..."

# Copy all files except exclusions
rsync -av \
    --exclude='node_modules' \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='.DS_Store' \
    --exclude='Thumbs.db' \
    --exclude='*.log' \
    --exclude='*.cache' \
    --exclude='.env' \
    --exclude='.env.local' \
    --exclude='.idea' \
    --exclude='.vscode' \
    --exclude='*.swp' \
    --exclude='*.swo' \
    --exclude='*~' \
    --exclude='package.json' \
    --exclude='package-lock.json' \
    --exclude='vite.config.js' \
    --exclude='tailwind.config.js' \
    --exclude='postcss.config.js' \
    --exclude='admin/src' \
    --exclude='admin/public' \
    --exclude='docs' \
    --exclude='README*.md' \
    --exclude='*.md' \
    --exclude='PHASE*.md' \
    --exclude='QUICK*.md' \
    --exclude='TESTING*.md' \
    --exclude='STRUCTURE.md' \
    --exclude='check-errors.php' \
    --exclude='debug.php' \
    --exclude='build-package.sh' \
    --exclude='*.zip' \
    "$PLUGIN_DIR/" "$BUILD_DIR/$PLUGIN_NAME/"

# Remove build directory and any other temporary files from the package
rm -rf "$BUILD_DIR/$PLUGIN_NAME/build"
rm -rf "$BUILD_DIR/$PLUGIN_NAME/build-package.sh" 2>/dev/null || true

echo "✅ Files copied successfully"
echo ""

# Step 4: Create zip file
echo "📦 Step 4: Creating zip package..."
cd "$BUILD_DIR"
zip -r "$ZIP_NAME" "$PLUGIN_NAME" -q

# Move zip to plugin directory
mv "$ZIP_NAME" "$PLUGIN_DIR/"

# Cleanup build directory
rm -rf "$BUILD_DIR"

echo "✅ Package created: $ZIP_NAME"
echo ""
echo "📊 Package Summary:"
echo "   File: $PLUGIN_DIR/$ZIP_NAME"
echo "   Size: $(du -h "$PLUGIN_DIR/$ZIP_NAME" | cut -f1)"
echo ""
echo "✨ Build complete! Ready for WordPress installation."
echo ""

