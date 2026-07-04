# Lien — Réseau social web (PHP natif + AJAX + MySQL)

Réseau social complet inspiré de Facebook, développé en PHP natif (API REST) et JavaScript natif (AJAX, sans rechargement de page), avec une base de données MySQL nommée **`social_network`**.

## Description du projet

Lien permet aux utilisateurs de s'inscrire (avec confirmation par e-mail), publier des contenus (texte + image), réagir par des likes et des commentaires en direct, se faire des amis, discuter en messagerie privée (texte + image, mise à jour par polling JS toutes les 3 secondes), et gérer leur profil. Un back-office distinct permet aux modérateurs et administrateurs de superviser la plateforme.

## Architecture du projet

```
/
├── index.html   # Page d'accueil publique (connexion / inscription)
├── App.html #Page principale qui va charger le spa
├── assets/
│   ├── css/                    # variables.css, main.css, admin.css
│   ├── js/                     # api.js (client API), interactions.js (likes, sons, animations)
│   ├── images/                 # avatar/couverture par défaut
│   └── uploads/                # fichiers envoyés par les utilisateurs (avatars, covers, posts, chat)
├── vues/
│   ├── clients/                # accueil, profil, amis, chat, vérification e-mail, mot de passe oublié…
│   └── back-office/            # connexion admin, tableau de bord, utilisateurs, publications, équipe
├── api/                        # endpoints PHP (auth, posts, friends, profile, chat, admin)
├── config/                     # database.php, app.php, auth.php, mailer.php
├── lib/PHPMailer/               # bibliothèque d'envoi d'e-mails SMTP
└── database/schema.sql         # schéma complet de la base "social_network"
```

## Mode de fonctionnement

- **Authentification par jeton** : à la connexion, l'API renvoie un jeton stocké côté client dans `sessionStorage` (équivalent du `session` PHP côté navigateur, comme demandé dans le cahier des charges). Chaque appel à l'API envoie ce jeton via l'en-tête `Authorization: Bearer <token>`.
- **Aucun rechargement de page** : toutes les actions (likes, commentaires, publication, amis, messagerie, profil) passent par `fetch()` vers l'API PHP, qui répond en JSON.
- **Messagerie en temps réel simulée** : la page de chat interroge `api/chat/poll.php` toutes les 3 secondes pour récupérer les nouveaux messages (conforme à la consigne « intervalle JS »).
- **E-mails HTML** : la confirmation d'inscription et la réinitialisation de mot de passe envoient un e-mail HTML via le SMTP de Gmail (PHPMailer), avec un gabarit aux couleurs de Lien.





## Identifiants de test

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur (back-office) | `admin@lien.test` | `Password123!` |
| Modérateur (back-office) | `moderateur@lien.test` | `Password123!` |
| Client | `daniel@lien.test` | `Password123!` |
| Client | `sandra@lien.test` | `Password123!` |

Le back-office est accessible séparément via `vues/back-office/connexion.html`.

## Fonctionnalités implémentées

- Inscription avec confirmation d'e-mail HTML, connexion, mot de passe oublié / réinitialisation
- Fil d'actualité : publication (texte + image), likes avec animation/son, commentaires en direct
- Gestion des amis : liste des membres, demandes d'amitié, acceptation/refus
- Profil personnel : modification des informations, mot de passe, photo de profil et couverture
- Messagerie : conversations, recherche d'amis, envoi de texte/image, actualisation par polling 3s
- Back-office : connexion séparée, tableau de bord avec statistiques, gestion des utilisateurs et publications (modérateur), gestion des modérateurs/administrateurs (administrateur)

## Membres du groupe



| Membre | Tâches réalisées |
|---|---|
| GBENOU Ezéchias | Architecture,index.html, app.html, routeur SPA, back-office, assets  |
| HOUNKPATI Joseph|Partials, Clients,config,database |
| KOUDJONOU Bless Elom| admin,auth,profile,chat |
| TIKO Joinel Mystère | posts, friends, notifications,lib |