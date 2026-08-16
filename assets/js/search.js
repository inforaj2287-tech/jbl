document.addEventListener('DOMContentLoaded', function() {
  var searchBtn = document.getElementById('searchCars');
  if (!searchBtn) return;

  searchBtn.addEventListener('click', function() {
    var tripType = (document.getElementById('tripType') || {}).value || 'local';
    var pickupInput = document.getElementById('pickup').value.trim();
    var dropInput = document.getElementById('drop').value.trim();
    var pickupDate = document.getElementById('date').value;
    var pickupTime = document.getElementById('time').value;

    if (!pickupInput || !dropInput) {
      var msgEl = document.getElementById('bookingMsg');
      if (msgEl) { msgEl.className = 'form-msg err'; msgEl.textContent = 'Please enter pickup and drop locations.'; }
      return;
    }

    var params = new URLSearchParams({
      trip: tripType,
      pickup: pickupInput,
      drop: dropInput,
      date: pickupDate || new Date().toISOString().slice(0, 10),
      time: pickupTime || '09:00'
    });

    window.location.href = 'outstation_search.html?' + params.toString();
  });
});
