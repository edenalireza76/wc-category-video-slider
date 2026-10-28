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

	function ratioOf( el ) {
		var raw = window.getComputedStyle( el ).getPropertyValue( '--rcvs-ratio' ).trim();
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
				state.lastFocus.focus();
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
			// Next frame so the transition runs.
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
				// Simple focus trap: keep focus on the close button / player.
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

	/* ---------------- Slider ---------------- */
	function Slider( root ) {
		this.root = root;
		this.opts = parseJSON( root.getAttribute( 'data-rcvs' ), {} );
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

		if ( ! this.track || ! this.slides.length ) {
			return;
		}

		this.rtl = window.getComputedStyle( this.track ).direction === 'rtl';
		root.classList.toggle( 'rcvs--rtl', this.rtl );

		this.bind();
		this.layout();
		root.classList.add( 'rcvs--ready' );
		this.startAutoplay();
	}

	Slider.prototype.perView = function () {
		var v = parseFloat( window.getComputedStyle( this.root ).getPropertyValue( '--rcvs-per-view' ) );
		return v > 0 ? v : 1;
	};

	Slider.prototype.maxScroll = function () {
		return Math.max( 0, this.track.scrollWidth - this.track.clientWidth );
	};

	Slider.prototype.atStart = function () {
		return Math.abs( this.track.scrollLeft ) <= 2;
	};

	Slider.prototype.atEnd = function () {
		return Math.abs( this.track.scrollLeft ) >= this.maxScroll() - 2;
	};

	Slider.prototype.pages = function () {
		return Math.max( 1, this.slides.length - Math.floor( this.perView() ) + 1 );
	};

	Slider.prototype.offsetOf = function ( slide ) {
		var tr = this.track.getBoundingClientRect();
		var r = slide.getBoundingClientRect();
		return this.rtl ? tr.right - r.right : r.left - tr.left;
	};

	Slider.prototype.current = function () {
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

	Slider.prototype.goTo = function ( index ) {
		var max = this.slides.length - 1;
		index = Math.max( 0, Math.min( max, index ) );
		var delta = this.offsetOf( this.slides[ index ] );
		this.track.scrollBy( { left: this.rtl ? -delta : delta, behavior: reduceMotion ? 'auto' : 'smooth' } );
	};

	Slider.prototype.step = function ( dir ) {
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

	Slider.prototype.buildDots = function () {
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
					self.pauseAutoplay( true );
				} );
			}( i ) );
			this.dotsWrap.appendChild( b );
			this.dots.push( b );
		}
	};

	Slider.prototype.update = function () {
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

	Slider.prototype.layout = function () {
		this.buildDots();
		this.update();
	};

	Slider.prototype.startAutoplay = function () {
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

	Slider.prototype.pauseAutoplay = function ( permanent ) {
		if ( permanent ) {
			this.stopped = true;
			clearInterval( this.timer );
		}
	};

	Slider.prototype.play = function ( btn ) {
		var data = parseJSON( btn.getAttribute( 'data-rcvs-video' ), null );
		if ( ! data || ! data.src ) {
			return;
		}
		var title = btn.getAttribute( 'data-rcvs-title' ) || '';
		var self = this;

		if ( this.opts.playMode === 'inline' ) {
			this.pauseAutoplay( true );
			var wrap = document.createElement( 'div' );
			wrap.className = 'rcvs__player';
			wrap.appendChild( buildPlayer( data, title ) );
			btn.parentNode.replaceChild( wrap, btn );
			var player = wrap.firstElementChild;
			if ( player && player.focus ) {
				player.setAttribute( 'tabindex', '-1' );
				player.focus( { preventScroll: true } );
			}
			return;
		}

		this.paused = true;
		getLightbox().open( data, title, ratioOf( this.root ), function () {
			self.paused = false;
		} );
	};

	Slider.prototype.bind = function () {
		var self = this;

		if ( this.prev ) {
			this.prev.addEventListener( 'click', function () {
				self.step( -1 );
				self.pauseAutoplay( true );
			} );
		}
		if ( this.next ) {
			this.next.addEventListener( 'click', function () {
				self.step( 1 );
				self.pauseAutoplay( true );
			} );
		}

		this.track.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest ? e.target.closest( '.rcvs__media' ) : null;
			if ( btn && self.track.contains( btn ) ) {
				e.preventDefault();
				self.play( btn );
			}
		} );

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

		// Pause on hover / focus / touch.
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
			self.pauseAutoplay( true );
		}, { passive: true } );

		var resizeTimer;
		var onResize = function () {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( function () {
				self.layout();
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
			if ( root.rcvsSlider ) {
				return;
			}
			root.rcvsSlider = new Slider( root );
		} );
	}

	window.RCVS = { init: init };

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			init( document );
		} );
	} else {
		init( document );
	}

	// Elementor editor / AJAX-rendered widgets.
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
