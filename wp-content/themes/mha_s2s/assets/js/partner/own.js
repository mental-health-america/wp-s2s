window.addEventListener('DOMContentLoaded', function(event) {
    // Send window height to parent document
    function getHeight()  {
        return document.getElementById('page').getBoundingClientRect().height;
    }
    window.parent.postMessage(JSON.stringify({
        eventName: 'loaded',
        clientHeight: getHeight()
    }), '*');
    document.body.addEventListener('click', function(ev) {
        window.parent.postMessage(JSON.stringify({
            eventName: 'click',
            node: ev.target.nodeName,
            name: ev.target.name,
            className: ev.target.className,
            clientHeight: getHeight()
        }), '*');
    }, false);
    setInterval(function() {
        window.parent.postMessage(JSON.stringify({
            eventName: 'heartbeat',
            clientHeight: getHeight()
        }), '*');
    }, 1000);
});
