<?php
// Configuration template. Copier en config.php (gitignore) pour la prod.
// La version de prod est generee automatiquement par scripts/deploy.py.

return [
    'env' => 'prod',
    'base_url' => 'https://cafetiereagrain.fr',

    // Base de donnees MySQL
    'db' => [
        'host' => 'MYSQL_HOST',
        'name' => 'DB_NAME',
        'user' => 'DB_USER',
        'pass' => 'DB_PASS',
        'charset' => 'utf8mb4',
    ],

    // Securite
    'security' => [
        'app_key' => 'CHANGE_ME_64_HEX_CHARS',
        'session_lifetime' => 7200,
        'max_login_attempts' => 5,
    ],

    // Token Bearer pour API auto-publication
    'api_token' => 'CHANGE_ME_API_TOKEN',

    // Cle API Anthropic Claude (pour ArticleGenerator)
    'anthropic_api_key' => 'sk-ant-CHANGEME',
    'anthropic_model'   => 'claude-sonnet-4-6',

    // Cle API Rainforest (sync produits Amazon, mutualisable entre sites)
    'rainforest_api_key' => 'CHANGEME_RAINFOREST',

    // Mangools KWFinder (decouverte mots-cles SEO)
    // Plan partage avec d'autres projets : on serre le budget mensuel pour cafetiereagrain.
    'mangools' => [
        'api_key' => 'CHANGEME_MANGOOLS',
        'enabled' => true,
        'monthly_budget' => 10,         // max 10 requests/mois pour ce projet
        'fallback_seeds_only' => true,  // si quota atteint : skip enrichissement related
    ],

    // Liens d'affiliation par defaut (Amazon Partenaires France)
    'affiliate' => [
        'amazon_tag' => 'lacasamarke08-21',
        'amazon_base' => 'https://www.amazon.fr',
    ],

    'admin_email' => 'bonjour@lacasamarketing.fr',

    // Analytics
    'analytics' => [
        'ga4_id' => '',
        'plausible_domain' => '',
    ],

    // Sync Amazon (Option C : top 3 quotidien + full hebdo)
    'sync' => [
        'top_daily_count' => 3,         // nombre de produits "top" sync chaque jour
        'full_weekly_day' => 0,         // 0=dimanche pour full sync hebdo
    ],
];
