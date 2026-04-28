"""
Genere database/seeds_data.sql a partir des JSON _data/.
Lance ce script localement, puis l'output SQL est a executer en prod via PHPMyAdmin.
"""
import json
import os
import re
import html
from pathlib import Path

DATA_DIR = Path('/sessions/friendly-trusting-planck/mnt/GITHUB/cafetiereagrainv2/_data')
OUT_SQL  = Path('/sessions/friendly-trusting-planck/mnt/GITHUB/cafetiereagrainv2/database/seeds_data.sql')

# ============= HELPERS =============
def sql_str(s):
    """Echappe pour SQL string."""
    if s is None:
        return 'NULL'
    if isinstance(s, (int, float)):
        return str(s)
    if isinstance(s, bool):
        return '1' if s else '0'
    s = str(s).replace('\\', '\\\\').replace("'", "''")
    return "'" + s + "'"

def sql_json(obj):
    if obj is None or obj == [] or obj == {}:
        return 'NULL'
    return "'" + json.dumps(obj, ensure_ascii=False).replace("'", "''").replace('\\', '\\\\') + "'"

def slugify(s, max_len=180):
    if not s:
        return ''
    s = s.lower()
    s = re.sub(r'[àáâãäå]', 'a', s)
    s = re.sub(r'[èéêë]', 'e', s)
    s = re.sub(r'[ìíîï]', 'i', s)
    s = re.sub(r'[òóôõö]', 'o', s)
    s = re.sub(r'[ùúûü]', 'u', s)
    s = re.sub(r"[^a-z0-9]+", '-', s)
    s = s.strip('-')
    return s[:max_len]

# ============= TYPOLOGIE CLASSIFIER =============
def classify_type(title, description, categories, brand=""):
    """Determine la typologie de cafetiere."""
    tl = (title or '').lower()
    txt = (title + ' ' + (description or '') + ' ' + ' '.join([c.get('name','') for c in (categories or [])])).lower()
    bl = (brand or '').lower()
    is_machine_title = any(x in tl for x in ['machine', 'expresso', 'espresso', 'robot', 'cafetiere', 'cafetière'])

    # Cas particuliers : Baristina = manuel sans broyeur
    if 'baristina' in txt:
        return 'espresso_manuel'
    if any(x in txt for x in ['dedica', 'prixton napoli']) and 'broyeur' not in txt:
        return 'espresso_manuel'
    # Hybride filtre + grain
    if any(x in txt for x in ['russell hobbs chester', 'ninja luxe', 'dual brew']):
        return 'hybride_grain_filtre'
    # Moulin pur : doit etre dans le TITRE et pas etre une machine
    if any(x in tl for x in ['moulin', 'grinder']) and not is_machine_title:
        return 'moulin'
    # Capsule
    if any(x in txt for x in ['nespresso', 'dolce gusto', 'tassimo']) and 'grain' not in txt and 'broyeur' not in txt:
        return 'expresso_capsule'
    # Espresso broyeur auto (cas standard)
    BRANDS_GRAIN = {"jura", "krups", "saeco", "delonghi", "de\'longhi", "klarstein", "melitta", "philips"}
    is_known_brand = bl in BRANDS_GRAIN
    has_machine_keyword = any(x in txt for x in [
        'broyeur', 'a grain', 'machine espresso', 'machine a expresso', 'machine a cafe',
        'machine cafe', 'expresso broyeur', 'machine a café',
        'magnifica', 'eletta', 'rivelia', 'lattego', 'granaroma', 'caffeo', 'avanza',
        'barista t', 'velaire', 'robot cafe', 'robot café', 'expresso ono',
        'machine automatique', 'expresso krups', 'expresso jura'
    ])
    if (is_known_brand and is_machine_title) or has_machine_keyword:
        return 'espresso_broyeur_auto'
    # Accessoire
    if any(x in tl for x in ['tasse', 'balance', 'douille', 'tamper']) and not is_known_brand:
        return 'accessoire'
    return 'autre'


