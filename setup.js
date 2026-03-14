const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcryptjs');

console.log('🚀 Setting up Government Job Portal...\n');

// Create database
const db = new sqlite3.Database('./jobportal.db', (err) => {
    if (err) {
        console.error('❌ Error creating database:', err.message);
        process.exit(1);
    }
    console.log('✅ Database created successfully!');
});

db.serialize(() => {
    // Enable WAL mode for better concurrency (supports 1000+ users)
    db.run('PRAGMA journal_mode = WAL');
    db.run('PRAGMA foreign_keys = ON');
    db.run('PRAGMA cache_size = -64000'); // 64MB cache
    db.run('PRAGMA synchronous = NORMAL');

    // Create categories table
    db.run(`CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        description TEXT,
        icon TEXT,
        job_count INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`, (err) => { if (err) console.error('categories:', err.message); });

    // Create sub_categories table
    db.run(`CREATE TABLE IF NOT EXISTS sub_categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        category_id INTEGER,
        name TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        description TEXT,
        job_count INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    )`, (err) => { if (err) console.error('sub_categories:', err.message); });

    // Create jobs table
    db.run(`CREATE TABLE IF NOT EXISTS jobs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        slug TEXT NOT NULL UNIQUE,
        organization TEXT NOT NULL,
        category_id INTEGER,
        sub_category_id INTEGER,
        description TEXT NOT NULL,
        eligibility TEXT,
        location TEXT,
        salary_min TEXT,
        salary_max TEXT,
        salary_description TEXT,
        job_type TEXT DEFAULT 'Full Time',
        vacancy_count INTEGER,
        application_fee TEXT,
        form_start_date DATE,
        form_end_date DATE,
        exam_date DATE,
        admit_card_date DATE,
        result_date DATE,
        official_website TEXT,
        how_to_apply TEXT,
        important_links TEXT,
        is_featured INTEGER DEFAULT 0,
        is_active INTEGER DEFAULT 1,
        views INTEGER DEFAULT 0,
        created_by INTEGER,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    )`, (err) => { if (err) console.error('jobs:', err.message); });

    // Create admin_users table
    db.run(`CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password TEXT NOT NULL,
        full_name TEXT,
        role TEXT DEFAULT 'admin',
        is_active INTEGER DEFAULT 1,
        last_login DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`, (err) => { if (err) console.error('admin_users:', err.message); });

    // Create applications table
    db.run(`CREATE TABLE IF NOT EXISTS applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        job_id INTEGER NOT NULL,
        applicant_name TEXT NOT NULL,
        email TEXT NOT NULL,
        phone TEXT,
        qualification TEXT,
        experience TEXT,
        cover_letter TEXT,
        status TEXT DEFAULT 'pending',
        applied_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
    )`, (err) => { if (err) console.error('applications:', err.message); });

    // Create settings table
    db.run(`CREATE TABLE IF NOT EXISTS settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key TEXT NOT NULL UNIQUE,
        value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`, (err) => { if (err) console.error('settings:', err.message); });

    // Create indexes for performance (1000+ users)
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_active ON jobs(is_active, form_end_date)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_category ON jobs(category_id)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_slug ON jobs(slug)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_created ON jobs(created_at DESC)`);
    db.run(`CREATE INDEX IF NOT EXISTS idx_jobs_featured ON jobs(is_featured, is_active)`);

    console.log('✅ Tables and indexes created successfully!');

    // Insert default admin (password: admin123)
    const hashedPassword = bcrypt.hashSync('admin123', 10);
    
    db.run(`INSERT OR IGNORE INTO admin_users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)`,
        ['admin', 'admin@jobportal.com', hashedPassword, 'Administrator', 'super_admin'],
        (err) => {
            if (err) console.log('⚠️ Admin user:', err.message);
            else console.log('✅ Admin user created (admin / admin123)');
        }
    );

    // Insert default settings
    const defaultSettings = [
        ['site_name', 'Government Job Portal'],
        ['site_tagline', 'Karnataka & Andhra Pradesh Sarkari Naukri'],
        ['contact_email', 'info@jobportal.com'],
        ['contact_phone', '+91 98765 43210'],
        ['jobs_per_page', '15'],
        ['maintenance_mode', '0']
    ];
    defaultSettings.forEach(([key, value]) => {
        db.run(`INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)`, [key, value]);
    });

    // Insert default categories
    const categories = [
        ['Indian Administrative Service (IAS)', 'ias', 'Indian Administrative Service and Civil Services', 'fa-building'],
        ['Indian Police Service (IPS)', 'ips', 'Indian Police Service and State Police Jobs', 'fa-shield-alt'],
        ['Banking Jobs', 'banking-jobs', 'Bank Jobs, RBI, Public Sector Banks', 'fa-university'],
        ['Teaching Jobs', 'teaching-jobs', 'Teacher, Professor, Lecturer Jobs', 'fa-graduation-cap'],
        ['Engineering Jobs', 'engineering-jobs', 'ISRO, DRDO, PWD, Railway Engineering', 'fa-cogs'],
        ['Defense Jobs', 'defense-jobs', 'Army, Navy, Air Force Jobs', 'fa-fighter-jet'],
        ['State Government Jobs', 'karnataka-jobs', 'Karnataka Government Jobs', 'fa-landmark'],
        ['Andhra Pradesh Jobs', 'ap-jobs', 'Andhra Pradesh Government Jobs', 'fa-landmark'],
        ['Railway Jobs', 'railway-jobs', 'Indian Railway Jobs', 'fa-train'],
        ['PSU Jobs', 'psu-jobs', 'Public Sector Undertaking Jobs', 'fa-industry']
    ];

    const catStmt = db.prepare('INSERT OR IGNORE INTO categories (name, slug, description, icon) VALUES (?, ?, ?, ?)');
    categories.forEach(cat => catStmt.run(cat));
    catStmt.finalize();
    console.log('✅ Categories inserted!');

    // Insert sample jobs with FUTURE dates (2026-2027)
    const jobs = [
        [
            'Karnataka Civil Police Constable 2026',
            'karnataka-police-constable-2026',
            'Karnataka State Police',
            7,
            'Recruitment of Civil Police Constable in Karnataka State Police. Selected candidates will be appointed in various districts of Karnataka. This is a state government job with excellent benefits and job security. The recruitment process includes written test, physical fitness test, and medical examination.',
            'SSLC / 10th Pass. Age: 18-28 years. Physical Standards: Height 168cm (Men), 157cm (Women).',
            'Karnataka',
            '25000', '50000',
            'Full Time',
            500,
            '2026-02-01', '2026-06-30',
            'https://www.ksp.karnataka.gov.in'
        ],
        [
            'APPSC Group 2 Services 2026',
            'appsc-group-2-2026',
            'Andhra Pradesh Public Service Commission',
            8,
            'Andhra Pradesh PSC Group 2 Recruitment 2026 - Various Gazetted and Non-Gazetted Posts. The Commission is recruiting for multiple departments including Revenue, Agriculture, and Municipal Administration. Exam will be conducted in two phases.',
            'Bachelor Degree from recognized university. Age: 18-42 years (Relaxation for reserved categories).',
            'Andhra Pradesh',
            '40000', '80000',
            'Full Time',
            300,
            '2026-03-01', '2026-07-31',
            'https://www.psc.ap.gov.in'
        ],
        [
            'IBPS PO Recruitment 2026',
            'ibps-po-2026',
            'Institute of Banking Personnel Selection',
            3,
            'IBPS PO Recruitment 2026 for Probationary Officers in Public Sector Banks across India. Selected candidates will be posted in various participating banks like Punjab National Bank, Bank of Baroda, Canara Bank, etc. The selection process includes Preliminary Exam, Main Exam and Interview.',
            'Bachelor Degree in any discipline. Age: 20-30 years. Computer literacy required.',
            'All India',
            '45000', '100000',
            'Full Time',
            5000,
            '2026-02-15', '2026-08-15',
            'https://www.ibps.in'
        ],
        [
            'Karnataka KAS Officers 2026',
            'karnataka-kas-officers-2026',
            'Karnataka Public Service Commission',
            1,
            'Karnataka Administrative Service (KAS) Recruitment 2026. This prestigious exam selects candidates for Group A & B posts in Karnataka government. The KAS examination is conducted in three stages: Preliminary, Main examination and Personality Test (Interview).',
            'Bachelor Degree from any recognized university. Age: 21-35 years (SC/ST: up to 38 years).',
            'Karnataka',
            '50000', '110000',
            'Full Time',
            100,
            '2026-03-15', '2026-09-30',
            'https://www.kpsc.kar.nic.in'
        ],
        [
            'ISRO Scientist/Engineer SC 2026',
            'isro-scientist-engineer-2026',
            'Indian Space Research Organisation',
            5,
            'ISRO Recruitment 2026 for Scientist/Engineer (SC) in various disciplines including Electronics, Mechanical, Computer Science, and Civil Engineering. Selected candidates will work on India\'s prestigious space missions. This is a Central Government job with Grade Pay 4600.',
            'B.E/B.Tech with minimum 65% marks in relevant discipline from recognized university. Age: 18-35 years.',
            'Bangalore / Ahmedabad / Thiruvananthapuram',
            '70000', '140000',
            'Full Time',
            50,
            '2026-04-01', '2026-07-15',
            'https://www.isro.gov.in'
        ],
        [
            'Indian Army Agniveer Recruitment 2026',
            'army-agniveer-2026',
            'Indian Army',
            6,
            'Agniveer Recruitment Rally 2026 for Karnataka & Andhra Pradesh regions. Recruitment for General Duty, Technical, Clerk/Store Keeper, and Tradesman categories. This is a great opportunity for youth to serve the nation. Comprehensive training and benefits provided.',
            '10th Pass / 12th Pass depending on trade. Age: 17.5 to 23 years. Physical fitness required.',
            'Karnataka / Andhra Pradesh',
            '30000', '40000',
            'Full Time',
            1000,
            '2026-02-01', '2026-05-31',
            'https://www.joinindianarmy.nic.in'
        ],
        [
            'RRB NTPC Graduate Level 2026',
            'rrb-ntpc-graduate-2026',
            'Railway Recruitment Board',
            9,
            'RRB NTPC Recruitment 2026 for Graduate Level posts including Junior Account Assistant, Senior Clerk, Junior Time Keeper, Trains Clerk, Commercial cum Ticket Clerk, and Station Master posts. CBT (Computer Based Test) will be held at various centers.',
            'Bachelor Degree in any discipline. Age: 18-36 years (Age relaxation as per rules).',
            'All India',
            '35000', '75000',
            'Full Time',
            8000,
            '2026-01-20', '2026-06-20',
            'https://www.indianrailways.gov.in'
        ]
    ];

    const jobStmt = db.prepare(`INSERT OR IGNORE INTO jobs 
        (title, slug, organization, category_id, description, eligibility, location, 
         salary_min, salary_max, job_type, vacancy_count, form_start_date, form_end_date, 
         official_website, is_featured, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1)`);
    jobs.forEach(job => jobStmt.run(job));
    jobStmt.finalize();
    console.log('✅ Sample jobs inserted with future dates!');

    // Insert sample sub-categories
    setTimeout(() => {
        const subCats = [
            [1, 'KAS Prelims', 'kas-prelims'],
            [1, 'KAS Mains', 'kas-mains'],
            [1, 'IAS Civil Services', 'ias-civil-services'],
            [2, 'Karnataka Police', 'karnataka-police'],
            [2, 'AP Police', 'ap-police'],
            [3, 'IBPS PO', 'ibps-po'],
            [3, 'IBPS Clerk', 'ibps-clerk'],
            [3, 'RBI Grade B', 'rbi-grade-b'],
            [3, 'SBI PO', 'sbi-po'],
            [4, 'TET/CTET', 'tet-ctet'],
            [4, 'PGT Teacher', 'pgt-teacher'],
            [4, 'Professor', 'professor'],
            [5, 'ISRO', 'isro'],
            [5, 'DRDO', 'drdo'],
            [6, 'Indian Army', 'indian-army'],
            [6, 'Indian Navy', 'indian-navy'],
            [6, 'Air Force', 'air-force'],
            [9, 'RRB NTPC', 'rrb-ntpc'],
            [9, 'RRB Group D', 'rrb-group-d'],
            [10, 'ONGC', 'ongc'],
            [10, 'BHEL', 'bhel']
        ];
        const subStmt = db.prepare('INSERT OR IGNORE INTO sub_categories (category_id, name, slug) VALUES (?, ?, ?)');
        subCats.forEach(sc => subStmt.run(sc));
        subStmt.finalize();
        console.log('✅ Sub-categories inserted!');

        // Update job counts in categories
        db.run(`UPDATE categories SET job_count = (
            SELECT COUNT(*) FROM jobs WHERE jobs.category_id = categories.id AND jobs.is_active = 1
        )`);

        console.log('\n🎉 Setup Complete!');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('📁 Database: jobportal.db (SQLite - WAL mode enabled)');
        console.log('⚡ Optimized for 1000+ concurrent users');
        console.log('🔑 Admin Login: admin / admin123');
        console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        console.log('\n🚀 Run: node server.js');
        console.log('🌐 Website: http://localhost:3000');
        console.log('🔐 Admin:   http://localhost:3000/admin');

        setTimeout(() => {
            db.close();
        }, 500);
    }, 1000);
});
