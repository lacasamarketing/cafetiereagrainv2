-- =================================================================
-- add_top_products_v1.sql
-- Ajout d'un nouveau produit bien noté Amazon FR identifié par scraping
-- (note réelle ≥ 4/5 confirmée sur la fiche Amazon individuelle).
-- À importer dans phpMyAdmin pour enrichir le top de la home.
-- =================================================================

SET NAMES utf8mb4;

-- AMUFER Lampe Anti-Moustique 18W 4400V — 4,6/5 sur 1 177 avis
-- Différenciée des 2 autres produits 4+/5 par sa surface (120 m²) et sa puissance (18 W)
INSERT INTO products
    (slug, name, brand, asin, technology, surface_m2, noise_db, autonomy_hours,
     price_eur, rating, reviews_count, rank_global, badge, target_use,
     verdict, pitch, pros, cons, status)
VALUES (
    'amufer-lampe-uv-18w',
    'AMUFER Lampe Anti-Moustique 18W 4400V',
    'AMUFER',
    'B0GH17DLZX',
    'uv', 120, NULL, NULL,
    39.99, 4.6, 1177, 2,
    'Note solide (4,6/5)',
    'jardin',
    'La lampe UV bien notée. 18 W, 4400V, IPX4, 120 m². 1 177 avis utilisateurs convergent.',
    'Lampe UV à grille électrique 18 W (4400V), étanche IPX4 pour usage intérieur ou extérieur. Surface annoncée 120 m². Les retours utilisateurs convergent largement (4,6/5 sur plus de 1 100 avis) sur l''efficacité contre mouches, papillons de nuit, moucherons. Comme toute lampe UV, l''efficacité reste limitée contre le moustique tigre (Aedes albopictus) qui n''est pas attiré par l''UV selon les recherches entomologiques.',
    JSON_ARRAY('Note utilisateurs très solide (4,6/5 sur 1 177 avis)', 'Puissance 18 W couvre 120 m² selon le constructeur', 'Étanche IPX4 (intérieur ou extérieur)', 'Sous 40 €'),
    JSON_ARRAY('Inefficace contre le moustique tigre (UV ne l''attire pas)', 'Bruit de grille électrique', 'Tue aussi des insectes utiles'),
    'published'
);

-- Réorganisation du top affiché sur la home (rang 1 à 3, par note décroissante) :
UPDATE products SET rank_global = 1 WHERE slug = 'lampe-led-anti-moustique-usb';   -- 4,9/5
UPDATE products SET rank_global = 2 WHERE slug = 'amufer-lampe-uv-18w';             -- 4,6/5
UPDATE products SET rank_global = 3 WHERE slug = 'yissvic-uv-led-rechargeable';     -- 4,4/5

-- Les autres produits gardent rank_global pour ordonner le comparateur global,
-- mais leur note < 4 les exclura automatiquement du top de la home (filtre SQL côté index.php).
UPDATE products SET rank_global = 4 WHERE slug = 'mosquito-magnet-pioneer';         -- référence marché
UPDATE products SET rank_global = 5 WHERE slug = 'biogents-bg-mosquitaire';         -- anti-tigre référence
UPDATE products SET rank_global = 6 WHERE slug = 'hexa-favex-hexasafe';             -- silencieux intérieur
UPDATE products SET rank_global = 7 WHERE slug = 'biogents-bg-gat';                 -- anti-ponte
