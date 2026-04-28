-- =================================================================
-- extend_topics_editorial.sql
-- Étend la queue article_queue avec ~50 nouveaux topics distribués
-- dans 4 nouvelles catégories : DIY (remèdes maison), Science, Santé, FAQ.
-- Stratégie éditoriale : 1 article/jour, rotation hebdo des clusters.
-- =================================================================

SET NAMES utf8mb4;

-- ========== CLUSTER : DIY / REMÈDES NATURELS (15 topics) ==========
-- Lundi & Jeudi : remèdes maison + DIY trap (avec ingrédients Amazon)
INSERT INTO article_queue (keyword_target, title_hint, cluster, persona, priority) VALUES
('anti moustique fait maison', 'Anti-moustique fait maison : 8 recettes qui marchent vraiment (et 3 mythes)', 'diy', 'particulier', 10),
('vinaigre blanc moustique', 'Le vinaigre blanc contre les moustiques : ce qui marche, ce qui ne marche pas', 'diy', 'particulier', 9),
('huile essentielle citronnelle moustique', 'Huile essentielle de citronnelle : guide complet anti-moustique', 'diy', 'particulier', 9),
('huile essentielle lavande moustique', 'Lavande contre les moustiques : usages et limites réelles', 'diy', 'particulier', 8),
('plantes anti moustiques', 'Top 12 des plantes anti-moustiques pour terrasse et balcon', 'diy', 'jardinier', 10),
('citronnelle plante moustique', 'La citronnelle (Cymbopogon) en pot : efficacité réelle et alternatives', 'diy', 'jardinier', 8),
('geranium anti moustique', 'Géranium odorant : la plante anti-moustique la plus efficace ?', 'diy', 'jardinier', 7),
('piege moustique maison bouteille', 'Cafetière à grain maison à la bouteille : tutoriel CO2 levure', 'diy', 'particulier', 9),
('repulsif moustique naturel', 'Répulsif moustique naturel : 5 recettes maison (avec ingrédients à acheter)', 'diy', 'particulier', 9),
('huile essentielle moustique tigre', 'Huiles essentielles efficaces contre le moustique tigre : la liste réelle', 'diy', 'particulier', 9),
('cafe marc anti moustique', 'Le marc de café contre les moustiques : info ou intox ?', 'diy', 'particulier', 6),
('clous girofle citron moustique', 'Le citron piqué de clous de girofle : test et avis', 'diy', 'particulier', 6),
('ventilateur anti moustique', 'Le ventilateur, le meilleur anti-moustique gratuit ? Explications', 'diy', 'particulier', 7),
('eau savonneuse moustique', 'Piège à eau savonneuse contre le moustique tigre : la méthode', 'diy', 'jardinier', 7),
('moustiquaire fenetre fait maison', 'Fabriquer une moustiquaire de fenêtre soi-même : guide pas à pas', 'diy', 'particulier', 6);

-- ========== CLUSTER : SCIENCE / BIOLOGIE (10 topics) ==========
-- Samedi : autorité topique, contenu informationnel
INSERT INTO article_queue (keyword_target, title_hint, cluster, persona, priority) VALUES
('cycle vie moustique', 'Cycle de vie du moustique : de l''œuf à l''adulte, 4 phases comprises', 'science', 'tous', 9),
('moustique tigre origine', 'Aedes albopictus : d''où vient le moustique tigre et pourquoi il s''étend', 'science', 'tous', 9),
('pourquoi moustique pique', 'Pourquoi les moustiques piquent (et pourquoi seulement les femelles)', 'science', 'tous', 10),
('moustique attire couleur', 'Quelles couleurs attirent les moustiques selon les études récentes', 'science', 'tous', 8),
('moustique groupe sanguin', 'Le moustique préfère-t-il un groupe sanguin ? Ce que dit la recherche', 'science', 'tous', 8),
('moustique odeur attire', 'Pourquoi certaines personnes attirent plus les moustiques (acide lactique, CO2, sueur)', 'science', 'tous', 9),
('moustique tigre vs commun', 'Moustique tigre vs moustique commun : les vraies différences', 'science', 'tous', 8),
('moustique reproduction', 'Reproduction du moustique : combien d''œufs, où, à quelle vitesse', 'science', 'tous', 7),
('moustique duree vie', 'Durée de vie d''un moustique : ce que disent les biologistes', 'science', 'tous', 6),
('moustique distance vol', 'Jusqu''où vole un moustique ? Distance, vitesse, autonomie réelle', 'science', 'tous', 7);

