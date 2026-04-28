-- =================================================================
-- update_articles_v3.sql — REMPLACE update_articles_v2.sql
-- Contenu des 4 articles initiaux avec accents UTF-8 corrects.
-- Posture : analyse des retours utilisateurs + croisement specs constructeurs.
-- =================================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- ----- 1. uv-vs-co2 -----
UPDATE articles SET
    title = 'UV, CO2, propane : quel principe choisir ?',
    description = 'Comprendre comment chaque technologie attire (ou n''attire pas) les moustiques tigres. Le guide pratique avant d''acheter.',
    content_html = '<p>Avant d''acheter un cafetière à grains, il faut comprendre <strong>comment il attire ses cibles</strong>. Trois grandes familles dominent le marché : les pièges UV, les pièges CO2/propane, et les pièges à ponte spécifiques. Chacun a une logique d''attraction différente, une efficacité radicalement variable selon l''espèce, et un coût d''usage très différent.</p>

<h2>Les pièges UV : grand public, faible portée</h2>
<p>Le piège UV émet une lumière dans le spectre 365 nm, théoriquement attractive pour les insectes volants. Le moustique entre en contact avec une grille électrifiée ou est aspiré dans un compartiment où il se déshydrate.</p>
<p><strong>Ce qui fonctionne :</strong> mouches, papillons de nuit, certains moucherons, selon le consensus des retours utilisateurs. <strong>Ce qui ne fonctionne pas :</strong> le moustique tigre (<em>Aedes albopictus</em>), espèce dominante en France métropolitaine depuis 2015. Les recherches entomologiques confirment que le tigre est attiré principalement par le CO2, la chaleur corporelle et l''acide lactique, pas par la lumière UV.</p>
<p>Résumé : utile en intérieur contre les nuisibles volants en général, <strong>peu efficace contre l''espèce qui pique réellement</strong> dans le Sud-Ouest selon la majorité des retours.</p>

<h2>Les pièges CO2 / propane : le standard sérieux</h2>
<p>Ces appareils brûlent du propane pour générer du CO2 et de la chaleur, simulant la respiration humaine. Certains modèles ajoutent de l''octénol, une molécule chimique qui amplifie l''attraction.</p>
<p>C''est la technologie utilisée par <strong>Mosquito Magnet</strong> et certains modèles <strong>Biogents</strong>. Le rayon d''action annoncé atteint 4000 m² pour les modèles haut de gamme, contre 30-50 m² pour un piège UV. Les utilisateurs remontent que la couverture utile réelle est généralement plus faible que la promesse, mais l''efficacité contre le tigre est nettement supérieure aux UV.</p>
<p>Coût d''usage : environ <strong>80 à 150 €/an</strong> de propane et de cartouches d''attractant pour un usage saisonnier (mai à octobre).</p>
<p><a href="/go/mosquito-magnet-patriot?campaign=uv-vs-co2-middle" rel="noopener sponsored">Voir le Mosquito Magnet Patriot sur Amazon</a></p>

<h2>Les pièges à ponte (Aedes-spécifiques)</h2>
<p>Conçus spécifiquement contre le moustique tigre, ces pièges imitent une eau stagnante (lieu de ponte) avec un attractant chimique. Les femelles viennent pondre, les larves restent piégées.</p>
<p>Les retours utilisateurs convergent sur un effet long terme plutôt que spectaculaire : on ne voit pas les moustiques mourir comme avec un UV grésillant, mais la population locale tend à diminuer sur 4 à 6 semaines.</p>