def synthesize_summary(top_reviews, lang='fr'):
    """Synthese de 3 avis 5* verified_purchase, max helpful_votes."""
    if not top_reviews:
        return None
    five_star = [r for r in top_reviews if r.get('rating') == 5 and r.get('verified_purchase')]
    if not five_star:
        five_star = [r for r in top_reviews if r.get('rating', 0) >= 4]
    five_star.sort(key=lambda x: x.get('helpful_votes', 0) or 0, reverse=True)
    top3 = five_star[:3]
    if not top3:
        return None
    snippets = []
    for r in top3:
        body = (r.get('body') or r.get('body_html') or '').strip()
        if not body and r.get('title'):
            body = r['title']
        body = re.sub(r'<[^>]+>', '', body)[:280].strip()
        if body:
            snippets.append(body)
    return ' | '.join(snippets) if snippets else None

def parse_specs(spec_list):
    """Extrait les specs cles."""
    out = {'capacity_l': None, 'dimensions': None, 'weight_kg': None, 'color': None, 'material': None}
    for s in spec_list or []:
        n = (s.get('name') or '').lower()
        v = s.get('value') or ''
        if 'capacit' in n:
            m = re.search(r'(\d+[.,]?\d*)\s*l', v.lower())
            if m:
                out['capacity_l'] = float(m.group(1).replace(',', '.'))
        elif 'dimension' in n:
            out['dimensions'] = v[:80]
        elif 'poids' in n or 'weight' in n:
            m = re.search(r'(\d+[.,]?\d*)\s*kg', v.lower())
            if m:
                out['weight_kg'] = float(m.group(1).replace(',', '.'))
        elif 'couleur' in n or 'color' in n:
            out['color'] = v[:80]
        elif 'matériau' in n or 'materiau' in n or 'material' in n:
            out['material'] = v[:80]
    return out

def parse_price(buybox):
    if not buybox or not buybox.get('price'):
        return None, None
    p = buybox['price']
    val = p.get('value')
    if val:
        return float(val), p.get('currency', 'EUR')
    raw = p.get('raw', '')
    m = re.search(r'(\d+[\s.,]?\d*[.,]?\d*)', raw.replace('\xa0', ' '))
    if m:
        v = m.group(1).replace(' ', '').replace('\xa0', '').replace(',', '.')
        try:
            return float(v), 'EUR'
        except: pass
    return None, 'EUR'

# ============= MAIN =============
print('=== Lecture des JSONs ===')
sql_lines = ['SET NAMES utf8mb4;', 'SET time_zone = "+01:00";', '']
sql_lines.append('-- ============= PRODUCTS =============')
sql_lines.append('-- Issus de Rainforest API (Amazon.fr) - 2026-04-28')

products_inserted = []

