#!/usr/bin/env bash

# Resolve script directory and project root
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$DIR/../.." && pwd)"
SASS_BIN="$PROJECT_ROOT/misc/dart-sass/sass"

if [ ! -x "$SASS_BIN" ]; then
    echo "SASS binary not found or not executable: $SASS_BIN" >&2
    exit 1
fi

"$SASS_BIN" -w \
"$PROJECT_ROOT/public/assets/sass/bootstrap-custom.scss:$PROJECT_ROOT/public/assets/css/vendor/bootstrap-corenominal.css"

exit 0