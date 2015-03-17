<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Event\ProcessEvents;
use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;

class ProcessHandler
{
    /**
     * @var ProcessFactory
     */
    protected $factory;

    /**
     * @var ProcessLogger
     */
    protected $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ProcessFactory $factory
     * @param ProcessLogger $logger
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ProcessFactory $factory,
        ProcessLogger $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->factory = $factory;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param ProcessTrigger $processTrigger
     * @param ProcessData $processData
     * @throws InvalidParameterException
     */
    public function handleTrigger(ProcessTrigger $processTrigger, ProcessData $processData)
    {
        $this->eventDispatcher->dispatch(
            ProcessEvents::HANDLE_BEFORE,
            new ProcessHandleEvent($processTrigger, $processData)
        );

        if (!$processData['data']) {
            throw new InvalidParameterException('Invalid process data. Entity can not be empty.');
        }

        $process = $this->factory->create($processTrigger->getDefinition());
        $process->execute($processData);

        $this->logger->debug('Process executed', $processTrigger, $processData);

        $this->eventDispatcher->dispatch(
            ProcessEvents::HANDLE_AFTER,
            new ProcessHandleEvent($processTrigger, $processData)
        );
    }

    /**
     * @param ProcessJob $processJob
     */
    public function handleJob(ProcessJob $processJob)
    {
        $this->handleTrigger($processJob->getProcessTrigger(), $processJob->getData());
    }

    /**
     * @param ProcessTrigger $processTrigger
     * @param ProcessData $processData
     */
    public function finishTrigger(ProcessTrigger $processTrigger, ProcessData $processData)
    {
        $this->eventDispatcher->dispatch(
            ProcessEvents::HANDLE_AFTER_FLUSH,
            new ProcessHandleEvent($processTrigger, $processData)
        );
    }

    /**
     * @param ProcessJob $processJob
     */
    public function finishJob(ProcessJob $processJob)
    {
        $this->finishTrigger($processJob->getProcessTrigger(), $processJob->getData());
    }
}