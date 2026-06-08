<?php
/**
 * Conversations admin page.
 *
 * @package MucacranWaAi
 *
 * @var array       $conversations Conversation rows.
 * @var int         $selected_id   Selected conversation ID.
 * @var object|null $conversation  Selected conversation.
 * @var array       $messages      Message rows.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap mucacran-wa-ai-page">
	<h1><?php echo esc_html__( 'Conversations', 'api-whatsaap-base-openai' ); ?></h1>

	<div class="mucacran-chat" data-selected-conversation="<?php echo esc_attr( $selected_id ); ?>">
		<aside class="mucacran-chat__list" aria-label="<?php echo esc_attr__( 'Conversation list', 'api-whatsaap-base-openai' ); ?>">
			<?php if ( empty( $conversations ) ) : ?>
				<p class="mucacran-chat__empty"><?php echo esc_html__( 'No conversations yet.', 'api-whatsaap-base-openai' ); ?></p>
			<?php endif; ?>

			<?php foreach ( $conversations as $item ) : ?>
				<a class="mucacran-chat__conversation <?php echo (int) $item->id === $selected_id ? 'is-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . MUCACRAN_WA_AI_PAGE_SLUG . '-conversations&conversation_id=' . (int) $item->id ) ); ?>" data-conversation-id="<?php echo esc_attr( $item->id ); ?>">
					<span class="mucacran-chat__conversation-name">
						<?php echo esc_html( $item->contact_name ? $item->contact_name : $item->contact_phone ); ?>
						<?php if ( (int) $item->unread_admin > 0 ) : ?>
							<span class="mucacran-chat__unread"><?php echo esc_html( (int) $item->unread_admin ); ?></span>
						<?php endif; ?>
					</span>
					<span class="mucacran-chat__conversation-phone"><?php echo esc_html( $item->contact_phone ); ?></span>
					<span class="mucacran-chat__conversation-message"><?php echo esc_html( wp_trim_words( $item->last_message, 12 ) ); ?></span>
					<span class="mucacran-chat__conversation-time"><?php echo esc_html( $item->last_message_at ? mysql2date( 'Y-m-d H:i', $item->last_message_at ) : '' ); ?></span>
				</a>
			<?php endforeach; ?>
		</aside>

		<section class="mucacran-chat__thread" aria-label="<?php echo esc_attr__( 'Selected conversation', 'api-whatsaap-base-openai' ); ?>">
			<header class="mucacran-chat__header">
				<strong class="mucacran-chat__header-name">
					<?php echo esc_html( $conversation ? ( $conversation->contact_name ? $conversation->contact_name : $conversation->contact_phone ) : __( 'Select a conversation', 'api-whatsaap-base-openai' ) ); ?>
				</strong>
				<?php if ( $conversation ) : ?>
					<span><?php echo esc_html( $conversation->contact_phone ); ?></span>
				<?php endif; ?>
			</header>

			<div class="mucacran-chat__messages" id="mucacran-chat-messages">
				<?php if ( empty( $messages ) ) : ?>
					<p class="mucacran-chat__empty"><?php echo esc_html__( 'Messages will appear here.', 'api-whatsaap-base-openai' ); ?></p>
				<?php endif; ?>

				<?php foreach ( $messages as $message ) : ?>
					<div class="mucacran-message mucacran-message--<?php echo esc_attr( $message->direction ); ?>">
						<div class="mucacran-message__bubble">
							<p><?php echo esc_html( $message->message_body ); ?></p>
							<span class="mucacran-message__meta">
								<?php echo esc_html( mysql2date( 'Y-m-d H:i', $message->created_at ) ); ?>
								<?php if ( $message->delivery_status ) : ?>
									 - <?php echo esc_html( $message->delivery_status ); ?>
								<?php endif; ?>
							</span>
							<?php if ( $message->error_message ) : ?>
								<span class="mucacran-message__error"><?php echo esc_html( $message->error_message ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<form class="mucacran-chat__composer" id="mucacran-chat-composer">
				<textarea name="message" rows="3" placeholder="<?php echo esc_attr__( 'Type a reply', 'api-whatsaap-base-openai' ); ?>" <?php disabled( ! $conversation ); ?>></textarea>
				<button type="submit" class="button button-primary" <?php disabled( ! $conversation ); ?>><?php echo esc_html__( 'Send', 'api-whatsaap-base-openai' ); ?></button>
				<span class="mucacran-chat__notice" role="status"></span>
			</form>
		</section>
	</div>
</div>
