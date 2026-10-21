// Practical 13 : Fetch API consumer for the PHP JSON API (no page reload)
// Endpoints: api/list.php (GET) | api/create.php (POST) | api/update.php (PUT) | api/delete.php (DELETE)

const eventsBody  = document.getElementById("eventsBody");
const countLabel  = document.getElementById("countLabel");
const messageBox  = document.getElementById("message");
const eventForm   = document.getElementById("eventForm");
const formTitle   = document.getElementById("formTitle");
const cancelBtn   = document.getElementById("cancelEditBtn");

// XSS-safe text injection
function esc(s) {
    return String(s ?? "").replace(/[&<>"']/g, (c) => ({
        "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;"
    }[c]));
}

function showMessage(text, isError) {
    messageBox.textContent = text;
    messageBox.className = isError ? "msg-error" : "msg-success";
    clearTimeout(showMessage._t);
    showMessage._t = setTimeout(() => { messageBox.className = "hidden"; }, 4000);
}

async function readJson(res) {
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.success === false) {
        throw new Error(data.message || ("Request failed (HTTP " + res.status + ")"));
    }
    return data;
}

// ---- LIST (READ) ----
async function loadEvents() {
    const q = document.getElementById("searchInput").value.trim();
    const status = document.getElementById("statusFilter").value;
    const params = new URLSearchParams();
    if (q) params.set("q", q);
    if (status) params.set("status", status);

    eventsBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Loading events...</td></tr>';
    try {
        const res = await fetch("api/list.php?" + params.toString());
        const json = await readJson(res);
        renderTable(json.data || []);
        countLabel.textContent = (json.count ?? 0) + " record(s) found.";
    } catch (err) {
        eventsBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">Failed to load events.</td></tr>';
        countLabel.textContent = "0 record(s) found.";
        showMessage("Failure: " + err.message, true);
    }
}

function renderTable(events) {
    if (!events.length) {
        eventsBody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No events found.</td></tr>';
        return;
    }
    eventsBody.innerHTML = events.map((e) => `
        <tr>
            <td>${e.id}</td>
            <td><b>${esc(e.title)}</b><br><span class="muted">${esc((e.description || "").slice(0, 80))}</span></td>
            <td>${esc(e.event_date)}</td>
            <td>${esc(e.venue)}</td>
            <td>${esc(e.status)}</td>
            <td class="actions">
                <button class="btn btn-edit" data-edit='${esc(JSON.stringify(e))}'>Edit</button>
                <button class="btn btn-delete" data-del="${e.id}">Delete</button>
            </td>
        </tr>`).join("");
}

// Event delegation: single listener for all Edit/Delete buttons (works after re-render)
eventsBody.addEventListener("click", async (e) => {
    const editBtn = e.target.closest("[data-edit]");
    const delBtn = e.target.closest("[data-del]");
    if (editBtn) {
        startEdit(JSON.parse(editBtn.getAttribute("data-edit")));
    } else if (delBtn) {
        await deleteEvent(delBtn.getAttribute("data-del"));
    }
});

// ---- CREATE + UPDATE (same form) ----
eventForm.addEventListener("submit", async (e) => {
    e.preventDefault(); // <-- key line: prevents full page reload
    const id = document.getElementById("eventId").value;
    const payload = {
        title: document.getElementById("title").value.trim(),
        description: document.getElementById("description").value.trim(),
        event_date: document.getElementById("event_date").value,
        venue: document.getElementById("venue").value.trim(),
        status: document.getElementById("status").value
    };
    if (id) payload.id = parseInt(id, 10);

    const isEdit = id !== "";
    try {
        const res = await fetch(isEdit ? "api/update.php" : "api/create.php", {
            method: isEdit ? "PUT" : "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload)
        });
        const json = await readJson(res);
        showMessage(json.message || "Saved.", false);
        resetForm();
        await loadEvents(); // refresh table only, no reload
    } catch (err) {
        showMessage("Failure: " + err.message, true);
    }
});

function startEdit(ev) {
    document.getElementById("eventId").value = ev.id;
    document.getElementById("title").value = ev.title || "";
    document.getElementById("description").value = ev.description || "";
    document.getElementById("event_date").value = ev.event_date || "";
    document.getElementById("venue").value = ev.venue || "";
    document.getElementById("status").value = ev.status || "upcoming";
    formTitle.textContent = "Edit Event (ID: " + ev.id + ")";
    document.getElementById("submitBtn").textContent = "Update Event";
    cancelBtn.classList.remove("hidden");
    window.scrollTo({ top: 0, behavior: "smooth" });
}

function resetForm() {
    eventForm.reset();
    document.getElementById("eventId").value = "";
    formTitle.textContent = "Add Event";
    document.getElementById("submitBtn").textContent = "Save Event";
    cancelBtn.classList.add("hidden");
}
cancelBtn.addEventListener("click", resetForm);

// ---- DELETE ----
async function deleteEvent(id) {
    if (!confirm("Delete event #" + id + "?")) return;
    try {
        const res = await fetch("api/delete.php", {
            method: "DELETE",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: parseInt(id, 10) })
        });
        const json = await readJson(res);
        showMessage(json.message, false);
        await loadEvents(); // refresh table only, no reload
    } catch (err) {
        showMessage("Failure: " + err.message, true);
    }
}

// ---- Search / filter (also async) ----
document.getElementById("searchBtn").addEventListener("click", loadEvents);
document.getElementById("resetBtn").addEventListener("click", () => {
    document.getElementById("searchInput").value = "";
    document.getElementById("statusFilter").value = "";
    loadEvents();
});
document.getElementById("searchInput").addEventListener("keydown", (e) => {
    if (e.key === "Enter") { e.preventDefault(); loadEvents(); }
});

// Initial load
loadEvents();
