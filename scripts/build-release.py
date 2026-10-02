#!/usr/bin/env python3
"""Build the directory submission from runtime files only."""
from pathlib import Path
import re
import zipfile

root = Path(__file__).resolve().parent.parent
version = re.search(r'^Version: (.+)$', (root / 'woonuxt.php').read_text(), re.M).group(1)
assert f'Stable tag: {version}' in (root / 'readme.txt').read_text()
assert f"'WOONUXT_SETTINGS_VERSION', '{version}'" in (root / 'includes/constants.php').read_text()
excluded = set((root / '.distignore').read_text().splitlines())
files = [root / 'woonuxt.php', root / 'readme.txt']
for folder in ('includes', 'templates', 'assets'):
    files.extend(p for p in (root / folder).rglob('*') if p.is_file() and not any(part.startswith('.') for part in p.relative_to(root).parts) and p.relative_to(root).as_posix() not in excluded)
output = root / 'output' / f'settings-for-woonuxt-{version}.zip'
output.parent.mkdir(exist_ok=True)
with zipfile.ZipFile(output, 'w', zipfile.ZIP_DEFLATED) as archive:
    for path in sorted(files):
        archive.write(path, 'settings-for-woonuxt/' + path.relative_to(root).as_posix())
print(output)
