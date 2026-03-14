const express = require('express');
const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcryptjs');
const path = require('path');
const crypto = require('crypto');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));
app.use(express.static(path.join(__dirname)));

// CORS headers for API
app.use((req, res, next) => {
    res.header('Access-Control-Allow-Origin', '*');
    res.header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    res.header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    if (req.method === 'OPTIONS') return res.sendStatus(200);
    next();
});

// Database with optimized settings for 1000+ concurrent users
const db = new sqlite3.Database('./jobportal.db', (err) => {
    if (err) {
        console.error('❌ DB Error:', err.message);
    } else {
        console.log('✅ Connected to SQLite database');
        db.serialize(() => {
            db.run('PRAGMA foreign_keys = ON');
            db.run('PRAGMA journal_mode = WAL');       // Write-Ahead Logging for concurrency
            db.run('PRAGMA cache_size = -64000');       // 64MB cache
            db.run('PRAGMA synchronous = NORMAL');      // Good balance of safety/speed
            db.run('PRAGMA temp_store = MEMORY');       // Temp tables in memory
            db.run('PRAGMA mmap_size = 268435456');     // 256MB memory-mapped I/O
            db.run('PRAGMA busy_timeout = 5000');       // 5s timeout for lock contention
        });
        
        // Auto-create tables if they don't exist (safety net)
        initializeDatabase();
    }
});

