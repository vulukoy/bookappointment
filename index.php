<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Free Appointment Booking Page for Hairdressers, Tutors & Trainers | BookMe</title>
<meta name="description" content="Give clients one link to see your real open time and book it themselves — no app to download, no account for them to create. Free to start.">
<link rel="canonical" href="http://bookappointment.me/">

<!-- Open Graph (Facebook, Instagram, LinkedIn, Slack previews) -->
<meta property="og:type" content="website">
<meta property="og:url" content="http://bookappointment.me/">
<meta property="og:title" content="Stop booking appointments by text message.">
<meta property="og:description" content="Give clients one link to see your real open time and book it themselves — no app to download, no account for them to create.">
<meta property="og:image" content="http://bookappointment.me/assets/img/og-image.jpg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="BookMe">

<!-- Twitter/X card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Stop booking appointments by text message.">
<meta name="twitter:description" content="One link. Real open time. No app for your clients to download.">
<meta name="twitter:image" content="http://bookappointment.me/assets/img/og-image.jpg">

<!-- Structured data: helps Google understand what this product is -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "BookMe",
  "url": "http://bookappointment.me/",
  "description": "Free appointment booking page for solo service providers — hairdressers, tutors, trainers, and consultants. Clients book real open time with no app or account required.",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "USD"
  }
}
</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
<style>
  :root{
    --ink:#1C2321;
    --paper:#ECEFE9;
    --paper-raised:#F5F7F2;
    --pine:#1F5C4E;
    --pine-dark:#163F35;
    --amber:#F2A73B;
    --rule:#C9CDBF;
    --muted:#5B6158;
  }
  *{box-sizing:border-box;}
  html{scroll-behavior:smooth;}
  body{
    margin:0;
    background:var(--paper);
    color:var(--ink);
    font-family:'Inter',-apple-system,sans-serif;
    line-height:1.55;
  }
  h1,h2,h3{
    font-family:'Oswald',sans-serif;
    text-transform:uppercase;
    letter-spacing:0.01em;
    margin:0;
    color:var(--ink);
  }
  .mono{font-family:'IBM Plex Mono',monospace;}
  a{color:inherit;}
  .wrap{max-width:1080px;margin:0 auto;padding:0 24px;}

  /* ---------- Nav ---------- */
  .nav{
    display:flex;align-items:center;justify-content:space-between;
    padding:22px 0;
    border-bottom:1px solid var(--rule);
  }
  .logo{
    font-family:'Oswald',sans-serif;
    font-weight:700;
    font-size:1.35rem;
    text-transform:uppercase;
    letter-spacing:0.03em;
    text-decoration:none;
    display:flex;align-items:center;gap:8px;
  }
  .logo .dot{width:10px;height:10px;background:var(--amber);border-radius:50%;display:inline-block;}
  .nav-links{display:flex;align-items:center;gap:28px;}
  .nav-links a{text-decoration:none;font-weight:500;font-size:0.95rem;color:var(--ink);}
  .btn{
    display:inline-block;
    background:var(--pine);
    color:#fff;
    font-weight:600;
    font-size:0.95rem;
    padding:12px 24px;
    border-radius:3px;
    text-decoration:none;
    border:1px solid var(--pine);
    transition:transform .15s ease, box-shadow .15s ease;
  }
  .btn:hover{transform:translateY(-1px); box-shadow:0 4px 14px rgba(31,92,78,0.28);}
  .btn.small{padding:9px 18px;font-size:0.85rem;}
  .btn.ghost{background:transparent;color:var(--ink);border:1px solid var(--ink);}

  /* ---------- Hero ---------- */
  .hero{padding:76px 0 40px; text-align:center;}
  .eyebrow{
    font-family:'IBM Plex Mono',monospace;
    font-size:0.78rem;
    letter-spacing:0.12em;
    text-transform:uppercase;
    color:var(--pine);
    display:inline-flex;align-items:center;gap:8px;
    margin-bottom:22px;
  }
  .eyebrow::before{content:'';width:22px;height:1px;background:var(--pine);}
  .eyebrow::after{content:'';width:22px;height:1px;background:var(--pine);}
  .hero h1{
    font-size:clamp(2.2rem, 5vw, 3.6rem);
    line-height:1.08;
    max-width:820px;
    margin:0 auto 20px;
  }
  .hero h1 em{font-style:normal;color:var(--pine);}
  .hero p.sub{
    max-width:520px;margin:0 auto 32px;
    font-size:1.1rem;color:var(--muted);
  }
  .hero-ctas{display:flex;gap:14px;justify-content:center;margin-bottom:12px;flex-wrap:wrap;}
  .hero-note{font-size:0.85rem;color:var(--muted);}

  /* ---------- Day strip (signature element) ---------- */
  .strip-wrap{padding:56px 0 84px;}
  .strip-label{
    display:flex;justify-content:space-between;align-items:baseline;
    max-width:920px;margin:0 auto 14px;
    font-family:'IBM Plex Mono',monospace;font-size:0.78rem;color:var(--muted);
    text-transform:uppercase;letter-spacing:0.08em;
  }
  .strip{
    max-width:920px;margin:0 auto;
    background:var(--paper-raised);
    border:1px solid var(--rule);
    border-radius:6px;
    padding:22px 18px 26px;
    position:relative;
    overflow-x:auto;
  }
  .strip::before{
    content:'';position:absolute;left:18px;top:0;bottom:0;width:2px;background:#C86B5A;opacity:0.55;
  }
  .day-track{display:flex;gap:6px;min-width:760px;padding-left:18px;}
  .slot{
    flex:1;min-width:74px;
    border-radius:4px;
    padding:14px 8px 10px;
    text-align:center;
    opacity:0;
    transform:translateY(6px);
    animation:rise .5s ease forwards;
  }
  .slot .time{display:block;font-family:'IBM Plex Mono',monospace;font-size:0.72rem;margin-bottom:8px;}
  .slot .label{display:block;font-size:0.82rem;font-weight:600;}
  .slot.booked{background:var(--pine);color:#fff;}
  .slot.booked .time{color:rgba(255,255,255,0.7);}
  .slot.open{
    background:transparent;
    border:1.5px dashed var(--amber);
    color:var(--pine-dark);
    cursor:pointer;
    transition:background .15s ease;
  }
  .slot.open .time{color:#9A7A2E;}
  .slot.open:hover{background:rgba(242,167,59,0.18);}
  @keyframes rise{to{opacity:1;transform:translateY(0);}}
  @media (prefers-reduced-motion: reduce){
    .slot{animation:none;opacity:1;transform:none;}
  }
  .strip-caption{
    text-align:center;margin-top:16px;font-size:0.85rem;color:var(--muted);
  }
  .strip-caption .amber-dot{display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--amber);margin-right:6px;}

  /* ---------- How it works ---------- */
  .section{padding:64px 0;}
  .section-head{text-align:center;max-width:560px;margin:0 auto 48px;}
  .section-head h2{font-size:clamp(1.6rem,3vw,2.1rem);margin-bottom:12px;}
  .section-head p{color:var(--muted);font-size:1.02rem;}

  .steps{display:grid;grid-template-columns:repeat(3,1fr);gap:0;border-top:1px solid var(--rule);}
  .step{
    padding:32px 26px;border-right:1px solid var(--rule);border-bottom:1px solid var(--rule);
  }
  .step:last-child{border-right:none;}
  .step .num{
    font-family:'IBM Plex Mono',monospace;color:var(--pine);font-size:0.85rem;margin-bottom:14px;display:block;
  }
  .step h3{font-size:1.15rem;margin-bottom:10px;text-transform:none;}
  .step p{color:var(--muted);font-size:0.95rem;margin:0;}

  /* ---------- Features ---------- */
  .features{background:var(--paper-raised);border-top:1px solid var(--rule);border-bottom:1px solid var(--rule);}
  .feature-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:28px 40px;max-width:820px;margin:0 auto;}
  .feature{display:flex;gap:14px;}
  .feature .mark{
    font-family:'IBM Plex Mono',monospace;color:var(--amber);font-weight:600;flex-shrink:0;
  }
  .feature h3{font-size:1rem;text-transform:none;margin-bottom:4px;}
  .feature p{margin:0;color:var(--muted);font-size:0.92rem;}

  /* ---------- Ticket / testimonial ---------- */
  .ticket-wrap{display:flex;justify-content:center;padding:70px 24px;}
  .ticket{
    max-width:520px;width:100%;
    background:var(--paper-raised);
    border:1px solid var(--rule);
    border-radius:4px;
    padding:34px 36px;
    position:relative;
  }
  .ticket::before, .ticket::after{
    content:'';position:absolute;top:50%;width:20px;height:20px;
    background:var(--paper);border:1px solid var(--rule);border-radius:50%;transform:translateY(-50%);
  }
  .ticket::before{left:-11px;}
  .ticket::after{right:-11px;}
  .ticket blockquote{
    margin:0 0 18px;font-size:1.15rem;line-height:1.5;font-family:'Oswald',sans-serif;
    text-transform:none;font-weight:500;
  }
  .ticket cite{font-style:normal;font-size:0.88rem;color:var(--muted);}

  /* ---------- Final CTA ---------- */
  .cta-band{
    text-align:center;padding:80px 24px;
    background:var(--pine);color:#fff;
  }
  .cta-band h2{color:#fff;font-size:clamp(1.7rem,3.4vw,2.3rem);margin-bottom:14px;}
  .cta-band p{color:rgba(255,255,255,0.78);margin:0 0 30px;font-size:1.02rem;}
  .cta-band .btn{background:var(--amber);border-color:var(--amber);color:var(--ink);}
  .cta-band .btn:hover{box-shadow:0 4px 14px rgba(0,0,0,0.25);}

  footer{padding:32px 24px;text-align:center;font-size:0.85rem;color:var(--muted);}
  footer a{text-decoration:underline;}

  @media (max-width:760px){
    .nav-links a:not(.btn){display:none;}
    .steps{grid-template-columns:1fr;}
    .step{border-right:none;}
    .feature-grid{grid-template-columns:1fr;}
  }
</style>
</head>
<body>

<div class="wrap">
  <nav class="nav">
    <a href="/" class="logo"><span class="dot"></span>BookAppointment.me</a>
    <div class="nav-links">
      <a href="#how">How it works</a>
      <a href="/dashboard/login.php">Log in</a>
      <a href="/dashboard/signup.php" class="btn small">Create your page</a>
    </div>
  </nav>
</div>

<section class="hero">
  <div class="wrap">
    <div class="eyebrow">Built for solo service providers</div>
    <h1>Stop booking appointments <em>by text message.</em></h1>
    <p class="sub">Get one link clients can use to see your real open time and book it themselves — no app to download, no account for them to create.</p>
    <div class="hero-ctas">
      <a href="/dashboard/signup.php" class="btn">Create your free page</a>
      <a href="#how" class="btn ghost">See how it works</a>
    </div>
    <p class="hero-note">Free to start · No credit card required</p>
  </div>
</section>

<div class="strip-wrap wrap">
  <div class="strip-label">
    <span>Tuesday · Jane's Hair Studio</span>
    <span><span class="amber-dot" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:var(--amber);margin-right:5px;"></span>Open — tap to book</span>
  </div>
  <div class="strip">
    <div class="day-track">
      <div class="slot booked" style="animation-delay:.02s"><span class="time">9:00</span><span class="label">J. Kim</span></div>
      <div class="slot booked" style="animation-delay:.06s"><span class="time">9:30</span><span class="label">J. Kim</span></div>
      <div class="slot open" style="animation-delay:.10s"><span class="time">10:00</span><span class="label">Open</span></div>
      <div class="slot open" style="animation-delay:.14s"><span class="time">10:30</span><span class="label">Open</span></div>
      <div class="slot booked" style="animation-delay:.18s"><span class="time">11:00</span><span class="label">R. Diaz</span></div>
      <div class="slot booked" style="animation-delay:.22s"><span class="time">11:30</span><span class="label">R. Diaz</span></div>
      <div class="slot open" style="animation-delay:.26s"><span class="time">12:00</span><span class="label">Open</span></div>
      <div class="slot booked" style="animation-delay:.30s"><span class="time">1:00</span><span class="label">T. Nguyen</span></div>
      <div class="slot open" style="animation-delay:.34s"><span class="time">1:30</span><span class="label">Open</span></div>
      <div class="slot open" style="animation-delay:.38s"><span class="time">2:00</span><span class="label">Open</span></div>
      <div class="slot booked" style="animation-delay:.42s"><span class="time">2:30</span><span class="label">A. Cole</span></div>
      <div class="slot open" style="animation-delay:.46s"><span class="time">3:00</span><span class="label">Open</span></div>
    </div>
  </div>
  <p class="strip-caption">This is what your clients see — real slots, pulled from your actual calendar, updated the moment someone books.</p>
</div>

<section class="section" id="how">
  <div class="wrap">
    <div class="section-head">
      <h2>Three steps. No back-and-forth.</h2>
      <p>Set it up once. Every booking after that runs itself.</p>
    </div>
    <div class="steps">
      <div class="step">
        <span class="num">01 / SET YOUR HOURS</span>
        <h3>Tell it when you work</h3>
        <p>Add your services, how long each one takes, and the hours you're available. Takes about two minutes.</p>
      </div>
      <div class="step">
        <span class="num">02 / SHARE ONE LINK</span>
        <h3>Send clients your page</h3>
        <p>Put it in your Instagram bio, text it, or add it to your storefront. They pick a service and a time — that's it.</p>
      </div>
      <div class="step">
        <span class="num">03 / IT RUNS ITSELF</span>
        <h3>Bookings land in your dashboard</h3>
        <p>You get notified, they get a confirmation, and the slot disappears from your page automatically.</p>
      </div>
    </div>
  </div>
</section>

<section class="section features">
  <div class="wrap">
    <div class="section-head">
      <h2>Built for how you actually work</h2>
      <p>No feature you have to explain to a client. No app for them to install.</p>
    </div>
    <div class="feature-grid">
      <div class="feature">
        <span class="mark">→</span>
        <div><h3>No login for clients</h3><p>They book in under a minute, from any phone, without creating an account.</p></div>
      </div>
      <div class="feature">
        <span class="mark">→</span>
        <div><h3>Buffer time built in</h3><p>Set a gap between appointments so you're never rushed or double-booked.</p></div>
      </div>
      <div class="feature">
        <span class="mark">→</span>
        <div><h3>Block off any date</h3><p>Vacation, a holiday, a half-day — close your calendar for that day in one click.</p></div>
      </div>
      <div class="feature">
        <span class="mark">→</span>
        <div><h3>Easy cancellations</h3><p>Clients can cancel with the link in their confirmation — no need to call you.</p></div>
      </div>
    </div>
  </div>
</section>

<div class="ticket-wrap">
  <div class="ticket">
    <blockquote>"I used to lose ten minutes a day just texting people back and forth about times. Now I just send my link."</blockquote>
    <cite>— Independent hairstylist, first month using BookAppointment.me</cite>
  </div>
</div>

<div class="cta-band">
  <h2>Your calendar, one link away.</h2>
  <p>Free to start. Set up your page in the time it takes to read this page.</p>
  <a href="/dashboard/signup.php" class="btn">Create your free page</a>
</div>

<footer>
  Already have a page? <a href="/dashboard/login.php">Log in</a>
</footer>

</body>
</html>
