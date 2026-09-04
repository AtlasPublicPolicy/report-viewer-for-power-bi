<?php
/**
 * AES-256-CBC encryption for sensitive plugin options.
 *
 * Uses AUTH_KEY from wp-config.php as the key source, so encrypted values
 * are unique per WordPress installation and cannot be decrypted elsewhere.
 */

defined( 'ABSPATH' ) || exit;

class RVPBI_Crypto {

	private const CIPHER = 'aes-256-cbc';
	private const PREFIX = '$rvpbi_enc_v1$';

	/**
	 * Encrypts a plaintext string.
	 *
	 * @return string Prefixed, base64-encoded ciphertext.
	 */
	public static function encrypt( string $plaintext ): string {
		if ( ! self::available() ) {
			return $plaintext;
		}

		$key = self::derive_key();
		$iv  = openssl_random_pseudo_bytes( 16 );

		$ciphertext = openssl_encrypt( $plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

		if ( $ciphertext === false ) {
			return $plaintext;
		}

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
		return self::PREFIX . base64_encode( $iv . $ciphertext );
	}

	/**
	 * Decrypts a previously encrypted string.
	 *
	 * Returns the original plaintext, or an empty string if decryption fails
	 * (e.g. AUTH_KEY was rotated).
	 */
	public static function decrypt( string $ciphertext ): string {
		if ( ! self::is_encrypted( $ciphertext ) ) {
			return $ciphertext;
		}

		if ( ! self::available() ) {
			return '';
		}

		$key = self::derive_key();

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		$raw = base64_decode( substr( $ciphertext, strlen( self::PREFIX ) ), true );

		if ( $raw === false || strlen( $raw ) < 17 ) {
			return '';
		}

		$iv   = substr( $raw, 0, 16 );
		$data = substr( $raw, 16 );

		$plaintext = openssl_decrypt( $data, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );

		return $plaintext !== false ? $plaintext : '';
	}

	/**
	 * Checks whether a value carries the encrypted prefix.
	 */
	public static function is_encrypted( string $value ): bool {
		return str_starts_with( $value, self::PREFIX );
	}

	/**
	 * Whether the OpenSSL extension is available.
	 */
	private static function available(): bool {
		return function_exists( 'openssl_encrypt' );
	}

	/**
	 * Derives a 256-bit key from the site's AUTH_KEY constant.
	 */
	private static function derive_key(): string {
		return hash( 'sha256', AUTH_KEY . 'rvpbi_credential_encryption', true );
	}
}
