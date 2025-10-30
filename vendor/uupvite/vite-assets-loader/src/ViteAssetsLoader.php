<?php
namespace UupVite;

/**
 * Load vite scripts and styles for current environment:
 *
 *   - if a dev process is running, load the vite dev server assets, allowing auto-reload / hmr
 *   - when a dev process is not running and vite manifest file exists, load the assets from the manifest
 *   - when dev process is not running, and manifest doesn't exist, do nothing
 */
class ViteAssetsLoader {
	/**
	 * The URL of the Vite server
	 */
	private $vite_server_url = null;

	/**
	 * json_decode()-ed version of the manifest file
	 */
	private $manifest_data = null;

	/**
	 * Path to the manifest file
	 */
	private $manifest_path;

	/**
	 * URL to the manifest file
	 */
	private $manifest_url;

	/**
	 * The enqueued scripts: [ 'script-handle' => 'resources/js/script.js', ... ]
	 */
	private $enqueued_scripts = [];

	/**
	 * Styles bundled with scripts. Since Vite 5, each asset can have extra styles.
	 */
	private $enqueued_scripts_extra_styles = [];

	/**
	 * The enqueued styles: [ 'style-handle' => 'resources/scss/file.scss', ... ]
	 */
	private $enqueued_styles = [];

	/**
	 * The enqueued editor styles: [ 'style-handle' => 'resources/scss/editor-styles.scss', ... ]
	 */
	private $enqueued_editor_styles = [];

	/**
	 * Initialize the asset loader
	 *
	 * @param string $manifest_path absolute path to the manifest file; file might not exist
	 * @param string $manifest_url url to the manifest; it's used to build production urls
	 */
	public function __construct( string $manifest_path, string $manifest_url ) {
		$this->manifest_path = $manifest_path;
		$this->manifest_url = $manifest_url;

		$hot_file_applied = $this->apply_hot_file_configuration();

		if ( ! $hot_file_applied ) {
			$this->apply_prod_manifest_configuration();
		}

		// wp_enqueue_script is the proper hook to enqueue scripts AND styles
		add_action( 'wp_enqueue_scripts', [ $this, 'push_assets_to_wp_queue' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'push_assets_to_wp_editor_queue' ] );

		// All <script> tags enqueued through this class should have
		// type="module" attribute, even in production.
		add_filter( 'script_loader_tag', function ( $tag, $handle ) {
			if ( isset( $this->enqueued_scripts[ $handle ] ) ) {
				return preg_replace( '/^<script /i', '<script type="module" ', $tag );
			}
			return $tag;
		}, 10, 2 );
	}

	/**
	 * Detects a vite server lock file, and applies the relevant configuration.
	 *
	 * Returns true if the hot file configuration is applied successfully,
	 * and false otherwise.
	 *
	 * @return bool
	 */
	protected function apply_hot_file_configuration() {
		$hot_file_path = dirname($this->manifest_path) . '/.hotfile.json';

		if ( ! file_exists( $hot_file_path ) ) {
			return false;
		}

		$hot_file_data = wp_json_file_decode(
			$hot_file_path,
			[ 'associative' => true ]
		);

		if ( ! $hot_file_data ) {
			$this->add_admin_bar_message( 'Invalid hot file' );
			return false;
		}

		if ( $hot_file_data[ 'generatedOnHost' ] !== gethostname() ) {
			$this->add_admin_bar_message(
				"Ignoring assets file from another host: " . $hot_file_data[ 'generatedOnHost' ]
			);
			return false;
		}

		$this->vite_server_url = $hot_file_data[ 'address' ];

		$this->add_admin_bar_message( 'Dev Process Running' );

		add_action( 'wp_head', [ $this, 'load_vite_client_scripts' ] );
		add_action( 'admin_head', [ $this, 'load_vite_client_scripts' ] );

		return true;
	}

	/**
	 * Applies production manifest configuration.
	 *
	 * @return void
	 */
	protected function apply_prod_manifest_configuration() {
		if ( file_exists( $this->manifest_path ) ) {
			$this->manifest_data = wp_json_file_decode(
				$this->manifest_path,
				[ 'associative' => true ]
			);
		} else {
			$this->add_admin_bar_message( 'Assets not built!' );
		}
	}

	/**
	 * Call wp_enqueue_script and wp_enqueue_style for each asset.
	 *
	 * @return void
	 */
	public function push_assets_to_wp_queue() {
		foreach ( $this->enqueued_scripts as $handle => $path ) {
			$url = $this->make_asset_url($path);
			if (is_null($url)) {
				$this->add_admin_bar_message("Missing script: $handle");
				continue;
			}
			wp_enqueue_script(
				$handle,
				$url,
				[],
				// This null is intentional: it prevents `?ver=X.X.X`
				// arguments in the URL. This would cause problems
				// with the Vite dev server
				null,
				[ 'in_footer' => true ]
			);
		}

		foreach ( $this->enqueued_styles as $handle => $path ) {
			$url = $this->make_asset_url( $path );
			if ( is_null( $url ) ) {
				$this->add_admin_bar_message( "Missing style: $handle" );
				continue;
			}
			wp_enqueue_style(
				$handle,
				$this->make_asset_url( $path ),
				[],
				// Again, the `null` arg is significant here
				null
			);
		}

		foreach ( $this->enqueued_scripts_extra_styles as $handle => $url ) {
			wp_enqueue_style( $handle, $url, [], null );
		}
	}

