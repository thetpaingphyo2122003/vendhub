const eyeOpen =
    '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>';
const eyeOff =
    '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 10.6A2.5 2.5 0 0 0 12 14.5a2.5 2.5 0 0 0 2.2-1.3"/><path d="M9.9 5.2A10.6 10.6 0 0 1 12 5c6.5 0 10 7 10 7a18.4 18.4 0 0 1-4.2 4.8"/><path d="M6.1 6.1A17.8 17.8 0 0 0 2 12s3.5 6 10 6a10.5 10.5 0 0 0 4.2-.9"/></svg>';

const rules = {
    login: {
        username(value) {
            if (value === "") return "Username is required.";
            if (!/^[a-zA-Z0-9_-]{3,32}$/.test(value)) return "Username must be 3-32 letters, numbers, hyphens, or underscores.";
            return "";
        },
        password(value) {
            if (value === "") return "Password is required.";
            return "";
        },
    },
    register: {
        name(value) {
            if (value === "") return "Name is required.";
            if (value.length > 100) return "Name must be 100 characters or fewer.";
            return "";
        },
        username(value) {
            if (value === "") return "Username is required.";
            if (!/^[a-zA-Z0-9_-]{3,32}$/.test(value)) return "Username must be 3-32 letters, numbers, hyphens, or underscores.";
            return "";
        },
        password(value) {
            return strongPasswordError(value);
        },
        password_confirmation(value, form) {
            if (value === "") return "Please confirm your password.";
            if (value !== valueOf(form, "password")) return "Password confirmation does not match.";
            return "";
        },
    },
    product: {
        name(value) {
            if (value === "") return "Name is required.";
            if (value.length > 150) return "Name must be 150 characters or fewer.";
            return "";
        },
        price(value) {
            if (value === "") return "Price is required.";
            if (!/^\d+(\.\d{1,3})?$/.test(value) || Number(value) <= 0) {
                return "Price must be greater than zero.";
            }
            return "";
        },
        quantity_available(value) {
            if (value === "") return "Quantity is required.";
            if (!/^\d+$/.test(value)) return "Quantity must be zero or greater.";
            return "";
        },
        reorder_point(value) {
            if (value === "") return "Reorder point is required.";
            if (!/^\d+$/.test(value)) return "Reorder point must be zero or greater.";
            return "";
        },
        category(value) {
            if (value === "") return "Category is required.";
            return "";
        },
    },
    category: {
        name(value) {
            if (value === "") return "Name is required.";
            if (value.length > 50) return "Name must be 50 characters or fewer.";
            return "";
        },
    },
    purchase: {
        quantity(value, form) {
            const stock = Number(form.getAttribute("data-stock") || "0");
            if (value === "") return "Quantity is required.";
            if (!/^\d+$/.test(value)) return "Quantity must be a whole number.";
            if (Number(value) < 1) return "Quantity must be at least 1.";
            if (Number(value) > stock) return "Not enough stock.";
            return "";
        },
    },
    profile: {
        name(value) {
            if (value === "") return "Name is required.";
            if (value.length > 100) return "Name must be 100 characters or fewer.";
            return "";
        },
        username(value) {
            if (value === "") return "Username is required.";
            if (!/^[a-zA-Z0-9_-]{3,32}$/.test(value)) return "Username must be 3-32 letters, numbers, hyphens, or underscores.";
            return "";
        },
        current_password(value, form) {
            const changing = valueOf(form, "password") !== "" || valueOf(form, "password_confirmation") !== "" || value !== "";
            if (changing && value === "") return "Current password is required.";
            return "";
        },
        password(value, form) {
            const changing = valueOf(form, "current_password") !== "" || value !== "" || valueOf(form, "password_confirmation") !== "";
            if (!changing) return "";
            return strongPasswordError(value);
        },
        password_confirmation(value, form) {
            const changing = valueOf(form, "current_password") !== "" || valueOf(form, "password") !== "" || value !== "";
            if (!changing) return "";
            if (value === "") return "Please confirm your password.";
            if (value !== valueOf(form, "password")) return "Password confirmation does not match.";
            return "";
        },
    },
};

const touchedByForm = new WeakMap();
const searchTimers = new WeakMap();
let softNavController = null;
let softNavToken = 0;

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
}

function safeHref(value) {
    const href = String(value ?? "").trim();
    if (href === "" || href.startsWith("/") || href.startsWith("#")) {
        return href || "#";
    }
    try {
        const url = new URL(href, window.location.origin);
        if (url.origin === window.location.origin && (url.protocol === "http:" || url.protocol === "https:")) {
            return url.pathname + url.search + url.hash;
        }
    } catch (_) {
        // ignore invalid URLs
    }
    return "#";
}

initPasswordToggles(document);
wireDelegatedEvents();
mountConfirmDialog();
enhanceSelects(document);
initPurchasesPage(document);
initProfilePage(document);
initProductImageSkeletons(document);
initFlashToasts(document);
initPurchaseSuccess(document);
initAdminNotifications(document);
initAdminSidebar(document);
initStoreNav(document);

if (document.body.classList.contains("store-app")) {
    enableSoftNavigation();
}

function initStoreNav(root) {
    const drawer = root.querySelector("[data-store-drawer]");
    const backdrop = root.querySelector("[data-store-drawer-backdrop]");
    const toggle = root.querySelector("[data-store-menu-toggle]");
    if (!drawer || !toggle || !document.body.classList.contains("store-app")) return;
    if (toggle.dataset.bound === "1") return;
    toggle.dataset.bound = "1";

    const closeBtn = root.querySelector("[data-store-menu-close]");
    const mq = window.matchMedia("(max-width: 820px)");

    const setOpen = (open) => {
        const shouldOpen = open && mq.matches;
        document.body.classList.toggle("store-nav-open", shouldOpen);
        toggle.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
        toggle.setAttribute("aria-label", shouldOpen ? "Close menu" : "Open menu");
        if (backdrop) backdrop.hidden = !shouldOpen;
    };

    const close = () => setOpen(false);
    const toggleMenu = () => setOpen(!document.body.classList.contains("store-nav-open"));

    toggle.addEventListener("click", toggleMenu);
    closeBtn?.addEventListener("click", close);
    backdrop?.addEventListener("click", close);

    drawer.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            if (mq.matches) close();
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && document.body.classList.contains("store-nav-open")) {
            close();
        }
    });

    const onViewportChange = () => {
        if (!mq.matches) close();
    };
    if (typeof mq.addEventListener === "function") mq.addEventListener("change", onViewportChange);
    else mq.addListener(onViewportChange);

    close();
}

