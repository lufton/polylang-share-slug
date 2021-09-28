<?php
/**
 * Plugin Name: Polylang Share Slug
 * Plugin URI: https://github.com/lufton/polylang-share-slug/
 * Description: Allow same slugs for different languages in Polylang.
 * Version: 1.0.0
 * Author: Lufton
 * Author URI: https://lufton.space
 */

class PolylangShareSlugTerm {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * Instance of a child class of PLL_Links_Model.
	 *
	 * @var PLL_Links_Model
	 */
	public $links_model;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param object $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->options     = &$polylang->options;
		$this->model       = &$polylang->model;
		$this->links_model = &$polylang->links_model;

		add_action( 'created_term', array( $this, 'save_term' ), 1, 3 );
		add_action( 'edited_term', array( $this, 'save_term' ), 1, 3 );
	}

	/**
	 * Will make slug unique per language and taxonomy
	 * Mostly taken from wp_unique_term_slug
	 *
	 * @since 1.9
	 *
	 * @param string  $slug The string that will be tried for a unique slug.
	 * @param string  $lang Language slug.
	 * @param WP_Term $term The term object that the $slug will belong too.
	 * @return string Will return a true unique slug.
	 */
	protected function unique_term_slug( $slug, $lang, $term ) {
		global $wpdb;

		$original_slug = $slug; // Save this for the filter at the end.

		// Quick check.
		if ( ! $this->model->term_exists_by_slug( $slug, $lang, $term->taxonomy ) ) {
			/** This filter is documented in /wordpress/wp-includes/taxonomy.php */
			return apply_filters( 'wp_unique_term_slug', $slug, $term, $original_slug );
		}

		/*
		 * As done by WP in term_exists except that we use our own term_exist.
		 * If the taxonomy supports hierarchy and the term has a parent,
		 * make the slug unique by incorporating parent slugs.
		 */
		if ( is_taxonomy_hierarchical( $term->taxonomy ) && ! empty( $term->parent ) ) {
			$the_parent = $term->parent;
			while ( ! empty( $the_parent ) ) {
				$parent_term = get_term( $the_parent, $term->taxonomy );
				if ( ! $parent_term instanceof WP_Term ) {
					break;
				}
				$slug .= '-' . $parent_term->slug;
				if ( ! $this->model->term_exists_by_slug( $slug, $lang ) ) { // Calls our own term_exists.
					/** This filter is documented in /wordpress/wp-includes/taxonomy.php */
					return apply_filters( 'wp_unique_term_slug', $slug, $term, $original_slug );
				}

				if ( empty( $parent_term->parent ) ) {
					break;
				}
				$the_parent = $parent_term->parent;
			}
		}

		// If we didn't get a unique slug, try appending a number to make it unique.
		if ( ! empty( $term->term_id ) ) {
			$query = $wpdb->prepare( "SELECT slug FROM {$wpdb->terms} WHERE slug = %s AND term_id != %d", $slug, $term->term_id );
		}
		else {
			$query = $wpdb->prepare( "SELECT slug FROM {$wpdb->terms} WHERE slug = %s", $slug );
		}

		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $wpdb->get_var( $query ) ) {
			$num = 2;
			do {
				$alt_slug = $slug . "-$num";
				$num++;
				$slug_check = $wpdb->get_var( $wpdb->prepare( "SELECT slug FROM {$wpdb->terms} WHERE slug = %s", $alt_slug ) );
			} while ( $slug_check );
			$slug = $alt_slug;
		}

		/** This filter is documented in /wordpress/wp-includes/taxonomy.php */
		return apply_filters( 'wp_unique_term_slug', $slug, $term, $original_slug );
	}

	/**
	 * Ugly hack to enable the same slug in several languages
	 *
	 * @since 1.9
	 *
	 * @param int    $term_id  The term id of a saved term.
	 * @param int    $tt_id    The term taxononomy id.
	 * @param string $taxonomy The term taxonomy.
	 * @return void
	 */
	public function save_term( $term_id, $tt_id, $taxonomy ) {
		global $wpdb;

		// Does nothing except on taxonomies which are filterable.
		if ( ! $this->model->is_translated_taxonomy( $taxonomy ) || 0 === $this->options['force_lang'] ) {
			return;
		}

		$term = get_term( $term_id, $taxonomy );

		if ( ! ( $term instanceof WP_Term ) || false === ( $pos = strpos( $term->slug, '___' ) ) ) {
			return;
		}

		$slug = substr( $term->slug, 0, $pos );
		$lang = substr( $term->slug, $pos + 3 );

		// Need to check for unique slug as we tricked wp_unique_term_slug from WP.
		$slug = $this->unique_term_slug( $slug, $lang, (object) $term );
		$wpdb->update( $wpdb->terms, compact( 'slug' ), compact( 'term_id' ) );
		clean_term_cache( $term_id, $taxonomy );
	}
}

