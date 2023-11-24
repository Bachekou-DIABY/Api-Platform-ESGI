<?php

// src/Controller/ApiEntrepriseController.php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApiEntrepriseController extends AbstractController
{
    public $username = 'username';
    public $password = 'password';

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
        $validation = $this->validateJson($decodedData);
        if ($validation->getStatusCode() == Response::HTTP_BAD_REQUEST) {
            return new Response('Le formulaire transmis au format JSON est manquant ou incomplet', Response::HTTP_BAD_REQUEST);
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
            ], new Response(null, Response::HTTP_CREATED));
        } else {
            // Si la création échoue, renvoyer le template d'échec avec le bon code de statut
            return $this->render('saveFailed.html.twig', ['message' => $result['message']], new Response(null, Response::HTTP_CONFLICT));
        }
    }

    #[Route('/patch/{siren}', name: 'patchCompany')]
    public function patchCompany(Request $request, $siren)
    {
        if (!$request->isMethod('PATCH')) {
            return new Response('Méthode non autorisée : Seul une requête avec la méthode PATCH est possible pour cette route', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (strpos($authorizationHeader, 'Basic') === 0) {
            $base64Credentials = substr($authorizationHeader, 6);
            $credentials = base64_decode($base64Credentials);
            list($enteredUsername, $enteredPassword) = explode(':', $credentials);

            // Vérifiez les informations d'identification
            if ($enteredUsername === $this->username && $enteredPassword === $this->password) {
                $data = json_decode($request->getContent(), true);
                $validation = $this->validateJsonPatch($data);
                if ($validation->getStatusCode() == Response::HTTP_BAD_REQUEST) {
                    return new Response('Le formulaire transmis au format JSON est manquant ou incomplet', Response::HTTP_BAD_REQUEST);
                }

                $num = $data['adresse']['Num'] ?? null;
                $voie = $data['adresse']['Voie'] ?? null;
                $codePostal = $data['adresse']['Code_postal'] ?? null;
                $ville = $data['adresse']['Ville'] ?? null;
                $latitude = $data['adresse']['GPS']['Latitude'] ?? null;
                $longitude = $data['adresse']['GPS']['Longitude'] ?? null;
                $adresse = "$num $voie $codePostal $ville $latitude $longitude" ?? null;
                if ($data === null) {
                    return new Response('Le contenu de la requête JSON est invalide ou manquant', Response::HTTP_BAD_REQUEST);
                } else {
                    $companies = $this->getcompanies();
                    $company = $this->findEntrepriseBySiren($companies, $siren);

                    if ($company === null) {
                        return new Response('Aucune entreprise avec ce SIREN n\'à été retrouvée', Response::HTTP_NOT_FOUND);
                    } else {
                        $result = $this->modifyCompany($data);
                        // Utiliser le code de statut HTTP et le message dans la réponse
                        $response = new Response($result['message'], $result['status_code']);
                        $response->headers->set('Content-Type', 'text/plain');
                        $companies = $this->getcompanies();
                        $company = $this->findEntrepriseBySiren($companies, $siren);

                        // Vérifier le statut de la réponse
                        if ($result['status_code'] === Response::HTTP_OK) {
                            // Si la création réussit, renvoyer le template de succès
                            return $this->render('saveSuccess.html.twig', [
                                'raisonSociale' => $company['raisonSociale'] ?? null,
                                'siren' => $company['siren'] ?? null,
                                'siret' => $company['siret'] ?? null,
                                'adresse' => $adresse ?? null,
                            ], new Response(null, Response::HTTP_OK));
                        } else {
                            // Si la création échoue, renvoyer le template d'échec avec le bon code de statut
                            return $this->render('saveFailed.html.twig', ['message' => $result['message']], new Response(null, Response::HTTP_NOT_FOUND));
                        }
                    }
                }
            } else {
                // Les informations d'identification sont incorrectes
                header('HTTP/1.0 401 Unauthorized');
                exit('Les informations d\'identifications fournies sont incorrectes');
            }
        } else {
            // Le header HTTP_AUTHORIZATION est absent
            header('HTTP/1.0 401 Unauthorized');
            exit('Il est nécessaire de se connecter pour utiliser cette fonction');
        }
    }

    #[Route('/delete/{siren}', name: 'deleteCompany')]
    public function deleteCompany(Request $request, $siren)
    {
        // Vérifier si le verbe HTTP utilisé est DELETE
        if (!$request->isMethod('DELETE')) {
            return new Response('Méthode non autorisée : Seul une requête avec la méthode DELETE est possible pour cette route', Response::HTTP_METHOD_NOT_ALLOWED);
        }
        // Vérifier l'authentification (implémenter votre propre logique d'authentification ici)
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (strpos($authorizationHeader, 'Basic') === 0) {
            $base64Credentials = substr($authorizationHeader, 6);
            $credentials = base64_decode($base64Credentials);
            list($enteredUsername, $enteredPassword) = explode(':', $credentials);

            // Vérifiez les informations d'identification
            if ($enteredUsername === $this->username && $enteredPassword === $this->password) {
                // Supprimer l'entreprise et son fichier
                $result = $this->deleteCompanyBySiren($siren);
            }else{
                // Les informations d'identification sont incorrectes
                header('HTTP/1.0 401 Unauthorized');
                exit('Les informations d\'identifications fournies sont incorrectes');
    
            }

            // Retourner la réponse JSON en fonction du résultat
            if ($result['status_code'] == Response::HTTP_OK) {
                return new Response($result['message'], $result['status_code']);
            } else {
                return new Response($result['message'], $result['status_code']);
            }
        } else {
            // Le header HTTP_AUTHORIZATION est absent
            header('HTTP/1.0 401 Unauthorized');
            exit('Il est nécessaire de se connecter pour utiliser cette fonction');
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

    public function validateJson($decodedData)
    {
        if (
            !$decodedData ||
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
        ) {
            return new Response('Le formulaire transmis au format JSON est manquant ou incomplet', Response::HTTP_BAD_REQUEST);
        } else {
            foreach ($decodedData as $key => $value) {
                if ($key !== 'adresse' && gettype($value) !== 'string') {
                    return new Response("Le paramètre \"$key\" doit être une chaine de caractères", Response::HTTP_BAD_REQUEST);
                };
            }
            foreach ($decodedData['adresse'] as $key => $value) {
                if ($key !== 'GPS' && gettype($value) !== 'string') {
                    return new Response("Le paramètre \"$key\" doit être une chaine de caractères", Response::HTTP_BAD_REQUEST);
                };
            }
            foreach ($decodedData['adresse']['GPS'] as $key => $value) {
                if (gettype($value) !== 'string') {
                    return new Response("Le paramètre \"$key\" doit être une chaine de caractères", Response::HTTP_BAD_REQUEST);
                };
            }
        }
        return new Response(Response::HTTP_OK);
    }

    public function validateJsonPatch($decodedData)
    {
        if (!$decodedData) {
            return new Response('Le formulaire transmis au format JSON est manquant ou incomplet', Response::HTTP_BAD_REQUEST);
        } else {
            foreach ($decodedData as $key => $value) {
                if ($key !== 'adresse' && gettype($value) !== 'string') {
                    return new Response("Le paramètre \"$key\" doit être une chaine de caractères", Response::HTTP_BAD_REQUEST);
                };
            }
            if (isset($decodedData['adresse'])) {
                foreach ($decodedData['adresse'] as $key => $value) {
                    if ($key !== 'GPS' && gettype($value) !== 'string') {
                        return new Response("Le paramètre \"$key\" doit être une chaine de caractères", Response::HTTP_BAD_REQUEST);
                    };
                }
                if (isset($decodedData['adresse']['GPS'])) {
                    foreach ($decodedData['adresse']['GPS'] as $key => $value) {
                        if (gettype($value) !== 'string') {
                            return new Response("Le paramètre \"$key\" doit être une chaine de caractères", Response::HTTP_BAD_REQUEST);
                        };
                    }
                }
            }
        }
        return new Response(Response::HTTP_OK);
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

    private function modifyCompany(array $company)
    {
        $siren = $company['siren'];
        $file = '../src/Database/companies.txt';
        $current = file_get_contents($file);

        if (!empty($current)) {
            $existingCompanies = explode("\n", $current);

            foreach ($existingCompanies as $index => $existingCompany) {
                $existingCompanyData = explode(',', $existingCompany);

                $currentSiren = $existingCompanyData[1];

                if ($currentSiren === $siren) {
                    // Mettre à jour les champs nécessaires

                    $existingCompanyData[0] = $company['raisonSociale'] ?? $existingCompanyData[0];
                    $existingCompanyData[1] = $company['siren'] ?? $existingCompanyData[1];
                    $existingCompanyData[2] = $company['siret'] ?? $existingCompanyData[2];
                    if (isset($company['adresse'])) {
                        $existingCompanyData[3] = implode(' ', $this->flattenArray($company['adresse']));
                    } else {
                        $existingCompanyData[3] = $existingCompanyData[3];
                    }

                    // Reconstruire la ligne mise à jour
                    $existingCompanies[$index] = implode(',', $existingCompanyData);

                    // Écrire le nouveau contenu dans le fichier
                    file_put_contents($file, implode("\n", $existingCompanies));

                    return [
                        'status_code' => Response::HTTP_OK,
                        'message' => 'Entreprise mise à jour avec succès'
                    ];
                }
            }
        }

        return [
            'status_code' => Response::HTTP_NOT_FOUND,
            'message' => 'Aucune entreprise avec ce SIREN'
        ];
    }

    // Fonction pour supprimer l'entreprise par SIREN
    private function deleteCompanyBySiren($siren)
    {
        $file = '../src/Database/companies.txt';
        $current = file_get_contents($file);
    
        if (!empty($current)) {
            $existingCompanies = explode("\n", $current);
            $updatedCompanies = [];
    
            $companyFound = false;
    
            foreach ($existingCompanies as $existingCompany) {
                $existingCompanyData = explode(',', $existingCompany);
                if (isset($existingCompanyData[1]) && $existingCompanyData[1] === $siren) {
                    // Entreprise trouvée, ne pas l'ajouter à la liste mise à jour
                    $companyFound = true;
                } else {
                    $updatedCompanies[] = $existingCompany;
                }
            }
    
            if ($companyFound) {
                // Mettre à jour le fichier avec les entreprises restantes
                file_put_contents($file, implode("\n", $updatedCompanies));
                return [
                    'status_code' => Response::HTTP_OK,
                    'message' => 'Entreprise supprimée'
                ];
            } else {
                return [
                    'status_code' => Response::HTTP_NOT_FOUND,
                    'message' => 'Aucune entreprise avec ce SIREN'
                ];
            }
        } else {
            return [
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'Aucune entreprise enregistrée'
            ];
        }
    }

    function flattenArray($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Appel récursif si la valeur est un tableau
                $result = array_merge($result, $this->flattenArray($value));
            } else {
                $result[] = $value;
            }
        }
        return $result;
    }
}
