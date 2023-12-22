<?php
// tests/Controller/ApiEntrepriseControllerTest.php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiEntrepriseControllerTest extends WebTestCase
{
    public function testGetAllCompanies()
    {
        // Create mock data to simulate our file which stores data
        $mockedData = [
            [
                'raisonSociale' => 'Company 1',
                'siren' => '123456789',
                'siret' => '12345678900001',
                'adresse' => '123 Street, City',
            ],
        ];

        // Create a temporary test file and write the mocked data
        $testFilePath = sys_get_temp_dir() . '/companies_test.txt';
        file_put_contents($testFilePath, $this->formatTestData($mockedData));

        $client = static::createClient();

        // Test if another method than GET is used and if it returns a Method Not Allowed
        $client->request('POST', '/getAllCompanies', ['format' => 'json', 'testFilePath' => $testFilePath]);
        $this->assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $client->getResponse()->getStatusCode());

        // Test if another parameter than json or csv is used for "format" and if it returns a Not Acceptable
        $client->request('GET', '/getAllCompanies', ['format' => 'blabla', 'testFilePath' => $testFilePath]);
        $this->assertEquals(Response::HTTP_NOT_ACCEPTABLE, $client->getResponse()->getStatusCode());

        // Test if the method returns a 200 
        $client->request('GET', '/getAllCompanies', ['format' => 'json', 'testFilePath' => $testFilePath]);
        $this->assertEquals(Response::HTTP_OK, $client->getResponse()->getStatusCode());

        //Delete the temporary test file
        unlink($testFilePath);
    }

    private function formatTestData(array $data)
    {
        // Format the test data as needed for our tests
        $formattedData = [];
        foreach ($data as $company) {
            $formattedData[] = implode(',', $company);
        }

        return implode("\n", $formattedData);
    }
}
