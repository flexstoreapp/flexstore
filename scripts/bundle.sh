#!/usr/bin/env bash
# scripts/bundle.sh
#
# Bundles the free edition of Flexstore for publishing.
#
# Produces:
#   build/flexstore-<version>/            Ready-to-ship app folder
#   build/flexstore-<version>.zip         The app code, unwrapped, at the zip root
#
# The zip ships with composer dependencies installed and frontend assets built,
# so the customer only has to upload it and open the installer.
#
# Version is taken from the most recent git tag (e.g. v1.2.0 -> 1.2.0).
# If no tag exists the script still builds the folder but labels it "dev".
#
# Usage:
#   ./scripts/bundle.sh [-v|--verbose]
#
# Flags:
#   -v, --verbose   Stream composer/npm output to the terminal. By default the
#                   output is captured to a temp log and only the tail is
#                   printed if the command fails.

set -euo pipefail

VERBOSE=false
for arg in "$@"; do
    case "$arg" in
        -v|--verbose) VERBOSE=true ;;
        -h|--help)
            sed -n '2,22p' "$0"
            exit 0
            ;;
        *) echo "Unknown argument: $arg" >&2; exit 1 ;;
    esac
done

# ---------------------------------------------------------------------------
# Pretty output helpers
# ---------------------------------------------------------------------------

if [ -t 1 ]; then
    C_RESET='\033[0m'
    C_BOLD='\033[1m'
    C_DIM='\033[2m'
    C_BLUE='\033[34m'
    C_GREEN='\033[32m'
    C_YELLOW='\033[33m'
    C_RED='\033[31m'
else
    C_RESET=''; C_BOLD=''; C_DIM=''; C_BLUE=''; C_GREEN=''; C_YELLOW=''; C_RED=''
fi

step()   { printf "${C_BLUE}${C_BOLD}==>${C_RESET} ${C_BOLD}%s${C_RESET}\n" "$1"; }
info()   { printf "    ${C_DIM}%s${C_RESET}\n" "$1"; }
warn()   { printf "${C_YELLOW}${C_BOLD}!! ${C_RESET}%s\n" "$1"; }
fail()   { printf "${C_RED}${C_BOLD}xx ${C_RESET}%s\n" "$1" >&2; exit 1; }
ok()     { printf "${C_GREEN}${C_BOLD}ok${C_RESET}  %s\n" "$1"; }

# run_quiet <cmd> [args...]
# Shows the full command in muted text and appends its stdout/stderr to
# $BUILD_LOG. In --verbose mode the command streams to the terminal instead.
# On failure the last lines of the log are printed before aborting.
run_quiet() {
    local label="$*"
    if [ "$VERBOSE" = true ]; then
        printf "    ${C_DIM}\$ %s${C_RESET}\n" "$label"
        "$@"
        return
    fi
    printf "    ${C_DIM}%s${C_RESET}" "$label"
    if "$@" >>"$BUILD_LOG" 2>&1; then
        printf " ${C_GREEN}done${C_RESET}\n"
    else
        local rc=$?
        printf " ${C_RED}failed${C_RESET}\n"
        printf "\n${C_RED}${C_BOLD}--- tail of ${BUILD_LOG} ---${C_RESET}\n" >&2
        tail -n 80 "$BUILD_LOG" >&2 || true
        printf "${C_RED}${C_BOLD}--- end ---${C_RESET}\n" >&2
        fail "command failed (exit $rc). Full log: $BUILD_LOG"
    fi
}

# ---------------------------------------------------------------------------
# Run from repo root regardless of where the script was invoked from
# ---------------------------------------------------------------------------

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "$REPO_ROOT"

# ---------------------------------------------------------------------------
# Pre-flight: required binaries
# ---------------------------------------------------------------------------

step "Checking required tools"
REQUIRED=(git zip rsync composer npm php)
for cmd in "${REQUIRED[@]}"; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
        fail "Required command '$cmd' is not installed."
    fi
done
ok "All tools present."

# ---------------------------------------------------------------------------
# Determine version from latest git tag
# ---------------------------------------------------------------------------

step "Resolving version from git tags"

LATEST_TAG="$(git tag --list --sort=-creatordate 'v*' | head -n 1 || true)"

if [ -n "$LATEST_TAG" ]; then
    VERSION="${LATEST_TAG#v}"
    info "Latest tag: $LATEST_TAG (version $VERSION)"
else
    VERSION="dev-$(git rev-parse --short HEAD 2>/dev/null || echo unknown)"
    warn "No git tags found, labelling build as '$VERSION'."
fi

# Warn on dirty working tree so the user knows the bundle contains uncommitted code
if [ -n "$(git status --porcelain)" ]; then
    warn "Working tree is dirty. The bundle will include uncommitted changes."
fi

BUILD_ROOT="${REPO_ROOT}/build"
RELEASE_NAME="flexstore-${VERSION}"
APP_DIR="${BUILD_ROOT}/${RELEASE_NAME}"

# Wipe any previous run for this version so the build is reproducible
rm -rf "$APP_DIR"
mkdir -p "$APP_DIR"

