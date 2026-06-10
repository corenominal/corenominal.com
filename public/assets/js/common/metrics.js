(() => {
  const sendMetrics = () => {
    const data = JSON.stringify({
      path: window.location.pathname,
      deviceType: /Mobi|Android/i.test(navigator.userAgent) ? 'mobile' : 'desktop',
      interactiveTime: Math.round(performance.now()),
      windowWidth: window.innerWidth,
      windowHeight: window.innerHeight,
    });

    if (navigator.sendBeacon) {
      navigator.sendBeacon('/metrics', data);
    } else {
      fetch('/metrics/receive', { method: 'POST', body: data, keepalive: true });
    }
  };

  // Run when page is settled
  window.addEventListener('load', () => {
    // Delay slightly to ensure performance metrics are populated
    setTimeout(sendMetrics, 500);
  });
})();