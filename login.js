function setOutput(message, type = "info") {
    const output = document.getElementById("output");
    output.textContent = message;
    output.className = type;
}

async function hashPassword(password, algorithm = "SHA-256") {
    const encoder = new TextEncoder();
    const data = encoder.encode(password);

    const hashBuffer = await crypto.subtle.digest(algorithm, data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));

    return hashArray
        .map(b => b.toString(16).padStart(2, "0"))
        .join("");
}

document.getElementById("login-form").addEventListener("submit", async function (event) {
    event.preventDefault();

    const username = document.getElementById("user").value.trim();
    const password = document.getElementById("pwd").value;

    if (!username || !password) {
        setOutput("Please fill in all fields.", "error");
        return;
    }

    setOutput("Processing...", "info");

    const hashedPassword = await hashPassword(password);
});