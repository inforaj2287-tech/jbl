document.addEventListener('DOMContentLoaded', function() {
  var searchBtn = document.getElementById('searchCars');
  if (!searchBtn) return;

  var savedParams = new URLSearchParams(window.location.search);
  var savedTrip = savedParams.get('trip');
  var savedPickup = savedParams.get('pickup');
  var savedDrop = savedParams.get('drop');
  var savedDate = savedParams.get('date');
  var savedTime = savedParams.get('time');

  if (savedPickup) document.getElementById('pickup').value = savedPickup;
  if (savedDrop) document.getElementById('drop').value = savedDrop;
  if (savedDate) document.getElementById('date').value = savedDate;
  if (savedTime) document.getElementById('time').value = savedTime;
  if (savedTrip) {
    document.getElementById('tripType').value = savedTrip;
    document.querySelectorAll('#meterTabs button').forEach(function (button) {
      button.classList.toggle('active', button.dataset.tab === savedTrip);
    });
  }

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
      showError('Please enter pickup and drop locations.');
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

    var params = new URLSearchParams({
      trip: tripType,
      pickup: pickupInput,
      drop: dropInput,
      date: pickupDate,
      time: pickupTime
    });

    window.location.href = 'outstation_search.html?' + params.toString();
  });
});
