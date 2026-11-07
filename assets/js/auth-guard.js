/* Hides protected pages from visitors who are not logged in. This is only a convenience:
   the PHP API checks the session and role on every request, which is the real protection. */
(function () {
  var F = window.FMCS;
  document.addEventListener("DOMContentLoaded", function () {
    var need = document.body.getAttribute("data-access");
    var main = document.getElementById("main");
    if (!need || !main) return;
    F.session()
      .then(function (s) {
        if (s.authenticated && (need === "student" || s.role === "admin")) {
          main.hidden = false;
          return;
        }
        var page = location.pathname.split("/").pop();
        location.replace(
          "login.html" +
            (need === "student" && page
              ? "?next=" + encodeURIComponent(page)
              : ""),
        );
      })
      .catch(function () {
        main.innerHTML =
          '<p class="form-alert is-error" role="alert">We could not check your login. Check your connection, then reload this page.</p>';
        main.hidden = false;
      });
  });
})();
