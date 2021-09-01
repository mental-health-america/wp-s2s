window.addEventListener('DOMContentLoaded', function(event) {
    // Send window height to parent document
    try {
        window.parent.postMessage(JSON.stringify({
            clientHeight: window.document.body.clientHeight
        }), '*');
    }catch(e){}
});
