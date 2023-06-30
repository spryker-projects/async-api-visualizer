<?php

namespace App\AsyncApi;

use Graphp\Graph\Graph;
use Graphp\GraphViz\GraphViz;

class AsyncApiRenderer
{
    /**
     * @param Graph $graph
     * @param GraphViz $graphViz
     */
    public function __construct(
        protected Graph $graph,
        protected GraphViz $graphViz
    )
    {
    }

    /**
     * Output will be like:
     * - "package that published a message" -> "message that gets published" -> "channel where the message is published to"
     * - "channel where a message is consumed from" -> "message that should be consumed" -> "package that consumes the message"
     *
     * @TODO
     * - Add message details like required fields for both send and receive
     * - Mark consuming line red when the publisher doesn't send all required fields
     *
     * @param array $asyncApiDetails
     *
     * @return string
     */
    public function createImageHtml(array $asyncApiDetails): string
    {
        foreach ($asyncApiDetails as $channelName => $messages) {
            $channelVertex = $this->graph->createVertex(['id' => $channelName]);
            $channelVertex->setAttribute('graphviz.shape', 'cylinder');
            $channelVertex->setAttribute('graphviz.color', 'grey');
            $channelVertex->setAttribute('graphviz.label_url', sprintf('/channels/%s', $channelName));

            foreach ($messages as $messageName => $messageDetails) {
                // This is for displaying who is subscribed so who consumes this message.
                if (isset($messageDetails['subscriber'])) {
                    $messageVertex = $this->graph->createVertex(['id' => $messageName . PHP_EOL . '(subscribe)']);
                    $messageVertex->setAttribute('graphviz.shape', 'tab');
                    $messageVertex->setAttribute('graphviz.label', $messageName);
                    $messageVertex->setAttribute('graphviz.label_url', sprintf('/messages/%s', $messageName));

                    // Connect channel with message
                    $this->graph->createEdgeDirected($channelVertex, $messageVertex);

                    foreach ($messageDetails['subscriber'] as $subscriberPackage => $sends) {
                        // Create subscriber vertex
                        $subscriberVertex = $this->graph->createVertex(['id' => $subscriberPackage]);

                        // Connect message with subscriber
                        $this->graph->createEdgeDirected($messageVertex, $subscriberVertex);
                    }
                }

                // This is for displaying who is publisher so who produces this message.
                if (isset($messageDetails['publisher'])) {
                    $messageVertex = $this->graph->createVertex(['id' => $messageName . PHP_EOL . '(publish)']);
                    $messageVertex->setAttribute('graphviz.shape', 'tab');
                    $messageVertex->setAttribute('graphviz.label', $messageName);
                    $messageVertex->setAttribute('graphviz.label_url', sprintf('/messages/%s', $messageName));

                    // Connect message with channel
                    $this->graph->createEdgeDirected($messageVertex, $channelVertex);

                    foreach ($messageDetails['publisher'] as $publisherPackage => $sends) {
                        // Create publisher vertex
                        $publisherVertex = $this->graph->createVertex(['id' => $publisherPackage]);

                        // Connect publisher with message
                        $this->graph->createEdgeDirected($publisherVertex, $messageVertex);
                    }
                }
            }
        }

        return $this->graphViz->createImageHtml($this->graph);
    }
}