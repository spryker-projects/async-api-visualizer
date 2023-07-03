<?php

namespace App\AsyncApi;

use SprykerSdk\AsyncApi\AsyncApi\AsyncApiInterface;
use SprykerSdk\AsyncApi\AsyncApi\Channel\AsyncApiChannelInterface;
use SprykerSdk\AsyncApi\AsyncApi\Loader\AsyncApiLoader;
use SprykerSdk\AsyncApi\AsyncApi\Message\AsyncApiMessageInterface;
use SprykerSdk\AsyncApi\AsyncApi\Message\Attributes\AsyncApiMessageAttributeCollectionInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Finder\Finder;
use Symfony\Contracts\Cache\ItemInterface;

class AsyncApi
{
    /**
     * @var array<AsyncApiInterface>
     */
    protected $asyncApiDetails = [];

    /**
     * @param AsyncApiLoader $asyncApiLoader
     * @param Finder $finder
     * @param string $projectDir
     */
    public function __construct(
        protected AsyncApiLoader $asyncApiLoader,
        protected Finder $finder,
        protected FilesystemAdapter $cache,
        protected string $projectDir,
    )
    {
    }

    /**
     * @return array<AsyncApiInterface>
     */
    public function getAsyncApiDetails(): array
    {
        $asyncApiSchemaFiles = $this->getAsyncApiSchemaFiles();

        $asyncApiDetails = [];

        foreach ($asyncApiSchemaFiles as $asyncApiSchemaFile) {
            $asyncApi = $this->loadAsyncApiFromFile($asyncApiSchemaFile);
            $packageName = $this->getPackageNameFromFilePath($asyncApiSchemaFile);
            $asyncApiDetails = $this->addChannelsAndMessagesFromAsyncApi($asyncApi, $packageName, $asyncApiDetails);
        }

        return $asyncApiDetails;
    }

    /**
     * @return array<string, string>
     */
    public function getPackageNames(): array
    {
        $asyncApiSchemaFiles = $this->getAsyncApiSchemaFiles();

        $packages = [];

        foreach ($asyncApiSchemaFiles as $asyncApiSchemaFile) {
            $packageName = $this->getPackageNameFromFilePath($asyncApiSchemaFile);
            $packages[str_replace('/', '_', $packageName)] = $packageName;
        }

        asort($packages);

        return $packages;
    }

    /**
     * @param string $packageName
     *
     * @return array<string, string>
     */
    public function getPackageDetails(string $packageName): array
    {
        $packageName = str_replace('_', '/', $packageName);
        $asyncApiSchemaFiles = $this->getAsyncApiSchemaFiles($packageName);

        $asyncApiDetails = [];

        foreach ($asyncApiSchemaFiles as $asyncApiSchemaFile) {
            $asyncApi = $this->loadAsyncApiFromFile($asyncApiSchemaFile);
            $asyncApiDetails = $this->addChannelsAndMessagesFromAsyncApi($asyncApi, $packageName, $asyncApiDetails);
        }

        $asyncApiSchemaFiles = $this->getAsyncApiSchemaFiles();
        $otherAsyncApiDetails = [];

        foreach ($asyncApiSchemaFiles as $asyncApiSchemaFile) {
            $asyncApi = $this->loadAsyncApiFromFile($asyncApiSchemaFile);
            $packageName = $this->getPackageNameFromFilePath($asyncApiSchemaFile);
            $otherAsyncApiDetails = $this->addChannelsAndMessagesFromAsyncApi($asyncApi, $packageName, $otherAsyncApiDetails);
        }

        foreach ($asyncApiDetails as $channelName => $messages) {
            foreach ($messages as $messageName => $messageDetails) {
                foreach ($messageDetails as $type => $publisherOrSubscriber) {
                    if ($type === 'subscriber' && isset($otherAsyncApiDetails[$channelName][$messageName]['publisher'])) {
                        if (!isset($asyncApiDetails[$channelName][$messageName]['publisher'])) {
                            $asyncApiDetails[$channelName][$messageName]['publisher'] = [];
                        }
                        $asyncApiDetails[$channelName][$messageName]['publisher'] = array_merge_recursive($asyncApiDetails[$channelName][$messageName]['publisher'], $otherAsyncApiDetails[$channelName][$messageName]['publisher']);
                    }

                    if ($type === 'publisher' && isset($otherAsyncApiDetails[$channelName][$messageName]['subscriber'])) {
                        if (!isset($asyncApiDetails[$channelName][$messageName]['subscriber'])) {
                            $asyncApiDetails[$channelName][$messageName]['subscriber'] = [];
                        }
                        $asyncApiDetails[$channelName][$messageName]['subscriber'] = array_merge_recursive($asyncApiDetails[$channelName][$messageName]['subscriber'], $otherAsyncApiDetails[$channelName][$messageName]['subscriber']);
                    }
                }
            }
        }

        return $asyncApiDetails;
    }

