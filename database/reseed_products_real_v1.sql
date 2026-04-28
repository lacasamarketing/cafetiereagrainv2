-- =================================================================
-- reseed_products_real_v1.sql
-- Remplace les 6 produits du seed initial (placeholders fictifs)
-- par les VRAIS bestsellers actuels d'Amazon France pour les pieges a moustiques.
-- A importer dans phpMyAdmin apres migration_products_amazon_full.sql.
-- =================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 1) Vide la table products (les liens /go/ dans les articles seront mis a jour ci-dessous)
DELETE FROM products;
ALTER TABLE products AUTO_INCREMENT = 1;

-- 2) Insere les 6 vrais produits
INSERT INTO products
    (slug, name, brand, asin, technology, surface_m2, noise_db, autonomy_hours,
     price_eur, rating, rank_global, badge, target_use, verdict, pitch, pros, cons, status)
VALUES

-- #1 Mosquito Magnet Pioneer (ex-Patriot, le seul Mosquito Magnet vraiment distribue en FR)
('mosquito-magnet-pioneer',
 'Mosquito Magnet Pioneer',
 'Mosquito Magnet',
 'B0CBQ6JSWK',
 'co2', 3000, 28.0, NULL,
 799.00, 4.0, 1,
 'Meilleur grand jardin',
 'jardin',
 'Le souverain du grand jardin. Cher, mais aucun concurrent serieux a sa portee.',
 'Bruleur catalytique propane, octenol et CO2. Surface annoncee jusqu''a 3000 m². Le standard de l''industrie depuis 15 ans selon les retours utilisateurs, particulierement plebiscite contre le moustique tigre apres 2 a 3 semaines de fonctionnement continu.',
 JSON_ARRAY('Surface annoncee tres grande (3000 m²)', 'Apprecie contre Aedes albopictus dans les retours', 'Robuste sur la duree', 'SAV reactif via Favex'),
 JSON_ARRAY('Investissement initial eleve (~800 €)', '~150 €/an de propane et octenol', 'Volumineux a stocker l''hiver'),
 'published'),

-- #2 Biogents BG-Mosquitaire (LE rapport qualite-prix de reference)
('biogents-bg-mosquitaire',
 'Biogents BG-Mosquitaire',
 'Biogents',
 'B00D3IL0LO',
 'aspirant', 200, 35.0, NULL,
 139.00, 4.4, 2,
 'Rapport qualite-prix',
 'jardin',
 'L''allemand serieux. Le compromis prix/efficacite plebiscite dans les retours.',
 'Piege aspirant qui imite les emanations corporelles humaines. Branche sur secteur, pas de propane. Recharge BG-Sweetscent a remplacer tous les 2 mois. Les utilisateurs remontent une efficacite reguliere contre le moustique tigre, ideal jardin urbain ou periurbain de 200 m².',
 JSON_ARRAY('Pas de propane a gerer', '35 dB constructeur, peu audible', 'Apprecie contre le tigre', 'Conception solide'),
 JSON_ARRAY('Cable secteur a tirer dans le jardin', 'Surface limitee a 200 m²', 'Recharge mensuelle ~12 €'),
 'published'),

-- #3 HEXA Favex Hexasafe (interieur silencieux, sans grille electrique)
('hexa-favex-hexasafe',
 'HEXA Favex Hexasafe',
 'Favex',
 'B0GPPNTQSZ',
 'aspirant', 40, 24.0, NULL,
 59.90, 4.3, 3,
 'Meilleur interieur',
 'chambre',
 'Le silencieux d''interieur. Pas de grezillement, gel attractant inclus 3 mois.',
 'Petit piege aspirant pour interieur, avec gel attractant integre 3 mois. Pas de grille electrique, donc aucun claquement la nuit. Couvre 40 m² selon le constructeur. Les retours utilisateurs convergent sur un fonctionnement compatible chambre et un assemblage soigne.',
 JSON_ARRAY('Vraiment silencieux', 'Gel attractant 3 mois inclus', 'Compact et discret', 'Sous 60 €'),
 JSON_ARRAY('Surface limitee 40 m²', 'Recharges gel a racheter ensuite', 'Peu efficace en exterieur'),
 'published'),

