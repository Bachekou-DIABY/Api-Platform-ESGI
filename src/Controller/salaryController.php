<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Form\companyFormType;
use App\Form\salaryFormType;

class salaryController extends AbstractController
{

    public function __construct(
        private HttpClientInterface $client,
        private RequestStack $requestStack,
        ) {
        $this->client = $client;
    }

    #[Route('/salary', name: 'salary')]
    public function index(Request $request): Response
    {
        $salaryForm = $this->createForm(salaryFormType::class);

        $salaryForm->handleRequest($request);

        //1.Calcul du salaire net avant impôt pour un salarié en CDI avec la cotisation salariale et le coût employeur

        if ($salaryForm->isSubmitted() && $salaryForm->isValid()) {
            $input = $salaryForm->get('input')->getData();
            $data1 = [
                'situation' => [
                    "salarié . contrat . salaire brut" =>
                        [
                        "valeur" => $input,
                        "unité" => "€ / mois"
                ],
                    'salarié . contrat' => "'stage'"
                ],
                'expressions' => [
                    "salarié . coût total employeur",
                    "salarié . cotisations",
                    "salarié . rémunération . net . à payer avant impôt"                
                    ]
            ];

            //1.Execution de la requete

            $response1 = $this->client->request('POST', 'https://mon-entreprise.urssaf.fr/api/v1/evaluate',[
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json'
                ],
                'json' => $data1
                
            ]); 

            //1.Mise en forme de la réponse

            $result1 = $response1->getContent();
            $decodedResult = json_decode($result1,true);
            $salaireCdi = [
                'CDI_cout_employeur' => $decodedResult['evaluate'][0]['nodeValue'] ?? null,
                'CDI_cotisations' => $decodedResult['evaluate'][1]['nodeValue'] ?? null,
                'CDI_salaire_net' => $decodedResult['evaluate'][2]['nodeValue'] ?? null,
            ];


            //2.Calcul de la gratification minimale d'un stagiaire

            $data2 = [
                'situation' => [
                    'salarié . contrat' => "'stage'"
                ],
                'expressions' => [
                    'salarié . contrat . stage . gratification minimale'
                ]
            ];

            //2.Execution de la requete

            $response2 = $this->client->request('POST', 'https://mon-entreprise.urssaf.fr/api/v1/evaluate',[
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json'
                ],
                'json' => $data2
                
            ]); 

            // 2. Mise en forme de la réponse

            $result2 = $response2->getContent();
            $decodedResult = json_decode($result2,true);
            $salaireStage = $decodedResult['evaluate'][0]['nodeValue'];

            //3.Calcul du salaire net avant impôt pour un.e alternant.e (contrat « alternance ») avec la cotisation salariale et le coût employeur

            $data3 = [
                'situation' => [
                    "salarié . contrat . salaire brut" =>
                    [
                    "valeur" => $input,
                    "unité" => "€ / mois"
            ],
                    'salarié . contrat' => "'apprentissage'"
                ],
                'expressions' => [
                    "salarié . coût total employeur",
                    "salarié . cotisations",
                    "salarié . rémunération . net . à payer avant impôt"                
                    ]
            ];

            //3.Execution de la requete

            $response3 = $this->client->request('POST', 'https://mon-entreprise.urssaf.fr/api/v1/evaluate',[
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json'
                ],
                'json' => $data3
                
            ]); 

            //3. Mise en forme de la réponse

            $result3 = $response3->getContent();
            $decodedResult = json_decode($result3,true);
            $salaireAlternance  = [
                'Alt_cout_employeur' => $decodedResult['evaluate'][0]['nodeValue'] ?? null,
                'Alt_cotisations' => $decodedResult['evaluate'][1]['nodeValue'] ?? null,
                'Alt_salaire_net' => $decodedResult['evaluate'][2]['nodeValue'] ?? null,
            ];

            //4.Calcul du salaire net avant impôt pour un « cdd » avec la cotisation salariale, le coût employeur et l’indemnité de fin de contrat

            $data4 = [
                'situation' => [
                    "salarié . contrat . salaire brut" =>
                    [
                    "valeur" => $input,
                    "unité" => "€ / mois"
            ],
                    'salarié . contrat' => "'CDD'"
                ],
                'expressions' => [
                    "salarié . coût total employeur",
                    "salarié . cotisations",
                    "salarié . rémunération . net . à payer avant impôt",
                    "salarié . rémunération . indemnités CDD . fin de contrat"               
                    ]
            ];

            //4.Execution de la requete

            $response4 = $this->client->request('POST', 'https://mon-entreprise.urssaf.fr/api/v1/evaluate',[
                'headers' => [
                    'accept' => 'application/json',
                    'content-type' => 'application/json'
                ],
                'json' => $data4
                
            ]); 

            //4.Mise en forme de la réponse

            $result4 = $response4->getContent();
            $decodedResult = json_decode($result4,true);
            $salaireCDD  = [
                'CDD_cout_employeur' => $decodedResult['evaluate'][0]['nodeValue'] ?? null,
                'CDD_cotisations' => $decodedResult['evaluate'][1]['nodeValue'] ?? null,
                'CDD_salaire_net' => $decodedResult['evaluate'][2]['nodeValue'] ?? null,
                'CDD_indemnités' => $decodedResult['evaluate'][3]['nodeValue'] ?? null,

            ];

            return $this->render('showSalary.html.twig', [
                'Brut' => $input,
                'CDI_cout_employeur' => $salaireCdi['CDI_cout_employeur'],
                'CDI_cotisations' => $salaireCdi['CDI_cotisations'],
                'CDI_salaire_net' => $salaireCdi['CDI_salaire_net'],
                'Alt_cout_employeur' => $salaireAlternance['Alt_cout_employeur'],
                'Alt_cotisations' => $salaireAlternance['Alt_cotisations'],
                'Alt_salaire_net' => $salaireAlternance['Alt_salaire_net'],
                'salaireStage' => $salaireStage,
                'CDD_cout_employeur' => $salaireCDD['CDD_cout_employeur'],
                'CDD_cotisations' => $salaireCDD['CDD_cotisations'],
                'CDD_salaire_net' => $salaireCDD['CDD_salaire_net'],
                'CDD_indemnités' => $salaireCDD['CDD_indemnités']
            ]);   
    
        }
        return $this->render('salaryEstimations.html.twig', [
            'salaryForm' => $salaryForm
        ]);
    }
}