function initializeDatabase() {
    db.run(`CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, slug TEXT NOT NULL UNIQUE,
        description TEXT, icon TEXT, job_count INTEGER DEFAULT 0, is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);
    db.run(`CREATE TABLE IF NOT EXISTS sub_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT, category_id INTEGER, name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE, description TEXT, job_count INTEGER DEFAULT 0, is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    )`);
    db.run(`CREATE TABLE IF NOT EXISTS jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, slug TEXT NOT NULL UNIQUE,
        organization TEXT NOT NULL, category_id INTEGER, sub_category_id INTEGER, description TEXT NOT NULL,
        eligibility TEXT, location TEXT, salary_min TEXT, salary_max TEXT, salary_description TEXT,
        job_type TEXT DEFAULT 'Full Time', vacancy_count INTEGER, application_fee TEXT,
        form_start_date DATE, form_end_date DATE, exam_date DATE, admit_card_date DATE, result_date DATE,
        official_website TEXT, how_to_apply TEXT, important_links TEXT,
        is_featured INTEGER DEFAULT 0, is_active INTEGER DEFAULT 1, views INTEGER DEFAULT 0,
        created_by INTEGER, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )`);
    db.run(`CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL, full_name TEXT, role TEXT DEFAULT 'admin', is_active INTEGER DEFAULT 1,
        last_login DATETIME, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);
    db.run(`CREATE TABLE IF NOT EXISTS applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT, job_id INTEGER NOT NULL, applicant_name TEXT NOT NULL,
        email TEXT NOT NULL, phone TEXT, qualification TEXT, experience TEXT, cover_letter TEXT,
        status TEXT DEFAULT 'pending', applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
    )`);
    db.run(`CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT, key TEXT NOT NULL UNIQUE, value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`);
    // Performance indexes
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_active ON jobs(is_active, form_end_date)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_category ON jobs(category_id)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_slug ON jobs(slug)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_created ON jobs(created_at DESC)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_featured ON jobs(is_featured, is_active)`);
}

// Session storage (in-memory with auto-cleanup)
const sessions = {};
setInterval(() => {
    const now = Date.now();
    for (const id in sessions) {
        if (now - sessions[id].createdAt > 24 * 60 * 60 * 1000) { // 24h expiry
            delete sessions[id];
        }
    }
}, 60 * 60 * 1000); // Cleanup every hour

// Helper Functions
function generateSlug(title) {
    return title.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') + '-' + Date.now().toString(36);
}

function generateSessionId() {
    return crypto.randomBytes(32).toString('hex');
}

function isAuthenticated(req, res) {
    const sessionId = req.headers.authorization;
    if (sessionId && sessions[sessionId]) {
        req.session = sessions[sessionId];
        return true;
    }
    res.status(401).json({ error: 'Unauthorized - Please log in again' });
    return false;
}

// ==================== PUBLIC API ROUTES ====================

// Get all jobs (for homepage)
app.get('/api/jobs', (req, res) => {
    const { search, category, location, job_type, sort = 'newest', limit = 10 } = req.query;
    
    let query = `SELECT j.*, c.name as category_name FROM jobs j LEFT JOIN categories c ON j.category_id = c.id WHERE j.is_active = 1`;
    const params = [];
    
    const today = new Date().toISOString().split('T')[0];
    query += ` AND j.form_end_date >= ?`;
    params.push(today);
    
    if (search) {
        query += ` AND (j.title LIKE ? OR j.organization LIKE ? OR j.description LIKE ?)`;
        params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }
    if (category) {
        query += ` AND j.category_id = ?`;
        params.push(category);
    }
    if (location) {
        query += ` AND j.location LIKE ?`;
        params.push(`%${location}%`);
    }
    if (job_type) {
        query += ` AND j.job_type = ?`;
        params.push(job_type);
    }
    
    switch(sort) {
        case 'oldest': query += ' ORDER BY j.created_at ASC'; break;
        case 'deadline': query += ' ORDER BY j.form_end_date ASC'; break;
        case 'title': query += ' ORDER BY j.title ASC'; break;
        default: query += ' ORDER BY j.is_featured DESC, j.created_at DESC';
    }
    
    query += ` LIMIT ?`;
    params.push(parseInt(limit) || 10);
    
    db.all(query, params, (err, rows) => {
        if (err) {
            console.error('GET /api/jobs error:', err.message);
            res.status(500).json({ error: 'Failed to load jobs' });
        } else {
            res.json(rows || []);
        }
    });
});

// Get single job by slug
app.get('/api/jobs/:slug', (req, res) => {
    db.get(`SELECT j.*, c.name as category_name FROM jobs j LEFT JOIN categories c ON j.category_id = c.id WHERE j.slug = ?`, 
        [req.params.slug], (err, row) => {
        if (err) {
            res.status(500).json({ error: err.message });
        } else if (!row) {
            res.status(404).json({ error: 'Job not found' });
        } else {
            db.run(`UPDATE jobs SET views = views + 1 WHERE id = ?`, [row.id]);
            res.json(row);
        }
    });
});

// Get categories (public)
app.get('/api/categories', (req, res) => {
    db.all('SELECT * FROM categories WHERE is_active = 1 ORDER BY name', (err, rows) => {
        if (err) res.status(500).json({ error: err.message });
        else res.json(rows || []);
    });
});

// Get all jobs with pagination (for jobs listing page)
app.get('/api/all-jobs', (req, res) => {
    const { search, category, location, job_type, sort = 'newest', page = 1 } = req.query;
    const limit = 15;
    const offset = (page - 1) * limit;
    
    let whereClause = `WHERE j.is_active = 1`;
    const params = [];
    
    const today = new Date().toISOString().split('T')[0];
    whereClause += ` AND j.form_end_date >= ?`;
    params.push(today);
    
    if (search) {
        whereClause += ` AND (j.title LIKE ? OR j.organization LIKE ? OR j.description LIKE ?)`;
        params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }
    if (category) {
        whereClause += ` AND j.category_id = ?`;
        params.push(category);
    }
    if (location) {
        whereClause += ` AND j.location LIKE ?`;
        params.push(`%${location}%`);
    }
    if (job_type) {
        whereClause += ` AND j.job_type = ?`;
        params.push(job_type);
    }
    
    db.get(`SELECT COUNT(*) as total FROM jobs j ${whereClause}`, params, (err, countResult) => {
        if (err) {
            res.status(500).json({ error: err.message });
            return;
        }
        
        let orderClause;
        switch(sort) {
            case 'oldest': orderClause = 'ORDER BY j.created_at ASC'; break;
            case 'deadline': orderClause = 'ORDER BY j.form_end_date ASC'; break;
            case 'title': orderClause = 'ORDER BY j.title ASC'; break;
            default: orderClause = 'ORDER BY j.is_featured DESC, j.created_at DESC';
        }
        
        const query = `SELECT j.*, c.name as category_name FROM jobs j 
            LEFT JOIN categories c ON j.category_id = c.id ${whereClause} ${orderClause} LIMIT ? OFFSET ?`;
        
        db.all(query, [...params, limit, offset], (err, rows) => {
            if (err) res.status(500).json({ error: err.message });
            else res.json({ 
                jobs: rows || [], 
                total: countResult ? countResult.total : 0, 
                pages: Math.ceil((countResult ? countResult.total : 0) / limit),
                current_page: parseInt(page)
            });
        });
    });
});

// Get site stats (public)
app.get('/api/stats', (req, res) => {
    const today = new Date().toISOString().split('T')[0];
    db.get(`SELECT 
        (SELECT COUNT(*) FROM jobs WHERE is_active = 1 AND form_end_date >= ?) as total_jobs,
        (SELECT COUNT(*) FROM categories WHERE is_active = 1) as total_categories,
        (SELECT COUNT(*) FROM jobs WHERE is_active = 1 AND form_end_date >= ?) as active_jobs`,
        [today, today],
        (err, row) => {
        if (err) res.status(500).json({ error: err.message });
        else res.json(row || { total_jobs: 0, total_categories: 0, active_jobs: 0 });
    });
});

// ==================== ADMIN ROUTES ====================

// Admin Login
app.post('/api/admin/login', (req, res) => {
    const { username, password } = req.body;
    
    if (!username || !password) {
        return res.status(400).json({ error: 'Username and password are required' });
    }
    
    db.get(`SELECT * FROM admin_users WHERE username = ? AND is_active = 1`, [username], (err, user) => {
        if (err) {
            res.status(500).json({ error: 'Server error' });
        } else if (!user) {
            res.status(401).json({ error: 'Invalid credentials' });
        } else if (bcrypt.compareSync(password, user.password)) {
            const sessionId = generateSessionId();
            sessions[sessionId] = { 
                id: user.id, 
                username: user.username, 
                full_name: user.full_name, 
                role: user.role,
                createdAt: Date.now()
            };
            db.run(`UPDATE admin_users SET last_login = datetime('now') WHERE id = ?`, [user.id]);
            res.json({ 
                success: true, 
                sessionId, 
                user: { username: user.username, full_name: user.full_name, role: user.role } 
            });
        } else {
            res.status(401).json({ error: 'Invalid password' });
        }
    });
});

// Admin Session Check
app.get('/api/admin/session', (req, res) => {
    const sessionId = req.headers.authorization;
    if (sessionId && sessions[sessionId]) {
        res.json({ valid: true, user: sessions[sessionId] });
    } else {
        res.status(401).json({ valid: false });
    }
});

// Admin Logout
app.post('/api/admin/logout', (req, res) => {
    const sessionId = req.headers.authorization;
    if (sessionId && sessions[sessionId]) {
        delete sessions[sessionId];
    }
    res.json({ success: true });
});

// Get Admin Dashboard Stats
app.get('/api/admin/stats', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const today = new Date().toISOString().split('T')[0];
    db.get(`SELECT 
        (SELECT COUNT(*) FROM jobs) as total_jobs,
        (SELECT COUNT(*) FROM jobs WHERE is_active = 1) as active_jobs,
        (SELECT COUNT(*) FROM jobs WHERE is_active = 0) as inactive_jobs,
        (SELECT COUNT(*) FROM jobs WHERE is_featured = 1) as featured_jobs,
        (SELECT COUNT(*) FROM categories) as total_categories,
        (SELECT COUNT(*) FROM sub_categories) as total_subcategories,
        (SELECT COUNT(*) FROM applications) as total_applications,
        (SELECT COUNT(*) FROM jobs WHERE is_active = 1 AND form_end_date >= '${today}') as live_jobs,
        (SELECT SUM(views) FROM jobs) as total_views,
        (SELECT SUM(vacancy_count) FROM jobs WHERE is_active = 1) as total_vacancies`, 
        (err, row) => {
        if (err) {
            console.error('Stats error:', err.message);
            res.status(500).json({ error: err.message });
        } else {
            res.json(row || {});
        }
    });
});

// Get All Jobs (Admin - with full details)
app.get('/api/admin/jobs', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const { search, category, status, page = 1 } = req.query;
    const limit = 15;
    const offset = (page - 1) * limit;
    
    let whereClause = 'WHERE 1=1';
    const params = [];
    
    if (search) {
        whereClause += ` AND (j.title LIKE ? OR j.organization LIKE ? OR j.location LIKE ?)`;
        params.push(`%${search}%`, `%${search}%`, `%${search}%`);
    }
    if (category) {
        whereClause += ` AND j.category_id = ?`;
        params.push(category);
    }
    if (status === '0' || status === '1') {
        whereClause += ` AND j.is_active = ?`;
        params.push(parseInt(status, 10));
    }
    
    db.get(`SELECT COUNT(*) as total FROM jobs j ${whereClause}`, params, (err, countResult) => {
        if (err) {
            res.status(500).json({ error: err.message });
            return;
        }
        
        const query = `SELECT j.*, c.name as category_name FROM jobs j 
            LEFT JOIN categories c ON j.category_id = c.id ${whereClause} 
            ORDER BY j.created_at DESC LIMIT ? OFFSET ?`;
        
        db.all(query, [...params, limit, offset], (err, rows) => {
            if (err) res.status(500).json({ error: err.message });
            else res.json({ 
                jobs: rows || [], 
                total: countResult ? countResult.total : 0, 
                pages: Math.ceil((countResult ? countResult.total : 0) / limit),
                current_page: parseInt(page)
            });
        });
    });
});

// Add Job (Admin) - FIXED: 18 columns, 18 placeholders
app.post('/api/admin/jobs', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const {
        title, organization, category_id, description, eligibility, location,
        salary_min, salary_max, job_type, vacancy_count, form_start_date,
        form_end_date, exam_date, official_website, how_to_apply, is_featured, is_active
    } = req.body;
    
    if (!title || !organization || !description) {
        return res.status(400).json({ error: 'Title, organization, and description are required' });
    }
    
    const slug = generateSlug(title);
    
    db.run(
        `INSERT INTO jobs (
            title, slug, organization, category_id, description, eligibility,
            location, salary_min, salary_max, job_type, vacancy_count,
            form_start_date, form_end_date, exam_date, official_website,
            how_to_apply, is_featured, is_active
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
        [
            title, slug, organization,
            category_id || null, description, eligibility || null,
            location || null, salary_min || null, salary_max || null,
            job_type || 'Full Time', vacancy_count || null,
            form_start_date || null, form_end_date || null,
            exam_date || null, official_website || null,
            how_to_apply || null,
            is_featured ? 1 : 0,
            is_active !== undefined ? (is_active ? 1 : 0) : 1
        ],
        function(err) {
            if (err) {
                console.error('Add job error:', err.message);
                res.status(500).json({ error: err.message });
            } else {
                // Update category job count
                if (category_id) {
                    db.run(`UPDATE categories SET job_count = (
                        SELECT COUNT(*) FROM jobs WHERE category_id = ? AND is_active = 1
                    ) WHERE id = ?`, [category_id, category_id]);
                }
                res.json({ success: true, id: this.lastID, slug });
            }
        }
    );
});

