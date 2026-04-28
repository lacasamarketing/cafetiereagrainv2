-- =================================================================
-- update_products_verdicts_v2.sql
-- Adaptation des verdicts/pitches des 6 produits aux VRAIES notes Amazon FR
-- pour rester crédible éditorialement (pas de "plébiscité" sur un 3.4/5).
-- =================================================================

SET NAMES utf8mb4;

-- ----- Mosquito Magnet Pioneer (note Amazon FR ~3,4/5) -----
UPDATE products SET
    verdict = 'Le premium qui divise. Référence du marché grand jardin, mais retours utilisateurs partagés.',
    pitch   = 'Brûleur catalytique propane, octénol et CO2. Surface annoncée jusqu''à 3000 m². Référence historique du marché européen, mais les retours utilisateurs sont partagés sur le rapport prix-efficacité (799 € + ~150 €/an de consommables). Plébiscité par les fans qui ont vu une vraie réduction de population, contesté par ceux qui attendaient un effet plus rapide.',
    pros    = JSON_ARRAY('Surface annoncée parmi les plus grandes du marché', 'Référence historique connue', 'SAV via Favex en France', 'Robustesse appréciée sur la durée par les utilisateurs satisfaits'),
    cons    = JSON_ARRAY('Investissement initial élevé (~800 €)', '~150 €/an de propane et octénol', 'Volumineux à stocker l''hiver', 'Retours utilisateurs partagés sur le rapport prix-efficacité')
WHERE slug = 'mosquito-magnet-pioneer';

-- ----- Biogents BG-Mosquitaire (note Amazon FR ~3,6/5) -----
UPDATE products SET
    verdict = 'L''allemand de référence. Solide sur le papier, retours utilisateurs nuancés.',
    pitch   = 'Piège aspirant qui imite les émanations corporelles humaines. Branché sur secteur, sans propane. Recharge BG-Sweetscent à remplacer tous les 2 mois. Référence européenne contre le moustique tigre selon le constructeur, mais les retours utilisateurs varient : certains rapportent une efficacité régulière, d''autres pointent une installation contraignante (câble secteur dans le jardin) et des recharges récurrentes.',
    pros    = JSON_ARRAY('Pas de propane à gérer', 'Conçu spécifiquement contre Aedes albopictus', 'Niveau sonore correct (35 dB)', 'Construction allemande appréciée'),
    cons    = JSON_ARRAY('Câble secteur à tirer dans le jardin', 'Surface annoncée limitée à 200 m²', 'Recharges mensuelles à prévoir (~12 €)', 'Retours utilisateurs partagés selon les conditions d''usage')
WHERE slug = 'biogents-bg-mosquitaire';

-- ----- HEXA Favex Hexasafe (produit récent, pas encore d'avis Amazon FR) -----
UPDATE products SET
    rating  = NULL,
    reviews_count = 0,
    verdict = 'Le silencieux d''intérieur. Produit récent, pas encore d''avis Amazon mais specs convaincantes.',
    pitch   = 'Piège aspirant pour intérieur, gel attractant intégré 3 mois. Pas de grille électrique grésillante, donc aucun claquement la nuit. Couvre 40 m² selon le constructeur, USB 5W, fonction UV pour les insectes volants. Produit récent (lancé 2025) qui n''a pas encore accumulé d''avis utilisateurs vérifiés.',
    pros    = JSON_ARRAY('Silencieux annoncé (pas de grille électrique)', 'Gel attractant 3 mois inclus', 'Alimentation USB 5W discrète', 'Conçu pour 40 m² intérieur'),
    cons    = JSON_ARRAY('Pas encore de retours utilisateurs vérifiés', 'Recharges gel à racheter ensuite', 'Peu efficace en extérieur')
WHERE slug = 'hexa-favex-hexasafe';

-- ----- YISSVIC UV LED 4200V (note Amazon FR ~4,4/5) -----
UPDATE products SET
    verdict = 'La lampe UV nomade bien notée. IPX4 étanche, batterie 12 h, grille 4200V.',
    pitch   = 'Lampe UV avec grille électrique 4200V, étanche IPX4 pour extérieur. Rechargeable USB, jusqu''à 12 h d''autonomie. Les retours utilisateurs convergent sur une bonne efficacité contre mouches, papillons de nuit et moucherons. Peu d''effet sur Aedes albopictus selon les recherches entomologiques (le tigre n''est pas attiré par l''UV).',
    pros    = JSON_ARRAY('Très bon prix sous 40 €', 'Rechargeable USB nomade', 'Étanche IPX4', 'Note utilisateurs solide (4,4/5)'),
    cons    = JSON_ARRAY('Inefficace contre le moustique tigre', 'Bruyant (grésillements ~48 dB)', 'Tue aussi des insectes utiles')
WHERE slug = 'yissvic-uv-led-rechargeable';

-- ----- Biogents BG-GAT (note Amazon FR ~3,5/5) -----
UPDATE products SET
    verdict = 'Le piège à ponte spécifique anti-tigre. Effet long terme apprécié, résultat lent.',
    pitch   = 'Imite une eau stagnante (lieu de ponte du tigre). Les femelles viennent pondre, restent piégées. Selon le constructeur, agit sur 4 à 6 semaines pour réduire la population locale. Pas de courant, pas de bruit. Retours utilisateurs partagés : les patients rapportent une vraie baisse après 1 à 2 mois, les autres trouvent l''effet trop lent et continuent à se faire piquer en attendant.',
    pros    = JSON_ARRAY('Aucune électricité, aucun bruit', 'Spécifique Aedes albopictus', 'Effet long terme sur le cycle de reproduction', 'Lot de 2 inclus'),
    cons    = JSON_ARRAY('Pas d''effet visible la 1re semaine', 'Couvre seulement ~50 m²', 'À combiner idéalement avec un piège actif (BG-Mosquitaire)', 'Retours utilisateurs partagés sur la rapidité de l''effet')
WHERE slug = 'biogents-bg-gat';

-- ----- Lampe Anti-Moustique LED USB 4000 mAh (note Amazon FR ~4,9/5) -----
UPDATE products SET
    verdict = 'La lampe nomade plébiscitée. Note utilisateurs exceptionnelle (4,9/5).',
    pitch   = 'Lampe LED rechargeable USB avec grille électrique. Batterie 4000 mAh, autonomie ~16 h. Double mode : lumière ambiance + grille anti-insectes. Note utilisateurs exceptionnelle (4,9/5), particulièrement appréciée en camping et soirées terrasse pour son rapport simplicité/efficacité.',
    pros    = JSON_ARRAY('Note utilisateurs exceptionnelle (4,9/5)', 'Batterie 4000 mAh = ~16 h d''autonomie', 'Double usage lampe ambiance + piège', 'Compact et léger'),
    cons    = JSON_ARRAY('Surface limitée (~60 m²)', 'Surtout efficace sur petits insectes volants', 'Pas dédié au moustique tigre')
WHERE slug = 'lampe-led-anti-moustique-usb';
