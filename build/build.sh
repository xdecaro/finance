#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
VERSION="$(tr -d '\r\n' < "$ROOT/VERSION")"
DIST="$ROOT/dist"
rm -rf "$DIST"; mkdir -p "$DIST"
ROOT="$ROOT" VERSION="$VERSION" python3 - <<'PY'
from pathlib import Path
from zipfile import ZipFile, ZipInfo, ZIP_DEFLATED
import hashlib, os
root=Path(os.environ['ROOT']); version=os.environ['VERSION']; dist=root/'dist'; fixed=(2026,1,1,0,0,0)
def add_bytes(zf,arc,data,executable=False):
    info=ZipInfo(arc,fixed); info.compress_type=ZIP_DEFLATED; info.create_system=3; info.external_attr=((0o755 if executable else 0o644)&0xffff)<<16; zf.writestr(info,data)
def add_tree(zf,source):
    for p in sorted(x for x in source.rglob('*') if x.is_file()): add_bytes(zf,p.relative_to(source).as_posix(),p.read_bytes(),os.access(p,os.X_OK))
def make(name,source):
    target=dist/name
    with ZipFile(target,'w') as zf:add_tree(zf,source)
    return target
component=make(f'com_decarofinance_{version}.zip',root/'component')
analytics=make(f'plg_xdecaroanalytics_decarofinance_{version}.zip',root/'plugins/xdecaroanalytics/decarofinance')
task=make(f'plg_task_decarofinance_{version}.zip',root/'plugins/task/decarofinance')
package=dist/f'pkg_decarofinance_{version}.zip'
with ZipFile(package,'w') as zf:
    add_bytes(zf,'pkg_decarofinance.xml',(root/'package/pkg_decarofinance.xml').read_bytes())
    add_bytes(zf,'script.php',(root/'package/script.php').read_bytes())
    add_bytes(zf,'com_decarofinance.zip',component.read_bytes())
    add_bytes(zf,'plg_xdecaroanalytics_decarofinance.zip',analytics.read_bytes())
    add_bytes(zf,'plg_task_decarofinance.zip',task.read_bytes())
files=[component,analytics,task,package]
(dist/'SHA256SUMS.txt').write_text(''.join(f'{hashlib.sha256(p.read_bytes()).hexdigest()}  {p.name}\n' for p in files),encoding='utf-8')
PY
echo "Built Finance by xdecaro $VERSION"
