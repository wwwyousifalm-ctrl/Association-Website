/* Admin pages: dashboard counts and the list, add, edit, delete table on each manage page.
   The PHP endpoints must check for an admin session on every request. */
(function () {
  "use strict";
  var F = window.FMCS,
    $ = F.$,
    $$ = F.$$;
  // Table columns per page: field or field:kind (d date, l label, v level)
  var COLS = {
    events: ["title", "starts_at:d", "location"],
    projects: ["title", "tech", "github_url"],
    resources: ["title", "program:l", "level:v", "course", "type:l"],
    news: ["title", "published_at:d"],
    users: [
      "full_name",
      "email",
      "student_id",
      "program:l",
      "level:v",
      "role:l",
    ],
    messages: [
      "name",
      "email",
      "subject:l",
      "body:m",
      "created_at:d",
      "is_read:s",
    ],
  };
  var stats = $("#stats");
  if (stats)
    F.api(F.base + "api/admin/stats.php").then(function (r) {
      if (r.ok)
        $$("[data-stat]", stats).forEach(function (e) {
          e.textContent = r.data[e.getAttribute("data-stat")] || 0;
        });
    });

  var tbl = $("#data-table");
  if (!tbl) return;
  var ep = tbl.getAttribute("data-endpoint"),
    slug = ep.split("/").pop().replace(".php", ""),
    cols = COLS[slug];
  var form = $("#item-form"),
    rows = {};

  function cell(r, c) {
    var p = c.split(":"),
      v = r[p[0]];
    if (v == null) return "";
    return p[1] === "d"
      ? F.fmt(v)
      : p[1] === "l"
        ? F.label(v)
        : p[1] === "v"
          ? "Level " + v
          : p[1] === "s"
            ? Number(v)
              ? "Read"
              : "New"
            : v;
  }
  function actions(r) {
    var b = function (a, cls, txt) {
      return (
        '<button type="button" class="btn ' +
        cls +
        '" data-action="' +
        a +
        '" data-id="' +
        F.esc(r.id) +
        '">' +
        txt +
        "</button> "
      );
    };
    if (slug === "messages")
      return (
        b(
          Number(r.is_read) ? "unread" : "read",
          "btn-secondary",
          Number(r.is_read) ? "Mark unread" : "Mark read",
        ) + b("delete", "btn-danger", "Delete")
      );
    if (slug === "users")
      return (
        b(
          r.role === "admin" ? "demote" : "promote",
          "btn-secondary",
          r.role === "admin" ? "Make student" : "Make admin",
        ) + b("delete", "btn-danger", "Delete account")
      );
    return (
      b("edit", "btn-secondary", "Edit") + b("delete", "btn-danger", "Delete")
    );
  }
  function load() {
    F.api(ep).then(function (r) {
      if (!r.ok) {
        F.notify(r.error || "Could not load the list.", true);
        return;
      }
      rows = {};
      $("tbody", tbl).innerHTML = r.data
        .map(function (x) {
          rows[x.id] = x;
          return (
            "<tr>" +
            cols
              .map(function (c) {
                return (
                  "<td>" +
                  (/:m$/.test(c)
                    ? "<details><summary>Read message</summary><p>" +
                      F.esc(x.body) +
                      "</p></details>"
                    : F.esc(cell(x, c))) +
                  "</td>"
                );
              })
              .join("") +
            "<td>" +
            actions(x) +
            "</td></tr>"
          );
        })
        .join("");
      $(".empty-state").hidden = r.data.length > 0;
      tbl.closest(".table-wrap").hidden = !r.data.length;
    });
  }
  function send(action, id, msg) {
    var fd = new FormData();
    fd.append("action", action);
    fd.append("id", id);
    fd.append("csrf_token", F.csrf);
    F.api(ep, { method: "POST", body: fd }).then(function (r) {
      if (r.ok) {
        F.notify(msg);
        load();
      } else F.notify(r.error || "That did not work. Try again.", true);
    });
  }
  function edit(r) {
    Object.keys(r).forEach(function (k) {
      var f = form.elements[k];
      if (f && f.type !== "file")
        f.value =
          k === "starts_at"
            ? String(r[k]).replace(" ", "T").slice(0, 16)
            : r[k];
    });
    if (form.elements.file) form.elements.file.required = false;
    F.fields(form)[0].focus();
    form.scrollIntoView({ block: "start" });
  }
  tbl.addEventListener("click", function (e) {
    var b = e.target.closest("button[data-action]"),
      r = b && rows[b.getAttribute("data-id")];
    if (!r) return;
    var a = b.getAttribute("data-action"),
      name = r.title || r.full_name || r.name;
    if (a === "edit") edit(r);
    else if (a === "read" || a === "unread")
      send(a, r.id, a === "read" ? "Marked as read." : "Marked as unread.");
    else if (a === "delete") {
      if (confirm('Delete "' + name + '"? This cannot be undone.'))
        send("delete", r.id, "Deleted.");
    } else if (a === "promote") {
      if (
        confirm(
          "Make " +
            name +
            " an admin? Admins can manage all site content and users.",
        )
      )
        send("promote", r.id, name + " is now an admin.");
    } else if (a === "demote") {
      if (confirm("Make " + name + " a student? They will lose admin access."))
        send("demote", r.id, name + " is now a student.");
    }
  });
  if (form) {
    F.submit(
      form,
      function () {
        F.notify("Saved.");
        form.reset();
        load();
      },
      function (fd) {
        fd.append("action", "save");
      },
    );
    form.addEventListener("reset", function () {
      if (form.elements.file) form.elements.file.required = true;
      $$(".field-error", form).forEach(function (e) {
        e.hidden = true;
      });
      $$("[aria-invalid]", form).forEach(function (e) {
        e.removeAttribute("aria-invalid");
      });
    });
  }
  load();
})();