function initAdminSidebar(root) {
    const sidebar = root.querySelector("[data-admin-sidebar]");
    const backdrop = root.querySelector("[data-admin-sidebar-backdrop]");
    const toggle = root.querySelector("[data-admin-menu-toggle]");
    if (!sidebar || !toggle || !document.body.classList.contains("admin")) return;
    if (toggle.dataset.bound === "1") return;
    toggle.dataset.bound = "1";

    const closeBtn = root.querySelector("[data-admin-menu-close]");
    const mq = window.matchMedia("(max-width: 900px)");
    const storageKey = "vendhub_admin_sidebar_open";

    const readStored = () => {
        try {
            const value = localStorage.getItem(storageKey);
            if (value === "1") return true;
            if (value === "0") return false;
        } catch (_) {}
        return null;
    };

    const writeStored = (open) => {
        try {
            localStorage.setItem(storageKey, open ? "1" : "0");
        } catch (_) {}
    };

    const setOpen = (open, persist = true) => {
        document.body.classList.toggle("admin-sidebar-open", open);
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
        toggle.setAttribute("aria-label", open ? "Close menu" : "Open menu");
        if (backdrop) backdrop.hidden = !(open && mq.matches);
        if (persist) writeStored(open);
    };

    const close = () => setOpen(false);
    const toggleMenu = () => setOpen(!document.body.classList.contains("admin-sidebar-open"));

    toggle.addEventListener("click", toggleMenu);
    closeBtn?.addEventListener("click", close);
    backdrop?.addEventListener("click", close);

    sidebar.querySelectorAll("a").forEach((link) => {
        link.addEventListener("click", () => {
            if (mq.matches) close();
        });
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && document.body.classList.contains("admin-sidebar-open")) {
            close();
        }
    });

    const initialStored = readStored();
    const initialOpen = initialStored === null ? !mq.matches : initialStored;
    setOpen(initialOpen, false);

    const onViewportChange = () => {
        if (mq.matches) {
            setOpen(false, false);
            return;
        }
        const stored = readStored();
        setOpen(stored === null ? true : stored, false);
    };
    if (typeof mq.addEventListener === "function") mq.addEventListener("change", onViewportChange);
    else mq.addListener(onViewportChange);
}

function initAdminNotifications(root) {
    const wrap = root.querySelector("[data-admin-notify]");
    if (!wrap || wrap.dataset.bound === "1") return;
    wrap.dataset.bound = "1";

    const url = wrap.getAttribute("data-notify-url");
    const toggle = wrap.querySelector("[data-notify-toggle]");
    const panel = wrap.querySelector("[data-notify-panel]");
    const list = wrap.querySelector("[data-notify-list]");
    const badge = wrap.querySelector("[data-notify-badge]");
    if (!url || !toggle || !panel || !list || !badge) return;

    const seenKey = "vendhub_admin_seen_purchase_id";
    let timer = null;
    let toastHost = null;

    const ensureToastHost = () => {
        if (toastHost) return toastHost;
        toastHost = document.createElement("div");
        toastHost.className = "admin-live-toast";
        toastHost.setAttribute("aria-live", "polite");
        document.body.appendChild(toastHost);
        return toastHost;
    };

    const pushToast = (item) => {
        const host = ensureToastHost();
        const node = document.createElement("div");
        node.className = "admin-live-toast__item";
        node.innerHTML = `<strong>${escapeHtml(item.title || "Update")}</strong><span>${escapeHtml(item.body || "")}</span>`;
        host.appendChild(node);
        window.setTimeout(() => {
            node.classList.add("is-leaving");
            window.setTimeout(() => node.remove(), 200);
        }, 4200);
    };

    const render = (payload) => {
        const items = Array.isArray(payload.items) ? payload.items : [];
        const count = items.length;
        if (count > 0) {
            badge.hidden = false;
            badge.textContent = count > 9 ? "9+" : String(count);
        } else {
            badge.hidden = true;
            badge.textContent = "0";
        }

        if (items.length === 0) {
            list.innerHTML = '<p class="admin-notify-empty">No alerts right now.</p>';
            return;
        }

        list.innerHTML = items
            .map((item) => {
                const type = escapeHtml(String(item.type || "info").replace(/[^a-z0-9_-]/gi, ""));
                const href = escapeHtml(safeHref(item.href || "#"));
                return `<a class="admin-notify-item is-${type}" href="${href}">
                    <strong>${escapeHtml(item.title || "Alert")}</strong>
                    <p>${escapeHtml(item.body || "")}</p>
                </a>`;
            })
            .join("");
    };

    const poll = async () => {
        try {
            const response = await fetch(url, {
                headers: { Accept: "application/json" },
                credentials: "same-origin",
            });
            if (!response.ok) return;
            const payload = await response.json();
            render(payload);

            const latestId = Number(payload.latest_purchase_id || 0);
            const seenId = Number(localStorage.getItem(seenKey) || "0");
            if (latestId > seenId) {
                const newest = (payload.items || []).find((item) => item.type === "purchase");
                if (newest && seenId > 0) {
                    pushToast(newest);
                }
                localStorage.setItem(seenKey, String(latestId));
            } else if (seenId === 0 && latestId > 0) {
                localStorage.setItem(seenKey, String(latestId));
            }
        } catch (_error) {
            // Keep polling quietly if the network blips.
        }
    };

    const setOpen = (open) => {
        panel.hidden = !open;
        wrap.classList.toggle("is-open", open);
        toggle.setAttribute("aria-expanded", open ? "true" : "false");
    };

    toggle.addEventListener("click", (event) => {
        event.stopPropagation();
        setOpen(panel.hidden);
    });

    document.addEventListener("click", (event) => {
        if (!wrap.contains(event.target)) setOpen(false);
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") setOpen(false);
    });

    poll();
    timer = window.setInterval(poll, 8000);
    window.addEventListener("beforeunload", () => {
        if (timer) window.clearInterval(timer);
    });
}

