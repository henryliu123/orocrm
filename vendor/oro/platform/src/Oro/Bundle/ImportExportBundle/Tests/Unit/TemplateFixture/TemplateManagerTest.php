<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestTemplateEntityRepository;

class TemplateManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityRegistry;

    /** @var TemplateManager */
    protected $templateManager;

    protected function setUp()
    {
        $this->entityRegistry     =
            $this->getMockBuilder('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry')
                ->disableOriginalConstructor()
                ->getMock();
        $this->templateManager = new TemplateManager($this->entityRegistry);
    }

    public function testHasEntityRepository()
    {
        $repository = $this->getMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('Test\Entity'));

        $this->templateManager->addEntityRepository($repository);

        $this->assertFalse(
            $this->templateManager->hasEntityRepository('unknown')
        );
        $this->assertTrue(
            $this->templateManager->hasEntityRepository('Test\Entity')
        );
    }

    public function testHasEntityFixture()
    {
        $repository = $this->getMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('Test\Entity1'));

        $fixture = $this->getMock('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface');
        $fixture->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('Test\Entity2'));

        $this->templateManager->addEntityRepository($repository);
        $this->templateManager->addEntityRepository($fixture);

        $this->assertFalse(
            $this->templateManager->hasEntityFixture('unknown')
        );
        $this->assertFalse(
            $this->templateManager->hasEntityFixture('Test\Entity1')
        );
        $this->assertTrue(
            $this->templateManager->hasEntityFixture('Test\Entity2')
        );
    }

    public function testGetEntityRepository()
    {
        $repository = $this->getMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('Test\Entity1'));

        $fixture = $this->getMock('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface');
        $fixture->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('Test\Entity2'));

        $this->templateManager->addEntityRepository($repository);
        $this->templateManager->addEntityRepository($fixture);

        $this->assertSame(
            $repository,
            $this->templateManager->getEntityRepository('Test\Entity1')
        );

        $this->assertSame(
            $fixture,
            $this->templateManager->getEntityRepository('Test\Entity2')
        );

        $this->setExpectedException(
            'Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException',
            'The template repository for "Test\Entity3" was not registered.'
        );
        $this->templateManager->getEntityRepository('Test\Entity3');
    }

    public function testGetEntityFixture()
    {
        $repository = $this->getMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('Test\Entity1'));

        $fixture = $this->getMock('Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface');
        $fixture->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('Test\Entity2'));

        $this->templateManager->addEntityRepository($repository);
        $this->templateManager->addEntityRepository($fixture);

        $this->assertSame(
            $fixture,
            $this->templateManager->getEntityFixture('Test\Entity2')
        );

        $this->setExpectedException(
            'Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException',
            'The template fixture for "Test\Entity1" was not registered.'
        );
        $this->templateManager->getEntityFixture('Test\Entity1');
    }

    public function testFrozenTemplateManager()
    {
        $repository = $this->getMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );
        $repository->expects($this->once())
            ->method('getEntityClass')
            ->will($this->returnValue('Test\Entity1'));

        $this->templateManager->addEntityRepository($repository);

        $this->assertSame(
            $repository,
            $this->templateManager->getEntityRepository('Test\Entity1')
        );

        $anotherRepository = $this->getMock(
            'Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface'
        );

        $this->setExpectedException(
            'Oro\Bundle\ImportExportBundle\Exception\LogicException',
            sprintf('The repository "%s" cannot be added to the frozen registry.', get_class($anotherRepository))
        );
        $this->templateManager->addEntityRepository($anotherRepository);
    }

    public function testInitialization()
    {
        $repository = new TestTemplateEntityRepository();
        $this->templateManager->addEntityRepository($repository);

        $this->assertSame(
            $this->templateManager,
            $this->templateManager->getEntityRepository($repository->getEntityClass())->getTemplateManager()
        );
    }
}
