#!/usr/bin/env bash
set -euo pipefail

# Run PHP-CS-Fixer over the project
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

CONFIG_FILE="$ROOT_DIR/.php-cs-fixer.php"

vendor/bin/php-cs-fixer fix --config="$CONFIG_FILE" --allow-risky=no "$@"
