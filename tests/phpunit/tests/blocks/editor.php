<?php
/**
 * Tests for the block editor methods.
 *
 * @package WordPress
 * @subpackage Blocks
 * @since 5.5.0
 *
 * @group blocks
 */
class Tests_Blocks_Editor extends WP_UnitTestCase {

	/**
	 * Sets up each test method.
	 */
	public function set_up() {
		global $post;

		parent::set_up();

		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		$args = array(
			'post_title' => 'Example',
		);

		$post = self::factory()->post->create_and_get( $args );

		global $wp_rest_server;
		$wp_rest_server = new Spy_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		global $post_ID;
		$post_ID = 1;
	}

	public function tear_down() {
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;
		global $post_ID;
		$post_ID = null;
		parent::tear_down();
	}

	public function filter_set_block_categories_post( $block_categories, $post ) {
		if ( empty( $post ) ) {
			return $block_categories;
		}

		return array(
			array(
				'slug'  => 'filtered-category',
				'title' => 'Filtered Category',
				'icon'  => null,
			),
		);
	}

	public function filter_set_allowed_block_types_post( $allowed_block_types, $post ) {
		if ( empty( $post ) ) {
			return $allowed_block_types;
		}

		return array( 'test/filtered-block' );
	}

	public function filter_set_block_editor_settings_post( $editor_settings, $post ) {
		if ( empty( $post ) ) {
			return $editor_settings;
		}

		return array(
			'filter' => 'deprecated',
		);
	}

	/**
	 * @ticket 52920
	 */
	public function test_block_editor_context_no_settings() {
		$context = new WP_Block_Editor_Context();

		$this->assertSame( 'core/edit-post', $context->name );
		$this->assertNull( $context->post );
	}

	/**
	 * @ticket 52920
	 */
	public function test_block_editor_context_post() {
		$context = new WP_Block_Editor_Context( array( 'post' => get_post() ) );

		$this->assertSame( 'core/edit-post', $context->name );
		$this->assertSame( get_post(), $context->post );
	}

	/**
	 * @ticket 55301
	 */
	public function test_block_editor_context_widgets() {
		$context = new WP_Block_Editor_Context( array( 'name' => 'core/edit-widgets' ) );

		$this->assertSame( 'core/edit-widgets', $context->name );
		$this->assertNull( $context->post );
	}

	/**
	 * @ticket 55301
	 */
	public function test_block_editor_context_widgets_customizer() {
		$context = new WP_Block_Editor_Context( array( 'name' => 'core/customize-widgets' ) );

		$this->assertSame( 'core/customize-widgets', $context->name );
		$this->assertNull( $context->post );
	}

	/**
	 * @ticket 55301
	 */
	public function test_block_editor_context_site() {
		$context = new WP_Block_Editor_Context( array( 'name' => 'core/edit-site' ) );

		$this->assertSame( 'core/edit-site', $context->name );
		$this->assertNull( $context->post );
	}

	/**
	 * @ticket 52920
	 * @expectedDeprecated block_categories
	 */
	public function test_get_block_categories_deprecated_filter_post_object() {
		add_filter( 'block_categories', array( $this, 'filter_set_block_categories_post' ), 10, 2 );

		$block_categories = get_block_categories( get_post() );

		remove_filter( 'block_categories', array( $this, 'filter_set_block_categories_post' ) );

		$this->assertSameSets(
			array(
				array(
					'slug'  => 'filtered-category',
					'title' => 'Filtered Category',
					'icon'  => null,
				),
			),
			$block_categories
		);
	}

	/**
	 * @ticket 52920
	 * @expectedDeprecated block_categories
	 */
	public function test_get_block_categories_deprecated_filter_post_editor() {
		add_filter( 'block_categories', array( $this, 'filter_set_block_categories_post' ), 10, 2 );

		$post_editor_context = new WP_Block_Editor_Context( array( 'post' => get_post() ) );
		$block_categories    = get_block_categories( $post_editor_context );

		remove_filter( 'block_categories', array( $this, 'filter_set_block_categories_post' ) );

		$this->assertSameSets(
			array(
				array(
					'slug'  => 'filtered-category',
					'title' => 'Filtered Category',
					'icon'  => null,
				),
			),
			$block_categories
		);
	}

