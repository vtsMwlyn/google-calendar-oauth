// Get the client ID from the meta tag
const clientId = document.querySelector('meta[name="google-client-id"]').getAttribute('content');

// Load the Google API client library
function initGoogleCalendar() {
    gapi.load('client:auth2', initAuth);
}

function initAuth() {
    gapi.auth2.init({
        client_id: clientId  // Use the clientId from the meta tag
    }).then(function () {
        // Check if the user is signed in
        if (gapi.auth2.getAuthInstance().isSignedIn.get()) {
            loadCalendar();
        } else {
            gapi.auth2.getAuthInstance().signIn().then(loadCalendar);
        }
    });
}

function loadCalendar() {
    // Load the Calendar API
    gapi.client.load('calendar', 'v3', function () {
        displayCalendar();
    });
}

function displayCalendar() {
    gapi.client.calendar.events.list({
        'calendarId': 'primary', // 'primary' for the authenticated user's calendar
        'timeMin': (new Date()).toISOString(),
        'showDeleted': false,
        'singleEvents': true,
        'maxResults': 10,
        'orderBy': 'startTime'
    }).then(function (response) {
        var events = response.result.items;
        if (events.length > 0) {
            var eventList = '<ul>';
            events.forEach(function(event) {
                eventList += `<li>${event.summary} (${event.start.dateTime || event.start.date})</li>`;
            });
            eventList += '</ul>';
            document.getElementById('calendar-events').innerHTML = eventList;
        } else {
            document.getElementById('calendar-events').innerHTML = 'No upcoming events found.';
        }
    });
}

window.onload = initGoogleCalendar;
