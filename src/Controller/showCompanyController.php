<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Form\SaveCompanyFormType;

class ShowCompanyController extends AbstractController
{

    public function __construct(
        private HttpClientInterface $client,
        private RequestStack $requestStack,
    ) {
        $this->client = $client;
    }

    #[Route('/show', name: 'showResults')]
    public function showCompanies(): Response
    {
        $session = $this->requestStack->getSession();
        $filteredCompanies = $session->get('filtered_companies', []);

        return $this->render('showCompany.html.twig', [
            'companies' => $filteredCompanies,
        ]);
    }

    #[Route('/save', name: 'saveResults')]
    public function saveResults(Request $request): Response
    {
        $session = $this->requestStack->getSession();
        $raisonSociale = $request->request->get('raisonSociale');
        $siren = $request->request->get('siren');
        $siret = $request->request->get('siret');
        $adresse = $request->request->get('adresse');
        $file = '../src/Database/companies.txt';
        $current = file_get_contents($file);
        if (!empty($current)) {
            $existingCompanies = explode("\n", $current);

            foreach ($existingCompanies as $existingCompany) {
                $existingCompanyData = explode(',', $existingCompany);
                if (isset($existingCompanyData[1]) && $existingCompanyData[1] === $siren) {
                    return $this->render('saveFailed.html.twig', [
                        'message' => 'Erreur : Une entreprise avec ce SIREN existe déjà.',
                    ]);
                }
            }
        }
        $company = "$raisonSociale,$siren,$siret,$adresse";
        $current = file_get_contents($file);
        $current .= $company;
        $current .= "\n";
        file_put_contents($file, $current);
        $session->set('company', $company);

        return $this->render('saveSuccess.html.twig', [
            'raisonSociale' => $raisonSociale,
            'siren' => $siren,
            'siret' => $siret,
            'adresse' => $adresse,
        ]);
    }
}
