<?php

declare(strict_types=1);

namespace App\Controller;

use Nowo\Openpay\Client;
use Nowo\Openpay\Country;
use Nowo\Openpay\Credentials;
use Nowo\Openpay\Http\HttpClient;
use Nowo\Openpay\Http\HttpResponse;
use Nowo\Openpay\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentController extends AbstractController
{
    private function session(?string $publicIp = null): Session
    {
        $merchantId = (string) ($_ENV['OPENPAY_MERCHANT_ID'] ?? '');
        $privateKey = (string) ($_ENV['OPENPAY_PRIVATE_KEY'] ?? '');
        $country = Country::tryFrom((string) ($_ENV['OPENPAY_COUNTRY'] ?? 'MX')) ?? Country::Mx;
        $sandbox = (($_ENV['OPENPAY_SANDBOX'] ?? '1') !== '0');
        // New Credentials per call: publicIp must not live in a shared service under FrankenPHP worker.
        $hasKeys = '' !== $merchantId && '' !== $privateKey;
        $credentials = new Credentials(
            $hasKeys ? $merchantId : 'demo_merchant',
            $hasKeys ? $privateKey : 'sk_demo',
            $country,
            $sandbox,
            $publicIp ?? '127.0.0.1',
        );

        if (!$hasKeys) {
            $http = new class implements HttpClient {
                public function request(string $method, string $url, ?string $jsonBody = null, array $headers = []): HttpResponse
                {
                    return new HttpResponse(200, json_encode([
                        'id' => 'cus_demo_'.substr(sha1($method.$url.(string) $jsonBody), 0, 8),
                        'name' => 'Demo customer',
                        'email' => 'demo@example.test',
                        'mock' => true,
                        'url' => $url,
                    ], \JSON_THROW_ON_ERROR));
                }
            };

            return new Session($credentials, $http);
        }

        return new Session($credentials);
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function home(): Response
    {
        $usingMock = ($_ENV['OPENPAY_MERCHANT_ID'] ?? '') === '' || ($_ENV['OPENPAY_PRIVATE_KEY'] ?? '') === '';

        return $this->render('payment/home.html.twig', [
            'usingMock' => $usingMock,
        ]);
    }

    #[Route('/customers', name: 'customers_create', methods: ['POST'])]
    public function createCustomer(Request $request): Response
    {
        $name = (string) $request->request->get('name', 'Demo');
        $email = (string) $request->request->get('email', 'demo@example.test');

        $customer = $this->session($request->getClientIp())->run(static function (Client $client) use ($name, $email): array {
            return $client->customers->create([
                'name' => $name,
                'email' => $email,
            ]);
        });

        return $this->render('payment/result.html.twig', [
            'title' => 'Customer created',
            'payload' => $customer,
        ]);
    }
}
