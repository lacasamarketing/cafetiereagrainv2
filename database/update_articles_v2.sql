-- =================================================================
-- update_articles_v2.sql
-- Reecriture des 4 articles initiaux sans references a un test physique.
-- Posture : analyse des retours utilisateurs + croisement specs constructeurs.
-- A executer apres seed_articles.sql (UPDATE qui remplace le content_html
-- des 4 articles existants).
-- =================================================================

-- ----- 1. uv-vs-co2 -----
UPDATE articles SET
    title = 'UV, CO2, propane : quel principe choisir ?',
    description = 'Comprendre comment chaque technologie attire (ou n''attire pas) les moustiques tigres. Le guide pratique avant d''acheter.',
    content_html = '<p>Avant d''acheter un piege a moustiques, il faut comprendre <strong>comment il attire ses cibles</strong>. Trois grandes familles dominent le marche : les pieges UV, les pieges CO2/propane, et les pieges a ponte specifiques. Chacun a une logique d''attraction differente, une efficacite radicalement variable selon l''espece, et un cout d''usage tres different.</p>

<h2>Les pieges UV : grand public, faible portee</h2>
<p>Le piege UV emet une lumiere dans le spectre 365 nm, theoriquement attractive pour les insectes volants. Le moustique entre en contact avec une grille electrifiee ou est aspire dans un compartiment ou il se deshydrate.</p>
<p><strong>Ce qui fonctionne :</strong> mouches, papillons de nuit, certains moucherons, selon le consensus des retours utilisateurs. <strong>Ce qui ne fonctionne pas :</strong> le moustique tigre (<em>Aedes albopictus</em>), espece dominante en France metropolitaine depuis 2015. Les recherches entomologiques confirment que le tigre est attire principalement par le CO2, la chaleur corporelle et l''acide lactique, pas par la lumiere UV.</p>
<p>Resume : utile en interieur contre les nuisibles volants en general, <strong>peu efficace contre l''espece qui pique reellement</strong> dans le Sud-Ouest selon la majorite des retours.</p>

<h2>Les pieges CO2 / propane : le standard serieux</h2>
<p>Ces appareils brulent du propane pour generer du CO2 et de la chaleur, simulant la respiration humaine. Certains modeles ajoutent de l''octenol, une molecule chimique qui amplifie l''attraction.</p>
<p>C''est la technologie utilisee par <strong>Mosquito Magnet</strong> et certains modeles <strong>Biogents</strong>. Le rayon d''action annonce atteint 4000 m2 pour les modeles haut de gamme, contre 30-50 m2 pour un piege UV. Les utilisateurs remontent que la couverture utile reelle est generalement plus faible que la promesse, mais l''efficacite contre le tigre est nettement superieure aux UV.</p>
<p>Cout d''usage : environ <strong>80 a 150 EUR/an</strong> de propane et de cartouches d''attractant pour un usage saisonnier (mai a octobre).</p>
<p><a href="/go/mosquito-magnet-patriot?campaign=uv-vs-co2-middle" rel="noopener sponsored">Voir le Mosquito Magnet Patriot sur Amazon</a></p>

<h2>Les pieges a ponte (Aedes-specifiques)</h2>
<p>Concus specifiquement contre le moustique tigre, ces pieges imitent une eau stagnante (lieu de ponte) avec un attractant chimique. Les femelles viennent pondre, les larves restent piegees.</p>
<p>Les retours utilisateurs convergent sur un effet long terme plutot que spectaculaire : on ne voit pas les moustiques mourir comme avec un UV gresillant, mais la population locale tend a diminuer sur 4 a 6 semaines.</p>

