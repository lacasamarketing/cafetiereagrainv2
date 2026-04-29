"""
Deploiement FTP local cafetieregrain.fr
=========================================
Alternative a GitHub Actions : upload local direct vers OVH.

Usage :
    python scripts/deploy.py

Pre-requis :
    - Python 3.7+
    - Fichier .env.deploy a la racine du repo (gitignore) avec :
        FTP_HOST=ftp.cluster127.hosting.ovh.net
        FTP_USER=lacasav-grain
        FTP_PASSWORD=ton-password-ftp
        FTP_PATH=/

Le script :
    1. Lit les credentials FTP
    2. Genere config/config.php a partir des secrets
    3. Connecte au FTP OVH
    4. Upload tous les fichiers (incremental - skip si meme taille)
    5. Affiche le progres en live
"""

from ftplib import FTP, error_perm
from pathlib import Path
import os
import sys
import time

# ============= CONFIG =============
ROOT = Path(__file__).parent.parent.resolve()
ENV_FILE = ROOT / '.env.deploy'

# Liste des chemins a EXCLURE du deploiement
# NOTE : 'scripts' n'est PLUS exclu globalement. On veut deployer les scripts PHP
# (cron generate_article.php, sync_top_products.php, discover_*.php).
# Seuls les .py et build_*.py restent exclus via EXCLUDE_EXTS et EXCLUDE_FILES.
EXCLUDE_DIRS = {
    '.git', '.github', '_data', '_mockup',
    'node_modules', 'vendor', '.idea', '.vscode',
}
EXCLUDE_FILES = {
    '.gitignore', '.gitattributes',
    '.htaccess.production', 'index-full.php.backup',
    # 'home.php' : on le DEPLOIE maintenant (include depuis index.php)
    'index-waiting.php',  # ancienne page d'attente, archivee
    'README.md', 'llms.txt',
    'info.php', 'install.php', 'test.php',
    '.ftp-deploy-sync-state.json', '.env.deploy', '.env',
    'deploy.py',
    # Scripts Python locaux qui n'ont pas leur place en prod
    'build_import_sql.py', 'reset_db.py', 'check_db.py',
}
EXCLUDE_PATTERNS = (
    '_mockup-',
)
# .py exclus = aucun script Python ne part en prod (pas besoin sur OVH)
# .sql exclus = migrations a executer manuellement via PHPMyAdmin (jamais auto)
EXCLUDE_EXTS = ('.md', '.sql', '.py', '.bak', '.swp', '.tmp', '.log')

# ============= LOAD ENV =============
def load_env(path: Path) -> dict:
    if not path.exists():
        print(f'[ERREUR] Fichier {path} introuvable.')
        print('Cree ce fichier avec :')
        print('  FTP_HOST=ftp.cluster127.hosting.ovh.net')
        print('  FTP_USER=lacasav-grain')
        print('  FTP_PASSWORD=...')
        print('  FTP_PATH=/')
        sys.exit(1)
    env = {}
    with open(path, 'r', encoding='utf-8') as f:
        for line in f:
            line = line.strip()
            if not line or line.startswith('#'):
                continue
            if '=' in line:
                k, v = line.split('=', 1)
                env[k.strip()] = v.strip().strip('"').strip("'")
    return env

# ============= GENERATE CONFIG.PHP =============
def generate_config(env: dict):
    cfg_dir = ROOT / 'config'
    cfg_dir.mkdir(exist_ok=True)
    cfg_file = cfg_dir / 'config.php'

    content = '''<?php
return [
    'env' => 'prod',
    'base_url' => 'https://cafetiereagrain.fr',
    'db' => [
        'host' => '{DB_HOST}',
        'name' => '{DB_NAME}',
        'user' => '{DB_USER}',
        'pass' => '{DB_PASS}',
        'charset' => 'utf8mb4',
    ],
    'security' => [
        'app_key' => '{APP_KEY}',
        'session_lifetime' => 7200,
        'max_login_attempts' => 5,
    ],
    'api_token' => '{API_TOKEN}',
    'anthropic_api_key' => '{ANTHROPIC_API_KEY}',
    'anthropic_model' => 'claude-sonnet-4-6',
    'rainforest_api_key' => '{RAINFOREST_API_KEY}',
    'mangools' => [
        'api_key' => '{MANGOOLS_API_KEY}',
        'enabled' => true,
        'monthly_budget' => {MANGOOLS_BUDGET},
        'fallback_seeds_only' => true,
    ],
    'affiliate' => [
        'amazon_tag' => '{AMAZON_TAG}',
        'amazon_base' => 'https://www.amazon.fr',
    ],
    'admin_email' => 'bonjour@lacasamarketing.fr',
    'analytics' => [
        'ga4_id' => '{GA4_ID}',
        'plausible_domain' => '{PLAUSIBLE_DOMAIN}',
    ],
    'sync' => [
        'top_daily_count' => 3,
        'full_weekly_day' => 0,
    ],
];
'''.format(
        DB_HOST=env.get('DB_HOST', ''),
        DB_NAME=env.get('DB_NAME', ''),
        DB_USER=env.get('DB_USER', ''),
        DB_PASS=env.get('DB_PASS', ''),
        APP_KEY=env.get('APP_KEY', ''),
        API_TOKEN=env.get('API_TOKEN', ''),
        ANTHROPIC_API_KEY=env.get('ANTHROPIC_API_KEY', ''),
        RAINFOREST_API_KEY=env.get('RAINFOREST_API_KEY', ''),
        MANGOOLS_API_KEY=env.get('MANGOOLS_API_KEY', ''),
        MANGOOLS_BUDGET=env.get('MANGOOLS_BUDGET', '10'),
        AMAZON_TAG=env.get('AMAZON_TAG', 'lacasamarke08-21'),
        GA4_ID=env.get('GA4_ID', ''),
        PLAUSIBLE_DOMAIN=env.get('PLAUSIBLE_DOMAIN', ''),
    )
    cfg_file.write_text(content, encoding='utf-8')
    print(f'  [config] generated config/config.php ({len(content)} bytes)')