class PolylangShareSlugPost {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var PLL_Model
	 */
	public $model;

	/**
	 * @var PLL_Links_Model
	 */
	public $links_model;

	/**
	 * The current language.
	 *
	 * @var PLL_Language
	 */
	public $curlang;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param object $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		$this->options     = &$polylang->options;
		$this->model       = &$polylang->model;
		$this->links_model = &$polylang->links_model;
		$this->curlang     = &$polylang->curlang;

		// Get page by pagename and lang.
		add_action( 'parse_query', array( $this, 'parse_query' ), 0 ); // Before all other functions hooked to 'parse_query'.

		// Get post by name and lang.
		add_filter( 'posts_join', array( $this, 'posts_join' ), 10, 2 );
		add_filter( 'posts_where', array( $this, 'posts_where' ), 10, 2 );

		add_filter( 'wp_unique_post_slug', array( $this, 'wp_unique_post_slug' ), 10, 6 );
		add_action( 'pll_translate_media', array( $this, 'pll_translate_media' ), 20, 3 ); // After PLL_Admin_Sync to avoid reverse sync.
	}

	/**
	 * Modifies the query object when a page is queried by slug and language
	 * This must be the first function hooked to 'parse_query' to run so that others get the right queried page
	 *
	 * @since 1.9
	 *
	 * @param WP_Query $query Reference to a WP_Query object.
	 * @return void
	 */
	public function parse_query( $query ) {
		if ( $lang = $this->get_language_for_filter( $query ) ) {
			$qv = $query->query_vars;

			// For hierarchical custom post types.
			if ( empty( $qv['pagename'] ) && ! empty( $qv['name'] ) && ! empty( $qv['post_type'] ) && array_intersect( get_post_types( array( 'hierarchical' => true ) ), (array) $qv['post_type'] ) ) {
				$qv['pagename'] = $qv['name'];
			}

			if ( ! empty( $qv['pagename'] ) ) {
				/*
				 * A simpler solution is available at https://github.com/mirsch/polylang-slug/commit/4bf2cb80256fc31347455f6539fac0c20f403c04
				 * But it supposes that pages sharing slug are translations of each other which we don't.
				 */
				$queried_object = $this->get_page_by_path( $qv['pagename'], $lang->slug, OBJECT, empty( $qv['post_type'] ) ? 'page' : $qv['post_type'] );

				// If we got nothing or an attachment, check if we also have a post with the same slug. See https://core.trac.wordpress.org/ticket/24612
				if ( empty( $qv['post_type'] ) && ( empty( $queried_object ) || 'attachment' === $queried_object->post_type ) && preg_match( '/^[^%]*%(?:postname)%/', get_option( 'permalink_structure' ) ) ) {
					$post = $this->get_page_by_path( $qv['pagename'], $lang->slug, OBJECT, 'post' );
					if ( $post ) {
						$queried_object = $post;
					}
				}

				if ( ! empty( $queried_object ) ) {
					$query->queried_object    = $queried_object;
					$query->queried_object_id = (int) $queried_object->ID;
				}
			}
		}
	}

	/**
	 * Retrieves a page given its path.
	 * This is the same function as WP get_page_by_path()
	 * Rewritten to make it language dependent
	 *
	 * @since 1.9
	 *
	 * @param string          $page_path Page path.
	 * @param string          $lang      Language slug.
	 * @param string          $output    Optional. Output type. Accepts OBJECT, ARRAY_N, or ARRAY_A. Default OBJECT.
	 * @param string|string[] $post_type Optional. Post type or array of post types. Default 'page'.
	 * @return WP_Post|null WP_Post on success or null on failure.
	 */
	protected function get_page_by_path( $page_path, $lang, $output = OBJECT, $post_type = 'page' ) {
		global $wpdb;

		$page_path = rawurlencode( urldecode( $page_path ) );
		$page_path = str_replace( '%2F', '/', $page_path );
		$page_path = str_replace( '%20', ' ', $page_path );
		$parts = explode( '/', trim( $page_path, '/' ) );
		$parts = array_map( 'sanitize_title_for_query', $parts );
		$escaped_parts = esc_sql( $parts );

		$in_string = "'" . implode( "','", $escaped_parts ) . "'";

		if ( is_array( $post_type ) ) {
			$post_types = $post_type;
		} else {
			$post_types = array( $post_type, 'attachment' );
		}

		$post_types = esc_sql( $post_types );
		$post_type_in_string = "'" . implode( "','", $post_types ) . "'";
		$sql  = "SELECT ID, post_name, post_parent, post_type FROM {$wpdb->posts}";
		$sql .= $this->model->post->join_clause();
		$sql .= " WHERE post_name IN ( {$in_string} ) AND post_type IN ( {$post_type_in_string} )";
		$sql .= $this->model->post->where_clause( $lang );

		// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
		$pages = $wpdb->get_results( $sql, OBJECT_K );

		$revparts = array_reverse( $parts );

		$foundid = 0;
		foreach ( (array) $pages as $page ) {
			if ( $page->post_name == $revparts[0] ) {
				$count = 0;
				$p = $page;
				while ( 0 != $p->post_parent && isset( $pages[ $p->post_parent ] ) ) {
					$count++;
					$parent = $pages[ $p->post_parent ];
					if ( ! isset( $revparts[ $count ] ) || $parent->post_name != $revparts[ $count ] ) {
						break;
					}
					$p = $parent;
				}

				if ( 0 == $p->post_parent && count( $revparts ) == $count + 1 && $p->post_name == $revparts[ $count ] ) {
					$foundid = $page->ID;
					if ( $page->post_type == $post_type ) {
						break;
					}
				}
			}
		}

		if ( $foundid ) {
			return get_post( $foundid, $output );
		}

		return null;
	}

	/**
	 * Adds our join clause to sql query.
	 * Useful when querying a post by name.
	 *
	 * @since 1.9
	 *
	 * @param string   $join  Original join clause.
	 * @param WP_Query $query The WP_Query object.
	 * @return string Modified join clause.
	 */
	public function posts_join( $join, $query ) {
		if ( $this->get_language_for_filter( $query ) ) {
			return $join . $this->model->post->join_clause();
		}
		return $join;
	}

	/**
	 * Adds our where clause to sql query.
	 * Useful when querying a post by name.
	 *
	 * @since 1.9
	 *
	 * @param string   $where Original where clause.
	 * @param WP_Query $query The WP_Query object.
	 * @return string Modified where clause.
	 */
	public function posts_where( $where, $query ) {
		if ( $language = $this->get_language_for_filter( $query ) ) {
			return $where . $this->model->post->where_clause( $language );
		}
		return $where;
	}

	/**
	 * Checks if the query must be filtered or not
	 *
	 * @since 1.9
	 *
	 * @param WP_Query $query The WP_Query object.
	 * @return PLL_Language|false The language to use for the filter, false if the query should be kept unfiltered.
	 */
	protected function get_language_for_filter( $query ) {
		$qv = $query->query_vars;

		$post_type = empty( $qv['post_type'] ) ? 'post' : $qv['post_type'];

		if ( ( ! empty( $qv['name'] ) || ! empty( $qv['pagename'] ) ) && $this->model->is_translated_post_type( $post_type ) ) {
			if ( ! empty( $qv['lang'] ) ) {
				return $this->model->get_language( $qv['lang'] );
			}

			if ( isset( $qv['tax_query'] ) && is_array( $qv['tax_query'] ) ) {
				foreach ( $qv['tax_query'] as $tax_query ) {
					if ( isset( $tax_query['taxonomy'] ) && 'language' === $tax_query['taxonomy'] ) {
						// We can't use directly PLL_Model::get_language() as it doesn't accept a term_taxonomy_id.
						foreach ( $this->model->get_languages_list() as $lang ) {
							if ( $lang->term_taxonomy_id === $tax_query['terms'] ) {
								return $lang;
							}
						}
					}
				}
			}

			if ( ! empty( $this->curlang ) ) {
				return $this->curlang;
			}
		}
		return false;
	}

	/**
	 * Checks if the slug is unique within language.
	 * Thanks to @AndyDeGroo for https://wordpress.org/support/topic/plugin-polylang-identical-page-names-in-different-languages?replies=8#post-2669927
	 * Thanks to Ulrich Pogson for https://github.com/grappler/polylang-slug/blob/master/polylang-slug.php
	 *
	 * @since 1.9
	 *
	 * @param string $slug          The slug defined by wp_unique_post_slug in WP
	 * @param int    $post_ID       The post id.
	 * @param string $post_status   Not used.
	 * @param string $post_type     The Post type.
	 * @param int    $post_parent   The id of the post parent.
	 * @param string $original_slug The original slug before it is modified by wp_unique_post_slug in WP.
	 * @return string Original slug if it is unique in the language or the modified slug otherwise.
	 */
	public function wp_unique_post_slug( $slug, $post_ID, $post_status, $post_type, $post_parent, $original_slug ) {
		global $wpdb;

		// Return slug if it was not changed.
		if ( $original_slug === $slug || 0 === $this->options['force_lang'] || ! $this->model->is_translated_post_type( $post_type ) ) {
			return $slug;
		}

		$lang = $this->model->post->get_language( $post_ID );

		if ( empty( $lang ) ) {
			return $slug;
		}

		if ( 'attachment' == $post_type ) {
			// Attachment slugs must be unique across all types.
			$sql  = "SELECT post_name FROM {$wpdb->posts}";
			$sql .= $this->model->post->join_clause();
			$sql .= $wpdb->prepare( ' WHERE post_name = %s AND ID != %d', $original_slug, $post_ID );
			$sql .= $this->model->post->where_clause( $lang ) . ' LIMIT 1';

			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$post_name_check = $wpdb->get_var( $sql );
		}

		elseif ( is_post_type_hierarchical( $post_type ) ) {
			// Page slugs must be unique within their own trees. Pages are in a separate namespace than posts so page slugs are allowed to overlap post slugs.
			$sql  = "SELECT ID FROM {$wpdb->posts}";
			$sql .= $this->model->post->join_clause();
			$sql .= $wpdb->prepare( " WHERE post_name = %s AND post_type IN ( %s, 'attachment' ) AND ID != %d AND post_parent = %d", $original_slug, $post_type, $post_ID, $post_parent );
			$sql .= $this->model->post->where_clause( $lang ) . ' LIMIT 1';

			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$post_name_check = $wpdb->get_var( $sql );
		}

		else {
			// Post slugs must be unique across all posts.
			$sql  = "SELECT post_name FROM {$wpdb->posts}";
			$sql .= $this->model->post->join_clause();
			$sql .= $wpdb->prepare( ' WHERE post_name = %s AND post_type = %s AND ID != %d', $original_slug, $post_type, $post_ID );
			$sql .= $this->model->post->where_clause( $lang ) . ' LIMIT 1';

			// PHPCS:ignore WordPress.DB.PreparedSQL.NotPrepared
			$post_name_check = $wpdb->get_var( $sql );
		}

		return $post_name_check ? $slug : $original_slug;
	}

	/**
	 * Updates the attachment slug when creating a translation to allow to share slugs
	 * This second step is needed because wp_unique_post_slug is called before the language is set
	 *
	 * @since 1.9
	 *
	 * @param int $post_id Original attachment id.
	 * @param int $tr_id   Translated attachment id.
	 * @return void
	 */
	public function pll_translate_media( $post_id, $tr_id ) {
		$post = get_post( $post_id );
		if ( $post ) {
			wp_update_post( array( 'ID' => $tr_id, 'post_name' => $post->post_name ) );
		}
	}
}