<h2>Notre recommandation</h2>
<ul>
<li><strong>Jardin avec terrasse, présence familiale régulière</strong> → CO2/propane (Mosquito Magnet ou Biogents BG-Mosquitaire).</li>
<li><strong>Lutte longue contre le moustique tigre</strong> → piège à ponte spécifique en complément.</li>
<li><strong>Intérieur, chambre, mouches et moucherons</strong> → UV silencieux acceptable.</li>
<li><strong>Achat à 30 € sur Amazon « anti-moustiques tigre UV »</strong> → les retours convergent sur l''inefficacité. À éviter.</li>
</ul>
<p>Le rapport entre <strong>principe d''attraction</strong> et <strong>espèce visée</strong> est le critère principal. C''est ce qui sépare un piège qui change la donne d''un gadget qui finit dans un placard.</p>
<p><a href="/go/biogents-bg-mosquitaire?campaign=uv-vs-co2-bottom" rel="noopener sponsored">Voir le Biogents BG-Mosquitaire sur Amazon</a></p>'
WHERE slug = 'uv-vs-co2';

-- ----- 2. surface-couverture -----
UPDATE articles SET
    title = 'Quelle surface couvre vraiment un piège ?',
    description = 'Promesses fabricants vs couverture utile réelle selon les retours utilisateurs. Pourquoi diviser la portée annoncée par 4.',
    content_html = '<p>Ouvrez n''importe quelle fiche produit de cafetière à grains : on vous promet 4000 m², 5000 m², parfois un hectare. Sur le terrain, les retours utilisateurs racontent une autre histoire.</p>

<h2>Les promesses des fabricants</h2>
<p>Les chiffres officiels sont calculés en conditions de laboratoire, sans vent, avec des moustiques d''élevage relâchés à proximité immédiate du piège. Inutile de dire qu''un jardin du Sud-Ouest, son figuier, son point d''eau et la brise de fin d''après-midi ne ressemblent pas à ces conditions.</p>

<h2>Ce que remontent les utilisateurs</h2>
<p>En croisant les retours utilisateurs sur les principaux modèles avec les surfaces annoncées, un écart se dessine de manière récurrente : la couverture utile en jardin réel se situe généralement <strong>entre 1/4 et 1/3 de la promesse fabricant</strong>.</p>
<table>
<thead><tr><th>Modèle</th><th>Surface annoncée</th><th>Couverture utile estimée</th><th>Écart</th></tr></thead>
<tbody>
<tr><td>Mosquito Magnet Patriot</td><td>4000 m²</td><td>500–800 m²</td><td>−80 % env.</td></tr>
<tr><td>Biogents BG-Mosquitaire</td><td>1500 m²</td><td>300–500 m²</td><td>−70 % env.</td></tr>
<tr><td>Inadays UV LED</td><td>« grande surface »</td><td>30–50 m² max</td><td>n/a</td></tr>
<tr><td>Flowtron BK-15D</td><td>1500 m²</td><td>200–300 m²</td><td>−80 % env.</td></tr>
</tbody>
</table>

<h2>Les facteurs qui réduisent la couverture</h2>
<ul>
<li><strong>Le vent</strong> divise par deux la portée du panache de CO2 dès 8 km/h.</li>
<li><strong>Les obstacles</strong> (haie, mur, mobilier) cassent le gradient olfactif.</li>
<li><strong>La concurrence</strong> : si le voisin fait un barbecue, son CO2 disperse votre signal.</li>
<li><strong>Le placement</strong> : un piège mal positionné (trop près d''un mur, trop bas) perd 50 % d''efficacité selon les retours.</li>
</ul>

<h2>La règle empirique</h2>
<p>Compte <strong>1/4 de la surface annoncée</strong> comme couverture utile réelle. Et place le piège <strong>à 5–10 m de la zone à protéger</strong>, pas dedans : on attire les moustiques <em>vers</em> le piège, donc on les éloigne de la zone de vie.</p>
<p>Pour un jardin de 200 m² avec terrasse de vie : un seul <strong>Biogents bien placé</strong> couvre la demande selon les utilisateurs. Pour un grand terrain de 1000 m², il en faut au moins deux, espacés de 30 m maximum.</p>
<p><a href="/go/biogents-bg-mosquitaire?campaign=surface-couverture-bottom" rel="noopener sponsored">Voir le Biogents BG-Mosquitaire sur Amazon</a></p>'
WHERE slug = 'surface-couverture';

