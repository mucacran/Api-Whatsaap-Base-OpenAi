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
	const DB_VERSION        = '2';

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
			lead_category varchar(50) NOT NULL DEFAULT '',
			confidence smallint(5) unsigned NOT NULL DEFAULT 0,
			reason text NULL,
			suggested_reply text NULL,
			raw_classification_response longtext NULL,
			classification_updated_at datetime NULL,
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
	 * Stores the latest internal lead classification for a conversation.
	 *
	 * @param int   $conversation_id Conversation ID.
	 * @param array $classification  Structured classification values.
	 * @return bool
	 */
	public static function update_conversation_classification( $conversation_id, $classification ) {
		global $wpdb;

		$conversation_id = absint( $conversation_id );

		if ( $conversation_id <= 0 ) {
			return false;
		}

		$updated = $wpdb->update(
			self::conversations_table(),
			array(
				'lead_category'              => sanitize_text_field( $classification['lead_category'] ?? '' ),
				'confidence'                 => min( 100, max( 0, absint( $classification['confidence'] ?? 0 ) ) ),
				'reason'                     => sanitize_textarea_field( $classification['reason'] ?? '' ),
				'suggested_reply'            => sanitize_textarea_field( $classification['suggested_reply'] ?? '' ),
				'raw_classification_response' => sanitize_textarea_field( $classification['raw_classification_response'] ?? '' ),
				'classification_updated_at'  => current_time( 'mysql' ),
				'updated_at'                 => current_time( 'mysql' ),
			),
			array( 'id' => $conversation_id ),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		return false !== $updated;
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
	 * @param array $args Optional filters.
	 * @return array
	 */
	public static function get_conversations( $args = array() ) {
		global $wpdb;

		$lead_category = isset( $args['lead_category'] ) ? sanitize_text_field( wp_unslash( $args['lead_category'] ) ) : '';
		$search        = isset( $args['search'] ) ? trim( sanitize_text_field( wp_unslash( $args['search'] ) ) ) : '';
		$allowed_categories = array(
			'Hot Lead',
			'Warm Lead',
			'General Inquiry',
			'Support Request',
			'Vendor / Not Lead',
			'Unknown / Spam',
		);

		$where_clauses = array();
		$query_args    = array();

		if ( in_array( $lead_category, $allowed_categories, true ) ) {
			$where_clauses[] = 'conversations.lead_category = %s';
			$query_args[]    = $lead_category;
		}

		if ( '' !== $search ) {
			$search_like      = '%' . $wpdb->esc_like( $search ) . '%';
			$where_clauses[]  = '(conversations.contact_name LIKE %s OR conversations.contact_phone LIKE %s OR conversations.wa_id LIKE %s OR conversations.last_message LIKE %s)';
			$query_args[]     = $search_like;
			$query_args[]     = $search_like;
			$query_args[]     = $search_like;
			$query_args[]     = $search_like;
		}

		$sql = 'SELECT conversations.*,
				(SELECT messages.message_body
				 FROM ' . self::messages_table() . ' messages
				 WHERE messages.conversation_id = conversations.id
				   AND messages.sender_type IN (\'user\', \'admin\')
				 ORDER BY messages.created_at DESC, messages.id DESC
				 LIMIT 1) AS display_last_message,
				(SELECT messages.created_at
				 FROM ' . self::messages_table() . ' messages
				 WHERE messages.conversation_id = conversations.id
				   AND messages.sender_type IN (\'user\', \'admin\')
				 ORDER BY messages.created_at DESC, messages.id DESC
				 LIMIT 1) AS display_last_message_at
			 FROM ' . self::conversations_table() . ' conversations';

		if ( ! empty( $where_clauses ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		$sql .= ' ORDER BY last_message_at DESC, updated_at DESC LIMIT 100';

		if ( empty( $query_args ) ) {
			return $wpdb->get_results( $sql );
		}

		return $wpdb->get_results( $wpdb->prepare( $sql, $query_args ) );
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
				'SELECT * FROM ' . self::messages_table() . " WHERE conversation_id = %d AND sender_type IN ('user', 'admin') ORDER BY created_at ASC, id ASC LIMIT 300",
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
