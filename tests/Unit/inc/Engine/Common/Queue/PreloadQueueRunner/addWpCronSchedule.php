<?php

namespace WP_Rocket\Tests\Unit\inc\Engine\Common\Queue\PreloadQueueRunner;

use ActionScheduler_ActionClaim;
use ActionScheduler_AsyncRequest_QueueRunner;
use ActionScheduler_Compatibility;
use ActionScheduler_FatalErrorMonitor;
use ActionScheduler_Lock;
use ActionScheduler_QueueCleaner;
use ActionScheduler_Store;
use Mockery;
use WP_Rocket\Engine\Common\Queue\PreloadQueueRunner;
use WP_Rocket\Logger\Logger;
use WP_Rocket\Tests\Unit\TestCase;
use Brain\Monkey\Functions;

class Test_AddWpCronSchedule extends TestCase
{
	protected $queueRunner;
	protected $store;
	protected $monitor;
	protected $cleaner;
	protected $async_request;
	protected $compatibility;
	protected $logger;
	protected $locker;

	protected function setUp(): void
	{
		parent::setUp();
		$this->store = Mockery::mock(ActionScheduler_Store::class);
		$this->monitor = Mockery::mock(ActionScheduler_FatalErrorMonitor::class);
		$this->async_request = Mockery::mock(ActionScheduler_AsyncRequest_QueueRunner::class);
		$this->compatibility = Mockery::mock(ActionScheduler_Compatibility::class);
		$this->cleaner = Mockery::mock(ActionScheduler_QueueCleaner::class);
		$this->logger = Mockery::mock(Logger::class);
		$this->locker = Mockery::mock(ActionScheduler_Lock::class);
		$this->queueRunner = new PreloadQueueRunner(
			$this->store,
			$this->monitor,
			$this->cleaner,
			$this->async_request,
			$this->compatibility,
			$this->logger,
			$this->locker,
		);
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldDoAsExpected($config, $expected) {
		Functions\when('__')->returnArg();
		$this->queueRunner->add_wp_cron_schedule($config);
		$this->assertSame($expected, $this->queueRunner->add_wp_cron_schedule($config));
	}
}