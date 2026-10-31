/*! WC Category Video Slider – front-end (no dependencies) */
( function () {
	'use strict';

	var i18n = window.rcvsFront || {};
	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	function parseJSON( str, fallback ) {
		try {
			return JSON.parse( str ) || fallback;
		} catch ( e ) {
			return fallback;
		}
	}

	function ratioOf( el, prop ) {
		var raw = window.getComputedStyle( el ).getPropertyValue( prop || '--rcvs-ratio' ).trim();
		var parts = raw.split( '/' );
		var r = parts.length === 2 ? parseFloat( parts[ 0 ] ) / parseFloat( parts[ 1 ] ) : parseFloat( raw );
		return r > 0 && isFinite( r ) ? r : 16 / 9;
	}

	/* ---------------- Player ---------------- */
	function buildPlayer( data, title ) {
		var el;
		if ( data.type === 'file' ) {
			el = document.createElement( 'video' );
			el.controls = true;
			el.autoplay = true;
			el.playsInline = true;
			el.setAttribute( 'playsinline', '' );
			el.setAttribute( 'preload', 'metadata' );
			if ( data.poster ) {
				el.poster = data.poster;
			}
			var source = document.createElement( 'source' );
			source.src = data.src;
			if ( data.mime ) {
				source.type = data.mime;
			}
			el.appendChild( source );
			var p = el.play && el.play();
			if ( p && p.catch ) {
				p.catch( function () {} );
			}
		} else {
			el = document.createElement( 'iframe' );
			el.src = data.src;
			el.title = title || i18n.dialog || 'Video';
			el.setAttribute( 'allow', 'autoplay; encrypted-media; picture-in-picture; fullscreen' );
			el.setAttribute( 'allowfullscreen', '' );
			el.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );
		}
		return el;
	}

	/* ---------------- Lightbox (shared) ---------------- */
	var lightbox = null;

	function getLightbox() {
		if ( lightbox ) {
			return lightbox;
		}
		var root = document.createElement( 'div' );
		root.className = 'rcvs-lightbox';
		root.setAttribute( 'role', 'dialog' );
		root.setAttribute( 'aria-modal', 'true' );
		root.setAttribute( 'aria-label', i18n.dialog || 'Video player' );
		root.hidden = true;
		root.innerHTML =
			'<button type="button" class="rcvs-lightbox__close" aria-label="' + ( i18n.close || 'Close' ) + '">' +
			'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>' +
			'</button><div class="rcvs-lightbox__frame"></div>';
		document.body.appendChild( root );

		var frame = root.querySelector( '.rcvs-lightbox__frame' );
		var closeBtn = root.querySelector( '.rcvs-lightbox__close' );
		var state = { lastFocus: null, ratio: 16 / 9, onClose: null };

		function size() {
			var maxW = parseFloat( window.getComputedStyle( root ).getPropertyValue( '--rcvs-lb-width' ) ) || 1000;
			var w = Math.min( maxW, window.innerWidth - 32, ( window.innerHeight - 120 ) * state.ratio );
			w = Math.max( 200, w );
			frame.style.width = w + 'px';
			frame.style.height = ( w / state.ratio ) + 'px';
		}

		function close() {
			if ( ! root.classList.contains( 'is-open' ) ) {
				return;
			}
			root.classList.remove( 'is-open' );
			document.documentElement.classList.remove( 'rcvs-lock' );
			frame.innerHTML = '';
			window.removeEventListener( 'resize', size );
			setTimeout( function () {
				if ( ! root.classList.contains( 'is-open' ) ) {
					root.hidden = true;
				}
			}, 260 );
			if ( state.lastFocus && state.lastFocus.focus ) {
				state.lastFocus.focus( { preventScroll: true } );
			}
			if ( state.onClose ) {
				state.onClose();
			}
		}

		function open( data, title, ratio, onClose ) {
			state.lastFocus = document.activeElement;
			state.ratio = ratio || 16 / 9;
			state.onClose = onClose || null;
			frame.innerHTML = '';
			frame.appendChild( buildPlayer( data, title ) );
			root.setAttribute( 'aria-label', title || i18n.dialog || '' );
			root.hidden = false;
			size();
			window.addEventListener( 'resize', size );
			document.documentElement.classList.add( 'rcvs-lock' );
			window.requestAnimationFrame( function () {
				root.classList.add( 'is-open' );
				closeBtn.focus();
			} );
		}

		closeBtn.addEventListener( 'click', close );
		root.addEventListener( 'click', function ( e ) {
			if ( e.target === root ) {
				close();
			}
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( ! root.classList.contains( 'is-open' ) ) {
				return;
			}
			if ( e.key === 'Escape' ) {
				close();
			} else if ( e.key === 'Tab' ) {
				var focusables = [ closeBtn, frame.firstElementChild ].filter( Boolean );
				var idx = focusables.indexOf( document.activeElement );
				e.preventDefault();
				var next = e.shiftKey ? idx - 1 : idx + 1;
				if ( next < 0 ) {
					next = focusables.length - 1;
				}
				if ( next >= focusables.length ) {
					next = 0;
				}
				focusables[ next ].focus();
			}
		} );

		lightbox = { open: open, close: close };
		return lightbox;
	}

	/* ---------------- Gallery ---------------- */
	function Gallery( root ) {
		this.root = root;
		this.opts = parseJSON( root.getAttribute( 'data-rcvs' ), {} );
		this.layout = this.opts.layout || 'slider';
		this.main = root.querySelector( '.rcvs__main' );
		this.track = root.querySelector( '.rcvs__track' );
		this.slides = Array.prototype.slice.call( this.track ? this.track.children : [] );
		this.prev = root.querySelector( '.rcvs__arrow--prev' );
		this.next = root.querySelector( '.rcvs__arrow--next' );
		this.dotsWrap = root.querySelector( '.rcvs__dots' );
		this.dots = [];
		this.timer = null;
		this.paused = false;
		this.stopped = false;
		this.raf = 0;

		this.bindPlay();

		if ( this.track && this.slides.length && this.layout !== 'grid' ) {
			this.rtl = window.getComputedStyle( this.track ).direction === 'rtl';
			root.classList.toggle( 'rcvs--rtl', this.rtl );
			this.bindSlider();
			this.layoutUpdate();
			this.startAutoplay();
		}
		root.classList.add( 'rcvs--ready' );
	}

	Gallery.prototype.perView = function () {
		var v = parseFloat( window.getComputedStyle( this.root ).getPropertyValue( '--rcvs-per-view' ) );
		return v > 0 ? v : 1;
	};

	Gallery.prototype.maxScroll = function () {
		return Math.max( 0, this.track.scrollWidth - this.track.clientWidth );
	};

	Gallery.prototype.atStart = function () {
		return Math.abs( this.track.scrollLeft ) <= 2;
	};

	Gallery.prototype.atEnd = function () {
		return Math.abs( this.track.scrollLeft ) >= this.maxScroll() - 2;
	};

	Gallery.prototype.pages = function () {
		return Math.max( 1, this.slides.length - Math.floor( this.perView() ) + 1 );
	};

	Gallery.prototype.offsetOf = function ( slide ) {
		var tr = this.track.getBoundingClientRect();
		var r = slide.getBoundingClientRect();
		return this.rtl ? tr.right - r.right : r.left - tr.left;
	};

	Gallery.prototype.current = function () {
		if ( this.atEnd() && this.maxScroll() > 0 ) {
			return this.pages() - 1;
		}
		var best = 0;
		var bestDist = Infinity;
		for ( var i = 0; i < this.slides.length; i++ ) {
			var d = Math.abs( this.offsetOf( this.slides[ i ] ) );
			if ( d < bestDist ) {
				bestDist = d;
				best = i;
			}
		}
		return Math.min( best, this.pages() - 1 );
	};

	Gallery.prototype.goTo = function ( index ) {
		index = Math.max( 0, Math.min( this.slides.length - 1, index ) );
		var delta = this.offsetOf( this.slides[ index ] );
		this.track.scrollBy( { left: this.rtl ? -delta : delta, behavior: reduceMotion ? 'auto' : 'smooth' } );
	};

	Gallery.prototype.reveal = function ( slide ) {
		// Scroll the thumbnail into view inside the track only (never the page).
		var tr = this.track.getBoundingClientRect();
		var r = slide.getBoundingClientRect();
		if ( r.left < tr.left || r.right > tr.right ) {
			this.goTo( this.slides.indexOf( slide ) );
		}
	};

	Gallery.prototype.step = function ( dir ) {
		if ( dir > 0 ) {
			if ( this.atEnd() ) {
				if ( this.opts.loop ) {
					this.goTo( 0 );
				}
				return;
			}
			this.goTo( this.current() + 1 );
		} else {
			if ( this.atStart() ) {
				if ( this.opts.loop ) {
					this.goTo( this.slides.length - 1 );
				}
				return;
			}
			this.goTo( this.current() - 1 );
		}
	};

	Gallery.prototype.buildDots = function () {
		if ( ! this.dotsWrap ) {
			return;
		}
		var count = this.maxScroll() > 2 ? this.pages() : 0;
		if ( count === this.dots.length ) {
			return;
		}
		this.dotsWrap.innerHTML = '';
		this.dots = [];
		var self = this;
		for ( var i = 0; i < count; i++ ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'rcvs__dot';
			b.setAttribute( 'aria-label', ( i18n.goTo || 'Go to slide %d' ).replace( '%d', i + 1 ) );
			( function ( n ) {
				b.addEventListener( 'click', function () {
					self.goTo( n );
					self.stopAutoplay();
				} );
			}( i ) );
			this.dotsWrap.appendChild( b );
			this.dots.push( b );
		}
	};

	Gallery.prototype.update = function () {
		var scrollable = this.maxScroll() > 2;
		this.root.classList.toggle( 'rcvs--static', ! scrollable );
		if ( this.prev ) {
			this.prev.disabled = ! this.opts.loop && this.atStart();
		}
		if ( this.next ) {
			this.next.disabled = ! this.opts.loop && this.atEnd();
		}
		var cur = this.current();
		for ( var i = 0; i < this.dots.length; i++ ) {
			var active = i === cur;
			this.dots[ i ].classList.toggle( 'is-active', active );
			if ( active ) {
				this.dots[ i ].setAttribute( 'aria-current', 'true' );
			} else {
				this.dots[ i ].removeAttribute( 'aria-current' );
			}
		}
	};

	Gallery.prototype.layoutUpdate = function () {
		this.buildDots();
		this.update();
	};

	Gallery.prototype.startAutoplay = function () {
		var self = this;
		if ( ! this.opts.autoplay || this.stopped || reduceMotion ) {
			return;
		}
		clearInterval( this.timer );
		this.timer = setInterval( function () {
			if ( ! self.paused && ! document.hidden && self.maxScroll() > 2 ) {
				if ( self.atEnd() ) {
					self.goTo( 0 );
				} else {
					self.step( 1 );
				}
			}
		}, Math.max( 1000, parseInt( this.opts.delay, 10 ) || 5000 ) );
	};

	Gallery.prototype.stopAutoplay = function () {
		this.stopped = true;
		clearInterval( this.timer );
	};

	/* ----- Playback ----- */
	Gallery.prototype.playInline = function ( btn ) {
		var data = parseJSON( btn.getAttribute( 'data-rcvs-video' ), null );
		if ( ! data || ! data.src ) {
			return;
		}
		var wrap = document.createElement( 'div' );
		wrap.className = 'rcvs__player';
		wrap.appendChild( buildPlayer( data, btn.getAttribute( 'data-rcvs-title' ) || '' ) );
		btn.parentNode.replaceChild( wrap, btn );
		var player = wrap.firstElementChild;
		if ( player && player.focus ) {
			player.setAttribute( 'tabindex', '-1' );
			player.focus( { preventScroll: true } );
		}
	};

	Gallery.prototype.playLightbox = function ( btn ) {
		var data = parseJSON( btn.getAttribute( 'data-rcvs-video' ), null );
		if ( ! data || ! data.src ) {
			return;
		}
		var self = this;
		this.paused = true;
		var r = ratioOf( this.root );
		if ( /^\d+\/\d+$/.test( data.ratio || '' ) ) {
			var p = data.ratio.split( '/' );
			r = parseFloat( p[ 0 ] ) / parseFloat( p[ 1 ] );
		}
		getLightbox().open( data, btn.getAttribute( 'data-rcvs-title' ) || '', r, function () {
			self.paused = false;
		} );
	};

	/* Featured layout: load a gallery video into the main box. */
	Gallery.prototype.playInMain = function ( btn ) {
		var data = parseJSON( btn.getAttribute( 'data-rcvs-video' ), null );
		if ( ! data || ! data.src || ! this.main ) {
			return;
		}
		var stage = this.main.querySelector( '.rcvs__stage' );
		var title = btn.getAttribute( 'data-rcvs-label' ) || '';
		var index = btn.getAttribute( 'data-index' );

		// "Auto" ratio: reshape the main box to the selected video (vertical / horizontal).
		if ( this.opts.mainRatio === 'auto' ) {
			var ratio = /^\d+\/\d+$/.test( data.ratio || '' ) ? data.ratio : '16/9';
			var parts = ratio.split( '/' );
			this.main.style.setProperty( '--rcvs-stage-ratio', ratio );
			this.main.style.setProperty( '--rcvs-stage-n', String( parseFloat( parts[ 0 ] ) / parseFloat( parts[ 1 ] ) ) );
		}

		var wrap = document.createElement( 'div' );
		wrap.className = 'rcvs__player';
		wrap.appendChild( buildPlayer( data, btn.getAttribute( 'data-rcvs-title' ) || '' ) );
		stage.innerHTML = '';
		stage.appendChild( wrap );

		var titleEl = this.main.querySelector( '.rcvs__main-title' );
		if ( titleEl ) {
			titleEl.textContent = title;
			titleEl.hidden = ! title;
		}
		this.main.setAttribute( 'data-current', index );
		this.markCurrent( index );

		// Bring the main box into view if the visitor scrolled past it.
		var rect = this.main.getBoundingClientRect();
		if ( rect.top < 0 || rect.top > window.innerHeight * 0.6 ) {
			this.main.scrollIntoView( { behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' } );
		}
	};

	Gallery.prototype.markCurrent = function ( index ) {
		var self = this;
		this.slides.forEach( function ( slide ) {
			var on = slide.getAttribute( 'data-index' ) === String( index );
			slide.classList.toggle( 'is-current', on );
			var b = slide.querySelector( '.rcvs__media' );
			if ( b ) {
				if ( on ) {
					b.setAttribute( 'aria-current', 'true' );
				} else {
					b.removeAttribute( 'aria-current' );
				}
			}
			if ( on && self.track && self.layout !== 'grid' ) {
				self.reveal( slide );
			}
		} );
	};

	Gallery.prototype.bindPlay = function () {
		var self = this;
		this.root.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '.rcvs__media' ) : null;
			if ( ! btn || ! self.root.contains( btn ) ) {
				return;
			}
			e.preventDefault();
			self.stopAutoplay();

			if ( btn.classList.contains( 'rcvs__media--main' ) ) {
				self.playInline( btn );
				return;
			}
			var action = self.layout === 'featured' ? self.opts.thumbAction : self.opts.playMode;
			if ( action === 'main' ) {
				self.playInMain( btn );
			} else if ( action === 'inline' ) {
				self.playInline( btn );
			} else {
				self.playLightbox( btn );
			}
		} );
	};

	Gallery.prototype.bindSlider = function () {
		var self = this;

		if ( this.prev ) {
			this.prev.addEventListener( 'click', function () {
				self.step( -1 );
				self.stopAutoplay();
			} );
		}
		if ( this.next ) {
			this.next.addEventListener( 'click', function () {
				self.step( 1 );
				self.stopAutoplay();
			} );
		}

		this.track.addEventListener( 'scroll', function () {
			if ( self.raf ) {
				return;
			}
			self.raf = window.requestAnimationFrame( function () {
				self.raf = 0;
				self.update();
			} );
		}, { passive: true } );

		this.track.addEventListener( 'keydown', function ( e ) {
			if ( e.target !== self.track ) {
				return;
			}
			var forward = self.rtl ? 'ArrowLeft' : 'ArrowRight';
			var back = self.rtl ? 'ArrowRight' : 'ArrowLeft';
			if ( e.key === forward ) {
				e.preventDefault();
				self.step( 1 );
			} else if ( e.key === back ) {
				e.preventDefault();
				self.step( -1 );
			}
		} );

		this.root.addEventListener( 'mouseenter', function () {
			self.paused = true;
		} );
		this.root.addEventListener( 'mouseleave', function () {
			self.paused = false;
		} );
		this.root.addEventListener( 'focusin', function () {
			self.paused = true;
		} );
		this.root.addEventListener( 'focusout', function () {
			self.paused = false;
		} );
		this.track.addEventListener( 'touchstart', function () {
			self.stopAutoplay();
		}, { passive: true } );

		var resizeTimer;
		var onResize = function () {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( function () {
				self.layoutUpdate();
			}, 120 );
		};
		if ( 'ResizeObserver' in window ) {
			new window.ResizeObserver( onResize ).observe( this.track );
		} else {
			window.addEventListener( 'resize', onResize );
		}
	};

	/* ---------------- Init ---------------- */
	function init( scope ) {
		var ctx = scope && scope.querySelectorAll ? scope : document;
		var roots = ctx.classList && ctx.classList.contains( 'rcvs' ) ? [ ctx ] : ctx.querySelectorAll( '.rcvs' );
		Array.prototype.forEach.call( roots, function ( root ) {
			if ( root.rcvsGallery || ! root.hasAttribute( 'data-rcvs' ) ) {
				return;
			}
			root.rcvsGallery = new Gallery( root );
		} );
	}

	window.RCVS = { init: init };

	function boot() {
		init( document );

		// Content injected later (WoodMart AJAX shop, filters, infinite scroll, Elementor loop AJAX, popups).
		if ( 'MutationObserver' in window && document.body ) {
			var pending = false;
			new window.MutationObserver( function ( mutations ) {
				if ( pending ) {
					return;
				}
				for ( var i = 0; i < mutations.length; i++ ) {
					var added = mutations[ i ].addedNodes;
					for ( var j = 0; j < added.length; j++ ) {
						var n = added[ j ];
						if ( n.nodeType === 1 && ( ( n.classList && n.classList.contains( 'rcvs' ) ) || ( n.querySelector && n.querySelector( '.rcvs' ) ) ) ) {
							pending = true;
							window.requestAnimationFrame( function () {
								pending = false;
								init( document );
							} );
							return;
						}
					}
				}
			} ).observe( document.body, { childList: true, subtree: true } );
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	// Elementor editor preview.
	function hookElementor() {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/rcvs-video-slider.default', function ( $scope ) {
				init( $scope && $scope[ 0 ] ? $scope[ 0 ] : document );
			} );
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/global', function ( $scope ) {
				if ( $scope && $scope[ 0 ] && $scope[ 0 ].querySelector && $scope[ 0 ].querySelector( '.rcvs' ) ) {
					init( $scope[ 0 ] );
				}
			} );
		}
	}
	if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
		hookElementor();
	} else if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', hookElementor );
	}
}() );