class PolylangShareSlugTermAdmin extends PolylangShareSlugTerm {
	/**
	 * Stores the name of a term being saved.
	 *
	 * @var string
	 */
	protected $pre_term_name;

	/**
	 * The id of the current post being updated.
	 *
	 * @var int
	 */
	protected $post_id;

	/**
	 * Constructor
	 *
	 * @since 1.9
	 *
	 * @param object $polylang Polylang object.
	 */
	public function __construct( &$polylang ) {
		parent::__construct( $polylang );

		add_action( 'pre_post_update', array( $this, 'pre_post_update' ), 5 );
		add_filter( 'pre_term_name', array( $this, 'pre_term_name' ), 5 );
		add_filter( 'pre_term_slug', array( $this, 'pre_term_slug' ), 5, 2 );
	}

	/**
	 * Stores the name of a term being saved, for use in the filter pre_term_slug
	 *
	 * @since 1.9
	 *
	 * @param string $name The term name to store.
	 * @return string Unmodified term name.
	 */
	public function pre_term_name( $name ) {
		return $this->pre_term_name = $name;
	}

	/**
	 * Stores the current post_id when bulk editing posts for use in save_language and pre_term_slug
	 *
	 * @since 1.9
	 *
	 * @param int $post_id The id of the current post being updated.
	 * @return void
	 */
	public function pre_post_update( $post_id ) {
		if ( isset( $_GET['bulk_edit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->post_id = $post_id;
		}
	}

	/**
	 * Creates the term slug in case the term already exists in another language
	 *
	 * @since 1.9
	 *
	 * @param string $slug     The inputed slug of the term being saved, may be empty.
	 * @param string $taxonomy The term taxonomy.
	 * @return string
	 */
	public function pre_term_slug( $slug, $taxonomy ) {
		if ( ! $slug ) {
			$slug = sanitize_title( $this->pre_term_name );
		}

		if ( $this->model->is_translated_taxonomy( $taxonomy ) && term_exists( $slug, $taxonomy ) ) {
			$parent = 0;

			if ( isset( $_POST['term_lang_choice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$lang = $this->model->get_language( sanitize_key( $_POST['term_lang_choice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

				if ( isset( $_POST['parent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$parent = intval( $_POST['parent'] ); // phpcs:ignore WordPress.Security.NonceVerification
				} elseif ( isset( $_POST[ "new{$taxonomy}_parent" ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$parent = intval( $_POST[ "new{$taxonomy}_parent" ] ); // phpcs:ignore WordPress.Security.NonceVerification
				}
			}

			elseif ( isset( $_POST['inline_lang_choice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$lang = $this->model->get_language( sanitize_key( $_POST['inline_lang_choice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			// *Post* bulk edit, in case a new term is created.
			elseif ( isset( $_GET['bulk_edit'], $_GET['inline_lang_choice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				// Bulk edit does not modify the language.
				if ( -1 == $_GET['inline_lang_choice'] ) { // phpcs:ignore WordPress.Security.NonceVerification
					$lang = $this->model->post->get_language( $this->post_id );
				} else {
					$lang = $this->model->get_language( sanitize_key( $_GET['inline_lang_choice'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				}
			}

			// Special cases for default categories as the select is disabled.
			elseif ( ! empty( $_POST['tag_ID'] ) && in_array( get_option( 'default_category' ), $this->model->term->get_translations( (int) $_POST['tag_ID'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$lang = $this->model->term->get_language( (int) $_POST['tag_ID'] ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			elseif ( ! empty( $_POST['tax_ID'] ) && in_array( get_option( 'default_category' ), $this->model->term->get_translations( (int) $_POST['tax_ID'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$lang = $this->model->term->get_language( (int) $_POST['tax_ID'] ); // phpcs:ignore WordPress.Security.NonceVerification
			}

			if ( ! empty( $lang ) ) {
				$term_id = $this->model->term_exists_by_slug( $slug, $lang, $taxonomy, $parent );

				// If no term exists or if we are editing the existing term, trick WP to allow shared slugs.
				if ( ! $term_id || ( ! empty( $_POST['tag_ID'] ) && $_POST['tag_ID'] == $term_id ) || ( ! empty( $_POST['tax_ID'] ) && $_POST['tax_ID'] == $term_id ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$slug .= '___' . $lang->slug;
				}
			}
		}

		return $slug;
	}
}

function polylang_share_slug_init(&$polylang) {
	if ( $polylang->model->get_languages_list() && get_option( 'permalink_structure' ) && $polylang->options['force_lang'] ) {
		// Share post slugs.
		$polylang->share_post_slug = new PolylangShareSlugPost( $polylang );

		// Share term slugs.
		if ( $polylang instanceof PLL_Admin ) {
			$polylang->share_term_slug = new PolylangShareSlugTermAdmin( $polylang );
		} else {
			$polylang->share_term_slug = new PolylangShareSlugTerm( $polylang );
		}
	}
}
add_action( 'pll_init', 'polylang_share_slug_init', 10, 1 );