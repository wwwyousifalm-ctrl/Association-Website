/* Shared behavior for every page: menu, theme, forms, validation, API helper, public content.
   API replies are JSON: { ok: true, data } or { ok: false, error, fields: { fieldName: message } } */
(function () {
  "use strict";
  var F = window.FMCS,
    base = F.base;
  var $ = function (s, r) {
    return (r || document).querySelector(s);
  };
  var $$ = function (s, r) {
    return Array.prototype.slice.call((r || document).querySelectorAll(s));
  };
  F.$ = $;
  F.$$ = $$;
  F.csrf = "";
  F.ready = F.session().catch(function () {
    return { authenticated: false };
  });
  F.esc = function (s) {
    return String(s == null ? "" : s).replace(/[&<>"']/g, function (c) {
      return {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
      }[c];
    });
  };
  F.label = function (v) {
    return String(v || "")
      .replace(/-/g, " ")
      .replace(/\b\w/g, function (c) {
        return c.toUpperCase();
      });
  };
  F.fmt = function (iso, long) {
    var d = new Date(String(iso).replace(" ", "T"));
    return isNaN(d)
      ? ""
      : d.toLocaleString(
          "en-GB",
          long
            ? { dateStyle: "full", timeStyle: "short" }
            : { day: "numeric", month: "short", year: "numeric" },
        );
  };
  F.api = function (url, opts) {
    opts = opts || {};
    opts.credentials = "same-origin";
    opts.headers = { Accept: "application/json", "X-CSRF-Token": F.csrf };
    return fetch(url, opts)
      .then(function (r) {
        return r.json().catch(function () {
          return {
            ok: false,
            error: "The server sent an unexpected reply. Try again later.",
          };
        });
      })
      .catch(function () {
        return {
          ok: false,
          error:
            "Could not reach the server. Check your connection and try again.",
        };
      });
  };
  F.notify = function (msg, isError) {
    var a = $(".form-alert");
    if (!a) {
      a = document.createElement("div");
      var h = $("main .page-header");
      if (h) h.after(a);
      else $("main").prepend(a);
    }
    a.className = "form-alert" + (isError ? " is-error" : "");
    a.setAttribute("role", isError ? "alert" : "status");
    a.textContent = msg;
    a.hidden = false;
    a.scrollIntoView({ block: "nearest" });
  };

  // CSRF token for every form, plus a logged-in header
  F.ready.then(function (s) {
    F.csrf = s.csrf_token || "";
    $$('input[name="csrf_token"]').forEach(function (i) {
      i.value = F.csrf;
    });
    var link = $(".header-actions a.btn:not(.btn-secondary)");
    if (s.authenticated && link && !document.body.getAttribute("data-access")) {
      var admin = s.role === "admin";
      link.textContent = admin ? "Dashboard" : "Settings";
      link.setAttribute(
        "href",
        admin ? "admin/dashboard.html" : "settings.html",
      );
      link.insertAdjacentHTML(
        "afterend",
        ' <button type="button" class="btn btn-secondary" data-action="logout">Log out</button>',
      );
    }
  });
  document.addEventListener("click", function (e) {
    if (!e.target.closest('[data-action="logout"]')) return;
    var fd = new FormData();
    fd.append("csrf_token", F.csrf);
    F.api(base + "api/auth/logout.php", { method: "POST", body: fd }).then(
      function () {
        location.href = base ? "login.html" : "index.html";
      },
    );
  });

  // Mobile menu, current page, theme
  var nt = $(".nav-toggle");
  if (nt) {
    nt.addEventListener("click", function () {
      nt.setAttribute(
        "aria-expanded",
        nt.getAttribute("aria-expanded") !== "true",
      );
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && nt.getAttribute("aria-expanded") === "true") {
        nt.setAttribute("aria-expanded", "false");
        nt.focus();
      }
    });
  }
  var cur = location.pathname.split("/").pop() || "index.html";
  $$(".site-nav a").forEach(function (a) {
    if (a.getAttribute("href") === cur) a.setAttribute("aria-current", "page");
  });
  function isDark() {
    var t = document.documentElement.getAttribute("data-theme");
    return t
      ? t === "dark"
      : matchMedia("(prefers-color-scheme: dark)").matches;
  }
  function syncTheme() {
    $$(".theme-toggle").forEach(function (b) {
      b.setAttribute("aria-pressed", isDark());
    });
  }
  F.setTheme = function (t) {
    var r = document.documentElement;
    try {
      if (t === "light" || t === "dark") {
        r.setAttribute("data-theme", t);
        localStorage.setItem("fmcs-theme", t);
      } else {
        r.removeAttribute("data-theme");
        localStorage.removeItem("fmcs-theme");
      }
    } catch (e) {}
    syncTheme();
  };
  $$(".theme-toggle").forEach(function (b) {
    b.addEventListener("click", function () {
      F.setTheme(isDark() ? "light" : "dark");
    });
  });
  syncTheme();
  $$("[data-toggle-password]").forEach(function (c) {
    c.addEventListener("change", function () {
      var i = document.getElementById(c.getAttribute("data-toggle-password"));
      if (i) i.type = c.checked ? "text" : "password";
    });
  });

  // Validation: one rule set for every form, messages shown in each field's error slot
  function nameOf(f) {
    var l = $('label[for="' + f.id + '"]');
    return l
      ? l.textContent
          .replace(/\s*\(required\)/, "")
          .trim()
          .toLowerCase()
      : "this field";
  }
  function check(f, form) {
    var v = f.value.trim(),
      n = f.name;
    if (f.type === "checkbox")
      return f.required && !f.checked
        ? "Accept the privacy policy to continue."
        : "";
    if (f.type === "file") {
      var x = f.files[0];
      if (!x) return f.required ? "Choose a file to upload." : "";
      return x.type !== "application/pdf"
        ? "Upload a PDF file."
        : x.size > 5242880
          ? "The file is larger than 5 MB."
          : "";
    }
    if (!v)
      return f.required
        ? (f.tagName === "SELECT" ? "Choose " : "Enter ") + nameOf(f) + "."
        : "";
    if (f.type === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v))
      return "Enter a valid email address, like name@example.com.";
    if (f.minLength > 0 && v.length < f.minLength)
      return "Use at least " + f.minLength + " characters.";
    if (f.type === "tel" && !/^\+?[0-9 ()-]{7,20}$/.test(v))
      return "Enter a valid phone number, for example +249 9X XXX XXXX.";
    if (n === "student_id" && !/^\d{4,20}$/.test(v))
      return "Student ID must be 4 to 20 digits.";
    if (
      ((n === "password" && form.getAttribute("id") === "register-form") ||
        n === "new_password") &&
      !(/[A-Za-z]/.test(v) && /\d/.test(v))
    )
      return "Use at least one letter and one number.";
    if (n === "password_confirm" && v !== form.elements.password.value)
      return "The passwords do not match.";
    if (n === "new_password_confirm" && v !== form.elements.new_password.value)
      return "The passwords do not match.";
    if (
      f.type === "url" &&
      !/^https:\/\/github\.com\/[\w.-]+\/[\w.-]+\/?$/.test(v)
    )
      return "Enter a GitHub repository link, like https://github.com/name/repo.";
    return "";
  }
  function show(f, msg) {
    var e = document.getElementById(f.id + "-error");
    if (!e) return;
    e.textContent = msg;
    e.hidden = !msg;
    if (msg) {
      f.setAttribute("aria-invalid", "true");
      var d = (f.getAttribute("aria-describedby") || "")
        .split(" ")
        .filter(Boolean);
      if (d.indexOf(e.id) < 0) d.push(e.id);
      f.setAttribute("aria-describedby", d.join(" "));
    } else f.removeAttribute("aria-invalid");
  }
  F.fields = function (form) {
    return Array.prototype.slice.call(form.elements).filter(function (f) {
      return (
        f.id &&
        !f.disabled &&
        ["hidden", "submit", "reset", "button"].indexOf(f.type) < 0
      );
    });
  };
  F.validate = function (form) {
    var first = null;
    F.fields(form).forEach(function (f) {
      var m = check(f, form);
      show(f, m);
      if (m && !first) first = f;
    });
    return first;
  };
  F.submit = function (form, done, prep) {
    form.addEventListener("input", function (e) {
      if (e.target.getAttribute("aria-invalid"))
        show(e.target, check(e.target, form));
    });
    form.addEventListener("submit", function (ev) {
      ev.preventDefault();
      var bad = F.validate(form);
      if (bad) {
        bad.focus();
        return;
      }
      var b = form.querySelector('[type="submit"]'),
        label = b.textContent;
      b.disabled = true;
      b.textContent = "Please wait…";
      var fd = new FormData(form);
      if (prep) prep(fd);
      F.api(form.action, { method: "POST", body: fd }).then(function (r) {
        b.disabled = false;
        b.textContent = label;
        if (r.ok) {
          done(r.data || {}, form);
          return;
        }
        var first = null;
        Object.keys(r.fields || {}).forEach(function (k) {
          var f = form.elements[k];
          if (f && f.id) {
            show(f, r.fields[k]);
            first = first || f;
          }
        });
        if (first) first.focus();
        F.notify(
          r.error || "Check the highlighted fields and try again.",
          true,
        );
      });
    });
  };

  // Wire each form to what happens after it succeeds
  var next =
    (location.search.match(/[?&]next=([\w-]+\.html)(&|$)/) || [])[1] ||
    "index.html";
  function go(id, fn) {
    var f = document.getElementById(id);
    if (f) F.submit(f, fn);
  }
  go("login-form", function (d) {
    location.href = d.role === "admin" ? "admin/dashboard.html" : next;
  });
  go("admin-login-form", function () {
    location.href = "dashboard.html";
  });
  go("register-form", function () {
    location.href = "login.html?registered=1";
  });
  go("contact-form", function (d, f) {
    f.reset();
    F.notify("Message sent. We reply within three working days.");
  });
  go("profile-form", function () {
    F.notify("Profile saved.");
  });
  go("password-form", function (d, f) {
    f.reset();
    F.notify("Password changed.");
  });
  go("prefs-form", function (d, f) {
    F.setTheme(f.elements.theme.value);
    F.notify("Preferences saved.");
  });
  if ($("#login-form") && /[?&]registered=1/.test(location.search))
    F.notify("Account created. Log in to continue.");
  if ($("#login-form") && /[?&]reset=1/.test(location.search))
    F.notify("Password changed. Log in with your new password.");
  go("forgot-form", function (d, f) {
    f.reset();
    F.notify(
      "If that email is registered, a reset link is on its way. It works for one hour.",
    );
  });
  go("reset-form", function () {
    location.href = "login.html?reset=1";
  });
  var rf = document.getElementById("reset-form");
  if (rf) {
    rf.elements.token.value =
      (location.search.match(/[?&]token=([a-f0-9]{64})/) || [])[1] || "";
    if (!rf.elements.token.value)
      F.notify("This reset link is not valid. Ask for a new one.", true);
  }

  // Settings: fill the forms from the logged-in user
  var pf = document.getElementById("profile-form"),
    pr = document.getElementById("prefs-form");
  if (pf)
    F.ready.then(function (s) {
      var u = s.user || {};
      ["full_name", "email", "phone", "program", "level"].forEach(function (k) {
        if (pf.elements[k] && u[k] != null) pf.elements[k].value = u[k];
      });
      if (u.language) pr.elements.language.value = u.language;
      if ("notify_email" in u)
        pr.elements.notify_email.checked = !!Number(u.notify_email);
      var t = document.documentElement.getAttribute("data-theme");
      pr.elements.theme.value = t || "system";
    });
  var ts = $("#theme");
  if (ts)
    ts.addEventListener("change", function () {
      F.setTheme(ts.value);
    });

  // Public content from the database; the HTML on the page stays as the fallback if the API is unavailable
  function safe(u) {
    return /^https:\/\/github\.com\//.test(u) ? F.esc(u) : "";
  }
  function fill(id, items, none) {
    var s = $('[aria-labelledby="' + id + '"]');
    if (!s) return;
    $$(".event,.empty-state", s).forEach(function (e) {
      e.remove();
    });
    s.insertAdjacentHTML(
      "beforeend",
      items.length ? items.join("") : '<p class="empty-state">' + none + "</p>",
    );
  }
  var R = {
    events: function (d) {
      var now = Date.now(),
        up = [],
        past = [];
      d.forEach(function (e) {
        (new Date(e.starts_at) >= now ? up : past).push(e);
      });
      var card = function (e) {
        return (
          '<article class="event"><h3>' +
          F.esc(e.title) +
          '</h3><p class="event-meta"><time datetime="' +
          F.esc(e.starts_at) +
          '">' +
          F.fmt(e.starts_at, 1) +
          "</time>, " +
          F.esc(e.location) +
          "</p><p>" +
          F.esc(e.description) +
          "</p></article>"
        );
      };
      fill(
        "upcoming-title",
        up
          .sort(function (a, b) {
            return new Date(a.starts_at) - new Date(b.starts_at);
          })
          .map(card),
        "No upcoming events right now. Check back soon.",
      );
      fill(
        "past-title",
        past
          .sort(function (a, b) {
            return new Date(b.starts_at) - new Date(a.starts_at);
          })
          .map(card),
        "No past events yet.",
      );
    },
    projects: function (d) {
      $(".card-grid").innerHTML =
        d
          .map(function (p) {
            return (
              '<article class="card"><h2>' +
              F.esc(p.title) +
              "</h2><p>" +
              F.esc(p.description) +
              '</p><p class="tech">' +
              F.esc(p.tech) +
              "</p>" +
              (safe(p.github_url)
                ? '<p><a href="' +
                  safe(p.github_url) +
                  '" target="_blank" rel="noopener noreferrer">View on GitHub<span class="visually-hidden"> (opens in a new tab)</span></a></p>'
                : "") +
              "</article>"
            );
          })
          .join("") || '<p class="empty-state">No projects yet.</p>';
    },
    news: function (d) {
      $$(".post,.empty-state").forEach(function (e) {
        e.remove();
      });
      $(".page-header").insertAdjacentHTML(
        "afterend",
        d
          .map(function (n) {
            return (
              '<article class="post"><h2>' +
              F.esc(n.title) +
              '</h2><p><time datetime="' +
              F.esc(n.published_at) +
              '">' +
              F.fmt(n.published_at) +
              "</time></p><p>" +
              F.esc(n.body) +
              "</p></article>"
            );
          })
          .join("") || '<p class="empty-state">No announcements yet.</p>',
      );
    },
  };
  var feed = cur.replace(".html", "");
  if (R[feed])
    F.api(base + "api/" + feed + ".php").then(function (r) {
      if (r.ok && Array.isArray(r.data)) R[feed](r.data);
    });
})();
