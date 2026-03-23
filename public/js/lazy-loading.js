// Lazy loading for images and content
document.addEventListener('DOMContentLoaded', function() {
    // Image lazy loading with intersection observer
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    
                    // Load the image
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                    }
                    
                    // Add fade-in effect
                    img.classList.add('fade-in');
                    
                    // Stop observing this image
                    observer.unobserve(img);
                }
            });
        }, {
            rootMargin: '50px 0px',
            threshold: 0.01
        });

        // Observe all images with data-src
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback for older browsers
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });
    }

    // Content lazy loading for modals and heavy content
    const contentObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const element = entry.target;
                
                // Load heavy content
                if (element.dataset.loadContent) {
                    loadContent(element);
                }
                
                observer.unobserve(element);
            }
        });
    }, {
        rootMargin: '100px 0px',
        threshold: 0.1
    });

    // Observe elements with data-load-content
    document.querySelectorAll('[data-load-content]').forEach(element => {
        contentObserver.observe(element);
    });

    // Preload critical images
    preloadCriticalImages();
});

function loadContent(element) {
    const contentUrl = element.dataset.loadContent;
    
    fetch(contentUrl)
        .then(response => response.text())
        .then(html => {
            element.innerHTML = html;
            element.classList.add('content-loaded');
        })
        .catch(error => {
            console.error('Error loading content:', error);
            element.innerHTML = '<p>Error loading content</p>';
        });
}

function preloadCriticalImages() {
    const criticalImages = document.querySelectorAll('[data-critical="true"]');
    
    criticalImages.forEach(img => {
        if (img.dataset.src) {
            const tempImg = new Image();
            tempImg.onload = function() {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            };
            tempImg.src = img.dataset.src;
        }
    });
}

// Add CSS for fade-in effect
const lazyLoadingStyle = document.createElement('style');
lazyLoadingStyle.textContent = `
    img.fade-in {
        animation: fadeIn 0.3s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .content-loaded {
        animation: slideUp 0.4s ease-out;
    }
    
    @keyframes slideUp {
        from { 
            opacity: 0;
            transform: translateY(20px);
        }
        to { 
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(lazyLoadingStyle);
