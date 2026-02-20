import os
import gradio as gr
from dotenv import load_dotenv
import requests
import json

load_dotenv()

LLM_ENDPOINT = "https://biz.zygy.com/api/v1/response"
API_KEY = os.getenv("API_KEY")

def respond(message, chat_history):
    """
    Gradio handler. Calls LLM API and yields two outputs: "" (to clear textbox) and chat_history.
    chat_history must be a list of (user, bot) tuples.
    """
    chat_history = chat_history or []
    chat_history.append((message, ""))

    
    payload = {
        "querySearch": message,
        "uid": "id",
        "chatHistory": [
            msg
            for user_msg, bot_msg in chat_history[:-1]
            for msg in (
                {"role": "user", "content": user_msg},
                {"role": "assistant", "content": bot_msg},
            )
        ],
        "mode":"websearch"
    }

    print(payload["chatHistory"])


    try:
        answer_started = False
        headers = {"Content-Type": "application/json","Authorization": f"Bearer {API_KEY}"}
        with requests.post(LLM_ENDPOINT, json=payload, headers=headers, stream=True, timeout=120) as r:
            r.raise_for_status()
            bot_reply = ""
            dot_counter = 0


            # Stream line by line (adjust depending on your backend format)
            for chunk in r.iter_lines(decode_unicode=True):
                if not chunk:                    
                    continue


                if chunk.startswith("data:"):
                    chunk = chunk[len("data:"):].strip()
                    chunk = json.loads(chunk)


                    if "content" in chunk['choices'][0]['delta']:
                        chunk = chunk['choices'][0]['delta']['content']

                        if "**Reference**" in chunk:
                            break

                        elif answer_started:
                            bot_reply += chunk
                        

                        elif "Combining results into a final answer..." in chunk:
                            bot_reply = "" 
                            answer_started = True

                        else:
                            dot_counter = (dot_counter + 1) % 3
                            bot_reply = "Thinking" + ("." * (dot_counter + 1))
                            chat_history[-1] = (message, bot_reply)
                            yield "", chat_history
 

                    else:
                        print("End of content")

                chat_history[-1] = (message, bot_reply)
                yield "", chat_history

    except Exception as e:
        chat_history[-1] = (message, f"[streaming error] {repr(e)}")
        yield "", chat_history


css = """
    #chat-container { 
        max-width: 400px; 
        margin: auto; 
        border-radius: 12px; 
        overflow: hidden; 
        background: #1c1c1c;
        color: white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }
    #header {
        background: #111; 
        padding: 10px; 
        display: flex; 
        align-items: center;
        font-weight: bold;
    }
    #status-dot {
        height: 10px; 
        width: 10px; 
        background: #4CAF50; 
        border-radius: 50%; 
        display: inline-block; 
        margin-right: 8px;
    }
    #chatbox { 
        background: #1c1c1c; 
        height: 400px; 
    }
    #input-area {
        background: white; 
        padding: 8px; 
        border-top: 1px solid #ccc;
    }
    #textbox textarea {
        border: none !important; 
        resize: none !important; 
        box-shadow: none !important;
        font-size: 14px;
        min-width: 120px;
    }
    #button-row {
        display: flex; 
        justify-content: space-between; 
        margin-top: 6px;
    }
"""

with gr.Blocks(theme=gr.themes.Citrus(), css=css) as demo:
    with gr.Column(elem_id="chat-container"):
        with gr.Row(elem_id="header"):
            gr.HTML("<span id='status-dot'></span> ZYGY AI Assistant")
        chatbot_ui = gr.Chatbot(elem_id="chatbox")
        
        # Input area (now two rows)
        with gr.Column(elem_id="input-area"):
            msg = gr.Textbox(
                show_label=False,
                placeholder="How may I be of assistance?",
                lines=1,
                elem_id="textbox"
            )
            with gr.Row(elem_id="button-row"):
                submit_btn = gr.Button("➤", scale=1)
                clear = gr.Button("🗑️", scale=1)

        submit_btn.click(respond, [msg, chatbot_ui], [msg, chatbot_ui], queue=True)
        msg.submit(respond, [msg, chatbot_ui], [msg, chatbot_ui], queue=True)
        clear.click(lambda: None, None, chatbot_ui, queue=False)

demo.launch()



