<style>
    .footer {
        position: fixed;
        left: 0;
        bottom: 0;
        width: 100%;
        background-color: #002245; /* Add a background color */
        color: white;
        text-align: center;
    }
</style>

<div class="footer">
    <?php $currentYear = date("Y"); ?>
    <p>&copy; <?php echo $currentYear; ?> Radpanda. Developed by: <a target="_blank" href="https://example.com">Radpanda</a></p>
    <p id="datetime"></p> <!-- This is where the date and time will be displayed -->
</div>
<!--//footer-->
<script>
    function updateDateTime() {
        const datetimeElement = document.getElementById("datetime");
        const now = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric' };
        const formattedDateTime = now.toLocaleDateString('en-US', options);
        datetimeElement.textContent = formattedDateTime;
    }

    // Call the function initially to display the date and time when the page loads
    updateDateTime();

    // Update the date and time every second (1000 milliseconds)
    setInterval(updateDateTime, 1000);
</script>