// Update Job (Admin)
app.put('/api/admin/jobs/:id', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const {
        title, organization, category_id, description, eligibility, location,
        salary_min, salary_max, job_type, vacancy_count, form_start_date,
        form_end_date, exam_date, official_website, how_to_apply, is_featured, is_active
    } = req.body;
    
    if (!title || !organization || !description) {
        return res.status(400).json({ error: 'Title, organization, and description are required' });
    }
    
    // Get old category to update count
    db.get(`SELECT category_id FROM jobs WHERE id = ?`, [req.params.id], (err, oldJob) => {
        if (err) return res.status(500).json({ error: err.message });
        
        db.run(
            `UPDATE jobs SET 
                title=?, organization=?, category_id=?, description=?, eligibility=?,
                location=?, salary_min=?, salary_max=?, job_type=?, vacancy_count=?,
                form_start_date=?, form_end_date=?, exam_date=?, official_website=?,
                how_to_apply=?, is_featured=?, is_active=?, updated_at=datetime('now')
            WHERE id=?`,
            [
                title, organization, category_id || null, description, eligibility || null,
                location || null, salary_min || null, salary_max || null,
                job_type || 'Full Time', vacancy_count || null,
                form_start_date || null, form_end_date || null,
                exam_date || null, official_website || null,
                how_to_apply || null, is_featured ? 1 : 0, is_active ? 1 : 0,
                req.params.id
            ],
            function(err) {
                if (err) {
                    res.status(500).json({ error: err.message });
                } else {
                    // Update category job counts
                    const catsToUpdate = new Set();
                    if (oldJob && oldJob.category_id) catsToUpdate.add(oldJob.category_id);
                    if (category_id) catsToUpdate.add(parseInt(category_id));
                    catsToUpdate.forEach(catId => {
                        db.run(`UPDATE categories SET job_count = (
                            SELECT COUNT(*) FROM jobs WHERE category_id = ? AND is_active = 1
                        ) WHERE id = ?`, [catId, catId]);
                    });
                    res.json({ success: true });
                }
            }
        );
    });
});

