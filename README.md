# Nom des étudiants
Bachekou DIABY  

# URL du dépôt
https://github.com/Bachekou-DIABY/Api-Platform-ESGI

# I ) Projet Api-Platform-ESGI

## 0. Lancement du projet
Vous pouvez lancer le serveur avec la commande symfony server:start puis vous rendre a l'url http://127.0.0.1:8000/.

## 1. Utilisation de l'api Recherche entreprise

Vous pouvez y accéder par l'url "http://127.0.0.1:8000/search". En entrant la raison sociale d'une entreprise puis en appuyant sur le bouton Valider, vous serez redirigé vers une page à l'url "http://127.0.0.1:8000/show" listant les entreprises avec une raison sociale similaire
Vous pourrez alors sauvegarder dans le fichier base de données l'entreprise souhaitée en appuyant sur le bouton sauvegarder correspondant.
Vous serez redirigé vers l'url "http://127.0.0.1:8000/save" ou vous pourrez voir la raison sociale, le siret, le siren et l'adresse de l'entreprise séléctionnée et sauvegardé en base de données.

## 2. Utilisation de l'api de l'URSAFF

Vous pouvez y accéder par l'url "http://127.0.0.1:8000/salary". En entrant le salaire pour lequel vous souhaitez faire vos estimations puis en appuyant sur le bouton Valider, vous serez redirigé vers une page à l'url vous indiquant une estimation de différentes valeurs comme le salaire net avant impot pour un salarié selon le contrat ou la gratificatio minimale d'un stagiaire dans l'entreprise en question. Je me suis rendu compte plus tard que je n'ai pas effectué les bons calculs mais étant seul sur le projet et en semaine d'entreprise, je n'ai pas eu réellement le temps de corriger cela.

## 3. Ouverture d'api 

Vous pourrez retrouver les différentes url permettant d'executer les différentes fonctions dans le code du fichier "ApiEntrepriseController.php" situé dans le dossier "src/Controller"
les requêtes a dispositions sont:
- http://127.0.0.1:8000/getAllCompanies  => permet de récuperer la liste des entreprises dans la base de données
- http://127.0.0.1:8000/getCompany/{siren} avec {siren} etant un paramètre a fournir lors de l'appel => permet de récuperer l'entreprise au siren correspondant dans la base de données
- http://127.0.0.1:8000/createCompany  => permet de créer une entreprise dans la base de données
- http://127.0.0.1:8000/patchCompany/{siren} avec {siren} etant un paramètre a fournir lors de l'appel => permet de modifier une entreprise dans la base de données
- http://127.0.0.1:8000/deleteCompany/{siren} avec {siren} etant un paramètre a fournir lors de l'appel => permet de supprimer une entreprise dans la base de données

# II ) Livraison Continue / Déploiement Continu

## Git / GitHub
Le projet est paramétré pour être public (il est accessible à tous)
Afin de réaliser un push sur la branche main, il est nécessaire de faire une pull request sur une autre branche que la branche main et il faut obligatoirement qu'un reviewer autre que le développeur qui à fait la pull request vérifie le code et approuve les modifications
Ainsi, la branche main ne peut contenir que des commit de merge depuis les autres branches.

## CI
Un script de CI est éxécuté à chaque pull request. Son rôle est de:
- Réaliser un lint du code et verifier qu'il est aux normes syntaxiques de PHP
- Réaliser les tests unitaires avec PHPUnit afin de vérifier que les fonctions marchent bien individuellement
- Réaliser le build de l'image docker afin de le déployer plus tard sur DockerHub

Si le lint ou les tests echouent, la PR est bloquée
Sinon la PR est autorisée à être mergée

## Déploiement Continu

Si la PR passe et que la branche est mergée sur main, on réexecute le script de CI précédent et si le script ne présente pas d'echec, 
une image docker est build, je me connecte a DockerHub puis je deploie l'image générée sur DockerHub avec le tag "latest"

## Livraison Continue

Dans le cas ou un tag serait crée directement depuis le depôt git, le script réalisant le processus de livraison continue et de déploiement continu est éxecuté et une image Docker est générée et publiée sur DockerHub mais cette fois ci avec le même tag que le tag crée sur git.

Le projet contient bien un DockerHub mais je n'ai pas pu effectuer la vérification avec hadolint car je n'ai pas réussi a installer de version compatible via composer 