(() => {
    'use strict';

    const translations = {
        sq: {
            common: { skip: 'Kalo te përmbajtja', language: 'Zgjidh gjuhën', close: 'Mbyll' },
            nav: { menu: 'Hap menunë', aria: 'Navigimi kryesor', services: 'Shërbimet', barbers: 'Berberët', my_booking: 'Rezervimi im', book: 'Rezervo tani' },
            hero: {
                eyebrow: 'GentlemanBarber',
                title: 'Stil i pastër. Rezervim i thjeshtë.',
                lead: 'Zgjidh berberin, shërbimin dhe orën. Ne kujdesemi për pjesën tjetër.',
                primary: 'Rezervo termin', secondary: 'Shiko shërbimet', availability_short: 'Termine të lira',
                availability: 'Për 3 ditët e ardhshme', image_alt: 'Ambient modern i GentlemanBarber'
            },
            benefit: {
                fast: { title: 'Rezervo shpejt', text: 'Tre zgjedhje dhe termini është dërguar.' },
                pro: { title: 'Zgjidh berberin', text: 'Rezervo direkt te profesionisti që preferon.' },
                wait: { title: 'Eja në orar', text: 'Ora e rezervuar ruhet vetëm për ty.' }
            },
            services: { kicker: 'Çmime të qarta', title: 'Shërbimet dhe çmimet', lead: 'Zgjidh shërbimin dhe shiko çmimin para rezervimit.' },
            duration: { minutes: 'min' },
            barbers: { kicker: 'Ekipi', title: 'Zgjidh berberin', lead: 'Shiko ekipin dhe rezervo me një klik.' },
            barber: { years: 'vite', book: 'Rezervo te', photo: 'Foto profili e' },
            lookup: { kicker: 'Ke rezervuar?', title: 'Kontrollo rezervimin', lead: 'Shkruaj numrin e telefonit për të parë statusin.', submit: 'Kontrollo statusin' },
            footer: { tagline: 'Stil i mirë. Pa pritje.', hours: 'Orari', days: 'Çdo ditë', contact: 'Kontakt' },
            booking: {
                eyebrow: 'Termini juaj', title: 'Rezervoni termin', with: 'Te', window: 'Terminet mund të rezervohen deri në 3 ditë përpara.',
                barber: 'Zgjidh berberin', barber_placeholder: '— Zgjidh —', choose_barber: 'Fillimisht zgjidh berberin',
                name: 'Emri dhe mbiemri', phone: 'Numri i telefonit', email: 'Email (opsional)', service: 'Zgjidh shërbimin',
                service_placeholder: '— Zgjidh —', date: 'Zgjidh datën', time: 'Zgjidh orën', note: 'Shënim (opsional)', book_service: 'Rezervo këtë shërbim',
                note_placeholder: 'p.sh. stili i dëshiruar', loading: 'Po kontrollohen oraret…', no_slots: 'Nuk ka orare të lira për këtë ditë.',
                submit: 'Dërgoni kërkesën', required: 'Plotësoni të gjitha fushat e detyrueshme dhe zgjidhni orën.',
                occupied: 'i zënë', slot_taken: 'Ky orar sapo u rezervua. Zgjidh një orar tjetër.',
                session_expired: 'Sesioni ka skaduar. Rifresko faqen dhe provo përsëri.',
                invalid_selection: 'Kontrollo të dhënat dhe zgjidh një berber, shërbim, datë dhe orë të vlefshme.',
                unavailable: 'Berberi ose shërbimi nuk është i disponueshëm.',
                too_many: 'Janë bërë shumë kërkesa. Prit pak dhe provo përsëri.',
                generic_error: 'Ndodhi një gabim. Provo përsëri.',
                success_eyebrow: 'U dërgua', success_title: 'Rezervimi është në pritje',
                success_text: 'Kontrollo statusin kur të duash vetëm me numrin e telefonit.', open_status: 'Kontrollo statusin'
            },
            client: {
                back: 'Kthehu te faqja', eyebrow: 'Rezervimet e tua', title: 'Kontrollo statusin',
                lead: 'Shkruaj numrin që përdore gjatë rezervimit.',
                phone: 'Numri i telefonit', submit: 'Kontrollo statusin',
                not_found: 'Nuk u gjet asnjë rezervim i fundit me këtë numër telefoni.', too_many: 'Shumë kërkesa. Prit 10 minuta dhe provo përsëri.', barber: 'Berberi', service: 'Shërbimi',
                date: 'Data', time: 'Ora', price: 'Çmimi', status: 'Statusi', note: 'Shënim:', privacy: 'Numri përdoret vetëm për të gjetur rezervimet e tua dhe nuk shfaqet publikisht.',
                new_booking: 'Bëj një rezervim të ri', other_phone: 'Përdor numër tjetër',
                pending_text: 'Kërkesa është regjistruar dhe pret konfirmimin e berberit.', accepted_text: 'Termini është konfirmuar. Ju presim në orarin e rezervuar.',
                rejected_text: 'Kërkesa nuk u pranua. Ju lutemi zgjidhni një termin tjetër.', cancelled_text: 'Ky rezervim është anuluar.'
            },
            status: { pending: 'Në pritje', accepted: 'Pranuar', rejected: 'Refuzuar', cancelled: 'Anuluar' }
        },
        mk: {
            common: { skip: 'Прескокни до содржината', language: 'Избери јазик', close: 'Затвори' },
            nav: { menu: 'Отвори мени', aria: 'Главна навигација', services: 'Услуги', barbers: 'Бербери', my_booking: 'Моја резервација', book: 'Резервирај' },
            hero: {
                eyebrow: 'GentlemanBarber', title: 'Чист стил. Лесна резервација.',
                lead: 'Изберете бербер, услуга и време. За останатото се грижиме ние.',
                primary: 'Резервирај термин', secondary: 'Види услуги', availability_short: 'Слободни термини',
                availability: 'Во следните 3 дена', image_alt: 'Модерен ентериер на GentlemanBarber'
            },
            benefit: {
                fast: { title: 'Резервирај брзо', text: 'Три избори и барањето е испратено.' },
                pro: { title: 'Избери бербер', text: 'Резервирај директно кај берберот што го сакаш.' },
                wait: { title: 'Дојди на време', text: 'Резервираното време е само за тебе.' }
            },
            services: { kicker: 'Јасни цени', title: 'Услуги и цени', lead: 'Изберете услуга и видете ја цената пред резервација.' },
            duration: { minutes: 'мин.' },
            barbers: { kicker: 'Тимот', title: 'Избери бербер', lead: 'Погледнете го тимот и резервирајте со еден клик.' },
            barber: { years: 'год.', book: 'Резервирај кај', photo: 'Профилна фотографија на' },
            lookup: { kicker: 'Имате резервација?', title: 'Провери резервација', lead: 'Внесете го телефонскиот број за да го видите статусот.', submit: 'Провери статус' },
            footer: { tagline: 'Добар стил. Без чекање.', hours: 'Работно време', days: 'Секој ден', contact: 'Контакт' },
            booking: {
                eyebrow: 'Ваш термин', title: 'Резервирајте термин', with: 'Кај', window: 'Термините може да се резервираат најмногу 3 дена однапред.',
                barber: 'Избери бербер', barber_placeholder: '— Избери —', choose_barber: 'Прво изберете бербер',
                name: 'Име и презиме', phone: 'Телефонски број', email: 'Е-пошта (незадолжително)', service: 'Избери услуга',
                service_placeholder: '— Избери —', date: 'Избери датум', time: 'Избери време', note: 'Забелешка (незадолжително)', book_service: 'Резервирај ја оваа услуга',
                note_placeholder: 'на пр. посакуван стил', loading: 'Ги проверуваме термините…', no_slots: 'Нема слободни термини за овој ден.',
                submit: 'Испратете барање', required: 'Пополнете ги задолжителните полиња и изберете време.',
                occupied: 'зафатено', slot_taken: 'Терминот во меѓувреме беше резервиран. Избери друг.',
                session_expired: 'Сесијата истече. Освежете ја страницата и обидете се повторно.',
                invalid_selection: 'Проверете ги податоците и изберете важечки бербер, услуга, датум и време.',
                unavailable: 'Берберот или услугата не се достапни.',
                too_many: 'Испратени се премногу барања. Почекајте малку и обидете се повторно.',
                generic_error: 'Настана грешка. Обиди се повторно.',
                success_eyebrow: 'Испратено', success_title: 'Резервацијата чека потврда',
                success_text: 'Проверете го статусот кога сакате само со телефонскиот број.', open_status: 'Провери статус'
            },
            client: {
                back: 'Назад кон страницата', eyebrow: 'Вашите резервации', title: 'Провери статус',
                lead: 'Внесете го бројот што го користевте при резервацијата.',
                phone: 'Телефонски број', submit: 'Провери статус',
                not_found: 'Не е пронајдена неодамнешна резервација со овој телефонски број.', too_many: 'Премногу барања. Почекајте 10 минути и обидете се повторно.', barber: 'Бербер', service: 'Услуга',
                date: 'Датум', time: 'Време', price: 'Цена', status: 'Статус', note: 'Забелешка:', privacy: 'Бројот се користи само за пронаоѓање на вашите резервации и не се прикажува јавно.',
                new_booking: 'Направи нова резервација', other_phone: 'Користи друг број',
                pending_text: 'Барањето е евидентирано и чека потврда од берберот.', accepted_text: 'Терминот е потврден. Ве очекуваме во резервираното време.',
                rejected_text: 'Барањето не е прифатено. Ве молиме изберете друг термин.', cancelled_text: 'Оваа резервација е откажана.'
            },
            status: { pending: 'Чека потврда', accepted: 'Прифатена', rejected: 'Одбиена', cancelled: 'Откажана' }
        },
        en: {
            common: { skip: 'Skip to content', language: 'Choose language', close: 'Close' },
            nav: { menu: 'Open menu', aria: 'Main navigation', services: 'Services', barbers: 'Barbers', my_booking: 'My booking', book: 'Book now' },
            hero: {
                eyebrow: 'GentlemanBarber', title: 'Clean style. Easy booking.',
                lead: 'Choose your barber, service, and time. We’ll take care of the rest.',
                primary: 'Book now', secondary: 'View services', availability_short: 'Times available',
                availability: 'For the next 3 days', image_alt: 'Modern GentlemanBarber interior'
            },
            benefit: {
                fast: { title: 'Book quickly', text: 'Three choices and your request is sent.' },
                pro: { title: 'Choose your barber', text: 'Book directly with the barber you prefer.' },
                wait: { title: 'Arrive on time', text: 'Your booked time is reserved for you.' }
            },
            services: { kicker: 'Clear pricing', title: 'Services and prices', lead: 'Choose a service and see the price before booking.' },
            duration: { minutes: 'min' },
            barbers: { kicker: 'The team', title: 'Choose your barber', lead: 'Meet the team and book in one click.' },
            barber: { years: 'years', book: 'Book with', photo: 'Profile photo of' },
            lookup: { kicker: 'Already booked?', title: 'Check your booking', lead: 'Enter your phone number to view the status.', submit: 'Check status' },
            footer: { tagline: 'Great style. No waiting.', hours: 'Opening hours', days: 'Every day', contact: 'Contact' },
            booking: {
                eyebrow: 'Your appointment', title: 'Book an appointment', with: 'With', window: 'Appointments may be booked up to 3 days in advance.',
                barber: 'Choose a barber', barber_placeholder: '— Choose —', choose_barber: 'Choose a barber first',
                name: 'Full name', phone: 'Phone number', email: 'Email (optional)', service: 'Choose a service',
                service_placeholder: '— Choose —', date: 'Choose a date', time: 'Choose a time', note: 'Note (optional)', book_service: 'Book this service',
                note_placeholder: 'e.g. preferred style', loading: 'Checking available times…', no_slots: 'No times are available on this day.',
                submit: 'Send booking request', required: 'Complete all required fields and choose a time.',
                occupied: 'occupied', slot_taken: 'This time was just booked. Please choose another.',
                session_expired: 'Your session expired. Refresh the page and try again.',
                invalid_selection: 'Check your details and choose a valid barber, service, date, and time.',
                unavailable: 'The barber or service is unavailable.',
                too_many: 'Too many requests were sent. Wait a moment and try again.',
                generic_error: 'Something went wrong. Please try again.',
                success_eyebrow: 'Sent', success_title: 'Your booking is pending',
                success_text: 'Check the status any time using only your phone number.', open_status: 'Check status'
            },
            client: {
                back: 'Back to the website', eyebrow: 'Your bookings', title: 'Check status',
                lead: 'Enter the number you used when booking.',
                phone: 'Phone number', submit: 'Check status',
                not_found: 'No recent booking was found with this phone number.', too_many: 'Too many requests. Wait 10 minutes and try again.', barber: 'Barber', service: 'Service',
                date: 'Date', time: 'Time', price: 'Price', status: 'Status', note: 'Note:', privacy: 'Your number is used only to find your bookings and is never shown publicly.',
                new_booking: 'Make a new booking', other_phone: 'Use another number',
                pending_text: 'Your request has been received and is awaiting confirmation from the barber.', accepted_text: 'Your appointment is confirmed. We look forward to seeing you at the reserved time.',
                rejected_text: 'Your request was not accepted. Please choose another appointment.', cancelled_text: 'This booking has been cancelled.'
            },
            status: { pending: 'Pending', accepted: 'Accepted', rejected: 'Declined', cancelled: 'Cancelled' }
        }
    };

    const body = document.body;
    const basePath = body.dataset.basePath || '';
    let language = 'sq';
    try {
        const saved = localStorage.getItem('gentlemanbarber_language');
        if (saved && translations[saved]) language = saved;
    } catch (_) {}

    const getText = (key) => key.split('.').reduce((value, part) => value && value[part], translations[language]) || key;

    function applyLanguage(nextLanguage) {
        if (!translations[nextLanguage]) return;
        language = nextLanguage;
        document.documentElement.lang = language;
        try { localStorage.setItem('gentlemanbarber_language', language); } catch (_) {}

        document.querySelectorAll('[data-language]').forEach((button) => {
            const active = button.dataset.language === language;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
        document.querySelectorAll('[data-i18n]').forEach((node) => {
            node.textContent = getText(node.dataset.i18n);
        });
        document.querySelectorAll('[data-i18n-placeholder]').forEach((node) => {
            node.placeholder = getText(node.dataset.i18nPlaceholder);
        });
        document.querySelectorAll('[data-i18n-aria-label]').forEach((node) => {
            node.setAttribute('aria-label', getText(node.dataset.i18nAriaLabel));
        });
        document.querySelectorAll('[data-i18n-alt]').forEach((node) => {
            node.setAttribute('alt', getText(node.dataset.i18nAlt));
        });
        document.querySelectorAll('[data-barber-photo-alt]').forEach((node) => {
            node.setAttribute('alt', `${getText('barber.photo')} ${node.dataset.barberName || ''}`.trim());
        });
        document.querySelectorAll('[data-service-card]').forEach((card) => {
            const name = card.querySelector('[data-service-name]');
            const description = card.querySelector('[data-service-description]');
            if (name) name.textContent = card.dataset[`name${language[0].toUpperCase()}${language.slice(1)}`] || card.dataset[`name${language}`] || card.getAttribute(`data-name-${language}`);
            if (description) description.textContent = card.getAttribute(`data-description-${language}`) || '';
        });
        document.querySelectorAll('[data-barber-card]').forEach((card) => {
            const title = card.querySelector('[data-barber-title]');
            const bio = card.querySelector('[data-barber-bio]');
            if (title) title.textContent = card.getAttribute(`data-title-${language}`) || '';
            if (bio) bio.textContent = card.getAttribute(`data-bio-${language}`) || '';
        });
        const serviceSelect = document.querySelector('#service-select');
        if (serviceSelect) {
            [...serviceSelect.options].forEach((option, index) => {
                if (index === 0) return;
                option.textContent = `${option.getAttribute(`data-name-${language}`)} · ${option.dataset.price}`;
            });
        }
        const dateSelect = document.querySelector('#appointment-date');
        if (dateSelect) {
            const dateWords = {
                sq: {
                    days: ['E diel', 'E hënë', 'E martë', 'E mërkurë', 'E enjte', 'E premte', 'E shtunë'],
                    months: ['janar', 'shkurt', 'mars', 'prill', 'maj', 'qershor', 'korrik', 'gusht', 'shtator', 'tetor', 'nëntor', 'dhjetor']
                },
                mk: {
                    days: ['Недела', 'Понеделник', 'Вторник', 'Среда', 'Четврток', 'Петок', 'Сабота'],
                    months: ['јануари', 'февруари', 'март', 'април', 'мај', 'јуни', 'јули', 'август', 'септември', 'октомври', 'ноември', 'декември']
                },
                en: {
                    days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                    months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
                }
            };
            [...dateSelect.options].forEach((option) => {
                const date = new Date(`${option.value}T12:00:00`);
                const words = dateWords[language];
                option.textContent = `${words.days[date.getDay()]}, ${date.getDate()} ${words.months[date.getMonth()]}`;
            });
        }
        const bookingLanguage = document.querySelector('#booking-language');
        if (bookingLanguage) bookingLanguage.value = language;
        document.querySelectorAll('[data-status-value]').forEach((node) => {
            node.textContent = getText(`status.${node.dataset.statusValue}`);
        });
        document.querySelectorAll('[data-status-description]').forEach((node) => {
            node.textContent = getText(`client.${node.dataset.statusDescription}_text`);
        });
        document.querySelectorAll('[data-client-service]').forEach((node) => {
            node.textContent = node.getAttribute(`data-service-${language}`) || node.dataset.serviceFallback || node.textContent;
        });
        document.dispatchEvent(new CustomEvent('gentlemanbarber:languagechange', { detail: { language } }));
    }

    document.querySelectorAll('[data-language]').forEach((button) => {
        button.addEventListener('click', () => {
            applyLanguage(button.dataset.language);
            updateSelectedBarberSummary();
            if (modal?.classList.contains('is-open')) loadAvailability();
        });
    });
    applyLanguage(language);

    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('#main-nav');
    if (menuToggle && nav) {
        menuToggle.addEventListener('click', () => {
            const open = nav.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
            nav.classList.remove('is-open');
            menuToggle.setAttribute('aria-expanded', 'false');
        }));
    }

    const modal = document.querySelector('#booking-modal');
    const dialog = modal?.querySelector('.booking-dialog');
    const bookingForm = document.querySelector('#booking-form');
    const bookingNotice = modal?.querySelector('.booking-notice');
    const successPanel = document.querySelector('#booking-success');
    const selectedBarberName = document.querySelector('#selected-barber-name');
    const selectedBarberPrefix = document.querySelector('#selected-barber-prefix');
    const barberInput = document.querySelector('#barber-id');
    const serviceSelect = document.querySelector('#service-select');
    const dateSelect = document.querySelector('#appointment-date');
    const timeInput = document.querySelector('#appointment-time');
    const timeSlots = document.querySelector('#time-slots');
    const bookingMessage = document.querySelector('#booking-message');
    let previouslyFocused = null;
    let inertedElements = [];

    function updateSelectedBarberSummary() {
        if (!selectedBarberName || !barberInput) return;
        const option = barberInput.selectedOptions?.[0];
        const hasBarber = Boolean(barberInput.value && option);
        selectedBarberName.textContent = hasBarber
            ? (option.dataset.name || option.textContent || '')
            : getText('booking.choose_barber');
        if (selectedBarberPrefix) selectedBarberPrefix.hidden = !hasBarber;
    }

    function setPageInert(inert) {
        if (!modal) return;
        if (inert) {
            inertedElements = [...body.children]
                .filter((element) => element !== modal && element instanceof HTMLElement)
                .map((element) => ({ element, wasInert: element.hasAttribute('inert') }));
            inertedElements.forEach(({ element }) => { element.inert = true; });
            return;
        }
        inertedElements.forEach(({ element, wasInert }) => {
            if (!wasInert) element.inert = false;
        });
        inertedElements = [];
    }

    function apiErrorMessage(response, data = {}) {
        const errorCode = String(data.error_code || data.code || '').toLowerCase();
        const errorKeys = {
            csrf: 'booking.session_expired',
            session_expired: 'booking.session_expired',
            rate_limit: 'booking.too_many',
            too_many_requests: 'booking.too_many',
            slot_taken: 'booking.slot_taken',
            slot_unavailable: 'booking.slot_taken',
            unavailable: 'booking.unavailable',
            invalid_selection: 'booking.invalid_selection',
            validation: 'booking.invalid_selection'
        };
        if (errorKeys[errorCode]) return getText(errorKeys[errorCode]);
        if (response.status === 419) return getText('booking.session_expired');
        if (response.status === 429) return getText('booking.too_many');
        if (response.status === 409) return getText('booking.slot_taken');
        if (response.status === 404) return getText('booking.unavailable');
        if (response.status === 422) return getText('booking.invalid_selection');
        return getText('booking.generic_error');
    }

    async function loadAvailability() {
        if (!timeSlots || !timeInput) return;
        timeInput.value = '';
        if (!barberInput?.value) {
            timeSlots.innerHTML = `<p class="slots-message">${getText('booking.choose_barber')}</p>`;
            return;
        }
        if (!dateSelect?.value) return;
        timeSlots.innerHTML = `<p class="slots-message">${getText('booking.loading')}</p>`;
        try {
            const serviceQuery = serviceSelect?.value ? `&service_id=${encodeURIComponent(serviceSelect.value)}` : '';
            const response = await fetch(`${basePath}/api/availability.php?barber_id=${encodeURIComponent(barberInput.value)}&date=${encodeURIComponent(dateSelect.value)}${serviceQuery}`, {
                headers: { Accept: 'application/json' }
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.ok) throw new Error(apiErrorMessage(response, data));
            timeSlots.innerHTML = '';
            const availableCount = data.slots.filter((slot) => slot.available).length;
            data.slots.forEach((slot) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'time-slot';
                button.textContent = slot.time;
                button.disabled = !slot.available;
                button.setAttribute('aria-pressed', 'false');
                button.setAttribute('aria-label', `${slot.time}${slot.available ? '' : ` — ${getText('booking.occupied')}`}`);
                button.addEventListener('click', () => {
                    timeSlots.querySelectorAll('.time-slot').forEach((item) => {
                        item.classList.remove('is-selected');
                        item.setAttribute('aria-pressed', 'false');
                    });
                    button.classList.add('is-selected');
                    button.setAttribute('aria-pressed', 'true');
                    timeInput.value = slot.time;
                    bookingMessage.textContent = '';
                });
                timeSlots.appendChild(button);
            });
            if (availableCount === 0) timeSlots.innerHTML = `<p class="slots-message">${getText('booking.no_slots')}</p>`;
        } catch (error) {
            const message = error instanceof TypeError ? getText('booking.generic_error') : error.message;
            timeSlots.innerHTML = `<p class="slots-message">${message}</p>`;
        }
    }

    function openBooking(button) {
        if (!modal || !dialog || !bookingForm) return;
        previouslyFocused = document.activeElement;
        bookingForm.reset();
        barberInput.value = button.dataset.bookBarber || '';
        const bookingLanguage = document.querySelector('#booking-language');
        if (bookingLanguage) bookingLanguage.value = language;
        if (button.dataset.serviceId) serviceSelect.value = button.dataset.serviceId;
        updateSelectedBarberSummary();
        bookingForm.hidden = false;
        if (bookingNotice) bookingNotice.hidden = false;
        successPanel.hidden = true;
        bookingMessage.textContent = '';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        body.classList.add('modal-open');
        setPageInert(true);
        requestAnimationFrame(() => dialog.focus());
        loadAvailability();
    }

    function closeBooking() {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        body.classList.remove('modal-open');
        setPageInert(false);
        previouslyFocused?.focus();
    }

    document.querySelectorAll('[data-book-barber]').forEach((button) => button.addEventListener('click', () => openBooking(button)));
    document.querySelectorAll('[data-close-modal]').forEach((button) => button.addEventListener('click', closeBooking));
    barberInput?.addEventListener('change', () => {
        updateSelectedBarberSummary();
        loadAvailability();
    });
    dateSelect?.addEventListener('change', loadAvailability);
    serviceSelect?.addEventListener('change', loadAvailability);

    document.addEventListener('keydown', (event) => {
        if (!modal?.classList.contains('is-open')) return;
        if (event.key === 'Escape') closeBooking();
        if (event.key === 'Tab' && dialog) {
            const focusable = [...dialog.querySelectorAll('button:not(:disabled), a[href], input:not([type="hidden"]), select, textarea')]
                .filter((node) => !node.hidden && !node.closest('[hidden]') && node.getClientRects().length > 0);
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
            else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
        }
    });

    bookingForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        bookingMessage.textContent = '';
        if (!bookingForm.checkValidity() || !timeInput.value) {
            bookingMessage.textContent = getText('booking.required');
            bookingForm.reportValidity();
            return;
        }

        const submit = bookingForm.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            const payload = Object.fromEntries(new FormData(bookingForm).entries());
            const response = await fetch(`${basePath}/api/book.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok || !data.ok) {
                if (response.status === 409) {
                    bookingMessage.textContent = getText('booking.slot_taken');
                    await loadAvailability();
                } else {
                    bookingMessage.textContent = apiErrorMessage(response, data);
                }
                return;
            }

            bookingForm.hidden = true;
            if (bookingNotice) bookingNotice.hidden = true;
            successPanel.hidden = false;
            successPanel.querySelector('button, a')?.focus();
        } catch (_) {
            bookingMessage.textContent = getText('booking.generic_error');
        } finally {
            submit.disabled = false;
        }
    });

    const clientBookings = document.querySelector('[data-client-bookings]');
    async function refreshClientStatuses() {
        if (!clientBookings || document.hidden) return;
        try {
            const response = await fetch(clientBookings.dataset.clientStatusUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            const data = await response.json();
            if (!response.ok || !data.ok) return;
            const cards = [...clientBookings.querySelectorAll('[data-booking-id]')];
            if (cards.length !== data.bookings.length) {
                window.location.reload();
                return;
            }
            const cardMap = new Map(cards.map((card) => [Number(card.dataset.bookingId), card]));
            data.bookings.forEach((booking) => {
                const card = cardMap.get(Number(booking.id));
                if (!card) return;
                const badge = card.querySelector('[data-status-badge]');
                const value = card.querySelector('[data-status-value]');
                const message = card.querySelector('[data-status-message]');
                const icon = card.querySelector('[data-status-icon]');
                const description = card.querySelector('[data-status-description]');
                if (badge) badge.className = `status-badge ${booking.status_class}`;
                if (value) { value.dataset.statusValue = booking.status; value.textContent = getText(`status.${booking.status}`); }
                if (message) message.className = `status-message ${booking.status_class}`;
                if (icon) icon.textContent = booking.status === 'accepted' ? '✓' : (booking.status === 'pending' ? '…' : '!');
                if (description) { description.dataset.statusDescription = booking.status; description.textContent = getText(`client.${booking.status}_text`); }
            });
        } catch (_) {
            // The panel keeps the last confirmed state when the connection is interrupted.
        }
    }
    if (clientBookings) {
        window.setInterval(refreshClientStatuses, 12000);
        document.addEventListener('visibilitychange', refreshClientStatuses);
    }

})();
