const express = require("express");
const path = require("path");
const app = express();
const PORT = process.env.PORT || 3000;
app.disable("x-powered-by");
app.use(express.json({ limit: "1mb" }));
app.use(express.static(path.join(__dirname, "public"), { extensions: ["html"] }));
app.get("/health", (_req, res) => res.json({ status: "ok", app: "AMIVON HOSTING", timestamp: new Date().toISOString() }));
app.get("/api/config", (_req, res) => res.json({
  siteName: "AMIVON HOSTING",
  currency: "KES",
  integrations: {
    namecheap: Boolean(process.env.NAMECHEAP_API_USER && process.env.NAMECHEAP_API_KEY),
    paystack: Boolean(process.env.PAYSTACK_SECRET_KEY),
    webuzo: Boolean(process.env.WEBUZO_API_URL && process.env.WEBUZO_API_KEY)
  }
}));
app.get("*", (_req, res) => res.sendFile(path.join(__dirname, "public", "index.html")));
app.listen(PORT, "0.0.0.0", () => console.log(`AMIVON HOSTING listening on ${PORT}`));
