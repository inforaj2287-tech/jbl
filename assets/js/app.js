document.addEventListener('DOMContentLoaded', function () {
  var navToggle = document.getElementById('navToggle');
  var navLinks = document.getElementById('navLinks');

  function closeNavigation() {
    if (!navToggle || !navLinks) return;
    navToggle.setAttribute('aria-expanded', 'false');
    navToggle.setAttribute('aria-label', 'Open navigation');
    navLinks.classList.remove('is-open');
  }

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function () {
      var isOpen = navToggle.getAttribute('aria-expanded') === 'true';
      navToggle.setAttribute('aria-expanded', String(!isOpen));
      navToggle.setAttribute('aria-label', isOpen ? 'Open navigation' : 'Close navigation');
      navLinks.classList.toggle('is-open', !isOpen);
    });
    navLinks.addEventListener('click', function (event) {
      if (event.target.closest('a')) closeNavigation();
    });
    window.addEventListener('resize', function () {
      if (window.innerWidth > 960) closeNavigation();
    });
  }

  var tabs = Array.prototype.slice.call(document.querySelectorAll('.booking-tabs [data-tab]'));
  var bookingType = document.getElementById('tripType');
  var returnDateField = document.getElementById('returnDateField');
  var journeyType = document.getElementById('journeyType');
  var returnDateInput = document.getElementById('returnDate');
  var pickupDateInput = document.getElementById('date');

  function setBookingType(type) {
    if (!tabs.length || !bookingType) return;
    var validType = ['local', 'outstation', 'package'].indexOf(type) !== -1 ? type : 'local';
    bookingType.value = validType;
    tabs.forEach(function (tab) {
      var selected = tab.dataset.tab === validType;
      tab.classList.toggle('active', selected);
      tab.setAttribute('aria-selected', String(selected));
      if (selected) document.getElementById('bookingPanel').setAttribute('aria-labelledby', tab.id);
    });
    var searchCarsButton = document.getElementById('searchCars');
    if (searchCarsButton) {
      searchCarsButton.hidden = validType === 'package';
      searchCarsButton.parentElement.classList.toggle('is-single', validType === 'package');
    }

    document.querySelectorAll('[data-show]').forEach(function (field) {
      var shown = field.dataset.show.split(',').indexOf(validType) !== -1;
      field.hidden = !shown;
      field.querySelectorAll('input,select,textarea').forEach(function (input) {
        if (input.dataset.initialRequired === undefined) {
          input.dataset.initialRequired = String(input.required);
        }
        input.required = shown && input.dataset.initialRequired === 'true';
      });
    });
    updateReturnDate();
  }

  function updateReturnDate() {
    if (!returnDateField || !journeyType || !bookingType) return;
    var visible = bookingType.value === 'outstation' && journeyType.value === 'Round Trip';
    returnDateField.hidden = !visible;
    var input = returnDateField.querySelector('input');
    input.required = visible;
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      setBookingType(tab.dataset.tab);
    });
  });
  if (journeyType) journeyType.addEventListener('change', updateReturnDate);
  if (returnDateInput) {
    returnDateInput.addEventListener('input', function () {
      returnDateInput.setCustomValidity('');
    });
  }
  if (pickupDateInput && returnDateInput) {
    pickupDateInput.addEventListener('input', function () {
      returnDateInput.setCustomValidity('');
      returnDateInput.min = pickupDateInput.value || returnDateInput.min;
    });
  }

  document.querySelectorAll('[data-book-tab]').forEach(function (link) {
    link.addEventListener('click', function () {
      var targetTab = link.dataset.bookTab;
      if (targetTab) setBookingType(targetTab);
      if (link.dataset.airportPickup === 'true') {
        var pickup = document.getElementById('pickup');
        if (pickup) pickup.value = 'Belagavi Airport';
      }
    });
  });
  document.querySelectorAll('[data-destination]').forEach(function (link) {
    link.addEventListener('click', function () {
      setBookingType('package');
      var destination = document.getElementById('destination');
      if (destination) destination.value = link.dataset.destination;
    });
  });

  if (tabs.length) {
    setBookingType(bookingType ? bookingType.value : 'local');
    var today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
    var minDate = today.toISOString().slice(0, 10);
    ['date', 'returnDate'].forEach(function (id) {
      var dateInput = document.getElementById(id);
      if (dateInput) dateInput.min = minDate;
    });
  }

  document.querySelectorAll('.faq-q').forEach(function (button) {
    button.addEventListener('click', function () {
      var item = button.closest('.faq-item');
      var wasOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(function (faqItem) {
        faqItem.classList.remove('open');
        var question = faqItem.querySelector('.faq-q');
        if (question) question.setAttribute('aria-expanded', 'false');
      });
      if (!wasOpen) {
        item.classList.add('open');
        button.setAttribute('aria-expanded', 'true');
      }
    });
  });

  var bookingForm = document.getElementById('bookingForm');
  var bookingMsg = document.getElementById('bookingMsg');
  if (bookingForm && bookingMsg) {
    bookingForm.addEventListener('submit', function (event) {
      event.preventDefault();
      bookingMsg.className = 'form-msg';
      bookingMsg.textContent = '';

      if (!bookingForm.reportValidity()) return;

      var data = new FormData(bookingForm);
      var service = data.get('service');
      var isAirport = service === 'local' && /airport/i.test(String(data.get('pickup')) + ' ' + String(data.get('drop')));
      if (service === 'outstation' && data.get('journey_type') === 'Round Trip' && data.get('return_date') < data.get('date')) {
        returnDateInput.setCustomValidity('Return date must be on or after the pickup date.');
        returnDateInput.reportValidity();
        return;
      }
      var parts = ['Hello JB Travels, I would like to enquire about a taxi booking.', ''];
      if (service === 'package') {
        parts.push(
          'Service: Tour Package',
          'Pickup: ' + data.get('pickup'),
          'Destination: ' + data.get('destination'),
          'Travel Date: ' + data.get('date'),
          'Number of Days: ' + data.get('days'),
          'Passengers: ' + data.get('passengers')
        );
      } else {
        parts.push(
          'Service: ' + (service === 'outstation' ? 'Outstation Taxi' : (isAirport ? 'Airport Taxi' : 'Local Taxi')),
          'Pickup: ' + data.get('pickup'),
          'Drop: ' + data.get('drop'),
          'Travel Date: ' + data.get('date'),
          'Pickup Time: ' + data.get('time')
        );
        if (service === 'outstation') {
          parts.push('Journey Type: ' + data.get('journey_type'));
          if (data.get('journey_type') === 'Round Trip') parts.push('Return Date: ' + data.get('return_date'));
        }
      }
      if (data.get('preferred_vehicle')) parts.push('Preferred Vehicle: ' + data.get('preferred_vehicle'));
      parts.push('', 'Passenger Name: ' + data.get('name'), 'Mobile: ' + data.get('phone'));
      if (data.get('email')) parts.push('Email: ' + data.get('email'));
      parts.push('', 'Please confirm availability and share a quotation.');

      var whatsappUrl = 'https://wa.me/919535608945?text=' + encodeURIComponent(parts.join('\n'));
      var message = document.createElement('span');
      message.textContent = 'Your enquiry is ready. Send it to JB Travels in WhatsApp to request your booking. ';
      var sendLink = document.createElement('a');
      sendLink.href = whatsappUrl;
      sendLink.target = '_blank';
      sendLink.rel = 'noopener noreferrer';
      sendLink.textContent = 'Open WhatsApp';
      message.appendChild(sendLink);
      bookingMsg.appendChild(message);
      bookingMsg.className = 'form-msg ok';
      window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
    });
  }

  var fleetGrid = document.getElementById('fleetGrid');
  if (fleetGrid) {
    fetch('api/fleet.php')
      .then(function (response) {
        if (!response.ok) throw new Error('Vehicle options could not be loaded.');
        return response.json();
      })
      .then(function (result) {
        if (!result.success || !Array.isArray(result.data)) throw new Error(result.message || 'Vehicle options could not be loaded.');
        if (!result.data.length) {
          showFleetMessage('No vehicles are currently listed. Contact dispatch to ask about available options.');
          return;
        }
        fleetGrid.replaceChildren();
        result.data.forEach(function (car) {
          fleetGrid.appendChild(createFleetCard(car));
        });
      })
      .catch(function (error) {
        console.error('Unable to load current fleet options.', error);
        showFleetMessage('Current vehicle options could not be loaded. Please contact dispatch to check availability.', true);
      });
  }

  function createFleetCard(car) {
    var card = document.createElement('article');
    card.className = 'fleet-card';
    var image = document.createElement('img');
    var category = String(car.category || '').toUpperCase();
    image.src = car.image || fleetImage(category);
    image.alt = car.name ? car.name + ' taxi' : 'JB Travels vehicle';
    image.loading = 'lazy';
    image.addEventListener('error', function () {
      image.src = fleetImage(category);
      image.onerror = null;
    });

    var body = document.createElement('div');
    body.className = 'fleet-card-body';
    var title = document.createElement('h3');
    title.textContent = car.name || categoryLabel(category);
    var subtitle = document.createElement('p');
    subtitle.textContent = categoryLabel(category);
    var specs = document.createElement('ul');
    specs.className = 'fleet-specs';
    [
      Number(car.seats) + ' passengers',
      Number(car.luggage_capacity) + ' luggage bags',
      car.ac_available ? 'AC available' : 'AC not listed'
    ].forEach(function (label) {
      var item = document.createElement('li');
      item.textContent = label;
      specs.appendChild(item);
    });
    var link = document.createElement('a');
    link.className = 'btn btn-yellow';
    link.href = '#book';
    link.textContent = 'Book Now →';
    link.addEventListener('click', function () {
      setBookingType('local');
      var preferredVehicle = document.getElementById('preferredVehicle');
      if (preferredVehicle) preferredVehicle.value = car.name || '';
      var pickup = document.getElementById('pickup');
      if (pickup) pickup.focus();
    });
    body.appendChild(title);
    body.appendChild(subtitle);
    body.appendChild(specs);
    body.appendChild(link);
    card.appendChild(image);
    card.appendChild(body);
    return card;
  }

  function fleetImage(category) {
    var images = {
      HATCHBACK: 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?auto=format&fit=crop&w=800&q=75',
      SEDAN: 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?auto=format&fit=crop&w=800&q=75',
      SUV: 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=800&q=75',
      MUV: 'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?auto=format&fit=crop&w=800&q=75',
      PREMIUM: 'https://images.unsplash.com/photo-1555215695-3004980ad54e?auto=format&fit=crop&w=800&q=75'
    };
    return images[category] || images.SEDAN;
  }

  function categoryLabel(category) {
    var labels = { HATCHBACK: 'Hatchback', SEDAN: 'Sedan', SUV: 'SUV', MUV: 'MUV', PREMIUM: 'Premium' };
    return labels[category] || 'Taxi';
  }

  function showFleetMessage(text, isError) {
    var message = document.createElement('p');
    message.className = 'fleet-state' + (isError ? ' error' : '');
    message.textContent = text + ' ';
    var link = document.createElement('a');
    link.className = 'text-link';
    link.href = 'tel:+919740309999';
    link.textContent = 'Call +91 97403 09999';
    message.appendChild(link);
    fleetGrid.replaceChildren(message);
  }

  var contactForm = document.getElementById('contactForm');
  var contactMsg = document.getElementById('contactMsg');
  if (contactForm && contactMsg) {
    contactForm.addEventListener('submit', function (event) {
      event.preventDefault();
      submitForm(contactForm, contactForm.dataset.endpoint || 'api/contact.php', contactMsg);
    });
  }

  var currentYear = document.getElementById('currentYear');
  if (currentYear) currentYear.textContent = String(new Date().getFullYear());

  function submitForm(form, endpoint, messageElement) {
    if (!form.reportValidity()) return;
    var data = new FormData(form);
    messageElement.className = 'form-msg';
    messageElement.textContent = '';
    fetch(endpoint, { method: 'POST', body: data })
      .then(function (response) {
        if (!response.ok) throw new Error('The server could not process your message.');
        return response.json();
      })
      .then(function (result) {
        if (!result.success) throw new Error(result.message || 'Your message could not be sent.');
        messageElement.textContent = result.message || 'Your message has been received.';
        messageElement.className = 'form-msg ok';
        form.reset();
      })
      .catch(function (error) {
        messageElement.textContent = error.message || 'Could not reach the server. Please try again or call +91 97403 09999.';
        messageElement.className = 'form-msg err';
      });
  }
});
