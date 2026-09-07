(() => {
    'use strict';
    const sidebar = document.querySelector('#dashboard-sidebar');
    const toggle = document.querySelector('.mobile-sidebar-toggle');
    toggle?.addEventListener('click', () => {
        const open = sidebar.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', (event) => {
        const confirmButton = event.target.closest('[data-confirm]');
        if (confirmButton && !window.confirm(confirmButton.dataset.confirm)) event.preventDefault();
        if (window.innerWidth <= 820 && sidebar?.classList.contains('is-open') && !sidebar.contains(event.target) && !toggle?.contains(event.target)) {
            sidebar.classList.remove('is-open');
            toggle?.setAttribute('aria-expanded', 'false');
        }
    });
    document.querySelectorAll('[data-photo-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const preview = document.querySelector(`#${input.dataset.preview}`);
            const file = input.files?.[0];
            if (preview && file) preview.src = URL.createObjectURL(file);
        });
    });
    document.querySelectorAll('[data-auto-submit]').forEach((control) => {
        control.addEventListener('change', () => control.form?.requestSubmit());
    });

    const scheduleManager = document.querySelector('#schedule-manager');
    if (!scheduleManager) return;

    const calendar = document.querySelector('#schedule-calendar');
    const feedback = document.querySelector('#schedule-feedback');
    const barberSelect = document.querySelector('#schedule-barber');
    const endpoint = scheduleManager.dataset.endpoint;
    const scheduleDates = (scheduleManager.dataset.dates || '').split(',').filter(Boolean).slice(0, 3);
    const dayNames = ['Die', 'Hën', 'Mar', 'Mër', 'Enj', 'Pre', 'Sht'];
    const monthNames = ['Jan', 'Shk', 'Mar', 'Pri', 'Maj', 'Qer', 'Kor', 'Gus', 'Sht', 'Tet', 'Nën', 'Dhj'];

    const parseDate = (value) => new Date(`${value}T12:00:00`);

    function escapeText(value) {
        const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(value).replace(/[&<>"']/g, (character) => entities[character]);
    }

    function renderDay(snapshot) {
        const date = parseDate(snapshot.date);
        const blockDay = snapshot.blocked_count === 0;
        const slots = snapshot.slots.map((slot) => {
            const bookingLabel = slot.booking ? `${slot.booking.customer} · ${slot.booking.service}` : '';
            return `<button type="button" class="schedule-slot is-${slot.state}" data-schedule-slot data-date="${snapshot.date}" data-time="${slot.time}" data-state="${slot.state}" ${slot.booked ? 'disabled' : ''} title="${escapeText(bookingLabel || (slot.blocked ? 'Kliko për ta liruar' : 'Kliko për ta bllokuar'))}"><span>${slot.time}</span>${slot.booked ? `<small>${escapeText(slot.booking.customer)}</small>` : `<small>${slot.blocked ? 'Bllokuar' : 'E lirë'}</small>`}</button>`;
        }).join('');
        return `<article class="schedule-day"><header><div><strong>${dayNames[date.getDay()]}</strong><span>${date.getDate()} ${monthNames[date.getMonth()]}</span></div><button type="button" class="day-toggle ${blockDay ? '' : 'is-unblock'}" data-day-toggle data-date="${snapshot.date}" data-blocked="${blockDay ? '1' : '0'}">${blockDay ? 'Blloko ditën' : 'Liro ditën'}</button></header><div class="schedule-day-slots">${slots}</div></article>`;
    }

    async function loadSchedule() {
        if (!calendar || !barberSelect?.value || scheduleDates.length === 0) return;
        calendar.setAttribute('aria-busy', 'true');
        calendar.innerHTML = '<p class="schedule-loading">Po ngarkohet kalendari…</p>';
        feedback.textContent = '';
        try {
            const snapshots = await Promise.all(scheduleDates.map(async (date) => {
                const response = await fetch(`${endpoint}?barber_id=${encodeURIComponent(barberSelect.value)}&date=${date}`, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                const data = await response.json();
                if (!response.ok || !data.ok) throw new Error(data.message || 'Kalendari nuk u ngarkua.');
                if (data.csrf_token) scheduleManager.dataset.csrf = data.csrf_token;
                return data;
            }));
            calendar.innerHTML = snapshots.map(renderDay).join('');
        } catch (error) {
            calendar.innerHTML = `<p class="schedule-loading schedule-error">${escapeText(error.message)}</p>`;
        } finally {
            calendar.removeAttribute('aria-busy');
        }
    }

    async function updateSchedule(payload) {
        scheduleManager.classList.add('is-saving');
        feedback.textContent = 'Po ruhet ndryshimi…';
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify({ csrf_token: scheduleManager.dataset.csrf, barber_id: barberSelect.value, ...payload })
            });
            const data = await response.json();
            if (!response.ok || !data.ok) throw new Error(data.message || 'Ndryshimi nuk u ruajt.');
            if (data.csrf_token) scheduleManager.dataset.csrf = data.csrf_token;
            const savedMessage = data.message || 'Orari u përditësua.';
            await loadSchedule();
            feedback.classList.remove('is-error');
            feedback.textContent = savedMessage;
        } catch (error) {
            feedback.textContent = error.message;
            feedback.classList.add('is-error');
        } finally {
            scheduleManager.classList.remove('is-saving');
        }
    }

    calendar?.addEventListener('click', (event) => {
        const slot = event.target.closest('[data-schedule-slot]');
        const day = event.target.closest('[data-day-toggle]');
        feedback.classList.remove('is-error');
        if (slot && !slot.disabled) {
            updateSchedule({ action: 'toggle_slot', date: slot.dataset.date, time: slot.dataset.time, blocked: slot.dataset.state === 'blocked' ? 0 : 1 });
        } else if (day) {
            updateSchedule({ action: 'set_day', date: day.dataset.date, blocked: Number(day.dataset.blocked) });
        }
    });
    barberSelect?.addEventListener('change', loadSchedule);
    loadSchedule();
})();