function initFlashToasts(root) {
    root.querySelectorAll("[data-flash-toast]").forEach((toast) => {
        if (toast.dataset.bound === "1") return;
        toast.dataset.bound = "1";
        window.setTimeout(() => {
            toast.classList.add("is-leaving");
            window.setTimeout(() => toast.remove(), 220);
        }, 2800);
    });
}

function initPurchaseSuccess(root) {
    const dialog = root.querySelector("[data-purchase-success]");
    if (!dialog || dialog.dataset.bound === "1") return;
    dialog.dataset.bound = "1";

    document.body.classList.add("ui-confirm-open");

    const close = () => {
        dialog.hidden = true;
        document.body.classList.remove("ui-confirm-open");
        document.removeEventListener("keydown", onKey);
    };

    const onKey = (event) => {
        if (event.key === "Escape") close();
    };

    dialog.addEventListener("click", (event) => {
        if (event.target.closest("[data-close-purchase-success]")) close();
    });

    document.addEventListener("keydown", onKey);
}

function initPasswordToggles(root) {
    root.querySelectorAll("[data-toggle-password]").forEach((button) => {
        if (button.dataset.bound === "1") return;
        button.dataset.bound = "1";
        button.type = "button";
        button.innerHTML = eyeOpen;
        button.setAttribute("aria-label", "Show password");
        button.setAttribute("aria-pressed", "false");

        button.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();

            const input = document.getElementById(button.getAttribute("data-toggle-password") || "");
            if (!input) return;

            const show = input.type === "password";
            input.type = show ? "text" : "password";
            button.innerHTML = show ? eyeOff : eyeOpen;
            button.setAttribute("aria-label", show ? "Hide password" : "Show password");
            button.setAttribute("aria-pressed", show ? "true" : "false");
            input.focus({ preventScroll: true });
        });
    });
}

function wireDelegatedEvents() {
    document.addEventListener("click", (event) => {
        const backBtn = event.target.closest("[data-history-back]");
        if (backBtn) {
            event.preventDefault();
            if (window.history.length > 1) window.history.back();
            else window.location.href = "/products";
            return;
        }

        const qtyBtn = event.target.closest("[data-qty]");
        if (!qtyBtn) return;
        const stepper = qtyBtn.closest(".qty-stepper");
        const input = stepper && stepper.querySelector('input[name="quantity"]');
        if (!input) return;

        const delta = Number(qtyBtn.getAttribute("data-qty") || "0");
        const min = Number(input.min || "1");
        const max = Number(input.max || "999");
        const next = Math.min(max, Math.max(min, Number(input.value || min) + delta));
        input.value = String(next);
        input.dispatchEvent(new Event("input", { bubbles: true }));
    });

    document.addEventListener("input", (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement)) return;

        if (input.matches('.shop-search input[name="q"]')) {
            scheduleShopSearch(input);
        }

        const form = input.closest("form[data-validate]");
        if (!form || !input.name) return;
        const schema = rules[form.getAttribute("data-validate") || ""];
        if (!schema || !schema[input.name]) return;

        const touched = getTouched(form);
        if (!touched.has(input.name) && valueOf(form, input.name) === "") {
            clearField(form, input.name);
            return;
        }
        touched.add(input.name);
        validateField(form, schema, input.name, true);
        if (input.name === "password" && schema.password_confirmation && touched.has("password_confirmation")) {
            validateField(form, schema, "password_confirmation", true);
        }
    });

    document.addEventListener("keydown", (event) => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || !input.matches('.shop-search input[name="q"]')) {
            return;
        }

        const form = input.closest("form");
        if (!form) return;

        if (event.key === "Enter") {
            window.clearTimeout(searchTimers.get(input) || 0);
            return;
        }

        if (event.key === "Escape" && input.value !== "") {
            event.preventDefault();
            window.clearTimeout(searchTimers.get(input) || 0);
            input.value = "";
            scheduleShopSearch(input, 0);
        }
    });

    document.addEventListener(
        "blur",
        (event) => {
            const input = event.target;
            if (!(input instanceof HTMLElement) || !input.name) return;

            if (input instanceof HTMLInputElement && input.matches('.shop-search input[name="q"]')) {
                const trimmed = shopSearchTrimmed(input.value);
                if (input.value !== trimmed) {
                    input.value = trimmed;
                }
                scheduleShopSearch(input, 0);
            }

            const form = input.closest("form[data-validate]");
            if (!form) return;
            const schema = rules[form.getAttribute("data-validate") || ""];
            if (!schema || !schema[input.name]) return;

            const touched = getTouched(form);
            touched.add(input.name);
            validateField(form, schema, input.name, true);
            if (input.name === "password" && schema.password_confirmation && touched.has("password_confirmation")) {
                validateField(form, schema, "password_confirmation", true);
            }
        },
        true,
    );

    document.addEventListener(
        "submit",
        (event) => {
            const form = event.target;
            if (!(form instanceof HTMLFormElement)) return;

            const schemaKey = form.getAttribute("data-validate");
            if (schemaKey && rules[schemaKey]) {
                const schema = rules[schemaKey];
                const touched = getTouched(form);
                Object.keys(schema).forEach((name) => touched.add(name));
                const errors = validateForm(form, schema, true);
                if (Object.keys(errors).length > 0) {
                    event.preventDefault();
                    const first = form.querySelector(".field.is-invalid input, .field.is-invalid [name], .qty-stepper input");
                    if (first) first.focus();
                    return;
                }
            }

            const confirmMessage = form.getAttribute("data-confirm");
            if (!confirmMessage || form.dataset.confirmed === "1") {
                delete form.dataset.confirmed;
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            let confirmItems = [];
            const rawItems = form.getAttribute("data-confirm-items");
            if (rawItems) {
                try {
                    const parsed = JSON.parse(rawItems);
                    if (Array.isArray(parsed)) confirmItems = parsed;
                } catch (_) {
                    confirmItems = [];
                }
            }

            askConfirm(confirmMessage, { items: confirmItems }).then((ok) => {
                if (!ok) return;
                form.dataset.confirmed = "1";
                if (typeof form.requestSubmit === "function") form.requestSubmit();
                else form.submit();
            });
        },
        true,
    );
}

