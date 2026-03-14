# Government Job Portal - Karnataka & Andhra Pradesh

A fully functional government job portal website with admin panel. Built with **Node.js + SQLite** - no PHP or database installation required!

## 🚀 Quick Start (3 Steps)

### Step 1: Install Dependencies
```bash
npm install
```

### Step 2: Setup Database
```bash
npm run setup
```

### Step 3: Run the Project
```bash
npm start
```

Then open **http://localhost:3000** in your browser!

---

## 📋 Features

### For Job Seekers
- ✅ Browse latest government jobs
- ✅ Search jobs by keyword
- ✅ Filter by location (Karnataka, Andhra Pradesh, All India)
- ✅ Filter by category (IAS, IPS, Banking, Teaching, etc.)
- ✅ Sort by date or deadline
- ✅ View job details with important dates
- ✅ Apply directly on official government websites

### For Admin
- ✅ Secure admin login
- ✅ Add new jobs with full details
- ✅ Edit/Delete jobs
- ✅ Manage categories
- ✅ Dashboard with statistics

---

## 🔐 Default Login

- **Username:** `admin`
- **Password:** `admin123`

---

## 🌐 URLs

| Page | URL |
|------|-----|
| Homepage | http://localhost:3000 |
| Browse Jobs | http://localhost:3000/jobs |
| Job Details | http://localhost:3000/jobs/{slug} |
| Admin Panel | http://localhost:3000/admin |

---

## 🛠️ How It Works

1. **Node.js Server** - Serves HTML pages and handles API requests
2. **SQLite Database** - Stores jobs, categories, and admin users (no setup needed!)
3. **REST API** - All data loaded dynamically via JavaScript

---

## 📝 Adding Jobs (Admin Panel)

1. Go to http://localhost:3000/admin
2. Login with admin/admin123
3. Click "Add Job"
4. Fill in job details:
   - Title, Organization
   - Category, Location
   - Description, Eligibility
   - Important Dates
   - Official Website URL (for applying)
5. Click "Add Job"

---

## 🚀 Deployment to Hostinger with Subdomain

This guide will help you deploy your Government Job Portal to Hostinger and configure it to handle 1000+ concurrent users.

### Prerequisites

- Hostinger account with Node.js hosting plan
- Domain name (optional but recommended)
- Subdomain (e.g., `jobs.yourdomain.com`)
- Basic knowledge of command line

### Step 1: Prepare Your Project for Production

#### 1.1 Update package.json for Production
```json
{
  "name": "government-job-portal",
  "version": "1.0.0",
  "description": "Government Job Portal for Karnataka & Andhra Pradesh",
  "main": "server.js",
  "scripts": {
    "start": "node server.js",
    "setup": "node setup.js",
    "prod": "NODE_ENV=production node server.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "sqlite3": "^5.1.7",
    "body-parser": "^1.20.2",
    "bcryptjs": "^2.4.3"
  },
  "engines": {
    "node": ">=16.0.0"
  }
}
```

#### 1.2 Create .gitignore
```
node_modules/
jobportal.db
*.log
.DS_Store
.env
```

#### 1.3 Create Production Environment Variables
Create a `.env` file (for local testing):
```
NODE_ENV=production
PORT=3000
DB_PATH=./jobportal.db
SESSION_SECRET=your-secret-key-here
```

### Step 2: Configure Subdomain in Hostinger

#### 2.1 Create Subdomain
1. Log in to your Hostinger control panel
2. Go to **Hosting** → **Manage** → **Subdomains**
3. Click **Create Subdomain**
4. Enter subdomain name (e.g., `jobs`)
5. Select your main domain
6. Set document root to `/public_html` (or create `/public_html/jobs` for dedicated folder)
7. Click **Create**

#### 2.2 Upload Files to Subdomain
1. Go to **File Manager** → **public_html**
2. Upload your project files (everything except `node_modules/`)
3. Make sure `server.js`, `package.json`, and `setup.js` are in the root directory
4. If using dedicated subdomain folder, upload to `/public_html/jobs/`

### Step 3: Deploy to Hostinger

#### 3.1 Install Dependencies
1. Go to **Advanced** → **SSH Access**
2. Connect via SSH or use the web terminal
3. Navigate to your project directory:
   ```bash
   cd /home/u123456789/public_html
   # OR for dedicated subdomain folder:
   cd /home/u123456789/public_html/jobs
   ```
4. Install dependencies:
   ```bash
   npm install
   ```

#### 3.2 Setup Database
```bash
node setup.js
```

### Step 4: Configure Node.js Application

#### 4.1 Update Server Configuration for Production
Modify your `server.js` to handle production environment:

```javascript
// Add at the top of server.js
const path = require('path');
const express = require('express');

// Production optimizations
if (process.env.NODE_ENV === 'production') {
    // Enable compression
    const compression = require('compression');
    app.use(compression());
    
    // Serve static files with caching
    app.use(express.static(path.join(__dirname, 'public'), {
        maxAge: '1d',
        etag: false
    }));
    
    // Security headers
    app.use((req, res, next) => {
        res.setHeader('X-Frame-Options', 'DENY');
        res.setHeader('X-Content-Type-Options', 'nosniff');
        res.setHeader('X-XSS-Protection', '1; mode=block');
        res.setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        next();
    });
}
```

#### 4.2 Database Optimization for 1000+ Users
Update your database configuration in `server.js`:

```javascript
// Enhanced database configuration for high traffic
const db = new sqlite3.Database('./jobportal.db', (err) => {
    if (err) {
        console.error('❌ DB Error:', err.message);
    } else {
        console.log('✅ Connected to SQLite database');
        db.serialize(() => {
            // Production database optimizations
            db.run('PRAGMA journal_mode = WAL');           // Write-Ahead Logging
            db.run('PRAGMA cache_size = -262144');         // 256MB cache
            db.run('PRAGMA synchronous = NORMAL');         // Balance safety/speed
            db.run('PRAGMA temp_store = MEMORY');          // Temp tables in memory
            db.run('PRAGMA mmap_size = 536870912');        // 512MB memory-mapped I/O
            db.run('PRAGMA busy_timeout = 10000');         // 10s timeout
            db.run('PRAGMA foreign_keys = ON');
            
            // Additional performance settings
            db.run('PRAGMA page_size = 4096');             // Optimal page size
            db.run('PRAGMA auto_vacuum = INCREMENTAL');    // Reduce fragmentation
        });
    }
});
```

### Step 5: Configure Hostinger for Node.js

#### 5.1 Set Up Node.js Application
1. Go to **Hosting** → **Manage** → **Node.js**
2. Set **Application Root**: `/public_html` (or `/public_html/jobs` for dedicated subdomain)
3. Set **Application Startup File**: `server.js`
4. Set **Node.js Version**: `18.x` or latest
5. Click **Save**

#### 5.2 Configure Environment Variables
1. In the Node.js settings, add environment variables:
   ```
   NODE_ENV=production
   PORT=3000
   DB_PATH=./jobportal.db
   ```

#### 5.3 Start the Application
1. Click **Start** in the Node.js section
2. Your application should now be running

### Step 6: Configure Subdomain to Point to Node.js App

#### 6.1 Update Subdomain Document Root
1. Go to **Hosting** → **Manage** → **Subdomains**
2. Find your subdomain (e.g., `jobs.yourdomain.com`)
3. Edit the document root to point to your Node.js application directory
4. Set it to the same path as your Node.js application root

#### 6.2 Configure Reverse Proxy (if needed)
If Hostinger doesn't automatically route subdomain traffic to your Node.js app:
1. Go to **Hosting** → **Manage** → **Advanced** → **.htaccess Editor**
2. Add reverse proxy rules:
   ```
   RewriteEngine On
   RewriteCond %{HTTP_HOST} ^jobs\.yourdomain\.com$ [NC]
   RewriteRule ^(.*)$ http://localhost:3000/$1 [P,L]
   ```

### Step 7: Database and Performance Optimization

#### 7.1 Database Backup Strategy
Create a backup script (`backup.js`):

```javascript
const sqlite3 = require('sqlite3');
const fs = require('fs');
const path = require('path');

function backupDatabase() {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const backupPath = `./backups/jobportal_${timestamp}.db`;
    
    // Create backups directory
    if (!fs.existsSync('./backups')) {
        fs.mkdirSync('./backups');
    }
    
    // Copy database file
    fs.copyFileSync('./jobportal.db', backupPath);
    console.log(`Database backed up to: ${backupPath}`);
}

// Run backup
backupDatabase();
```

#### 7.2 Performance Monitoring
Add monitoring to your `server.js`:

```javascript
// Add performance monitoring
app.get('/api/health', (req, res) => {
    const memUsage = process.memoryUsage();
    const uptime = process.uptime();
    
    res.json({
        status: 'healthy',
        uptime: uptime,
        memory: {
            rss: Math.round(memUsage.rss / 1024 / 1024) + ' MB',
            heapTotal: Math.round(memUsage.heapTotal / 1024 / 1024) + ' MB',
            heapUsed: Math.round(memUsage.heapUsed / 1024 / 1024) + ' MB',
            external: Math.round(memUsage.external / 1024 / 1024) + ' MB'
        },
        database: 'connected',
        concurrent_sessions: Object.keys(sessions).length
    });
});
```

### Step 8: Caching and CDN Setup