	/**
	 * @ticket 52920
	 */
	public function test_get_allowed_block_types_default() {
		$post_editor_context = new WP_Block_Editor_Context( array( 'post' => get_post() ) );
		$allowed_block_types = get_allowed_block_types( $post_editor_context );

		$this->assertTrue( $allowed_block_types );
	}

	/**
	 * @ticket 52920
	 * @expectedDeprecated allowed_block_types
	 */
	public function test_get_allowed_block_types_deprecated_filter_post_editor() {
		add_filter( 'allowed_block_types', array( $this, 'filter_set_allowed_block_types_post' ), 10, 2 );

		$post_editor_context = new WP_Block_Editor_Context( array( 'post' => get_post() ) );
		$allowed_block_types = get_allowed_block_types( $post_editor_context );

		remove_filter( 'allowed_block_types', array( $this, 'filter_set_allowed_block_types_post' ) );

		$this->assertSameSets( array( 'test/filtered-block' ), $allowed_block_types );
	}

	/**
	 * @ticket 52920
	 */
	public function test_get_default_block_editor_settings() {
		$settings = get_default_block_editor_settings();

		$this->assertCount( 20, $settings );
		$this->assertFalse( $settings['alignWide'] );
		$this->assertIsArray( $settings['allowedMimeTypes'] );
		$this->assertTrue( $settings['allowedBlockTypes'] );
		$this->assertSameSets(
			array(
				array(
					'slug'  => 'text',
					'title' => 'Text',
					'icon'  => null,
				),
				array(
					'slug'  => 'media',
					'title' => 'Media',
					'icon'  => null,
				),
				array(
					'slug'  => 'design',
					'title' => 'Design',
					'icon'  => null,
				),
				array(
					'slug'  => 'widgets',
					'title' => 'Widgets',
					'icon'  => null,
				),
				array(
					'slug'  => 'theme',
					'title' => 'Theme',
					'icon'  => null,
				),
				array(
					'slug'  => 'embed',
					'title' => 'Embeds',
					'icon'  => null,
				),
				array(
					'slug'  => 'reusable',
					'title' => 'Patterns',
					'icon'  => null,
				),
			),
			$settings['blockCategories']
		);
		$this->assertFalse( $settings['disableCustomColors'] );
		$this->assertFalse( $settings['disableCustomFontSizes'] );
		$this->assertFalse( $settings['disableCustomGradients'] );
		$this->assertFalse( $settings['disableLayoutStyles'] );
		$this->assertFalse( $settings['enableCustomLineHeight'] );
		$this->assertFalse( $settings['enableCustomSpacing'] );
		$this->assertFalse( $settings['enableCustomUnits'] );
		$this->assertFalse( $settings['isRTL'] );
		$this->assertSame( 'large', $settings['imageDefaultSize'] );
		$this->assertSameSets(
			array(
				array(
					'width'  => 150,
					'height' => 150,
					'crop'   => true,
				),
				array(
					'width'  => 300,
					'height' => 300,
					'crop'   => false,
				),
				array(
					'width'  => 1024,
					'height' => 1024,
					'crop'   => false,
				),
			),
			$settings['imageDimensions']
		);
		$this->assertTrue( $settings['imageEditing'] );
		$this->assertSameSets(
			array(
				array(
					'slug' => 'full',
					'name' => 'Full Size',
				),
				array(
					'slug' => 'large',
					'name' => 'Large',
				),
				array(
					'slug' => 'medium',
					'name' => 'Medium',
				),
				array(
					'slug' => 'thumbnail',
					'name' => 'Thumbnail',
				),
			),
			$settings['imageSizes']
		);
		$this->assertIsInt( $settings['maxUploadFileSize'] );
		$this->assertSame( admin_url( '/' ), $settings['__experimentalDashboardLink'] );
		$this->assertTrue( $settings['__unstableGalleryWithImageBlocks'] );
	}

	/**
	 * @ticket 56815
	 */
	public function test_get_default_block_editor_settings_max_upload_file_size() {
		// Force the return value of wp_max_upload_size() to be 500.
		add_filter(
			'upload_size_limit',
			static function () {
				return 500;
			}
		);

		// Expect 0 when user is not allowed to upload (as wp_max_upload_size() should not be called).
		$settings = get_default_block_editor_settings();
		$this->assertSame( 0, $settings['maxUploadFileSize'] );

		// Set up an administrator, as they can upload files.
		$administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $administrator );

