<?php
/**
 * Class file for the Translation Tools Globals.
 *
 * @package Translation Tools
 *
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'TTools_Globals' ) ) {

	/**
	 * Class TTools_Globals.
	 */
	class TTools_Globals {


		/**
		 * Check if site locale is 'en_US'.
		 *
		 * @since 1.0.0
		 *
		 * @return bool  True if site locale is 'en_US', false otherwise.
		 */
		public function locale_is_english() {

			// Get Translation Tools language.
			$language = get_locale();

			// Check if user locale is 'en_US'.
			if ( 'en_US' === $language ) {
				return true;
			}

			return false;
		}


		/**
		 * Returns array of allowed HTML elements for use in wp_kses().
		 *
		 * @since 1.0.0
		 *
		 * @return array  Array of allowed HTML elements.
		 */
		public function allowed_html() {
			$allowed_html = array(
				'a'      => array(
					'href'   => array(),
					'title'  => array(),
					'class'  => array(),
					'data'   => array(),
					'rel'    => array(),
					'target' => array(),
				),
				'br'     => array(),
				'button' => array(
					'aria-expanded' => array(),
					'class'         => array(),
					'id'            => array(),
					'type'          => array(),
				),
				'div'    => array(
					'class' => array(),
					'data'  => array(),
					'style' => array(),
				),
				'em'     => array(),
				'form'   => array(
					'action' => array(),
					'class'  => array(),
					'method' => array(),
					'name'   => array(),
				),
				'img'    => array(
					'alt'    => array(),
					'class'  => array(),
					'height' => array(),
					'src'    => array(),
					'width'  => array(),
				),
				'input'  => array(
					'class' => array(),
					'name'  => array(),
					'type'  => array(),
					'value' => array(),
				),
				'li'     => array(
					'class' => array(),
				),
				'ol'     => array(
					'class' => array(),
				),
				'option' => array(
					'value'    => array(),
					'selected' => array(),
				),
				'p'      => array(
					'class' => array(),
				),
				'script' => array(),
				'select' => array(
					'id'    => array(),
					'class' => array(),
					'name'  => array(),
				),
				'span'   => array(
					'class' => array(),
					'style' => array(),
				),
				'strong' => array(),
				'style'  => array(),

				'ul'     => array(
					'class' => array(),
				),
			);

			return $allowed_html;
		}

	}

}