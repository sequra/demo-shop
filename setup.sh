#!/usr/bin/env bash
#
# One-time developer setup for the SeQura Checkout Demo.
# Enables the shared git hooks under .githooks/ so the quality gates run
# automatically. Safe to re-run.

set -euo pipefail

cd "$(git rev-parse --show-toplevel)"

git config core.hooksPath .githooks
chmod +x .githooks/* 2>/dev/null || true

echo "✓ Git hooks enabled (core.hooksPath=.githooks)."
echo "  pre-commit:  php -l on staged PHP + scoped phpcs (when the container is up)"
echo "  pre-push:    full phpcs over src/ on PHP-touching pushes (when the container is up)"
