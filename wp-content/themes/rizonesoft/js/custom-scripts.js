document.addEventListener('DOMContentLoaded', function() {
     // Target the specific link using its href attribute
     const kbLink = document.querySelector('a.ast-builder-social-element[href="/knowledge-base/"]');
     if (kbLink && kbLink.target === '_blank') {
       kbLink.removeAttribute('target');
       // Optional: remove rel attribute too
       // kbLink.removeAttribute('rel');
       // Console log removed to prevent console warnings
     }
   });