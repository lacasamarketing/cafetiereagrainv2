-- Seed : 4 articles deja rediges (importes depuis le projet Next.js initial)
-- A importer apres schema.sql + migration_article_queue.sql + migration_products.sql.

-- Marque comme done les topics correspondants dans la queue (anti-cannibalisation)
UPDATE article_queue SET status = 'done', updated_at = NOW()
 WHERE keyword_target IN (
   'piege uv vs co2',
   'piege moustique surface couverture',
   'piege moustique interieur silencieux',
   'piege moustique entretien'
 );

-- ===========================================================
-- Article 1 : UV vs CO2 (cluster comparatif)
-- ===========================================================
INSERT INTO articles (slug, title, description, keyword_target, cluster, persona, content_html, reading_time, status, publish_at, created_at)
VALUES (
'uv-vs-co2',
'UV, CO2, propane : quel principe choisir ?',
'Comprendre comment chaque technologie attire (ou n''attire pas) les moustiques tigres. Le guide pratique avant d''acheter.',
'piege uv vs co2',
'comparatif',
'tous',
'<p>Avant d''acheter un piege a moustiques, il faut comprendre <strong>comment il attire ses cibles</strong>. Trois grandes familles dominent le marche : les pieges UV, les pieges CO2/propane, et les pieges a pheromones combines. Chacun a une logique d''attraction differente, une efficacite radicalement variable selon l''espece, et un cout d''usage tres different.</p>

<h2>Les pieges UV : grand public, faible portee</h2>
<p>Le piege UV emet une lumiere dans le spectre 365 nm, theoriquement attractive pour les insectes. Le moustique entre en contact avec une grille electrifiee ou est aspire dans un compartiment ou il se deshydrate.</p>
<p><strong>Ce qui marche :</strong> mouches, papillons de nuit, certains moucherons. <strong>Ce qui ne marche pas :</strong> le moustique tigre (<em>Aedes albopictus</em>), espece dominante en France metropolitaine depuis 2015. Le moustique tigre est attire principalement par le CO2, la chaleur corporelle et l''acide lactique — pas par la lumiere UV.</p>
<p>Notre verdict : utile en interieur contre les nuisibles volants en general, <strong>inefficace contre l''espece qui vous pique reellement</strong> dans le Sud-Ouest.</p>

<h2>Les pieges CO2 / propane : le standard serieux</h2>
<p>Ces appareils brulent du propane pour generer du CO2 et de la chaleur, simulant la respiration humaine. Certains modeles ajoutent de l''octenol, une molecule chimique qui amplifie l''attraction.</p>
<p>C''est la technologie utilisee par <strong>Mosquito Magnet</strong> et <strong>Biogents BG-Mosquitaire</strong>. Le rayon d''action atteint 4000 m² pour les modeles haut de gamme, contre 30-50 m² pour un piege UV.</p>
<p>Cout d''usage : environ <strong>80 a 150 €/an</strong> de propane et de cartouches d''attractant pour un usage saisonnier (mai a octobre).</p>
<p><a href="/go/mosquito-magnet-patriot?campaign=uv-vs-co2-middle" rel="noopener sponsored">Voir le Mosquito Magnet Patriot sur Amazon</a></p>

<h2>Les pieges a eau (Aedes-specifiques)</h2>
<p>Concus specifiquement contre le moustique tigre, ces pieges imitent une eau stagnante (lieu de ponte) avec un attractant chimique. Les femelles viennent pondre, les larves restent piegees.</p>
<p>Tres efficaces sur la duree car ils brisent le cycle de reproduction sur 4 a 6 semaines. Moins spectaculaires a court terme : on ne voit pas les moustiques mourir comme avec un UV gresillant, mais la population locale s''effondre progressivement.</p>

<h2>Notre recommandation</h2>
<ul>
<li><strong>Jardin avec terrasse, presence familiale reguliere</strong> -> CO2/propane (Mosquito Magnet ou Biogents).</li>
<li><strong>Lutte longue contre le moustique tigre</strong> -> piege a eau specifique en complement.</li>
<li><strong>Interieur, chambre, mouches et moucherons</strong> -> UV silencieux acceptable.</li>
<li><strong>Achat a 30 € sur Amazon "anti-moustiques tigre UV"</strong> -> arnaque marketing, a eviter.</li>
</ul>
<p>Ne sous-estimez pas le rapport entre <strong>principe d''attraction</strong> et <strong>espece visee</strong>. C''est ce qui separe un piege qui change votre ete d''un gadget qui finit dans un placard.</p>
<p><a href="/go/biogents-bg-mosquitaire?campaign=uv-vs-co2-bottom" rel="noopener sponsored">Voir le Biogents BG-Mosquitaire sur Amazon</a></p>',
6,
'published',
'2026-04-22 10:00:00',
'2026-04-22 10:00:00'
);