// Delete Job (Admin)
app.delete('/api/admin/jobs/:id', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    db.get(`SELECT category_id FROM jobs WHERE id = ?`, [req.params.id], (err, job) => {
        if (err) return res.status(500).json({ error: err.message });
        
        db.run(`DELETE FROM jobs WHERE id = ?`, [req.params.id], function(err) {
            if (err) res.status(500).json({ error: err.message });
            else {
                if (job && job.category_id) {
                    db.run(`UPDATE categories SET job_count = (
                        SELECT COUNT(*) FROM jobs WHERE category_id = ? AND is_active = 1
                    ) WHERE id = ?`, [job.category_id, job.category_id]);
                }
                res.json({ success: true });
            }
        });
    });
});

// Get Job for Edit (Admin)
app.get('/api/admin/jobs/:id/edit', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    db.get(`SELECT * FROM jobs WHERE id = ?`, [req.params.id], (err, row) => {
        if (err) res.status(500).json({ error: err.message });
        else if (!row) res.status(404).json({ error: 'Job not found' });
        else res.json(row);
    });
});

// Toggle Job Status (Admin)
app.post('/api/admin/jobs/:id/toggle', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    db.run(`UPDATE jobs SET is_active = CASE WHEN is_active = 1 THEN 0 ELSE 1 END WHERE id = ?`, 
        [req.params.id], function(err) {
        if (err) res.status(500).json({ error: err.message });
        else {
            // Update category count
            db.get(`SELECT category_id FROM jobs WHERE id = ?`, [req.params.id], (err, job) => {
                if (job && job.category_id) {
                    db.run(`UPDATE categories SET job_count = (
                        SELECT COUNT(*) FROM jobs WHERE category_id = ? AND is_active = 1
                    ) WHERE id = ?`, [job.category_id, job.category_id]);
                }
            });
            res.json({ success: true });
        }
    });
});

