document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById("dicomCanvas");
    const cornerstoneElement = canvas;

    // Enable Cornerstone on the canvas
    cornerstone.enable(cornerstoneElement);

    const fileInput = document.getElementById("file-input");

    // Allow any file type
    fileInput.addEventListener("change", function (event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const arrayBuffer = e.target.result;

                // Check if the file is a valid DICOM by inspecting the header
                if (isDicomFile(arrayBuffer)) {
                    loadDicomImage(arrayBuffer);
                } else {
                    alert("The file does not appear to be a valid DICOM file.");
                }
            };
            reader.readAsArrayBuffer(file);
        }
    });

    function isDicomFile(arrayBuffer) {
        // DICOM files start with 'DICM' at byte offset 128 (file magic number)
        const byteArray = new Uint8Array(arrayBuffer);
        const dicmHeader = [68, 73, 67, 77]; // ASCII for 'DICM'

        for (let i = 0; i < dicmHeader.length; i++) {
            if (byteArray[128 + i] !== dicmHeader[i]) {
                return false;
            }
        }
        return true;
    }

    function loadDicomImage(arrayBuffer) {
        const fileBlob = new Blob([arrayBuffer], { type: 'application/octet-stream' });
        const imageId = cornerstoneWADOImageLoader.wadouri.fileManager.add(fileBlob);

        cornerstone.loadAndCacheImage(imageId).then(function (image) {
            cornerstone.displayImage(cornerstoneElement, image);
        }).catch(function (err) {
            console.error("Error loading DICOM image:", err);
        });
    }
});