rainforest_dir = DATA_DIR / 'rainforest'
for fname in sorted(os.listdir(rainforest_dir)):
    asin = fname.replace('.json', '')
    with open(rainforest_dir / fname, 'r', encoding='utf-8') as f:
        data = json.load(f)
    p = data.get('product') or {}
    if not p or not p.get('title'):
        print(f'  SKIP {asin} (pas de produit)')
        continue

    title = p.get('title', '')
    brand = p.get('brand', '')
    desc_long = (p.get('description') or '')[:5000]
    type_caf = classify_type(title, desc_long, p.get("categories"), brand)
    price_eur, currency = parse_price(p.get('buybox_winner'))
    rating = p.get('rating')
    ratings_total = p.get('ratings_total', 0)
    main_image = (p.get('main_image') or {}).get('link', '')
    images = [{'link': i.get('link')} for i in (p.get('images') or [])[:8] if i.get('link')]
    features = p.get('feature_bullets') or []
    top_reviews_raw = p.get('top_reviews') or []
    top_reviews_clean = [{
        'rating': r.get('rating'),
        'title': r.get('title'),
        'body': r.get('body') or r.get('body_html'),
        'date': (r.get('date') or {}).get('utc') or (r.get('date') or {}).get('raw'),
        'helpful_votes': r.get('helpful_votes', 0),
        'verified_purchase': r.get('verified_purchase', False),
    } for r in top_reviews_raw[:10]]
    summary = synthesize_summary(top_reviews_raw)

    specs = parse_specs(p.get('specifications'))
    bs_rank = None
    bs_cat = None
    bsr = p.get('bestsellers_rank') or []
    for b in bsr:
        if b.get('category') and 'machines à café' in b['category'].lower():
            bs_rank = b.get('rank')
            bs_cat = b['category']
            break
    if bs_rank is None and bsr:
        bs_rank = bsr[0].get('rank')
        bs_cat = bsr[0].get('category')

    slug = slugify(f"{brand} {title}".strip())
    name = title[:300]

    sql_lines.append(
        f"INSERT INTO products (asin, slug, name, brand, model_number, type_cafetiere, "
        f"description_long, price_eur, currency, rating, ratings_total, bestsellers_rank, bestsellers_cat, "
        f"amazons_choice, color, material, capacity_l, dimensions_cm, weight_kg, "
        f"main_image_url, images_json, specifications_json, features_json, top_reviews_json, "
        f"summary_avis, status) VALUES ({sql_str(asin)}, {sql_str(slug)}, {sql_str(name)}, "
        f"{sql_str(brand)}, {sql_str(p.get('model_number'))}, {sql_str(type_caf)}, "
        f"{sql_str(desc_long)}, {sql_str(price_eur) if price_eur else 'NULL'}, {sql_str(currency or 'EUR')}, "
        f"{sql_str(rating) if rating else 'NULL'}, {ratings_total or 0}, "
        f"{bs_rank if bs_rank else 'NULL'}, {sql_str(bs_cat)}, "
        f"{1 if p.get('amazons_choice') else 0}, {sql_str(specs['color'])}, {sql_str(specs['material'])}, "
        f"{sql_str(specs['capacity_l']) if specs['capacity_l'] else 'NULL'}, {sql_str(specs['dimensions'])}, "
        f"{sql_str(specs['weight_kg']) if specs['weight_kg'] else 'NULL'}, "
        f"{sql_str(main_image)}, {sql_json(images)}, {sql_json(p.get('specifications'))}, "
        f"{sql_json(features)}, {sql_json(top_reviews_clean)}, {sql_str(summary)}, 'published') "
        f"ON DUPLICATE KEY UPDATE name=VALUES(name), price_eur=VALUES(price_eur), rating=VALUES(rating), "
        f"ratings_total=VALUES(ratings_total), bestsellers_rank=VALUES(bestsellers_rank), "
        f"main_image_url=VALUES(main_image_url), images_json=VALUES(images_json), "
        f"top_reviews_json=VALUES(top_reviews_json), summary_avis=VALUES(summary_avis), updated_at=NOW();"
    )
    products_inserted.append({'asin': asin, 'slug': slug, 'type': type_caf, 'brand': brand})
    print(f'  {asin} | {brand:15} | {type_caf:25} | {name[:50]}')

sql_lines.append('')
sql_lines.append('-- ============= ARTICLES =============')

with open(DATA_DIR / 'wp-articles.json', 'r', encoding='utf-8') as f:
    articles = json.load(f)
with open(DATA_DIR / 'wp-categories.json', 'r', encoding='utf-8') as f:
    cats_wp = {c['id']: c['slug'] for c in json.load(f)}

# Map cluster slug WP -> notre slug categorie
CAT_MAP = {
    'cafetiere-a-grain': 'cafetiere-a-grain',
    'cafes-en-grain':    'cafes-en-grain',
    'accessoires':       'accessoires',
    'conseils-budget':   'conseils-budget',
    'uncategorized':     'cafetiere-a-grain',
}

