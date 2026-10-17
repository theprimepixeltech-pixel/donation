#!/usr/bin/env python3
"""Build a Hostinger upload ZIP with public/private files split safely."""
import argparse
import stat
import zipfile
from pathlib import Path
from urllib.parse import urlsplit

ROOT = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--url', required=True, help='HTTPS website origin, without a path')
parser.add_argument('--output', type=Path, default=ROOT/'dist'/'hostinger-site.zip')
args = parser.parse_args()
url = urlsplit(args.url)
if url.scheme != 'https' or not url.hostname or url.username or url.password or url.path not in ('', '/') or url.query or url.fragment:
    parser.error('--url must be an HTTPS origin without credentials, query or path')
if not (ROOT/'vendor/autoload.php').is_file():
    parser.error('Run composer install --no-dev first; dependencies must be included in the upload.')
args.output.parent.mkdir(parents=True, exist_ok=True)

def add(archive, name, content, directory=False):
    info = zipfile.ZipInfo(name.rstrip('/')+'/' if directory else name)
    info.create_system = 3
    info.external_attr = ((stat.S_IFDIR | 0o755) if directory else (stat.S_IFREG | 0o644)) << 16
    if directory:
        info.external_attr |= 0x10
    info.compress_type = zipfile.ZIP_DEFLATED
    archive.writestr(info, content)

with zipfile.ZipFile(args.output, 'w') as archive:
    for directory in ['public_html', 'hope-private', 'hope-private/storage', 'hope-private/storage/uploads', 'hope-private/public']:
        add(archive, directory, b'', True)
    for source, destination in [('app','hope-private/app'),('vendor','hope-private/vendor'),('bin','hope-private/bin'),('public/assets','public_html/assets')]:
        add(archive, destination, b'', True)
        for path in sorted((ROOT/source).rglob('*')):
            relative = path.relative_to(ROOT/source)
            if '.git' in relative.parts or '__pycache__' in relative.parts:
                continue
            if path.is_symlink():
                raise SystemExit(f'Refusing to package symlink: {path}')
            if path.is_dir():
                add(archive, str(Path(destination)/relative), b'', True)
            if path.is_file():
                add(archive, str(Path(destination)/relative), path.read_bytes())
    for source, destination in [('public/index.php','hope-private/public/index.php'),('public/.htaccess','public_html/.htaccess'),('composer.json','hope-private/composer.json'),('composer.lock','hope-private/composer.lock')]:
        add(archive, destination, (ROOT/source).read_bytes())
    origin = args.url.rstrip('/').replace('\\', '\\\\').replace("'", "\\'")
    add(archive, 'hope-private/hosting.php', "<?php\n// Non-secret hosting settings. Keep this file outside public_html.\nif (!getenv('HOPE_URL')) putenv('HOPE_URL="+origin+"');\n")
    add(archive, 'public_html/index.php', "<?php\n$private = dirname(__DIR__) . '/hope-private';\nrequire $private . '/hosting.php';\ndefine('HOPE_APP_ROOT', $private);\nrequire $private . '/public/index.php';\n")
    add(archive, 'HOSTINGER-INSTRUCTIONS.txt', (ROOT/'docs/HOSTINGER.md').read_bytes())
print(f'Created {args.output} ({args.output.stat().st_size:,} bytes)')
print('Extract in the domain directory ABOVE public_html, keeping hope-private beside public_html.')
