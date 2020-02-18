<?php
/**
 * Author Builder for the indexables.
 *
 * @package Yoast\YoastSEO\Builders
 */

namespace Yoast\WP\SEO\Builders;

use Yoast\WP\SEO\Helpers\Author_Archive_Helper;
use Yoast\WP\SEO\Helpers\Options_Helper;
use Yoast\WP\SEO\Models\Indexable;
use Yoast\WP\SEO\Repositories\Indexable_Repository;

/**
 * Formats the author meta to indexable format.
 */
class Indexable_Author_Builder {
	use Indexable_Social_Image_Trait;

	/**
	 * The options helper.
	 *
	 * @var Options_Helper
	 */
	private $options;

	/**
	 * The author archive helper.
	 *
	 * @var Author_Archive_Helper
	 */
	private $author_archive;

	/**
	 * The indexable repository.
	 *
	 * @var Indexable_Repository
	 */
	protected $indexable_repository;

	/**
	 * Indexable_Author_Builder constructor.
	 *
	 * @param Options_Helper        $options        The options helper.
	 * @param Author_Archive_Helper $author_archive The author archive helper.
	 */
	public function __construct(
		Options_Helper $options,
		Author_Archive_Helper $author_archive
	) {
		$this->options        = $options;
		$this->author_archive = $author_archive;
	}

	/**
	 * @required
	 *
	 * Sets the indexable repository. Done to avoid circular dependencies.
	 *
	 * @param Indexable_Repository $indexable_repository The indexable repository.
	 */
	public function set_indexable_repository( Indexable_Repository $indexable_repository ) {
		$this->indexable_repository = $indexable_repository;
	}

	/**
	 * Formats the data.
	 *
	 * @param int       $user_id   The user to retrieve the indexable for.
	 * @param Indexable $indexable The indexable to format.
	 *
	 * @return Indexable The extended indexable.
	 */
	public function build( $user_id, Indexable $indexable ) {
		$meta_data = $this->get_meta_data( $user_id );

		$indexable->object_id              = $user_id;
		$indexable->object_type            = 'user';
		$indexable->permalink              = \get_author_posts_url( $user_id );
		$indexable->title                  = $meta_data['wpseo_title'];
		$indexable->description            = $meta_data['wpseo_metadesc'];
		$indexable->is_cornerstone         = false;
		$indexable->is_robots_noindex      = ( $meta_data['wpseo_noindex_author'] === 'on' );
		$indexable->is_robots_nofollow     = null;
		$indexable->is_robots_noarchive    = null;
		$indexable->is_robots_noimageindex = null;
		$indexable->is_robots_nosnippet    = null;
		$indexable->is_public              = $this->is_public( $indexable );

		$this->reset_social_images( $indexable );
		$this->handle_social_images( $indexable );

		return $indexable;
	}

	/**
	 * Determines the value of is_public.
	 *
	 * @param Indexable $indexable The indexable.
	 *
	 * @return bool
	 */
	protected function is_public( $indexable ) {
		// Check the robots noindex setting at user level.
		if ( (int) $indexable->is_robots_noindex === 1 ) {
			return false;
		}

		// Check the robots noindex setting site-wide.
		$is_robots_noindex = $this->options->get( 'noindex-author-wpseo', false );
		if ( $is_robots_noindex === true ) {
			return false;
		}

		// Check whether the author archive should be public for authors without posts.
		$is_author_without_posts_noindex = $this->options->get( 'noindex-author-noposts-wpseo', false );
		if ( $is_author_without_posts_noindex === false ) {
			return true;
		}

		// If so, check whether an author has public posts.
		return $this->author_archive->author_has_public_posts( $indexable->object_id );
	}

	/**
	 * Retrieves the meta data for this indexable.
	 *
	 * @param int $user_id The user to retrieve the meta data for.
	 *
	 * @return array List of meta entries.
	 */
	protected function get_meta_data( $user_id ) {
		$keys = [
			'wpseo_title',
			'wpseo_metadesc',
			'wpseo_noindex_author',
		];

		$output = [];
		foreach ( $keys as $key ) {
			$output[ $key ] = $this->get_author_meta( $user_id, $key );
		}

		return $output;
	}

	/**
	 * Retrieves the author meta.
	 *
	 * @param int    $user_id The user to retrieve the indexable for.
	 * @param string $key     The meta entry to retrieve.
	 *
	 * @return string The value of the meta field.
	 */
	protected function get_author_meta( $user_id, $key ) {
		$value = \get_the_author_meta( $key, $user_id );
		if ( \is_string( $value ) && $value === '' ) {
			return null;
		}

		return $value;
	}

	/**
	 * Finds an alternative image for the social image.
	 *
	 * @param Indexable $indexable The indexable.
	 *
	 * @return array|bool False when not found, array with data when found.
	 */
	protected function find_alternative_image( Indexable $indexable ) {
		$gravatar_image = \get_avatar_url(
			$indexable->object_id,
			[
				'size'   => 500,
				'scheme' => 'https',
			]
		);
		if ( $gravatar_image ) {
			return [
				'image'  => $gravatar_image,
				'source' => 'gravatar-image',
			];
		}

		return false;
	}
}