    /**
     * @return array<string, string>
     */
    public function getChannelNames(): array
    {
        $asyncApiSchemaFiles = $this->getAsyncApiSchemaFiles();

        $channels = [];

        foreach ($asyncApiSchemaFiles as $asyncApiSchemaFile) {
            $asyncApi = $this->loadAsyncApiFromFile($asyncApiSchemaFile);
            foreach ($asyncApi->getChannels() as $channel) {
                $channels[$channel->getName()] = $channel->getName();
            }
        }

        sort($channels);

        return $channels;
    }

    /**
     * @param string $channelName
     *
     * @return array<string, string>
     */
    public function getChannelDetails(string $channelName): array
    {
        $asyncApiSchemaFiles = $this->getAsyncApiSchemaFiles();

        $asyncApiDetails = [];

        foreach ($asyncApiSchemaFiles as $asyncApiSchemaFile) {
            $asyncApi = $this->loadAsyncApiFromFile($asyncApiSchemaFile);
            $packageName = $this->getPackageNameFromFilePath($asyncApiSchemaFile);
            $asyncApiDetails = $this->addChannelsAndMessagesFromAsyncApi($asyncApi, $packageName, $asyncApiDetails);
        }

        foreach ($asyncApiDetails as $channel => $messages) {
            if ($channel !== $channelName) {
                unset($asyncApiDetails[$channel]);
            }
        }

        return $asyncApiDetails;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getMessageNames(): array
    {
        $asyncApiSchemaFiles = $this->getAsyncApiSchemaFiles();

        $messages = [
            'published' => [],
            'subscribed' => [],
        ];

        foreach ($asyncApiSchemaFiles as $asyncApiSchemaFile) {
            $asyncApi = $this->loadAsyncApiFromFile($asyncApiSchemaFile);
            foreach ($asyncApi->getChannels() as $channel) {
                foreach ($channel->getPublishMessages() as $message) {
                    $messages['subscribed'][$message->getName()] = $message->getName();
                }
                foreach ($channel->getSubscribeMessages() as $message) {
                    $messages['published'][$message->getName()] = $message->getName();
                }
            }
        }

        sort($messages['published']);
        sort($messages['subscribed']);

        return $messages;
    }

    /**
     * @param string $messageName
     *
     * @return array<string, string>
     */
    public function getMessageDetails(string $messageName): array
    {
        $asyncApiSchemaFiles = $this->getAsyncApiSchemaFiles();

        $asyncApiDetails = [];

        foreach ($asyncApiSchemaFiles as $asyncApiSchemaFile) {
            $asyncApi = $this->loadAsyncApiFromFile($asyncApiSchemaFile);
            $packageName = $this->getPackageNameFromFilePath($asyncApiSchemaFile);
            $asyncApiDetails = $this->addChannelsAndMessagesFromAsyncApi($asyncApi, $packageName, $asyncApiDetails);
        }

        foreach ($asyncApiDetails as $channel => $messages) {
            foreach ($messages as $message => $messageDetails) {
                if ($message !== $messageName) {
                    unset($asyncApiDetails[$channel][$message]);
                }
            }
            if (count($asyncApiDetails[$channel]) === 0) {
                unset($asyncApiDetails[$channel]);
            }
        }

        return $asyncApiDetails;
    }

    /**
     * @param AsyncApiInterface $asyncApi
     * @param string $packageName
     * @param array $asyncApiDetails
     *
     * @return array
     */
    protected function addChannelsAndMessagesFromAsyncApi(AsyncApiInterface $asyncApi, string $packageName, array $asyncApiDetails): array
    {
        foreach ($asyncApi->getChannels() as $channel) {
            $asyncApiDetails = $this->addSubscribeMessagesAndChannels($channel, $packageName, $asyncApiDetails);
            $asyncApiDetails = $this->addPublishMessagesAndChannels($channel, $packageName, $asyncApiDetails);
        }

        return $asyncApiDetails;
    }

    /**
     * @param AsyncApiChannelInterface $channel
     * @param string $packageName
     * @param array $asyncApiDetails
     *
     * @return array
     */
    protected function addSubscribeMessagesAndChannels(AsyncApiChannelInterface $channel, string $packageName, array $asyncApiDetails): array
    {
        foreach ($channel->getSubscribeMessages() as $subscribeMessage) {
            $asyncApiDetails = $this->addPublishedMessageAndChannelForPackage($subscribeMessage, $channel->getName(), $packageName, $asyncApiDetails);
        }

        return $asyncApiDetails;
    }

    /**
     * @param AsyncApiChannelInterface $channel
     * @param string $packageName
     * @param array $asyncApiDetails
     *
     * @return array
     */
    protected function addPublishMessagesAndChannels(AsyncApiChannelInterface $channel, string $packageName, array $asyncApiDetails): array
    {
        foreach ($channel->getPublishMessages() as $publishedMessage) {
            $asyncApiDetails = $this->addSubscribedMessageAndChannelForPackage($publishedMessage, $channel->getName(), $packageName, $asyncApiDetails);
        }

        return $asyncApiDetails;
    }

    /**
     * @param string|null $packageName
     *
     * @return Finder
     */
    protected function getAsyncApiSchemaFiles(?string $packageName = null): Finder
    {
        $pathToAsyncApiSchemaFiles = sprintf('%s/vendor/spryker-projects/async-api-contracts/resources/%s', $this->projectDir, $packageName ?? '');

        return $this->finder
            ->in($pathToAsyncApiSchemaFiles)
            ->name('*.yml')
            ->files();
    }

    /**
     * @param string $asyncApiSchemaFile
     * @param array $asyncApiDetails
     *
     * @return array
     */
    protected function addMessagesAndChannelsFromFilePath(string $asyncApiSchemaFile, array $asyncApiDetails): array
    {
        $asyncApi = $this->loadAsyncApiFromFile($asyncApiSchemaFile);
        $packageName = $this->getPackageNameFromFilePath($asyncApiSchemaFile);

        foreach ($asyncApi->getChannels() as $channelName => $channel) {
            foreach ($channel->getSubscribeMessages() as $subscribeMessage) {
                $asyncApiDetails = $this->addPublishedMessageAndChannelForPackage($subscribeMessage, $channelName, $packageName, $asyncApiDetails);
            }
            foreach ($channel->getPublishMessages() as $publishMessage) {
                $asyncApiDetails = $this->addSubscribedMessageAndChannelForPackage($publishMessage, $channelName, $packageName, $asyncApiDetails);
            }
        }

        return $asyncApiDetails;
    }

    /**
     * @param AsyncApiMessageInterface $message
     * @param string $channelName
     * @param string $package
     * @param array $asyncApiDetails
     *
     * @return array
     */
    protected function addPublishedMessageAndChannelForPackage(AsyncApiMessageInterface $message, string $channelName, string $package, array $asyncApiDetails): array
    {
        if (!isset($asyncApiDetails[$channelName])) {
            $asyncApiDetails[$channelName] = [];
        }

        $asyncApiDetails[$channelName][$message->getName()]['publisher'][$package] = [
            'sends' => $this->getRequiredAttributesForMessage($message),
        ];

        return $asyncApiDetails;
    }

    /**
     * @param AsyncApiMessageInterface $message
     * @param string $channelName
     * @param string $package
     * @param array $asyncApiDetails
     *
     * @return void
     */
    protected function addSubscribedMessageAndChannelForPackage(AsyncApiMessageInterface $message, string $channelName, string $package, array $asyncApiDetails): array
    {
        if (!isset($asyncApiDetails[$channelName])) {
            $asyncApiDetails[$channelName] = [];
        }

        $asyncApiDetails[$channelName][$message->getName()]['subscriber'][$package] = [
            'requires' => $this->getRequiredAttributesForMessage($message),
        ];

        return $asyncApiDetails;
    }

    /**
     * @param string $asyncApiSchemaFile
     *
     * @throws \Psr\Cache\InvalidArgumentException
     *
     * @return AsyncApiInterface
     */
    protected function loadAsyncApiFromFile(string $asyncApiSchemaFile): AsyncApiInterface
    {
        $asyncApi = $this->cache->get(str_replace(['/'], '-', $asyncApiSchemaFile), function (ItemInterface $item) use ($asyncApiSchemaFile): AsyncApiInterface {
            $item->expiresAfter(3600);

            return $this->asyncApiLoader->load($asyncApiSchemaFile);
        });

        return $asyncApi;
    }

    /**
     * @param string $asyncApiSchemaFilePath
     *
     * @return string
     */
    protected function getPackageNameFromFilePath(string $asyncApiSchemaFilePath): string
    {
        $pathFragments = explode(DIRECTORY_SEPARATOR, $asyncApiSchemaFilePath);
        array_pop($pathFragments);
        $namespace = array_pop($pathFragments);
        $organization = array_pop($pathFragments);

        return sprintf('%s/%s', $organization, $namespace);
    }



    /**
     * @param \SprykerSdk\AsyncApi\AsyncApi\Message\AsyncApiMessageInterface $message
     *
     * @return array
     */
    protected function getRequiredAttributesForMessage(AsyncApiMessageInterface $message): array
    {
        $payloadAttribute = $message->getAttribute('payload');

        // In case we have a "marker" message without any payload then we can skip the required field validation.
        if (!$payloadAttribute) {
            return [];
        }

        $requiredAttributes = [];
        $this->getRequiredAttributes($payloadAttribute, $requiredAttributes);

        return $requiredAttributes;
    }

    /**
     * @param \SprykerSdk\AsyncApi\AsyncApi\Message\Attributes\AsyncApiMessageAttributeCollectionInterface $properties
     * @param string $lookupAttributeName
     *
     * @return bool
     */
    protected function hasPropertiesCollectionProperty(AsyncApiMessageAttributeCollectionInterface $properties, string $lookupAttributeName): bool
    {
        foreach ($properties->getAttributes() as $attributeName => $attribute) {
            if ($attributeName === $lookupAttributeName) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \SprykerSdk\AsyncApi\AsyncApi\Message\Attributes\AsyncApiMessageAttributeCollectionInterface $properties
     * @param string $lookupPropertyName
     *
     * @throws \Exception
     *
     * @return \SprykerSdk\AsyncApi\AsyncApi\Message\Attributes\AsyncApiMessageAttributeCollectionInterface
     */
    protected function getPropertiesCollectionProperty(
        AsyncApiMessageAttributeCollectionInterface $properties,
        string $lookupPropertyName
    ): AsyncApiMessageAttributeCollectionInterface {
        foreach ($properties->getAttributes() as $attributeName => $attribute) {
            if ($attributeName !== $lookupPropertyName) {
                continue;
            }

            return $attribute;
        }

        throw new \Exception(sprintf('You MUST call "hasPropertiesCollectionProperty" before "getPropertiesCollectionProperty". Property "%s" not found in collection.', $lookupPropertyName));
    }

    /**
     * @param \SprykerSdk\AsyncApi\AsyncApi\Message\Attributes\AsyncApiMessageAttributeInterface $attribute
     * @param array $requiredAttributes
     * @param string $currentKey
     *
     * @return void
     */
    protected function getRequiredAttributes(AsyncApiMessageAttributeCollectionInterface $attribute, array &$requiredAttributes, string $currentKey = ''): void
    {
        $properties = $attribute->getAttribute('properties');
        $required = $attribute->getAttribute('required');

        if (!$required) {
            return;
        }

        foreach ($required->getAttributes() as $attribute) {
            $key = $currentKey ? sprintf('%s.%s', $currentKey, $attribute->getValue()) : $attribute->getValue();
            $requiredAttributes[$key] = $key;

            if ($this->hasPropertiesCollectionProperty($properties, $attribute->getValue())) {
                $this->getRequiredAttributes($this->getPropertiesCollectionProperty($properties, $attribute->getValue()), $requiredAttributes, $key);
            }
        }
    }
}