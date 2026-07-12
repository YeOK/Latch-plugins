#!/usr/bin/env bash
# Build all catalog zips and attach them to an existing GitHub Release tag.
#
# Usage: ./scripts/publish-release.sh v1.0.3
#
# Prereqs: gh auth login, zip(1), catalog.json "release" must match TAG.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TAG="${1:-}"

if [[ -z "${TAG}" ]]; then
    echo "Usage: $0 <tag>   e.g. v1.0.3" >&2
    exit 1
fi

TAG="${TAG#v}"
TAG="v${TAG}"

CATALOG_RELEASE="$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["release"];' "${ROOT}/catalog.json")"
if [[ "${CATALOG_RELEASE}" != "${TAG}" ]]; then
    echo "Error: catalog.json release is ${CATALOG_RELEASE}, expected ${TAG}" >&2
    exit 1
fi

if ! gh release view "${TAG}" --repo YeOK/Latch-plugins >/dev/null 2>&1; then
    echo "Error: GitHub release ${TAG} not found. Create it first:" >&2
    echo "  gh release create ${TAG} --repo YeOK/Latch-plugins --title \"Latch-plugins ${TAG}\" --notes \"...\"" >&2
    exit 1
fi

"${ROOT}/scripts/build-zips.sh" "${TAG}"

echo "==> Uploading all zips to ${TAG}"
gh release upload "${TAG}" "${ROOT}/releases"/*.zip \
    --repo YeOK/Latch-plugins \
    --clobber

echo "==> Assets on ${TAG}:"
gh release view "${TAG}" --repo YeOK/Latch-plugins --json assets --jq '.assets[].name'