/**
 * MBG-Track Swipe-To-Depart Interactive Slider Component
 * Prevents accidental tap-to-dispatch while driving or loading.
 * Features touch & pointer dragging, snap-back animation,
 * atomic backend authorization check, double-submit protection, and a11y support.
 */
export function initSwipeToDepart(sliderId, options = {}) {
    const sliderContainer = document.getElementById(sliderId);
    if (!sliderContainer) return null;

    const thumb = sliderContainer.querySelector('.swipe-thumb');
    const fill = sliderContainer.querySelector('.swipe-fill');
    const label = sliderContainer.querySelector('.swipe-label');
    const actionUrl = sliderContainer.dataset.actionUrl;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!thumb || !fill || !actionUrl) return null;

    let isDragging = false;
    let startX = 0;
    let currentX = 0;
    let maxDistance = 0;
    let isLocked = false;

    function updateMaxDistance() {
        maxDistance = sliderContainer.clientWidth - thumb.clientWidth - 8; // 4px padding each side
    }

    updateMaxDistance();
    window.addEventListener('resize', updateMaxDistance);

    function setPosition(x) {
        currentX = Math.max(0, Math.min(x, maxDistance));
        thumb.style.transform = `translateX(${currentX}px)`;
        fill.style.width = `${currentX + (thumb.clientWidth / 2)}px`;
    }

    function onPointerDown(e) {
        if (isLocked) return;
        isDragging = true;
        startX = (e.touches ? e.touches[0].clientX : e.clientX) - currentX;
        thumb.style.transition = 'none';
        fill.style.transition = 'none';
        document.body.style.userSelect = 'none';
    }

    function onPointerMove(e) {
        if (!isDragging || isLocked) return;
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const newX = clientX - startX;
        setPosition(newX);

        // Feedback when nearing threshold
        if (currentX / maxDistance > 0.8) {
            thumb.classList.add('ring-4', 'ring-emerald-400');
        } else {
            thumb.classList.remove('ring-4', 'ring-emerald-400');
        }
    }

    function onPointerUp() {
        if (!isDragging || isLocked) return;
        isDragging = false;
        document.body.style.userSelect = '';
        thumb.classList.remove('ring-4', 'ring-emerald-400');

        const thresholdRatio = 0.82; // 82% threshold
        if (currentX >= maxDistance * thresholdRatio) {
            // Trigger activation
            triggerDeparture();
        } else {
            // Snap back
            snapBack();
        }
    }

    function snapBack() {
        thumb.style.transition = 'transform 0.3s cubic-bezier(0.25, 1, 0.5, 1)';
        fill.style.transition = 'width 0.3s cubic-bezier(0.25, 1, 0.5, 1)';
        setPosition(0);
    }

    function triggerDeparture() {
        isLocked = true;
        setPosition(maxDistance);
        thumb.style.transition = 'transform 0.2s ease';
        fill.style.transition = 'width 0.2s ease';

        if (label) {
            label.innerText = 'Mengonfirmasi Keberangkatan...';
            label.classList.add('animate-pulse');
        }

        fetch(actionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ swiped: true }),
        })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));

                if (response.ok && data.success) {
                    if (label) {
                        label.innerText = 'Perjalanan Resmi Dimulai!';
                        label.classList.remove('animate-pulse');
                    }
                    sliderContainer.classList.add('bg-emerald-600', 'text-white');
                    thumb.classList.add('bg-white', 'text-emerald-700');

                    setTimeout(() => {
                        window.location.reload();
                    }, 600);
                } else {
                    const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Gagal memulai perjalanan. Pastikan seluruh gerbang verifikasi telah dipenuhi.');
                    alert(errorMsg);
                    isLocked = false;
                    if (label) {
                        label.innerText = 'Geser untuk mulai perjalanan';
                        label.classList.remove('animate-pulse');
                    }
                    snapBack();
                }
            })
            .catch((err) => {
                console.error('Error starting trip:', err);
                alert('Terjadi kesalahan jaringan saat memulai perjalanan.');
                isLocked = false;
                if (label) {
                    label.innerText = 'Geser untuk mulai perjalanan';
                    label.classList.remove('animate-pulse');
                }
                snapBack();
            });
    }

    // Touch events
    thumb.addEventListener('touchstart', onPointerDown, { passive: true });
    window.addEventListener('touchmove', onPointerMove, { passive: true });
    window.addEventListener('touchend', onPointerUp);

    // Mouse events
    thumb.addEventListener('mousedown', onPointerDown);
    window.addEventListener('mousemove', onPointerMove);
    window.addEventListener('mouseup', onPointerUp);

    // Accessible keyboard alternative
    const accessibleBtn = sliderContainer.querySelector('.swipe-accessible-btn');
    if (accessibleBtn) {
        accessibleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('Konfirmasi: Anda yakin ingin memulai perjalanan pengantaran menuju sekolah sekarang?')) {
                triggerDeparture();
            }
        });
    }

    return {
        snapBack,
        unlock: () => { isLocked = false; snapBack(); },
    };
}

window.initSwipeToDepart = initSwipeToDepart;