// ==================== ADMIN CATEGORIES ====================

app.get('/api/admin/categories', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    db.all('SELECT * FROM categories ORDER BY name', (err, rows) => {
        if (err) res.status(500).json({ error: err.message });
        else res.json(rows || []);
    });
});

app.post('/api/admin/categories', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const { name, description, icon } = req.body;
    if (!name) return res.status(400).json({ error: 'Category name is required' });
    
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    
    db.run(`INSERT INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)`, 
        [name, slug, description || null, icon || null], function(err) {
        if (err) res.status(500).json({ error: err.message });
        else res.json({ success: true, id: this.lastID });
    });
});

app.put('/api/admin/categories/:id', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const { name, description, icon, is_active } = req.body;
    if (!name) return res.status(400).json({ error: 'Category name is required' });
    
    db.run(`UPDATE categories SET name=?, description=?, icon=?, is_active=? WHERE id=?`,
        [name, description || null, icon || null, is_active !== undefined ? (is_active ? 1 : 0) : 1, req.params.id],
        function(err) {
            if (err) res.status(500).json({ error: err.message });
            else res.json({ success: true });
        }
    );
});

app.delete('/api/admin/categories/:id', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    db.run(`DELETE FROM categories WHERE id = ?`, [req.params.id], function(err) {
        if (err) res.status(500).json({ error: err.message });
        else res.json({ success: true });
    });
});