function enableSoftNavigation() {
    ensureNavProgress();

    document.addEventListener("click", (event) => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest("a[href]");
        if (!link || link.target === "_blank" || link.hasAttribute("download")) return;

        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        if (url.hash && url.pathname === window.location.pathname && url.search === window.location.search) return;
        if (!isSoftNavPath(url.pathname)) return;
        if (url.href === window.location.href) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        softNavigate(url.href, true);
    });

    document.addEventListener("submit", (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) return;
        if ((form.getAttribute("method") || "get").toLowerCase() !== "get") return;
        if (!form.closest(".store-content") && !form.classList.contains("shop-toolbar")) return;

        const action = form.getAttribute("action") || window.location.pathname;
        const url = new URL(action, window.location.href);
        if (!isSoftNavPath(url.pathname)) return;

        event.preventDefault();
        const params = new URLSearchParams(new FormData(form));
        if (params.has("q")) {
            const trimmed = shopSearchTrimmed(params.get("q") || "");
            if (trimmed === "") params.delete("q");
            else params.set("q", trimmed);
        }
        url.search = params.toString();
        softNavigate(url.href, true);
    });

    window.addEventListener("popstate", () => {
        if (!isSoftNavPath(window.location.pathname)) {
            window.location.reload();
            return;
        }
        softNavigate(window.location.href, false);
    });

    document.querySelectorAll(".store-nav a[href], .store-brand[href]").forEach((link) => {
        link.addEventListener("pointerenter", () => prefetch(link.href), { once: true });
    });
}

function isSoftNavPath(pathname) {
    return (
        pathname === "/products" ||
        pathname.startsWith("/products/") ||
        pathname === "/purchases" ||
        pathname === "/profile"
    );
}

function shopSearchTrimmed(value) {
    return String(value ?? "").trim().replace(/\s+/g, " ");
}

function currentShopSearchQuery() {
    return shopSearchTrimmed(new URL(window.location.href).searchParams.get("q") || "");
}

function scheduleShopSearch(input, delay = 320) {
    const form = input.closest("form");
    if (!form) return;

    window.clearTimeout(searchTimers.get(input) || 0);
    searchTimers.set(
        input,
        window.setTimeout(() => {
            const next = shopSearchTrimmed(input.value);
            if (next === currentShopSearchQuery()) {
                return;
            }
            if (typeof form.requestSubmit === "function") form.requestSubmit();
            else form.submit();
        }, delay),
    );
}

function captureShopSearchState() {
    const input = document.querySelector('.shop-search input[name="q"]');
    if (!(input instanceof HTMLInputElement)) return null;

    return {
        value: input.value,
        focused: document.activeElement === input,
        start: input.selectionStart,
        end: input.selectionEnd,
    };
}

function restoreShopSearchState(state) {
    if (!state) return;

    const input = document.querySelector('.shop-search input[name="q"]');
    if (!(input instanceof HTMLInputElement)) return;

    if (input.value !== state.value) {
        input.value = state.value;
    }

    if (!state.focused) return;

    input.focus({ preventScroll: true });
    try {
        const start = typeof state.start === "number" ? state.start : input.value.length;
        const end = typeof state.end === "number" ? state.end : start;
        input.setSelectionRange(start, end);
    } catch (_) {
        // Some input types may not support selection ranges.
    }

    if (shopSearchTrimmed(state.value) !== currentShopSearchQuery()) {
        scheduleShopSearch(input, 280);
    }
}

async function softNavigate(href, push) {
    if (softNavController) {
        softNavController.abort();
    }

    const controller = new AbortController();
    softNavController = controller;
    const token = ++softNavToken;

    document.body.classList.add("is-navigating");

    try {
        const url = new URL(href, window.location.href);
        if (url.pathname === "/products") {
            showProductsSkeleton();
        }

        const response = await fetch(href, {
            headers: { Accept: "text/html", "X-Soft-Nav": "1" },
            credentials: "same-origin",
            signal: controller.signal,
        });

        if (token !== softNavToken) return;

        if (!response.ok) {
            window.location.href = href;
            return;
        }

        const html = await response.text();
        if (token !== softNavToken) return;

        const doc = new DOMParser().parseFromString(html, "text/html");

        if (!doc.body.classList.contains("store-app")) {
            window.location.href = href;
            return;
        }

        const nextMain = doc.querySelector(".store-content");
        const currentMain = document.querySelector(".store-content");
        if (!nextMain || !currentMain) {
            window.location.href = href;
            return;
        }

        // Capture after fetch so keystrokes typed during the request are kept.
        const searchState = captureShopSearchState();
        const keepSearchFocus = Boolean(searchState?.focused);
        currentMain.innerHTML = nextMain.innerHTML;
        document.title = doc.title;
        syncStoreChrome(doc);
        enhanceSelects(currentMain);
        initPurchasesPage(currentMain);
        initProfilePage(currentMain);
        initPasswordToggles(currentMain);
        initProductImageSkeletons(currentMain);
        initFlashToasts(currentMain);
        initPurchaseSuccess(currentMain);

        if (push && token === softNavToken) {
            history.pushState({ soft: true }, "", href);
        }

        restoreShopSearchState(searchState);

        if (!keepSearchFocus) {
            window.scrollTo(0, 0);
        }
    } catch (error) {
        if (error && error.name === "AbortError") return;
        window.location.href = href;
    } finally {
        if (token === softNavToken) {
            softNavController = null;
            document.body.classList.remove("is-navigating");
        }
    }
}

function productsSkeletonCard() {
    return `
        <article class="shop-card shop-card--skeleton" aria-hidden="true">
            <div class="shop-card-media"><span class="skeleton-block skeleton-media"></span></div>
            <div class="shop-card-body">
                <div class="skeleton-row">
                    <span class="skeleton-line w-55"></span>
                    <span class="skeleton-line w-22"></span>
                </div>
                <span class="skeleton-line w-30"></span>
                <span class="skeleton-line w-45"></span>
                <div class="skeleton-row skeleton-actions">
                    <span class="skeleton-line w-28 h-32"></span>
                    <span class="skeleton-line w-55 h-32"></span>
                </div>
            </div>
        </article>
    `;
}

