document.addEventListener('DOMContentLoaded', function() {
  var searchBtn = document.getElementById('searchCars');
  if (!searchBtn) return;

  var savedParams = new URLSearchParams(window.location.search);
  var savedTrip = savedParams.get('trip');
  var savedPickup = savedParams.get('pickup');
  var savedDrop = savedParams.get('drop');
  var savedDate = savedParams.get('date');
  var savedTime = savedParams.get('time');

  if (savedDate) document.getElementById('date').value = savedDate;
  if (savedTime) document.getElementById('time').value = savedTime;
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
    'Dharwad', 'Uttara Kannada'
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

  var activeTripType = savedTrip || 'local';

  function updatePickupOptions(tripType) {
    activeTripType = tripType;
    var pickupValue = pickupSelect.value || savedPickup || '';
    var airportCities = [
      { city: 'Belagavi', label: 'Belagavi' },
      { city: 'Belagavi Airport', label: 'Belagavi Airport' }
    ];
    var pickupCities = tripType === 'airport'
      ? airportCities
      : [{ city: 'Belagavi', label: 'Belagavi' }];
    populateCitySelect(pickupSelect, pickupCities, pickupValue);
  }

  var cityOptions = requestedCities.map(function (city) {
    return { city: city, label: city };
  });
  updatePickupOptions(savedTrip || 'local');
  populateCitySelect(dropSelect, cityOptions, savedDrop);

  document.querySelectorAll('#meterTabs button').forEach(function (button) {
    button.addEventListener('click', function () {
      updatePickupOptions(button.dataset.tab);
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
    var tripType = (document.getElementById('tripType') || {}).value || 'local';
    var pickupInput = document.getElementById('pickup').value.trim();
    var dropInput = document.getElementById('drop').value.trim();
    var pickupDate = document.getElementById('date').value;
    var pickupTime = document.getElementById('time').value;
    var msgEl = document.getElementById('bookingMsg');

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
    if (!pickupDate || !/^\d{4}-\d{2}-\d{2}$/.test(pickupDate)) {
      showError('Please select a valid pickup date.');
      return;
    }
    if (pickupDate < new Date().toISOString().slice(0, 10)) {
      showError('Pickup date cannot be in the past.');
      return;
    }
    if (!pickupTime || !/^\d{2}:\d{2}$/.test(pickupTime)) {
      showError('Please select a valid pickup time.');
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
        pickup: pickupInput,
        drop: dropInput,
        date: pickupDate,
        time: pickupTime
      });

      window.location.href = 'outstation_search.html?' + params.toString();
    }).catch(function () {
      showError('Could not validate these cities. Please try again.');
    });
  });
});
