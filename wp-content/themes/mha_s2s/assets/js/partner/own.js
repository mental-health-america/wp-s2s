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

    var buttons = document.querySelectorAll('#screen-about, #screen-email, #screen-answers');
    var clickHandler = function(ev) {
        var id = ev.currentTarget.id;
        var targetId;
        if (/about/.test(id)) {
            targetId = 'score-interpretation';
        }
        else if (/email/.test(id)) {
            targetId = 'email-results';
        }
        else {
            targetId = 'your-answers';
        }
        var target = document.getElementById(targetId);

        if (target && !/\bshow\b/.test(target.className)) {
            window.requestAnimationFrame(function() {
                window.parent.postMessage(JSON.stringify({
                    eventName: 'scroll',
                    scrollY: window.scrollY,
                    rect: target.getBoundingClientRect()
                }), '*');
            });
        }

    };
    if (buttons.length) {
        buttons.forEach(function(btn) {
            btn.addEventListener('click', clickHandler);
        });
    }
});
