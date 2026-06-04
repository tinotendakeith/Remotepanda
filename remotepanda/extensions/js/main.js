document.getElementById('uploadForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    fetch('upload.php', {
        method: 'POST',
        body: formData,
    })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                loadAndDisplayDICOM();
            } else {
                alert(data.message);
            }
        });
});

function loadAndDisplayDICOM() {
    const viewer = document.getElementById('dicomViewer');
    cornerstone.enable(viewer);

    // Fetch a DICOM file from the extracted folder
    fetch('uploads/example.dcm') // Replace with dynamic file selection
        .then(response => response.blob())
        .then(file => {
            const imageId = cornerstone.fileImageLoader.createImageId(file);
            return cornerstone.loadImage(imageId);
        })
        .then(image => {
            cornerstone.displayImage(viewer, image);
        })
        .catch(error => {
            console.error('Error loading DICOM:', error);
        });
}