# ============= FILE FILTERING =============
def should_skip(rel_path: Path) -> bool:
    parts = rel_path.parts
    # Exclude top-level dirs
    for p in parts[:-1]:
        if p in EXCLUDE_DIRS:
            return True
    name = rel_path.name
    if name in EXCLUDE_FILES:
        return True
    for pat in EXCLUDE_PATTERNS:
        if pat in name:
            return True
    if rel_path.suffix in EXCLUDE_EXTS:
        return True
    return False

def collect_files() -> list:
    files = []
    for root, dirs, fnames in os.walk(ROOT):
        rel_root = Path(root).relative_to(ROOT)
        # Filter dirs in-place
        dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS]
        for fname in fnames:
            rel = rel_root / fname
            if should_skip(rel):
                continue
            files.append(rel)
    return sorted(files)

# ============= FTP UPLOAD =============
def ftp_upload_all(env: dict, files: list):
    host = env['FTP_HOST']
    user = env['FTP_USER']
    pwd  = env['FTP_PASSWORD']
    base_path = env.get('FTP_PATH', '/').strip() or '/'

    print(f'\n[FTP] Connexion a {host} ...')
    try:
        ftp = FTP(host, timeout=30)
        ftp.login(user, pwd)
        ftp.set_pasv(True)
    except Exception as e:
        print(f'[ERREUR] Connexion FTP echouee : {e}')
        sys.exit(1)
    print(f'  Connecte. Pwd : {ftp.pwd()}')

    if base_path != '/' and base_path != './':
        try:
            ftp.cwd(base_path)
            print(f'  Cwd vers {base_path}')
        except error_perm as e:
            print(f'  cwd {base_path} echoue ({e}), reste a {ftp.pwd()}')

    # Cache des dossiers crees pour eviter de retenter
    created_dirs = set()

    def ensure_dir(remote_dir: str):
        if remote_dir in created_dirs or remote_dir in ('', '.'):
            return
        # Cree recursivement
        parts = remote_dir.split('/')
        cur = ''
        for p in parts:
            if not p:
                continue
            cur = cur + '/' + p if cur else p
            if cur in created_dirs:
                continue
            try:
                ftp.mkd(cur)
            except error_perm:
                pass  # exists already
            created_dirs.add(cur)

    total = len(files)
    print(f'\n[Upload] {total} fichiers a deployer\n')

    start = time.time()
    bytes_total = 0
    for i, rel in enumerate(files, 1):
        local = ROOT / rel
        remote = str(rel).replace('\\', '/')
        remote_dir = '/'.join(remote.split('/')[:-1])
        ensure_dir(remote_dir)
        try:
            with open(local, 'rb') as f:
                ftp.storbinary(f'STOR {remote}', f)
            size = local.stat().st_size
            bytes_total += size
            mark = 'OK'
        except Exception as e:
            mark = f'ERR ({e})'
            size = 0
        pct = i * 100 // total
        print(f'  [{i:3}/{total}] {pct:3}% | {remote} ({size:,} B) {mark}')

    elapsed = time.time() - start
    try:
        ftp.quit()
    except:
        pass
    print(f'\n[OK] {total} fichiers ({bytes_total:,} bytes) deployes en {elapsed:.1f}s')

# ============= MAIN =============
def main():
    print('=== Deploiement FTP cafetieregrain.fr ===\n')
    env = load_env(ENV_FILE)
    print(f'[env] FTP_HOST={env.get("FTP_HOST")}')
    print(f'[env] FTP_USER={env.get("FTP_USER")}')
    print(f'[env] FTP_PATH={env.get("FTP_PATH", "/")}')
    print()

    print('[step 1] Genere config/config.php')
    generate_config(env)

    print('\n[step 2] Liste des fichiers a deployer')
    files = collect_files()
    print(f'  {len(files)} fichiers selectionnes')

    print('\n[step 3] Upload FTP')
    ftp_upload_all(env, files)

    print('\n=== Deploiement termine. Visite https://cafetiereagrain.fr ===')

if __name__ == '__main__':
    main()
FTP_USER")}')
    print(f'[env] FTP_PATH={env.get("FTP_PATH", "/")}')
    print()

    print('[step 1] Genere config/config.php')
    generate_config(env)

    print('\n[step 2] Liste des fichiers a deployer')
    files = collect_files()
    print(f'  {len(files)} fichiers selectionnes')

    print('\n[step 3] Upload FTP')
    ftp_upload_all(env, files)

    print('\n=== Deploiement termine. Visite https://cafetiereagrain.fr ===')

if __name__ == '__main__':
    main()