<h2>Notre recommandation</h2>
<ul>
<li><strong>Jardin avec terrasse, presence familiale reguliere</strong> -> CO2/propane (Mosquito Magnet ou Biogents BG-Mosquitaire).</li>
<li><strong>Lutte longue contre le moustique tigre</strong> -> piege a ponte specifique en complement.</li>
<li><strong>Interieur, chambre, mouches et moucherons</strong> -> UV silencieux acceptable.</li>
<li><strong>Achat a 30 EUR sur Amazon "anti-moustiques tigre UV"</strong> -> les retours convergent sur l''inefficacite. A eviter.</li>
</ul>
<p>Le rapport entre <strong>principe d''attraction</strong> et <strong>espece visee</strong> est le critere principal. C''est ce qui separe un piege qui change la donne d''un gadget qui finit dans un placard.</p>
<p><a href="/go/biogents-bg-mosquitaire?campaign=uv-vs-co2-bottom" rel="noopener sponsored">Voir le Biogents BG-Mosquitaire sur Amazon</a></p>'
WHERE slug = 'uv-vs-co2';

-- ----- 2. surface-couverture -----
UPDATE articles SET
    title = 'Quelle surface couvre vraiment un piege ?',
    description = 'Promesses fabricants vs couverture utile reelle selon les retours utilisateurs. Pourquoi diviser la portee annoncee par 4.',
    content_html = '<p>Ouvrez n''importe quelle fiche produit de piege a moustiques : on vous promet 4000 m2, 5000 m2, parfois un hectare. Sur le terrain, les retours utilisateurs racontent une autre histoire.</p>

<h2>Les promesses des fabricants</h2>
<p>Les chiffres officiels sont calcules en conditions de laboratoire, sans vent, avec des moustiques d''elevage relaches a proximite immediate du piege. Inutile de dire qu''un jardin du Sud-Ouest, son figuier, son point d''eau et la brise de fin d''apres-midi ne ressemblent pas a ces conditions.</p>

<h2>Ce que remontent les utilisateurs</h2>
<p>En croisant les retours utilisateurs sur les principaux modeles avec les surfaces annoncees, un ecart se dessine de maniere recurrente : la couverture utile en jardin reel se situe generalement <strong>entre 1/4 et 1/3 de la promesse fabricant</strong>.</p>
<table>
<thead><tr><th>Modele</th><th>Surface annoncee</th><th>Couverture utile estimee</th><th>Ecart</th></tr></thead>
<tbody>
<tr><td>Mosquito Magnet Patriot</td><td>4000 m2</td><td>500-800 m2</td><td>-80 % env.</td></tr>
<tr><td>Biogents BG-Mosquitaire</td><td>1500 m2</td><td>300-500 m2</td><td>-70 % env.</td></tr>
<tr><td>Inadays UV LED</td><td>"grande surface"</td><td>30-50 m2 max</td><td>n/a</td></tr>
<tr><td>Flowtron BK-15D</td><td>1500 m2</td><td>200-300 m2</td><td>-80 % env.</td></tr>
</tbody>
</table>

<h2>Les facteurs qui reduisent la couverture</h2>
<ul>
<li><strong>Le vent</strong> divise par deux la portee du panache de CO2 des 8 km/h.</li>
<li><strong>Les obstacles</strong> (haie, mur, mobilier) cassent le gradient olfactif.</li>
<li><strong>La concurrence</strong> : si le voisin fait un barbecue, son CO2 disperse votre signal.</li>
<li><strong>Le placement</strong> : un piege mal positionne (trop pres d''un mur, trop bas) perd 50 % d''efficacite selon les retours.</li>
</ul>

<h2>La regle empirique</h2>
<p>Compte <strong>1/4 de la surface annoncee</strong> comme couverture utile reelle. Et place le piege <strong>a 5-10 m de la zone a proteger</strong>, pas dedans : on attire les moustiques <em>vers</em> le piege, donc on les eloigne de la zone de vie.</p>
<p>Pour un jardin de 200 m2 avec terrasse de vie : un seul <strong>Biogents bien place</strong> couvre la demande selon les utilisateurs. Pour un grand terrain de 1000 m2, il en faut au moins deux, espaces de 30 m maximum.</p>
<p><a href="/go/biogents-bg-mosquitaire?campaign=surface-couverture-bottom" rel="noopener sponsored">Voir le Biogents BG-Mosquitaire sur Amazon</a></p>'
WHERE slug = 'surface-couverture';

