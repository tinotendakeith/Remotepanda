<!DOCTYPE html>
<html>
<head>
    <title>Radpanda Diary</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha.6/css/bootstrap.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.min.js"></script>
    <script>
        $(document).ready(function() {

            var eventColors = {
                'hot': '#FF0000', // Red
                'warm': '#00FF00', // Green
                'cold': '#0000FF', // Blue
                // Add more event types and colors as needed
            };

            var calendar = $('#calendar').fullCalendar({
                editable: true,
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'agendaWeek,month,list,agendaDay'
                },

                events: 'load.php',
                selectable: true,
                selectHelper: true,
                select: function(start, end, allDay) {
                    var title = prompt("Enter Patient Name");
                    var modality = prompt("Enter Modality");
                    var location = prompt("Enter location");
                    var eventType = prompt("Enter Event Type (e.g., hot, warm, cold)");

                    if (title && modality && location && eventType && eventColors.hasOwnProperty(eventType)) {
                        var start = $.fullCalendar.formatDate(start, "Y-MM-DD HH:mm:ss");
                        var end = $.fullCalendar.formatDate(end, "Y-MM-DD HH:mm:ss");
                        var color = eventColors[eventType]; // Get the color based on the event type

                        $.ajax({
                            url: "insert.php",
                            type: "POST",
                            data: {
                                title: title,
                                modality: modality,
                                location: location,
                                start: start,
                                end: end,
                                color: color
                            },
                            success: function() {
                                calendar.fullCalendar('refetchEvents');
                                alert("Added Successfully");
                            }
                        });
                    }
                },

                defaultView: 'agendaDay', // Set the default view to 'agendaDay'

                    views: {
                        agendaDay: {
                            type: 'agenda',
                            duration: { days: 1 }, // Display only one day at a time
                            buttonText: 'Day',
                            groupByResource: true // Enable grouping of events by resource (location)
                        }   
                    },
                editable: true,
                eventResize: function(event) {
                    var start = $.fullCalendar.formatDate(event.start, "Y-MM-DD HH:mm:ss");
                    var end = $.fullCalendar.formatDate(event.end, "Y-MM-DD HH:mm:ss");
                    var title = event.title;
                    var modality = event.modality;
                    var location = event.location;
                    var id = event.id;
                    var color = event.color; // Fix: Assign the color from the event object
                    $.ajax({
                        url: "update.php",
                        type: "POST",
                        data: {
                            title: title,
                            modality: modality,
                            location: location,
                            color: color,
                            start: start,
                            end: end,
                            id: id
                        },
                        success: function() {
                            calendar.fullCalendar('refetchEvents');
                            alert('Event Update');
                        }
                    })
                },
                eventDrop: function(event) {
                    var start = $.fullCalendar.formatDate(event.start, "Y-MM-DD HH:mm:ss");
                    var start = $.fullCalendar.formatDate(event.start, "Y-MM-DD HH:mm:ss");
                    var end = $.fullCalendar.formatDate(event.end, "Y-MM-DD HH:mm:ss");
                    var title = event.title;
                    var modality = event.modality;
                    var location = event.location;
                    var id = event.id;
                    var color = event.color; // Fix: Assign the color from the event object
                    $.ajax({
                        url: "update.php",
                        type: "POST",
                        data: {
                            title: title,
                            modality: modality,
                            location: location,
                            start: start,
                            color: color,
                            end: end,
                            id: id
                        },
                        success: function() {
                            calendar.fullCalendar('refetchEvents');
                            alert("Event Updated");
                        }
                    });
                },
                eventClick: function(event) {
                    if (confirm("Are you sure you want to remove it?")) {
                        var id = event.id;
                        $.ajax({
                            url: "delete.php",
                            type: "POST",
                            data: {
                                id: id
                            },
                            success: function() {
                                calendar.fullCalendar('refetchEvents');
                                alert("Event Removed");
                            }
                        })
                    }
                },
                eventRender: function(event, element) {
                    element.on('contextmenu', function(e) {
                        e.preventDefault(); // Prevent the default right-click menu
                        // Show your custom context menu or perform any desired action
                        showContextMenu(event, e.pageX, e.pageY);
                    }).on('mouseenter', function() {
                        showEventDetails(event, this);
                    }).on('mouseleave', function() {
                        hideEventDetails();
                    });
                }
            });

            function showContextMenu(event, x, y) {
                // Create and display your custom context menu
                var menu = $('<ul class="context-menu">' +
                    '<li>View Details</li>' +
                    '<li>Edit Event</li>' +
                    '<li>Delete Event</li>' +
                    '</ul>');
                menu.css({
                    position: 'absolute',
                    left: x,
                    top: y,
                    zIndex: 10000 // Set a higher z-index value
                });
                $('body').append(menu);

                // Handle menu item clicks
                menu.on('click', 'li', function() {
                    var action = $(this).text();
                    switch (action) {
                        case 'View Details':
                            // Perform action for viewing event details
                            // Replace this with your own logic
                            alert('View Details clicked for event: ' + event.title);
                            break;
                        case 'Edit Event':
                            // Perform action for editing event
                            // Replace this with your own logic
                            alert('Edit Event clicked for event: ' + event.title);
                            break;
                        case 'Delete Event':
                            // Perform action for deleting event
                            // Replace this with your own logic
                            alert('Delete Event clicked for event: ' + event.title);
                            break;
                        default:
                            break;
                    }
                    menu.remove(); // Remove the context menu after action is performed
                });

                // Close the menu when clicking outside of it
                $(document).on('click', function() {
                    menu.remove();
                });
            }

            function showEventDetails(event, element) {
                var tooltip = '<div class="event-details">' +
                    '<strong>' + event.title + '</strong><br>' +
                    'Modality: ' + event.modality + '<br>'+
                    'location: ' + event.location + '<br>'+
                    'start: ' + event.start + '<br>'+
                    'end: ' + event.end +
                    '</div>';
                $(element).append(tooltip);
            }


            function hideEventDetails() {
                $('.event-details').remove();
            }
        });
    </script>
    <style>
        .context-menu {
            position: absolute;
            z-index: 10000;
            list-style-type: none;
            background-color: #fff;
            border: 1px solid #ccc;
            padding: 5px;
        }

        .context-menu li {
            padding: 5px 10px;
            cursor: pointer;
        }

        .context-menu li:hover {
            background-color: #f2f2f2;
        }

        .event-details {
            position: absolute;
            z-index: 10000;
            padding: 5px;
            background-color: #fff;
            border: 1px solid #ccc;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <br />
    <h2 align="center"><a href="#">RADPANDA DIARY</a></h2>
    <br />
    <div class="container">
        <div id="calendar"></div>
    </div>
</body>
</html>
