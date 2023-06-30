<?php

namespace App\Controller;

use App\AsyncApi\AsyncApi;
use App\AsyncApi\AsyncApiRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AsyncApiPackagesController extends AbstractController
{
    #[Route('/packages')]
    public function packages(AsyncApi $asyncApi): Response
    {
        return $this->render('AsyncApi/packages.html.twig', [
            'packages' => $asyncApi->getPackages(),
        ]);
    }

    #[Route('/packages/{packageName}')]
    public function package(string $packageName, AsyncApi $asyncApi, AsyncApiRenderer $asyncApiRenderer): Response
    {
        return $this->render('AsyncApi/package.html.twig', [
            'graph' => $asyncApiRenderer->createImageHtml(
                $asyncApi->getPackage($packageName)
            ),
            'package' => str_replace('_', '/',$packageName),
        ]);
    }
}