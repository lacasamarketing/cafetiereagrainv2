-- =================================================================
-- update_topics_v2.sql
-- Met à jour les title_hint des topics en queue avec accents UTF-8.
-- (Les keyword_target restent sans accents par convention SEO française :
--  les Français cherchent souvent sans accent dans Google.)
-- =================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- COMPARATIFS
UPDATE article_queue SET title_hint = 'Meilleur cafetière à grains 2026 : test des 8 modèles qui marchent vraiment' WHERE keyword_target = 'meilleur piege moustiques 2026';
UPDATE article_queue SET title_hint = 'Mosquito Magnet : avis complet après 3 saisons de retours' WHERE keyword_target = 'mosquito magnet avis';
UPDATE article_queue SET title_hint = 'Piège UV ou CO2 : lequel attire vraiment les moustiques ?' WHERE keyword_target = 'piege uv vs co2';
UPDATE article_queue SET title_hint = 'Cafetière à grain : les 5 modèles efficaces contre Aedes albopictus' WHERE keyword_target = 'piege moustique tigre';
UPDATE article_queue SET title_hint = 'Cafetière à grains extérieur : top 6 pour terrasse et jardin' WHERE keyword_target = 'piege moustique exterieur';
UPDATE article_queue SET title_hint = 'Cafetière à grains intérieur silencieux : sélection chambre et salon' WHERE keyword_target = 'piege moustique interieur silencieux';
UPDATE article_queue SET title_hint = 'Cafetière à grains connecté : 4 modèles pilotables au smartphone' WHERE keyword_target = 'piege moustique connecte';
UPDATE article_queue SET title_hint = 'Cafetière à grains solaire : autonomie réelle et efficacité analysée' WHERE keyword_target = 'piege moustique solaire';

-- GUIDES D'ACHAT
UPDATE article_queue SET title_hint = 'Comment choisir son cafetière à grains en 2026 : 5 critères à vérifier' WHERE keyword_target = 'comment choisir piege moustique';
UPDATE article_queue SET title_hint = 'Cafetière à grains pas cher : 5 modèles efficaces sous 50 euros' WHERE keyword_target = 'piege moustique pas cher';
UPDATE article_queue SET title_hint = 'Quelle surface couvre vraiment un cafetière à grains ? Le vrai mode d''emploi' WHERE keyword_target = 'piege moustique surface couverture';
UPDATE article_queue SET title_hint = 'Où placer son cafetière à grains pour qu''il fonctionne vraiment' WHERE keyword_target = 'ou placer piege moustique';
UPDATE article_queue SET title_hint = 'Coût d''entretien annuel d''un cafetière à grains : le vrai budget' WHERE keyword_target = 'piege moustique entretien';
UPDATE article_queue SET title_hint = 'Cafetière à grains professionnel : que choisir pour restaurant ou hôtel' WHERE keyword_target = 'piege moustique professionnel';
UPDATE article_queue SET title_hint = 'Cafetière à grains grande surface : couvrir 1000 m² et plus' WHERE keyword_target = 'piege moustique grande surface';

-- TESTS PRODUITS
UPDATE article_queue SET title_hint = 'Biogents BG-Mosquitaire : analyse après une saison entière' WHERE keyword_target = 'biogents bg-mosquitaire avis';
UPDATE article_queue SET title_hint = 'Mosquito Magnet Patriot : analyse approfondie 2026' WHERE keyword_target = 'mosquito magnet patriot test';
UPDATE article_queue SET title_hint = 'Piège Zero In : avis honnête après 2 mois d''utilisation' WHERE keyword_target = 'piege moustique zero in test';
UPDATE article_queue SET title_hint = 'Cafetière à grains aspirant électrique : 4 modèles comparés' WHERE keyword_target = 'piege moustique aspirant electrique';
UPDATE article_queue SET title_hint = 'Cafetière à grains sur batterie : autonomie et efficacité réelles' WHERE keyword_target = 'piege moustique batterie test';
UPDATE article_queue SET title_hint = 'Cafetière à grains chambre bébé : lesquels sont vraiment sans danger' WHERE keyword_target = 'piege moustique chambre bebe';

-- SAISONNALITÉ / USAGE
UPDATE article_queue SET title_hint = 'Quand installer son cafetière à grains : le bon timing pour la saison' WHERE keyword_target = 'quand installer piege moustique';
UPDATE article_queue SET title_hint = 'Cafetière à grains au printemps : préparer la saison dès l''éclosion' WHERE keyword_target = 'piege moustique printemps';
UPDATE article_queue SET title_hint = 'Cafetière à grains en été : l''utilisation optimale pendant le pic' WHERE keyword_target = 'piege moustique ete';
UPDATE article_queue SET title_hint = 'Comment stocker son cafetière à grains l''hiver sans l''abîmer' WHERE keyword_target = 'piege moustique hiver stockage';
UPDATE article_queue SET title_hint = 'Cycle de vie du moustique : à quel moment le piège est efficace' WHERE keyword_target = 'cycle vie moustique piege';

-- USAGE / INSTALLATION
UPDATE article_queue SET title_hint = 'Installer un cafetière à grains sur terrasse : tutoriel pas à pas' WHERE keyword_target = 'installer piege moustique terrasse';
UPDATE article_queue SET title_hint = 'Augmenter l''efficacité de son piège au jardin : 7 astuces qui marchent' WHERE keyword_target = 'piege moustique jardin efficacite';
UPDATE article_queue SET title_hint = 'Cafetière à grains au propane : durée et coût d''une bouteille' WHERE keyword_target = 'piege moustique propane bouteille';
UPDATE article_queue SET title_hint = 'Attractants pour cafetière à grains : octénol, lurex, lactique comparés' WHERE keyword_target = 'attractant moustique piege';

-- BONUS HAUTE INTENTION (autocomplete Amazon)
UPDATE article_queue SET title_hint = 'Cafetière à grain : le guide ultime pour bien choisir en 2026' WHERE keyword_target = 'piege a moustique';
UPDATE article_queue SET title_hint = 'Cafetière à grains : sélection des modèles vraiment efficaces 2026' WHERE keyword_target = 'piege a moustiques';
UPDATE article_queue SET title_hint = 'Cafetière à grain électrique : 6 modèles analysés pour 2026' WHERE keyword_target = 'piege a moustique electrique';
UPDATE article_queue SET title_hint = 'Cafetière à grain UV : ce qui marche vraiment et ce qu''il faut éviter' WHERE keyword_target = 'piege a moustique uv';
UPDATE article_queue SET title_hint = 'Cafetière à grain CO2 : la technologie la plus efficace expliquée' WHERE keyword_target = 'piege a moustique co2';
UPDATE article_queue SET title_hint = 'Cafetière à grain CO2 extérieur : top 4 pour grand jardin' WHERE keyword_target = 'piege a moustique co2 exterieur';
UPDATE article_queue SET title_hint = 'Cafetière à grain pour extérieur : la sélection 2026' WHERE keyword_target = 'piege a moustique tigre exterieur';
UPDATE article_queue SET title_hint = 'Cafetière à grain intérieur : silencieux et efficaces sans pesticide' WHERE keyword_target = 'piege a moustique interieur';
UPDATE article_queue SET title_hint = 'Cafetières à grain Biogents : toute la gamme comparée (BG-Mosquitaire, BG-Home, GAT)' WHERE keyword_target = 'piege a moustiques biogents';
UPDATE article_queue SET title_hint = 'Cafetière à grain extérieur : 8 modèles comparés pour terrasse et jardin' WHERE keyword_target = 'piege a moustique exterieur';