-- #4 YISSVIC UV LED 4200V (petit budget exterieur, lampe UV avec grille)
('yissvic-uv-led-rechargeable',
 'YISSVIC UV LED 4200V',
 'YISSVIC',
 'B0DY13D4D4',
 'uv', 100, 48.0, 12,
 39.99, 4.4, 4,
 'Petit budget exterieur',
 'terrasse',
 'La lampe UV nomade. IPX4 etanche, batterie integree, 4200V de grille.',
 'Lampe UV avec grille electrique 4200V, etanche IPX4 pour exterieur. Rechargeable USB, jusqu''a 12 h d''autonomie. Les retours utilisateurs convergent : efficace sur mouches, papillons de nuit, moucherons, peu d''effet sur Aedes albopictus qui n''est pas attire par l''UV selon les recherches entomologiques.',
 JSON_ARRAY('Tres bon prix sous 40 €', 'Rechargeable USB nomade', 'Etanche IPX4', 'Grille puissante 4200V'),
 JSON_ARRAY('Inefficace contre le moustique tigre', 'Bruyant (gresillements ~48 dB)', 'Tue aussi des insectes utiles'),
 'published'),

-- #5 Biogents BG-GAT (anti-tigre piege a ponte, longue duree)
('biogents-bg-gat',
 'Biogents BG-GAT (Lot de 2)',
 'Biogents',
 'B07FNLJSRG',
 'larvaire', 50, 0.0, NULL,
 58.84, 4.4, 5,
 'Anti-tigre longue duree',
 'tigre',
 'Le piege a ponte qui casse le cycle. A combiner avec un BG-Mosquitaire selon les retours.',
 'Imite une eau stagnante (lieu de ponte du tigre). Les femelles viennent pondre, restent piegees. Selon le constructeur et les retours utilisateurs, agit sur 4 a 6 semaines pour reduire la population locale. Pas de courant, pas de bruit, pas d''entretien intensif.',
 JSON_ARRAY('Aucune electricite, aucun bruit', 'Specifique Aedes albopictus', 'Effet long terme (cycle de reproduction)', 'Lot de 2 inclus'),
 JSON_ARRAY('Pas d''effet visible la 1re semaine', 'Couvre seulement ~50 m²', 'A vider tous les 15 jours'),
 'published'),

-- #6 Lampe LED USB Rechargeable (alternative nomade, top reviews)
('lampe-led-anti-moustique-usb',
 'Lampe Anti-Moustique LED USB 4000 mAh',
 'Generique',
 'B0GHDP4Q2D',
 'uv', 60, 32.0, 16,
 32.97, 4.9, 6,
 'Bestseller nomade',
 'terrasse',
 'La lampe nomade plebiscitee. Batterie 4000 mAh, double mode lumiere et anti-insectes.',
 'Lampe LED rechargeable USB avec grille electrique 4200V. Batterie 4000 mAh autonome 16 h. Double mode : lumiere ambiance + grille anti-insectes. Tres bien notee selon les retours utilisateurs (4,9/5), particulierement appreciee en camping et soirees terrasse.',
 JSON_ARRAY('Notation utilisateurs exceptionnelle (4,9/5)', 'Batterie 4000 mAh = 16 h', 'Double usage lampe + piege', 'Compact et leger'),
 JSON_ARRAY('Surface limitee (~60 m²)', 'Surtout efficace sur petits insectes volants', 'Pas dedie au moustique tigre'),
 'published');

-- 3) Mise a jour des liens /go/ dans les articles existants
--    pour qu'ils pointent vers les nouveaux slugs
UPDATE articles SET content_html = REPLACE(content_html, '/go/mosquito-magnet-patriot', '/go/mosquito-magnet-pioneer');
UPDATE articles SET content_html = REPLACE(content_html, '/go/inadays-uv-led-silent', '/go/hexa-favex-hexasafe');
UPDATE articles SET content_html = REPLACE(content_html, '/go/aspectek-bug-zapper', '/go/yissvic-uv-led-rechargeable');
UPDATE articles SET content_html = REPLACE(content_html, '/go/biogents-gat', '/go/biogents-bg-gat');
UPDATE articles SET content_html = REPLACE(content_html, '/go/coleman-mosquito-deleto', '/go/lampe-led-anti-moustique-usb');
-- biogents-bg-mosquitaire reste inchange

-- 4) Reset le cron amazon_sync pour forcer un sync immediat (recupere images, features, etc.)
DELETE FROM settings WHERE setting_key = 'cron:amazon_sync';