-- ----- 3. piege-silencieux-chambre -----
UPDATE articles SET
    title = 'Le piège silencieux qui marche en chambre',
    description = 'Décibels constructeurs et retours utilisateurs : la sélection des cafetières à grain compatibles sommeil.',
    content_html = '<p>La plupart des cafetières à grain font un bruit incompatible avec une chambre. Ventilateur audible, grésillement de la grille électrifiée, lumière UV trop vive : autant de raisons de débrancher l''appareil avant de dormir, et de finir piqué quand même.</p>
<p>Voici les niveaux sonores annoncés par les fabricants, croisés avec les retours utilisateurs sur la perception réelle du bruit la nuit.</p>

<h2>Niveaux sonores annoncés et retours</h2>
<table>
<thead><tr><th>Modèle</th><th>Bruit annoncé (1 m)</th><th>Acceptable en chambre selon les utilisateurs</th></tr></thead>
<tbody>
<tr><td>Inadays UV LED</td><td>22–24 dB</td><td>Oui, quasi imperceptible selon les retours</td></tr>
<tr><td>Aspectek Bug Zapper</td><td>~38 dB</td><td>Non, claquements aléatoires remontés par les utilisateurs</td></tr>
<tr><td>Biogents Indoor</td><td>~32 dB</td><td>À la limite, ronronnement de ventilateur signalé</td></tr>
<tr><td>Stinger Cordless</td><td>~28 dB</td><td>Oui pour adulte selon les retours</td></tr>
<tr><td>Piège UV no-name</td><td>40+ dB</td><td>Non, à éviter selon le consensus utilisateurs</td></tr>
</tbody>
</table>

<h2>Les critères d''un bon piège de chambre</h2>
<ol>
<li><strong>Moins de 30 dB</strong> annoncés à un mètre. En dessous, les utilisateurs rapportent ne pas l''entendre dormir.</li>
<li><strong>Pas de grille électrifiée</strong> : les claquements aléatoires réveillent.</li>
<li><strong>Lumière UV tamisée ou dirigée</strong> : un cône orientable est un plus.</li>
<li><strong>Aspiration silencieuse</strong> plutôt que ventilateur d''extraction puissant.</li>
<li><strong>Mode nuit</strong> (intensité lumineuse réduite) : plébiscité par les parents pour les chambres d''enfant.</li>
</ol>

<h2>Notre recommandation</h2>
<p>Pour une chambre d''adulte, l''<strong>Inadays UV LED</strong> ressort comme le meilleur compromis dans les retours utilisateurs. Discret, peu cher, son aspiration douce ne réveille pas selon le consensus. Il ne traite pas le moustique tigre comme détaillé dans notre <a href="/blog/uv-vs-co2">comparatif des principes d''attraction</a>, mais en chambre fermée avec moustiquaire, l''enjeu n''est plus la couverture mais le ramassage des quelques moustiques qui ont passé la barrière.</p>
<p><a href="/go/inadays-uv-led-silent?campaign=piege-silencieux-chambre" rel="noopener sponsored">Voir l''Inadays UV LED sur Amazon</a></p>
<p>Pour une chambre d''enfant, ajoute ces critères : <strong>veilleuse intégrée orientable</strong> plutôt que pleine lumière UV, <strong>fixation murale ou hauteur adulte</strong> (jamais à portée), et <strong>idéalement une moustiquaire de lit</strong> en parallèle. Les retours indiquent que le piège seul ne remplace pas une bonne moustiquaire dans cette configuration.</p>

<h2>Ce qu''on déconseille pour la chambre</h2>
<ul>
<li>Tous les <strong>modèles à grille électrifiée</strong> : claquements imprévisibles signalés par les utilisateurs.</li>
<li>Les <strong>pièges CO2/propane</strong> : volumineux, conçus pour l''extérieur, dégagent une chaleur qui altère le sommeil.</li>
<li>Les <strong>plug-ins chimiques</strong> type prises diffusantes : efficacité contestée, pas adaptés aux nourrissons et asthmatiques selon les recommandations santé.</li>
</ul>
<p>Un environnement de sommeil silencieux est non négociable. Mieux vaut un piège modeste mais discret qu''un appareil performant qu''on finit par débrancher chaque soir.</p>'
WHERE slug = 'piege-silencieux-chambre';

