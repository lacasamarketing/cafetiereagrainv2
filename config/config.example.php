<?php
// Configuration template. Copier en config.php (gitignore) pour la prod.
// La version de prod est generee automatiquement a partir des GitHub Secrets.

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

    // Liens d'affiliation par defaut (Amazon Partenaires France)
    'affiliate' => [
        'amaz