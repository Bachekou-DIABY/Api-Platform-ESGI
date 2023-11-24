# Api-Platform-ESGI

# 0. Lancement du projet
Vous pouvez lancer le serveur avec la commande symfony server:start puis vous rendre a l'url http://127.0.0.1:8000/.

# 1. Utilisation de l'api Recherche entreprise

Vous pouvez y accéder par l'url "http://127.0.0.1:8000/search". En entrant la raison sociale d'une entreprise puis en appuyant sur le bouton Valider, vous serez redirigé vers une page à l'url "http://127.0.0.1:8000/show" listant les entreprises avec une raison sociale similaire
Vous pourrez alors sauvegarder dans le fichier base de données l'entreprise souhaitée en appuyant sur le bouton sauvegarder correspondant.
Vous serez redirigé vers l'url "http://127.0.0.1:8000/save" ou vous pourrez voir la raison sociale, le siret, le siren et l'adresse de l'entreprise séléctionnée et sauvegardé en base de données.

# 2. Utilisation de l'api de l'URSAFF

Vous pouvez y accéder par l'url "http://127.0.0.1:8000/salary". En entrant le salaire pour lequel vous souhaitez faire vos estimations puis en appuyant sur le bouton Valider, vous serez redirigé vers une page à l'url vous indiquant une estimation de différentes valeurs comme le salaire net avant impot pour un salarié selon le contrat ou la gratificatio minimale d'un stagiaire dans l'entreprise en question. Je me suis rendu compte plus tard que je n'ai pas effectué les bons calculs mais étant seul sur le projet et en semaine d'entreprise, je n'ai pas eu réellement le temps de corriger cela.

# 3. Ouverture d'api 

Vous pourrez retrouver les différentes url permettant d'executer les différentes fonctions dans le code du fichier "ApiEntrepriseController.php" situé dans le dossier "src/Controller"
les requêtes a dispositions sont:
- http://127.0.0.1:8000/getAllCompanies  => permet de récuperer la liste des entreprises dans la base de données
- http://127.0.0.1:8000/getCompany/{siren} avec {siren} etant un paramètre a fournir lors de l'appel => permet de récuperer l'entreprise au siren correspondant dans la base de données
- http://127.0.0.1:8000/createCompany  => permet de créer une entreprise dans la base de données
- http://127.0.0.1:8000/patchCompany/{siren} avec {siren} etant un paramètre a fournir lors de l'appel => permet de modifier une entreprise dans la base de données
- http://127.0.0.1:8000/deleteCompany/{siren} avec {siren} etant un paramètre a fournir lors de l'appel => permet de supprimer une entreprise dans la base de données