<script src="<?php echo base_url("vendor/tinymce/tinymce/tinymce.min.js") ?>" type="text/javascript"></script>
<script>
    tinymce.init({
        selector: 'textarea',
        menubar: 'file edit help',
        plugins: 'emoticons help wordcount searchreplace codesample',
        toolbar: "undo redo | indent outdent | bold italic strikethrough | emoticons codesample | wordcount "
    });
</script>