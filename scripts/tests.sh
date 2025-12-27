#!/usr/bin/env bash
set -euo pipefail

# Run PHPUnit test suite from project root
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

vendor/bin/phpunit "$@"
