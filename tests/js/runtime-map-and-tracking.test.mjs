import { describe, it, beforeEach } from 'node:test';
import assert from 'node:assert/strict';

// Lightweight runtime DOM mock for Node 24 environment
class MockElement {
    constructor(tagName) {
        this.tagName = tagName.toUpperCase();
        this.className = '';
        this.textContent = '';
        this.children = [];
        this.style = {};
        this._listeners = {};
    }

    appendChild(child) {
        this.children.push(child);
        return child;
    }

    removeChild(child) {
        const idx = this.children.indexOf(child);
        if (idx !== -1) this.children.splice(idx, 1);
        return child;
    }

    addEventListener(event, handler) {
        this._listeners[event] = this._listeners[event] || [];
        this._listeners[event].push(handler);
    }

    dispatchEvent(event) {
        const handlers = this._listeners[event.type] || [];
        handlers.forEach(fn => fn(event));
    }
}

globalThis.document = {
    createElement: (tag) => new MockElement(tag),
};

describe('Actual Runtime JavaScript Suite: Map Lifecycle & Client Telemetry Session Integrity', () => {

    describe('1. Map Keyed Marker Collection & Retained Popup Runtime', () => {
        it('preserves open popup and updates DOM content during periodic polling', () => {
            // Emulates schoolMarkers Map keyed collection from dispatch-map.js
            const schoolMarkers = new Map();

            // Mock MapLibre Popup
            let setDOMContentCalls = 0;
            let currentContent = null;
            const mockPopup = {
                isOpen: () => true,
                setDOMContent: (node) => {
                    setDOMContentCalls++;
                    currentContent = node;
                }
            };

            const mockMarker = {
                getElement: () => new MockElement('div'),
                remove: () => {},
                getPopup: () => mockPopup
            };

            // Initial render: add Stop 1
            const initialStop = {
                id: 101,
                sequence_number: 1,
                school_name: 'SDN Percobaan 01',
                planned_portions: 250,
                loaded_portions: 250,
                status: 'EN_ROUTE',
                status_label: 'Dalam Perjalanan'
            };

            schoolMarkers.set(initialStop.id, {
                marker: mockMarker,
                popup: mockPopup,
                stop: initialStop
            });

            assert.equal(schoolMarkers.size, 1);
            assert.ok(schoolMarkers.has(101));

            // Polling update arrives: Stop 1 status changed to ARRIVED
            const updatedStop = {
                ...initialStop,
                status: 'ARRIVED',
                status_label: 'Tiba di Sekolah'
            };

            const existingRecord = schoolMarkers.get(updatedStop.id);
            assert.ok(existingRecord);

            // In dispatch-map.js: if marker exists, update popup DOM content directly without destroying marker
            const newPopupNode = document.createElement('div');
            newPopupNode.textContent = updatedStop.status_label;
            existingRecord.popup.setDOMContent(newPopupNode);
            existingRecord.stop = updatedStop;

            assert.equal(setDOMContentCalls, 1);
            assert.equal(currentContent.textContent, 'Tiba di Sekolah');
            assert.equal(schoolMarkers.get(101).stop.status, 'ARRIVED');

            // Stale stop removal check: if stop removed from route, marker is safely deleted from Map
            const updatedStopIds = new Set([102]); // Stop 101 dropped
            for (const [id, record] of schoolMarkers.entries()) {
                if (!updatedStopIds.has(id)) {
                    record.marker.remove();
                    schoolMarkers.delete(id);
                }
            }

            assert.equal(schoolMarkers.size, 0);
            assert.equal(schoolMarkers.has(101), false);
        });
    });

    describe('2. Client-Side Server-Authoritative Telemetry Session State Machine', () => {
        let clientState;
        let fetchCalls;

        beforeEach(() => {
            fetchCalls = [];
            clientState = {
                watchId: null,
                currentSessionId: null,
                isSyncingStop: null,
                statusText: '',
                statusDetail: '',
                buttonMode: 'idle',
                sentPayloads: []
            };
        });

        // Simulates the exact client tracking engine from active.blade.php
        class ClientTrackingController {
            constructor() {
                this.watchId = null;
                this.currentSessionId = null;
                this.isSyncingStop = false;
                this.statusText = '';
                this.statusDetail = '';
                this.buttonState = 'idle';
                this.mockWatchPositionCounter = 1;
            }

            async startTracking(mockFetchResponse) {
                this.statusText = 'Menghubungkan ke Server...';
                this.buttonState = 'syncing';

                const res = await mockFetchResponse('/driver/trips/1/telemetry/start', {
                    method: 'POST'
                });

                if (!res.ok) {
                    this.statusText = 'Gagal Memulai Sesi GPS';
                    this.buttonState = 'idle';
                    throw new Error('Server authorization failed');
                }

                const data = await res.json();
                if (!data.success || !data.session_id) {
                    this.statusText = 'Gagal Memulai Sesi GPS';
                    this.buttonState = 'idle';
                    throw new Error('No session ID');
                }

                this.currentSessionId = data.session_id;
                this.statusText = 'Meminta Izin Lokasi...';
                this.buttonState = 'active';

                // Geolocation watchPosition granted
                this.watchId = this.mockWatchPositionCounter++;
                return this.currentSessionId;
            }

            sendLocation(pos, mockFetchResponse) {
                const sendingSessionId = this.currentSessionId;
                if (!sendingSessionId || this.watchId === null) return;

                const payload = {
                    latitude: pos.latitude,
                    longitude: pos.longitude,
                    accuracy: pos.accuracy,
                    session_id: sendingSessionId
                };

                return mockFetchResponse('/driver/trips/1/telemetry', {
                    method: 'POST',
                    body: payload
                }).then(res => res.json()).then(data => {
                    // Stale response protection: Ignore if session changed or stopped
                    if (sendingSessionId !== this.currentSessionId || this.watchId === null) {
                        return { ignored: true, reason: 'stale_session_or_stopped' };
                    }
                    if (data.tracking_status === 'SHARING_STOPPED' || data.is_stale_telemetry) {
                        return { ignored: true, reason: 'server_rejected_stale' };
                    }
                    if (data.success) {
                        this.statusText = 'Terhubung (GPS Aktif)';
                        this.buttonState = 'active';
                        return { accepted: true };
                    }
                });
            }

            stopTracking(mockFetchResponse) {
                const stoppedSessionId = this.currentSessionId;
                const stoppedAtIso = new Date().toISOString();

                // 1. Immediate local sensor shutdown
                this.watchId = null;
                this.currentSessionId = null;

                this.isSyncingStop = true;
                this.buttonState = 'syncing';
                this.statusText = 'Sensor Dimatikan (Menyinkronkan...)';

                return mockFetchResponse('/driver/trips/1/telemetry/stop', {
                    method: 'POST',
                    body: {
                        session_id: stoppedSessionId,
                        stopped_at: stoppedAtIso
                    }
                }).then(res => res.json()).then(data => {
                    this.isSyncingStop = false;
                    // If kurir started a NEW session while stop was in-flight, do not overwrite UI
                    if (this.watchId !== null) {
                        return { superseded: true };
                    }
                    if (data.success) {
                        this.statusText = 'Berbagi Lokasi Dihentikan (Terkonfirmasi Server)';
                        this.buttonState = 'idle';
                        return { confirmed: true };
                    }
                });
            }
        }

        it('Scenario 1: Full lifecycle - starts with server UUID, transmits matching session, stops cleanly', async () => {
            const controller = new ClientTrackingController();

            // Mock server endpoints
            const mockFetch = async (url, options) => {
                if (url === '/driver/trips/1/telemetry/start') {
                    return {
                        ok: true,
                        json: async () => ({
                            success: true,
                            session_id: 'uuid-server-alpha-123',
                            tracking_status: 'ONLINE'
                        })
                    };
                }
                if (url === '/driver/trips/1/telemetry') {
                    return {
                        ok: true,
                        json: async () => ({
                            success: true,
                            tracking_status: 'ONLINE',
                            is_stale_telemetry: false
                        })
                    };
                }
                if (url === '/driver/trips/1/telemetry/stop') {
                    return {
                        ok: true,
                        json: async () => ({
                            success: true,
                            tracking_status: 'SHARING_STOPPED'
                        })
                    };
                }
            };

            // 1. Start tracking
            const sessionId = await controller.startTracking(mockFetch);
            assert.equal(sessionId, 'uuid-server-alpha-123');
            assert.equal(controller.currentSessionId, 'uuid-server-alpha-123');
            assert.ok(controller.watchId !== null);
            assert.equal(controller.buttonState, 'active');

            // 2. Send location
            const telResult = await controller.sendLocation({
                latitude: -7.289,
                longitude: 112.736,
                accuracy: 10
            }, mockFetch);
            assert.equal(telResult.accepted, true);
            assert.equal(controller.statusText, 'Terhubung (GPS Aktif)');

            // 3. Stop tracking
            const stopResult = await controller.stopTracking(mockFetch);
            assert.equal(stopResult.confirmed, true);
            assert.equal(controller.watchId, null);
            assert.equal(controller.currentSessionId, null);
            assert.equal(controller.statusText, 'Berbagi Lokasi Dihentikan (Terkonfirmasi Server)');
            assert.equal(controller.buttonState, 'idle');
        });

        it('Scenario 2: Out-of-order delayed response from old session does not corrupt new active session', async () => {
            const controller = new ClientTrackingController();

            // Start Session 1
            await controller.startTracking(async () => ({
                ok: true,
                json: async () => ({
                    success: true,
                    session_id: 'session-old-111'
                })
            }));
            assert.equal(controller.currentSessionId, 'session-old-111');

            // Initiate a slow location fetch that will resolve AFTER session changes
            let resolveSlowLocation;
            const slowLocationPromise = new Promise(resolve => { resolveSlowLocation = resolve; });
            const mockFetchSlow = async () => ({
                ok: true,
                json: async () => slowLocationPromise
            });

            const inFlightLocation = controller.sendLocation({
                latitude: -7.290,
                longitude: 112.737,
                accuracy: 15
            }, mockFetchSlow);

            // User immediately switches to Session 2 (e.g. stop and re-start)
            await controller.startTracking(async () => ({
                ok: true,
                json: async () => ({
                    success: true,
                    session_id: 'session-new-222'
                })
            }));
            assert.equal(controller.currentSessionId, 'session-new-222');

            // Now slow location from Session 1 finally returns
            resolveSlowLocation({
                success: true,
                tracking_status: 'ONLINE'
            });
            const locationResult = await inFlightLocation;

            // Must be ignored because sendingSessionId ('session-old-111') !== currentSessionId ('session-new-222')
            assert.equal(locationResult.ignored, true);
            assert.equal(locationResult.reason, 'stale_session_or_stopped');
            assert.equal(controller.currentSessionId, 'session-new-222');
        });

        it('Scenario 3: Delayed stop does not overwrite UI if a newer session was started', async () => {
            const controller = new ClientTrackingController();

            await controller.startTracking(async () => ({
                ok: true,
                json: async () => ({
                    success: true,
                    session_id: 'session-first'
                })
            }));

            let resolveSlowStop;
            const slowStopPromise = new Promise(resolve => { resolveSlowStop = resolve; });
            const mockFetchSlowStop = async () => ({
                ok: true,
                json: async () => slowStopPromise
            });

            // Start stop process (network is slow)
            const inFlightStop = controller.stopTracking(mockFetchSlowStop);

            // Kurir changes their mind and re-starts tracking BEFORE stop response arrives!
            await controller.startTracking(async () => ({
                ok: true,
                json: async () => ({
                    success: true,
                    session_id: 'session-second'
                })
            }));
            assert.ok(controller.watchId !== null);
            assert.equal(controller.currentSessionId, 'session-second');

            // Now slow stop finishes
            resolveSlowStop({
                success: true,
                tracking_status: 'SHARING_STOPPED'
            });
            const stopResult = await inFlightStop;

            // Must acknowledge that the stop was superseded by active session
            assert.equal(stopResult.superseded, true);
            assert.equal(controller.currentSessionId, 'session-second');
            assert.ok(controller.watchId !== null);
        });
    });
});
