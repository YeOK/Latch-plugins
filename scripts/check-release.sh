#!/usr/bin/env bash
# Verify catalog.json, plugin.json versions, local zips, and (optionally) GitHub assets.
#
# Usage:
#   ./scripts/check-release.sh              # local + GitHub (catalog release tag)
#   ./scripts/check-release.sh --local      # releases/ dir only
#   ./scripts/check-release.sh --github     # GitHub release assets only
#   ./scripts/check-release.sh v1.0.3       # explicit tag
#
# Exit 0 when all expected zips are present; non-zero with a clear error list otherwise.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO="${GITHUB_REPO:-YeOK/Latch-plugins}"
CHECK_LOCAL=0
CHECK_GITHUB=0
TAG=""

for arg in "$@"; do
    case "$arg" in
        --local) CHECK_LOCAL=1 ;;
        --github) CHECK_GITHUB=1 ;;
        -h|--help)
            sed -n '2,11p' "$0"
            exit 0
            ;;
        *)
            TAG="${arg#v}"
            TAG="v${TAG}"
            ;;
    esac
done

if [[ "${CHECK_LOCAL}" -eq 0 && "${CHECK_GITHUB}" -eq 0 ]]; then
    CHECK_LOCAL=1
    CHECK_GITHUB=1
fi

if [[ -z "${TAG}" ]]; then
    TAG="$(php -r 'echo json_decode(file_get_contents($argv[1]), true)["release"];' "${ROOT}/catalog.json")"
fi

if [[ -z "${TAG}" ]]; then
    echo "Error: could not determine release tag (pass vX.Y.Z or set catalog.json release)" >&2
    exit 1
fi

mapfile -t EXPECTED < <(php -r '
$root = $argv[1];
$tag = $argv[2];
$catalog = json_decode(file_get_contents($root . "/catalog.json"), true);
if (!is_array($catalog)) {
    fwrite(STDERR, "Error: invalid catalog.json\n");
    exit(1);
}
$catalogRelease = (string) ($catalog["release"] ?? "");
if ($catalogRelease !== $tag) {
    fwrite(STDERR, "Error: catalog.json release is {$catalogRelease}, expected {$tag}\n");
    exit(1);
}
$bundle = "latch-plugins-" . ltrim($tag, "v") . ".zip";
echo $bundle . "\n";
foreach ($catalog["plugins"] as $entry) {
    $slug = (string) ($entry["slug"] ?? "");
    $version = (string) ($entry["version"] ?? "");
    if ($slug === "" || $version === "") {
        fwrite(STDERR, "Error: catalog entry missing slug or version\n");
        exit(1);
    }
    $pluginJson = $root . "/" . $slug . "/plugin.json";
    if (!is_file($pluginJson)) {
        fwrite(STDERR, "Error: missing {$slug}/plugin.json\n");
        exit(1);
    }
    $manifest = json_decode(file_get_contents($pluginJson), true);
    $manifestVersion = (string) ($manifest["version"] ?? "");
    if ($manifestVersion !== $version) {
        fwrite(STDERR, "Error: {$slug} catalog version {$version} != plugin.json {$manifestVersion}\n");
        exit(1);
    }
    echo $slug . "-" . $version . ".zip\n";
}
' "${ROOT}" "${TAG}")

errors=0

check_list() {
    local label="$1"
    shift
    local -a have=("$@")
    local missing=()

    for want in "${EXPECTED[@]}"; do
        local found=0
        for got in "${have[@]}"; do
            if [[ "${got}" == "${want}" ]]; then
                found=1
                break
            fi
        done
        if [[ "${found}" -eq 0 ]]; then
            missing+=("${want}")
        fi
    done

    if [[ "${#missing[@]}" -gt 0 ]]; then
        echo "Error: ${label} missing ${#missing[@]} asset(s) for ${TAG}:" >&2
        printf '  - %s\n' "${missing[@]}" >&2
        errors=1
        return 1
    fi

    echo "${label}: OK (${#EXPECTED[@]} zips for ${TAG})"
}

if [[ "${CHECK_LOCAL}" -eq 1 ]]; then
    mapfile -t LOCAL < <(find "${ROOT}/releases" -maxdepth 1 -name '*.zip' -printf '%f\n' 2>/dev/null | sort)
    if [[ "${#LOCAL[@]}" -eq 0 ]]; then
        echo "Error: no zips in releases/ — run ./scripts/build-zips.sh ${TAG}" >&2
        errors=1
    else
        check_list "Local releases/" "${LOCAL[@]}" || true
    fi
fi

if [[ "${CHECK_GITHUB}" -eq 1 ]]; then
    if ! command -v gh >/dev/null 2>&1; then
        echo "Error: gh CLI required for --github check" >&2
        exit 1
    fi
    if ! gh release view "${TAG}" --repo "${REPO}" >/dev/null 2>&1; then
        echo "Error: GitHub release ${TAG} not found on ${REPO}" >&2
        errors=1
    else
        mapfile -t REMOTE < <(gh release view "${TAG}" --repo "${REPO}" --json assets --jq '.assets[].name' | sort)
        check_list "GitHub ${REPO} ${TAG}" "${REMOTE[@]}" || true
    fi
fi

if [[ "${errors}" -ne 0 ]]; then
    echo >&2
    echo "Expected zips for ${TAG}:" >&2
    printf '  - %s\n' "${EXPECTED[@]}" >&2
    echo "Fix: ./scripts/publish-release.sh ${TAG}" >&2
    exit 1
fi

exit 0