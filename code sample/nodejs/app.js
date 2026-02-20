

import express from "express";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);


dotenv.config();

const app = express();
app.use(express.json());

const LLM_ENDPOINT = "https://biz.zygy.com/api/v1/response";
const API_KEY = process.env.API_KEY;

async function* respond(message, chatHistory = []) {

  const payload = {
    querySearch: message,
    uid: "uid",
  };

  console.log("Calling LLM...");

  const response = await fetch(LLM_ENDPOINT, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Bearer ${API_KEY}`
    },
    body: JSON.stringify(payload),
    stream: true
  });

  if (!response.ok) {
  const text = await response.text();
  console.log("ERROR BODY:", text);
  return;
}

  const reader = response.body.getReader();
  const decoder = new TextDecoder();

  let buffer = "";
  let botReply = "";
  let answerStarted = false;
  let dotCounter = 0;

  while (true) {
    const { done, value } = await reader.read();
    if (done) break;

    buffer += decoder.decode(value, { stream: true });

    const events = buffer.split("\n\n");
    buffer = events.pop();

    for (const event of events) {

      if (!event.startsWith("data:")) continue;

      const data = event.slice(5).trim();
      if (data === "[DONE]") return;

      let chunk;
      try {
        chunk = JSON.parse(data);
      } catch {
        continue;
      }

      const delta = chunk?.choices?.[0]?.delta?.content;
      if (!delta) continue;

      if (delta.includes("**Reference**")) return;

      if (answerStarted) {
        botReply += delta;
      }
      else if (delta.includes("Combining results into a final answer...")) {
        botReply = "";
        answerStarted = true;
      }
      else {
        dotCounter = (dotCounter + 1) % 3;
        botReply = "Thinking" + ".".repeat(dotCounter + 1);
      }

      chatHistory[chatHistory.length - 1] =
        [message, botReply];

      yield botReply;
    }
  }
}

/* =========================
   HTTP ENDPOINT
========================= */

app.post("/chat", async (req, res) => {

  const { message } = req.body;

  // SSE headers
  res.setHeader("Content-Type", "text/event-stream");
  res.setHeader("Cache-Control", "no-cache");
  res.setHeader("Connection", "keep-alive");

  try {
    for await (const update of respond(message, [])) {
      res.write(`data: ${JSON.stringify(update)}\n\n`);
    }
  } catch (err) {
    res.write(`data: ${JSON.stringify({ error: err.message })}\n\n`);
  }

  res.end();
});


const PORT = 3000;
app.use(express.static(path.join(__dirname, "public")));

app.listen(PORT, () => {
  console.log(`✅ Server running at http://localhost:${PORT}`);
});

