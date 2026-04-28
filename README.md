# cafetiereagrain.fr

Site comparateur indépendant de cafetières à grain. Stack PHP + MySQL, hébergé sur OVH mutualisé. Auto-publication d'articles via API Claude.

## Stack technique

- PHP 8.x (architecture MVC custom, autoload PSR-4 sur `App\\`)
- MySQL 8 (PDO, schema dans `database/`)
- Front-end : HTML/CSS pur (pas de build step), Three.js vanilla pour le piège 3D animé
- Déploiement : GitHub Actions → FTP OVH (FTP-Deploy-Action v4.3.5)
- Affiliation : Amazon Partenaires France (tag `lacasamarke08-21`)
- Auto-rédaction : API Anthropic (Claude Sonnet 4.6) via cron OVH ou GitHub Actions cron

## Arborescence

```
.
├── app/
│   ├── bootstrap.php             # autoload + timezone + headers Authorization OVH
│   └── Core/
│       ├── Article.php           # CRUD articles
│       ├── ArticleGenerator.php  # appelle Claude API, écrit en BDD
│       ├── Auth.php              # login admin (rate limit, sessions, fingerprint)
│       ├── CSRF.php              # tokens CSRF
│       ├── CoverGenerator.php    # cover SVG dynamique pour articles
│       ├── Database.php          # PDO singleton
│       ├── Layout.php            # helpers d'affichage + amazonLink()
│       ├── Mailer.php            # PHP mail() pour reset password
│       └── Queue.php             # FIFO de mots-clés
├── admin/                        # back-office (login, articles, queue)
├── api/                          # API auth Bearer
│   ├── article-create.php        # POST -> publie un article
│   └── next-topic.php            # GET -> pioche le prochain mot-clé
├── assets/
│   ├── css/custom.css            # charte dark / UV cyan / cream
│   └── js/mosquito-trap.js       # piège 3D Three.js vanilla
├── config/
│   └── config.example.php        # template — la prod génère config.php depuis les secrets
├── database/
│   ├── schema.sql                # tables principales
│   ├── migration_article_queue.sql  # queue + 40 topics moustiques
│   ├── migration_products.sql    # table products + 6 modèles testés
│   └── seed_articles.sql         # 4 articles initiaux (UV vs CO2, surface, chambre, coût)
├── partials/
│   ├── header.php                # head + nav + JSON-LD Organization/WebSite
│   └── footer.php
├── scripts/
│   └── generate_article.php      # CLI cron : pioche queue + publie
├── .github/workflows/deploy.yml  # CI/CD OVH
├── .htaccess                     # routing Apache OVH (URLs propres)
├── .ovhconfig                    # marqueur OVH (vide, défauts cluster)
├── article.php                   # /blog/{slug}
├── blog.php                      # /blog (filtres cluster)
├── cover.php                     # SVG cover dynamique
├── go.php                        # tracking Amazon /go/{slug}
├── index.php                     # homepage
├── install.php                   # création initial du compte admin
├── llms.txt                      # description du site pour LLMs
├── mentions-legales.php
├── robots.txt
├── sitemap.php                   # sitemap XML dynamique
└── test.php                      # /tests/{slug-produit} fiche produit
```

## Installation initiale

### 1. Secrets GitHub

Dans `Settings → Secrets and variables → Actions` du repo :

| Secret | Description |
|---|---|
| `FTP_HOST` | Hôte FTP OVH (ex: `ftp.cluster0XX.hosting.ovh.net`) |
| `FTP_USER` | User FTP dédié à ce site |
| `FTP_PASSWORD` | Password FTP |
| `FTP_PATH` | `/` si user FTP dédié au webroot, sinon `/www/cafetiereagrain/` |
| `DB_HOST` | Hôte MySQL OVH |
| `DB_NAME` | Nom de la base |
| `DB_USER` | User MySQL |
| `DB_PASS` | Password MySQL |
| `APP_KEY` | 64 chars hex (`openssl rand -hex 32`) |
| `API_TOKEN` | Bearer token auto-pub (`openssl rand -hex 24`) |
| `ANTHROPIC_API_KEY` | Clé API Anthropic (`sk-ant-...`) |
| `AMAZON_TAG` | Tag affiliation (par défaut `lacasamarke08-21`) |
| `GA4_ID` | Optionnel — Google Analytics 4 |
| `PLAUSIBLE_DOMAIN` | Optionnel — Plausible Analytics |

### 2. Premier déploiement

```bash
git add .
git commit -m "Initial commit"
git push origin main
```

Le workflow GitHub Actions s'occupe du reste : génération de `config/config.php` depuis les secrets puis push FTP.

### 3. BDD MySQL OVH (phpMyAdmin)

Importe dans cet ordre :
1. `database/schema.sql`
2. `database/migration_article_queue.sql` (40 topics pré-remplis)
3. `database/migration_products.sql` (6 produits du top)
4. `database/seed_articles.sql` (4 articles existants)

### 4. Compte admin

Va sur `https://cafetiereagrain.fr/install.php` et crée ton compte (email + password 12+ chars avec maj/min/chiffre/spécial). La page se désactive auto.

### 5. Cron auto-publication (optionnel)

Dans Manager OVH → Hébergements → Tâches planifiées (Cron) :

```
Commande : /usr/local/php8.2/bin/php /home/cluster0XX/www/cafetiereagrain/scripts/generate_article.php
Frequence : 1x/jour à 9h
```

Le script pioche le prochain topic de la queue, appelle l'API Claude, écrit l'article en BDD, et marque la queue comme `done`. Aucune intervention manuelle requise.

## URLs publiques

| URL | Page |
|---|---|
| `/` | Homepage (top 3, comparateur, méthode, articles) |
| `/blog` | Liste de tous les articles |
| `/blog?cluster=comparatif` | Filtrer par cluster (comparatif, guide, test, saison, usage) |
| `/blog/{slug}` | Article détaillé |
| `/tests/{slug-produit}` | Fiche produit (specs, verdict, pros/cons, CTA Amazon) |
| `/go/{slug-produit}` | Tracking + redirect Amazon |
| `/sitemap.xml` | Sitemap dynamique |
| `/llms.txt` | Description du site pour LLMs |
| `/admin/login.php` | Back-office |

## Commandes admin manuelles

Ajouter un mot-clé à la queue :
```sql
INSERT INTO article_queue (keyword_target, title_hint, cluster, persona, priority)
VALUES ('mon mot cle', 'Titre suggere', 'comparatif', 'tous', 8);
```

Forcer la génération d'un article :
```bash
php scripts/generate_article.php
```

## Charte graphique

- Couleurs : `--ink: #050810`, `--cream: #f4f1eb`, `--uv: #5cabff`, `--warm: #ff6b9d`
- Typo : Fraunces (display), Bricolage Grotesque (body), JetBrains Mono (mono)
- Accents : grain SVG en overlay, glows UV en arrière-plan, piège 3D Three.js animé en hero

## Auteur

[Antony Maurice — La Casa Marketing](https://lacasamarketing.fr)
