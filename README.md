# Nigerian Legal Assistant

An AI-powered legal research assistant specialising in Nigerian law. Upload PDF documents (Acts, Regulations, Constitutions, Court Rules, etc.) and ask questions in plain language — the assistant retrieves relevant legal context and provides accurate, cited answers.

---

## Features

- **AI Chat Interface** — Conversational chat powered by GPT-4o, grounded exclusively in uploaded legal documents
- **Document Ingestion** — Upload PDF law documents; they are automatically chunked, embedded, and stored for semantic search
- **Semantic Search (RAG)** — Uses vector similarity search (pgvector) to retrieve the most relevant legal passages before answering
- **Conversation History** — Maintains multi-turn conversations per user, stored and resumable
- **Admin Panel** — Filament-powered admin for managing law documents (upload, reprocess, delete)
- **Google OAuth Login** — Users authenticate via Google
- **Jurisdiction Support** — Federal, FCT, and all 36 Nigerian states
- **Document Categories** — Acts, Regulations, Decrees, Constitutions, Court Rules, Criminal/Civil/Commercial/Family/Labour law, and more

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 (PHP 8.4) |
| AI / LLM | OpenAI GPT-4o via `laravel/ai` |
| Embeddings | OpenAI `text-embedding-3-small` |
| Vector Database | PostgreSQL 16 + pgvector |
| Admin Panel | Filament v5 |
| Frontend | Livewire v4 + Tailwind CSS v4 |
| Queue / Cache | Redis |
| PDF Parsing | smalot/pdfparser |
| Auth | Laravel Socialite (Google OAuth) |
| Asset Bundling | Vite |

---

## Requirements

- PHP 8.4+
- Composer
- Node.js & npm
- PostgreSQL 16 with the **pgvector** extension
- Redis
- An **OpenAI API key** (for GPT-4o and embeddings)
- A **Google OAuth app** (Client ID + Secret) for authentication

---

## Installation & Setup

### Option A — Docker (recommended)

1. **Clone the repository**

   ```bash
   git clone <repo-url> nigerian-law-assistant
   cd nigerian-law-assistant
   ```

2. **Copy and configure the environment file**

   ```bash
   cp .env.example .env
   ```

   Open `.env` and fill in:

   ```env
   OPENAI_API_KEY=sk-...

   GOOGLE_CLIENT_ID=your-google-client-id
   GOOGLE_CLIENT_SECRET=your-google-client-secret
   GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

   # Docker data folder (created automatically)
   DOCKER_CONFIG_FOLDER=~/.docker-config/nigerian-law-assistant
   ```

3. **Start the Docker stack**

   ```bash
   docker compose up -d
   ```

   This starts:
   - `webserver` — Laravel app on port 8000
   - `postgres` — PostgreSQL 16 with pgvector on port 5432
   - `redis` — Redis on port 6379
   - `queue` — Queue worker for PDF processing (`law-ingestion` queue)

4. **Run setup inside the container**

   ```bash
   docker compose exec webserver composer run setup
   ```

   This installs PHP and Node dependencies, generates the app key, runs migrations, and builds frontend assets.

5. **Create the first admin user**

   ```bash
   docker compose exec webserver php artisan make:filament-user
   ```

6. **Visit the app**

   - Chat: [http://localhost:8000](http://localhost:8000)
   - Admin: [http://localhost:8000/admin](http://localhost:8000/admin)

---

### Option B — Local (without Docker)

1. **Clone and install dependencies**

   ```bash
   git clone <repo-url> nigerian-law-assistant
   cd nigerian-law-assistant
   composer install
   npm install
   ```

2. **Configure environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Update `.env` with your local database, Redis, and API credentials:

   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=nigerian_law
   DB_USERNAME=your_db_user
   DB_PASSWORD=your_db_password

   REDIS_HOST=127.0.0.1

   OPENAI_API_KEY=sk-...

   GOOGLE_CLIENT_ID=your-google-client-id
   GOOGLE_CLIENT_SECRET=your-google-client-secret
   GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback
   ```

3. **Enable pgvector on your PostgreSQL database**

   ```sql
   CREATE EXTENSION IF NOT EXISTS vector;
   ```

4. **Run migrations and build assets**

   ```bash
   php artisan migrate
   npm run build
   ```

5. **Create an admin user**

   ```bash
   php artisan make:filament-user
   ```

6. **Start all services**

   ```bash
   composer run dev
   ```

   This concurrently runs the Laravel server, queue worker, log watcher, and Vite dev server.

---

## Using the Application

### Uploading Law Documents (Admin)

1. Log in to the admin panel at `/admin`
2. Navigate to **Law Documents → New Document**
3. Fill in the title, category, jurisdiction, source, and year
4. Upload a PDF (max 50 MB)
5. Save — the document is queued for processing automatically

The queue worker extracts text from the PDF, splits it into chunks, generates vector embeddings via OpenAI, and stores them in PostgreSQL. Status updates from `pending → processing → completed` (or `failed`).

You can **Reprocess** any document from the table actions if needed.

### Chatting with the Assistant

1. Log in via Google at `/login`
2. Ask any question about Nigerian law in plain English
3. The assistant searches the knowledge base semantically and answers with citations to the specific law and section
4. Conversations are saved and listed in the sidebar — click any to resume

---

## Running Tests

```bash
php artisan test --compact
```

---

## Development Commands

| Command | Description |
|---|---|
| `composer run dev` | Start server, queue, logs, and Vite concurrently |
| `composer run setup` | Full first-time setup (install, migrate, build) |
| `npm run build` | Build production frontend assets |
| `npm run dev` | Start Vite dev server with HMR |
| `vendor/bin/pint` | Fix PHP code style |
| `php artisan queue:listen --queue=law-ingestion,default` | Run queue worker manually |

---

## License

MIT
