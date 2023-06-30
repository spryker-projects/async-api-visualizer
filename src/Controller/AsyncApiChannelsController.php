<?php

namespace App\Controller;

use App\AsyncApi\AsyncApi;
use App\AsyncApi\AsyncApiRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AsyncApiChannelsController extends AbstractController
{
    #[Route('/channels')]
    public function channels(AsyncApi $asyncApi): Response
    {
        return $this->render('AsyncApi/channels.html.twig', [
            'channels' => $asyncApi->getChannelNames(),
        ]);
    }

    #[Route('/channels/{channelName}')]
    public function channel(string $channelName, AsyncApi $asyncApi, AsyncApiRenderer $asyncApiRenderer): Response
    {
        return $this->render('AsyncApi/channel.html.twig', [
            'graph' => $asyncApiRenderer->createImageHtml(
                $asyncApi->getChannelDetails($channelName)
            ),
            'channel' => $channelName,
        ]);
    }
}