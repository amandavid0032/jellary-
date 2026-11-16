#!/bin/bash
# Run WP-CLI against the local store: ./wp.sh plugin list
cd "$(dirname "$0")" && docker compose run --rm -T wpcli "$@"
