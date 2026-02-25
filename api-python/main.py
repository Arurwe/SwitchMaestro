from fastapi import FastAPI
from app.api.endpoints import devices 
from app.api.endpoints import terminal 
app = FastAPI(title="SwitchMaestro API")



app.include_router(devices.router, prefix="/api", tags=["Devices"])
app.include_router(terminal.router, prefix="/api", tags=["Terminal"])

@app.get("/")
def read_root():
    return {"app": "SwitchMaestro API"}


@app.get("/api/health")
def health_check():
    return {
        "api_status": "ok",
    }