articles_inserted = 0
for a in articles:
    wp_id = a['id']
    slug = a['slug']
    title = a['title']['rendered'][:300]
    title = html.unescape(title)
    excerpt = re.sub(r'<[^>]+>', '', a.get('excerpt', {}).get('rendered', ''))[:400].strip()
    excerpt = html.unescape(excerpt)
    content = a['content']['rendered']  # HTML brut
    # cluster = premiere categorie de l'article
    cat_ids = a.get('categories') or []
    cluster = 'cafetiere-a-grain'
    for cid in cat_ids:
        wp_slug = cats_wp.get(cid)
        if wp_slug and wp_slug in CAT_MAP:
            cluster = CAT_MAP[wp_slug]
            break
    publish_at = a.get('date', '').replace('T', ' ')[:19]

    # reading time : ~250 mots / min
    word_count = len(re.sub(r'<[^>]+>', ' ', content).split())
    reading_time = max(1, round(word_count / 250))

    sql_lines.append(
        f"INSERT INTO articles (wp_id, slug, title, description, cluster, content_html, "
        f"reading_time, status, publish_at) VALUES ({wp_id}, {sql_str(slug)}, {sql_str(title)}, "
        f"{sql_str(excerpt)}, {sql_str(cluster)}, {sql_str(content)}, {reading_time}, 'published', "
        f"{sql_str(publish_at)}) "
        f"ON DUPLICATE KEY UPDATE title=VALUES(title), description=VALUES(description), "
        f"content_html=VALUES(content_html), updated_at=NOW();"
    )
    articles_inserted += 1

sql_lines.append('')
sql_lines.append('-- ============= ARTICLE_PRODUCTS (maillage interne) =============')

with open(DATA_DIR / 'articles-amazon-mapping.json', 'r', encoding='utf-8') as f:
    mapping = json.load(f)
with open(DATA_DIR / 'amzn-shortlinks-resolved.json', 'r', encoding='utf-8') as f:
    amzn_resolved = json.load(f)

# Map short_code -> asin
short_to_asin = {}
for short_url, info in amzn_resolved.items():
    code = short_url.replace('https://amzn.to/', '').strip()
    if info.get('asin'):
        short_to_asin[code] = info['asin']

links_count = 0
for slug, info in mapping.items():
    article_id_sql = f"(SELECT id FROM articles WHERE slug = {sql_str(slug)})"
    asins_for_article = set(info.get('asins', []))
    for code in info.get('short_links', []):
        if code in short_to_asin:
            asins_for_article.add(short_to_asin[code])
    for i, asin in enumerate(asins_for_article, start=1):
        product_id_sql = f"(SELECT id FROM products WHERE asin = {sql_str(asin)})"
        sql_lines.append(
            f"INSERT IGNORE INTO article_products (article_id, product_id, position) "
            f"SELECT a.id, p.id, {i} FROM articles a, products p "
            f"WHERE a.slug = {sql_str(slug)} AND p.asin = {sql_str(asin)};"
        )
        links_count += 1

sql_lines.append('')
sql_lines.append('-- ============= STATS =============')
sql_lines.append(f'-- Produits inseres : {len(products_inserted)}')
sql_lines.append(f'-- Articles inseres : {articles_inserted}')
sql_lines.append(f'-- Liens article<->produit : {links_count}')

print()
print(f'Produits   : {len(products_inserted)}')
print(f'Articles   : {articles_inserted}')
print(f'Maillage   : {links_count} liens article<->produit')

# Stats par typologie
from collections import Counter
typo_count = Counter(p['type'] for p in products_inserted)
print()
print('=== Typologie produits ===')
for t, n in typo_count.most_common():
    print(f'  {t:30} {n}')

# Sauvegarde
OUT_SQL.parent.mkdir(parents=True, exist_ok=True)
with open(OUT_SQL, 'w', encoding='utf-8') as f:
    f.write('\n'.join(sql_lines))

print()
print(f'SQL ecrit dans : {OUT_SQL}')
print(f'Taille : {OUT_SQL.stat().st_size / 1024:.1f} KB')
