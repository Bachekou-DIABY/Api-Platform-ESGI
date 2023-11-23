<?php

// src/Controller/ApiEntrepriseController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiEntrepriseController extends AbstractController
{

    #[Route('/getAllCompanies', name: 'getCompanyList')]
    public function getAllCompanies(Request $request)
    {
        // Vérifier si le verbe HTTP utilisé est GET
        if (!$request->isMethod('GET')) {
            return new Response('Méthode non autorisée', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $format = $request->query->get('format', 'html');
        if($format != 'json' && $format != 'csv' && $format != 'html'){
            return new Response('Format non pris en compte : Seul les formats "json" et "csv" sont autorisés', Response::HTTP_NOT_ACCEPTABLE);
        }

        $companies = $this->getcompanies();

        // Vérifier si aucune entreprise n'est enregistrée
        if (empty($companies)) {
            return new Response('Aucune entreprise enregistrée', Response::HTTP_OK);
        }

        // Récupérer le format demandé depuis le paramètre de requête
        if($format == 'json' || $format == 'html'){
            return $this->json($companies, Response::HTTP_OK);
        }
        else if($format == 'csv'){
            $csvData = $this->generateCSV($companies);
            $response = new Response($csvData, Response::HTTP_OK);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="companies.csv"');
            return $response;
        }
        
    }

    #[Route('/getCompany/{siren}', name: 'getCompany')]
    public function getCompanyBySiret(Request $request, $siren)
    {
        // Vérifier si le verbe HTTP utilisé est GET
        if (!$request->isMethod('GET')) {
            return new Response('Méthode non autorisée', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        // Récupérer les entreprises depuis le fichier ou autre source
        $companies = $this->getcompanies();

        // Rechercher l'entreprise par siren
        $company = $this->findEntrepriseBySiren($companies, $siren);

        // Vérifier si aucune entreprise n'a été trouvée
        if (!$company) {
            return new Response('Aucune entreprise avec ce SIREN', Response::HTTP_NOT_FOUND);
        }

        // Renvoyer les informations au format JSON
        return $this->json($company, Response::HTTP_OK);
    }

    private function findEntrepriseBySiren(array $companies, $siren)
    {
        foreach ($companies as $company) {
            if ($company['siren'] === $siren) {
                return $company;
            }
        }

        return null;
    }


    // Remplacez cette fonction par la logique pour récupérer les entreprises depuis votre source de données
    private function getcompanies()
    {
        // Adaptation pour lire les entreprises depuis un fichier texte
        $filePath = $this->getParameter('kernel.project_dir') . '/src/Database/companies.txt'; // Remplacez par le chemin de votre fichier
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $companies = [];
        foreach ($lines as $line) {
            // Divisez chaque ligne en fonction de l'espace
            $fields = explode(',', $line);

            // Organisez les informations en un tableau associatif
            $company = [
                'raisonSociale' => $fields[0],
                'siren' => $fields[1],
                'siret' => $fields[2],
                'adresse' => implode(' ', array_slice($fields, 3)),
            ];
            // Ajoutez l'entreprise à la liste
            $companies[] = $company;
        }
        return $companies;
    }


    private function generateCSV(array $companies)
    {
        // Entêtes CSV
        $csvData = "Raison Sociale,Siret,Siren,Adresse\n";
    
        foreach ($companies as $company) {
            // Ajoutez chaque ligne du CSV avec les informations appropriées
            $csvData .=
                '"' . $company['raisonSociale'] . '",' .
                '"' . $company['siret'] . '",' .
                '"' . $company['siren'] . '",' .
                '"' . $company['adresse'] . '"' . "\n";
        }
    
        return $csvData;
    }


}
