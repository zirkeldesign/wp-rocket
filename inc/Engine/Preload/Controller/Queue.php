<?php

namespace WP_Rocket\Engine\Preload\Controller;

use WP_Rocket\Engine\Common\Queue\AbstractASQueue;

class Queue extends AbstractASQueue {
	/**
	 * Queue group.
	 *
	 * @var string
	 */
	protected $group = 'rocket-preload';

	/**
	 * Pending jobs cron hook.
	 *
	 * @var string
	 */
	private $pending_job_cron = 'rocket_preload_pending_job_cron';

	/**
	 * Check if pending jobs cron is scheduled.
	 *
	 * @return bool
	 */
	public function is_pending_jobs_cron_scheduled() {
		return $this->is_scheduled( $this->pending_job_cron );
	}

	/**
	 * Cancel pending jobs cron.
	 *
	 * @return void
	 */
	public function cancel_pending_jobs_cron() {
		$this->cancel_all( $this->pending_job_cron );
	}

	/**
	 * Schedule pending jobs cron.
	 *
	 * @param int $interval Cron interval in seconds.
	 *
	 * @return string
	 */
	public function schedule_pending_jobs_cron( int $interval ) {
		return $this->schedule_recurring( time(), $interval, $this->pending_job_cron );
	}

	/**
	 * Add Async job with DB row ID.
	 *
	 * @param string $sitemap_url DB row ID.
	 *
	 * @return string
	 */
	public function add_job_preload_job_parse_sitemap_async( string $sitemap_url ) {
		return $this->add_async(
			'rocket_preload_job_parse_sitemap',
			[
				$sitemap_url,
			]
		);
	}

	/**
	 * Add Async job with DB row ID.
	 *
	 * @param string $sitemap_url DB row ID.
	 *
	 * @return string
	 */
	public function add_job_preload_job_preload_url_async( string $sitemap_url ) {
		return $this->add_async(
			'rocket_preload_job_preload_url',
			[
				$sitemap_url,
			]
		);
	}
}