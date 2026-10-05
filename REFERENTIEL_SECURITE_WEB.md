# Référentiel de test de vulnérabilités — Application web

> **Cadre d'usage.** Ce document sert à tester la sécurité de VOTRE application (ou d'une application pour laquelle vous avez une autorisation écrite), de préférence sur un environnement de **staging**. Les tests intrusifs sur la production se font uniquement avec accord explicite et hors des heures de charge. Objectif : trouver les failles, en apporter une preuve minimale, et livrer la correction. On ne cherche jamais à exploiter au-delà de ce qui prouve la faille, ni à exfiltrer des données réelles.

> **Instruction pour Claude.** Quand ce document est fourni, tu agis comme un testeur de sécurité applicative expérimenté. Tu suis la méthodologie ci-dessous (basée sur l'OWASP Web Security Testing Guide et le Top 10). Tu commences par cadrer (section 1), puis tu déroules les catégories pertinentes, tu consignes chaque constat au format de la section 12, et tu termines par un rapport priorisé. Si une information de cadrage manque, tu la demandes avant de commencer.

## Sommaire
1. [Cadrage et règles d'engagement](#1-cadrage)
2. [Reconnaissance et cartographie](#2-reconnaissance)
3. [Configuration et déploiement](#3-configuration)
4. [Authentification](#4-authentification)
5. [Gestion de session](#5-session)
6. [Contrôle d'accès et autorisations](#6-autorisations)
7. [Validation des entrées et injections](#7-injections)
8. [Logique métier](#8-logique-métier)
9. [Côté client](#9-côté-client)
10. [API et services](#10-api)
11. [Dépendances et chaîne d'approvisionnement](#11-dépendances)
12. [Modèle de constat et rapport](#12-livrables)
13. [Boîte à outils](#13-outils)

---

## 1. Cadrage

Avant tout test, fixer :
- **Périmètre** : domaines, sous-domaines, API incluses ; ce qui est explicitement hors périmètre.
- **Environnement** : staging de préférence ; URL, comptes de test par rôle.
- **Autorisation** : confirmer que l'application vous appartient ou que vous êtes autorisé à la tester.
- **Contraintes** : pas de test de charge/déni de service sans accord, pas de manipulation de données clients réelles, fenêtre horaire.
- **Comptes** : au moins deux comptes par rôle (pour tester l'accès horizontal entre utilisateurs), plus un compte à privilèges faibles et un compte admin.

Livrable de cette étape : une carte des risques `Zone | Impact (1-5) | Exposition (1-5) | Score | Catégories à tester`, qui priorise le reste de l'audit.

---

## 2. Reconnaissance

**Compétence : comprendre la surface d'attaque avant de la sonder.**
- Cartographier les pages, les formulaires, les points d'upload, les redirections.
- Repérer les technologies : en-têtes de réponse, empreintes du framework, fichiers `robots.txt`, `sitemap.xml`, `/.well-known/`.
- Chercher les points d'entrée cachés : paramètres d'URL, champs masqués, endpoints d'API référencés dans le JavaScript.
- Repérer les fuites d'information : commentaires HTML, messages d'erreur verbeux, fichiers exposés (`.env`, `.git/`, `.bak`, `/backup`, dumps SQL).
- Contenu généré côté client : lire les bundles JS pour y trouver des routes, des clés et des commentaires.

---

## 3. Configuration

**Compétence : détecter les mauvaises configurations, cause fréquente de compromission.**
- **En-têtes de sécurité** : présence et valeur de `Content-Security-Policy`, `Strict-Transport-Security`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `X-Frame-Options` ou directive `frame-ancestors`.
- **TLS** : HTTPS partout, redirection depuis HTTP, pas de contenu mixte, certificat valide.
- **CORS** : vérifier que `Access-Control-Allow-Origin` n'est pas `*` sur des endpoints authentifiés, et qu'il ne reflète pas aveuglément l'origine avec `Allow-Credentials: true`.
- **Méthodes HTTP** : méthodes inutiles (`PUT`, `DELETE`, `TRACE`) désactivées si non nécessaires.
- **Erreurs** : pas de stack trace ni de version de composant exposées ; page 404/500 générique.
- **Fichiers exposés** : panneaux d'administration, endpoints de debug, `/actuator`, `/phpinfo`, consoles, listing de répertoires.
- **Cookies** : attributs `Secure`, `HttpOnly`, `SameSite` cohérents.

---

## 4. Authentification

**Compétence : évaluer la robustesse de l'entrée dans l'application.**
- Politique de mot de passe : longueur minimale, blocage des mots de passe courants.
- Limitation des tentatives (rate limiting) et verrouillage après échecs répétés — vérifier aussi côté API, pas seulement sur le formulaire.
- Énumération de comptes : les messages « identifiant inconnu » vs « mot de passe incorrect » et les temps de réponse ne doivent pas révéler l'existence d'un compte.
- Réinitialisation de mot de passe : jeton à usage unique, imprévisible, à durée de vie courte, invalidé après usage ; pas de fuite du jeton dans l'URL de référent.
- Authentification à deux facteurs : contournement, réutilisation de code, absence de limitation sur la saisie du code.
- « Se souvenir de moi » et persistance : durée, révocation.
- Transport : identifiants jamais envoyés en clair, jamais en paramètre d'URL.

---

## 5. Session

**Compétence : vérifier qu'une session ne peut être ni volée ni prolongée indûment.**
- Génération de l'identifiant de session : aléatoire, suffisamment long, régénéré après connexion (anti fixation de session).
- Invalidation réelle côté serveur à la déconnexion (le jeton ne doit plus fonctionner ensuite).
- Expiration par inactivité et durée maximale absolue.
- Cookies de session avec `HttpOnly`, `Secure`, `SameSite`.
- Jetons JWT le cas échéant : algorithme non `none`, signature vérifiée, expiration présente, secret non trivial, pas de données sensibles en clair dans le payload.
- Protection CSRF sur les actions changeant l'état : jeton anti-CSRF ou `SameSite` strict, vérifié côté serveur.

---

## 6. Autorisations

**Compétence : la faille la plus courante et la plus grave — vérifier que chacun n'accède qu'à ce qui lui revient.**
- **Accès horizontal (IDOR)** : avec le compte A, tenter d'accéder aux ressources du compte B en changeant un identifiant dans l'URL, le corps de requête ou l'API. À tester sur lecture ET sur modification/suppression.
- **Accès vertical** : avec un compte à faibles privilèges, tenter d'atteindre les fonctions admin (URL directe, appel d'API, paramètre `role`).
- **Contrôle côté serveur uniquement** : vérifier qu'une fonction masquée dans l'interface est bien refusée au niveau de l'API, pas seulement cachée à l'écran.
- **Références directes** : identifiants séquentiels prévisibles, UUID exposés réutilisables.
- **Élévation** : modification du rôle via un champ transmis par le client, endpoints d'inscription qui acceptent un rôle.

C'est la catégorie à traiter en priorité : les défauts d'autorisation sont fréquents, faciles à exploiter et à fort impact.

---

## 7. Injections

**Compétence : identifier là où une entrée utilisateur est interprétée comme du code ou une commande.**
- **XSS (Cross-Site Scripting)** : injecter des marqueurs dans chaque champ affiché ensuite ; distinguer réfléchi, stocké (le plus grave, car servi à d'autres utilisateurs) et basé sur le DOM. Vérifier l'encodage en sortie et la CSP.
- **Injection SQL / NoSQL** : caractères spéciaux dans les paramètres, comportements différentiels, erreurs de base ; confirmer que le code utilise des requêtes paramétrées.
- **Injection de commandes système** : partout où une entrée alimente une commande serveur.
- **Injection de templates (SSTI)** : moteurs de rendu côté serveur.
- **Traversée de répertoire (path traversal)** : `../` dans les paramètres de fichiers et les uploads.
- **Redirection ouverte** : paramètres `redirect`, `next`, `url` renvoyant vers un domaine externe.
- **En-têtes** : injection dans les en-têtes reflétés (Host, en-têtes de cache).
- **Upload de fichiers** : type et taille validés côté serveur, renommage, stockage hors racine web, pas d'exécution du fichier déposé.

Approche défensive : on prouve l'injection avec la charge la plus inoffensive possible (un marqueur qui s'affiche, une erreur de syntaxe), on ne va pas plus loin, et on documente la correction (paramétrage, encodage, validation).

---

## 8. Logique métier

**Compétence : ce qu'aucun scanner ne trouve — les failles propres au fonctionnement de l'application.**
- Contournement d'étapes d'un processus (passer directement au paiement, sauter une validation).
- Manipulation de valeurs : prix négatif, quantité négative, remise cumulée, montant modifié entre le panier et le paiement.
- Rejeu et idempotence : soumettre deux fois une opération sensible (paiement, transfert).
- Concurrence (race condition) : deux requêtes simultanées sur la même ressource (double dépense, double usage d'un coupon).
- Abus de fonctionnalités légitimes : quotas non appliqués, limites contournées.
- Cohérence des droits selon l'état de l'objet (brouillon, publié, archivé).

---

## 9. Côté client

**Compétence : ce que le navigateur expose.**
- Secrets ou clés d'API dans le code front ou le dépôt.
- Données sensibles en `localStorage` / `sessionStorage`.
- Logique de sécurité implémentée uniquement côté client (toujours doublonner côté serveur).
- Bibliothèques front obsolètes avec vulnérabilités connues.
- Clickjacking : la page peut-elle être placée dans une iframe tierce ?
- Postmessage et communication inter-fenêtres non filtrée.

---

## 10. API

**Compétence : les API portent aujourd'hui la majorité de la surface d'attaque.**
- Authentification et autorisation sur **chaque** endpoint, y compris ceux non documentés.
- Autorisation au niveau de l'objet (l'équivalent IDOR pour les API) et au niveau de la propriété (un champ sensible modifiable qu'il ne devrait pas être — *mass assignment*).
- Validation des entrées : types, bornes, champs inattendus.
- Limitation de débit et protection contre l'énumération.
- Verbes HTTP et endpoints cachés (documentation Swagger/OpenAPI exposée, versions anciennes `/v1` encore actives).
- Réponses trop bavardes : renvoyer plus de données que ce que l'écran affiche.
- Consommation de ressources : pagination imposée, taille de payload limitée.

---

## 11. Dépendances

**Compétence : la plupart du code d'une app moderne vient de tiers.**
- Inventaire des dépendances directes et transitives.
- Scan des vulnérabilités connues : `npm audit`, `pip-audit`, `osv-scanner`, ou l'équivalent du langage.
- Versions obsolètes des composants serveur (framework, serveur web, base).
- Images de conteneurs : base à jour, pas de secrets embarqués.
- Fichiers de verrouillage (`package-lock`, `poetry.lock`) présents et cohérents.

---

## 12. Livrables

### Modèle de constat
```
ID : VULN-001
Catégorie : Autorisation / Injection / Config / Auth / Session / Logique / API / Dépendances
Titre : description courte de la faille
Sévérité : Critique / Élevée / Moyenne / Faible / Info
Emplacement : URL, endpoint, paramètre
Pré-requis : rôle/compte nécessaire
Preuve : requête minimale + réponse, capture ; strictement de quoi démontrer la faille
Impact : conséquence concrète pour l'application ou les utilisateurs
Correction : recommandation précise, avec exemple de code ou de configuration
Effort : S / M / L
Références : identifiant OWASP WSTG, CWE
```

### Échelle de sévérité (repère)
- **Critique** : accès non authentifié à des données/fonctions sensibles, exécution de code, prise de contrôle de compte à grande échelle.
- **Élevée** : IDOR sur données sensibles, injection exploitable, contournement d'authentification limité.
- **Moyenne** : XSS réfléchi nécessitant une interaction, fuite d'information exploitable.
- **Faible** : en-tête manquant, énumération de comptes, divulgation mineure.
- **Info** : bonne pratique non respectée sans impact direct.

### Rapport de synthèse
1. Périmètre, environnement, dates, comptes utilisés.
2. Carte des risques.
3. Tableau des constats par sévérité.
4. Les constats détaillés, du plus grave au moins grave.
5. Les 5 corrections prioritaires.
6. Ce qui n'a pas été couvert et pourquoi.
7. Recommandation générale sur la posture de sécurité.

---

## 13. Outils

Selon les droits et l'environnement :
- **Manuels** : navigateur + outils de développement, un proxy d'interception (OWASP ZAP, Burp Suite) pour observer et rejouer les requêtes.
- **Scan passif / actif encadré** : OWASP ZAP sur staging.
- **En-têtes et TLS** : inspection via le navigateur, testssl.sh pour la configuration TLS.
- **Dépendances** : `npm audit`, `pip-audit`, `osv-scanner`, Trivy pour les conteneurs.
- **Statique** : Semgrep sur le code source pour repérer requêtes non paramétrées, secrets, patterns dangereux.
- **Accessibilité de la revue** : lire le code des routes, des middlewares d'auth et de la validation reste souvent le moyen le plus sûr de confirmer une faille.

Privilégier toujours la vérification manuelle raisonnée sur le scan aveugle : un scanner génère du bruit et rate la logique métier et les défauts d'autorisation, qui sont les plus importants.

---

## Utilisation
- **Projet Claude** : ajouter ce fichier aux connaissances, puis demander « Audite la sécurité de mon app selon ce référentiel, sur staging ».
- **Dépôt** : placer à la racine ou dans `docs/`, éventuellement référencé dans `CLAUDE.md`.
- **Ponctuel** : joindre le fichier en indiquant la catégorie voulue (par exemple « section 6, autorisations »).

Fournir l'URL de staging et, si possible, l'accès au code accélère et fiabilise l'audit.
