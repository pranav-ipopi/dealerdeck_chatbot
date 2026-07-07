# Cloudflare Micro-AI Training Guide

This document is your master blueprint for training a YOLOv8-Nano model to visually detect Cloudflare Turnstile checkboxes and loading spinners. By following this guide, you will create a highly optimized, 3MB AI model that runs in pure Python (via `onnxruntime`) in under 15 milliseconds.

---

## Phase 1: Uploading & Labeling (Roboflow)

Since you already have the 100+ screenshots of all the various states (loading, broken, checkbox), you are ready to label. 

1. **Create Project**: 
   - Go to [Roboflow.com](https://roboflow.com/) and create a free account.
   - Click **Create New Project** -> **Object Detection (Bounding Box)** -> `cloudflare_widget`.

2. **Upload Images**: Drag and drop all your collected screenshots into the project.

3. **The Golden Rule of Labeling**:
   You must create exactly **two classes** (labels). Draw the boxes around the **entire white rectangular widget border**.
   - `cf_checkbox`: Draw the box around the entire widget when it shows the empty checkbox and "Verify you are human".
   - `cf_loading`: Draw the box around the entire widget when it shows the green dotted spinner and "Verifying...".

   > [!IMPORTANT]
   > Do **NOT** draw boxes on broken pages (like the tiny black arc on `dftsocial.com`) or pages where the widget is missing completely (like `altbookmark.com`). Leaving those images completely blank teaches the AI what a "stuck" page looks like!

4. **Generate & Export**:
   - Click **Generate New Version** (Add light Brightness/Blur augmentations).
   - Click **Export Dataset** -> Format: **YOLOv8** -> Select **Show download code**.

---

## Phase 2: Training (Google Colab)

1. **Setup Colab**:
   - Go to [Google Colab](https://colab.research.google.com/).
   - Click **Runtime > Change runtime type** -> Select **T4 GPU**.

2. **Install YOLOv8**:
   ```python
   !pip install ultralytics roboflow
   ```

3. **Download Your Dataset** (Paste the code from Roboflow):
   ```python
   from roboflow import Roboflow
   rf = Roboflow(api_key="YOUR_PRIVATE_API_KEY")
   project = rf.workspace("your-workspace").project("cloudflare_widget")
   version = project.version(1)
   dataset = version.download("yolov8")
   ```

4. **Start Training**:
   ```python
   !yolo task=detect mode=train model=yolov8n.pt data={dataset.location}/data.yaml epochs=150 imgsz=640
   ```

---

## Phase 3: Exporting to Micro Format (ONNX)

1. **Export the Model**:
   ```python
   from ultralytics import YOLO
   model = YOLO('/content/runs/detect/train/weights/best.pt')
   model.export(format='onnx', imgsz=640)
   ```

2. **Download `best.onnx`** to your PC/VPS.

---

## Phase 4: Python VPS Integration (State Machine)

Instead of editing your main script right away, we will put the AI logic in a standalone test file. 

The AI script will feature a **State Machine** that handles:
- Seeing `cf_loading` and waiting.
- Seeing `cf_checkbox`, clicking it, and incrementing a `click_count`.
- Refreshing the page (`page.reload()`) if it clicks 3 times without success, or if the AI sees no widget at all for 10 seconds (meaning it's stuck on the "Why is this taking longer?" screen).

We have created the code for this logic in `playwright_worker/methods/ai_cloudflare_handler.py`. Once you have the `best.onnx` file, put it in the `playwright_worker/cf_clearence` folder and you can run that test script!

---

## Phase 5: Production Deployment & Dependencies

When you are ready to deploy this logic to your production VPS, you must install the lightweight dependencies that allow Python to process the image and communicate with the ONNX model.

Run the following command on your VPS:
```bash
pip install onnxruntime opencv-python numpy
```

*(Note: If your VPS is a headless Linux environment without a GUI, you may need to use `opencv-python-headless` instead of `opencv-python` to avoid missing UI library errors).*
