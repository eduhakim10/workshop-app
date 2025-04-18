
# Coding Challenge: Sales Dashboard with Next.js & FastAPI

## Overview

This project implements a sales dashboard application using Next.js for the frontend and FastAPI for the backend API. The application visualizes data from a provided `dummyData.json` file, showcasing sales representative information, deal statuses, and integrates a generative AI component for question answering.

## Features

* **Frontend (Next.js):**  
  Displays sales representative data in cards, interactive charts (pie and bar charts) visualizing deal statuses, and a form for interacting with the generative AI. Uses Tailwind CSS for styling.

* **Backend (FastAPI):**  
  Serves sales data via RESTful API endpoints. Includes an endpoint to interact with a generative AI model (e.g., OpenAI).

* **Generative AI Integration:**  
  Allows users to ask questions related to the sales data and receive AI-generated answers.

* **Data Visualization:**  
  Clear visualization of sales data using charts.

* **Search and Filtering:**  
  Enables searching of sales representatives by name and filtering by role.

## Technologies Used

* **Frontend:** Next.js, React, Recharts, Chart.js, Tailwind CSS  
* **Backend:** FastAPI, Uvicorn (server)  
* **Generative AI:** OpenAI API or similar  

## Setup and Installation

### Backend (FastAPI)

1. Clone the repository:  
   `git clone <repository_url>`

2. Navigate to backend directory:  
   `cd backend`

3. Create virtual environment:  

   - On macOS/Linux:  
     ```bash
     python3 -m venv venv
     source venv/bin/activate
     ```
   
   - On Windows:   
     ```powershell
     python -m venv venv
     .\venv\Scripts\activate
     ```

4. Install dependencies:   
   `pip install -r requirements.txt`

5. Set environment variables:

   Rename `.env_example` to `.env` in backend directory then add your OpenAI or Google Generative AI key:

   ```
   OPENAI_API_KEY=your_generativeai_api_json_key_here
   ```

6. Run backend server:

    ```bash
    uvicorn main:app --reload --host 0.0.0.0 --port 8000
    ```

### Frontend (Next.js)

1. Navigate to frontend directory:

    ```bash
    cd frontend
    ```

2. Install dependencies:

    ```bash
    npm install 
    # or yarn install if you use yarn 
    ```

3. Run development server:

    ```bash 
    npm run dev 
    # or yarn dev 
    ```

4. Open browser at [http://localhost:3000](http://localhost:3000)

---

Kalau mau aku tambahin bagian lain atau revisi tinggal bilang ya bro! 🚀
