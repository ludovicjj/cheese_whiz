<?php

namespace App\Controller;

use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login', methods: ['POST'])]
    public function login(IriConverterInterface $iriConverter)
    {
        if (!$this->isGranted("IS_AUTHENTICATED_FULLY")) {
            return $this->json([
                'error' => 'Invalid login request: check that the Content-Type header is "application/json".'
            ], 400);
        }

        return new Response(
            null,
            Response::HTTP_NO_CONTENT,
            ['Location' => $iriConverter->getIriFromItem($this->getUser())]
        );
    }

    #[Route(path: '/logout', name: 'app_logout', methods: ['POST'])]
    public function logout()
    {
        throw new \Exception('should not be reached');
    }
}