function showProductsSkeleton() {
    const main = document.querySelector(".store-content");
    if (!main) return;

    const page = main.querySelector(".shop-page");
    const perPage = Number(document.querySelector('select[name="per_page"]')?.value || "8");
    const count = Math.min(Math.max(perPage || 8, 4), 12);
    const cards = Array.from({ length: count }, () => productsSkeletonCard()).join("");
    const gridHtml = `<div class="shop-grid shop-skeleton-grid" aria-busy="true" aria-live="polite">${cards}</div>`;

    if (page && page.querySelector(".shop-toolbar")) {
        page.querySelectorAll(".shop-grid, .shop-footer, .empty, .shop-skeleton-grid").forEach((node) => node.remove());
        page.insertAdjacentHTML("beforeend", gridHtml);
        return;
    }

    main.innerHTML = `
        <section class="shop-page" aria-busy="true">
            <div class="shop-sticky">
                <header class="shop-heading">
                    <div>
                        <span class="skeleton-line w-35 h-28"></span>
                        <span class="skeleton-line w-40 mt-8"></span>
                    </div>
                </header>
                <div class="shop-toolbar shop-toolbar--skeleton">
                    <span class="skeleton-line skeleton-search"></span>
                    <span class="skeleton-line w-28 h-36"></span>
                    <span class="skeleton-line w-28 h-36"></span>
                </div>
            </div>
            ${gridHtml}
        </section>
    `;
}

function initProductImageSkeletons(root) {
    root.querySelectorAll(".shop-card-media img, .product-detail-media img").forEach((img) => {
        const media = img.closest(".shop-card-media, .product-detail-media");
        if (!media || media.dataset.skeletonBound === "1") return;
        media.dataset.skeletonBound = "1";

        const reveal = () => media.classList.remove("is-loading");
        media.classList.add("is-loading");

        if (img.complete && img.naturalWidth > 0) {
            reveal();
            return;
        }

        img.addEventListener("load", reveal, { once: true });
        img.addEventListener("error", reveal, { once: true });
    });
}

function syncStoreChrome(doc) {
    const nextNav = doc.querySelector(".store-nav");
    const currentNav = document.querySelector(".store-nav");
    if (nextNav && currentNav) {
        currentNav.innerHTML = nextNav.innerHTML;
        currentNav.querySelectorAll("a[href]").forEach((link) => {
            link.addEventListener("pointerenter", () => prefetch(link.href), { once: true });
        });
    }

    const nextDrawerNav = doc.querySelector(".store-drawer-nav");
    const currentDrawerNav = document.querySelector(".store-drawer-nav");
    if (nextDrawerNav && currentDrawerNav) {
        currentDrawerNav.innerHTML = nextDrawerNav.innerHTML;
        currentDrawerNav.querySelectorAll("a").forEach((link) => {
            link.addEventListener("click", () => {
                if (window.matchMedia("(max-width: 820px)").matches) {
                    document.body.classList.remove("store-nav-open");
                    const toggle = document.querySelector("[data-store-menu-toggle]");
                    const backdrop = document.querySelector("[data-store-drawer-backdrop]");
                    if (toggle) {
                        toggle.setAttribute("aria-expanded", "false");
                        toggle.setAttribute("aria-label", "Open menu");
                    }
                    if (backdrop) backdrop.hidden = true;
                }
            });
            link.addEventListener("pointerenter", () => prefetch(link.href), { once: true });
        });
    }

    const nextDrawerFoot = doc.querySelector(".store-drawer-foot");
    const currentDrawerFoot = document.querySelector(".store-drawer-foot");
    if (nextDrawerFoot && currentDrawerFoot) {
        currentDrawerFoot.innerHTML = nextDrawerFoot.innerHTML;
    }

    const nextActions = doc.querySelector(".store-top-actions");
    const currentActions = document.querySelector(".store-top-actions");
    if (nextActions && currentActions) {
        currentActions.innerHTML = nextActions.innerHTML;
    }

    document.body.classList.remove("store-nav-open");
    const toggle = document.querySelector("[data-store-menu-toggle]");
    const backdrop = document.querySelector("[data-store-drawer-backdrop]");
    if (toggle) {
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-label", "Open menu");
    }
    if (backdrop) backdrop.hidden = true;
}

function prefetch(href) {
    try {
        const url = new URL(href, window.location.href);
        if (!isSoftNavPath(url.pathname)) return;
        const existing = document.head.querySelector(`link[rel="prefetch"][href="${url.href}"]`);
        if (existing) return;
        const link = document.createElement("link");
        link.rel = "prefetch";
        link.href = url.href;
        document.head.appendChild(link);
    } catch (error) {
        // ignore bad urls
    }
}

function ensureNavProgress() {
    if (document.querySelector(".nav-progress")) return;
    const bar = document.createElement("div");
    bar.className = "nav-progress";
    bar.setAttribute("aria-hidden", "true");
    document.body.prepend(bar);
}

let confirmResolver = null;