-- ========== CLUSTER : SANTÉ & SÉCURITÉ (10 topics) ==========
-- Mardi alt + sujets pointus E-A-T
INSERT INTO article_queue (keyword_target, title_hint, cluster, persona, priority) VALUES
('protection moustique bebe', 'Comment protéger un bébé des moustiques : les solutions vraiment safe', 'sante', 'parent', 10),
('moustiquaire lit bebe', 'Moustiquaire de lit bébé : guide d''achat et installation sécurisée', 'sante', 'parent', 9),
('allergie piqure moustique', 'Allergie aux piqûres de moustique : symptômes, soins, quand consulter', 'sante', 'particulier', 9),
('huile essentielle moustique enfant', 'Huiles essentielles anti-moustiques : lesquelles sont safe pour les enfants', 'sante', 'parent', 9),
('moustique chien chat', 'Moustiques et animaux domestiques : risques (dirofilariose) et protections', 'sante', 'particulier', 8),
('piqure moustique tigre danger', 'Piqûre de moustique tigre : risques réels et signes à surveiller', 'sante', 'particulier', 9),
('chikungunya dengue zika france', 'Chikungunya, dengue, zika en France : où en est-on en 2026', 'sante', 'tous', 9),
('moustique nid jardin', 'Comment savoir si on a un nid de moustiques dans son jardin', 'sante', 'jardinier', 8),
('repulsif moustique femme enceinte', 'Anti-moustiques et grossesse : ce qui est autorisé, ce qui ne l''est pas', 'sante', 'parent', 9),
('soulager piqure moustique', '10 solutions pour soulager une piqûre de moustique (testées)', 'sante', 'particulier', 8);

-- ========== CLUSTER : FAQ / QUESTIONS USUELLES (10 topics) ==========
-- Dimanche : questions courtes, snippets Google, longue traîne
INSERT INTO article_queue (keyword_target, title_hint, cluster, persona, priority) VALUES
('piege moustique fonctionne vraiment', 'Les cafetières à grain fonctionnent-ils vraiment ? La réponse honnête', 'faq', 'tous', 10),
('combien temps piege moustique efficace', 'Au bout de combien de temps un cafetière à grains est-il efficace ?', 'faq', 'tous', 8),
('piege moustique allume jour nuit', 'Faut-il laisser son cafetière à grains allumé jour et nuit ?', 'faq', 'tous', 7),
('piege moustique consommation electrique', 'Combien consomme un cafetière à grains sur l''année ?', 'faq', 'tous', 6),
('piege moustique pluie', 'Un cafetière à grains peut-il rester sous la pluie ?', 'faq', 'jardinier', 7),
('moustique disparaitre jardin', 'Comment faire disparaître les moustiques de son jardin durablement', 'faq', 'jardinier', 9),
('moustique attire lumiere', 'Les moustiques sont-ils vraiment attirés par la lumière ?', 'faq', 'tous', 8),
('piege moustique tigre vraiment efficace', 'Existe-t-il un piège vraiment efficace contre le moustique tigre ?', 'faq', 'tous', 10),
('moustique piscine attire', 'Une piscine attire-t-elle les moustiques (et que faire) ?', 'faq', 'jardinier', 7),
('piege moustique sans bruit', 'Existe-t-il un cafetière à grains totalement silencieux ?', 'faq', 'particulier', 8);

-- ========== CLUSTER : SAISON (5 topics complémentaires) ==========
INSERT INTO article_queue (keyword_target, title_hint, cluster, persona, priority) VALUES
('moustique septembre octobre', 'Moustiques en septembre-octobre : la fin de saison qui n''en finit pas', 'saison', 'tous', 7),
('moustique avril mai', 'Avril-mai : pourquoi c''est le mois clé pour casser le cycle des moustiques', 'saison', 'tous', 8),
('moustique juin juillet', 'Juin-juillet : le pic moustique, comment l''anticiper', 'saison', 'tous', 8),
('moustique nuit jour', 'À quelle heure piquent les moustiques (et comment s''adapter)', 'saison', 'tous', 7),
('moustique apres pluie', 'Pourquoi il y a plus de moustiques après la pluie', 'saison', 'tous', 7);
