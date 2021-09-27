<?php
/**
 * Class file for the Translation Tools Update Translations.
 *
 * @package Translation_Tools
 *
 * @since 1.0.0
 */

namespace Translation_Tools;

use Gettext\Translations as Translations;
use WP_Error;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\Update_Translations' ) ) {

	/**
	 * Class Update_Translations.
	 */
	class Update_Translations {


		/**
		 * Gettext.
		 *
		 * @var object
		 */
		protected $gettext;


		/**
		 * Constructor.
		 */
		public function __construct() {

			// Instantiate Translation Tools Gettext.
			$this->gettext = new Gettext();

		}

		// TODO: Message for when the downloaded .po has no strings.


		/**
		 * Update project translation.
		 * Download .po file, extract with Gettext to generate .po and .json files.
		 *
		 * @since 1.0.0
		 * @since 1.5.0  Removed $destination parameter.
		 *               New $type parameter.
		 *
		 * @param string $type             Type of translation project ( e.g.: 'wp', 'plugins', 'themes' ).
		 * @param array  $project          Project array.
		 * @param string $wp_locale        WP Locale ( e.g.: 'pt_PT' ).
		 * @param bool   $generate_po_mo   Whether to download and generate 'po' files. Defaults to true.
		 * @param bool   $generate_json    Whether to generate 'json' files. Defaults to true.
		 * @param bool   $include_domain   Whether to include domain in 'json' files. Defaults to true.
		 *
		 * @return array|WP_Error         Array on success, WP_Error on failure.
		 */
		public function update_translation( $type, $project, $wp_locale, $generate_po_mo = true, $generate_json = true, $include_domain = true ) {

			/**
			 * Filter to set whether to download and generate .po/.mo files on each update. ( true or false ).
			 *
			 * @since 1.5.0
			 */
			$generate_po_mo = apply_filters( 'translation_tools_update_download', $generate_po_mo ) ? true : false;

			/**
			 * Filter to set whether to generate .json files from local .po file on each update. ( true or false ).
			 *
			 * @since 1.5.0
			 */
			$generate_json = apply_filters( 'translation_tools_update_generate_json', $generate_json ) ? true : false;

			// Define variable.
			$result = array();

			// Destination of translation files.
			$destination = Translations_API::get_translation_destination( $type );

			// Set array of log entries.
			$result['log'] = array();

			// Get Translation Tools Locale data.
			$locale = Translations_API::locale( $wp_locale );

			if ( $generate_po_mo ) {

				// Download file from WordPress.org translation table.
				$download = $this->download_translations( $type, $project, $locale );
				// Multiple logs if some subprojects fail.
				foreach ( $download['log'] as $download_log ) {
					array_push( $result['log'], $download_log );
				}
				$result['data'] = $download['data'];
				if ( is_wp_error( $result['data'] ) ) {
					return $result;
				}

				// Generate .po from WordPress.org response.
				$generate_po = $this->generate_po( $destination, $project, $locale, $download['data'] );
				array_push( $result['log'], $generate_po['log'] );
				$result['data'] = $generate_po['data'];
				if ( is_wp_error( $result['data'] ) ) {
					return $result;
				}

				// Extract translations from file.
				$translations = $this->extract_translations( $destination, $project, $locale );
				array_push( $result['log'], $translations['log'] );
				$result['data'] = $translations['data'];
				if ( is_wp_error( $result['data'] ) ) {
					return $result;
				}

				// Generate .mo file from extracted translations.
				$generate_mo = $this->generate_mo( $destination, $project, $locale, $translations['data'] );
				array_push( $result['log'], $generate_mo['log'] );
				$result['data'] = $generate_mo['data'];
				if ( is_wp_error( $result['data'] ) ) {
					return $result;
				}
			}

			if ( $generate_json ) {

				// Avoid extract again if was already extracted on generating .po/.mo files.
				if ( ! isset( $translations ) ) {

					// Extract translations from file.
					$translations = $this->extract_translations( $destination, $project, $locale );
					array_push( $result['log'], $translations['log'] );
					$result['data'] = $translations['data'];
					if ( is_wp_error( $result['data'] ) ) {
						return $result;
					}
				}

				// Generate .json files from extracted translations.
				$generate_jsons = $this->gettext->make_json( $destination, $project, $locale, $translations['data'], $include_domain );
				$result['log']  = array_merge( $result['log'], $generate_jsons['log'] );
				$result['data'] = $generate_jsons['data'];
				if ( is_wp_error( $result['data'] ) ) {
					return $result;
				}
			}

			if ( $generate_po_mo || $generate_json ) {

				array_push( $result['log'], esc_html__( 'Translation updated successfully.', 'translation-tools' ) );

			}

			return $result;

		}


		/**
		 * Download file from WordPress.org translation table.
		 * The downloaded .po files differ from the ones included in language packs:
		 *  - .po files downloaded from translate.wp.org include all the strings.
		 *  - .po files included in the language packs don't include strings that are exclusive to JavaScript source files, those strings are included in the .json files.
		 *
		 * @since 1.0.0
		 * @since 1.5.0  New $type parameter.
		 *
		 * @param string $type      Type of translation project ( e.g.: 'wp', 'plugins', 'themes' ).
		 * @param array  $project   Project array.
		 * @param object $locale    Locale object.
		 *
		 * @return array|WP_Error   Array on success, WP_Error on failure.
		 */
		public function download_translations( $type, $project, $locale ) {

			// Set translation data path.
			$sources = Translations_API::translation_path( $type, $project, $locale );

			// Define variable.
			$result = array();

			// Define log array.
			$result['log'] = array();

			// Define variable.
			$response = '';

			// Get the translation data.
			foreach ( $sources as $source ) {

				// Report message.
				$result['log'][] = sprintf(
					/* translators: %s: URL. */
					esc_html__( 'Downloading translation from %s…', 'translation-tools' ),
					'<code>' . esc_html( $source ) . '</code>'
				);

				$response = wp_remote_get( $source );

				if ( ! is_array( $response ) || 'application/octet-stream' !== $response['headers']['content-type'] ) {

					// Report message.
					$result['log'][] = esc_html__( 'Translation project not found.', 'translation-tools' );

					// Translation project not found, try the next one.
					continue;

				}

				// Translation project found, skip the rest of the sources.
				break;

			}

			if ( ! is_array( $response ) || 'application/octet-stream' !== $response['headers']['content-type'] ) {

				// Report message.
				$result['data'] = new WP_Error(
					'download-translation',
					sprintf(
						'%s %s',
						esc_html__( 'Download failed.', 'translation-tools' ),
						esc_html__( 'A valid URL was not provided.', 'translation-tools' )
					)
				);
				return $result;

			}

			$result['data'] = $response;

			return $result;

		}


		/**
		 * Generate .po from WordPress.org response.
		 *
		 * The .po files obtained include ALL the strings, including the strings from .js files.
		 * These files are different from the ones provided by the language packs, that don't include strings that belong exclusively to .json files.
		 *
		 * @since 1.0.0
		 * @since 1.2.0  Use Locale object.
		 *
		 * @param string $destination   Local destination of the language file. ( e.g: local/site/wp-content/languages/ ).
		 * @param array  $project       Project array.
		 * @param object $locale        Locale object.
		 * @param array  $response      HTTP response.
		 *
		 * @return array|WP_Error       Array on success, WP_Error on failure.
		 */
		public function generate_po( $destination, $project, $locale, $response ) {

			// Set the file naming convention. ( e.g.: {domain}-{locale}.po ).
			$domain    = $project['Domain'] ? $project['Domain'] . '-' : '';
			$file_name = $domain . $locale->wp_locale . '.po';

			// Define variable.
			$result = array();

			// Report message.
			$result['log'] = sprintf(
				/* translators: %s: File name. */
				esc_html__( 'Saving file %s…', 'translation-tools' ),
				'<code>' . esc_html( $file_name ) . '</code>'
			);

			// Generate .po file.
			$success = file_put_contents( $destination . $file_name, $response['body'] ); // phpcs:ignore

			if ( ! $success ) {

				// Report message.
				$result['data'] = new WP_Error(
					'generate-po',
					esc_html__( 'Could not create file.', 'translation-tools' )
				);
				return $result;

			}

			$result['data'] = true;

			return $result;

		}


		/**
		 * Extract translations from file.
		 *
		 * @since 1.0.0
		 * @since 1.2.0  Use Locale object.
		 *
		 * @param string $destination   Local destination of the language file. ( e.g: local/site/wp-content/languages/ ).
		 * @param array  $project       Project array.
		 * @param object $locale        Locale object.
		 *
		 * @return array|WP_Error       Array on success, WP_Error on failure.
		 */
		public function extract_translations( $destination, $project, $locale ) {

			// Set the file naming convention. ( e.g.: {domain}-{locale}.po ).
			$domain    = $project['Domain'] ? $project['Domain'] . '-' : '';
			$file_name = $domain . $locale->wp_locale . '.po';

			// Define variable.
			$result = array();

			// Report message.
			$result['log'] = sprintf(
				/* translators: %s: File name. */
				esc_html__( 'Extracting translations from file %s…', 'translation-tools' ),
				'<code>' . esc_html( $file_name ) . '</code>'
			);

			// Check if .po file exist.
			if ( ! is_file( $destination . $file_name ) ) {

				// Report message.
				$result['data'] = new WP_Error(
					'extract-translations',
					esc_html__( 'File not found.', 'translation-tools' )
				);

				return $result;
			}

			$translations = Translations::fromPoFile( $destination . $file_name );

			if ( ! is_object( $translations ) ) {

				// Report message.
				$result['data'] = new WP_Error(
					'extract-translations',
					esc_html__( 'Could not extract translations from file.', 'translation-tools' )
				);

				return $result;

			}

			$result['data'] = $translations;

			return $result;

		}


		/**
		 * Generate .mo file from extracted translations.
		 *
		 * @since 1.0.0
		 * @since 1.2.0  Use Locale object.
		 *
		 * @param string $destination    Local destination of the language file. ( e.g: local/site/wp-content/languages/ ).
		 * @param array  $project        Project array.
		 * @param object $locale         Locale object.
		 * @param object $translations   Extracted translations to export.
		 *
		 * @return array|WP_Error        Array on success, WP_Error on failure.
		 */
		public function generate_mo( $destination, $project, $locale, $translations ) {

			// Set the file naming convention. ( e.g.: {domain}-{locale}.po ).
			$domain    = $project['Domain'] ? $project['Domain'] . '-' : '';
			$file_name = $domain . $locale->wp_locale . '.mo';

			// Define variable.
			$result = array();

			// Report message.
			$result['log'] = sprintf(
				/* translators: %s: File name. */
				esc_html__( 'Saving file %s…', 'translation-tools' ),
				'<code>' . $file_name . '</code>'
			);

			// Generate .mo file.
			$generate = $translations->toMoFile( $destination . $file_name );

			if ( ! $generate ) {

				// Report message.
				$result['data'] = new WP_Error(
					'generate-mo',
					esc_html__( 'Could not create file.', 'translation-tools' )
				);
				return $result;

			}

			$result['data'] = true;

			return $result;

		}

	}

}