function mountConfirmDialog() {
    if (document.getElementById("ui-confirm")) return;

    const root = document.createElement("div");
    root.id = "ui-confirm";
    root.className = "ui-confirm";
    root.hidden = true;
    root.innerHTML = `
        <div class="ui-confirm__backdrop" data-ui-cancel></div>
        <div class="ui-confirm__panel" role="alertdialog" aria-modal="true" aria-labelledby="ui-confirm-title" aria-describedby="ui-confirm-text">
            <div class="ui-confirm__badge" aria-hidden="true">?</div>
            <h3 id="ui-confirm-title">Confirm</h3>
            <p id="ui-confirm-text"></p>
            <div class="ui-confirm__items" id="ui-confirm-items" hidden></div>
            <div class="ui-confirm__actions">
                <button type="button" class="ui-btn ui-btn--ghost" data-ui-cancel>Cancel</button>
                <button type="button" class="ui-btn ui-btn--primary" data-ui-ok>
                    <span data-ui-ok-label>Confirm</span>
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(root);

    root.addEventListener("click", (event) => {
        if (event.target.closest("[data-ui-ok]")) closeConfirm(true);
        if (event.target.closest("[data-ui-cancel]")) closeConfirm(false);
    });

    document.addEventListener("keydown", (event) => {
        if (root.hidden) return;
        if (event.key === "Escape") closeConfirm(false);
    });
}

function confirmScrollRoot(target) {
    if (!(target instanceof Element)) return null;
    return target.closest(".ui-confirm__items-list");
}

function preventBackgroundScroll(event) {
    if (!document.body.classList.contains("ui-confirm-open")) return;
    const scrollRoot = confirmScrollRoot(event.target);
    if (scrollRoot && scrollRoot.scrollHeight > scrollRoot.clientHeight) {
        return;
    }
    event.preventDefault();
}

function preventBackgroundKeys(event) {
    if (!document.body.classList.contains("ui-confirm-open")) return;
    const keys = ["ArrowUp", "ArrowDown", "PageUp", "PageDown", "Home", "End", " ", "Spacebar"];
    if (!keys.includes(event.key)) return;
    if (confirmScrollRoot(event.target)) return;
    event.preventDefault();
}

function askConfirm(message, options = {}) {
    mountConfirmDialog();
    const root = document.getElementById("ui-confirm");
    const panel = root.querySelector(".ui-confirm__panel");
    const text = document.getElementById("ui-confirm-text");
    const title = document.getElementById("ui-confirm-title");
    const itemsBox = document.getElementById("ui-confirm-items");
    const ok = root.querySelector("[data-ui-ok]");
    const items = Array.isArray(options.items) ? options.items : [];
    const isDanger = /delete|log out/i.test(message);

    title.textContent = isDanger ? "Are you sure?" : "Please confirm";
    text.textContent = message;
    const okLabel = root.querySelector("[data-ui-ok-label]");
    const label = /log out/i.test(message) ? "Log out" : /delete/i.test(message) ? "Delete" : /purchase/i.test(message) ? "Purchase" : "Confirm";
    if (okLabel) okLabel.textContent = label;
    else ok.textContent = label;
    root.classList.toggle("is-danger", isDanger);
    root.classList.toggle("has-items", items.length > 0);
    if (panel) panel.classList.toggle("has-items", items.length > 0);

    if (itemsBox) {
        if (items.length === 0) {
            itemsBox.hidden = true;
            itemsBox.innerHTML = "";
        } else {
            itemsBox.hidden = false;
            const rows = items
                .map((item) => {
                    const name = escapeHtml(String(item?.name || "Product"));
                    const initial = escapeHtml(String(item?.initial || name.slice(0, 1) || "P"));
                    const image = typeof item?.image === "string" && item.image ? item.image : "";
                    const media = image
                        ? `<img src="${escapeHtml(image)}" alt="">`
                        : `<span class="ui-confirm__item-fallback" aria-hidden="true">${initial}</span>`;
                    return `<div class="ui-confirm__item"><div class="ui-confirm__item-media">${media}</div><div class="ui-confirm__item-copy"><strong>${name}</strong><small>Will be deleted</small></div></div>`;
                })
                .join("");
            itemsBox.innerHTML = `
                <div class="ui-confirm__items-head">
                    <strong>${items.length} product${items.length === 1 ? "" : "s"}</strong>
                    <span>Scroll to review</span>
                </div>
                <div class="ui-confirm__items-list" tabindex="0">${rows}</div>
            `;
        }
    }

    root.hidden = false;
    document.body.classList.add("ui-confirm-open");
    document.addEventListener("wheel", preventBackgroundScroll, { passive: false });
    document.addEventListener("touchmove", preventBackgroundScroll, { passive: false });
    document.addEventListener("keydown", preventBackgroundKeys);
    ok.focus({ preventScroll: true });

    return new Promise((resolve) => {
        confirmResolver = resolve;
    });
}

function closeConfirm(result) {
    const root = document.getElementById("ui-confirm");
    if (!root) return;

    root.hidden = true;
    document.body.classList.remove("ui-confirm-open");
    document.removeEventListener("wheel", preventBackgroundScroll);
    document.removeEventListener("touchmove", preventBackgroundScroll);
    document.removeEventListener("keydown", preventBackgroundKeys);

    if (confirmResolver) {
        confirmResolver(result);
        confirmResolver = null;
    }
}

function enhanceSelects(root) {
    root.querySelectorAll("select").forEach((select) => {
        if (select.dataset.uiSelect === "1" || select.multiple) return;
        wrapSelect(select);
    });
}

function wrapSelect(select) {
    select.dataset.uiSelect = "1";

    const shell = document.createElement("div");
    shell.className = "ui-select";
    select.parentNode.insertBefore(shell, select);
    shell.appendChild(select);
    select.classList.add("ui-select__native");

    const button = document.createElement("button");
    button.type = "button";
    button.className = "ui-select__button";
    button.setAttribute("aria-haspopup", "listbox");
    button.setAttribute("aria-expanded", "false");

    const list = document.createElement("div");
    list.className = "ui-select__list";
    list.setAttribute("role", "listbox");
    list.hidden = true;

    const labelOf = () => {
        const option = select.options[select.selectedIndex];
        return option ? option.textContent.trim() : "Select";
    };

    const paint = () => {
        button.innerHTML = `<span class="ui-select__value">${escapeHtml(labelOf())}</span><span class="ui-select__chevron" aria-hidden="true"></span>`;
        list.innerHTML = "";
        Array.from(select.options).forEach((option, index) => {
            const row = document.createElement("button");
            row.type = "button";
            row.className = "ui-select__option" + (option.selected ? " is-active" : "");
            row.setAttribute("role", "option");
            row.setAttribute("aria-selected", option.selected ? "true" : "false");
            row.disabled = option.disabled;
            row.textContent = option.textContent;
            row.addEventListener("click", () => {
                select.selectedIndex = index;
                select.dispatchEvent(new Event("change", { bubbles: true }));
                paint();
                closeSelect(shell, button, list);
                if (select.hasAttribute("data-autosubmit")) {
                    const form = select.closest("form");
                    if (form) form.requestSubmit();
                }
            });
            list.appendChild(row);
        });
    };

    button.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        const open = shell.classList.contains("is-open");
        closeAllSelects();
        if (!open) {
            shell.classList.add("is-open");
            button.setAttribute("aria-expanded", "true");
            list.hidden = false;
        }
    });

    shell.appendChild(button);
    shell.appendChild(list);
    paint();
    select.addEventListener("change", paint);
}

function closeSelect(shell, button, list) {
    shell.classList.remove("is-open");
    button.setAttribute("aria-expanded", "false");
    list.hidden = true;
}

function closeAllSelects() {
    document.querySelectorAll(".ui-select.is-open").forEach((shell) => {
        shell.classList.remove("is-open");
        const button = shell.querySelector(".ui-select__button");
        const list = shell.querySelector(".ui-select__list");
        if (button) button.setAttribute("aria-expanded", "false");
        if (list) list.hidden = true;
    });
}

document.addEventListener("click", (event) => {
    if (!event.target.closest(".ui-select")) closeAllSelects();
    if (!event.target.closest(".tx-menu") && !event.target.closest(".tx-menu-list")) closePurchaseMenus();
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
        closeAllSelects();
        closePurchaseMenus();
    }
});

window.addEventListener("scroll", closePurchaseMenus, true);
window.addEventListener("resize", closePurchaseMenus);

function closePurchaseMenus() {
    document.querySelectorAll(".tx-menu.is-open").forEach((menu) => {
        menu.classList.remove("is-open");
        const button = menu.querySelector("[data-tx-menu]");
        const list = menu.querySelector(".tx-menu-list");
        if (button) button.setAttribute("aria-expanded", "false");
        if (list) {
            list.hidden = true;
            list.style.top = "";
            list.style.left = "";
            list.style.right = "";
            list.style.bottom = "";
        }
    });
}

function positionPurchaseMenu(button, list) {
    const rect = button.getBoundingClientRect();
    const menuWidth = 168;
    const gap = 6;
    let left = rect.right - menuWidth;
    if (left < 8) left = 8;
    if (left + menuWidth > window.innerWidth - 8) {
        left = Math.max(8, window.innerWidth - menuWidth - 8);
    }

    list.style.position = "fixed";
    list.style.right = "auto";
    list.style.left = `${left}px`;
    list.style.zIndex = "80";

    const spaceBelow = window.innerHeight - rect.bottom;
    const estimatedHeight = list.offsetHeight || 120;
    if (spaceBelow < estimatedHeight + gap && rect.top > estimatedHeight + gap) {
        list.style.top = `${Math.max(8, rect.top - estimatedHeight - gap)}px`;
        list.style.bottom = "auto";
    } else {
        list.style.top = `${rect.bottom + gap}px`;
        list.style.bottom = "auto";
    }
}

function initPurchasesPage(root) {
    const page = root.querySelector("[data-purchases-page]");
    if (!page || page.dataset.bound === "1") return;
    page.dataset.bound = "1";

    const rows = Array.from(page.querySelectorAll("[data-purchase-row]"));
    if (rows.length === 0) return;

    let perPage = Math.max(1, Number(page.getAttribute("data-per-page") || "5"));
    const searchInput = page.querySelector("[data-purchase-search]");
    const rangeSelect = page.querySelector("[data-purchase-range]");
    const perPageSelect = page.querySelector("[data-purchase-per-page]");
    const summary = page.querySelector("[data-purchase-summary]");
    const pager = page.querySelector("[data-purchase-pager]");
    const exportBtn = page.querySelector("[data-purchase-export]");
    let currentPage = 1;

    if (perPageSelect) {
        perPageSelect.value = String(perPage);
    }

    const today = new Date();
    const dayKey = formatDateKey(today);
    const weekKey = formatWeekKey(today);
    const monthKey = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}`;

    const filteredRows = () => {
        const query = (searchInput?.value || "").trim().toLowerCase();
        const range = rangeSelect?.value || "all";

        return rows.filter((row) => {
            const haystack = `${row.dataset.code || ""} ${row.dataset.product || ""}`.toLowerCase();
            if (query && !haystack.includes(query)) return false;
            if (range === "today" && row.dataset.day !== dayKey) return false;
            if (range === "week" && row.dataset.week !== weekKey) return false;
            if (range === "month" && row.dataset.month !== monthKey) return false;
            return true;
        });
    };

    const render = () => {
        const matched = filteredRows();
        const pageSize = Math.max(1, perPage);
        const lastPage = Math.max(1, Math.ceil(matched.length / pageSize));
        if (currentPage > lastPage) currentPage = lastPage;

        const start = (currentPage - 1) * pageSize;
        const visible = matched.slice(start, start + pageSize);
        const visibleSet = new Set(visible);

        rows.forEach((row) => {
            row.hidden = !visibleSet.has(row);
        });

        visible.forEach((row, index) => {
            const cell = row.querySelector("[data-col-index]");
            if (cell) cell.textContent = String(start + index + 1);
        });

        if (summary) {
            const from = matched.length === 0 ? 0 : start + 1;
            const to = matched.length === 0 ? 0 : start + visible.length;
            summary.textContent = `Showing ${from} – ${to} of ${matched.length} purchases`;
        }

        if (pager) {
            pager.hidden = false;
            pager.innerHTML = "";
            const addBtn = (label, pageNum, disabled = false, current = false) => {
                const btn = document.createElement("button");
                btn.type = "button";
                btn.textContent = label;
                btn.disabled = disabled;
                if (current) btn.classList.add("is-current");
                btn.addEventListener("click", () => {
                    currentPage = pageNum;
                    render();
                });
                pager.appendChild(btn);
            };

            addBtn("‹", Math.max(1, currentPage - 1), currentPage === 1);
            for (let i = 1; i <= lastPage; i += 1) {
                addBtn(String(i), i, false, i === currentPage);
            }
            addBtn("›", Math.min(lastPage, currentPage + 1), currentPage === lastPage);
        }
    };

    page.querySelectorAll("[data-tx-menu]").forEach((button) => {
        button.addEventListener("click", (event) => {
            event.preventDefault();
            event.stopPropagation();
            const menu = button.closest(".tx-menu");
            const list = menu?.querySelector(".tx-menu-list");
            const open = menu?.classList.contains("is-open");
            closePurchaseMenus();
            if (!open && menu && list) {
                menu.classList.add("is-open");
                button.setAttribute("aria-expanded", "true");
                list.hidden = false;
                positionPurchaseMenu(button, list);
            }
        });
    });

    page.querySelectorAll("[data-copy-code]").forEach((button) => {
        button.addEventListener("click", async (event) => {
            event.preventDefault();
            event.stopPropagation();
            const code = button.getAttribute("data-copy-code") || "";
            try {
                await navigator.clipboard.writeText(code);
                button.textContent = "Copied";
                window.setTimeout(() => {
                    button.textContent = "Copy ID";
                }, 1200);
            } catch (error) {
                button.textContent = "Copy failed";
            }
            closePurchaseMenus();
        });
    });

    searchInput?.addEventListener("input", () => {
        currentPage = 1;
        render();
    });

    rangeSelect?.addEventListener("change", () => {
        currentPage = 1;
        render();
    });

    perPageSelect?.addEventListener("change", () => {
        perPage = Math.max(1, Number(perPageSelect.value || "5"));
        page.setAttribute("data-per-page", String(perPage));
        currentPage = 1;
        render();
    });

    exportBtn?.addEventListener("click", () => {
        const matched = filteredRows();
        const lines = [["Transaction ID", "Product", "Quantity", "Total", "Purchased At", "Status"]];
        matched.forEach((row) => {
            lines.push([
                row.dataset.code || "",
                row.dataset.product || "",
                row.dataset.qty || "",
                row.dataset.total || "",
                row.dataset.when || "",
                "Completed",
            ]);
        });
        const csv = lines.map((cols) => cols.map((v) => `"${String(v).replaceAll('"', '""')}"`).join(",")).join("\n");
        const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = "my-purchases.csv";
        link.click();
        URL.revokeObjectURL(link.href);
    });

    render();
}

