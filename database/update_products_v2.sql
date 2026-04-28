-- =================================================================
-- update_products_v2.sql
-- Réécriture des verdicts / pitches / pros / cons des 6 produits seedés,
-- avec accents UTF-8 corrects et posture analyse comparative.
-- =================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ----- Mosquito Magnet Patriot -----
UPDATE products SET
    badge   = 'Meilleur global',
    target_use = 'jardin',
    verdict = 'Le souverain. Cher, mais plébiscité par les utilisateurs sur grand jardin.',
    pitch   = 'Le standard de l''industrie depuis 15 ans. Brûleur catalytique propane, octénol et CO2. Surface annoncée jusqu''à 4000 m². Les utilisateurs remontent une efficacité particulièrement marquée sur le moustique tigre après 2 à 3 semaines de fonctionnement continu.',
    pros = JSON_ARRAY('Surface annoncée très grande', 'Plébiscité contre Aedes albopictus dans les retours', 'Robuste sur la durée selon les acheteurs', 'SAV réactif'),
    cons = JSON_ARRAY('Investissement initial élevé', '80–150 €/an de propane et octénol', 'Volumineux à stocker l''hiver')
WHERE slug = 'mosquito-magnet-patriot';

-- ----- Biogents BG-Mosquitaire -----
UPDATE products SET
    badge   = 'Rapport qualité-prix',
    target_use = 'jardin',
    verdict = 'L''allemand sérieux. Le compromis prix/efficacité plébiscité dans les retours.',
    pitch   = 'Piège aspirant qui imite les émanations corporelles humaines. Branché sur secteur, pas de propane. Recharge BG-Sweetscent à remplacer tous les 2 mois. Les utilisateurs remontent une efficacité régulière contre le moustique tigre, idéal jardin urbain ou périurbain.',
    pros = JSON_ARRAY('Pas de propane à gérer', '35 dB constructeur, peu audible selon les retours', 'Apprécié contre le tigre', 'Conception solide selon les acheteurs'),
    cons = JSON_ARRAY('Câble secteur à tirer dans le jardin', 'Surface annoncée limitée à 200 m²', 'Recharge mensuelle ~12 €')
WHERE slug = 'biogents-bg-mosquitaire';

-- ----- Inadays UV LED Silent -----
UPDATE products SET
    badge   = 'Meilleur petit budget',
    target_use = 'chambre',
    verdict = 'Le silencieux d''intérieur. 22 dB annoncés, confirmés par les retours.',
    pitch   = 'Petit piège UV avec ventilateur aspirant. Pas de grille électrique grésillante. 22 dB constructeur, le consensus utilisateurs confirme un fonctionnement compatible chambre. Sur batterie 8 h ou USB. Peu efficace en extérieur selon les retours.',
    pros = JSON_ARRAY('Vraiment silencieux selon les retours', 'USB-C, batterie intégrée', 'Compact et discret', 'Sous 60 €'),
    cons = JSON_ARRAY('Inefficace contre le moustique tigre', 'Surface très limitée (50 m²)', 'Surtout utile contre mouches/moucherons')
WHERE slug = 'inadays-uv-led-silent';

-- ----- Aspectek Bug Zapper 20W -----
UPDATE products SET
    badge   = 'Petit budget extérieur',
    target_use = 'terrasse',
    verdict = 'Le grésilleur classique. Apprécié sur les volants généraux, peu efficace sur le tigre selon les retours.',
    pitch   = 'Lampe UV 20W avec grille électrique. Bruyant (grésillements 48 dB constructeur). Les retours utilisateurs convergent : efficace sur mouches, papillons de nuit, moucherons mais peu d''effet sur Aedes albopictus, qui n''est pas attiré par l''UV selon les recherches entomologiques.',
    pros = JSON_ARRAY('Très bon prix', 'Robuste, étanche IP44', 'Couvre 100 m² d''insectes volants'),
    cons = JSON_ARRAY('Inefficace contre le moustique tigre', 'Bruyant (grésillements 48 dB)', 'Tue aussi des insectes utiles')
WHERE slug = 'aspectek-bug-zapper';

-- ----- Biogents BG-GAT -----
UPDATE products SET
    badge   = 'Anti-tigre longue durée',
    target_use = 'tigre',
    verdict = 'Le piège à ponte qui casse le cycle. À combiner avec un BG-Mosquitaire selon les retours.',
    pitch   = 'Imite une eau stagnante (lieu de ponte du tigre). Les femelles viennent pondre, restent piégées. Selon le constructeur et les retours utilisateurs, agit sur 4 à 6 semaines pour réduire la population locale. Pas de courant, pas de bruit.',
    pros = JSON_ARRAY('Aucune électricité, aucun bruit', 'Spécifique Aedes albopictus', 'Effet long terme (cycle de reproduction)', 'À combiner pour synergies'),
    cons = JSON_ARRAY('Pas d''effet visible la 1re semaine', 'Couvre seulement 50 m²', 'À vider tous les 15 jours')
WHERE slug = 'biogents-gat';

-- ----- Coleman Mosquito Deleto -----
UPDATE products SET
    badge   = 'Compact et nomade',
    target_use = 'terrasse',
    verdict = 'L''alternative plus accessible au Mosquito Magnet, sur petite/moyenne terrasse.',
    pitch   = 'Brûle un mélange propane+octénol pour générer CO2 et chaleur. Surface annoncée 800 m². Les retours utilisateurs pointent une construction moins solide que le Mosquito Magnet pour environ la moitié du prix. Bon compromis pour terrasse 50–100 m².',
    pros = JSON_ARRAY('Prix divisé par 2 vs Mosquito Magnet', 'Mobile (poignée, batterie option)', 'Efficace sur tigre (CO2)'),
    cons = JSON_ARRAY('Construction plus fragile', 'Cartouche octénol obligatoire', 'Recharges propane plus fréquentes')
WHERE slug = 'coleman-mosquito-deleto';
