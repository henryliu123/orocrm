<?php

namespace Oro\Component\Config\Loader;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\CumulativeResourceManager;

class CumulativeConfigLoader
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var CumulativeResourceLoaderCollection
     */
    protected $resourceLoaders;

    /**
     * @param string                                              $name The unique name of a configuration resource
     * @param CumulativeResourceLoader|CumulativeResourceLoader[] $resourceLoader
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $resourceLoader)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('$name must not be empty.');
        }
        if (empty($resourceLoader)) {
            throw new \InvalidArgumentException('$resourceLoader must not be empty.');
        }

        $this->name            = $name;
        $this->resourceLoaders = new CumulativeResourceLoaderCollection(
            is_array($resourceLoader) ? $resourceLoader : [$resourceLoader]
        );
    }

    /**
     * Loads resources
     *
     * @param ContainerBuilder|null $container The container builder
     *                                         If NULL the loaded resources will not be registered in the container
     *                                         and as result will not be monitored for changes
     * @return CumulativeResourceInfo[]
     */
    public function load(ContainerBuilder $container = null)
    {
        $result = [];

        $bundles = CumulativeResourceManager::getInstance()->getBundles();
        foreach ($bundles as $bundleClass) {
            $reflection = new \ReflectionClass($bundleClass);
            $bundleDir  = dirname($reflection->getFilename());
            /** @var CumulativeResourceLoader $resourceLoader */
            foreach ($this->resourceLoaders as $resourceLoader) {
                $resource = $resourceLoader->load($bundleClass, $bundleDir);
                if (null !== $resource) {
                    if (is_array($resource)) {
                        foreach ($resource as $res) {
                            $result[] = $res;
                        }
                    } else {
                        $result[] = $resource;
                    }
                }
            }
        }

        if ($container) {
            $this->registerResources($container);
        }

        return $result;
    }

    /**
     * Adds a resource objects to the container.
     * These objects will be used to monitor whether resources are up-to-date or not.
     *
     * @param ContainerBuilder $container
     * @throws \RuntimeException if the container builder was not specified
     */
    public function registerResources(ContainerBuilder $container)
    {
        $bundles  = CumulativeResourceManager::getInstance()->getBundles();
        $resource = new CumulativeResource($this->name, $this->resourceLoaders);
        /** @var CumulativeResourceLoader $resourceLoader */
        foreach ($this->resourceLoaders as $resourceLoader) {
            foreach ($bundles as $bundleClass) {
                $reflection = new \ReflectionClass($bundleClass);
                $bundleDir  = dirname($reflection->getFilename());
                $resourceLoader->registerFoundResource($bundleClass, $bundleDir, $resource);
            }
        }
        $container->addResource($resource);
    }
}