// ==================== ADMIN SUB-CATEGORIES ====================

app.get('/api/admin/subcategories', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    db.all(`SELECT sc.*, c.name as category_name FROM sub_categories sc 
            LEFT JOIN categories c ON sc.category_id = c.id ORDER BY c.name, sc.name`, (err, rows) => {
        if (err) res.status(500).json({ error: err.message });
        else res.json(rows || []);
    });
});

app.post('/api/admin/subcategories', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const { category_id, name, description } = req.body;
    if (!name || !category_id) return res.status(400).json({ error: 'Name and category are required' });
    
    const slug = name.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    
    db.run(`INSERT INTO sub_categories (category_id, name, slug, description) VALUES (?, ?, ?, ?)`,
        [category_id, name, slug, description || null], function(err) {
        if (err) res.status(500).json({ error: err.message });
        else res.json({ success: true, id: this.lastID });
    });
});

app.delete('/api/admin/subcategories/:id', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    db.run(`DELETE FROM sub_categories WHERE id = ?`, [req.params.id], function(err) {
        if (err) res.status(500).json({ error: err.message });
        else res.json({ success: true });
    });
});

// ==================== ADMIN APPLICATIONS ====================

app.get('/api/admin/applications', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const { status, page = 1 } = req.query;
    const limit = 15;
    const offset = (page - 1) * limit;
    
    let whereClause = '1=1';
    const params = [];
    if (status) {
        whereClause += ` AND a.status = ?`;
        params.push(status);
    }
    
    db.get(`SELECT COUNT(*) as total FROM applications a WHERE ${whereClause}`, params, (err, countResult) => {
        if (err) return res.status(500).json({ error: err.message });
        
        db.all(`SELECT a.*, j.title as job_title FROM applications a 
                LEFT JOIN jobs j ON a.job_id = j.id WHERE ${whereClause} 
                ORDER BY a.applied_at DESC LIMIT ? OFFSET ?`,
            [...params, limit, offset], (err, rows) => {
            if (err) res.status(500).json({ error: err.message });
            else res.json({
                applications: rows || [],
                total: countResult ? countResult.total : 0,
                pages: Math.ceil((countResult ? countResult.total : 0) / limit)
            });
        });
    });
});

// ==================== ADMIN SETTINGS ====================

app.get('/api/admin/settings', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    db.all('SELECT * FROM settings ORDER BY key', (err, rows) => {
        if (err) res.status(500).json({ error: err.message });
        else {
            const settings = {};
            (rows || []).forEach(r => settings[r.key] = r.value);
            res.json(settings);
        }
    });
});

