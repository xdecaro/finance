#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(tr -d '\r\n' < "$ROOT/VERSION")"
DIST="$ROOT/dist"
WORK="$ROOT/build/.work"
rm -rf "$DIST" "$WORK"
mkdir -p "$DIST" "$WORK/component" "$WORK/package"

cp -R "$ROOT/component/." "$WORK/component/"
( cd "$WORK/component" && zip -qr "$DIST/com_decarofinance_${VERSION}.zip" . )

cp "$ROOT/package/pkg_decarofinance.xml" "$WORK/package/pkg_decarofinance.xml"
cp "$DIST/com_decarofinance_${VERSION}.zip" "$WORK/package/com_decarofinance.zip"
( cd "$WORK/package" && zip -qr "$DIST/pkg_decarofinance_${VERSION}.zip" . )

( cd "$DIST" && sha256sum "com_decarofinance_${VERSION}.zip" "pkg_decarofinance_${VERSION}.zip" > SHA256SUMS.txt )

echo "Built Finance by xdecaro $VERSION"
