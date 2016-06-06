<?php

/**
 * Helper to access to Acceptor's settings
 *
 * @since 0.1
 */
class AcceptorSettingsHelper {
	private $_settings;

	/**
	 * Contructor
	 *
	 * @since 0.1
	 * @param array $acceptor_settings
	 * @return void
	 */
	function __construct( $acceptor_settings ) {
		$this->_settings = $acceptor_settings;
	}

	/**
	 * Returns 'allow_duplicate_post_title' or predefined value if it's not present
	 *
	 * @since 0.1
	 * @return integer
	 */
	function allow_duplicate_post_title() {
		if ( isset( $this->_settings['allow_duplicate_post_title'] ) ) {
			return absint( $this->_settings['allow_duplicate_post_title'] ) == 1;
		} else {
			return ALLOW_DUPLICATE_POST_TITLE;
		}
	}

	/**
	 * Returns 'save_duplicate_post_title_to_draft' or predefined value if it's not present
	 *
	 * @since 0.1
	 * @return integer
	 */
	function save_duplicate_post_title_to_draft() {
		if ( isset( $this->_settings['save_duplicate_post_title_to_draft'] ) ) {
			return absint( $this->_settings['save_duplicate_post_title_to_draft'] ) == 1;
		} else {
			return SAVE_DUPLICATE_POST_TITLE_TO_DRAFT;
		}
	}

	/**
	 * Returns 'author_id' or predefined value if it's not present
	 *
	 * @since 0.1
	 * @return integer
	 */
	function author_id() {
		if ( isset( $this->_settings['author_id'] ) ) {
			return absint( $this->_settings['author_id'] );
		} else {
			return DEFAULT_AUTHOR_ID;
		}
	}

	/**
	 * Returns 'create_missing_categories' or predefined value if it's not present
	 *
	 * @since 0.1
	 * @return integer
	 */
	function create_missing_categories() {
		if ( isset( $this->_settings['create_missing_categories'] ) ) {
			return absint( $this->_settings['create_missing_categories'] ) == 1;
		} else {
			return CREATE_MISSING_CATEGORIES;
		}
	}

	/**
	 * Returns 'default_category_id' or predefined value if it's not present
	 *
	 * @since 0.1
	 * @return integer
	 */
	function default_category_id() {
		if ( isset( $this->_settings['default_category_id'] ) ) {
			return absint( $this->_settings['default_category_id'] );
		} else {
			return DEFAULT_CATEGORY_ID;
		}
	}

	/**
	 * Returns 'compare_category_by' or predefined value if it's not present
	 *
	 * @since 0.1
	 * @return string
	 */
	function compare_category_by() {
		if ( isset( $this->_settings['compare_category_by'] ) ) {
			if ( in_array( esc_attr( $this->_settings['compare_category_by'] ), array( 'slug', 'name' ) ) ) {
				return esc_attr( $this->_settings['compare_category_by'] );
			} else {
				return 'slug';
			}
		} else {
			return COMPARE_CATEGORY_BY;
		}
	}
}


