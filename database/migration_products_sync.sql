-- Migration : ajout des colonnes de sync Rainforest pour traquer les updates produits
-- Compatible MySQL 5.7+ / MariaDB 10.x sur OVH mutualisé.
-- Si tu réimportes après une 1re tentative partielle, MySQL renvoie « Duplicate column » :
-- ignore l'erreur, c'est idempotent.

ALTER TABLE products
    ADD COLUMN last_sync_at DATETIME DEFAULT NULL COMMENT 'Derniere synchro avec Rainforest API',
    ADD COLUMN reviews_count INT UNSIGNED DEFAULT NULL COMMENT 'Nombre d avis remontes (info, pas affiche)',
    ADD COLUMN in_stock TINYINT(1) DEFAULT 1 COMMENT '1 en stock selon Rainforest, 0 epuise';

ALTER TABLE products ADD INDEX idx_last_sync (last_sync_at);
ALTER TABLE products ADD INDEX idx_in_stock (in_stock);
