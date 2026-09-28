# Deploying SkillSprout to Vercel

This guide provides complete instructions for deploying the **SkillSprout** application onto [Vercel](https://vercel.com) using the serverless PHP runtime (`vercel-php`).

---

## 1. Architecture Overview on Vercel

```
                                      ┌─────────────────────────────────┐
                                      │        Vercel Global Edge       │
                                      │        (CDN / Anycast)          │
                                      └────────────────┬────────────────┘
                                                       │
                           ┌───────────────────────────┴───────────────────────────┐
                           ▼                                                       ▼
                [ Static Asset Routes ]                                 [ Dynamic Page Requests ]
            /assets/*, /uploads/*, favicon.ico                             /* (Rewrite Catch-all)
                           │                                                       │
                           ▼                                                       ▼
                 Served instantly by CDN                                    [ api/index.php ]
                                                                       Serverless Function Handler
                                                                                   │
                                                   ┌───────────────────────────────┴───────────────────────────────┐
                                                   ▼                                                               ▼
                                       [ Session Save Handler ]                                          [ Modular PHP Route ]
                                      Stateful sessions stored in                                  auth/login.php, tasks/tasks.php,
                                      Cloud MySQL `sessions` table                                 tasks/create_task.php, etc.
                                                   │                                                               │
                                                   └───────────────────────────────┬───────────────────────────────┘
                                                                                   │
                                                                                   ▼
                                                                     [ Remote Cloud MySQL DB ]
                                                                     (TiDB, Aiven, Railway, RDS)
```

1. **Serverless PHP Runtime**: Configured in `vercel.json` with `vercel-php@0.9.0` to run PHP in AWS Lambda serverless containers.
2. **Unified Front Controller (`api/index.php`)**: Intercepts requests, serves static fallbacks if needed, and routes directly to the requested application script.
3. **Database-Backed Sessions (`config/session.php`)**: Because serverless instances are stateless and ephemeral, user sessions are saved in the cloud database `sessions` table. This keeps users logged in across different function invocations.
4. **Environment-Aware `BASE_URL`**: Resolves to `/` on Vercel and `/SkillSprout/` on local XAMPP automatically.

---

## 2. Prerequisites

1. A **GitHub** account and repository containing the SkillSprout code.
2. A **Vercel** account ([https://vercel.com](https://vercel.com)).
3. A **Remote MySQL Database** (since Vercel is serverless and does not run local MySQL).

### Recommended Free Cloud MySQL Providers:
- **TiDB Cloud (Serverless)**: 5GB free storage, instant setup ([https://tidbcloud.com](https://tidbcloud.com)).
- **Aiven for MySQL**: Free tier available ([https://aiven.io](https://aiven.io)).
- **Railway**: Free starter credits ([https://railway.app](https://railway.app)).
- **Clever Cloud**: Free MySQL add-on ([https://clever-cloud.com](https://clever-cloud.com)).

---

## 3. Step-by-Step Deployment Guide

### Step 1: Initialize Your Cloud Database

1. Create a MySQL database instance on your chosen cloud provider (e.g. TiDB Cloud).
2. Note your connection details:
   - **Host**: (e.g., `gateway01.us-east-1.prod.aws.tidbcloud.com`)
   - **Port**: (e.g., `4000` for TiDB, or `3306` for standard MySQL)
   - **Username**: (e.g., `xxxxxx.root`)
   - **Password**: Your database password
   - **Database Name**: `workpoint`
3. Import the database schema:
   - Open your cloud database SQL console or connect via MySQL Workbench / phpMyAdmin.
   - Run the contents of `database/database.sql` to generate all tables (`users`, `tasks`, `applications`, `submissions`, `sessions`).

---

### Step 2: Push Your Code to GitHub

From your local project folder:

```bash
git add .
git commit -m "Configure SkillSprout for Vercel serverless deployment"
git push origin main
```

---

### Step 3: Import Project into Vercel

1. Log into your [Vercel Dashboard](https://vercel.com).
2. Click **"Add New..."** > **"Project"**.
3. Select your **SkillSprout** GitHub repository and click **Import**.
4. In the project configuration screen:
   - **Framework Preset**: Leave as *Other*.
   - **Root Directory**: `./` (leave default).

---

### Step 4: Configure Environment Variables in Vercel

Before clicking **Deploy**, expand the **"Environment Variables"** section and add the following keys:

| Variable Name | Example Value | Description |
|---|---|---|
| `DB_HOST` | `gateway01.us-east-1.prod.aws.tidbcloud.com` | Cloud MySQL host address |
| `DB_USER` | `xxxxxx.root` | Database username |
| `DB_PASSWORD` | `your_cloud_db_password` | Database password |
| `DB_NAME` | `workpoint` | Database name |
| `DB_PORT` | `4000` (or `3306`) | Database port |
| `DB_SSL` | `true` | Required for TiDB / Aiven / PlanetScale |
| `BASE_URL` | `/` | Root URL for Vercel deployment |
| `SESSION_DRIVER`| `database` | Enables MySQL serverless session persistence |

*(Optional)* If your cloud provider gave you a single connection string:
- `DATABASE_URL`: `mysql://username:password@hostname:port/workpoint?ssl-mode=REQUIRED`

---

### Step 5: Deploy

1. Click **"Deploy"**.
2. Vercel will build the project and deploy the serverless functions.
3. Once finished, Vercel will assign a live URL (e.g., `https://skillsprout.vercel.app`).

---

## 4. Verification Checklist

1. **Homepage**: Open your live Vercel URL. The landing page, hero image, and public header/footer should render cleanly.
2. **Registration**: Go to `/auth/register.php` (or `/register`), create a test account, and verify the 100 WP initial balance.
3. **Login & Session**: Log in with your new credentials. Verify that you are redirected to `/user/dashboard.php` and your session remains intact across refreshes and page transitions.
4. **Task Creation**: Go to `/tasks/create_task.php` (or `/create-task`), create a task with a 20 WP reward, and verify that your wallet balance is immediately deducted to 80 WP.
5. **Static Assets**: Inspect the browser console (F12) to verify `assets/css/style.css` and `assets/js/*.js` load with HTTP 200 without routing errors.

---

## 5. Local Development Compatibility

The repository remains 100% compatible with local **XAMPP / Apache**:
- Visiting `http://localhost/SkillSprout/` continues to use your local MySQL database (`localhost:3306`), local file sessions, and the `/SkillSprout/` base URL automatically.
- No local files or `.htaccess` redirects were removed or broken.
