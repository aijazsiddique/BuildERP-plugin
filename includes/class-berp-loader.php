<?php
/**
 * Register all actions and filters for the plugin.
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Loader Class
 *
 * Maintains a list of all hooks and executes them.
 */
class BERP_Loader {


	/**
	 * Array of actions registered with WordPress.
	 *
	 * @var array
	 */
	protected $actions;

	/**
	 * Array of filters registered with WordPress.
	 *
	 * @var array
	 */
	protected $filters;

	/**
	 * Initialize collections.
	 */
	public function __construct() {
		$this->actions = array();
		$this->filters = array();
	}

	/**
	 * Add a new action to the collection.
	 *
	 * @param string $hook          The name of the WordPress action.
	 * @param object $component     A reference to the instance of the object.
	 * @param string $callback      The name of the function.
	 * @param int    $priority      Optional. Priority (default 10).
	 * @param int    $accepted_args Optional. Number of args (default 1).
	 */
	public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->actions = $this->add( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a new filter to the collection.
	 *
	 * @param string $hook          The name of the WordPress filter.
	 * @param object $component     A reference to the instance of the object.
	 * @param string $callback      The name of the function.
	 * @param int    $priority      Optional. Priority (default 10).
	 * @param int    $accepted_args Optional. Number of args (default 1).
	 */
	public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
		$this->filters = $this->add( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
	}

	/**
	 * Utility to add hooks to the collection.
	 *
	 * @param  array  $hooks         The collection of hooks.
	 * @param  string $hook          The name of the WordPress action/filter.
	 * @param  object $component     A reference to the instance of the object.
	 * @param  string $callback      The name of the function.
	 * @param  int    $priority      The priority.
	 * @param  int    $accepted_args The number of arguments.
	 * @return array The collection with the new hook added.
	 */
	private function add( $hooks, $hook, $component, $callback, $priority, $accepted_args ) {
		$hooks[] = array(
			'hook'          => $hook,
			'component'     => $component,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);

		return $hooks;
	}

	/**
	 * Register all filters and actions with WordPress.
	 */
	public function run() {
		foreach ( $this->filters as $hook ) {
			add_filter(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}

		foreach ( $this->actions as $hook ) {
			add_action(
				$hook['hook'],
				array( $hook['component'], $hook['callback'] ),
				$hook['priority'],
				$hook['accepted_args']
			);
		}
	}
}
