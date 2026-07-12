#!/usr/bin/env bash
# Build all catalog zips, upload to GitHub, and verify every asset is present.
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

echo "==> Pre-flight: catalog.json release + plugin.json versions"
php -r '
$root = $argv[1];
$tag = $argv[2];
$catalog = json_decode(file_get_contents($root . "/catalog.json"), true);
if (($catalog["release"] ?? "") !== $tag) {
    fwrite(STDERR, "Error: catalog.json release must be {$tag} before publish\n");
    exit(1);
}
' "${ROOT}" "${TAG}"

if ! gh release view "${TAG}" --repo YeOK/Latch-plugins >/dev/null 2>&1; then
    echo "Error: GitHub release ${TAG} not found. Create it first:" >&2
    echo "  gh release create ${TAG} --repo YeOK/Latch-plugins --title \"Latch-plugins ${TAG}\" --notes-file docs/RELEASE.md" >&2
    exit 1
fi

"${ROOT}/scripts/build-zips.sh" "${TAG}"

echo "==> Uploading all zips to ${TAG}"
gh release upload "${TAG}" "${ROOT}/releases"/*.zip \
    --repo YeOK/Latch-plugins \
    --clobber

echo "==> Verify local + GitHub assets"
"${ROOT}/scripts/check-release.sh" "${TAG}"

echo "Publish complete: ${TAG}"