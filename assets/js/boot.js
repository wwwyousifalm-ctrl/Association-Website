/* Loaded in <head> on every page: applies the saved theme before first paint and shares the session helper. */
(function () {
  try {
    var t = localStorage.getItem("fmcs-theme");
    if (t === "light" || t === "dark")
      document.documentElement.setAttribute("data-theme", t);
  } catch (e) {}
  var F = (window.FMCS = window.FMCS || {});
  F.base = location.pathname.indexOf("/admin/") !== -1 ? "../" : "";
  // One cached call returns { authenticated, role, user, csrf_token }
  F.session = function () {
    if (!F._s) {
      F._s = fetch(F.base + "api/auth/session.php", {
        credentials: "same-origin",
        headers: { Accept: "application/json" },
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (j) {
          return j.data || { authenticated: false };
        });
    }
    return F._s;
  };
})();
