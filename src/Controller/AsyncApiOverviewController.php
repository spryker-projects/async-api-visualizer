<?php

namespace App\Controller;

use App\AsyncApi\AsyncApi;
use App\AsyncApi\AsyncApiRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AsyncApiOverviewController extends AbstractController
{
    #[Route('/async-api')]
    public function overview(AsyncApi $asyncApi, AsyncApiRenderer $asyncApiRenderer): Response
    {
        return $this->render('AsyncApi/overview.html.twig', [
            'graph' => $asyncApiRenderer->createImageHtml($asyncApi->collect())
        ]);
    }
}