app.put('/api/admin/settings', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const settings = req.body;
    if (!settings || typeof settings !== 'object') {
        return res.status(400).json({ error: 'Invalid settings data' });
    }
    
    const stmt = db.prepare(`INSERT OR REPLACE INTO settings (key, value, updated_at) VALUES (?, ?, datetime('now'))`);
    for (const [key, value] of Object.entries(settings)) {
        stmt.run([key, value]);
    }
    stmt.finalize();
    res.json({ success: true });
});

// ==================== ADMIN RECENT ACTIVITY ====================

app.get('/api/admin/recent-jobs', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    db.all(`SELECT j.*, c.name as category_name FROM jobs j 
            LEFT JOIN categories c ON j.category_id = c.id 
            ORDER BY j.created_at DESC LIMIT 10`, (err, rows) => {
        if (err) res.status(500).json({ error: err.message });
        else res.json(rows || []);
    });
});

app.get('/api/admin/deadline-jobs', (req, res) => {
    if (!isAuthenticated(req, res)) return;
    
    const today = new Date().toISOString().split('T')[0];
    db.all(`SELECT j.*, c.name as category_name FROM jobs j 
            LEFT JOIN categories c ON j.category_id = c.id 
            WHERE j.is_active = 1 AND j.form_end_date >= ?
            ORDER BY j.form_end_date ASC LIMIT 10`, [today], (err, rows) => {
        if (err) res.status(500).json({ error: err.message });
        else res.json(rows || []);
    });
});

// ==================== PAGE ROUTES ====================

app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

app.get('/jobs', (req, res) => {
    res.sendFile(path.join(__dirname, 'jobs.html'));
});

app.get('/jobs/:slug', (req, res) => {
    res.sendFile(path.join(__dirname, 'job-details.html'));
});

app.get('/candidate', (req, res) => {
    res.sendFile(path.join(__dirname, 'candidate.html'));
});

app.get('/contact', (req, res) => {
    res.sendFile(path.join(__dirname, 'contact.html'));
});

app.get('/blog', (req, res) => {
    res.sendFile(path.join(__dirname, 'blog.html'));
});

app.get('/single-blog', (req, res) => {
    res.sendFile(path.join(__dirname, 'single-blog.html'));
});

app.get('/elements', (req, res) => {
    res.sendFile(path.join(__dirname, 'elements.html'));
});

app.get('/admin', (req, res) => {
    res.sendFile(path.join(__dirname, 'admin.html'));
});

// Health check endpoint
app.get('/api/health', (req, res) => {
    db.get('SELECT COUNT(*) as count FROM jobs', (err, row) => {
        res.json({
            status: 'ok',
            database: err ? 'error' : 'connected',
            jobs: row ? row.count : 0,
            uptime: process.uptime(),
            memory: process.memoryUsage(),
            sessions: Object.keys(sessions).length
        });
    });
});

// Error handling middleware
app.use((err, req, res, next) => {
    console.error('Server error:', err);
    res.status(500).json({ error: 'Internal server error' });
});

// Start Server
app.listen(PORT, () => {
    console.log(`
╔══════════════════════════════════════════════════╗
║    🚀 Government Job Portal Running!            ║
║                                                  ║
║    🌐 Website:  http://localhost:${PORT}            ║
║    🔐 Admin:    http://localhost:${PORT}/admin      ║
║    ❤️  Health:   http://localhost:${PORT}/api/health ║
║                                                  ║
║    👤 Default Login:                             ║
║       Username: admin                            ║
║       Password: admin123                         ║
║                                                  ║
║    ⚡ Optimized for 1000+ concurrent users       ║
╚══════════════════════════════════════════════════╝
    `);
});

// Graceful shutdown
process.on('SIGINT', () => {
    console.log('\n🛑 Shutting down gracefully...');
    db.close(() => {
        console.log('✅ Database closed');
        process.exit(0);
    });
});
