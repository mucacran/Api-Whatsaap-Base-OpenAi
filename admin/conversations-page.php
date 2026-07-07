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

$lead_category   = $conversation && isset( $conversation->lead_category ) ? (string) $conversation->lead_category : '';
$confidence      = $conversation && isset( $conversation->confidence ) ? (int) $conversation->confidence : 0;
$reason          = $conversation && isset( $conversation->reason ) ? (string) $conversation->reason : '';
$suggested_reply = $conversation && isset( $conversation->suggested_reply ) ? (string) $conversation->suggested_reply : '';
?>

<div class="wrap mucacran-wa-ai-page">
	<h1><?php echo esc_html__( 'Conversations', 'api-whatsaap-base-openai' ); ?></h1>

	<form class="mucacran-chat__filters" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( MUCACRAN_WA_AI_PAGE_SLUG . '-conversations' ); ?>" />
		<?php if ( $selected_id ) : ?>
			<input type="hidden" name="conversation_id" value="<?php echo esc_attr( $selected_id ); ?>" />
		<?php endif; ?>

		<label for="mucacran-filter-category" class="screen-reader-text">
			<?php echo esc_html__( 'Filter by category', 'api-whatsaap-base-openai' ); ?>
		</label>
		<select id="mucacran-filter-category" name="lead_category">
			<option value=""><?php echo esc_html__( 'All categories', 'api-whatsaap-base-openai' ); ?></option>
			<option value="Hot Lead" <?php selected( $lead_category_filter, 'Hot Lead' ); ?>><?php echo esc_html__( 'Hot Lead', 'api-whatsaap-base-openai' ); ?></option>
			<option value="Warm Lead" <?php selected( $lead_category_filter, 'Warm Lead' ); ?>><?php echo esc_html__( 'Warm Lead', 'api-whatsaap-base-openai' ); ?></option>
			<option value="General Inquiry" <?php selected( $lead_category_filter, 'General Inquiry' ); ?>><?php echo esc_html__( 'General Inquiry', 'api-whatsaap-base-openai' ); ?></option>
			<option value="Support Request" <?php selected( $lead_category_filter, 'Support Request' ); ?>><?php echo esc_html__( 'Support Request', 'api-whatsaap-base-openai' ); ?></option>
			<option value="Vendor / Not Lead" <?php selected( $lead_category_filter, 'Vendor / Not Lead' ); ?>><?php echo esc_html__( 'Vendor / Not Lead', 'api-whatsaap-base-openai' ); ?></option>
			<option value="Unknown / Spam" <?php selected( $lead_category_filter, 'Unknown / Spam' ); ?>><?php echo esc_html__( 'Unknown / Spam', 'api-whatsaap-base-openai' ); ?></option>
		</select>

		<label for="mucacran-filter-search" class="screen-reader-text">
			<?php echo esc_html__( 'Search conversations', 'api-whatsaap-base-openai' ); ?>
		</label>
		<input type="search" id="mucacran-filter-search" name="search" value="<?php echo esc_attr( $search_filter ); ?>" placeholder="<?php echo esc_attr__( 'Search by name, phone, or message', 'api-whatsaap-base-openai' ); ?>" />

		<button type="submit" class="button"><?php echo esc_html__( 'Apply filters', 'api-whatsaap-base-openai' ); ?></button>
		<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . MUCACRAN_WA_AI_PAGE_SLUG . '-conversations' ) ); ?>">
			<?php echo esc_html__( 'Clear filters', 'api-whatsaap-base-openai' ); ?>
		</a>
	</form>

	<?php if ( '' !== $lead_category_filter || '' !== $search_filter ) : ?>
		<p class="mucacran-chat__filters-summary">
			<?php echo esc_html__( 'Active filters:', 'api-whatsaap-base-openai' ); ?>
			<?php if ( '' !== $lead_category_filter ) : ?>
				<strong><?php echo esc_html( $lead_category_filter ); ?></strong>
			<?php endif; ?>
			<?php if ( '' !== $search_filter ) : ?>
				<?php if ( '' !== $lead_category_filter ) : ?>
					<span>•</span>
				<?php endif; ?>
				<strong><?php echo esc_html( $search_filter ); ?></strong>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<div class="mucacran-chat" data-selected-conversation="<?php echo esc_attr( $selected_id ); ?>">
		<aside class="mucacran-chat__list" aria-label="<?php echo esc_attr__( 'Conversation list', 'api-whatsaap-base-openai' ); ?>">
			<?php if ( empty( $conversations ) ) : ?>
				<p class="mucacran-chat__empty">
					<?php echo esc_html( '' !== $lead_category_filter || '' !== $search_filter ? __( 'No conversations match the current filters.', 'api-whatsaap-base-openai' ) : __( 'No conversations yet.', 'api-whatsaap-base-openai' ) ); ?>
				</p>
			<?php endif; ?>

			<?php foreach ( $conversations as $item ) : ?>
				<?php $item_category = isset( $item->lead_category ) ? (string) $item->lead_category : ''; ?>
				<?php
					$link_query = array(
						'page'            => MUCACRAN_WA_AI_PAGE_SLUG . '-conversations',
						'conversation_id' => (int) $item->id,
					);
				if ( '' !== $lead_category_filter ) {
					$link_query['lead_category'] = $lead_category_filter;
				}
				if ( '' !== $search_filter ) {
					$link_query['search'] = $search_filter;
				}
				$conversation_url = add_query_arg( $link_query, admin_url( 'admin.php' ) );
				?>
				<a class="mucacran-chat__conversation <?php echo (int) $item->id === $selected_id ? 'is-active' : ''; ?>" href="<?php echo esc_url( $conversation_url ); ?>" data-conversation-id="<?php echo esc_attr( $item->id ); ?>">
					<span class="mucacran-chat__conversation-name">
						<?php echo esc_html( $item->contact_name ? $item->contact_name : $item->contact_phone ); ?>
						<?php if ( (int) $item->unread_admin > 0 ) : ?>
							<span class="mucacran-chat__unread"><?php echo esc_html( (int) $item->unread_admin ); ?></span>
						<?php endif; ?>
					</span>
					<?php if ( '' !== $item_category ) : ?>
						<span class="mucacran-chat__lead-badge"><?php echo esc_html( $item_category ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $item->confidence ) ) : ?>
						<span class="mucacran-chat__confidence"><?php echo esc_html( (int) $item->confidence . '%' ); ?></span>
					<?php endif; ?>
					<span class="mucacran-chat__conversation-phone"><?php echo esc_html( $item->contact_phone ); ?></span>
					<span class="mucacran-chat__conversation-message"><?php echo esc_html( wp_trim_words( (string) $item->display_last_message, 12 ) ); ?></span>
					<span class="mucacran-chat__conversation-time"><?php echo esc_html( $item->display_last_message_at ? mysql2date( 'Y-m-d H:i', $item->display_last_message_at ) : '' ); ?></span>
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

			<?php if ( $conversation ) : ?>
				<section class="mucacran-classification" aria-label="<?php echo esc_attr__( 'Internal lead classification', 'api-whatsaap-base-openai' ); ?>">
					<div class="mucacran-classification__heading">
						<strong><?php echo esc_html__( 'Internal Lead Classification', 'api-whatsaap-base-openai' ); ?></strong>
						<?php if ( '' !== $lead_category ) : ?>
							<span class="mucacran-chat__lead-badge"><?php echo esc_html( $lead_category ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( '' !== $lead_category ) : ?>
						<dl class="mucacran-classification__details">
							<div>
								<dt><?php echo esc_html__( 'Confidence', 'api-whatsaap-base-openai' ); ?></dt>
								<dd><?php echo esc_html( $confidence . '%' ); ?></dd>
							</div>
							<div>
								<dt><?php echo esc_html__( 'Reason', 'api-whatsaap-base-openai' ); ?></dt>
								<dd><?php echo esc_html( $reason ); ?></dd>
							</div>
						</dl>
					<?php else : ?>
						<p class="mucacran-classification__empty"><?php echo esc_html__( 'This conversation has not been classified yet.', 'api-whatsaap-base-openai' ); ?></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

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

			<?php if ( '' !== $suggested_reply ) : ?>
				<section class="mucacran-suggested-reply" aria-label="<?php echo esc_attr__( 'Suggested reply draft', 'api-whatsaap-base-openai' ); ?>">
					<div>
						<strong><?php echo esc_html__( 'Suggested Reply', 'api-whatsaap-base-openai' ); ?></strong>
						<span><?php echo esc_html__( 'AI draft for operator review — not sent', 'api-whatsaap-base-openai' ); ?></span>
					</div>
					<p id="mucacran-suggested-reply-text"><?php echo esc_html( $suggested_reply ); ?></p>
					<button type="button" class="button" id="mucacran-use-suggested-reply"><?php echo esc_html__( 'Use Suggested Reply', 'api-whatsaap-base-openai' ); ?></button>
				</section>
			<?php endif; ?>

			<form class="mucacran-chat__composer" id="mucacran-chat-composer">
				<textarea name="message" rows="3" placeholder="<?php echo esc_attr__( 'Type a reply', 'api-whatsaap-base-openai' ); ?>" <?php disabled( ! $conversation ); ?>></textarea>
				<button type="submit" class="button button-primary" <?php disabled( ! $conversation ); ?>><?php echo esc_html__( 'Send', 'api-whatsaap-base-openai' ); ?></button>
				<span class="mucacran-chat__notice" role="status"></span>
			</form>
		</section>
	</div>
</div>