function initProfilePage(root) {
    const form = root.querySelector("form.profile-layout");
    if (!form || form.dataset.bound === "1") return;
    form.dataset.bound = "1";

    const nameInput = form.querySelector('input[name="name"]');
    const usernameInput = form.querySelector('input[name="username"]');
    const avatar = root.querySelector("[data-profile-avatar]");
    const namePreview = root.querySelector("[data-profile-name]");
    const usernamePreview = root.querySelector("[data-profile-username]");

    nameInput?.addEventListener("input", () => {
        const value = nameInput.value.trim();
        if (avatar) avatar.textContent = (value ? value[0] : "U").toUpperCase();
        if (namePreview) namePreview.textContent = value || "Your name";
    });

    usernameInput?.addEventListener("input", () => {
        if (!usernamePreview) return;
        usernamePreview.textContent = usernameInput.value.trim() || "username";
    });

    const dialog = form.querySelector("[data-password-dialog]");
    const openBtn = form.querySelector("[data-open-password-dialog]");
    if (!dialog || !openBtn) return;

    const passwordFields = ["current_password", "password", "password_confirmation"];

    const onPasswordKeydown = (event) => {
        if (event.key === "Escape") closePasswordDialog(true);
    };

    const openPasswordDialog = () => {
        dialog.hidden = false;
        document.body.classList.add("ui-confirm-open");
        document.addEventListener("keydown", onPasswordKeydown);
        dialog.querySelector("#current_password")?.focus({ preventScroll: true });
    };

    const closePasswordDialog = (clear = false) => {
        if (dialog.hidden) return;
        dialog.hidden = true;
        document.body.classList.remove("ui-confirm-open");
        document.removeEventListener("keydown", onPasswordKeydown);
        if (clear) {
            passwordFields.forEach((name) => {
                const input = form.querySelector(`[name="${name}"]`);
                if (input) {
                    input.value = "";
                    input.type = "password";
                }
                paintField(form, name, "");
            });
        }
        openBtn.focus({ preventScroll: true });
    };

    openBtn.addEventListener("click", openPasswordDialog);

    dialog.addEventListener("click", (event) => {
        if (event.target.closest("[data-close-password-dialog]")) {
            closePasswordDialog(true);
            return;
        }
        if (event.target.closest("[data-apply-password-dialog]")) {
            closePasswordDialog(false);
        }
    });

    if (!dialog.hidden) openPasswordDialog();
}

