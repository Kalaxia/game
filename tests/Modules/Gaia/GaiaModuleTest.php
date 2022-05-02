<?php

namespace Tests\App\Modules\Gaia;

use App\Classes\DependencyInjection\Container;
use App\Classes\Worker\Application;
use App\Modules\Gaia\GaiaModule;

class GaiaModuleTest extends \PHPUnit\Framework\TestCase
{
	/** @var GaiaModule * */
	protected $module;

	public function setUp(): void
	{
		$this->module = new GaiaModule($this->getApplicationMock());
	}

	public function testGetName()
	{
		$this->assertEquals('Gaia', $this->module->getName());
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
