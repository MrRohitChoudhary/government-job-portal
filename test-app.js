const http = require('http');

function makeRequest(method, path, data) {
    return new Promise((resolve, reject) => {
        const options = {
            hostname: 'localhost',
            port: 3000,
            path: path,
            method: method,
            headers: {
                'Content-Type': 'application/json'
            }
        };

        const req = http.request(options, (res) => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => {
                try {
                    resolve({ status: res.statusCode, data: JSON.parse(body) });
                } catch (e) {
                    resolve({ status: res.statusCode, data: body });
                }
            });
        });

        req.on('error', reject);
        if (data) req.write(JSON.stringify(data));
        req.end();
    });
}

async function test() {
    console.log('=== TESTING ADMIN LOGIN ===');
    const loginRes = await makeRequest('POST', '/api/admin/login', {
        username: 'admin',
        password: 'admin123'
    });
    console.log('Status:', loginRes.status);
    console.log('Response:', JSON.stringify(loginRes.data, null, 2));

    if (loginRes.data.success) {
        const sessionId = loginRes.data.sessionId;
        console.log('\n✓ Login successful!');
        console.log('Session ID:', sessionId);

        console.log('\n=== ADDING SAMPLE JOB ===');
        const jobData = {
            title: 'Senior Software Engineer - Bangalore',
            organization: 'Government of India',
            category_id: 1,
            description: 'Looking for experienced Senior Software Engineer with 5+ years of experience. Must have strong knowledge of Java, Spring Boot, and cloud technologies. Responsible for developing and maintaining government applications.',
            eligibility: 'B.E / B.Tech in Computer Science or related field, 5+ years experience',
            location: 'Bangalore, Karnataka',
            salary_min: '50000',
            salary_max: '80000',
            job_type: 'Full Time',
            vacancy_count: 10,
            form_start_date: '2026-02-28',
            form_end_date: '2026-03-31',
            exam_date: '2026-04-15',
            official_website: 'https://www.example.gov.in',
            how_to_apply: 'Apply through this portal or official website',
            is_featured: true,
            is_active: true
        };

        const addJobRes = await makeRequest('POST', '/api/admin/jobs', jobData);
        // Add authorization header for authenticated request
        const options = {
            hostname: 'localhost',
            port: 3000,
            path: '/api/admin/jobs',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': sessionId
            }
        };

        const jobRes = await new Promise((resolve) => {
            const req = http.request(options, (res) => {
                let body = '';
                res.on('data', chunk => body += chunk);
                res.on('end', () => {
                    try {
                        resolve({ status: res.statusCode, data: JSON.parse(body) });
                    } catch (e) {
                        resolve({ status: res.statusCode, data: body });
                    }
                });
            });
            req.write(JSON.stringify(jobData));
            req.end();
        });

        console.log('Status:', jobRes.status);
        console.log('Response:', JSON.stringify(jobRes.data, null, 2));

        if (jobRes.data.success) {
            console.log('\n✓ Job added successfully!');
            console.log('Job ID:', jobRes.data.id);
            console.log('Job Slug:', jobRes.data.slug);

            console.log('\n=== VERIFYING JOB ADDED ===');
            const getJobRes = await makeRequest('GET', '/api/jobs');
            console.log('Total jobs available:', getJobRes.data.length);
            if (getJobRes.data.length > 0) {
                console.log('Latest job:', getJobRes.data[0].title);
            }
        }
    }
}

test().catch(console.error);