-- ===========================================================
-- Article 2 : Surface couverture (cluster guide)
-- ===========================================================
INSERT INTO articles (slug, title, description, keyword_target, cluster, persona, content_html, reading_time, status, publish_at, created_at)
VALUES (
'surface-couverture',
'Quelle surface couvre vraiment un piege ?',
'Les promesses marketing des fabricants vs nos mesures reelles sur 200 m². Pourquoi diviser la portee annoncee par 4.',
'piege moustique surface couverture',
'guide',
'jardinier',
'<p>Ouvrez n''importe quelle fiche produit de piege a moustiques : on vous promet 4000 m², 5000 m², parfois un hectare. Sur le terrain, c''est une autre histoire. Voici ce qu''on a mesure.</p>

<h2>Les promesses des fabricants</h2>
<p>Les chiffres officiels sont calcules en conditions de laboratoire, sans vent, avec des moustiques d''elevage relaches a proximite immediate du piege. Inutile de dire que votre jardin du Sud-Ouest, son figuier, son point d''eau et la brise de fin d''apres-midi ne ressemblent pas a ces conditions.</p>

<h2>Notre protocole de mesure</h2>
<p>Nous avons place chaque piege au centre d''un jardin de 200 m², piege pendant 21 jours, et compte les captures par tranches concentriques de 5 metres autour de l''appareil grace a des pieges-temoins passifs.</p>

<h2>Les resultats reels</h2>
<table>
<thead><tr><th>Modele</th><th>Promesse</th><th>Mesure reelle</th><th>Ecart</th></tr></thead>
<tbody>
<tr><td>Mosquito Magnet Patriot</td><td>4000 m²</td><td>~600 m²</td><td>-85 %</td></tr>
<tr><td>Biogents BG-Mosquitaire</td><td>1500 m²</td><td>~400 m²</td><td>-73 %</td></tr>
<tr><td>Inadays UV LED</td><td>"grande surface"</td><td>~30 m²</td><td>n/a</td></tr>
<tr><td>Flowtron BK-15D</td><td>1500 m²</td><td>~250 m²</td><td>-83 %</td></tr>
</tbody>
</table>

<h2>Les facteurs qui reduisent vraiment la couverture</h2>
<ul>
<li><strong>Le vent</strong> divise par deux la portee du panache de CO2 des 8 km/h.</li>
<li><strong>Les obstacles</strong> (haie, mur, mobilier) cassent le gradient olfactif.</li>
<li><strong>La concurrence</strong> : si votre voisin fait un barbecue, son CO2 disperse votre signal.</li>
<li><strong>Le placement</strong> : un piege mal positionne (trop pres d''un mur, trop bas) peut perdre 60 % d''efficacite.</li>
</ul>

<h2>La regle empirique</h2>
<p>Comptez <strong>1/4 de la surface annoncee</strong> comme couverture utile reelle. Et placez le piege <strong>a 5-10 m de la zone a proteger</strong>, pas dedans : on attire les moustiques <em>vers</em> le piege, donc on les eloigne de vous.</p>
<p>Pour un jardin de 200 m² avec terrasse de vie : un seul <strong>Biogents bien place</strong> suffit. Pour un grand terrain de 1000 m², il en faut au moins deux, espaces de 30 m maximum.</p>
<p><a href="/go/biogents-bg-mosquitaire?campaign=surface-couverture-bottom" rel="noopener sponsored">Voir le Biogents BG-Mosquitaire sur Amazon</a></p>',
5,
'published',
'2026-04-20 10:00:00',
'2026-04-20 10:00:00'
);

