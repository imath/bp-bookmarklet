<?php
/**
 * The bookmarklet form template.
 *
 * @since 3.0.0
 */

bp_bookmarklet_enqueue_form(); ?>

<div id="bookmark-container"></div>

<script type="text/html" id="tmpl-bookmark-post-form-content">
	<# if ( data.display_avatar ) { #>
		<div id="bp-bookmarklet-avatar">
			<a href="{{data.user_domain}}">
				<img src="{{data.avatar_url}}" class="avatar user-{{data.user_id}}-avatar avatar-50 photo" width="{{data.avatar_width}}" height="{{data.avatar_width}}" alt="{{data.avatar_alt}}" />
			</a>
		</div>
	<# } #>
	<div id="bp-bookmarklet-textarea"></div>
</script>

<script type="text/html" id="tmpl-bookmark-link-output">
	<# if ( 'link' === data.type ) { #>
		<# if ( data.images[0] ) { #>
			<div class="thumbnail-preview">
				<a href="#" id="bookmarklet-no-image">
					<span class="bp-screen-reader-text"><?php esc_html_e( 'Close', 'bp-bookmarklet' ); ?></span>
				</a>
				<img src="{{data.images[0]}}" class="link-thumbnail" />
			</div>
		<# } #>

		<div class="link-content">
			<h4><a href="{{data.url}}" target="_blank" title="{{data.title}}">{{data.title}}</a></h4>
			<p class="description">{{data.description}}</p>
		</div>
	<# } else { #>
		<div id="oembed-preview"></div>
	<# } #>
</script>


<script type="text/html" id="tmpl-bookmark-target-item">
	<# if ( data.selected ) { #>
		<input type="hidden" value="{{data.id}}">
	<# } #>

	<# if ( data.avatar_url ) { #>
		<img src="{{data.avatar_url}}" class="avatar {{data.object_type}}-{{data.id}}-avatar photo" />
	<# } #>

	<span class="bp-item-name">{{data.name}}</span>

	<# if ( data.selected ) { #>
		<a href="#" class="bp-remove-item" data-item_id="{{data.id}}">
			<span class="bp-screen-reader-text"><?php esc_html_e( 'Remove item', 'bp-bookmarklet' ); ?></span>
		</a>
	<# } #>
</script>
