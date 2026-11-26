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
  var returnTimeField = document.getElementById('returnTimeField');
  var journeyRadios = Array.prototype.slice.call(document.querySelectorAll('input[name="journey_type"]'));
  var returnDateInput = document.getElementById('returnDate');
  var returnTimeInput = document.getElementById('returnTime');
  var pickupDateInput = document.getElementById('date');
  var bookingContactDetails = document.getElementById('bookingContactDetails');

  function setBookingType(type) {
    if (!tabs.length || !bookingType) return;
    var validType = ['local', 'outstation', 'airport', 'package'].indexOf(type) !== -1 ? type : 'outstation';
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
    if (!returnDateField || !returnTimeField || !bookingType) return;
    var visible = ['outstation', 'airport'].indexOf(bookingType.value) !== -1 && getJourneyType() === 'Round Trip';
    returnDateField.hidden = !visible;
    returnTimeField.hidden = !visible;
    if (returnDateInput) returnDateInput.required = visible;
    if (returnTimeInput) returnTimeInput.required = visible;
  }

  function getJourneyType() {
    var selected = document.querySelector('input[name="journey_type"]:checked');
    return selected ? selected.value : 'One Way';
  }

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      setBookingType(tab.dataset.tab);
    });
  });
  journeyRadios.forEach(function (radio) {
    radio.addEventListener('change', updateReturnDate);
  });
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

      if (bookingContactDetails) bookingContactDetails.open = true;
      if (!bookingForm.reportValidity()) return;

      var data = new FormData(bookingForm);
      var service = data.get('service');
      var isAirport = service === 'local' && /airport/i.test(String(data.get('pickup')) + ' ' + String(data.get('drop')));
      var journeyType = getJourneyType();
      var hasReturnTrip = ['outstation', 'airport'].indexOf(service) !== -1 && journeyType === 'Round Trip';
      if (hasReturnTrip && data.get('return_date') < data.get('date')) {
        returnDateInput.setCustomValidity('Return date must be on or after the pickup date.');
        returnDateInput.reportValidity();
        return;
      }
      var parts = ['Hello JB Travels, I would like to enquire about a car booking.', ''];
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
          'Service: ' + (service === 'outstation' ? 'Outstation Car' : ((service === 'airport' || isAirport) ? 'Airport Car' : 'Local Car')),
          'Pickup: ' + data.get('pickup'),
          'Drop: ' + data.get('drop'),
          'Travel Date: ' + data.get('date'),
          'Pickup Time: ' + data.get('time')
        );
        if (['outstation', 'airport'].indexOf(service) !== -1) {
          parts.push('Journey Type: ' + journeyType);
          if (hasReturnTrip) {
            parts.push('Return Date: ' + data.get('return_date'));
            parts.push('Return Time: ' + data.get('return_time'));
          }
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
    var fallbackImage = fleetImage(category, car.name);
    image.src = fallbackImage;
    image.alt = car.name ? car.name + ' taxi' : 'JB Travels vehicle';
    image.loading = 'lazy';
    image.addEventListener('error', function () {
      image.src = fallbackImage;
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

  function fleetImage(category, carName) {
    var images = {
      HATCHBACK: 'assets/images/fleet-hatchback.jpg',
      SEDAN: 'assets/images/fleet-sedan.jpg',
      SUV: 'assets/images/fleet-suv.jpg',
      MUV: 'assets/images/fleet-muv.jpg',
      PREMIUM: 'assets/images/fleet-premium.jpg'
    };
    var model = String(carName || '').toLowerCase();
    if (/innova|carens|ertiga/.test(model)) return images.MUV;
    if (/dzire/.test(model)) return images.SEDAN;
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
      submitForm(contactForm, contactMsg);
    });
  }

  var currentYear = document.getElementById('currentYear');
  if (currentYear) currentYear.textContent = String(new Date().getFullYear());

  function submitForm(form, messageElement) {
    if (!form.reportValidity()) return;
    var data = new FormData(form);
    var enquiry = [
      'Hello JB Travels, I would like to send an enquiry.',
      '',
      'Name: ' + data.get('name'),
      'Phone: ' + data.get('phone'),
      'Email: ' + data.get('email'),
      'Message: ' + data.get('message')
    ].join('\n');
    var whatsappUrl = 'https://wa.me/919535608945?text=' + encodeURIComponent(enquiry);

    messageElement.className = 'form-msg';
    messageElement.textContent = '';
    messageElement.className = 'form-msg ok';
    messageElement.appendChild(document.createTextNode('WhatsApp is ready with your enquiry. Review it and press Send. '));
    var sendLink = document.createElement('a');
    sendLink.href = whatsappUrl;
    sendLink.target = '_blank';
    sendLink.rel = 'noopener noreferrer';
    sendLink.textContent = 'Open WhatsApp';
    messageElement.appendChild(sendLink);
    window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
  }
});
