(function () {
  var CHANNEL = "grok-preview-bridge";
  if (window.parent === window) return;
  function safe(path) {
    return typeof path === "string" && path.charAt(0) === "/" && path.charAt(1) !== "/";
  }
  window.addEventListener("message", function (e) {
    var d = e.data;
    if (!d || d.channel !== CHANNEL) return;
    if (d.type === "hello") {
      window.parent.postMessage(
        {
          channel: CHANNEL,
          version: 1,
          type: "location",
          path: location.pathname || "/",
          search: location.search,
          hash: location.hash,
        },
        e.origin
      );
    }
    if (d.type === "navigate" && safe(d.path)) {
      location.assign(d.path);
    }
    if (d.type === "history" && (d.delta === -1 || d.delta === 1)) {
      history.go(d.delta);
    }
  });
})();
