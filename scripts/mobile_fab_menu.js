(function () {
  var STYLE_ID = "iubo-fab-menu-style";
  if (!document.getElementById(STYLE_ID)) {
    var style = document.createElement("style");
    style.id = STYLE_ID;
    style.textContent =
      ".iubo-fab-wrap{position:fixed;right:16px;top:16px;z-index:12000}" +
      ".iubo-fab-main{width:56px;height:56px;border-radius:9999px;border:0;background:#5c068c;color:#fff;box-shadow:0 10px 24px rgba(0,0,0,.25);display:flex;align-items:center;justify-content:center;cursor:pointer}" +
      ".iubo-fab-main:active{transform:translateY(1px)}" +
      ".iubo-fab-items{position:absolute;right:0;top:68px;display:none;flex-direction:column;gap:8px}" +
      ".iubo-fab-wrap.iubo-fab-bottom .iubo-fab-items{top:auto;bottom:68px}" +
      ".iubo-fab-wrap.open .iubo-fab-items{display:flex}" +
      ".iubo-fab-item{width:46px;height:46px;border:0;border-radius:9999px;background:#5c068c;color:#fff;padding:0;display:flex;align-items:center;justify-content:center;box-shadow:0 8px 18px rgba(0,0,0,.2);cursor:pointer;text-decoration:none}" +
      ".iubo-fab-item span.material-symbols-outlined{font-size:20px}" +
      "@media (min-width:768px){.iubo-fab-wrap{display:none!important}}";
    document.head.appendChild(style);
  }

  function createItem(action) {
    var el = action.href ? document.createElement("a") : document.createElement("button");
    if (action.href) el.href = action.href;
    if (!action.href) el.type = "button";
    el.className = "iubo-fab-item";
    el.setAttribute("aria-label", action.label || "Accion");
    el.setAttribute("title", action.label || "Accion");
    if (action.textIcon) {
      el.innerHTML = '<span style="font:700 14px/1 sans-serif;letter-spacing:.4px">' + action.textIcon + "</span>";
    } else {
      el.innerHTML = '<span class="material-symbols-outlined">' + (action.icon || "chevron_right") + "</span>";
    }
    if (typeof action.onClick === "function") {
      el.addEventListener("click", function (e) {
        e.preventDefault();
        action.onClick();
      });
    }
    return el;
  }

  window.initIuboFabMenu = function (config) {
    if (!config || !Array.isArray(config.actions) || config.actions.length === 0) return;
    var wrap = document.createElement("div");
    wrap.className = "iubo-fab-wrap";
    if (config.position === "bottom-right") {
      wrap.style.top = "auto";
      wrap.style.bottom = "16px";
      wrap.style.right = "16px";
      wrap.classList.add("iubo-fab-bottom");
    }

    var items = document.createElement("div");
    items.className = "iubo-fab-items";
    config.actions.forEach(function (a) { items.appendChild(createItem(a)); });

    var main = document.createElement("button");
    main.type = "button";
    main.className = "iubo-fab-main";
    main.setAttribute("aria-label", "Menu rapido");
    main.innerHTML = '<span class="material-symbols-outlined">menu</span>';

    function toggleMenu(e) {
      if (e) {
        e.preventDefault();
        e.stopPropagation();
      }
      wrap.classList.toggle("open");
      main.innerHTML = wrap.classList.contains("open")
        ? '<span class="material-symbols-outlined">close</span>'
        : '<span class="material-symbols-outlined">menu</span>';
    }
    main.addEventListener("click", toggleMenu);
    main.addEventListener("touchstart", toggleMenu, { passive: false });

    document.addEventListener("click", function (e) {
      if (!wrap.contains(e.target)) {
        wrap.classList.remove("open");
        main.innerHTML = '<span class="material-symbols-outlined">menu</span>';
      }
    });
    document.addEventListener("touchstart", function (e) {
      if (!wrap.contains(e.target)) {
        wrap.classList.remove("open");
        main.innerHTML = '<span class="material-symbols-outlined">menu</span>';
      }
    }, { passive: true });

    wrap.appendChild(items);
    wrap.appendChild(main);
    document.body.appendChild(wrap);
  };
})();