-- ----- 4. cout-entretien-annuel -----
UPDATE articles SET
    title = 'Combien ça coûte par an, vraiment ?',
    description = 'Cartouches, propane, ampoules UV : le coût caché d''un cafetière à grains sur une saison de 6 mois.',
    content_html = '<p>Le prix d''achat ne représente qu''une fraction du coût total d''un cafetière à grains. Voici l''estimation comptable, modèle par modèle, sur une saison de 6 mois (mai à octobre), basée sur les fiches constructeurs et les retours d''usage des propriétaires.</p>

<h2>Mosquito Magnet Patriot</h2>
<ul>
<li>Achat : <strong>499 €</strong></li>
<li>Bouteille de propane (1 par mois en moyenne selon les retours) : 6 × 30 € = <strong>180 €</strong></li>
<li>Cartouches d''attractant Octénol : 4 × 18 € = <strong>72 €</strong></li>
<li>Filets de capture : <strong>30 €</strong></li>
<li><strong>Coût annuel hors achat : 282 €</strong></li>
</ul>
<p><a href="/go/mosquito-magnet-patriot?campaign=cout-entretien-mm" rel="noopener sponsored">Voir le Mosquito Magnet Patriot sur Amazon</a></p>

<h2>Biogents BG-Mosquitaire</h2>
<ul>
<li>Achat : <strong>229 €</strong></li>
<li>Attractant BG-Sweetscent (1 sachet/2 mois) : 3 × 14 € = <strong>42 €</strong></li>
<li>Électricité (consommation continue ~6 W) : <strong>~10 €</strong></li>
<li><strong>Coût annuel hors achat : 52 €</strong></li>
</ul>

<h2>Inadays UV LED</h2>
<ul>
<li>Achat : <strong>59 €</strong></li>
<li>Ampoule UV de rechange (à mi-saison) : <strong>12 €</strong></li>
<li>Électricité (15 W) : <strong>~25 €</strong></li>
<li><strong>Coût annuel hors achat : 37 €</strong></li>
</ul>

<h2>Le calcul sur 5 ans</h2>
<p>Sur cinq saisons d''utilisation, le coût total cumulé s''établit ainsi :</p>
<table>
<thead><tr><th>Modèle</th><th>Coût total 5 ans</th></tr></thead>
<tbody>
<tr><td>Mosquito Magnet Patriot</td><td>1 909 €</td></tr>
<tr><td>Biogents BG-Mosquitaire</td><td>489 €</td></tr>
<tr><td>Inadays UV LED</td><td>244 €</td></tr>
</tbody>
</table>

<h2>Les pièges à éviter pour leur coût caché</h2>
<ul>
<li><strong>Les Mosquito Magnet d''occasion</strong> : si la pompe à propane est usée, comptez 200–300 € de réparation selon les retours, parfois plus que la valeur de l''appareil.</li>
<li><strong>Les pièges UV bas de gamme</strong> : grilles électrifiées qui claquent au moindre insecte, durée de vie réelle souvent inférieure à 2 saisons selon les utilisateurs.</li>
<li><strong>Les abonnements à des « consommables exclusifs »</strong> : certains fabricants verrouillent leur cartouche d''attractant à des prix abusifs (50 € pour 6 semaines).</li>
</ul>

<h2>Notre recommandation</h2>
<p>Pour la grande majorité des usages, <strong>Biogents BG-Mosquitaire offre le meilleur rapport coût total / efficacité annoncée</strong> selon le croisement specs + retours utilisateurs. Mosquito Magnet Patriot reste pertinent uniquement pour les très grands terrains où sa portée justifie le surcoût en propane.</p>
<p><a href="/go/biogents-bg-mosquitaire?campaign=cout-entretien-bottom" rel="noopener sponsored">Voir le Biogents BG-Mosquitaire sur Amazon</a></p>'
WHERE slug = 'cout-entretien-annuel';
