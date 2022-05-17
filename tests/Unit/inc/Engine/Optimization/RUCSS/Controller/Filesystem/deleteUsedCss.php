<?php

namespace WP_Rocket\Tests\Unit\inc\Engine\Optimization\RUCSS\Controller\Filesystem;

use Brain\Monkey\Functions;
use WP_Rocket\Engine\Optimization\RUCSS\Controller\Filesystem;
use WP_Rocket\Tests\Unit\FilesystemTestCase;

/**
 * @covers \WP_Rocket\Engine\Optimization\RUCSS\Controller\Filesystem::delete_used_css
 *
 * @group FRUCSS
 */
class test_DeleteUsedCss extends FilesystemTestCase {
	protected $path_to_test_data = '/inc/Engine/Optimization/RUCSS/Controller/Filesystem/deleteUsedCss.php';

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'get_current_blog_id' )->justReturn( 1 );
	}

	/**
	 * @dataProvider providerTestData
	 */
	public function testShouldReturnExpected( $hash, $file ) {
		$filesystem = new Filesystem( null, $this->filesystem->getUrl( 'wp-content/cache/used-css/' ) );

		$this->assertTrue( $filesystem->delete_used_css( $hash ) );
		$this->assertFalse( $this->filesystem->exists( $file ) );
	}
}