		// Expect the above 500 as the user is now allowed to upload.
		$settings = get_default_block_editor_settings();
		$this->assertSame( 500, $settings['maxUploadFileSize'] );
	}

	/**
	 * @ticket 53397
	 */
	public function test_get_legacy_widget_block_editor_settings() {
		$settings = get_legacy_widget_block_editor_settings();
		$this->assertCount( 1, $settings );
		$this->assertSameSets(
			array(
				'archives',
				'block',
				'calendar',
				'categories',
				'custom_html',
				'media_audio',
				'media_gallery',
				'media_image',
				'media_video',
				'pages',
				'recent-comments',
				'recent-posts',
				'rss',
				'search',
				'tag_cloud',
				'text',
			),
			$settings['widgetTypesToHideFromLegacyWidgetBlock']
		);
	}

	/**
	 * @ticket 52920
	 */
	public function test_get_block_editor_settings_overrides_default_settings_all_editors() {
		function filter_allowed_block_types_my_editor() {
			return array( 'test/filtered-my-block' );
		}
		function filter_block_categories_my_editor() {
			return array(
				array(
					'slug'  => 'filtered-my-category',
					'title' => 'Filtered My Category',
					'icon'  => null,
				),
			);
		}
		function filter_block_editor_settings_my_editor( $editor_settings ) {
			$editor_settings['maxUploadFileSize'] = 12345;

			return $editor_settings;
		}

		add_filter( 'allowed_block_types_all', 'filter_allowed_block_types_my_editor', 10, 1 );
		add_filter( 'block_categories_all', 'filter_block_categories_my_editor', 10, 1 );
		add_filter( 'block_editor_settings_all', 'filter_block_editor_settings_my_editor', 10, 1 );

		$my_editor_context = new WP_Block_Editor_Context();
		$settings          = get_block_editor_settings( array(), $my_editor_context );

		remove_filter( 'allowed_block_types_all', 'filter_allowed_block_types_my_editor' );
		remove_filter( 'block_categories_all', 'filter_block_categories_my_editor' );
		remove_filter( 'block_editor_settings_all', 'filter_block_editor_settings_my_editor' );

		$this->assertSameSets( array( 'test/filtered-my-block' ), $settings['allowedBlockTypes'] );
		$this->assertSameSets(
			array(
				array(
					'slug'  => 'filtered-my-category',
					'title' => 'Filtered My Category',
					'icon'  => null,
				),
			),
			$settings['blockCategories']
		);
		$this->assertSame( 12345, $settings['maxUploadFileSize'] );
	}

	/**
	 * @ticket 58534
	 */
	public function test_wp_get_first_block() {
		$block_name               = 'core/paragraph';
		$blocks                   = array(
			array(
				'blockName' => 'core/image',
			),
			array(
				'blockName' => $block_name,
				'attrs'     => array(
					'content' => 'Hello World!',
				),
			),
			array(
				'blockName' => 'core/heading',
			),
			array(
				'blockName' => $block_name,
			),
		);
		$blocks_with_no_paragraph = array(
			array(
				'blockName' => 'core/image',
			),
			array(
				'blockName' => 'core/heading',
			),
		);

		$this->assertSame( $blocks[1], wp_get_first_block( $blocks, $block_name ) );

		$this->assertSame( array(), wp_get_first_block( $blocks_with_no_paragraph, $block_name ) );
	}

	/**
	 * @ticket 58534
	 */
	public function test_wp_get_post_content_block_attributes() {
		$attributes_with_layout = array(
			'layout' => array(
				'type' => 'constrained',
			),
		);
		// With no block theme, expect null.
		$this->assertNull( wp_get_post_content_block_attributes() );

		switch_theme( 'block-theme' );

		$this->assertSame( $attributes_with_layout, wp_get_post_content_block_attributes() );
	}

	public function test_wp_get_post_content_block_attributes_no_layout() {
		switch_theme( 'block-theme-post-content-default' );

		$this->assertSame( array(), wp_get_post_content_block_attributes() );
	}

	/**
	 * @ticket 53458
	 */
	public function test_get_block_editor_settings_theme_json_settings() {
		switch_theme( 'block-theme' );

		$post_editor_context = new WP_Block_Editor_Context( array( 'post' => get_post() ) );

		$settings = get_block_editor_settings( array(), $post_editor_context );

		// Related entry in theme.json: settings.color.palette
		$this->assertSameSetsWithIndex(
			array(
				array(
					'slug'  => 'light',
					'name'  => 'Light',
					'color' => '#f5f7f9',
				),
				array(
					'slug'  => 'dark',
					'name'  => 'Dark',
					'color' => '#000',
				),
			),
			$settings['colors']
		);
		// settings.color.gradients
		$this->assertSameSetsWithIndex(
			array(
				array(
					'name'     => 'Custom gradient',
					'gradient' => 'linear-gradient(135deg,rgba(0,0,0) 0%,rgb(0,0,0) 100%)',
					'slug'     => 'custom-gradient',
				),
			),
			$settings['gradients']
		);
		// settings.typography.fontSizes
		$this->assertSameSetsWithIndex(
			array(
				array(
					'name' => 'Custom',
					'slug' => 'custom',
					'size' => '100px',
				),
			),
			$settings['fontSizes']
		);
		// settings.color.custom
		$this->assertTrue( $settings['disableCustomColors'] );
		// settings.color.customGradient
		$this->assertTrue( $settings['disableCustomGradients'] );
		// settings.typography.customFontSize
		$this->assertTrue( $settings['disableCustomFontSizes'] );
		// settings.typography.customLineHeight
		$this->assertTrue( $settings['enableCustomLineHeight'] );
		// settings.spacing.enableCustomUnits
		$this->assertSameSets( array( 'rem' ), $settings['enableCustomUnits'] );
		// settings.spacing.customPadding
		$this->assertTrue( $settings['enableCustomSpacing'] );
		// settings.postContentAttributes
		$this->assertSameSets(
			array(
				'layout' => array(
					'type' => 'constrained',
				),
			),
			$settings['postContentAttributes']
		);

		switch_theme( WP_DEFAULT_THEME );
	}

	/**
	 * @ticket 59358
	 */
	public function test_get_block_editor_settings_without_post_content_block() {

		$post_editor_context = new WP_Block_Editor_Context( array( 'post' => get_post() ) );

		$settings = get_block_editor_settings( array(), $post_editor_context );

		$this->assertArrayNotHasKey( 'postContentAttributes', $settings );
	}

	/**
	 * @ticket 52920
	 * @expectedDeprecated block_editor_settings
	 */
	public function test_get_block_editor_settings_deprecated_filter_post_editor() {
		add_filter( 'block_editor_settings', array( $this, 'filter_set_block_editor_settings_post' ), 10, 2 );

		$post_editor_context = new WP_Block_Editor_Context( array( 'post' => get_post() ) );
		$settings            = get_block_editor_settings( array(), $post_editor_context );

		remove_filter( 'block_editor_settings', array( $this, 'filter_set_block_editor_settings_post' ) );

		$this->assertSameSets(
			array(
				'filter' => 'deprecated',
			),
			$settings
		);
	}

	/**
	 * @ticket 52920
	 */
	public function test_block_editor_rest_api_preload_no_paths() {
		$editor_context = new WP_Block_Editor_Context();
		block_editor_rest_api_preload( array(), $editor_context );

		$after = implode( '', wp_scripts()->registered['wp-api-fetch']->extra['after'] );
		$this->assertStringNotContainsString( 'wp.apiFetch.createPreloadingMiddleware', $after );
	}

	/**
	 * @ticket 52920
	 * @expectedDeprecated block_editor_preload_paths
	 */
	public function test_block_editor_rest_api_preload_deprecated_filter_post_editor() {
		function filter_remove_preload_paths( $preload_paths, $post ) {
			if ( empty( $post ) ) {
				return $preload_paths;
			}
			return array();
		}
		add_filter( 'block_editor_preload_paths', 'filter_remove_preload_paths', 10, 2 );

		$post_editor_context = new WP_Block_Editor_Context( array( 'post' => get_post() ) );
		block_editor_rest_api_preload(
			array(
				array( '/wp/v2/blocks', 'OPTIONS' ),
			),
			$post_editor_context
		);

		remove_filter( 'block_editor_preload_paths', 'filter_remove_preload_paths' );

		$after = implode( '', wp_scripts()->registered['wp-api-fetch']->extra['after'] );
		$this->assertStringNotContainsString( 'wp.apiFetch.createPreloadingMiddleware', $after );
	}

	/**
	 * @ticket 52920
	 */
	public function test_block_editor_rest_api_preload_filter_all() {
		function filter_add_preload_paths( $preload_paths, WP_Block_Editor_Context $context ) {
			if ( empty( $context->post ) ) {
				array_push( $preload_paths, array( '/wp/v2/types', 'OPTIONS' ) );
			}

			return $preload_paths;
		}
		add_filter( 'block_editor_rest_api_preload_paths', 'filter_add_preload_paths', 10, 2 );

		$editor_context = new WP_Block_Editor_Context();
		block_editor_rest_api_preload(
			array(
				array( '/wp/v2/blocks', 'OPTIONS' ),
			),
			$editor_context
		);

		remove_filter( 'block_editor_rest_api_preload_paths', 'filter_add_preload_paths' );

		$after = implode( '', wp_scripts()->registered['wp-api-fetch']->extra['after'] );
		$this->assertStringContainsString( 'wp.apiFetch.createPreloadingMiddleware', $after );
		$this->assertStringContainsString( '"\/wp\/v2\/blocks"', $after );
		$this->assertStringContainsString( '"\/wp\/v2\/types"', $after );
	}

	/**
	 * @ticket 54558
	 * @dataProvider data_block_editor_rest_api_preload_adds_missing_leading_slash
	 *
	 * @covers ::block_editor_rest_api_preload
	 *
	 * @param array  $preload_paths The paths to preload.
	 * @param string $expected      The expected substring.
	 */
	public function test_block_editor_rest_api_preload_adds_missing_leading_slash( array $preload_paths, $expected ) {
		block_editor_rest_api_preload( $preload_paths, new WP_Block_Editor_Context() );
		$haystack = implode( '', wp_scripts()->registered['wp-api-fetch']->extra['after'] );
		$this->assertStringContainsString( $expected, $haystack );
	}

	/**
	 * @ticket 57547
	 *
	 * @covers ::get_classic_theme_supports_block_editor_settings
	 */
	public function test_get_classic_theme_supports_block_editor_settings() {
		$font_sizes = array(
			array(
				'name' => 'Small',
				'size' => 12,
				'slug' => 'small',
			),
			array(
				'name' => 'Regular',
				'size' => 16,
				'slug' => 'regular',
			),
		);

		add_theme_support( 'editor-font-sizes', $font_sizes );
		$settings = get_classic_theme_supports_block_editor_settings();
		remove_theme_support( 'editor-font-sizes' );

		$this->assertFalse( $settings['disableCustomColors'], 'Value for array key "disableCustomColors" does not match expectations' );
		$this->assertFalse( $settings['disableCustomFontSizes'], 'Value for array key "disableCustomFontSizes" does not match expectations' );
		$this->assertFalse( $settings['disableCustomGradients'], 'Value for array key "disableCustomGradients" does not match expectations' );
		$this->assertFalse( $settings['disableLayoutStyles'], 'Value for array key "disableLayoutStyles" does not match expectations' );
		$this->assertFalse( $settings['enableCustomLineHeight'], 'Value for array key "enableCustomLineHeight" does not match expectations' );
		$this->assertFalse( $settings['enableCustomSpacing'], 'Value for array key "enableCustomSpacing" does not match expectations' );
		$this->assertFalse( $settings['enableCustomUnits'], 'Value for array key "enableCustomUnits" does not match expectations' );

		$this->assertSame(
			$font_sizes,
			$settings['fontSizes'],
			'Value for array key "fontSizes" does not match expectations'
		);
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function data_block_editor_rest_api_preload_adds_missing_leading_slash() {
		return array(
			'a string without a slash'               => array(
				'preload_paths' => array( 'wp/v2/blocks' ),
				'expected'      => '\/wp\/v2\/blocks',
			),
			'a string with a slash'                  => array(
				'preload_paths' => array( '/wp/v2/blocks' ),
				'expected'      => '\/wp\/v2\/blocks',
			),
			'a string starting with a question mark' => array(
				'preload_paths' => array( '?context=edit' ),
				'expected'      => '/?context=edit',
			),
			'an array with a string without a slash' => array(
				'preload_paths' => array( array( 'wp/v2/blocks', 'OPTIONS' ) ),
				'expected'      => '\/wp\/v2\/blocks',
			),
			'an array with a string with a slash'    => array(
				'preload_paths' => array( array( '/wp/v2/blocks', 'OPTIONS' ) ),
				'expected'      => '\/wp\/v2\/blocks',
			),
			'an array with a string starting with a question mark' => array(
				'preload_paths' => array( array( '?context=edit', 'OPTIONS' ) ),
				'expected'      => '\/?context=edit',
			),
		);
	}
}
