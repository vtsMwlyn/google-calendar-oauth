require('./bootstrap');

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// import { Calendar } from '@fullcalendar/core';
// import dayGridPlugin from '@fullcalendar/daygrid';
// import interactionPlugin from '@fullcalendar/interaction';

// document.addEventListener('DOMContentLoaded', function() {
//     var calendarEl = document.getElementById('calendar');

//     var calendar = new Calendar(calendarEl, {
//         plugins: [dayGridPlugin, interactionPlugin],
//         events: '/events', // URL to fetch event data
//         editable: true,
//         droppable: true,
//     });

//     calendar.render();
// });
