<?php
/**
 * @package WPSEO\Admin\Import\External
 */

/**
 * Class WPSEO_Import_External
 *
 * Class with functionality to import Yoast SEO settings from other plugins
 */
class WPSEO_Import_External {
	/**
	 * @var WPSEO_Import_Status
	 */
	public $status;

	/**
	 * @var WPSEO_External_Importer
	 */
	protected $importer;

	/**
	 * Import class constructor.
	 *
	 * @param WPSEO_External_Importer $importer The importer that needs to perform this action.
	 * @param string $action The action to perform.
	 */
	public function __construct( WPSEO_External_Importer $importer, $action ) {
		$this->importer = $importer;

		switch( $action ) {
			case 'cleanup':
				$this->status = $this->importer->cleanup();
				break;
			case 'detect':
			default:
				$this->status = $this->importer->detect();
				break;
			case 'import':
				$this->status = $this->importer->import();
				break;
		}

		$this->status->set_msg( $this->complete_msg( $this->status->get_msg() ) );
	}

	/**
	 * Convenience function to replace %s with plugin name in import message.
	 *
	 * @param string $msg Message string.
	 *
	 * @return string
	 */
	protected function complete_msg( $msg ) {
		return sprintf( $msg, $this->importer->plugin_name() );
	}
}
