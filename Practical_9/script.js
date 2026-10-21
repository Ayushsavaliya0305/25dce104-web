// script.js — Frontend validation for Practical 9 (mirrors backend rules)

function setErr(id, msg) {
    const e = document.getElementById(id);
    if (e) e.textContent = msg || "";
    return !msg;
}

function validateField(input, rule) {
    const errId = input.dataset.err;
    const ok = rule(input.value.trim());
    input.classList.toggle("invalid", !ok.valid);
    setErr(errId, ok.valid ? "" : ok.msg);
    return ok.valid;
}

const rules = {
    fullname: v => v.length >= 3
        ? { valid: true } : { valid: false, msg: "Full name must be at least 3 characters." },
    username: v => /^[A-Za-z0-9_]{4,20}$/.test(v)
        ? { valid: true } : { valid: false, msg: "4–20 chars: letters, numbers, underscore only." },
    email: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)
        ? { valid: true } : { valid: false, msg: "Enter a valid email address." },
    password: v => v.length >= 8
        ? { valid: true } : { valid: false, msg: "Password must be at least 8 characters." },
    confirm: v => v === document.getElementById("password").value
        ? { valid: true } : { valid: false, msg: "Passwords do not match." }
};

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("regForm");
    if (!form) return;

    // Live validation on every keystroke
    form.querySelectorAll("[data-rule]").forEach(inp => {
        inp.addEventListener("input", () => {
            validateField(inp, rules[inp.dataset.rule]);
            if (inp.id === "password") updateMeter(inp.value);
            if (inp.dataset.rule === "password") {
                const c = document.getElementById("confirm_password");
                if (c && c.value) validateField(c, rules.confirm);
            }
        });
    });

    form.addEventListener("submit", e => {
        let ok = true;
        form.querySelectorAll("[data-rule]").forEach(inp => {
            if (!validateField(inp, rules[inp.dataset.rule])) ok = false;
        });
        if (!ok) {
            e.preventDefault();
            alert("Please fix the highlighted errors before submitting.");
        }
    });
});

// Password strength meter + show/hide
function updateMeter(pw) {
    const bar = document.getElementById("meterBar");
    if (!bar) return;
    let score = 0;
    if (pw.length >= 8) score++;
    if (/[A-Z]/.test(pw) && /[a-z]/.test(pw)) score++;
    if (/\d/.test(pw)) score++;
    if (/[^A-Za-z0-9]/.test(pw)) score++;
    const pct = [0, 25, 50, 75, 100][score];
    const col = ["#eee", "#e74c3c", "#f39c12", "#3498db", "#27ae60"][score];
    bar.style.width = pct + "%";
    bar.style.background = col;
}

function togglePass(id, btn) {
    const f = document.getElementById(id);
    if (f.type === "password") { f.type = "text"; btn.textContent = "Hide"; }
    else { f.type = "password"; btn.textContent = "Show"; }
}
