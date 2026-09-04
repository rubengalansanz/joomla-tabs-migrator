/**
 * Joomla Tabs Migrator — interactividad de pestañas y sliders.
 *
 * - Tabs: clic + teclado (patrón WAI-APG tabs con activación
 *   automática): flechas ←/→/↑/↓, Home, End. Roving tabindex.
 * - Sliders: alternado de cada panel (los <button> ya responden a Enter/Espacio).
 * - Deep-link: al activar una pestaña o abrir un slider se publica el id del
 *   panel en location.hash (replaceState, sin salto); al cargar, si el hash
 *   coincide con un panel o su data-alias, se activa/abre.
 * - Seguro ante re-ejecución y contenido AJAX: window.jtmInit(root).
 */
( function () {
    'use strict';

    /**
     * Activa la pestaña dada dentro de su contenedor.
     *
     * @param {Element} container .jtm-tabs
     * @param {Element} link .jtm-tab-link a activar
     * @param {Object} [opts] { focus: mover foco al tab, updateHash: publicar hash }
     */
    function activateTab( container, link, opts ) {
        if ( ! container || ! link ) {
            return;
        }

        var options = opts || {};
        var panelId = link.getAttribute( 'aria-controls' );
        var panel   = panelId ? document.getElementById( panelId ) : null;

        if ( ! panel ) {
            return;
        }

        container.querySelectorAll( '.jtm-tab-item' ).forEach( function ( el ) {
            el.classList.remove( 'jtm-active' );
        } );

        container.querySelectorAll( '.jtm-tab-link' ).forEach( function ( el ) {
            el.setAttribute( 'aria-selected', 'false' );
            el.setAttribute( 'tabindex', '-1' );
        } );

        container.querySelectorAll( '.jtm-tab-panel' ).forEach( function ( el ) {
            el.classList.remove( 'jtm-active' );
            el.hidden = true;
        } );

        var item = link.closest( '.jtm-tab-item' );
        if ( item ) {
            item.classList.add( 'jtm-active' );
        }
        link.setAttribute( 'aria-selected', 'true' );
        link.setAttribute( 'tabindex', '0' );
        panel.classList.add( 'jtm-active' );
        panel.hidden = false;

        if ( options.focus && link.focus ) {
            link.focus();
        }

        if ( options.updateHash !== false ) {
            publishHash( panel.id || link.getAttribute( 'data-alias' ) );
        }
    }

    function initTabs( container ) {
        if ( ! container || container.dataset.jtmInit ) {
            return;
        }
        container.dataset.jtmInit = '1';

        var links = Array.prototype.slice.call( container.querySelectorAll( '.jtm-tab-link' ) );

        // Roving tabindex inicial: solo el tab activo es tabulable.
        var anyActive = false;
        links.forEach( function ( link ) {
            var selected = link.getAttribute( 'aria-selected' ) === 'true';
            if ( selected ) {
                anyActive = true;
            }
            link.setAttribute( 'tabindex', selected ? '0' : '-1' );
        } );
        if ( ! anyActive && links.length ) {
            links[ 0 ].setAttribute( 'tabindex', '0' );
        }

        container.addEventListener( 'click', function ( e ) {
            var link = e.target.closest( '.jtm-tab-link' );
            if ( ! link || ! container.contains( link ) ) {
                return;
            }
            activateTab( container, link, { focus: false, updateHash: true } );
        } );

        // Teclado estilo WAI-APG con activación automática.
        container.addEventListener( 'keydown', function ( e ) {
            var link = e.target.closest( '.jtm-tab-link' );
            if ( ! link || ! container.contains( link ) ) {
                return;
            }

            var key = e.key;
            var current = links.indexOf( link );
            var next = -1;

            if ( key === 'ArrowRight' || key === 'ArrowDown' ) {
                next = ( current + 1 ) % links.length;
            } else if ( key === 'ArrowLeft' || key === 'ArrowUp' ) {
                next = ( current - 1 + links.length ) % links.length;
            } else if ( key === 'Home' ) {
                next = 0;
            } else if ( key === 'End' ) {
                next = links.length - 1;
            } else {
                return;
            }

            e.preventDefault();
            if ( links[ next ] ) {
                activateTab( container, links[ next ], { focus: true, updateHash: true } );
            }
        } );
    }

    /**
     * Abre o cierra un slider.
     *
     * @param {Element} toggle .jtm-slider-toggle
     * @param {boolean|null} force true = abrir, false = cerrar, null = alternar
     * @param {Object} [opts] { updateHash }
     */
    function setSlider( toggle, force, opts ) {
        if ( ! toggle ) {
            return;
        }

        var panelId = toggle.getAttribute( 'aria-controls' );
        var panel   = panelId ? document.getElementById( panelId ) : null;

        if ( ! panel ) {
            return;
        }

        var expanded = toggle.getAttribute( 'aria-expanded' ) === 'true';
        var open     = ( force === null || typeof force === 'undefined' ) ? ! expanded : !! force;

        toggle.setAttribute( 'aria-expanded', String( open ) );
        panel.hidden = ! open;

        var item = toggle.closest( '.jtm-slider-item' );
        if ( item ) {
            item.classList.toggle( 'jtm-active', open );
        }

        if ( open && ( ! opts || opts.updateHash !== false ) ) {
            publishHash( panel.id || toggle.getAttribute( 'data-alias' ) );
        }
    }

    function initSliders( container ) {
        if ( ! container || container.dataset.jtmInit ) {
            return;
        }
        container.dataset.jtmInit = '1';

        container.addEventListener( 'click', function ( e ) {
            var toggle = e.target.closest( '.jtm-slider-toggle' );
            if ( ! toggle || ! container.contains( toggle ) ) {
                return;
            }
            setSlider( toggle, null, { updateHash: true } );
        } );
    }

    /**
     * Publica un hash sin provocar salto ni entrada en el historial.
     */
    function publishHash( value ) {
        if ( ! value || ! window.history || ! window.history.replaceState ) {
            return;
        }
        try {
            window.history.replaceState( null, '', '#' + value );
        } catch ( err ) {
            // p. ej. file:// o URLs no aptas: se ignora sin romper la UI.
        }
    }

    function findByAlias( value ) {
        if ( ! value ) {
            return null;
        }
        // Sin CSS.escape (compatibilidad): comparación manual insensible a mayúsculas en data-alias.
        var all = document.querySelectorAll( '[data-alias]' );
        var lower = value.toLowerCase();
        for ( var i = 0; i < all.length; i++ ) {
            if ( ( all[ i ].getAttribute( 'data-alias' ) || '' ).toLowerCase() === lower ) {
                return all[ i ];
            }
        }
        return null;
    }

    /**
     * Activa/abre el elemento apuntado por location.hash (id de panel o data-alias).
     */
    function openFromHash() {
        var raw = ( window.location && window.location.hash ) ? window.location.hash.replace( /^#/, '' ) : '';
        if ( ! raw ) {
            return;
        }

        var value;
        try {
            value = decodeURIComponent( raw );
        } catch ( err ) {
            value = raw;
        }
        if ( ! value ) {
            return;
        }

        var target = document.getElementById( value ) || findByAlias( value );
        if ( ! target ) {
            return;
        }

        // El hash puede apuntar al botón o al panel.
        var tabsContainer = target.closest ? target.closest( '.jtm-tabs' ) : null;
        if ( tabsContainer ) {
            var link = target.classList && target.classList.contains( 'jtm-tab-link' )
                ? target
                : tabsContainer.querySelector( '.jtm-tab-link[aria-controls="' + ( target.id || '' ) + '"]' );
            if ( ! link && target.classList && target.classList.contains( 'jtm-tab-panel' ) ) {
                link = tabsContainer.querySelector( '[aria-controls="' + target.id + '"]' );
            }
            if ( link ) {
                activateTab( tabsContainer, link, { focus: false, updateHash: false } );
                return;
            }
        }

        var slidersContainer = target.closest ? target.closest( '.jtm-sliders' ) : null;
        if ( slidersContainer ) {
            var toggle = target.classList && target.classList.contains( 'jtm-slider-toggle' )
                ? target
                : slidersContainer.querySelector( '.jtm-slider-toggle[aria-controls="' + ( target.id || '' ) + '"]' );
            if ( ! toggle && target.classList && target.classList.contains( 'jtm-slider-panel' ) ) {
                toggle = slidersContainer.querySelector( '[aria-controls="' + target.id + '"]' );
            }
            if ( toggle ) {
                setSlider( toggle, true, { updateHash: false } );
            }
        }
    }

    function init( root ) {
        var scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll( '.jtm-tabs' ).forEach( initTabs );
        scope.querySelectorAll( '.jtm-sliders' ).forEach( initSliders );
    }

    // API pública para contenido cargado por AJAX o re-renderizado.
    window.jtmInit = function ( root ) {
        init( root );
        openFromHash();
    };

    document.addEventListener( 'DOMContentLoaded', function () {
        init( document );
        openFromHash();
    } );
} )();
