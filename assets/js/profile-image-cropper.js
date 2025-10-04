document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const avatarInput = document.getElementById('avatar');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const imagePreview = document.getElementById('image-preview');
    const cropButton = document.getElementById('crop-button');
    const croppedImageInput = document.getElementById('cropped-image-data');
    
    let cropper;
    
    // Initialize image preview and cropper when file is selected
    avatarInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        
        if (!file) {
            imagePreviewContainer.style.display = 'none';
            return;
        }
        
        // Check if file is an image
        if (!file.type.match('image.*')) {
            alert('Please select an image file');
            return;
        }
        
        // Create a FileReader to read the image
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Display the image preview container
            imagePreviewContainer.style.display = 'block';
            
            // Set the image source
            imagePreview.src = e.target.result;
            
            // Destroy existing cropper if it exists
            if (cropper) {
                cropper.destroy();
            }
            
            // Initialize cropper after image is loaded
            imagePreview.onload = function() {
                cropper = new Cropper(imagePreview, {
                    aspectRatio: 1, // Square crop
                    viewMode: 1,    // Restrict the crop box to not exceed the size of the canvas
                    autoCropArea: 0.8, // Define the automatic cropping area size
                    responsive: true,
                    guides: true,
                    center: true,
                    highlight: true,
                    background: false,
                    cropBoxResizable: true,
                    cropBoxMovable: true
                });
            };
        };
        
        reader.readAsDataURL(file);
    });
    
    // Crop button click handler
    cropButton.addEventListener('click', function() {
        if (!cropper) {
            return;
        }
        
        // Get the cropped canvas
        const canvas = cropper.getCroppedCanvas({
            width: 200,   // Output image width
            height: 200,  // Output image height
            fillColor: '#fff',
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });
        
        if (canvas) {
            // Convert canvas to base64 string
            const croppedImageData = canvas.toDataURL('image/png');
            
            // Set the cropped image data to the hidden input
            croppedImageInput.value = croppedImageData;
            
            // Update the preview with the cropped image
            imagePreview.src = croppedImageData;
            
            // Destroy the cropper but keep the preview visible
            cropper.destroy();
            cropper = null;
            
            // Show success message
            alert('Image cropped successfully! Click "Update Profile" to save changes.');
        }
    });
});