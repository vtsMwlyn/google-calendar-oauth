<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    You're logged in!

					{{-- <pre>{{ print_r(session('google_user_info'), true) }}</pre> --}}

					<!-- Your content goes here -->
					<h1 class="mt-4 font-bold">Upcoming Events</h1>

					<div class="overflow-y-auto w-full mt-2" style="max-height: 400px;">
						<table class="w-full">
							<thead>
								<th class="border py-2 px-4 bg-slate-200 text-start">Calendar Source</th>
								<th class="border py-2 px-4 bg-slate-200 text-start">Event</th>
								<th class="border py-2 px-4 bg-slate-200 text-start">Start</th>
								<th class="border py-2 px-4 bg-slate-200 text-start">End</th>
							</thead>

							<tbody>
								@if (count($allEventData) > 0)
									@foreach ($allEventData as $event)
										@if($event['summary'] != 'Happy birthday!')
											<tr>
												<td class="border py-2 px-4">{{ $event['calendar'] }}</td>
												<td class="border py-2 px-4">{{ $event['summary'] }}</td>
												<td class="border py-2 px-4">{{ \Carbon\Carbon::parse($event['start'])->toDayDateTimeString() }}</td>
												<td class="border py-2 px-4">{{ \Carbon\Carbon::parse($event['end'])->toDayDateTimeString() }}</td>
											</tr>
										@endif
									@endforeach
								@else
									<tr><td>No upcoming events found.</td></tr>
								@endif
							</tbody>
						</table>
					</div>

					<!-- Your content goes here -->
					<h1 class="mt-4 font-bold">Upcoming Tasks</h1>

					<div class="overflow-y-auto w-full mt-2" style="max-height: 400px;">
						<table class="w-full">
							<thead>
								<th class="border py-2 px-4 bg-slate-200 text-start">Task List Source</th>
								<th class="border py-2 px-4 bg-slate-200 text-start">Task</th>
								<th class="border py-2 px-4 bg-slate-200 text-start">Due Date</th>
							</thead>
							<tbody>
								@if (count($allTaskData) > 0)
									@foreach ($allTaskData as $task)
										<tr>
											<td class="border py-2 px-4">{{ $task['taskList'] }}</td>
											<td class="border py-2 px-4">{{ $task['title'] }}</td>
											<td class="border py-2 px-4">{{ \Carbon\Carbon::parse($task['due'])->toDayDateTimeString() }}</td>
										</tr>
									@endforeach
								@else
									<tr><td class="border py-2 px-4">No upcoming tasks found.</td></tr>
								@endif
							</tbody>
						</table>
					</div>

					<!-- FullCalendar -->
					<div id="calendar" class="mt-4"></div>

					<script>
						document.addEventListener('DOMContentLoaded', function() {
							var calendarEl = document.getElementById('calendar');

							var calendar = new FullCalendar.Calendar(calendarEl, {
								plugins: ['dayGrid', 'interaction'],
								events: '/events', // URL to retrieve events data (set in your routes)
								editable: true,
								droppable: true, // Allow dragging and dropping
							});

							calendar.render();
						});
					</script>

                </div>
            </div>
        </div>
    </div>

	<script>
		document.addEventListener('DOMContentLoaded', function() {
			const allEventData = @json($allEventData);  // Events from Google Calendar
			const allTaskData = @json($allTaskData);    // Tasks from Google Tasks

			console.log(allEventData);  // For debugging purposes
			console.log(allTaskData);   // For debugging purposes

			// Convert event data from Google Calendar
			const ibento = allEventData.map(ev => ({
				title: ev.summary,
				start: ev.start,  // Make sure this is in the correct format (ISO 8601)
				end: ev.end       // Make sure this is in the correct format (ISO 8601)
			}));

			// Convert task data from Google Tasks
			const tasuku = allTaskData.map(tsk => ({
				title: tsk.title,
				start: tsk.due,  // Make sure this is in the correct format (ISO 8601)
				end: tsk.due     // For tasks, you can use the same start and end date if it's a due date
			}));

			// Combine events and tasks into one array
			const events = [...ibento, ...tasuku];

			// Initialize the FullCalendar with combined event data
			var calendarEl = document.getElementById('calendar');
			var calendar = new FullCalendar.Calendar(calendarEl, {
				initialView: 'dayGridMonth',
				events: events,  // Pass the combined events array
			});

			calendar.render();  // Render the calendar
		});
	</script>


	<!-- Add a meta tag to store the Google Calendar Client ID -->
    {{-- <meta name="google-client-id" content="{{ env('GOOGLE_CALENDAR_CLIENT_ID') }}">
	<script src="{{ asset('js/google-calendar.js') }}"></script> --}}
</x-app-layout>