# Capture composer/npm output to a temp log. Cleaned up on exit (success or fail).
BUILD_LOG="$(mktemp -t flexstore-bundle.XXXXXX)"
cleanup() {
    rm -f "$BUILD_LOG"
}
trap cleanup EXIT

# ---------------------------------------------------------------------------
# Stage source into build/<stage>/ with exclusions
# ---------------------------------------------------------------------------

step "Staging source into ${APP_DIR}"

# Exclusions applied during rsync. Paths are relative to the repo root.
RSYNC_EXCLUDES=(
    # Version control & CI
    '.git'
    '.github'

    # OS / editor noise
    '.DS_Store'

    # Dev/build tooling not needed at runtime
    'node_modules'
    'vendor'

    # Project docs & agent metadata
    'README.md'
    'skills-lock.json'

    # Folders excluded per request
    '/build'
    '/scripts'

    # Octane binaries, pulled in by octane:install rather than committed
    'frankenphp'
    'frankenphp-worker.php'
    'rr'
    '.rr.yaml'
    'caddy'
    '**/caddy'

    # Environment & credentials
    '.env'

    # Local SQLite DB, whatever it was named
    'database/*.sqlite*'
    'database/*.sqlite3*'
    'storage/*.sqlite*'
    'storage/*.sqlite3*'

    # Runtime / user-specific state
    'public/hot'
    'public/storage'
    'public/build'
    'storage/installed'
    'storage/framework/installer_key'
    'storage/framework/schema-state.json*'
    'bootstrap/cache/*.php'
    'bootstrap/ssr'
    'storage/pail/*'
    'storage/logs/*'
    'storage/framework/cache/data/*'
    'storage/framework/sessions/*'
    'storage/framework/views/*'
    'storage/framework/testing/*'
    'storage/app/public/*'
    'storage/app/private/*'
    'storage/app/temp/*'

    # Wayfinder-generated TS files are regenerated during the staging build
    'resources/js/actions'
    'resources/js/routes'
    'resources/js/wayfinder'
    'resources/js/types/translations.ts'
)

RSYNC_ARGS=(-a --delete)
for pattern in "${RSYNC_EXCLUDES[@]}"; do
    RSYNC_ARGS+=(--exclude="$pattern")
done

rsync "${RSYNC_ARGS[@]}" ./ "$APP_DIR/"

# Re-add .gitkeep-style placeholders so Laravel's expected dirs exist at runtime.
for d in \
    storage/logs \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/app/public \
    storage/app/private \
    storage/app/temp \
    bootstrap/cache
do
    mkdir -p "$APP_DIR/$d"
    [ -f "$APP_DIR/$d/.gitignore" ] || printf "*\n!.gitignore\n" > "$APP_DIR/$d/.gitignore"
done

ok "Source staged."

# ---------------------------------------------------------------------------
# Install production dependencies inside the staging dir
# ---------------------------------------------------------------------------

step "Copying .env.example to .env"
cp "${REPO_ROOT}/.env.example" "${APP_DIR}/.env"

step "Installing composer dependencies"
(
    cd "$APP_DIR"
    run_quiet composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-progress
)

step "Generating application key"
(
    cd "$APP_DIR"
    run_quiet php artisan key:generate --ansi
)

step "Installing npm dependencies"
(
    cd "$APP_DIR"
    if [ -f package-lock.json ]; then
        run_quiet npm ci --omit=dev --no-audit --no-fund --loglevel=error
    else
        run_quiet npm install --omit=dev --no-audit --no-fund --loglevel=error
    fi
)

step "Building frontend assets"
(
    cd "$APP_DIR"
    run_quiet npm run build --silent
)

# node_modules is no longer needed in the shipped bundle. Vite output is in public/build/.
step "Pruning dev-only artifacts from staging"
rm -rf "${APP_DIR}/node_modules"
# Remove anything rsync re-picked that we still don't want in the zip (defense in depth)
find "$APP_DIR" -name '.DS_Store' -delete 2>/dev/null || true

# ---------------------------------------------------------------------------
# Write a VERSION file so the installed app can report its own version
# ---------------------------------------------------------------------------

BUILD_DATE="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

cat > "${APP_DIR}/VERSION" <<EOF
version: ${VERSION}
built:   ${BUILD_DATE}
EOF

APP_SIZE="$(du -sh "$APP_DIR" | awk '{print $1}')"
ok "App: ${APP_DIR}/ (${APP_SIZE})"

# ---------------------------------------------------------------------------
# Zip the app folder for distribution
# ---------------------------------------------------------------------------

RELEASE_ZIP="${BUILD_ROOT}/${RELEASE_NAME}.zip"
rm -f "$RELEASE_ZIP"

step "Zipping app folder"
(
    cd "$APP_DIR"
    zip -qrX "${RELEASE_ZIP}" .
)
RELEASE_ZIP_SIZE="$(du -h "$RELEASE_ZIP" | awk '{print $1}')"
ok "Release zip: ${RELEASE_ZIP} (${RELEASE_ZIP_SIZE})"