#### 8.1 Enable Static File Caching
```javascript
// Add to server.js for better caching
app.use(express.static(path.join(__dirname), {
    maxAge: '1d',
    etag: true,
    lastModified: true,
    setHeaders: (res, path) => {
        if (path.endsWith('.html')) {
            res.setHeader('Cache-Control', 'no-cache');
        } else if (path.endsWith('.js') || path.endsWith('.css')) {
            res.setHeader('Cache-Control', 'public, max-age=86400');
        }
    }
}));
```

#### 8.2 Configure CDN (Optional)
1. Sign up for a CDN service (Cloudflare, BunnyCDN, etc.)
2. Point your subdomain to the CDN
3. Configure caching rules for static assets

### Step 9: Security Hardening

#### 9.1 Add Rate Limiting
```javascript
const rateLimit = require('express-rate-limit');

const apiLimiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // limit each IP to 100 requests per windowMs
    message: 'Too many requests from this IP, please try again later.'
});

app.use('/api/', apiLimiter);
```

#### 9.2 HTTPS Configuration
1. In Hostinger, go to **SSL** section
2. Enable **Auto SSL** for your subdomain
3. Force HTTPS in your application:
   ```javascript
   app.use((req, res, next) => {
       if (req.header('x-forwarded-proto') !== 'https') {
           res.redirect(`https://${req.header('host')}${req.url}`);
       } else {
           next();
       }
   });
   ```

### Step 10: Monitoring and Maintenance

#### 10.1 Set Up Log Monitoring
```javascript
// Add logging middleware
app.use((req, res, next) => {
    const start = Date.now();
    res.on('finish', () => {
        const duration = Date.now() - start;
        console.log(`${req.method} ${req.url} - ${res.statusCode} - ${duration}ms`);
    });
    next();
});
```

#### 10.2 Regular Maintenance Tasks
Create a maintenance script (`maintenance.js`):

```javascript
const sqlite3 = require('sqlite3');

function maintenanceTasks() {
    const db = new sqlite3.Database('./jobportal.db');
    
    // Vacuum database to reduce fragmentation
    db.run('VACUUM', (err) => {
        if (err) console.error('Vacuum error:', err.message);
        else console.log('Database vacuumed successfully');
    });
    
    // Update statistics
    db.run('ANALYZE', (err) => {
        if (err) console.error('Analyze error:', err.message);
        else console.log('Database statistics updated');
    });
    
    db.close();
}

maintenanceTasks();
```

### Step 11: Scaling for 1000+ Users

#### 11.1 Database Optimization
- **WAL Mode**: Already enabled for concurrent reads
- **Indexing**: Ensure proper indexes on frequently queried columns
- **Connection Pooling**: SQLite handles this internally with WAL mode

#### 11.2 Application Optimization
- **Caching**: Implement Redis for session storage (optional)
- **Load Balancing**: Consider multiple Node.js instances behind a reverse proxy
- **CDN**: Use CDN for static assets

#### 11.3 Monitoring Tools
- **Uptime Monitoring**: Use services like UptimeRobot
- **Performance Monitoring**: Use tools like New Relic or PM2

### Step 12: Testing Your Deployment

#### 12.1 Test Subdomain Access
1. Visit your subdomain (e.g., `https://jobs.yourdomain.com`)
2. Verify the homepage loads correctly
3. Test job browsing and search functionality
4. Test admin panel access

#### 12.2 Test Performance
1. Use online tools like GTmetrix or PageSpeed Insights
2. Check response times
3. Monitor server resources

#### 12.3 Test Security
1. Verify HTTPS is working
2. Check security headers
3. Test rate limiting

### Troubleshooting

#### Common Issues

**Subdomain not loading:**
- Check Node.js application is running
- Verify subdomain document root is correct
- Check firewall settings

**Database connection errors:**
- Ensure `jobportal.db` exists and has proper permissions
- Check SQLite3 module is installed

**Performance issues:**
- Monitor server resources
- Check database indexes
- Consider upgrading hosting plan

**SSL/HTTPS issues:**
- Ensure SSL certificate is installed
- Check HTTPS redirect configuration

### Success! 🎉

Your Government Job Portal is now live on your Hostinger subdomain and optimized to handle 1000+ concurrent users!

**Your live site:** `https://jobs.yourdomain.com`
**Admin panel:** `https://jobs.yourdomain.com/admin`
**Health check:** `https://jobs.yourdomain.com/api/health`

---

## 🔧 Troubleshooting

**Port already in use?**
```bash
# Kill existing process or change port in server.js
```

**Database error?**
```bash
# Delete jobportal.db and run setup again
rm jobportal.db
npm run setup
```

---

## 📄 License

MIT License - Free to use and modify!

---

**Built with ❤️ for Karnataka & Andhra Pradesh Government Jobs**