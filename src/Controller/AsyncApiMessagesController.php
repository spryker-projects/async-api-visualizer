<?php

namespace App\Controller;

use App\AsyncApi\AsyncApi;
use App\AsyncApi\AsyncApiRenderer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AsyncApiMessagesController extends AbstractController
{
    #[Route('/messages')]
    public function messages(AsyncApi $asyncApi): Response
    {
        return $this->render('AsyncApi/messages.html.twig', [
            'publishedMessages' => $asyncApi->getMessages()['published'],
            'subscribedMessages' => $asyncApi->getMessages()['subscribed'],
        ]);
    }

    #[Route('/messages/{messageName}')]
    public function channel(string $messageName, AsyncApi $asyncApi, AsyncApiRenderer $asyncApiRenderer): Response
    {
        return $this->render('AsyncApi/message.html.twig', [
            'graph' => $asyncApiRenderer->createImageHtml(
                $asyncApi->getMessage($messageName)
            ),
            'message' => $messageName,
        ]);
    }
}