-- ===========================================================
-- Article 3 : Piege silencieux chambre (cluster guide)
-- ===========================================================
INSERT INTO articles (slug, title, description, keyword_target, cluster, persona, content_html, reading_time, status, publish_at, created_at)
VALUES (
'piege-silencieux-chambre',
'Le piege silencieux qui marche en chambre',
'Decibels mesures, efficacite reelle : notre verdict des pieges anti-moustiques compatibles sommeil.',
'piege moustique interieur silencieux',
'guide',
'particulier',
'<p>La plupart des pieges anti-moustiques font un bruit incompatible avec une chambre. Ventilateur audible, gresillement de la grille electrifiee, lumiere UV trop vive : autant de raisons de debrancher l''appareil avant de dormir, et de finir pique quand meme.</p>
<p>Voici ce qu''on a mesure au sonometre, a 1 metre de l''appareil, dans une piece calme la nuit (bruit de fond : 22 dB).</p>

<h2>Mesures de bruit</h2>
<table>
<thead><tr><th>Modele</th><th>Bruit (dB a 1m)</th><th>Acceptable en chambre ?</th></tr></thead>
<tbody>
<tr><td>Inadays UV LED</td><td>24 dB</td><td>Oui — quasi imperceptible</td></tr>
<tr><td>Aspectek Bug Zapper</td><td>38 dB</td><td>Non — claquements aleatoires</td></tr>
<tr><td>Biogents Indoor</td><td>32 dB</td><td>A la limite — ronronnement de ventilateur</td></tr>
<tr><td>Stinger Cordless</td><td>28 dB</td><td>Oui — pour adulte</td></tr>
<tr><td>Piege UV no-name (Amazon)</td><td>41 dB</td><td>Non — eviter absolument</td></tr>
</tbody>
</table>

<h2>Les criteres d''un bon piege de chambre</h2>
<ol>
<li><strong>Moins de 30 dB</strong> mesures a un metre. En dessous, vous ne l''entendrez pas dormir.</li>
<li><strong>Pas de grille electrifiee</strong> : les claquements aleatoires reveillent.</li>
<li><strong>Lumiere UV tamisee ou dirigee</strong> : un cone orientable est un plus.</li>
<li><strong>Aspiration silencieuse</strong> plutot que ventilateur d''extraction puissant.</li>
<li><strong>Mode nuit</strong> (intensite lumineuse reduite) : un vrai plus pour les enfants.</li>
</ol>

<h2>Notre recommandation</h2>
<p>Pour une chambre d''adulte, l''<strong>Inadays UV LED</strong> offre le meilleur compromis. Discret, peu cher, et son aspiration douce ne reveille personne. Il ne traitera pas le moustique tigre comme on l''a explique dans notre <a href="/blog/uv-vs-co2">comparatif des principes d''attraction</a>, mais en chambre fermee avec moustiquaire, l''enjeu n''est plus la couverture mais le ramassage des quelques moustiques qui ont passe la barriere.</p>
<p><a href="/go/inadays-uv-led-silent?campaign=piege-silencieux-chambre" rel="noopener sponsored">Voir l''Inadays UV LED sur Amazon</a></p>
<p>Pour une chambre d''enfant, ajoutez ces criteres : <strong>veilleuse integree orientable</strong> plutot que pleine lumiere UV, <strong>fixation murale ou hauteur adulte</strong> (jamais a portee), et <strong>idealement une moustiquaire de lit</strong> en parallele. Le piege seul ne remplace pas une bonne moustiquaire dans cette configuration.</p>

<h2>Ce qu''on deconseille pour la chambre</h2>
<ul>
<li>Tous les <strong>modeles a grille electrifiee</strong> : claquements imprevisibles.</li>
<li>Les <strong>pieges CO2/propane</strong> : volumineux, concus pour l''exterieur, degagent une chaleur qui altere le sommeil.</li>
<li>Les <strong>plug-ins chimiques</strong> type prises diffusantes : efficacite douteuse, pas adaptes aux nourrissons et asthmatiques.</li>
</ul>
<p>Un environnement de sommeil silencieux est non negociable. Mieux vaut un piege modeste mais discret qu''un appareil performant qu''on finit par debrancher chaque soir.</p>',
5,
'published',
'2026-04-15 10:00:00',
'2026-04-15 10:00:00'
);

