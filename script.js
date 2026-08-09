document.addEventListener('DOMContentLoaded', function () {

  /* ---------------- mobile nav ---------------- */
  var navToggle = document.getElementById('navToggle');
  var navLinks = document.getElementById('navLinks');
  navToggle.addEventListener('click', function () {
    var open = navLinks.style.display === 'flex';
    navLinks.style.display = open ? 'none' : 'flex';
    navLinks.style.cssText += 'flex-direction:column; position:absolute; top:76px; left:0; right:0; background:#12161d; padding:20px 24px; gap:18px;';
  });

  /* ---------------- booking tabs ---------------- */
  var tabs = document.querySelectorAll('#meterTabs button');
  var tripType = document.getElementById('tripType');
  tabs.forEach(function (btn) {
    btn.addEventListener('click', function () {
      tabs.forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      tripType.value = btn.dataset.tab;
    });
  });

  /* ---------------- fare meter ---------------- */
  var RATES = { hatchback: 9.5, sedan: 10.5, suv: 16.0 };
  var kmInput = document.getElementById('km');
  var cabSelect = document.getElementById('cabType');
  var fareDigits = document.getElementById('fareDigits');

  function calcFare() {
    var km = parseFloat(kmInput.value) || 0;
    var rate = RATES[cabSelect.value] || RATES.hatchback;
    var fare = Math.round(km * rate);
    animateDigits(fare);
  }

  function animateDigits(target) {
    var current = parseInt(fareDigits.textContent.replace(/\D/g, ''), 10) || 0;
    var steps = 12;
    var stepVal = (target - current) / steps;
    var i = 0;
    clearInterval(fareDigits._timer);
    fareDigits._timer = setInterval(function () {
      i++;
      var val = Math.round(current + stepVal * i);
      fareDigits.textContent = String(val).padStart(4, '0');
      if (i >= steps) {
        fareDigits.textContent = String(target).padStart(4, '0');
        clearInterval(fareDigits._timer);
      }
    }, 22);
  }

  kmInput.addEventListener('input', calcFare);
  cabSelect.addEventListener('change', calcFare);
  calcFare();

  /* ---------------- FAQ accordion ---------------- */
  document.querySelectorAll('.faq-q').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq-item');
      var wasOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(function (i) { i.classList.remove('open'); });
      if (!wasOpen) item.classList.add('open');
    });
  });

  /* ---------------- testimonial carousel ---------------- */
  var track = document.getElementById('testiTrack');
  var cardWidth = 360;
  document.getElementById('testiNext').addEventListener('click', function () {
    track.scrollBy({ left: cardWidth, behavior: 'smooth' });
  });
  document.getElementById('testiPrev').addEventListener('click', function () {
    track.scrollBy({ left: -cardWidth, behavior: 'smooth' });
  });

  /* ---------------- about odometer tick ---------------- */
  var aboutMeter = document.getElementById('aboutMeter');
  if (aboutMeter) {
    var base = 240318;
    setInterval(function () {
      base += Math.floor(Math.random() * 2);
      aboutMeter.textContent = base.toLocaleString('en-IN');
    }, 4000);
  }

  /* ---------------- booking form -> booking.php ---------------- */
  var bookingForm = document.getElementById('bookingForm');
  var bookingMsg = document.getElementById('bookingMsg');
  bookingForm.addEventListener('submit', function (e) {
    e.preventDefault();
    submitForm(bookingForm, 'booking.php', bookingMsg, 'Booking confirmed — a driver will be assigned shortly. We\'ll text you the details.');
  });

  /* ---------------- contact form -> contact.php ---------------- */
  var contactForm = document.getElementById('contactForm');
  var contactMsg = document.getElementById('contactMsg');
  contactForm.addEventListener('submit', function (e) {
    e.preventDefault();
    submitForm(contactForm, 'contact.php', contactMsg, 'Message sent — our team will get back to you shortly.');
  });

  function submitForm(form, endpoint, msgEl, successText) {
    var data = new FormData(form);
    msgEl.className = 'form-msg';
    msgEl.textContent = '';

    fetch(endpoint, { method: 'POST', body: data })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (json.success) {
          msgEl.textContent = successText;
          msgEl.className = 'form-msg ok';
          form.reset();
          calcFare();
        } else {
          msgEl.textContent = json.message || 'Something went wrong. Please try again.';
          msgEl.className = 'form-msg err';
        }
      })
      .catch(function () {
        // PHP backend not reachable (e.g. static preview without a PHP server)
        msgEl.textContent = 'Could not reach the server. If you are previewing this file directly, run it through a PHP server (see README).';
        msgEl.className = 'form-msg err';
      });
  }

});
