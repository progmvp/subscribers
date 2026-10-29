document.querySelectorAll('.clickable-image').forEach(function(image) {
    image.addEventListener('click', function() {
        window.location.href = image.getAttribute('href');
    });
});
