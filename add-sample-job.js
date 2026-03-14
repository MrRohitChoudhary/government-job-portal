const fetch = global.fetch;

async function main() {
  try {
    // Login as admin to obtain sessionId
    const loginRes = await fetch('http://localhost:3000/api/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username: 'admin', password: 'admin123' })
    });

    if (!loginRes.ok) {
      const text = await loginRes.text();
      console.error('Admin login failed:', loginRes.status, text);
      process.exit(1);
    }

    const loginData = await loginRes.json();
    if (!loginData.success || !loginData.sessionId) {
      console.error('Admin login response missing success/sessionId:', loginData);
      process.exit(1);
    }

    const sessionId = loginData.sessionId;

    const jobPayload = {
      title: 'Senior Frontend Engineer (Gov Portal Test)',
      organization: 'Karnataka E-Governance Department',
      category_id: 7,
      description: 'Lead frontend development for the Karnataka & AP Government Job Portal. Work with modern JS, accessibility, and performance best practices.',
      eligibility: 'B.E/B.Tech in Computer Science or equivalent with 3+ years of experience.',
      location: 'Bangalore',
      salary_min: '80000',
      salary_max: '140000',
      job_type: 'Full Time',
      vacancy_count: 3,
      form_start_date: '2026-03-01',
      form_end_date: '2026-12-31',
      exam_date: '2026-10-15',
      official_website: 'https://www.karnataka.gov.in',
      how_to_apply: 'Apply online through the official Karnataka government careers portal.',
      is_featured: 1,
      is_active: 1
    };

    const addRes = await fetch('http://localhost:3000/api/admin/jobs', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: sessionId
      },
      body: JSON.stringify(jobPayload)
    });

    const addText = await addRes.text();
    if (!addRes.ok) {
      console.error('Failed to add sample job:', addRes.status, addText);
      process.exit(1);
    }

    console.log('Sample job created successfully via admin API.');
    console.log('Title:', jobPayload.title);
    console.log('Raw response:', addText);
  } catch (err) {
    console.error('Unexpected error while creating sample job:', err);
    process.exit(1);
  }
}

main();

