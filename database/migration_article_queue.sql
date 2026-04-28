-- Migration : table article_queue + queue de demarrage cafetiereagrain.fr
-- A importer via phpMyAdmin une fois.

CREATE TABLE IF NOT EXISTS article_queue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    keyword_target VARCHAR(150) NOT NULL,
    title_hint VARCHAR(255) DEFAULT NULL,
    cluster VARCHAR(50) DEFAULT 'general',
    persona VARCHAR(50) DEFAULT 'tous',
    priority INT DEFAULT 5,
    status ENUM('pending','in_progress','done','failed') DEFAULT 'pending',
    notes TEXT DEFAULT NULL,
    article_id INT UNSIGNED DEFAULT NULL,
    attempts INT UNSIGNED DEFAULT 0,
    last_attempt_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status_priority (status, priority, created_at),
    CONSTRAINT fk_queue_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===========================================================
-- Queue de demarrage : 30 mots-cles, anti-cannibalisation, pic SEO mai-aout
-- ===========================================================

INSERT INTO article_queue (keyword_target, title_hint, cluster, persona, priority) VALUES

-- COMPARATIFS (8) - les plus rentables en affiliation
('meilleur piege moustiques 2026', 'Meilleur piege a moustiques 2026 : test des 8 modeles qui marchent vraiment', 'comparatif', 'tous', 10),
('mosquito magnet avis', 'Mosquito Magnet : avis complet apres 3 saisons de test', 'test', 'tous', 10),
('piege uv vs co2', 'Piege UV ou CO2 : lequel attire vraiment les moustiques ?', 'comparatif', 'tous', 10),
('piege moustique tigre', 'Piege a moustique tigre : les 5 modeles efficaces contre Aedes albopictus', 'comparatif', 'tous', 9),
('piege moustique exterieur', 'Piege a moustiques exterieur : top 6 pour terrasse et jardin', 'comparatif', 'jardinier', 9),
('piege moustique interieur silencieux', 'Piege a moustiques interieur silencieux : selection chambre et salon', 'comparatif', 'particulier', 9),
('piege moustique connecte', 'Piege a moustiques connecte : 4 modeles pilotables au smartphone', 'comparatif', 'tous', 7),
('piege moustique solaire', 'Piege a moustiques solaire : autonomie reelle et efficacite testee', 'comparatif', 'jardinier', 7),

-- GUIDES D''ACHAT (7) - intention transactionnelle moyenne
('comment choisir piege moustique', 'Comment choisir son piege a moustiques en 2026 : 5 criteres a verifier', 'guide', 'tous', 10),
('piege moustique pas cher', 'Piege a moustiques pas cher : 5 modeles efficaces sous 50 euros', 'guide', 'particulier', 9),
('piege moustique surface couverture', 'Quelle surface couvre vraiment un piege a moustiques ? Le vrai mode d''emploi', 'guide', 'tous', 8),
('ou placer piege moustique', 'Ou placer son piege a moustiques pour qu''il fonctionne vraiment', 'guide', 'tous', 8),
('piege moustique entretien', 'Cout d''entretien annuel d''un piege a moustiques : le vrai budget', 'guide', 'tous', 7),
('piege moustique professionnel', 'Piege a moustiques professionnel : que choisir pour restaurant ou hotel', 'guide', 'pro', 7),
('piege moustique grande surface', 'Piege a moustiques grande surface : couvrir 1000 m2 et plus', 'guide', 'pro', 6),

-- TESTS PRODUITS (6) - SEO long-tail + conversion
('biogents bg-mosquitaire avis', 'Biogents BG-Mosquitaire : test apres une saison entiere', 'test', 'tous', 9),
('mosquito magnet patriot test', 'Mosquito Magnet Patriot : test approfondi 2026', 'test', 'tous', 8),
('piege moustique zero in test', 'Piege Zero In : avis honnete apres 2 mois d''utilisation', 'test', 'tous', 7),
('piege moustique aspirant electrique', 'Piege a moustiques aspirant electrique : 4 modeles compares', 'test', 'particulier', 7),
('piege moustique batterie test', 'Piege a moustiques sur batterie : autonomie et efficacite reelles', 'test', 'tous', 6),
('piege moustique chambre bebe', 'Piege a moustiques chambre bebe : lesquels sont vraiment sans danger', 'test', 'particulier', 9),

-- SAISONNALITE / USAGE (5) - SEO informationnel + retention
('quand installer piege moustique', 'Quand installer son piege a moustiques : le bon timing pour la saison', 'saison', 'tous', 8),
('piege moustique printemps', 'Piege a moustiques au printemps : preparer la saison des l''eclosion', 'saison', 'tous', 7),
('piege moustique ete', 'Piege a moustiques en ete : l''utilisation optimale pendant le pic', 'saison', 'tous', 8),
('piege moustique hiver stockage', 'Comment stocker son piege a moustiques l''hiver sans l''abimer', 'saison', 'tous', 5),
('cycle vie moustique piege', 'Cycle de vie du moustique : a quel moment le piege est efficace', 'usage', 'tous', 5),

-- USAGE / INSTALLATION (4) - tutos engageants
('installer piege moustique terrasse', 'Installer un piege a moustiques sur terrasse : tutoriel pas a pas', 'usage', 'jardinier', 7),
('piege moustique jardin efficacite', 'Augmenter l''efficacite de son piege au jardin : 7 astuces qui marchent', 'usage', 'jardinier', 7),
('piege moustique propane bouteille', 'Piege a moustiques au propane : duree et cout d''une bouteille', 'usage', 'tous', 6),
('attractant moustique piege', 'Attractants pour piege a moustiques : octenol, lurex, lactique compares', 'usage', 'tous', 8);

-- ===========================================================
-- BONUS : Top requetes a forte intention d'achat (autocomplete Amazon)
-- Ces mots-cles sont ceux tapes juste avant un achat, ROI affiliation maximal.
-- ===========================================================

INSERT INTO article_queue (keyword_target, title_hint, cluster, persona, priority) VALUES

-- Le generique pur (volume max, conversion forte)
('piege a moustique', 'Piege a moustique : le guide ultime pour bien choisir en 2026', 'guide', 'tous', 10),
('piege a moustiques', 'Piege a moustiques : selection des modeles vraiment efficaces 2026', 'comparatif', 'tous', 10),

-- Variations Amazon a haute intention (chaque variante = page dediee anti-cannibalisation)
('piege a moustique electrique', 'Piege a moustique electrique : 6 modeles testes pour 2026', 'comparatif', 'tous', 10),
('piege a moustique uv', 'Piege a moustique UV : ce qui marche vraiment et ce qu''il faut eviter', 'comparatif', 'tous', 10),
('piege a moustique co2', 'Piege a moustique CO2 : la technologie la plus efficace expliquee', 'comparatif', 'tous', 10),
('piege a moustique co2 exterieur', 'Piege a moustique CO2 exterieur : top 4 pour grand jardin', 'comparatif', 'jardinier', 9),
('piege a moustique tigre exterieur', 'Piege a moustique tigre pour exterieur : la selection 2026', 'comparatif', 'jardinier', 10),
('piege a moustique interieur', 'Piege a moustique interieur : silencieux et efficaces sans pesticide', 'comparatif', 'particulier', 10),
('piege a moustiques biogents', 'Pieges a moustiques Biogents : toute la gamme comparee (BG-Mosquitaire, BG-Home, GAT)', 'comparatif', 'tous', 9),
('piege a moustique exterieur', 'Piege a moustique exterieur : 8 modeles testes pour terrasse et jardin', 'comparatif', 'jardinier', 10);
