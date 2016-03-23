/* globals bp, BP_Bookmarklet, _, Backbone */

window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Bookmarklet === 'undefined' ) {
		return;
	}

	window.bp = _.extend( window.bp, _.pick( window.wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.BookMarklet = {
		start: function() {
			this.views   = new Backbone.Collection();
			this.items   = new bp.Collections.Items();

			this.postFormView();
		},

		postFormView: function() {
			// Create the BuddyPress Uploader
			var postForm = new bp.Views.PostForm();

			// Add it to views
			this.views.add( { id: 'post_form', view: postForm } );

			// Display it
			postForm.inject( '#bookmark-container' );
		},

		/**
		 * Get the Query string parameter(s)
		 *
		 * @param  {string} url   The url to parse (defaults to current)
		 * @param  {string} param The specific parameter to get.
		 * @return {string|array} A specific or all parameters.
		 */
		getLinkParams: function( url, param ) {
			var qs;

			if ( url ) {
				qs = ( -1 !== url.indexOf( '?' ) ) ? '?' + url.split( '?' )[1] : '';
			} else {
				qs = document.location.search;
			}

			if ( ! qs ) {
				return null;
			}

			var params = qs.replace( /(^\?)/, '' ).split( '&' ).map( function( n ) {
				return n = n.split( '=' ),this[n[0]] = n[1],this;
			}.bind( {} ) )[0];

			if ( param ) {
				return params[param];
			}

			return params;
		},

		// Credits @WordPress's press-this.js
		stripTags: function( string ) {
			string = string || '';

			return string
				.replace( /<!--[\s\S]*?(-->|$)/g, '' )
				.replace( /<(script|style)[^>]*>[\s\S]*?(<\/\1>|$)/ig, '' )
				.replace( /<\/?[a-z][\s\S]*?(>|$)/ig, '' );
		},

		// Credits @WordPress's press-this.js
		checkUrl: function( url ) {
			url = $.trim( url || '' );

			if ( /^(?:https?:)?\/\//.test( url ) ) {
				url = this.stripTags( url );
				return url.replace( /["\\]+/g, '' );
			}

			return '';
		}
	};

	if ( typeof bp.View === 'undefined' ) {
		// Extend wp.Backbone.View with .prepare() and .inject()
		bp.View = bp.Backbone.View.extend( {
			inject: function( selector ) {
				this.render();
				$(selector).html( this.el );
				this.views.ready();
			},

			prepare: function() {
				if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
					return this.model.toJSON();
				} else {
					return {};
				}
			}
		} );
	}

	/** Models ****************************************************************/

	bp.Models.BookMarklet = Backbone.Model.extend( {
		bookmarklet: {
			user_id     : 0,
			item_id     : 0,
			object      : '',
			content     : '',
			type        : '',
			url         : '',
			title       : '',
			description : '',
			images      : [],
			fetched     : false
		},

		getSource: function() {
			var self = this;

			if ( ! this.get( 'url' ) ) {
				return;
			}

			if ( ! _.isUndefined( this.get( 'errors' ) ) ) {
				this.unset( 'errors', { silent: true } );
			}

			bp.ajax.post( 'bp_bookmarklet_fetch_link', { url: self.get( 'url' ) } ).done( function( response ) {
				// Use the title we got from the Bookmarklet if we were not able to fetch it with Press This
				if ( self.get( 'title' ) && _.isEmpty( response.title ) ) {
					response.title = self.get( 'title' );
				}

				self.set( response );
				self.set( 'fetching', false );

			} ).fail( function( response ) {
				self.set( response );
				self.set( 'fetching', false );
			} );
		}
	} );

	// Item (group or blog or any other)
	bp.Models.Item = Backbone.Model.extend( {
		item: {}
	} );

	/** Collections ***********************************************************/

	// Items (groups or blogs or any others)
	bp.Collections.Items = Backbone.Collection.extend( {
		model: bp.Models.Item,

		sync: function( method, model, options ) {

			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action: 'bp_bookmarklet_get_items'
				} );

				return bp.ajax.send( options );
			}
		},

		parse: function( resp ) {
			if ( ! _.isArray( resp ) ) {
				resp = [resp];
			}

			return resp;
		}

	} );

	/** Views *****************************************************************/

	// Feedback messages
	bp.Views.activityWarning = bp.View.extend( {
		tagName: 'div',
		id: 'message',

		initialize: function() {
			this.value = this.options.value;
			this.type  = 'info';

			if ( ! _.isUndefined( this.options.type ) && 'info' !== this.options.type ) {
				this.type = this.options.type;
			} else {
				this.el.className = this.type;
			}
		},

		render: function() {
			var message;

			if ( 'info' === this.type ) {
				message = '<p>' + this.value + '</p>';
			} else {
				message = '<p class="' + this.type + '">' + this.value + '</p>';
			}
			this.$el.html( message );
			return this;
		}
	} );

	// Regular input
	bp.Views.ActivityInput = bp.View.extend( {
		tagName  : 'input',

		attributes: {
			type : 'text'
		},

		initialize: function() {
			if ( ! _.isObject( this.options ) ) {
				return;
			}

			_.each( this.options, function( value, key ) {
				this.$el.prop( key, value );
			}, this );
		}
	} );

	// The content of the activity
	bp.Views.WhatsNew = bp.View.extend( {
		tagName:   'textarea',
		className: 'bp-suggestions',
		id       : 'bp-bookmarklet',

		attributes: {
			role         : 'textbox',
			name         : 'bp-bookmarklet',
			cols         : '50',
			rows         : '4',
			placeholder  : BP_Bookmarklet.strings.textareaPlaceholder,
			'aria-label' : BP_Bookmarklet.strings.textareaLabel
		},

		initialize: function() {
			this.options.activity.on( 'change:content', this.resetContent, this );
		},

		resetContent: function( activity ) {
			if ( _.isUndefined( activity ) ) {
				return;
			}

			this.$el.val( activity.get( 'content' ) ).css( {
				height: this.$el.get( 0 ).scrollHeight
			} );
		}
	} );

	bp.Views.WhatsNewPostIn = bp.View.extend( {
		tagName:   'select',
		id:        'bp-bookmarklet-post-in',

		attributes: {
			name         : 'bp-bookmarklet-post-in',
			'aria-label' : BP_Bookmarklet.strings.selectObject
		},

		events: {
			change: 'change'
		},

		keys: [],

		initialize: function() {
			this.model = new Backbone.Model();

			this.filters = this.options.filters || {};

			// Build `<option>` elements.
			this.$el.html( _.chain( this.filters ).map( function( filter, value ) {
				return {
					el: $( '<option></option>' ).val( value ).html( filter.text )[0],
					priority: filter.priority || 50
				};
			}, this ).sortBy('priority').pluck('el').value() );
		},

		/**
		 * When the selected filter changes, update the Item Query properties to match.
		 */
		change: function() {
			var filter = this.filters[ this.el.value ];
			if ( filter ) {
				this.model.set( { 'selected': this.el.value, 'placeholder': filter.autocomplete_placeholder } );
			}
		}
	} );

	bp.Views.Item = bp.View.extend( {
		tagName:   'li',
		className: 'bp-item',
		template:  bp.template( 'bookmark-target-item' ),

		attributes: {
			role: 'checkbox'
		},

		initialize: function() {
			if ( this.model.get( 'selected' ) ) {
				this.el.className += ' selected';
			}
		},

		events: {
			'click': 'setObject'
		},

		setObject:function( event ) {
			event.preventDefault();

			if ( true === this.model.get( 'selected' ) ) {
				this.model.clear();
			} else {
				this.model.set( 'selected', true );
			}
		}
	} );

	bp.Views.AutoComplete = bp.View.extend( {
		tagName  : 'ul',
		id       : 'bp-bookmarklet-post-in-box-items',

		events: {
			'keyup':  'autoComplete'
		},

		initialize: function() {
			var autocomplete = new bp.Views.ActivityInput( {
				type: 'text',
				id: 'bookmark-item-autocomplete',
				placeholder: this.options.placeholder || ''
			} ).render();

			this.$el.prepend( $( '<li></li>' ).html( autocomplete.$el ) );

			this.on( 'ready', this.setFocus, this );
			this.collection.on( 'add', this.addItemView, this );
			this.collection.on( 'reset', this.cleanView, this );
		},

		setFocus: function() {
			this.$el.find( '#bookmark-item-autocomplete' ).focus();
		},

		addItemView: function( item ) {
			this.views.add( new bp.Views.Item( { model: item } ) );
		},

		autoComplete: function() {
			var search = $( '#bookmark-item-autocomplete' ).val();

			// Reset the collection before starting a new search
			this.collection.reset();

			if ( 2 > search.length ) {
				return;
			}

			this.collection.fetch( {
				data: {
					type: this.options.type,
					search: search
				},
				success : _.bind( this.itemFetched, this ),
				error : _.bind( this.itemFetched, this )
			} );
		},

		itemFetched: function( items ) {
			if ( ! items.length ) {
				this.cleanView();
			}
		},

		cleanView: function() {
			_.each( this.views._views[''], function( view ) {
					view.remove();
			} );
		}
	} );

	bp.Views.FormContent = bp.View.extend( {
		tagName  : 'div',
		id       : 'bp-bookmarklet-content',
		template : bp.template( 'bookmark-post-form-content' ),

		initialize: function() {
			this.model = new Backbone.Model( _.pick( BP_Bookmarklet.params, [
				'user_id',
				'avatar_url',
				'avatar_width',
				'avatar_height',
				'avatar_alt',
				'user_domain'
			] ) );

			if ( this.model.has( 'avatar_url' ) ) {
				this.model.set( 'display_avatar', true );
			}

			this.views.set( '#bp-bookmarklet-textarea', new bp.Views.WhatsNew( { activity: this.options.activity } ) );
		}
	} );

	bp.Views.FormTarget = bp.View.extend( {
		tagName   : 'div',
		id        : 'bp-bookmarklet-post-in-box',
		className : 'in-profile',

		initialize: function() {
			var select = new bp.Views.WhatsNewPostIn( { filters: BP_Bookmarklet.params.objects } );
			this.views.add( select );

			select.model.on( 'change', this.attachAutocomplete, this );
			bp.BookMarklet.items.on( 'change:selected', this.postIn, this );
		},

		attachAutocomplete: function( model ) {
			if ( 0 !== bp.BookMarklet.items.models.length ) {
				bp.BookMarklet.items.reset();
			}

			// Clean up views
			_.each( this.views._views[''], function( view ) {
				if ( ! _.isUndefined( view.collection ) ) {
					view.remove();
				}
			} );

			if ( 'profile' !== model.get( 'selected') ) {
				this.views.add( new bp.Views.AutoComplete( {
					collection:   bp.BookMarklet.items,
					type:         model.get( 'selected' ),
					placeholder : model.get( 'placeholder' )
				} ) );

				// Set the object type
				this.model.set( 'object', model.get( 'selected') );

			} else {
				this.model.set( { object: 'user', item_id: 0 } );
			}

			this.updateDisplay();
		},

		postIn: function( model ) {
			if ( _.isUndefined( model.get( 'id' ) ) ) {
				// Reset the item id
				this.model.set( 'item_id', 0 );

				// When the model has been cleared, Attach Autocomplete!
				this.attachAutocomplete( new Backbone.Model( { selected: this.model.get( 'object' ) } ) );
				return;
			}

			// Set the item id for the selected object
			this.model.set( 'item_id', model.get( 'id' ) );

			// Set the view to the selected object
			this.views.set( '#bp-bookmarklet-post-in-box-items', new bp.Views.Item( { model: model } ) );
		},

		updateDisplay: function() {
			if ( 'user' !== this.model.get( 'object' ) ) {
				this.$el.removeClass( );
			} else if ( ! this.$el.hasClass( 'in-profile' ) ) {
				this.$el.addClass( 'in-profile' );
			}
		}
	} );

	bp.Views.FormSubmit = bp.View.extend( {
		tagName   : 'div',
		id        : 'bp-bookmarklet-submit',
		className : 'in-profile',

		initialize: function() {
			var reset = new bp.Views.ActivityInput( {
				type:  'reset',
				id:    'bp-bookmarklet-reset-button',
				value: BP_Bookmarklet.strings.cancelText
			} );

			var submit = new bp.Views.ActivityInput( {
				type:  'submit',
				id:    'bp-bookmarklet-submit-button',
				name:  'bp-bookmarklet-submit-button',
				value: BP_Bookmarklet.strings.submitText
			} );

			this.views.add( submit );
			this.views.add( reset );

			this.model.on( 'change:object', this.updateDisplay, this );
		},

		updateDisplay: function( model ) {
			if ( _.isUndefined( model ) ) {
				return;
			}

			if ( 'user' !== model.get( 'object' ) ) {
				this.$el.removeClass( 'in-profile' );
			} else if ( ! this.$el.hasClass( 'in-profile' ) ) {
				this.$el.addClass( 'in-profile' );
			}
		}
	} );

	bp.Views.FormLink = bp.View.extend( {
		tagName  : 'div',
		id       : 'bp-bookmarklet-link',

		events: {
			'blur #activity-link': 'loadContent'
		},

		initialize: function() {
			var linkInput = new bp.Views.ActivityInput( {
				type        : 'text',
				id          : 'activity-link',
				placeholder : 'http://',
				readonly    : 'readonly'
			} ).render();

			this.$el.prepend( $( '<div></div>' ).html( linkInput.$el ) );

			this.on( 'ready', this.setFocus, this );
			this.model.on( 'change:type', this.setBookmark, this );
			this.model.on( 'change:errors', this.setBookmark, this );
			this.model.on( 'change:fetching', this.refreshContent, this );
		},

		setFocus: function() {
			var isFrame = bp.BookMarklet.getLinkParams(), self = this,
				linkInput = this.$el.find( '#activity-link' );

			if ( ! _.isUndefined( isFrame.url ) ) {
				/**
				 * _.mapObject was introduced in Underscore 1.8
				 * This version of underscore will be introduced in WordPress 4.5
				 *
				 * @todo when the required version of the plugin will be 4.5
				 * Use _.mapObject instead of $.map
				 */
				$.map( isFrame, function( attr, i ) {
					if ( -1 !== _.indexOf( ['title', 'description', 'copied'], i ) ) {
						var attribute = i;

						if ( 'copied' === i ) {
							attribute = 'content';
						}

						if ( ! _.isNull( attr ) && 'null' !== attr ) {
							self.model.set( attribute, decodeURIComponent( attr ) );
						}
					}
				} );

				linkInput.val( decodeURIComponent( isFrame.url ) );
				linkInput.trigger( 'blur' );

			} else {
				linkInput.focus();
			}
		},

		setBookmark: function( link ) {
			var images = link.get( 'images' ) || [];

			if ( link.get( 'errors' ) ) {

				if ( _.isArray( link.get( 'errors' ) ) ) {
					link.set( {
						errors     : { type: 'info', value: _.first( link.get( 'errors' ) ) },
						fetching: false
					}, { silent: true } );
				}

				if ( ! link.get( 'title' ) ) {
					return;
				} else {
					link.set( { type: 'link', 'images' : [''] }, { silent: true } );
					this.model.unset( 'errors' );
				}
			} else if ( link.get( 'title' ) && false === link.get( 'fetching' ) ) {
				if ( 'link' === link.get( 'type' ) && 0 !== images.length ) {
					link.set( 'image', _.first( images ) );
				}

				this.views.add( new bp.Views.linkOutput( { model: link } ) );

				if ( 1 < images.length ) {
					// Let the user choose the image for his link
					this.views.add( new bp.Views.imageSelector( { model: link } ) );
				}
			}
		},

		loadContent: function( event ) {
			var url = bp.BookMarklet.checkUrl( $( event.target).val() );

			if ( '' === url || this.model.get( 'url' ) === url ) {
				return;
			}

			this.model.set( { url: url, type: '', fetching: true } );
			this.model.unset( 'errors' );

			this.model.getSource();
		},

		refreshContent: function( model ) {
			if ( ! model.get( 'title') ) {
				// Clean up views
				_.each( this.views._views[''], function( view ) {
					view.remove();
				} );
			}

			if ( true === model.get( 'fetching' ) ) {
				this.$el.append( '<div id="loader"></div>' );
			} else {
				this.$el.find( '#loader' ).remove();
			}
		}
	} );

	bp.Views.linkOutput = bp.View.extend( {
		tagName:   'div',
		className: 'link-output',
		template:  bp.template( 'bookmark-link-output' ),

		events: {
			'click #bookmarklet-no-image': 'removeImage'
		},

		initialize: function() {
			this.on( 'ready', this.insertEmbed, this );
		},

		insertEmbed: function() {
			if ( 'oembed' === this.model.get( 'type' ) && this.model.get( 'description' ) ) {
				this.$el.find( '#oembed-preview' ).html( this.model.get( 'description' ) );
			}
		},

		removeImage: function( event ) {
			event.preventDefault();

			this.model.set( 'image', '' );
			$( '#bp-bookmarklet-link .thumbnail-preview' ).addClass( 'noimage' );

			$( '#bp-bookmarklet-link .link-image' ).each( function( e, image ) {
				$( image ).removeClass( 'selected' );
			} );
		}
	} );

	bp.Views.imageSelector = bp.View.extend( {
		tagName:   'ul',
		className: 'link-images',

		events: {
			'click .link-image':  'setImage'
		},

		initialize: function() {
			_.each( this.model.get( 'images' ), function( src ) {
				var selected = '';

				if ( src === this.model.get( 'image' ) ) {
					selected = 'selected';
				}

				this.$el.append( $( '<li></li>' ).html( '<a href="#" class="link-image ' + selected + '" role="checkbox" style="background:url(' + src + ')" data-image="' + src + '"></a>' ) );
			}, this );
		},

		setImage: function( event ) {
			event.preventDefault();

			var src = $( event.target ).data( 'image' );

			$( '.link-image' ).each( function() {
				var item = $( this );

				if ( item.hasClass( 'selected' ) ) {
					item.removeClass( 'selected' );
				}
			} );

			// Update models
			this.model.set( 'image', src );

			$( event.target ).addClass( 'selected' );
			$( '#bp-bookmarklet-link .link-thumbnail' ).prop( 'src', src );
			$( '#bp-bookmarklet-link .thumbnail-preview' ).removeClass( 'noimage' );
		}
	} );

	bp.Views.PostForm = bp.View.extend( {
		tagName:   'form',
		className: 'bookmarklet-form',

		events: {
			'reset' : 'resetForm',
			'submit': 'postUpdate'
		},

		initialize: function() {
			this.model = new bp.Models.BookMarklet( _.pick(
				BP_Bookmarklet.params,
				['user_id', 'item_id', 'object' ]
			) );

			// Clone the model to set the resetted one
			this.resetModel = this.model.clone();
			this.resetModel.set( 'fetched', false );

			this.views.add( new bp.Views.FormLink( { model: this.model } ) );

			this.model.on( 'change:errors', this.displayFeedback, this );
			this.model.on( 'change:title', this.displayFull, this );
		},

		displayFull: function() {
			if ( 1 !== this.views._views[''].length || _.isUndefined( this.model.get( 'title' ) ) ) {
				return;
			}

			this.views.add( new bp.Views.FormContent( { activity: this.model } ) );

			// Select box for the object
			if ( ! _.isUndefined( BP_Bookmarklet.params.objects ) && 1 < _.keys( BP_Bookmarklet.params.objects ).length ) {
				this.views.add( new bp.Views.FormTarget( { model: this.model } ) );
			}

			this.views.add( new bp.Views.FormSubmit( { model: this.model } ) );
		},

		resetForm: function() {
			_.each( this.views._views[''], function( view, index ) {
				if ( 0 !== index ) {
					view.remove();
				}
			} );

			// Reset the model
			this.model.clear();
			this.model.set( this.resetModel.attributes );
			this.maybeClose();
		},

		clearFeedback: function() {
			_.each( this.views._views[''], function( view ) {
				if ( 'message' === view.$el.prop( 'id' ) ) {
					view.remove();
				}
			} );
		},

		displayFeedback: function( model ) {
			if ( _.isUndefined( this.model.get( 'errors' ) ) ) {
				this.clearFeedback();
			} else {
				this.views.add( new bp.Views.activityWarning( model.get( 'errors' ) ) );
			}
		},

		postUpdate: function( event ) {
			var self = this,
			    meta = {}, submitError = [];

			if ( event ) {
				event.preventDefault();
			}

			// Set the content and meta
			_.each( this.$el.serializeArray(), function( pair ) {
				pair.name = pair.name.replace( '[]', '' );
				if ( 'bp-bookmarklet' === pair.name ) {
					self.model.set( 'content', pair.value );
				} else if ( -1 === _.indexOf( ['bp-bookmarklet-submit-button', 'bp-bookmarklet-post-in'], pair.name ) ) {
					if ( _.isUndefined( meta[ pair.name ] ) ) {
						meta[ pair.name ] = pair.value;
					} else {
						if ( ! _.isArray( meta[ pair.name ] ) ) {
							meta[ pair.name ] = [ meta[ pair.name ] ];
						}

						meta[ pair.name ].push( pair.value );
					}
				}
			} );

			this.clearFeedback();

			// Validate
			if ( true === this.model.get( 'fetching' ) || ! this.model.get( 'title' ) || ! this.model.get( 'url' ) ) {
				submitError.push( { 'type' : 'error', 'value' : BP_Bookmarklet.strings.errorGeneric } );
			}

			if ( 'group' === this.model.get( 'object' ) && ! this.model.get( 'item_id' ) ) {
				submitError.push( { 'type' : 'error', 'value' : BP_Bookmarklet.strings.errorObject } );
			}

			if ( 0 < submitError.length ) {
				_.each( submitError, function( error ) {
					self.views.add( new bp.Views.activityWarning( error ) );
				} );

				return;
			}

			// Set meta if some are found
			this.model.set( 'meta', meta );

			// Set the nonce
			var data = {
				'_wpnonce_bookmarklet_create': BP_Bookmarklet.params.post_nonce
			};

			// Submit
			bp.ajax.post( 'bp_bookmarklet_post_update', _.extend( data, this.model.attributes ) ).done( function( response ) {
				// Reset the form
				self.resetForm();

				// Display a feedback
				self.views.add( new bp.Views.activityWarning( { type: 'info', value: response.message } ) );

				// Close the window.
				self.maybeClose();

			} ).fail( function( response ) {
				// Display an error.
				self.views.add( new bp.Views.activityWarning( { type: 'error', value: response.error } ) );
			} );
		},

		maybeClose: function() {
			if ( 'doclose' === bp.BookMarklet.getLinkParams( null, 'jump' ) ) {
				setTimeout( function() {
					window.close();
				}, 1000 );
			}
		}
	} );

	bp.BookMarklet.start();

} )( bp, jQuery );
