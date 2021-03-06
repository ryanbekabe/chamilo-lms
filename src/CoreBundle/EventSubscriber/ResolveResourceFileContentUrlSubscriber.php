<?php

/* For licensing terms, see /license.txt */

namespace Chamilo\CoreBundle\EventSubscriber;

use ApiPlatform\Core\EventListener\EventPriorities;
use ApiPlatform\Core\Util\RequestAttributesExtractor;
use Chamilo\CoreBundle\Entity\AbstractResource;
use Chamilo\CoreBundle\Entity\ResourceFile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

class ResolveResourceFileContentUrlSubscriber implements EventSubscriberInterface
{
    private $storage;
    private $generator;

    public function __construct(StorageInterface $storage, UrlGeneratorInterface $generator)
    {
        $this->storage = $storage;
        $this->generator = $generator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onPreSerialize', EventPriorities::PRE_SERIALIZE],
        ];
    }

    public function onPreSerialize(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();

        if ($controllerResult instanceof Response || !$request->attributes->getBoolean('_api_respond', true)) {
            return;
        }
        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if (!($attributes = RequestAttributesExtractor::extractAttributes($request)) ||
            //!\is_a($attributes['resource_class'], ResourceFile::class, true)
            !\is_a($attributes['resource_class'], AbstractResource::class, true)
        ) {
            return;
        }
        $mediaObjects = $controllerResult;

        if (!is_iterable($mediaObjects)) {
            $mediaObjects = [$mediaObjects];
        }

        foreach ($mediaObjects as $mediaObject) {
            if (!$mediaObject instanceof AbstractResource) {
                continue;
            }

            $params = [
                'id' => $mediaObject->getResourceNode()->getId(),
                'tool' => $mediaObject->getResourceNode()->getResourceType()->getTool()->getName(),
                'type' => $mediaObject->getResourceNode()->getResourceType()->getName(),
            ];

            $mediaObject->contentUrl = $this->generator->generate('chamilo_core_resource_view_file', $params);
        }
    }
}