-- ----- 3. piege-silencieux-chambre -----
UPDATE articles SET
    title = 'Le piege silencieux qui marche en chambre',
    description = 'Decibels constructeurs et retours utilisateurs : la selection des pieges a moustiques compatibles sommeil.',
    content_html = '<p>La plupart des pieges anti-moustiques font un bruit incompatible avec une chambre. Ventilateur audible, gresillement de la grille electrifiee, lumiere UV trop vive : autant de raisons de debrancher l''appareil avant de dormir, et de finir pique quand meme.</p>
<p>Voici les niveaux sonores annonces par les fabricants, croises avec les retours utilisateurs sur la perception reelle du bruit la nuit.</p>

<h2>Niveaux sonores annonces et retours</h2>
<table>
<thead><tr><th>Modele</th><th>Bruit annonce (1m)</th><th>Acceptable en chambre selon les utilisateurs</th></tr></thead>
<tbody>
<tr><td>Inadays UV LED</td><td>22-24 dB</td><td>Oui, quasi imperceptible selon les retours</td></tr>
<tr><td>Aspectek Bug Zapper</td><td>~38 dB</td><td>Non, claquements aleatoires remontes par les utilisateurs</td></tr>
<tr><td>Biogents Indoor</td><td>~32 dB</td><td>A la limite, ronronnement de ventilateur signale</td></tr>
<tr><td>Stinger Cordless</td><td>~28 dB</td><td>Oui pour adulte selon les retours</td></tr>
<tr><td>Piege UV no-name</td><td>40+ dB</td><td>Non, a eviter selon le consensus utilisateurs</td></tr>
</tbody>
</table>

<h2>Les criteres d''un bon piege de chambre</h2>
<ol>
<li><strong>Moins de 30 dB</strong> annonces a un metre. En dessous, les utilisateurs rapportent ne pas l''entendre dormir.</li>
<li><strong>Pas de grille electrifiee</strong> : les claquements aleatoires reveillent.</li>
<li><strong>Lumiere UV tamisee ou dirigee</strong> : un cone orientable est un plus.</li>
<li><strong>Aspiration silencieuse</strong> plutot que ventilateur d''extraction puissant.</li>
<li><strong>Mode nuit</strong> (intensite lumineuse reduite) : plebiscite par les parents pour les chambres d''enfant.</li>
</ol>

<h2>Notre recommandation</h2>
<p>Pour une chambre d''adulte, l''<strong>Inadays UV LED</strong> ressort comme le meilleur compromis dans les retours utilisateurs. Discret, peu cher, son aspiration douce ne reveille pas selon le consensus. Il ne traite pas le moustique tigre comme detaille dans notre <a href="/blog/uv-vs-co2">comparatif des principes d''attraction</a>, mais en chambre fermee avec moustiquaire, l''enjeu n''est plus la couverture mais le ramassage des quelques moustiques qui ont passe la barriere.</p>
<p><a href="/go/inadays-uv-led-silent?campaign=piege-silencieux-chambre" rel="noopener sponsored">Voir l''Inadays UV LED sur Amazon</a></p>
<p>Pour une chambre d''enfant, ajoute ces criteres : <strong>veilleuse integree orientable</strong> plutot que pleine lumiere UV, <strong>fixation murale ou hauteur adulte</strong> (jamais a portee), et <strong>idealement une moustiquaire de lit</strong> en parallele. Les retours indiquent que le piege seul ne remplace pas une bonne moustiquaire dans cette configuration.</p>

<h2>Ce qu''on deconseille pour la chambre</h2>
<ul>
<li>Tous les <strong>modeles a grille electrifiee</strong> : claquements imprevisibles signales par les utilisateurs.</li>
<li>Les <strong>pieges CO2/propane</strong> : volumineux, concus pour l''exterieur, degagent une chaleur qui altere le sommeil.</li>
<li>Les <strong>plug-ins chimiques</strong> type prises diffusantes : efficacite contestee, pas adaptes aux nourrissons et asthmatiques selon les recommandations sante.</li>
</ul>
<p>Un environnement de sommeil silencieux est non negociable. Mieux vaut un piege modeste mais discret qu''un appareil performant qu''on finit par debrancher chaque soir.</p>'
WHERE slug = 'piege-silencieux-chambre';

