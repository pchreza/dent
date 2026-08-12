#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="${1:-0.1.2}"
RELEASE_NAME="dent-release-${VERSION}-cpanel"
OUTPUT_DIR="${ROOT_DIR}/../releases"
STAGING_DIR="${OUTPUT_DIR}/${RELEASE_NAME}"
ZIP_PATH="${OUTPUT_DIR}/${RELEASE_NAME}.zip"
SHA_PATH="${OUTPUT_DIR}/${RELEASE_NAME}.sha256"

rm -rf "${STAGING_DIR}" "${ZIP_PATH}" "${SHA_PATH}"
mkdir -p "${STAGING_DIR}"

cd "${ROOT_DIR}"
tar \
  --exclude='./.git' \
  --exclude='./.env' \
  --exclude='./vendor' \
  --exclude='./node_modules' \
  --exclude='./tests' \
  --exclude='./phpunit.xml' \
  --exclude='./vite.config.js' \
  --exclude='./package.json' \
  --exclude='./pnpm-lock.yaml' \
  --exclude='./storage/logs' \
  --exclude='./storage/framework/cache' \
  --exclude='./storage/framework/sessions' \
  --exclude='./storage/framework/views' \
  --exclude='./storage/framework/testing' \
  --exclude='./storage/app/private' \
  --exclude='./database/database.sqlite' \
  -cf - . | tar -xf - -C "${STAGING_DIR}"

# Laravel requires its runtime directories to exist, but generated cache files
# and local runtime data must never be shipped in a public release.
mkdir -p \
  "${STAGING_DIR}/bootstrap/cache" \
  "${STAGING_DIR}/storage/logs" \
  "${STAGING_DIR}/storage/framework/cache" \
  "${STAGING_DIR}/storage/framework/sessions" \
  "${STAGING_DIR}/storage/framework/views" \
  "${STAGING_DIR}/storage/framework/testing" \
  "${STAGING_DIR}/storage/app/private"
rm -f "${STAGING_DIR}/bootstrap/cache/"*.php

COMMIT="$(git rev-parse HEAD)"
TEST_SUMMARY="${TEST_SUMMARY:-Run acceptance checks before packaging}"
cat > "${STAGING_DIR}/RELEASE-MANIFEST.txt" <<EOF
Dent Dental SaaS — Clean cPanel Release ${VERSION}
Commit: ${COMMIT}
Built: $(date -u +%Y-%m-%dT%H:%M:%SZ)
Tests: ${TEST_SUMMARY}
Vendor: intentionally excluded; install with Composer from composer.lock.
Secrets/local data: excluded; create .env from .env.example.
EOF

cd "${OUTPUT_DIR}"
zip -qr "${ZIP_PATH}" "${RELEASE_NAME}"
sha256sum "${ZIP_PATH}" | tee "${SHA_PATH}"

if unzip -l "${ZIP_PATH}" | grep -E '(^|/)(\.env$|vendor/|node_modules/|tests/|\.git/|database/database\.sqlite|storage/logs/[^/]+|storage/app/private/[^/]+|storage/framework/testing/[^/]+|bootstrap/cache/[^/]+\.php)' >/dev/null; then
  echo "Release validation failed: forbidden files found." >&2
  exit 1
fi

printf 'Clean release created: %s\n' "${ZIP_PATH}"
