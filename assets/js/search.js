document.addEventListener('DOMContentLoaded', function() {
  var searchBtn = document.getElementById('searchCars');
  if (!searchBtn) return;

  var savedParams = new URLSearchParams(window.location.search);
  var savedTrip = savedParams.get('trip');
  var savedPickup = savedParams.get('pickup');
  var savedDrop = savedParams.get('drop');
  var savedDate = savedParams.get('date');
  var savedTime = savedParams.get('time');
  var savedName = savedParams.get('name');
  var savedPhone = savedParams.get('phone');
  var savedEmail = savedParams.get('email');
  var savedJourney = savedParams.get('journey');
  var savedReturnDate = savedParams.get('return_date');
  var savedReturnTime = savedParams.get('return_time');
  var savedContact = {};
  try {
    savedContact = JSON.parse(sessionStorage.getItem('jblBookingContact') || '{}');
  } catch (error) {
    console.error('Unable to restore passenger details for car search.', error);
  }

  if (savedDate) document.getElementById('date').value = savedDate;
  if (savedTime) document.getElementById('time').value = savedTime;
  if (savedName || savedContact.name) document.getElementById('passengerName').value = savedName || savedContact.name;
  if (savedPhone || savedContact.phone) document.getElementById('mobile').value = savedPhone || savedContact.phone;
  if (savedEmail || savedContact.email) document.getElementById('email').value = savedEmail || savedContact.email;
  if (savedJourney) {
    var journeyLabel = savedJourney.toLowerCase() === 'round_trip' || savedJourney.toLowerCase() === 'round trip' ? 'Round Trip' : 'One Way';
    var savedJourneyRadio = document.querySelector('input[name="journey_type"][value="' + journeyLabel + '"]');
    if (savedJourneyRadio) savedJourneyRadio.checked = true;
  }
  if (savedReturnDate) document.getElementById('returnDate').value = savedReturnDate;
  if (savedReturnTime) document.getElementById('returnTime').value = savedReturnTime;
  if (savedTrip) {
    document.getElementById('tripType').value = savedTrip;
    document.querySelectorAll('#meterTabs button').forEach(function (button) {
      button.classList.toggle('active', button.dataset.tab === savedTrip);
    });
  }

  var pickupSelect = document.getElementById('pickup');
  var dropSelect = document.getElementById('drop');
  var requestedCities = [
    'Belagavi', 'Hubballi', 'Pune', 'Goa', 'Bengaluru', 'Mumbai', 'Delhi',
    'Jaipur', 'Kolkata', 'Chennai', 'Hyderabad', 'Visakhapatnam',
    'Bengaluru International Airport', 'Chandigarh', 'Ahmedabad', 'Surat',
    'Lucknow', 'Indore', 'Nagpur', 'Coimbatore', 'Thiruvananthapuram',
    'Gandhinagar', 'Rajkot', 'Madurai', 'Kochi', 'Mysuru', 'Shivamogga',
    'Kolar', 'Mangaluru', 'Raichur', 'Bidar', 'Kalaburagi', 'Vijayanagara',
    'Hassan', 'Davangere', 'Tumakuru', 'Chitradurga', 'Bagalkot', 'Gadag',
    'Dharwad', 'Uttara Kannada', 'Gokarna', 'Dandeli', 'Hampi', 'Tirupati',
    'Coorg', 'Belagavi Airport'
  ];
  var cityAliases = {
    hubli: 'Hubballi',
    bengaluru: 'Bengaluru',
    belgaum: 'Belagavi'
  };

  function restoreCity(select, savedCity) {
    if (!savedCity) return;
    var canonicalCity = cityAliases[savedCity.toLowerCase()] || savedCity;
    var matchingOption = Array.prototype.find.call(select.options, function (option) {
      return option.value.toLowerCase() === canonicalCity.toLowerCase();
    });
    if (matchingOption) select.value = matchingOption.value;
  }

  function populateCitySelect(select, cities, savedCity) {
    select.replaceChildren(new Option('Choose a city', ''));
    cities.forEach(function (city) {
      select.add(new Option(city.label, city.city));
    });
    restoreCity(select, savedCity);
  }

  var activeTripType = savedTrip || document.getElementById('tripType').value || 'outstation';

  function updatePickupOptions(tripType) {
    activeTripType = tripType;
    var pickupValue = pickupSelect.value || savedPickup || '';
    var pickupCities = cityOptions
      .filter(function (city) { return city.city.toLowerCase() === 'belagavi'; })
      .map(function (city) { return { city: city.city, label: 'Belagavi' }; });
    if (!pickupCities.length) pickupCities.push({ city: 'Belagavi', label: 'Belagavi' });
    if (tripType === 'airport') {
      pickupCities.push({ city: 'Belagavi Airport', label: 'Belagavi Airport' });
    }
    if (!pickupCities.some(function (city) { return city.city.toLowerCase() === pickupValue.toLowerCase(); })) {
      pickupValue = 'Belagavi';
    }
    populateCitySelect(pickupSelect, pickupCities, pickupValue);
  }

  var cityOptions = requestedCities.map(function (city) {
    return { city: city, label: city };
  });
  updatePickupOptions(activeTripType);
  populateCitySelect(dropSelect, cityOptions, savedDrop);

  document.querySelectorAll('#meterTabs button').forEach(function (button) {
    button.addEventListener('click', function () {
      updatePickupOptions(button.dataset.tab);
    });
  });
  document.querySelectorAll('.booking-tabs [data-tab], [data-book-tab]').forEach(function (button) {
    button.addEventListener('click', function () {
      updatePickupOptions(button.dataset.tab || button.dataset.bookTab);
    });
  });

  fetch('api/locations.php?list=1')
    .then(function (response) {
      if (!response.ok) throw new Error('City list could not load.');
      return response.json();
    })
    .then(function (json) {
      if (!json.success) throw new Error(json.message || 'City list could not load.');
      var cityMap = new Map();
      requestedCities.forEach(function (city) {
        cityMap.set(city.toLowerCase(), { city: city, label: city });
      });
      (json.data || []).forEach(function (city) {
        cityMap.set(city.city.toLowerCase(), city);
      });
      cityOptions = Array.from(cityMap.values());
      updatePickupOptions(activeTripType);
      populateCitySelect(dropSelect, cityOptions, savedDrop);
    })
    .catch(function (error) {
      console.error('Unable to load additional city options.', error);
    });

  searchBtn.addEventListener('click', function() {
    var tripType = (document.getElementById('tripType') || {}).value || 'outstation';
    var wasLocal = tripType === 'local';
    var pickupInput = document.getElementById('pickup').value.trim();
    var dropInput = document.getElementById('drop').value.trim();
    var pickupDate = document.getElementById('date').value;
    var pickupTime = document.getElementById('time').value;
    var msgEl = document.getElementById('bookingMsg');
    var bookingContactDetails = document.getElementById('bookingContactDetails');

    function showError(message) {
      if (msgEl) { msgEl.className = 'form-msg err'; msgEl.textContent = message; }
    }

    if (!pickupInput || !dropInput) {
      showError('Please select pickup and drop cities.');
      return;
    }
    if (pickupInput.toLowerCase() === dropInput.toLowerCase()) {
      showError('Pickup and drop locations must be different.');
      return;
    }
    if (wasLocal && /airport/i.test(pickupInput + ' ' + dropInput)) {
      tripType = 'airport';
    }
    if (!pickupDate || !/^\d{4}-\d{2}-\d{2}$/.test(pickupDate)) {
      showError('Please select a valid pickup date.');
      return;
    }
    if (pickupDate < document.getElementById('date').min) {
      showError('Pickup date cannot be in the past.');
      return;
    }
    if (!pickupTime || !/^\d{2}:\d{2}$/.test(pickupTime)) {
      showError('Please select a valid pickup time.');
      return;
    }
    if (bookingContactDetails) bookingContactDetails.open = true;
    if (!document.getElementById('bookingForm').reportValidity()) return;

    var selectedJourney = document.querySelector('input[name="journey_type"]:checked');
    var journeyType = wasLocal ? 'One Way' : (selectedJourney ? selectedJourney.value : 'One Way');
    var returnDate = document.getElementById('returnDate').value;
    var returnTime = document.getElementById('returnTime').value;
    if (['outstation', 'airport'].indexOf(tripType) !== -1 && journeyType === 'Round Trip' && returnDate < pickupDate) {
      showError('Return date must be on or after the pickup date.');
      return;
    }
    if (['outstation', 'airport'].indexOf(tripType) !== -1 && journeyType === 'Round Trip'
      && returnDate === pickupDate && returnTime <= pickupTime) {
      showError('Return time must be after pickup time for a same-day return.');
      return;
    }

    Promise.all([pickupInput, dropInput].map(function (location) {
      return fetch('api/locations.php?resolve=' + encodeURIComponent(location))
        .then(function (response) {
          if (!response.ok) throw new Error('Location validation failed.');
          return response.json();
        })
        .then(function (json) {
          if (!json.success) throw new Error(json.message || 'Location validation failed.');
          return json.data;
        });
    })).then(function (locations) {
      if (!locations[0] || !locations[1]) {
        showError('Please choose pickup and drop cities from the suggestions.');
        return;
      }

      var params = new URLSearchParams({
        trip: tripType,
        journey: ['outstation', 'airport'].indexOf(tripType) !== -1 ? (journeyType === 'Round Trip' ? 'round_trip' : 'one_way') : 'one_way',
        pickup: pickupInput,
        drop: dropInput,
        date: pickupDate,
        time: pickupTime,
        return_date: ['outstation', 'airport'].indexOf(tripType) !== -1 && journeyType === 'Round Trip' ? returnDate : '',
        return_time: ['outstation', 'airport'].indexOf(tripType) !== -1 && journeyType === 'Round Trip' ? returnTime : ''
      });

      try {
        sessionStorage.setItem('jblBookingContact', JSON.stringify({
          name: document.getElementById('passengerName').value.trim(),
          phone: document.getElementById('mobile').value.trim(),
          email: document.getElementById('email').value.trim()
        }));
        window.location.href = 'outstation_search.html?' + params.toString();
      } catch (error) {
        console.error('Unable to pass passenger details to car search.', error);
        showError('Could not continue to car search. Please use Book on WhatsApp or try again.');
      }
    }).catch(function () {
      showError('Could not validate these cities. Please try again.');
    });
  });
});
