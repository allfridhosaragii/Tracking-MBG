import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '../..');

describe('Dispatch Map & Telemetry JavaScript Regression Suite (PBI-09)', () => {
    const dispatchMapPath = path.join(projectRoot, 'resources/js/dispatch-map.js');
    const dispatchMapContent = fs.readFileSync(dispatchMapPath, 'utf8');

    it('Priority 1: MapLibre Map is aliased as MapLibreMap to prevent constructor collision', () => {
        // Assert proper aliasing
        assert.match(
            dispatchMapContent,
            /import\s*\{\s*Map\s+as\s+MapLibreMap/m,
            'Map from maplibre-gl must be aliased as MapLibreMap'
        );

        // Assert map instantiation uses MapLibreMap
        assert.match(
            dispatchMapContent,
            /map\s*=\s*new\s+MapLibreMap\s*\(/m,
            'Geographic map must be instantiated with new MapLibreMap(...)'
        );

        // Assert window.maplibregl exports Map: MapLibreMap for backwards compatibility
        assert.match(
            dispatchMapContent,
            /window\.maplibregl\s*=\s*\{\s*Map:\s*MapLibreMap/m,
            'window.maplibregl must map Map property to MapLibreMap'
        );
    });

    it('Priority 1: Native JavaScript Map is used for keyed marker collection without shadowing', () => {
        // Assert schoolMarkers uses native Map
        assert.match(
            dispatchMapContent,
            /const\s+schoolMarkers\s*=\s*new\s+Map\(\)/m,
            'schoolMarkers must be initialized with native new Map()'
        );

        // Verify that native Map is not shadowed in the file
        const shadowedMapDeclarations = dispatchMapContent.match(/const\s+Map\s*=|let\s+Map\s*=|var\s+Map\s*=|function\s+Map\s*\(/g);
        assert.equal(
            shadowedMapDeclarations,
            null,
            'There must be no shadowing of standard JavaScript Map constructor'
        );

        // Verify standard ES6 Map methods are used on schoolMarkers
        assert.match(dispatchMapContent, /schoolMarkers\.get\(/);
        assert.match(dispatchMapContent, /schoolMarkers\.set\(/);
        assert.match(dispatchMapContent, /schoolMarkers\.delete\(/);
    });

    it('Priority 4: Preserves open popups and avoids marker destruction during polling updates', () => {
        // Ensure school markers update popup DOM content instead of destroying markers
        assert.match(
            dispatchMapContent,
            /markerRecord\.popup\.setDOMContent\(/,
            'Polling updates must call setDOMContent on existing popup to retain open state'
        );

        // Ensure fitBounds is only called on initial render to prevent camera reset
        assert.match(
            dispatchMapContent,
            /if\s*\(\s*hasPoints\s*&&\s*!bounds\.isEmpty\(\)\s*&&\s*isInitialRender\s*\)/,
            'fitBounds must only execute on initial render to preserve user pan/zoom'
        );

        // Ensure requestAnimationFrame visual interpolation handles prefers-reduced-motion
        assert.match(
            dispatchMapContent,
            /prefers-reduced-motion:\s*reduce/,
            'RAF interpolation must honor prefers-reduced-motion'
        );
    });

    it('Priority 5: Public map refresh supports light polling and sanitization', () => {
        const publicViewPath = path.join(projectRoot, 'resources/views/public/delivery-map.blade.php');
        const publicViewContent = fs.readFileSync(publicViewPath, 'utf8');

        // Check pollInterval configured
        assert.match(
            publicViewContent,
            /pollInterval:\s*15000/,
            'Public map must configure 15s pollInterval'
        );

        // Check onTelemetryUpdate handler updates real last_updated event
        assert.match(
            publicViewContent,
            /onTelemetryUpdate:\s*function\s*\(\s*data\s*\)/,
            'Public map must have onTelemetryUpdate callback'
        );

        assert.match(
            publicViewContent,
            /data\.last_updated/,
            'Public map updates must read genuine last_updated timestamp from server'
        );

        // Ensure dispatch-map pauses polling when tab is hidden
        assert.match(
            dispatchMapContent,
            /if\s*\(\s*!dataUrl\s*\|\|\s*document\.hidden\s*\)\s*return;/,
            'fetchAndRender must halt when document.hidden is true'
        );
    });

    it('Priority 2 & 3: Driver active view distinguishes sensor stop, server confirmation, and handles race condition', () => {
        const activeViewPath = path.join(projectRoot, 'resources/views/driver/trips/active.blade.php');
        const activeViewContent = fs.readFileSync(activeViewPath, 'utf8');

        // Immediate sensor clearWatch for privacy
        assert.match(
            activeViewContent,
            /navigator\.geolocation\.clearWatch\(watchId\)/,
            'clearWatch must be invoked immediately upon stop for privacy'
        );

        // Server-authoritative session UUID assignment
        assert.match(
            activeViewContent,
            /currentSessionId\s*=\s*data\.session_id/,
            'Server-authoritative session UUID must be assigned on start'
        );

        // Check HTTP response ok and Content-Type
        assert.match(
            activeViewContent,
            /res\.headers\.get\('content-type'\)/,
            'Stop request must verify response content-type'
        );

        // Status reconciliation
        assert.match(
            activeViewContent,
            /reconcileServerStopStatus/,
            'Status reconciliation function must be present for network drop handling'
        );

        // Stale response rejection
        assert.match(
            activeViewContent,
            /sendingSessionId\s*!==\s*currentSessionId\s*\|\|\s*watchId\s*===\s*null/,
            'In-flight telemetry responses from stopped or old sessions must be rejected'
        );
    });
});
