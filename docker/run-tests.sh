#!/bin/bash
set -e

echo "======================================"
echo "Testing on PHP $(php -v | head -n 1)"
echo "======================================"
echo ""

# Install dependencies (must be done after volume mount)
echo "Installing dependencies..."
composer install --no-interaction --ignore-platform-req=ext-frankenphp --quiet

echo ""
echo "=== Running PHPUnit ==="
./vendor/bin/phpunit --colors=never 2>&1 | tail -5

echo ""
echo "=== Running Rector ==="
./vendor/bin/rector --dry-run --no-progress-bar 2>&1 | grep -E "OK|changes|would" || echo "Rector completed"

echo ""
echo "=== Running PHPStan ==="
./vendor/bin/phpstan analyse --no-progress --memory-limit=-1 2>&1 | tail -10 || echo "PHPStan check completed"

echo ""
echo "=== Running Composer Require Checker ==="
./vendor/bin/composer-require-checker check --config-file=composer-require-checker.json 2>&1 | tail -10 || echo "Composer Require Checker completed"

echo ""
echo "======================================"
echo "All checks completed for PHP $(php -v | head -n 1)"
echo "======================================"
