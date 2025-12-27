#!/usr/bin/env bash
set -euo pipefail

# Run PHPUnit suites (unit, and integration when added) from project root
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

# Always run unit tests
vendor/bin/phpunit tests/unit "$@"

# Run integration tests if/when the folder exists
if [ -d "tests/integration" ]; then
	vendor/bin/phpunit tests/integration "$@"
fi