-- ----- 4. cout-entretien-annuel -----
UPDATE articles SET
    title = 'Combien ca coute par an, vraiment ?',
    description = 'Cartouches, propane, ampoules UV : le cout cache d''un piege a moustiques sur une saison de 6 mois.',
    content_html = '<p>Le prix d''achat ne represente qu''une fraction du cout total d''un piege a moustiques. Voici l''estimation comptable, modele par modele, sur une saison de 6 mois (mai a octobre), basee sur les fiches constructeurs et les retours d''usage des proprietaires.</p>

<h2>Mosquito Magnet Patriot</h2>
<ul>
<li>Achat : <strong>499 EUR</strong></li>
<li>Bouteille de propane (1 par mois en moyenne selon les retours) : 6 x 30 EUR = <strong>180 EUR</strong></li>
<li>Cartouches d''attractant Octenol : 4 x 18 EUR = <strong>72 EUR</strong></li>
<li>Filets de capture : <strong>30 EUR</strong></li>
<li><strong>Cout annuel hors achat : 282 EUR</strong></li>
</ul>
<p><a href="/go/mosquito-magnet-patriot?campaign=cout-entretien-mm" rel="noopener sponsored">Voir le Mosquito Magnet Patriot sur Amazon</a></p>

<h2>Biogents BG-Mosquitaire</h2>
<ul>
<li>Achat : <strong>229 EUR</strong></li>
<li>Attractant BG-Sweetscent (1 sachet/2 mois) : 3 x 14 EUR = <strong>42 EUR</strong></li>
<li>Electricite (consommation continue ~6 W) : <strong>~10 EUR</strong></li>
<li><strong>Cout annuel hors achat : 52 EUR</strong></li>
</ul>

<h2>Inadays UV LED</h2>
<ul>
<li>Achat : <strong>59 EUR</strong></li>
<li>Ampoule UV de rechange (a mi-saison) : <strong>12 EUR</strong></li>
<li>Electricite (15 W) : <strong>~25 EUR</strong></li>
<li><strong>Cout annuel hors achat : 37 EUR</strong></li>
</ul>

<h2>Le calcul sur 5 ans</h2>
<p>Sur cinq saisons d''utilisation, le cout total cumule s''etablit ainsi :</p>
<table>
<thead><tr><th>Modele</th><th>Cout total 5 ans</th></tr></thead>
<tbody>
<tr><td>Mosquito Magnet Patriot</td><td>1 909 EUR</td></tr>
<tr><td>Biogents BG-Mosquitaire</td><td>489 EUR</td></tr>
<tr><td>Inadays UV LED</td><td>244 EUR</td></tr>
</tbody>
</table>

<h2>Les pieges a eviter pour leur cout cache</h2>
<ul>
<li><strong>Les Mosquito Magnet d''occasion</strong> : si la pompe a propane est usee, comptez 200-300 EUR de reparation selon les retours, parfois plus que la valeur de l''appareil.</li>
<li><strong>Les pieges UV bas de gamme</strong> : grilles electrifiees qui claquent au moindre insecte, duree de vie reelle souvent inferieure a 2 saisons selon les utilisateurs.</li>
<li><strong>Les abonnements a des "consommables exclusifs"</strong> : certains fabricants verrouillent leur cartouche d''attractant a des prix abusifs (50 EUR pour 6 semaines).</li>
</ul>

<h2>Notre recommandation</h2>
<p>Pour la grande majorite des usages, <strong>Biogents BG-Mosquitaire offre le meilleur rapport cout total / efficacite annoncee</strong> selon le croisement specs + retours utilisateurs. Mosquito Magnet Patriot reste pertinent uniquement pour les tres grands terrains ou sa portee justifie le surcout en propane.</p>
<p><a href="/go/biogents-bg-mosquitaire?campaign=cout-entretien-bottom" rel="noopener sponsored">Voir le Biogents BG-Mosquitaire sur Amazon</a></p>'
WHERE slug = 'cout-entretien-annuel';
