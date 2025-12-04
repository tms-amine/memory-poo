Memory Football - petit README

Installation rapide:
- Importez `database/schema.sql` dans votre MySQL (via phpMyAdmin ou mysql CLI)
- Placez le dossier dans le répertoire www de Laragon
- Ouvrez `http://localhost/memory-poo/public` ou votre host configuré

Notes:
- La base de données par défaut est `memory_game` et l'utilisateur `root` sans mot de passe (modifiez `src/Database.php` si besoin)
- Le jeu enregistre le score via `save_score.php` et affiche le top via `leaderboard.php`