	/**
	 * Call wp_enqueue_style for each asset
	 *
	 * @return void
	 */
	public function push_assets_to_wp_editor_queue() {
		foreach ( $this->enqueued_editor_styles as $handle => $path ) {
			$url = $this->make_asset_url( $path );
			if ( is_null( $url ) ) {
				$this->add_admin_bar_message( "Missing editor style: $handle" );
				continue;
			}
			wp_enqueue_style(
				$handle,
				$this->make_asset_url( $path ),
				[],
				// Again, the `null` arg is significant here
				null
			);
		}
	}

	/**
	 * Produce "hot" asset URL from the vite server.
	 *
	 * @param string $path
	 * @return string URL for the path from the hot server
	 */
	private function hot_asset_url( $path ) {
		return $this->vite_server_url . $path;
	}

	/**
	 * URL to a file in the dist/ directory.
	 *
	 * @param string $path
	 * @return string $url
	 */
	private function dist_url( $file ) {
		return trailingslashit( dirname( $this->manifest_url ) ) . $file;
	}

	/**
	 * Produce URL for a path from the manifest
	 *
	 * @param string $path
	 * @return string URL for the path from the hot server
	 */
	private function prod_asset_url( $path ) {
		if ( ! isset( $this->manifest_data[ $path ][ 'file' ] ) ) {
			return null;
		}
		return $this->dist_url( $this->manifest_data[ $path ][ 'file' ] );
	}

	/**
	 * Produce a hot or production URL for a path, based on the current configuration
	 *
	 * @param string $path
	 * @return string URL
	 */
	private function make_asset_url( $path ) {
		return $this->is_dev() ?
			$this->hot_asset_url( $path ) :
			$this->prod_asset_url( $path );
	}

	/**
	 * Print the vite client scripts
	 *
	 * @return void
	 */
	public function load_vite_client_scripts()
	{
		// JSX requires some extra scripts for hot reload, see https://vitejs.dev/guide/backend-integration.html
		if ( $this->has_jsx() ) : ?>
			<script type="module">
				import RefreshRuntime from '<?php echo $this->hot_asset_url("@react-refresh") ?>'
				RefreshRuntime.injectIntoGlobalHook(window)
				window.$RefreshReg$ = () => {}
				window.$RefreshSig$ = () => (type) => type
				window.__vite_plugin_react_preamble_installed__ = true
			</script>
		<?php endif; ?>
		<script type="module" src="<?php echo $this->hot_asset_url( '@vite/client' ) ?>"></script>
		<?php
	}

	/**
	 * Do we have vite dev server running?
	 *
	 * @return bool
	 */
	private function is_dev() {
		return !empty( $this->vite_server_url );
	}

	/**
	 * Do we have manifest, and no vite server running?
	 *
	 * @return bool
	 */
	private function is_prod() {
		return ! $this->is_dev() && !empty( $this->manifest_data );
	}

	/**
	 * Add a message in the admin bar when a dev process is running.
	 *
	 * @param string $message The title of the admin bar message
	 * @param string ?$link If you'd like to link the message, pass in the href
	 * @return void
	 */
	private function add_admin_bar_message( $message ) {
		add_action( 'admin_bar_menu', function ( $wp_admin_bar ) use ($message) {
			$wp_admin_bar->add_node( [
				'id'     => 'vite-dev-process-' . rand(),
				'title'  => $message,
				'parent' => 'top-secondary',
			] );
		}, 100 );
	}

	/**
	 * Is there at least 1 enqueued entry point with jsx / tsx extension
	 *
	 * @return true
	 */
	private function has_jsx() {
		$jsx_paths = preg_grep( '/\.[jt]sx$/i', $this->enqueued_scripts );
		return count( $jsx_paths ) > 0;
	}

	/**
	 * Enqueue a javascript-ish file built with the vite dev process.
	 *
	 * @param string $handle The wp_enqueue_script handle
	 * @param string $path Path to the file in the resources directory
	 * @return void
	 */
	public function enqueue_script( $handle, $path ) {
		$this->enqueued_scripts[ $handle ] = $path;

		// Since Vite 5, manifest files might include extra "css" property for each
		// script that contains styles. This is relevant for vue / react bundles. See:
		// https://vitejs.dev/guide/migration.html#corresponding-css-files-are-not-listed-as-top-level-entry-in-manifest-json-file
		if ( $this->is_prod() && isset( $this->manifest_data[$path]['css'] ) ) {
			foreach ( $this->manifest_data[ $path ][ 'css' ] as $i => $file ) {
				$this->enqueued_scripts_extra_styles[ "$handle-styles-$i" ] = $this->dist_url( $file );
			}
		}
	}

	/**
	 * Enqueue a CSS-ish file built with the vite dev process.
	 *
	 * @param string $handle The wp_enqueue_style handle
	 * @param string $path Path to the file in the resources directory
	 * @return void
	 */
	public function enqueue_style( $handle, $path ) {
		$this->enqueued_styles[ $handle ] = $path;
	}

	/**
	 * Enqueue a CSS-ish editor file built with the vite dev process.
	 *
	 * @param string $handle The wp_enqueue_style handle
	 * @param string $path Path to the file in the resources directory
	 * @return void
	 */
	public function enqueue_editor_style( $handle, $path ) {
		$this->enqueued_editor_styles[ $handle ] = $path;
	}
}
