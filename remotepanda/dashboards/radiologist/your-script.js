// Import CornerstoneJS modules
import * as cornerstone from '@cornerstonejs/core';
import * as cornerstoneTools from '@cornerstonejs/tools';
import '@cornerstonejs/streaming-image-volume-loader';

// Set up CornerstoneJS
cornerstoneTools.init();

// Load DICOM image 
const element = document.getElementById('viewer');
const imageId = 'wadouri:C:\Sante Server DB\2023\12\VONGAI MASANGANISE (01)\1.2.300.0.7230010.3.1.3.2331142439.2612.1702393677.4\1.2.392.200036.9125.3.160722813917878.6554080494.39727';
cornerstone.enable(element);
cornerstone.loadImage(imageId).then(image => {
    cornerstone.displayImage(element, image);
});




















































































