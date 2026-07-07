import os
import uuid
import time
import shutil
from typing import Dict, Optional
from pydantic import BaseModel
from fastapi import FastAPI, HTTPException, UploadFile, File, Depends, Security
from fastapi.security.api_key import APIKeyHeader
from fastapi.middleware.cors import CORSMiddleware

from langchain_community.document_loaders import PyPDFLoader
from langchain_text_splitters import RecursiveCharacterTextSplitter
from langchain_openai import OpenAIEmbeddings, ChatOpenAI
from langchain_community.vectorstores import Chroma
from langchain.chains import create_retrieval_chain
from langchain.chains.combine_documents import create_stuff_documents_chain
from langchain_core.prompts import ChatPromptTemplate, MessagesPlaceholder

# Initialize FastAPI
app = FastAPI()

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

import glob

from dotenv import load_dotenv

# Load configuration from .env file
load_dotenv()

API_KEY = os.getenv("DEALERDECK_API_KEY")
API_KEY_NAME = "X-API-Key"
api_key_header = APIKeyHeader(name=API_KEY_NAME, auto_error=False)

async def get_api_key(api_key: str = Security(api_key_header)):
    if api_key == API_KEY:
        return api_key
    raise HTTPException(status_code=403, detail="Could not validate credentials")

def get_latest_pdf():
    pdf_files = glob.glob("data/*.pdf")
    if not pdf_files:
        return "data/dealerDeck_ai_Support_Knowledge_Doc.pdf"
    return max(pdf_files, key=os.path.getmtime)

PDF_PATH = get_latest_pdf()
SUPPORT_EMAIL = "info@dealerDeck.ai"
SESSION_TTL_SECONDS = 1800  # 30 minutes

# 1. Dynamic RAG Chain Builder
rag_chain = None

def build_rag_chain(pdf_path: str):
    global rag_chain
    loader = PyPDFLoader(pdf_path)
    docs = loader.load()

    text_splitter = RecursiveCharacterTextSplitter(chunk_size=1000, chunk_overlap=200)
    splits = text_splitter.split_documents(docs)

    # Use a unique collection name so it doesn't append to the old one in memory
    unique_collection_name = f"kb_{uuid.uuid4().hex}"
    vectorstore = Chroma.from_documents(
        documents=splits, 
        embedding=OpenAIEmbeddings(),
        collection_name=unique_collection_name
    )
    retriever = vectorstore.as_retriever(search_kwargs={"k": 3})

    llm = ChatOpenAI(model="gpt-4o-mini", temperature=0.3, max_tokens=50)

    system_prompt = (
        "You are a highly concise AI assistant for DealerDeck.\n"
        "CRITICAL: You MUST answer in exactly ONE short sentence. Never output more than one line.\n"
        "Be polite. If the user greets you, respond warmly in one short sentence.\n"
        "Use the provided context to answer questions, summarizing it into a single efficient sentence.\n"
        "If you don't know the answer, politely suggest they reach out to support in one sentence.\n\n"
        "Context:\n{context}"
    )

    qa_prompt = ChatPromptTemplate.from_messages([
        ("system", system_prompt),
        MessagesPlaceholder(variable_name="chat_history"),
        ("human", "{input}"),
    ])

    question_answer_chain = create_stuff_documents_chain(llm, qa_prompt)
    rag_chain = create_retrieval_chain(retriever, question_answer_chain)

# Initial load
build_rag_chain(PDF_PATH)

# 3. Simple In-Memory History Storage with Expiration
# Format: { session_id: {"history": [...], "last_accessed": timestamp} }
chat_histories: Dict[str, dict] = {}

def get_and_clean_history(session_id: str) -> list:
    """Retrieves chat history and resets it if the TTL has expired."""
    current_time = time.time()
    
    if session_id not in chat_histories:
        chat_histories[session_id] = {"history": [], "last_accessed": current_time}
        return []
    
    session_data = chat_histories[session_id]
    
    # Check if history expired
    if current_time - session_data["last_accessed"] > SESSION_TTL_SECONDS:
        session_data["history"] = [] # Reset history
        
    session_data["last_accessed"] = current_time
    return session_data["history"]

# 4. API Schemas
class ChatRequest(BaseModel):
    session_id: Optional[str] = None
    message: str

class ChatResponse(BaseModel):
    session_id: str
    response: str

# 5. FastAPI Endpoints
@app.post("/chat", response_model=ChatResponse)
async def chat_endpoint(request: ChatRequest, api_key: str = Depends(get_api_key)):
    # Generate new session ID if PHP doesn't pass one
    session_id = request.session_id or str(uuid.uuid4())
    
    # Retrieve active or reset history
    history = get_and_clean_history(session_id)
    
    try:
        # Run execution chain
        result = rag_chain.invoke({
            "input": request.message,
            "chat_history": history
        })
        
        answer = result["answer"]
        
        # Append latest turn to the storage
        history.append(("human", request.message))
        history.append(("ai", answer))
        
        return ChatResponse(session_id=session_id, response=answer)
        
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/upload-knowledge-base")
async def upload_knowledge_base(file: UploadFile = File(...), api_key: str = Depends(get_api_key)):
    if not file.filename.endswith('.pdf'):
        raise HTTPException(status_code=400, detail="Only PDF files are allowed")
    
    # Save the file
    save_path = os.path.join("data", file.filename)
    os.makedirs("data", exist_ok=True)
    with open(save_path, "wb") as buffer:
        shutil.copyfileobj(file.file, buffer)
        
    # Rebuild chain
    try:
        build_rag_chain(save_path)
        global PDF_PATH
        PDF_PATH = save_path
        return {"status": "success", "message": f"Knowledge base updated to {file.filename}"}
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Failed to process PDF: {str(e)}")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)
