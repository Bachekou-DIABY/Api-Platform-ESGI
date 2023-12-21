<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Form\CompanyFormType;

class FindCompanyController extends AbstractController
{
    
    public function __construct(
        private HttpClientInterface $client,
        private RequestStack $requestStack,
    ) {
        $this->client = $client;
    }

    #[Route('/search', name: 'findCompany')]
    public function index(Request $request): Response
    {
        $session = $this->requestStack->getSession();

        // creates a task object and initializes some data for this example
        $form = $this->createForm(CompanyFormType::class);
        $form->handleRequest($request);

        $filteredCompanies = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $params = $form->get('param')->getData();
            $response = $this->client->request('GET', 'https://recherche-entreprises.api.gouv.fr/search', [
                'query' => [
                    'q'=> $params,
                ],
            ]);
            if ($response->getStatusCode() === 200) {
                $data = $response->getContent();
            
                $decodedData = json_decode($data, true);
                
                if (isset($decodedData['results']) && is_array($decodedData['results'])) {
                    foreach ($decodedData['results'] as $company) {
                        $filteredCompanies[] = [
                            'raisonSociale' => $company['nom_complet'] ?? null,
                            'siren' => $company['siren'] ?? null,
                            'siret' => $company['siege']['siret'] ?? null,
                            'adresse' => $company['siege']['adresse'] ?? null,
                        ];
                    }
                    $session->set('filtered_companies', $filteredCompanies);
                }
            
                return $this->redirectToRoute('showResults');
            }
        }

        return $this->render('findCompany.html.twig', [
            'form' => $form,
            'companies' => null,
        ]);
    }
}
