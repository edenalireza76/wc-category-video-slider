/* global jQuery, wp, rcvsAdmin */
( function ( $ ) {
	'use strict';

	var i18n = window.rcvsAdmin || {};

	function mediaFrame( opts, onSelect ) {
		var frame = wp.media( {
			title: opts.title,
			button: { text: opts.button },
			library: { type: opts.type },
			multiple: opts.multiple || false
		} );
		frame.on( 'select', function () {
			onSelect( frame.state().get( 'selection' ).toJSON() );
		} );
		frame.open();
	}

	/* ---------- Video list editor (categories + galleries) ---------- */
	function initRepeater( $box ) {
		var $list = $box.find( '.rcvs-items' );
		var tmpl = wp.template( 'rcvs-item' );

		function newKey() {
			return 'n' + Date.now().toString( 36 ) + Math.floor( Math.random() * 1e6 ).toString( 36 );
		}

		function syncMain() {
			var $items = $list.children( '.rcvs-item' );
			if ( $items.length && ! $items.find( '.rcvs-main-radio:checked' ).length ) {
				$items.first().find( '.rcvs-main-radio' ).prop( 'checked', true );
			}
			$items.each( function () {
				$( this ).toggleClass( 'is-main', $( this ).find( '.rcvs-main-radio' ).is( ':checked' ) );
			} );
		}

		function refresh() {
			var $items = $list.children( '.rcvs-item' );
			$box.find( '.rcvs-empty' ).prop( 'hidden', $items.length > 0 );
			$items.each( function ( i ) {
				$( this ).find( '.rcvs-item__index' ).text( '#' + ( i + 1 ) );
			} );
			syncMain();
		}

		function setVideo( $item, att ) {
			$item.find( '.rcvs-video-id' ).val( att.id );
			$item.find( '.rcvs-video-name' ).text( att.filename || att.title || '' );
			var $title = $item.find( '.rcvs-title-input' );
			if ( ! $title.val() && att.title ) {
				$title.val( att.title ).trigger( 'input' );
			}
		}

		function setCover( $item, att ) {
			var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
			$item.find( '.rcvs-cover-id' ).val( att.id );
			$item.find( '.rcvs-cover-preview' ).addClass( 'has-image' ).empty().append( $( '<img>', { src: url, alt: '' } ) );
			$item.find( '.rcvs-remove-cover' ).prop( 'hidden', false );
		}

		function addItem( data ) {
			var $item = $( tmpl( { key: newKey() } ) );
			$list.append( $item );
			if ( data && data.video ) {
				setVideo( $item, data.video );
			}
			refresh();
			return $item;
		}

		$box.on( 'click', '.rcvs-add', function () {
			addItem().find( '.rcvs-title-input' ).trigger( 'focus' );
		} );

		$box.on( 'click', '.rcvs-add-bulk', function () {
			mediaFrame( { title: i18n.chooseVideos, button: i18n.addSelected, type: 'video', multiple: 'add' }, function ( list ) {
				list.forEach( function ( att ) {
					addItem( { video: att } );
				} );
			} );
		} );

		$box.on( 'change', '.rcvs-type', function () {
			var external = this.value === 'external';
			$( this ).closest( '.rcvs-item' ).toggleClass( 'rcvs-item--external', external ).toggleClass( 'rcvs-item--upload', ! external );
		} );

		$box.on( 'change', '.rcvs-main-radio', syncMain );

		$box.on( 'click', '.rcvs-pick-video', function () {
			var $item = $( this ).closest( '.rcvs-item' );
			mediaFrame( { title: i18n.chooseVideo, button: i18n.useThis, type: 'video' }, function ( list ) {
				if ( list[ 0 ] ) {
					setVideo( $item, list[ 0 ] );
				}
			} );
		} );

		$box.on( 'click', '.rcvs-pick-cover', function () {
			var $item = $( this ).closest( '.rcvs-item' );
			mediaFrame( { title: i18n.chooseCover, button: i18n.useThis, type: 'image' }, function ( list ) {
				if ( list[ 0 ] ) {
					setCover( $item, list[ 0 ] );
				}
			} );
		} );

		$box.on( 'click', '.rcvs-remove-cover', function () {
			var $item = $( this ).closest( '.rcvs-item' );
			$item.find( '.rcvs-cover-id' ).val( '' );
			$item.find( '.rcvs-cover-preview' ).removeClass( 'has-image' ).html( '<span class="dashicons dashicons-format-image"></span>' );
			$( this ).prop( 'hidden', true );
		} );

		$box.on( 'click', '.rcvs-item__remove', function () {
			if ( window.confirm( i18n.confirmRemove ) ) {
				$( this ).closest( '.rcvs-item' ).remove();
				refresh();
			}
		} );

		$box.on( 'click', '.rcvs-item__toggle', function () {
			var collapsed = $( this ).closest( '.rcvs-item' ).toggleClass( 'is-collapsed' ).hasClass( 'is-collapsed' );
			$( this ).attr( 'aria-expanded', collapsed ? 'false' : 'true' );
		} );

		$box.on( 'input', '.rcvs-title-input', function () {
			var val = $.trim( this.value );
			$( this ).closest( '.rcvs-item' ).find( '.rcvs-item__label' ).text( val || i18n.untitled );
		} );

		if ( $.fn.sortable ) {
			$list.sortable( {
				handle: '.rcvs-item__handle',
				placeholder: 'rcvs-item-placeholder',
				forcePlaceholderSize: true,
				axis: 'y',
				update: refresh
			} );
		}

		refresh();
	}

	/* ---------- Settings page ---------- */
	function initSettings() {
		var $wrap = $( '.rcvs-settings' );
		if ( ! $wrap.length ) {
			return;
		}

		function showTab( id ) {
			$wrap.find( '.nav-tab' ).removeClass( 'nav-tab-active' ).filter( '[data-tab="' + id + '"]' ).addClass( 'nav-tab-active' );
			$wrap.find( '.rcvs-tab-panel' ).removeClass( 'is-active' ).filter( '#rcvs-tab-' + id ).addClass( 'is-active' );
			try {
				window.sessionStorage.setItem( 'rcvsTab', id );
			} catch ( e ) {}
		}

		$wrap.on( 'click', '.nav-tab', function ( e ) {
			e.preventDefault();
			showTab( $( this ).data( 'tab' ) );
		} );

		try {
			var saved = window.sessionStorage.getItem( 'rcvsTab' );
			if ( saved && $wrap.find( '#rcvs-tab-' + saved ).length ) {
				showTab( saved );
			}
		} catch ( e ) {}

		$wrap.on( 'input change', '.rcvs-color input[type="color"]', function () {
			$( this ).siblings( 'input[type="text"]' ).val( this.value );
		} );
		$wrap.on( 'input', '.rcvs-color input[type="text"]', function () {
			if ( /^#[0-9a-f]{6}$/i.test( this.value ) ) {
				$( this ).siblings( 'input[type="color"]' ).val( this.value );
			}
		} );

		$wrap.on( 'click', '.rcvs-reset', function ( e ) {
			if ( ! window.confirm( $( this ).data( 'confirm' ) ) ) {
				e.preventDefault();
			}
		} );
	}

	/* ---------- Image fields ---------- */
	function initImageFields() {
		// Image fields (custom play icon, category image).
		$( document ).on( 'click', '.rcvs-image-field__pick', function () {
			var $field = $( this ).closest( '.rcvs-image-field' );
			mediaFrame( { title: i18n.chooseImage, button: i18n.useThis, type: 'image' }, function ( list ) {
				var att = list[ 0 ];
				if ( ! att ) {
					return;
				}
				var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
				$field.find( 'input[type="hidden"]' ).val( att.id );
				$field.find( '.rcvs-image-field__preview' ).empty().append( $( '<img>', { src: url, alt: '' } ) );
				$field.find( '.rcvs-image-field__remove' ).prop( 'hidden', false );
				$field.closest( '.rcvs-settings' ).find( '.rcvs-icon-picker input[value="custom"]' ).prop( 'checked', true );
			} );
		} );
		$( document ).on( 'click', '.rcvs-image-field__remove', function () {
			var $field = $( this ).closest( '.rcvs-image-field' );
			$field.find( 'input[type="hidden"]' ).val( '' );
			$field.find( '.rcvs-image-field__preview' ).empty();
			$( this ).prop( 'hidden', true );
		} );

	}

	/* ---------- Copy shortcode ---------- */
	function initCopy() {
		$( document ).on( 'click', '.rcvs-copy', function () {
			var $btn = $( this );
			var text = String( $btn.data( 'copy' ) || '' );
			var done = function () {
				var old = $btn.attr( 'data-old' ) || $btn.html();
				$btn.attr( 'data-old', old ).text( i18n.copied || 'Copied!' );
				setTimeout( function () {
					$btn.html( old );
				}, 1400 );
			};
			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( text ).then( done );
			} else {
				var $tmp = $( '<textarea>' ).val( text ).css( { position: 'fixed', opacity: 0 } ).appendTo( 'body' );
				$tmp[ 0 ].select();
				try {
					document.execCommand( 'copy' );
					done();
				} catch ( e ) {}
				$tmp.remove();
			}
		} );
	}

	$( function () {
		// Category screen: place our row right after the description editor.
		var $termRow = $( '.rcvs-term-row' );
		var $desc = $( '.term-description-wrap' ).first();
		if ( $termRow.length && $desc.length ) {
			$termRow.insertAfter( $desc );
		}

		$( '[data-rcvs-repeater]' ).each( function () {
			initRepeater( $( this ) );
		} );
		initSettings();
		initImageFields();
		initCopy();
	} );
}( jQuery ) );
