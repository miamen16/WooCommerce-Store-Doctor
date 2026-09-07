( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var link = document.querySelector( '.wcsd-view-details' );
		var modal = document.getElementById( 'wcsd-plugin-details-modal' );
		var close = modal ? modal.querySelector( '.wcsd-plugin-modal__close' ) : null;

		if ( ! link || ! modal ) {
			return;
		}

		function openModal() {
			modal.classList.add( 'is-open' );
			modal.setAttribute( 'aria-hidden', 'false' );
			document.body.classList.add( 'wcsd-modal-open' );
		}

		function closeModal() {
			modal.classList.remove( 'is-open' );
			modal.setAttribute( 'aria-hidden', 'true' );
			document.body.classList.remove( 'wcsd-modal-open' );
		}

		link.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			openModal();
		} );

		if ( close ) {
			close.addEventListener( 'click', closeModal );
		}

		modal.addEventListener( 'click', function ( event ) {
			if ( event.target === modal ) {
				closeModal();
			}
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && modal.classList.contains( 'is-open' ) ) {
				closeModal();
			}
		} );

		modal.querySelectorAll( '.wcsd-plugin-modal__tab' ).forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var target = tab.getAttribute( 'data-target' );

				modal.querySelectorAll( '.wcsd-plugin-modal__tab' ).forEach( function ( item ) {
					item.classList.remove( 'is-active' );
					item.setAttribute( 'aria-selected', 'false' );
				} );

				modal.querySelectorAll( '.wcsd-plugin-modal__panel' ).forEach( function ( panel ) {
					panel.classList.remove( 'is-active' );
				} );

				tab.classList.add( 'is-active' );
				tab.setAttribute( 'aria-selected', 'true' );
				var panel = modal.querySelector( target );
				if ( panel ) {
					panel.classList.add( 'is-active' );
				}
			} );
		} );
	} );
} )();
