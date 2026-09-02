document.addEventListener( 'DOMContentLoaded', function () {
    document.querySelectorAll( '.jtm-tabs' ).forEach( initTabs );
    document.querySelectorAll( '.jtm-sliders' ).forEach( initSliders );
} );

function initTabs( container ) {
    container.addEventListener( 'click', function ( e ) {
        const link = e.target.closest( '.jtm-tab-link' );
        if ( ! link ) return;

        const panelId = link.getAttribute( 'aria-controls' );
        const panel   = document.getElementById( panelId );

        container.querySelectorAll( '.jtm-tab-item' ).forEach( function ( el ) {
            el.classList.remove( 'jtm-active' );
        } );
        container.querySelectorAll( '.jtm-tab-link' ).forEach( function ( el ) {
            el.setAttribute( 'aria-selected', 'false' );
        } );
        container.querySelectorAll( '.jtm-tab-panel' ).forEach( function ( el ) {
            el.classList.remove( 'jtm-active' );
            el.hidden = true;
        } );

        link.closest( '.jtm-tab-item' ).classList.add( 'jtm-active' );
        link.setAttribute( 'aria-selected', 'true' );
        panel.classList.add( 'jtm-active' );
        panel.hidden = false;
    } );
}

function initSliders( container ) {
    container.addEventListener( 'click', function ( e ) {
        const toggle = e.target.closest( '.jtm-slider-toggle' );
        if ( ! toggle ) return;

        const panelId  = toggle.getAttribute( 'aria-controls' );
        const panel    = document.getElementById( panelId );
        const expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';

        toggle.setAttribute( 'aria-expanded', String( ! expanded ) );
        panel.hidden = expanded;
        toggle.closest( '.jtm-slider-item' ).classList.toggle( 'jtm-active', ! expanded );
    } );
}