function formatDateKey(date) {
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
}

function formatWeekKey(date) {
    const tmp = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
    const dayNum = tmp.getUTCDay() || 7;
    tmp.setUTCDate(tmp.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(tmp.getUTCFullYear(), 0, 1));
    const week = Math.ceil((((tmp - yearStart) / 86400000) + 1) / 7);
    return `${tmp.getUTCFullYear()}-${String(week).padStart(2, "0")}`;
}

function getTouched(form) {
    if (!touchedByForm.has(form)) {
        touchedByForm.set(form, new Set());
    }
    return touchedByForm.get(form);
}

function strongPasswordError(value) {
    if (value === "") return "Password is required.";
    if (
        value.length < 8 ||
        !/[A-Z]/.test(value) ||
        !/[a-z]/.test(value) ||
        !/\d/.test(value) ||
        !/[^A-Za-z0-9]/.test(value)
    ) {
        return "Password must be at least 8 characters and include uppercase, lowercase, a number, and a symbol.";
    }
    return "";
}

function valueOf(form, name) {
    const field = form.querySelector(`[name="${name}"]`);
    return field ? field.value.trim() : "";
}

function validateForm(form, schema, paint) {
    const errors = {};
    Object.keys(schema).forEach((name) => {
        const message = schema[name](valueOf(form, name), form);
        if (message) errors[name] = message;
        if (paint) paintField(form, name, message);
    });
    return errors;
}

function validateField(form, schema, name, paint) {
    const message = schema[name](valueOf(form, name), form);
    if (paint) paintField(form, name, message);
    return message;
}

function paintField(form, name, message) {
    const slot = form.querySelector(`[data-field="${name}"]`) || closestField(form.querySelector(`[name="${name}"]`));
    if (!slot) return;

    slot.querySelectorAll(".field-error").forEach((node) => node.remove());
    slot.classList.remove("is-invalid", "is-valid");

    const shell = slot.querySelector(".input-shell");
    if (shell) shell.classList.remove("is-invalid", "is-valid");

    const input = slot.querySelector(`[name="${name}"]`);
    const empty = !input || input.value.trim() === "";

    if (message) {
        slot.classList.add("is-invalid");
        if (shell) shell.classList.add("is-invalid");
        const error = document.createElement("p");
        error.className = "field-error js";
        error.textContent = message;
        slot.appendChild(error);
        return;
    }

    if (!empty) {
        slot.classList.add("is-valid");
        if (shell) shell.classList.add("is-valid");
    }
}

function clearField(form, name) {
    const slot = form.querySelector(`[data-field="${name}"]`) || closestField(form.querySelector(`[name="${name}"]`));
    if (!slot) return;
    slot.querySelectorAll(".field-error").forEach((node) => node.remove());
    slot.classList.remove("is-invalid", "is-valid");
    const shell = slot.querySelector(".input-shell");
    if (shell) shell.classList.remove("is-invalid", "is-valid");
}

function closestField(node) {
    return node ? node.closest(".field") : null;
}
