/* global jQuery, wp, rcvsAdmin */
( function ( $ ) {
	'use strict';

	var i18n = window.rcvsAdmin || {};

	/* ---------- Category edit screen ---------- */
	function initTermBox() {
		var $box = $( '#rcvs-term-box' );
		if ( ! $box.length ) {
			return;
		}

		// Place the row right after the description editor.
		var $row = $box.closest( 'tr' );
		var $desc = $( '.term-description-wrap' ).first();
		if ( $row.length && $desc.length ) {
			$row.insertAfter( $desc );
		}

		var $list = $box.find( '.rcvs-items' );
		var tmpl = wp.template( 'rcvs-item' );

		function newKey() {
			return 'n' + Date.now().toString( 36 ) + Math.floor( Math.random() * 1e6 ).toString( 36 );
		}

		function refresh() {
			var $items = $list.children( '.rcvs-item' );
			$box.find( '.rcvs-empty' ).prop( 'hidden', $items.length > 0 );
			$items.each( function ( i ) {
				$( this ).find( '.rcvs-item__index' ).text( '#' + ( i + 1 ) );
			} );
		}

		function addItem( data ) {
			var $item = $( tmpl( { key: newKey(), type: 'upload' } ) );
			$list.append( $item );
			if ( data && data.video ) {
				setVideo( $item, data.video );
			}
			refresh();
			return $item;
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

		function mediaFrame( opts, onSelect ) {
			var frame = wp.media( {
				title: opts.title,
				button: { text: opts.button },
				library: { type: opts.type },
				multiple: !! opts.multiple
			} );
			frame.on( 'select', function () {
				var selection = frame.state().get( 'selection' ).toJSON();
				onSelect( selection );
			} );
			frame.open();
		}

		// Add one.
		$box.on( 'click', '.rcvs-add', function () {
			var $item = addItem();
			$item.find( '.rcvs-title-input' ).trigger( 'focus' );
		} );

		// Bulk add from library.
		$box.on( 'click', '.rcvs-add-bulk', function () {
			mediaFrame( { title: i18n.chooseVideos, button: i18n.addSelected, type: 'video', multiple: 'add' }, function ( list ) {
				list.forEach( function ( att ) {
					addItem( { video: att } );
				} );
			} );
		} );

		// Source type switch.
		$box.on( 'change', '.rcvs-type', function () {
			var $item = $( this ).closest( '.rcvs-item' );
			var external = this.value === 'external';
			$item.toggleClass( 'rcvs-item--external', external ).toggleClass( 'rcvs-item--upload', ! external );
		} );

		// Pick video.
		$box.on( 'click', '.rcvs-pick-video', function () {
			var $item = $( this ).closest( '.rcvs-item' );
			mediaFrame( { title: i18n.chooseVideo, button: i18n.useThis, type: 'video' }, function ( list ) {
				if ( list[ 0 ] ) {
					setVideo( $item, list[ 0 ] );
				}
			} );
		} );

		// Pick cover.
		$box.on( 'click', '.rcvs-pick-cover', function () {
			var $item = $( this ).closest( '.rcvs-item' );
			mediaFrame( { title: i18n.chooseCover, button: i18n.useThis, type: 'image' }, function ( list ) {
				if ( list[ 0 ] ) {
					setCover( $item, list[ 0 ] );
				}
			} );
		} );

		// Remove cover.
		$box.on( 'click', '.rcvs-remove-cover', function () {
			var $item = $( this ).closest( '.rcvs-item' );
			$item.find( '.rcvs-cover-id' ).val( '' );
			$item.find( '.rcvs-cover-preview' ).removeClass( 'has-image' ).html( '<span class="dashicons dashicons-format-image"></span>' );
			$( this ).prop( 'hidden', true );
		} );

		// Remove item.
		$box.on( 'click', '.rcvs-item__remove', function () {
			if ( window.confirm( i18n.confirmRemove ) ) {
				$( this ).closest( '.rcvs-item' ).remove();
				refresh();
			}
		} );

		// Collapse.
		$box.on( 'click', '.rcvs-item__toggle', function () {
			var $item = $( this ).closest( '.rcvs-item' );
			var collapsed = $item.toggleClass( 'is-collapsed' ).hasClass( 'is-collapsed' );
			$( this ).attr( 'aria-expanded', collapsed ? 'false' : 'true' );
		} );

		// Live label.
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

		// Native color picker <-> text field (text keeps rgba values).
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

	$( function () {
		initTermBox();
		initSettings();
	} );
}( jQuery ) );
