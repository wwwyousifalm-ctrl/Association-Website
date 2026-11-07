/* Resources page: loads the list from the database, then search and type filtering. */
(function () {
  "use strict";
  var F = window.FMCS,
    $ = F.$,
    $$ = F.$$;
  var list = $("#resource-list"),
    q = $("#filter-q"),
    t = $("#filter-type"),
    count = $("#result-count"),
    none = $("#empty-state"),
    form = $("#resource-filter");
  if (!list) return;
  var TYPE = {
    lecture: "Lecture",
    reference: "Reference",
    notes: "Notes",
    exam: "Past exam",
  };

  function build(d) {
    var g = {};
    d.forEach(function (r) {
      var p = (g[r.program] = g[r.program] || {}),
        l = (p[r.level] = p[r.level] || {});
      (l[r.course] = l[r.course] || []).push(r);
    });
    list.innerHTML = Object.keys(g)
      .sort()
      .map(function (p, i) {
        return (
          '<details class="program"' +
          (i ? "" : " open") +
          "><summary>" +
          F.esc(F.label(p)) +
          "</summary>" +
          Object.keys(g[p])
            .sort()
            .map(function (l) {
              return (
                '<details class="level"><summary>Level ' +
                F.esc(l) +
                "</summary>" +
                Object.keys(g[p][l])
                  .sort()
                  .map(function (c) {
                    return (
                      '<article class="course" data-course="' +
                      F.esc(c) +
                      '"><h3>' +
                      F.esc(c) +
                      "</h3><ul>" +
                      g[p][l][c]
                        .map(function (r) {
                          var ok = /^(downloads|uploads)\//.test(r.file_url),
                            ext = String(r.file_url)
                              .split(".")
                              .pop()
                              .toUpperCase();
                          return (
                            '<li data-type="' +
                            F.esc(r.type) +
                            '"><a href="' +
                            (ok ? F.esc(r.file_url) : "#") +
                            '">' +
                            F.esc(r.title) +
                            "</a> <span>" +
                            F.esc(TYPE[r.type] || F.label(r.type)) +
                            ", " +
                            F.esc(ext) +
                            "</span></li>"
                          );
                        })
                        .join("") +
                      "</ul></article>"
                    );
                  })
                  .join("") +
                "</details>"
              );
            })
            .join("") +
          "</details>"
        );
      })
      .join("");
  }

  function filter() {
    var s = q.value.trim().toLowerCase(),
      ty = t.value,
      on = !!(s || ty),
      n = 0;
    $$(".course", list).forEach(function (c) {
      var hit = 0;
      $$("li", c).forEach(function (li) {
        var ok =
          (!ty || li.getAttribute("data-type") === ty) &&
          (!s ||
            (c.getAttribute("data-course") + " " + li.textContent)
              .toLowerCase()
              .indexOf(s) > -1);
        li.hidden = !ok;
        if (ok) hit++;
      });
      c.hidden = !hit;
      n += hit;
    });
    $$("details.level,details.program", list).forEach(function (d) {
      var v = !!$(".course:not([hidden])", d);
      d.hidden = !v;
      if (on && v) d.open = true;
    });
    count.textContent = on
      ? n === 1
        ? "1 resource found"
        : n + " resources found"
      : "";
    none.hidden = n > 0;
  }

  form.addEventListener("input", filter);
  form.addEventListener("submit", function (e) {
    e.preventDefault();
  });
  F.api(F.base + "api/resources.php").then(function (r) {
    if (r.ok && Array.isArray(r.data)) build(r.data);
    filter();
  });
})();
