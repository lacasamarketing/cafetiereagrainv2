-- Migration : champs Amazon enrichis (récupérés via Rainforest getProduct)
-- Compatible MySQL 5.7+ : 5 ALTER séparés pour être idempotent.
-- Si une colonne existe déjà, MySQL renverra "#1060 Nom du champ X déjà utilisé"
-- → IGNORE l'erreur, c'est sans conséquence, les autres ALTER continueront.

ALTER TABLE products ADD COLUMN amazon_title VARCHAR(500) DEFAULT NULL COMMENT 'Titre exact Amazon (read-only, info)';

ALTER TABLE products ADD COLUMN features JSON DEFAULT NULL COMMENT 'Bullet points Amazon (caracteristiques)';

ALTER TABLE products ADD COLUMN description TEXT DEFAULT NULL COMMENT 'Description complete Amazon';

ALTER TABLE products ADD COLUMN is_prime TINYINT(1) DEFAULT NULL COMMENT '1 = eligible Prime, 0 = non';

ALTER TABLE products ADD COLUMN delivery_info VARCHAR(255) DEFAULT NULL COMMENT 'Info livraison Amazon';
