 $(document).ready(function(){
	 $.ajax({
		url: 'events.php',
		dataType: "json"
	}).done(function(response) {		
		var calendar = $('#calendar').Calendar({
			locale: 'en',
			weekday: {
			timeline: {
				intervalMinutes: 30,
				fromHour: 10
			}
		},
		events: response.result
		}).init();
	}); 
});