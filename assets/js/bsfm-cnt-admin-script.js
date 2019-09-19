(function( $ ) {

var ContactInMautic = {
		
		/**
		 * Initializes the services logic.
		 *
		 * @return void
		 * @since 1.0.3
		 */
		init: function()
		{	
			$( document ).on( 'change', '.bsfm-mautic-type', this._changeMauticType );
		},

		_changeMauticType: function() {
			var val = $( this ).val();
			if( val != 'mautic_api' ) {
				$( '.contacts-in-mautic-text-bsfm-username' ).show();
				$( '.contacts-in-mautic-text-bsfm-password' ).show();

				$( '.contacts-in-mautic-text-bsfm-public-key' ).hide();
				$( '.contacts-in-mautic-text-bsfm-secret-key' ).hide();
			} else {
				$( '.contacts-in-mautic-text-bsfm-username' ).hide();
				$( '.contacts-in-mautic-text-bsfm-password' ).hide();

				$( '.contacts-in-mautic-text-bsfm-public-key' ).show();
				$( '.contacts-in-mautic-text-bsfm-secret-key' ).show();
			}
		}
	};


	$ ( function() {
		ContactInMautic.init();
	});

})(jQuery);