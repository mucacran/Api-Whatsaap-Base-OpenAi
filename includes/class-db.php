<?php
/**
 * Database tables and small data helpers.
 *
 * @package MucacranWaAi
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the two custom tables used by the chat window.
 */
class Mucacran_Wa_Ai_DB {

	const DB_VERSION_OPTION = 'mucacran_wa_ai_db_version';
	const DB_VERSION        = '1';

	/**
	 * Creates or updates the plugin tables.
	 *
	 * @return void
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate     = $wpdb->get_charset_collate();
		$conversations_table = self::conversations_table();
		$messages_table      = self::messages_table();

		/*
		 * This table stores each conversation.
		 * One row represents one WhatsApp contact.
		 */
		$conversations_sql = "CREATE TABLE {$conversations_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			wa_id varchar(64) NOT NULL,
			contact_phone varchar(64) NOT NULL,
			contact_name varchar(190) DEFAULT '',
			last_message text NULL,
			last_message_at datetime NULL,
			unread_admin int(11) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY wa_id (wa_id),
			KEY last_message_at (last_message_at)
		) {$charset_collate};";

		/*
		 * This table stores every message in a conversation.
		 * Incoming rows come from WhatsApp, outgoing rows are sent by the admin or AI.
		 */
		$messages_sql = "CREATE TABLE {$messages_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			conversation_id bigint(20) unsigned NOT NULL,
			direction varchar(20) NOT NULL DEFAULT 'incoming',
			sender_type varchar(20) NOT NULL DEFAULT 'user',
			message_type varchar(30) NOT NULL DEFAULT 'text',
			message_body longtext NULL,
			whatsapp_message_id varchar(190) DEFAULT '',
			raw_payload longtext NULL,
			delivery_status varchar(50) DEFAULT '',
			error_message text NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY conversation_id (conversation_id),
			KEY whatsapp_message_id (whatsapp_message_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $conversations_sql );
		dbDelta( $messages_sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION, false );
	}

	/**
	 * Creates the tables when this plugin was updated while already active.
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		if ( self::DB_VERSION !== get_option( self::DB_VERSION_OPTION ) ) {
			self::activate();
		}
	}

	/**
	 * @return string
	 */
	public static function conversations_table() {
		global $wpdb;

		return $wpdb->prefix . 'mucacran_wa_ai_conversations';
	}

	/**
	 * @return string
	 */
	public static function messages_table() {
		global $wpdb;

		return $wpdb->prefix . 'mucacran_wa_ai_messages';
	}

	/**
	 * Finds an existing conversation or creates it for a WhatsApp contact.
	 *
	 * @param string $wa_id WhatsApp ID.
	 * @param string $phone Contact phone.
	 * @param string $name  Contact name.
	 * @return int
	 */
	public static function find_or_create_conversation( $wa_id, $phone, $name = '' ) {
		global $wpdb;

		$table = self::conversations_table();
		$now   = current_time( 'mysql' );

		$conversation_id = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE wa_id = %s LIMIT 1", $wa_id )
		);

		if ( $conversation_id > 0 ) {
			$wpdb->update(
				$table,
				array(
					'contact_phone' => $phone,
					'contact_name'  => $name,
					'updated_at'    => $now,
				),
				array( 'id' => $conversation_id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);

			return $conversation_id;
		}

		$wpdb->insert(
			$table,
			array(
				'wa_id'          => $wa_id,
				'contact_phone'  => $phone,
				'contact_name'   => $name,
				'last_message'   => '',
				'last_message_at' => $now,
				'unread_admin'   => 0,
				'created_at'     => $now,
				'updated_at'     => $now,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	/**
	 * Saves a message and updates the conversation preview.
	 *
	 * @param array $message Message values.
	 * @return int
	 */
	public static function insert_message( $message ) {
		global $wpdb;

		$now       = current_time( 'mysql' );
		$direction = sanitize_key( $message['direction'] ?? 'incoming' );
		$body      = sanitize_textarea_field( $message['message_body'] ?? '' );

		$wpdb->insert(
			self::messages_table(),
			array(
				'conversation_id'     => absint( $message['conversation_id'] ?? 0 ),
				'direction'           => in_array( $direction, array( 'incoming', 'outgoing' ), true ) ? $direction : 'incoming',
				'sender_type'         => sanitize_key( $message['sender_type'] ?? 'user' ),
				'message_type'        => sanitize_key( $message['message_type'] ?? 'text' ),
				'message_body'        => $body,
				'whatsapp_message_id' => sanitize_text_field( $message['whatsapp_message_id'] ?? '' ),
				'raw_payload'         => isset( $message['raw_payload'] ) ? wp_json_encode( $message['raw_payload'] ) : '',
				'delivery_status'     => sanitize_text_field( $message['delivery_status'] ?? '' ),
				'error_message'       => sanitize_textarea_field( $message['error_message'] ?? '' ),
				'created_at'          => $now,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		self::update_conversation_after_message( absint( $message['conversation_id'] ?? 0 ), $body, 'incoming' === $direction );

		return (int) $wpdb->insert_id;
	}

	/**
	 * Updates the list preview after saving a message.
	 *
	 * @param int    $conversation_id Conversation ID.
	 * @param string $last_message    Message preview.
	 * @param bool   $is_unread       Whether admin should see it as unread.
	 * @return void
	 */
	private static function update_conversation_after_message( $conversation_id, $last_message, $is_unread ) {
		global $wpdb;

		if ( $conversation_id <= 0 ) {
			return;
		}

		$table = self::conversations_table();
		$now   = current_time( 'mysql' );
		$sql   = "UPDATE {$table} SET last_message = %s, last_message_at = %s, updated_at = %s";
		$args  = array( $last_message, $now, $now );

		if ( $is_unread ) {
			$sql .= ', unread_admin = unread_admin + 1';
		}

		$sql   .= ' WHERE id = %d';
		$args[] = $conversation_id;

		$wpdb->query( $wpdb->prepare( $sql, $args ) );
	}

	/**
	 * Returns conversations for the left column.
	 *
	 * @return array
	 */
	public static function get_conversations() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM " . self::conversations_table() . ' ORDER BY last_message_at DESC, updated_at DESC LIMIT 100'
		);
	}

	/**
	 * Returns one conversation by ID.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return object|null
	 */
	public static function get_conversation( $conversation_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::conversations_table() . ' WHERE id = %d', absint( $conversation_id ) )
		);
	}

	/**
	 * Returns messages for one conversation.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return array
	 */
	public static function get_messages( $conversation_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::messages_table() . ' WHERE conversation_id = %d ORDER BY created_at ASC LIMIT 300',
				absint( $conversation_id )
			)
		);
	}

	/**
	 * Marks a conversation as read in the admin chat window.
	 *
	 * @param int $conversation_id Conversation ID.
	 * @return void
	 */
	public static function mark_read( $conversation_id ) {
		global $wpdb;

		$wpdb->update(
			self::conversations_table(),
			array( 'unread_admin' => 0 ),
			array( 'id' => absint( $conversation_id ) ),
			array( '%d' ),
			array( '%d' )
		);
	}
}
