( function ( $ ) {
	'use strict';

	$( function () {
		initScanButton();
		initFixModal();
		initRevertButtons();
		initHistoryChart();

		var resizeTimer;
		$( window ).on( 'resize', function () {
			clearTimeout( resizeTimer );
			resizeTimer = setTimeout( initHistoryChart, 150 );
		} );
	} );

	function initScanButton() {
		var $button = $( '#wcsd-run-scan' );
		var $status = $( '#wcsd-scan-status' );

		if ( ! $button.length ) {
			return;
		}

		$button.on( 'click', function () {
			$button.prop( 'disabled', true );
			$status.text( WCSD.i18n.scanning );

			$.post( WCSD.ajaxUrl, {
				action: 'wcsd_run_scan',
				nonce: WCSD.nonce
			} )
				.done( function ( response ) {
					if ( response.success ) {
						window.location.reload();
					} else {
						$status.text( ( response.data && response.data.message ) || WCSD.i18n.error );
						$button.prop( 'disabled', false );
					}
				} )
				.fail( function () {
					$status.text( WCSD.i18n.error );
					$button.prop( 'disabled', false );
				} );
		} );
	}

	/**
	 * Preview -> Confirm -> Apply modal for the Issue Center "Fix" buttons.
	 */
	function initFixModal() {
		var $modal      = $( '#wcsd-fix-modal' );
		var $title      = $( '#wcsd-fix-modal-title' );
		var $body       = $( '#wcsd-fix-modal-body' );
		var $applyBtn   = $( '#wcsd-fix-modal-apply' );
		var $cancelBtn  = $( '#wcsd-fix-modal-cancel' );
		var currentType = null;

		if ( ! $modal.length ) {
			return;
		}

		$( '.wcsd-fix-button' ).on( 'click', function () {
			currentType = $( this ).data( 'issue-type' );
			var label   = $( this ).data( 'issue-label' );

			$title.text( label );
			$applyBtn.prop( 'disabled', true );
			$body.html( '<p class="wcsd-modal-loading">' + WCSD.i18n.loadingPreview + '</p>' );
			$modal.show();

			$.post( WCSD.ajaxUrl, {
				action: 'wcsd_fix_preview',
				nonce: WCSD.nonce,
				issue_type: currentType
			} )
				.done( function ( response ) {
					if ( ! response.success ) {
						$body.html( '<p>' + escapeHtml( response.data.message || WCSD.i18n.error ) + '</p>' );
						return;
					}
					renderPreview( response.data );
				} )
				.fail( function () {
					$body.html( '<p>' + WCSD.i18n.error + '</p>' );
				} );
		} );

		function renderPreview( data ) {
			if ( ! data.items.length ) {
				$body.html( '<p>' + WCSD.i18n.noAutoFix + '</p>' );
				$applyBtn.prop( 'disabled', true );
				return;
			}

			var html = '<p>' + escapeHtml( data.fixer_label ) + ' — ' + data.total + ' item(s)</p>';
			html += '<table class="widefat striped"><thead><tr>';
			html += '<th>Item</th><th>Current</th><th>Proposed</th></tr></thead><tbody>';

			data.items.forEach( function ( item ) {
				html += '<tr><td>' + escapeHtml( item.label ) + '</td>';
				html += '<td>' + escapeHtml( item.current ) + '</td>';
				html += '<td>' + escapeHtml( item.proposed ) + '</td></tr>';
			} );

			html += '</tbody></table>';

			if ( data.total > data.items.length ) {
				html += '<p class="description">+ ' + ( data.total - data.items.length ) + ' more not shown.</p>';
			}

			$body.html( html );
			$applyBtn.prop( 'disabled', false );
		}

		$applyBtn.on( 'click', function () {
			if ( ! currentType ) {
				return;
			}

			if ( ! window.confirm( WCSD.i18n.confirmApply ) ) {
				return;
			}

			$applyBtn.prop( 'disabled', true );
			$body.html( '<p class="wcsd-modal-loading">' + WCSD.i18n.applying + '</p>' );

			$.post( WCSD.ajaxUrl, {
				action: 'wcsd_fix_apply',
				nonce: WCSD.nonce,
				issue_type: currentType
			} )
				.done( function ( response ) {
					if ( response.success ) {
						window.location.reload();
					} else {
						$body.html( '<p>' + escapeHtml( response.data.message || WCSD.i18n.error ) + '</p>' );
					}
				} )
				.fail( function () {
					$body.html( '<p>' + WCSD.i18n.error + '</p>' );
				} );
		} );

		$cancelBtn.on( 'click', function () {
			$modal.hide();
			currentType = null;
		} );
	}

	function initRevertButtons() {
		$( '.wcsd-revert-button' ).on( 'click', function () {
			var $btn     = $( this );
			var batchId  = $btn.data( 'batch-id' );

			if ( ! window.confirm( WCSD.i18n.confirmRevert ) ) {
				return;
			}

			$btn.prop( 'disabled', true ).text( WCSD.i18n.reverting );

			$.post( WCSD.ajaxUrl, {
				action: 'wcsd_fix_revert',
				nonce: WCSD.nonce,
				batch_id: batchId
			} )
				.done( function ( response ) {
					if ( response.success ) {
						window.location.reload();
					} else {
						alert( ( response.data && response.data.message ) || WCSD.i18n.error );
						$btn.prop( 'disabled', false );
					}
				} )
				.fail( function () {
					alert( WCSD.i18n.error );
					$btn.prop( 'disabled', false );
				} );
		} );
	}

	/**
	 * Draws the Store Health trend line on <canvas id="wcsd-history-chart">.
	 * No charting library — just a small hand-rolled line chart, since this
	 * is the only chart the plugin needs and it keeps the plugin dependency-free.
	 */
	function initHistoryChart() {
		var canvas = document.getElementById( 'wcsd-history-chart' );
		if ( ! canvas || ! WCSD.history || WCSD.history.length < 2 ) {
			return;
		}

		var ctx    = canvas.getContext( '2d' );
		var data   = WCSD.history;
		var dpr    = window.devicePixelRatio || 1;

		// Match the canvas's backing resolution to its displayed CSS size so
		// lines stay crisp on high-DPI screens instead of blurring.
		var cssWidth  = canvas.clientWidth || canvas.parentElement.clientWidth || 600;
		var cssHeight = canvas.height || 220;
		canvas.width  = cssWidth * dpr;
		canvas.height = cssHeight * dpr;
		ctx.scale( dpr, dpr );

		var padding = { top: 16, right: 16, bottom: 28, left: 34 };
		var plotW   = cssWidth - padding.left - padding.right;
		var plotH   = cssHeight - padding.top - padding.bottom;

		var maxScore = 100;
		var minScore = 0;

		function xFor( i ) {
			return padding.left + ( i / ( data.length - 1 ) ) * plotW;
		}
		function yFor( score ) {
			return padding.top + ( 1 - ( score - minScore ) / ( maxScore - minScore ) ) * plotH;
		}

		ctx.clearRect( 0, 0, cssWidth, cssHeight );

		// Horizontal gridlines at 0/25/50/75/100 with score labels.
		ctx.strokeStyle = '#f0f0f1';
		ctx.fillStyle   = '#646970';
		ctx.font        = '11px -apple-system, sans-serif';
		ctx.lineWidth   = 1;

		[ 0, 25, 50, 75, 100 ].forEach( function ( tick ) {
			var y = yFor( tick );
			ctx.beginPath();
			ctx.moveTo( padding.left, y );
			ctx.lineTo( cssWidth - padding.right, y );
			ctx.stroke();
			ctx.fillText( String( tick ), 4, y + 3 );
		} );

		// X-axis date labels — thin out if there are many points so they don't overlap.
		var labelEvery = Math.ceil( data.length / 6 );
		ctx.textAlign = 'center';
		data.forEach( function ( point, i ) {
			if ( i % labelEvery !== 0 && i !== data.length - 1 ) {
				return;
			}
			ctx.fillText( point.label, xFor( i ), cssHeight - 8 );
		} );
		ctx.textAlign = 'left';

		// The score line itself.
		ctx.strokeStyle = '#2271b1';
		ctx.lineWidth   = 2;
		ctx.beginPath();
		data.forEach( function ( point, i ) {
			var x = xFor( i );
			var y = yFor( point.score );
			if ( i === 0 ) {
				ctx.moveTo( x, y );
			} else {
				ctx.lineTo( x, y );
			}
		} );
		ctx.stroke();

		// A dot on each data point, with the final point emphasized.
		data.forEach( function ( point, i ) {
			var x = xFor( i );
			var y = yFor( point.score );
			var isLast = i === data.length - 1;
			ctx.beginPath();
			ctx.arc( x, y, isLast ? 4 : 3, 0, Math.PI * 2 );
			ctx.fillStyle = isLast ? '#00a32a' : '#2271b1';
			ctx.fill();
		} );
	}

	function escapeHtml( str ) {
		return $( '<div>' ).text( str == null ? '' : str ).html();
	}
} )( jQuery );
