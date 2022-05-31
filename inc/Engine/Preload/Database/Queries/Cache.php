<?php

namespace WP_Rocket\Engine\Preload\Database\Queries;

use WP_Rocket\Dependencies\Database\Query;

class Cache extends Query {


	/**
	 * Name of the database table to query.
	 *
	 * @var   string
	 */
	protected $table_name = 'wpr_rocket_cache';

	/**
	 * String used to alias the database table in MySQL statement.
	 *
	 * Keep this short, but descriptive. I.E. "tr" for term relationships.
	 *
	 * This is used to avoid collisions with JOINs.
	 *
	 * @var   string
	 */
	protected $table_alias = 'wpr_cache';
	/**
	 * Name of class used to setup the database schema.
	 *
	 * @var string
	 */
	protected $table_schema = self::class;

	/** Item ******************************************************************/

	/**
	 * Name for a single item.
	 *
	 * Use underscores between words. I.E. "term_relationship"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @var   string
	 */
	protected $item_name = 'cache';

	/**
	 * Plural version for a group of items.
	 *
	 * Use underscores between words. I.E. "term_relationships"
	 *
	 * This is used to automatically generate action hooks.
	 *
	 * @var string
	 */
	protected $item_name_plural = 'caches';

	/**
	 * Name of class used to turn IDs into first-class objects.
	 *
	 * This is used when looping through return values to guarantee their shape.
	 *
	 * @var mixed
	 */
	protected $item_shape = self::class;

	/**
	 * Create new resource row or update its contents if not created before.
	 *
	 * @since 3.9
	 *
	 * @param array $resource Resource array.
	 *
	 * @return bool
	 */
	public function create_or_update( array $resource ) {
		// check the database if those resources added before.
		$rows = $this->query(
			[
				'url' => untrailingslashit( $resource['url'] ),
			]
		);

		if ( count( $rows ) === 0 ) {
			// Create this new row in DB.
			$resource_id = $this->add_item(
				[
					'url'           => untrailingslashit( $resource['url'] ),
					'status'        => $resource['status'],
					'last_accessed' => current_time( 'mysql', true ),
				]
			);

			if ( $resource_id ) {
				return $resource_id;
			}

			return false;
		}

		$db_row = array_pop( $rows );

		// Update this row with the new content.
		$this->update_item(
			$db_row->id,
			[
				'url'      => untrailingslashit( $resource['url'] ),
				'status'   => $resource['status'],
				'modified' => current_time( 'mysql', true ),
				'last_accessed' => current_time( 'mysql', true ),
			]
		);

		return $db_row->id;
	}

	/**
	 * Create new resource row or update its contents if not created before.
	 *
	 * @since 3.9
	 *
	 * @param array $resource Resource array.
	 *
	 * @return bool
	 */
	public function create_or_nothing( array $resource ) {
		// check the database if those resources added before.
		$rows = $this->query(
			[
				'url' => untrailingslashit( $resource['url'] ),
			]
		);

		if ( count( $rows ) > 0 ) {
			return false;
		}

		// Create this new row in DB.
		$resource_id = $this->add_item(
			[
				'url'           => untrailingslashit( $resource['url'] ),
				'status'        => $resource['status'],
				'last_accessed' => current_time( 'mysql', true ),
			]
		);

		if ( $resource_id ) {
			return $resource_id;
		}

		return false;
	}

	/**
	 * Get all rows with the same url (desktop and mobile versions).
	 *
	 * @param string $url Page url.
	 *
	 * @return array|false
	 */
	public function get_rows_by_url( string $url ) {
		$query = $this->query(
			[
				'url' => untrailingslashit( $url ),
			]
		);

		if ( empty( $query ) ) {
			return false;
		}

		return $query;
	}

	/**
	 * Delete DB row by url.
	 *
	 * @param string $url Page url to be deleted.
	 *
	 * @return bool
	 */
	public function delete_by_url( string $url ) {
		$items = $this->get_rows_by_url( $url );

		if ( ! $items ) {
			return false;
		}

		$deleted = true;
		foreach ( $items as $item ) {
			$deleted = $deleted && $this->delete_item( $item->id );
		}

		return $deleted;
	}

	/**
	 * Get all preload caches which were not accessed in the last month.
	 *
	 * @return array
	 */
	public function get_old_cache() : array {
		// Get the database interface.
		$db = $this->get_db();

		// Bail if no database interface is available.
		if ( empty( $db ) ) {
			return [];
		}

		$prefixed_table_name = $this->apply_prefix( $this->table_name );
		$query               = "SELECT id FROM `$prefixed_table_name` WHERE `last_accessed` <= date_sub(now(), interval 1 month)";
		$rows_affected       = $db->get_results( $query );

		return $rows_affected;
	}

	/**
	 * Remove all completed rows one by one.
	 *
	 * @return void
	 */
	public function remove_all_not_accessed_rows() {
		$rows = $this->get_old_cache();

		foreach ( $rows as $row ) {
			$this->delete_item( $row->id );
		}
	}
}
