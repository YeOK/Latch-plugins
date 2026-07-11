#!/usr/bin/env bash
# Build per-plugin and bundle zips for GitHub Releases.
#
# Usage: ./scripts/build-zips.sh [tag]
# Example: ./scripts/build-zips.sh v1.0.0
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TAG="${1:-v1.0.0}"
OUT="${ROOT}/releases"
BUNDLE_SLUGS=(forum-stats image-upload word-filter spam-bridge slack-notify)

mkdir -p "${OUT}"
rm -f "${OUT}"/*.zip

if ! command -v zip >/dev/null 2>&1; then
    echo "Error: zip(1) required" >&2
    exit 1
fi

build_plugin_zip() {
    local slug="$1"
    local dir="${ROOT}/${slug}"
    local version
    version="$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["version"];' "${dir}/plugin.json")"
    local zip_name="${slug}-${version}.zip"
    local staging="${OUT}/.stage-${slug}"

    if [[ ! -f "${dir}/plugin.json" ]]; then
        echo "Error: missing ${dir}/plugin.json" >&2
        exit 1
    fi

    rm -rf "${staging}"
    mkdir -p "${staging}/${slug}"
    rsync -a --exclude='.git' "${dir}/" "${staging}/${slug}/"
    (cd "${staging}" && zip -qr "${OUT}/${zip_name}" "${slug}")
    rm -rf "${staging}"
    echo "Wrote ${OUT}/${zip_name}"
}

for slug in "${BUNDLE_SLUGS[@]}"; do
    build_plugin_zip "${slug}"
done

bundle_name="latch-plugins-${TAG#v}.zip"
bundle_stage="${OUT}/.stage-bundle"
rm -rf "${bundle_stage}"
mkdir -p "${bundle_stage}"

for slug in "${BUNDLE_SLUGS[@]}"; do
    rsync -a --exclude='.git' "${ROOT}/${slug}/" "${bundle_stage}/${slug}/"
done

(cd "${bundle_stage}" && zip -qr "${OUT}/${bundle_name}" .)
rm -rf "${bundle_stage}"
echo "Wrote ${OUT}/${bundle_name}"
echo "Attach ${OUT}/*.zip to GitHub Release ${TAG}"