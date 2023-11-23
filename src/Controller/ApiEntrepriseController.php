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
            return new Response('Méthode non autorisée : Seul une requête avec la méthode GET est possible pour cette route', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        //On récupère la valeur du paramètre "format" de la requête et on assigne 'json' par défaut si le paramètre n'est pas indiqué
        $format = $request->query->get('format', 'json');
        if ($format != 'json' && $format != 'csv') {
            return new Response('Format non pris en compte : Seul les formats "json" et "csv" sont autorisés', Response::HTTP_NOT_ACCEPTABLE);
        }

        $companies = $this->getcompanies();

        // Vérifier si aucune entreprise n'est enregistrée
        if (empty($companies)) {
            return new Response('Aucune entreprise enregistrée', Response::HTTP_OK);
        }

        // Récupérer le format demandé depuis le paramètre de requête "format"
        if ($format == 'json' || $format == 'html') {
            return $this->json($companies, Response::HTTP_OK);
        } else if ($format == 'csv') {
            $csvData = $this->generateCSV($companies);
            $response = new Response($csvData, Response::HTTP_OK);
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="companies.csv"');
            return $response;
        }
    }

    #[Route('/getCompany/{siren}', name: 'getCompany')]
    public function getCompanyBySiren(Request $request, $siren)
    {
        // Vérifier si le verbe HTTP utilisé est GET
        if (!$request->isMethod('GET')) {
            return new Response('Méthode non autorisée : Seul une requête avec la méthode GET est possible pour cette route', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        // Récupérer les entreprises depuis le fichier ou autre source
        $companies = $this->getcompanies();

        // Rechercher l'entreprise par siren
        $company = $this->findEntrepriseBySiren($companies, $siren);
        // Vérifier si aucune entreprise n'a été trouvée
        if (!$company) {
            return new Response('Aucune entreprise avec ce SIREN n\'à été retrouvée', Response::HTTP_NOT_FOUND);
        }

        // Répondre avec un statut 201 Created
        return $this->json($company, Response::HTTP_OK);
    }


    #[Route('/create', name: 'createCompany')]
    public function createCompany(Request $request)
    {
        // Vérifier si le verbe HTTP utilisé est POST
        if (!$request->isMethod('POST')) {
            return new Response('Méthode non autorisée : Seul une requête avec la méthode POST est possible pour cette route', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        // Récupérer le contenu JSON de la requête
        $data = $request->getContent();

        // Désérialiser le JSON en un tableau associatif
        $decodedData = json_decode($data, true);

        // Valider les données JSON
        if(!$decodedData ||
        !isset($decodedData['siren']) ||
        !isset($decodedData['siret']) ||
        !isset($decodedData['raisonSociale']) ||
        !isset($decodedData['adresse']) || 
        !isset($decodedData['adresse']['Num']) ||
        !isset($decodedData['adresse']['Voie']) ||
        !isset($decodedData['adresse']['Code_postal']) ||
        !isset($decodedData['adresse']['Ville']) ||
        !isset($decodedData['adresse']['GPS']['Latitude']) ||
        !isset($decodedData['adresse']['GPS']['Longitude'])
        ){
            return new Response('Le formulaire transmis au format JSON est manquant ou incomplet', Response::HTTP_BAD_REQUEST);
        
        }else{
            foreach ($decodedData as $key => $value) {
                if($key !== 'adresse' && gettype($value) !== 'string' ){
                    return new Response("Le paramètre \"$key\" doit être une chaine de caractères", Response::HTTP_BAD_REQUEST);
                };
            }
            foreach ($decodedData['adresse'] as $key => $value) {
                if($key !== 'GPS' && gettype($value) !== 'string'){
                    return new Response("Le paramètre \"$key\" doit être une chaine de caractères", Response::HTTP_BAD_REQUEST);
                };
            }
            foreach ($decodedData['adresse']['GPS'] as $key => $value) {
                if(gettype($value) !== 'string'){
                    return new Response("Le paramètre \"$key\" doit être une chaine de caractères", Response::HTTP_BAD_REQUEST);
                };
            }
        }

        // Créer l'entreprise avec les données fournies
        $num = $decodedData['adresse']['Num'];
        $voie = $decodedData['adresse']['Voie'];
        $codePostal = $decodedData['adresse']['Code_postal'];
        $ville = $decodedData['adresse']['Ville'];
        $latitude = $decodedData['adresse']['GPS']['Latitude'];
        $longitude = $decodedData['adresse']['GPS']['Longitude'];
        $adresse = "$num $voie $codePostal $ville $latitude $longitude";
        
        $company = [
            'raisonSociale' => $decodedData['raisonSociale'],
            'siren' => $decodedData['siren'],
            'siret' => $decodedData['siret'],
            'adresse' => $adresse
        ];
        // Ajouter l'entreprise au fichier ou à la source de données
        $result = $this->addCompany($company);

        // Utiliser le code de statut HTTP et le message dans la réponse
        $response = new Response($result['message'], $result['status_code']);
        $response->headers->set('Content-Type', 'text/plain');
    
        // Vérifier le statut de la réponse
        if ($result['status_code'] === Response::HTTP_CREATED) {
            // Si la création réussit, renvoyer le template de succès
            return $this->render('saveSuccess.html.twig', [
                'raisonSociale' => $company['raisonSociale'],
                'siren' => $company['siren'],
                'siret' => $company['siret'],
                'adresse' => $adresse,
            ], new Response (null,Response::HTTP_CREATED));
        } else {
            // Si la création échoue, renvoyer le template d'échec avec le bon code de statut
            return $this->render('saveFailed.html.twig', ['message' => $result['message']], new Response (null,Response::HTTP_CONFLICT));
        }
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

    private function getcompanies()
    {
        $filePath = $this->getParameter('kernel.project_dir') . '/src/Database/companies.txt'; 
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $companies = [];
        foreach ($lines as $line) {
            $fields = explode(',', $line);
            $company = [
                'raisonSociale' => $fields[0],
                'siren' => $fields[1],
                'siret' => $fields[2],
                'adresse' => implode(' ', array_slice($fields, 3)),
            ];
            $companies[] = $company;
        }
        return $companies;
    }


    private function generateCSV(array $companies)
    {
        $csvData = "Raison Sociale,Siret,Siren,adresse\n";
        foreach ($companies as $company) {
            $csvData .=
                '"' . $company['raisonSociale'] . '",' .
                '"' . $company['siret'] . '",' .
                '"' . $company['siren'] . '",' .
                '"' . $company['adresse'] . '"' . "\n";
        }
        return $csvData;
    }

    private function addCompany(array $company)
    {
        $raisonSociale = $company['raisonSociale'];
        $siren = $company['siren'];
        $siret = $company['siret'];
        $adresse = $company['adresse'];
        $file = '../src/Database/companies.txt';
        $current = file_get_contents($file);
        if (!empty($current)) {
            $existingCompanies = explode("\n", $current);

            foreach ($existingCompanies as $existingCompany) {
                $existingCompanyData = explode(',', $existingCompany);
                if (isset($existingCompanyData[1]) && $existingCompanyData[1] === $company['siren']) {
                    return [
                        'status_code' => Response::HTTP_CONFLICT,
                        'message' => 'Erreur : Une entreprise avec ce SIREN existe déjà.'
                    ];
                }
            }
        }
        $company = "$raisonSociale,$siren,$siret,$adresse";
        $current .= $company;
        $current .= "\n";
        file_put_contents($file, $current);
        return [
            'status_code' => Response::HTTP_CREATED,
            'message' => 'Entreprise créée avec succès'
        ];
    }
}
