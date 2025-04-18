# Coding Challenge: Sales Dashboard with Next.js & FastAPI

## Overview

This project implements a sales dashboard application using Next.js for the frontend and FastAPI for the backend API.  The application visualizes data from a provided `dummyData.json` file, showcasing sales representative information, deal statuses, and integrates a generative AI component for question answering.

## Features

* **Frontend (Next.js):**  Displays sales representative data in cards, interactive charts (pie and bar charts) visualizing deal statuses, and a form for interacting with the generative AI.  Uses Tailwind CSS for styling.
* **Backend (FastAPI):** Serves sales data via a RESTful API endpoint. Includes an endpoint for interacting with a generative AI model (e.g., OpenAI).
* **Generative AI Integration:** Allows users to ask questions related to the sales data and receive AI-generated answers.
* **Data Visualization:** Clear and concise visualization of sales data using charts.
* **Search and Filtering:**  Allows users to search for sales representatives by name and filter by role.


## Technologies Used

* **Frontend:** Next.js, React, recharts, chart.js, Tailwind CSS
* **Backend:** FastAPI, Uvicorn (for running the server)
* **Generative AI:** OpenAI API (or similar)


## Setup and Installation
### Backend (FastAPI)

1. **Clone the repository:**  
   `git clone <repository_url>`

2. **Navigate to the backend directory:**  
   `cd backend`

3. **Create a virtual environment:**  
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

4. **Install dependencies:**  
   `pip install -r requirements.txt`

5. **Set environment variables:**  
   - Rename `.env_example` to `.env` in the backend directory.
   - Add your Google Generative AI credentials:  

    ```env
    OPENAI_API_KEY=your_path_generative_ai_api_json_here
    ```
5.  **Run the backend:**:
  ```bash
      uvicorn main:app --reload --host 0.0.0.0 --port 8000
       ```

### Frontend (Next.js)

1. Navigate to the frontend directory:  
   cd frontend

2. Install dependencies using npm or yarn:

   npm install  

    or  

   yarn install

3. Run the development server:

   npm run dev  

    or  

   yarn dev


### Notes
- Make sure you have Node.js and Python installed on your machine.
- The Google Generative AI API key is required for AI features in this project.
- Adjust <repository_url> with your actual repository link.


