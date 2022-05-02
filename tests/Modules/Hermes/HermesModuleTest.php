<?php

namespace Tests\App\Modules\Hermes;

use App\Classes\DependencyInjection\Container;
use App\Classes\Worker\Application;
use App\Modules\Hermes\HermesModule;

class HermesModuleTest extends \PHPUnit\Framework\TestCase
{
	/** @var HermesModule * */
	protected $module;

	public function setUp(): void
	{
		$this->module = new HermesModule($this->getApplicationMock());
	}

	public function testGetName()
	{
		$this->assertEquals('Hermes', $this->module->getName());
	}

	public function getApplicationMock()
	{
		$applicationMock = $this
			->getMockBuilder(Application::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$applicationMock
			->expects($this->any())
			->method('getContainer')
			->willReturnCallback([$this, 'getContainerMock'])
		;

		return $applicationMock;
	}

	public function getContainerMock()
	{
		$containerMock = $this
			->getMockBuilder(Container::class)
			->disableOriginalConstructor()
			->getMock()
		;
		$containerMock
			->expects($this->any())
			->method('getParameter')
			->willReturn(realpath('.'))
		;

		return $containerMock;
	}
}
