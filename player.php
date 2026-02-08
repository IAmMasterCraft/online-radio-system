// ── Request Modal Logic ─────────────────────
    async function openRequestModal() {
        document.getElementById('requestModal').classList.add('visible');
        await loadRequestMedia();
    }

    function closeRequestModal() {
        document.getElementById('requestModal').classList.remove('visible');
        document.getElementById('requestMedia').value = '';
        document.getElementById('requestName').value = '';
        document.getElementById('requestMessage').value = '';
    }

    async function loadRequestMedia() {
        const select = document.getElementById('requestMedia');
        select.innerHTML = '<option value="">Loading songs...</option>';
        try {
            const resp = await fetch(BASE + '/api/media.php?active=1&is_loop=0');
            const data = await resp.json();
            if (data.length) {
                select.innerHTML = '<option value="">— Select a song —</option>' + 
                    data.map(m => `<option value="${m.id}">${esc(m.title)} - ${esc(m.artist)}</option>`).join('');
            } else {
                select.innerHTML = '<option value="">No songs available</option>';
            }
        } catch (err) {
            console.error('Failed to load media for requests:', err);
            select.innerHTML = '<option value="">Error loading songs</option>';
        }
    }

    async function submitRequest() {
        const mediaId = document.getElementById('requestMedia').value;
        const requesterName = document.getElementById('requestName').value;
        const message = document.getElementById('requestMessage').value;

        if (!mediaId) {
            alert('Please select a song to request.');
            return;
        }

        try {
            const resp = await fetch(BASE + '/api/requests.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ media_id: mediaId, requester_name: requesterName, message: message })
            });
            const result = await resp.json();

            if (result.success) {
                alert('Your song request has been submitted!');
                closeRequestModal();
            } else {
                alert('Failed to submit request: ' + (result.error || 'Unknown error'));
            }
        } catch (err) {
            console.error('Request submission error:', err);
            alert('An error occurred while submitting your request.');
        }
    }