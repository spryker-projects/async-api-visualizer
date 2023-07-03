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
            'packages' => $asyncApi->getPackageNames(),
        ]);
    }

    #[Route('/packages/{packageName}/{withDetails}', name: 'package')]
    public function package(string $packageName, AsyncApi $asyncApi, AsyncApiRenderer $asyncApiRenderer, bool $withDetails = false): Response
    {
        $graph = $this->getGraph($packageName, $withDetails, $asyncApi, $asyncApiRenderer);

        return $this->render('AsyncApi/package.html.twig', [
            'graph' => $graph,
            'package' => str_replace('_', '/',$packageName),
            'packageUrl' => $packageName,
            'withDetails' => $withDetails,
        ]);
    }

    /**
     * @param string $packageName
     * @param bool $withDetails
     * @param AsyncApi $asyncApi
     * @param AsyncApiRenderer $asyncApiRenderer
     * @return string
     */
    protected function getGraph(string $packageName, bool $withDetails, AsyncApi $asyncApi, AsyncApiRenderer $asyncApiRenderer): string
    {
        if ($withDetails) {
            return $asyncApiRenderer->createImageHtmlWithMessageDetails(
                $asyncApi->getPackageDetails($packageName),
            );
        }

        return $asyncApiRenderer->createImageHtml(
            $asyncApi->getPackageDetails($packageName),
        );
    }
}