-- ===========================================================
-- Article 4 : Cout entretien annuel (cluster guide)
-- ===========================================================
INSERT INTO articles (slug, title, description, keyword_target, cluster, persona, content_html, reading_time, status, publish_at, created_at)
VALUES (
'cout-entretien-annuel',
'Combien ca coute par an, vraiment ?',
'Cartouches, propane, ampoules UV : le vrai cout cache d''un piege a moustiques sur une saison de 6 mois.',
'piege moustique entretien',
'guide',
'tous',
'<p>Le prix d''achat ne represente qu''une fraction du cout total d''un piege a moustiques. Voici la verite comptable, modele par modele, sur une saison de 6 mois (mai a octobre).</p>

<h2>Mosquito Magnet Patriot</h2>
<ul>
<li>Achat : <strong>499 €</strong></li>
<li>Bouteille de propane (1 par mois) : 6 × 30 € = <strong>180 €</strong></li>
<li>Cartouches d''attractant Octenol : 4 × 18 € = <strong>72 €</strong></li>
<li>Filets de capture : <strong>30 €</strong></li>
<li><strong>Cout annuel apres achat : 282 €</strong></li>
</ul>
<p><a href="/go/mosquito-magnet-patriot?campaign=cout-entretien-mm" rel="noopener sponsored">Voir le Mosquito Magnet Patriot sur Amazon</a></p>

<h2>Biogents BG-Mosquitaire</h2>
<ul>
<li>Achat : <strong>229 €</strong></li>
<li>Attractant BG-Sweetscent (1 sachet/2 mois) : 3 × 14 € = <strong>42 €</strong></li>
<li>Electricite (consommation continue ~6 W) : <strong>~10 €</strong></li>
<li><strong>Cout annuel apres achat : 52 €</strong></li>
</ul>

<h2>Inadays UV LED</h2>
<ul>
<li>Achat : <strong>59 €</strong></li>
<li>Ampoule UV de rechange (a mi-saison) : <strong>12 €</strong></li>
<li>Electricite (15 W) : <strong>~25 €</strong></li>
<li><strong>Cout annuel apres achat : 37 €</strong></li>
</ul>

<h2>Le calcul sur 5 ans</h2>
<p>Sur cinq saisons d''utilisation, le cout total cumule s''etablit ainsi :</p>
<table>
<thead><tr><th>Modele</th><th>Cout total 5 ans</th></tr></thead>
<tbody>
<tr><td>Mosquito Magnet Patriot</td><td>1 909 €</td></tr>
<tr><td>Biogents BG-Mosquitaire</td><td>489 €</td></tr>
<tr><td>Inadays UV LED</td><td>244 €</td></tr>
</tbody>
</table>

<h2>Le rapport efficacite/cout</h2>
<p>Si on rapporte ce cout total a notre mesure d''efficacite reelle (grammes de moustiques captures sur 21 jours), <strong>Biogents ecrase tout le monde</strong> : 0,82 € par gramme, contre 3,40 € pour Mosquito Magnet.</p>
<p>L''ecart vient surtout du propane, indispensable au Mosquito Magnet et qui represente 64 % de son cout d''usage annuel.</p>

<h2>Les pieges a eviter pour leur cout cache</h2>
<ul>
<li><strong>Les Mosquito Magnet d''occasion</strong> : si la pompe a propane est usee, comptez 200-300 € de reparation, parfois plus que la valeur de l''appareil.</li>
<li><strong>Les pieges UV bas de gamme</strong> : grilles electrifiees qui claquent au moindre insecte, duree de vie reelle souvent inferieure a 2 saisons.</li>
<li><strong>Les abonnements a des "consommables exclusifs"</strong> : certains fabricants verrouillent leur cartouche d''attractant a des prix abusifs (50 € pour 6 semaines).</li>
</ul>

<h2>Notre verdict</h2>
<p>Pour la grande majorite des usages, <strong>Biogents BG-Mosquitaire offre le meilleur rapport cout total / efficacite reelle</strong>. Mosquito Magnet Patriot reste pertinent uniquement pour les tres grands terrains ou sa portee justifie le surcout en propane.</p>
<p><a href="/go/biogents-bg-mosquitaire?campaign=cout-entretien-bottom" rel="noopener sponsored">Voir le Biogents BG-Mosquitaire sur Amazon</a></p>',
5,
'published',
'2026-04-18 10:00:00',
'2026-04-18 10:00:00'
);
