( function( $ ) {
	$( 'button.bp-bookmarklet' ).on( 'click', function( event ) {
		var self = $( event.currentTarget );

		$( '#bookmarklet-code-wrap' ).slideToggle( 200 );
		self.prop( 'aria-expanded', self.prop( 'aria-expanded' ) === 'false' ? 'true' : 'false' );
	} );

	$( '.bookmarklet-code' ).on( 'click focus', function() {
		var self = this;

		setTimeout( function() { self.select(); }, 50 );
	} );

} )( jQuery );
